<?php
session_start();

require '../config.php';
require_once 'security.php';
require_once 'SmsService.php';
require_once 'MatchingEngine.php';

require_login(3);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../signatory/tempSigHome.php?sms_status=err&sms_message=' . urlencode('Invalid request method.'));
    exit;
}

$currentUserID = (int) ($_SESSION['currentUserID'] ?? 0);
$studentSelection = trim((string) ($_POST['student_id'] ?? ''));
$messageTemplate = trim((string) ($_POST['message_template'] ?? 'deadline_reminder'));

if ($currentUserID <= 0 || $studentSelection === '' || $messageTemplate === '') {
    header('Location: ../signatory/tempSigHome.php?sms_status=err&sms_message=' . urlencode('Missing required SMS fields.'));
    exit;
}

$conn = getDbConnection();
if (!$conn || $conn->connect_error) {
    header('Location: ../signatory/tempSigHome.php?sms_status=err&sms_message=' . urlencode('Database connection failed.'));
    exit;
}

$validTemplates = [
    'deadline_reminder',
    'profile_completion',
    'status_update',
    'resend_matches'
];

if (!in_array($messageTemplate, $validTemplates, true)) {
    $conn->close();
    header('Location: ../signatory/tempSigHome.php?sms_status=err&sms_message=' . urlencode('Invalid SMS template selected.'));
    exit;
}

$recipientSql = "SELECT DISTINCT ST.studentID, ST.firstName, ST.lastName, ST.phone, ST.contactNo
                 FROM application A
                 JOIN student ST ON ST.studentID = A.studentID
                 WHERE A.sigID = ?";

$params = [$currentUserID];
$types = 'i';

if (strtolower($studentSelection) !== 'all') {
    $studentID = (int) $studentSelection;
    if ($studentID <= 0) {
        $conn->close();
        header('Location: ../signatory/tempSigHome.php?sms_status=err&sms_message=' . urlencode('Invalid student selection.'));
        exit;
    }
    $recipientSql .= " AND ST.studentID = ?";
    $params[] = $studentID;
    $types .= 'i';
}

$recipientStmt = $conn->prepare($recipientSql);
if (!$recipientStmt) {
    $conn->close();
    header('Location: ../signatory/tempSigHome.php?sms_status=err&sms_message=' . urlencode('Unable to prepare SMS recipient lookup.'));
    exit;
}

if ($types === 'ii') {
    $recipientStmt->bind_param($types, $params[0], $params[1]);
} else {
    $recipientStmt->bind_param($types, $params[0]);
}

$recipientStmt->execute();
$recipientRes = $recipientStmt->get_result();
$recipients = [];
while ($recipientRes && ($row = $recipientRes->fetch_assoc())) {
    $phone = trim((string) (($row['phone'] ?? '') !== '' ? ($row['phone'] ?? '') : ($row['contactNo'] ?? '')));
    if ($phone === '') {
        continue;
    }

    $name = trim((string) (($row['firstName'] ?? '') . ' ' . ($row['lastName'] ?? '')));
    if ($name === '') {
        $name = 'Student';
    }

    $recipients[] = [
        'studentID' => (int) $row['studentID'],
        'name' => $name,
        'phone' => $phone
    ];
}
$recipientStmt->close();

if (empty($recipients)) {
    $conn->close();
    header('Location: ../signatory/tempSigHome.php?sms_status=err&sms_message=' . urlencode('No eligible recipients with mobile numbers found.'));
    exit;
}

$sigScholarshipIds = [];
if ($messageTemplate === 'resend_matches') {
    $sigScholarshipStmt = $conn->prepare('SELECT scholarshipID FROM scholarship WHERE sigID = ?');
    if ($sigScholarshipStmt) {
        $sigScholarshipStmt->bind_param('i', $currentUserID);
        $sigScholarshipStmt->execute();
        $sigScholarshipRes = $sigScholarshipStmt->get_result();
        while ($sigScholarshipRes && ($sigRow = $sigScholarshipRes->fetch_assoc())) {
            $sigScholarshipIds[(int) $sigRow['scholarshipID']] = true;
        }
        $sigScholarshipStmt->close();
    }
}

$conn->close();

$sentCount = 0;
$failedCount = 0;

foreach ($recipients as $recipient) {
    $studentName = $recipient['name'];

    if ($messageTemplate === 'deadline_reminder') {
        $finalMessage = "ScholarConnect [Deadline Reminder]: Hi {$studentName}, remember to complete your scholarship applications before the posted deadlines in your dashboard.";
    } elseif ($messageTemplate === 'profile_completion') {
        $finalMessage = "ScholarConnect [Profile Completion]: Hi {$studentName}, please update missing profile details to improve your scholarship eligibility ranking.";
    } elseif ($messageTemplate === 'status_update') {
        $finalMessage = "ScholarConnect [Status Update]: Hi {$studentName}, one or more of your applications has a status update. Log in to ScholarConnect to review details.";
    } else {
        $matches = MatchingEngine::getMatches((int) $recipient['studentID']);
        $eligible = [];
        foreach ($matches as $match) {
            $schId = (int) ($match['id'] ?? 0);
            $score = (int) ($match['match'] ?? 0);
            if ($schId <= 0 || $score <= 30 || !isset($sigScholarshipIds[$schId])) {
                continue;
            }
            $eligible[] = $match;
        }

        if (empty($eligible)) {
            $finalMessage = "ScholarConnect [Matched Scholarships]: Hi {$studentName}, there are currently no new scholarships meeting eligibility from our listings for your profile.";
        } else {
            $parts = [];
            $position = 1;
            foreach (array_slice($eligible, 0, 3) as $row) {
                $title = trim((string) ($row['title'] ?? 'Scholarship'));

                $reasonText = '';
                if (!empty($row['reasons']) && is_array($row['reasons'])) {
                    $reasons = array_slice(array_values($row['reasons']), 0, 2);
                    if (!empty($reasons)) {
                        $reasonText = ' why: ' . implode(', ', $reasons);
                    }
                }

                $parts[] = $position . ') ' . $title . $reasonText;
                $position++;
            }

            $finalMessage = "ScholarConnect [Matched Scholarships]: Hi {$studentName}, your current top matches are " . implode(' | ', $parts) . ". Log in to apply.";
        }
    }

    if (strlen($finalMessage) > 480) {
        $finalMessage = substr($finalMessage, 0, 477) . '...';
    }

    $smsResult = SmsService::sendSms($recipient['phone'], $finalMessage);
    if (!empty($smsResult['status'])) {
        $sentCount++;
    } else {
        $failedCount++;
    }
}

if ($sentCount > 0 && $failedCount === 0) {
    $successTarget = (strtolower($studentSelection) === 'all') ? 'all selected students' : $recipients[0]['name'];
    header('Location: ../signatory/tempSigHome.php?sms_status=ok&sms_message=' . urlencode('SMS sent to ' . $successTarget . '.'));
    exit;
}

if ($sentCount > 0 && $failedCount > 0) {
    header('Location: ../signatory/tempSigHome.php?sms_status=ok&sms_message=' . urlencode('SMS partially sent. Success: ' . $sentCount . ', Failed: ' . $failedCount . '.'));
    exit;
}

header('Location: ../signatory/tempSigHome.php?sms_status=err&sms_message=' . urlencode('SMS failed for all selected recipients.'));
exit;
