<?php
// One-time backfill: notify currently eligible students of existing scholarships (>30% match).

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/MatchingEngine.php';
require_once __DIR__ . '/SmsService.php';

$conn = getDbConnection();
if (!$conn || $conn->connect_error) {
    fwrite(STDERR, "Database connection failed.\n");
    exit(1);
}

$studentSql = "SELECT studentID, firstName, lastName, phone, contactNo
               FROM student
               WHERE status = 'active'";

$res = $conn->query($studentSql);
if (!$res) {
    fwrite(STDERR, "Unable to load students.\n");
    $conn->close();
    exit(1);
}

$sent = 0;
$skipped = 0;
$failed = 0;

while ($student = $res->fetch_assoc()) {
    $studentId = (int) ($student['studentID'] ?? 0);
    if ($studentId <= 0) {
        $skipped++;
        continue;
    }

    $phone = trim((string) (($student['phone'] ?? '') !== '' ? $student['phone'] : ($student['contactNo'] ?? '')));
    if ($phone === '') {
        $skipped++;
        continue;
    }

    $name = trim((string) (($student['firstName'] ?? '') . ' ' . ($student['lastName'] ?? '')));
    if ($name === '') {
        $name = 'Student';
    }

    $matches = MatchingEngine::getMatches($studentId);
    $eligible = [];
    foreach ($matches as $m) {
        $score = (int) ($m['match'] ?? 0);
        if ($score <= 30) {
            continue;
        }
        $eligible[] = $m;
    }

    if (empty($eligible)) {
        $skipped++;
        continue;
    }

    $parts = [];
    $position = 1;
    foreach (array_slice($eligible, 0, 3) as $m) {
        $title = trim((string) ($m['title'] ?? 'Scholarship'));
        $reasonText = '';
        if (!empty($m['reasons']) && is_array($m['reasons'])) {
            $reasons = array_slice(array_values($m['reasons']), 0, 2);
            if (!empty($reasons)) {
                $reasonText = ' why: ' . implode(', ', $reasons);
            }
        }
        $parts[] = $position . ') ' . $title . $reasonText;
        $position++;
    }

    $message = "ScholarConnect [Backfill Matches]: Hi {$name}, your current top scholarship matches are " . implode(' | ', $parts) . ". Log in to apply.";
    if (strlen($message) > 480) {
        $message = substr($message, 0, 477) . '...';
    }

    $result = SmsService::sendSms($phone, $message);
    if (!empty($result['status'])) {
        $sent++;
    } else {
        $failed++;
    }
}

$res->free();
$conn->close();

echo "Backfill complete. Sent={$sent}, Skipped={$skipped}, Failed={$failed}\n";
