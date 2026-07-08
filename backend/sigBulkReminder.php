<?php
session_start();

require '../config.php';
require_once 'security.php';
require_once 'SmsService.php';

require_login(3);
@set_time_limit(180);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../signatory/tempSigHome.php?sms_status=err&sms_message=' . urlencode('Invalid request method.'));
    exit;
}

$currentUserID = (int) ($_SESSION['currentUserID'] ?? 0);
if ($currentUserID <= 0) {
    header('Location: ../signatory/tempSigHome.php?sms_status=err&sms_message=' . urlencode('Unable to identify signatory account.'));
    exit;
}

$conn = getDbConnection();
if (!$conn || $conn->connect_error) {
    header('Location: ../signatory/tempSigHome.php?sms_status=err&sms_message=' . urlencode('Database connection failed.'));
    exit;
}

$recipientSql = "SELECT
                    ST.studentID,
                    ST.firstName,
                    ST.lastName,
                    ST.gender,
                    ST.birthDate,
                    ST.birthPlace,
                    ST.presStreetAddr,
                    ST.presProvCity,
                    ST.presRegion,
                    ST.contactNo,
                    ST.phone,
                    ST.dept,
                    ST.college
                 FROM application A
                 JOIN student ST ON ST.studentID = A.studentID
                 WHERE A.sigID = ?
                 GROUP BY ST.studentID, ST.firstName, ST.lastName, ST.gender, ST.birthDate, ST.birthPlace,
                          ST.presStreetAddr, ST.presProvCity, ST.presRegion, ST.contactNo, ST.phone, ST.dept, ST.college";

$stmt = $conn->prepare($recipientSql);
if (!$stmt) {
    $conn->close();
    header('Location: ../signatory/tempSigHome.php?sms_status=err&sms_message=' . urlencode('Unable to prepare applicants lookup.'));
    exit;
}

$stmt->bind_param('i', $currentUserID);
$stmt->execute();
$res = $stmt->get_result();

$needsAttention = [];
while ($res && ($row = $res->fetch_assoc())) {
    $fields = [
        'firstName', 'lastName', 'gender', 'birthDate', 'birthPlace',
        'presStreetAddr', 'presProvCity', 'presRegion', 'contactNo', 'dept', 'college'
    ];

    $filled = 0;
    foreach ($fields as $field) {
        $value = trim((string) ($row[$field] ?? ''));
        if ($value !== '' && $value !== '0000-00-00') {
            $filled++;
        }
    }

    $profilePct = (int) round(($filled / count($fields)) * 100);
    if ($profilePct >= 75) {
        continue;
    }

    $phone = trim((string) (($row['phone'] ?? '') !== '' ? ($row['phone'] ?? '') : ($row['contactNo'] ?? '')));
    if ($phone === '') {
        continue;
    }

    $name = trim((string) (($row['firstName'] ?? '') . ' ' . ($row['lastName'] ?? '')));
    if ($name === '') {
        $name = 'Student';
    }

    $needsAttention[] = [
        'name' => $name,
        'phone' => $phone,
        'profilePct' => $profilePct,
    ];
}

$stmt->close();
$conn->close();

if (empty($needsAttention)) {
    header('Location: ../signatory/tempSigHome.php?sms_status=err&sms_message=' . urlencode('No applicants currently need attention for bulk reminders.'));
    exit;
}

$sent = 0;
$failed = 0;
$invalid = 0;
foreach ($needsAttention as $recipient) {
    if (!SmsService::formatPhoneNumber($recipient['phone'])) {
        $failed++;
        $invalid++;
        continue;
    }

    $message = "ScholarConnect [Profile Reminder]: Hi {$recipient['name']}, your profile is {$recipient['profilePct']}% complete. Please update missing details to improve scholarship review readiness.";
    if (strlen($message) > 480) {
        $message = substr($message, 0, 477) . '...';
    }

    $result = SmsService::sendSms($recipient['phone'], $message);
    if (!empty($result['status'])) {
        $sent++;
    } else {
        $failed++;
    }
}

if ($sent > 0 && $failed === 0) {
    header('Location: ../signatory/tempSigHome.php?sms_status=ok&sms_message=' . urlencode("Bulk reminder sent to {$sent} student(s) via Africa's Talking."));
    exit;
}

if ($sent > 0 && $failed > 0) {
    $msg = "Bulk reminder partially sent. Success: {$sent}, Failed: {$failed}";
    if ($invalid > 0) {
        $msg .= " (invalid mobile format: {$invalid})";
    }
    header('Location: ../signatory/tempSigHome.php?sms_status=ok&sms_message=' . urlencode($msg . '.'));
    exit;
}

if ($failed > 0 && $invalid > 0) {
    header('Location: ../signatory/tempSigHome.php?sms_status=err&sms_message=' . urlencode("Bulk reminder failed. Invalid mobile format for {$invalid} recipient(s)."));
    exit;
}

header('Location: ../signatory/tempSigHome.php?sms_status=err&sms_message=' . urlencode('Bulk reminder failed for all applicants needing attention.'));
exit;
