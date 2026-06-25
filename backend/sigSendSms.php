<?php
session_start();

require '../config.php';
require_once 'security.php';
require_once 'SmsService.php';

require_login(3);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../signatory/tempSigHome.php?sms_status=err&sms_message=' . urlencode('Invalid request method.'));
    exit;
}

$currentUserID = (int) ($_SESSION['currentUserID'] ?? 0);
$studentID = (int) ($_POST['student_id'] ?? 0);
$messageType = trim((string) ($_POST['message_type'] ?? 'Reminder'));
$messageText = trim((string) ($_POST['message_text'] ?? ''));

if ($currentUserID <= 0 || $studentID <= 0 || $messageText === '') {
    header('Location: ../signatory/tempSigHome.php?sms_status=err&sms_message=' . urlencode('Missing required SMS fields.'));
    exit;
}

if (strlen($messageText) > 480) {
    header('Location: ../signatory/tempSigHome.php?sms_status=err&sms_message=' . urlencode('Message is too long. Keep it under 480 characters.'));
    exit;
}

$conn = getDbConnection();
if (!$conn || $conn->connect_error) {
    header('Location: ../signatory/tempSigHome.php?sms_status=err&sms_message=' . urlencode('Database connection failed.'));
    exit;
}

$studentSql = "SELECT ST.firstName, ST.lastName, ST.phone
               FROM application A
               JOIN student ST ON ST.studentID = A.studentID
               WHERE A.sigID = ? AND ST.studentID = ?
               LIMIT 1";

$stmt = $conn->prepare($studentSql);
if (!$stmt) {
    $conn->close();
    header('Location: ../signatory/tempSigHome.php?sms_status=err&sms_message=' . urlencode('Unable to prepare SMS recipient lookup.'));
    exit;
}

$stmt->bind_param('ii', $currentUserID, $studentID);
$stmt->execute();
$result = $stmt->get_result();
$student = $result ? $result->fetch_assoc() : null;
$stmt->close();
$conn->close();

if (!$student) {
    header('Location: ../signatory/tempSigHome.php?sms_status=err&sms_message=' . urlencode('Student not found in your applicant list.'));
    exit;
}

$phone = trim((string) ($student['phone'] ?? ''));
if ($phone === '') {
    header('Location: ../signatory/tempSigHome.php?sms_status=err&sms_message=' . urlencode('Selected student has no mobile number on profile.'));
    exit;
}

$studentName = trim((string) (($student['firstName'] ?? '') . ' ' . ($student['lastName'] ?? '')));
if ($studentName === '') {
    $studentName = 'Student';
}

$prefix = 'ScholarConnect';
if ($messageType !== '') {
    $prefix .= ' [' . $messageType . ']';
}

$finalMessage = $prefix . ': ' . $messageText;
$smsResult = SmsService::sendSms($phone, $finalMessage);

if (!empty($smsResult['status'])) {
    header('Location: ../signatory/tempSigHome.php?sms_status=ok&sms_message=' . urlencode('SMS sent to ' . $studentName . '.'));
    exit;
}

$errorMessage = 'SMS failed to send.';
if (!empty($smsResult['providerMessage'])) {
    $errorMessage .= ' ' . (string) $smsResult['providerMessage'];
} elseif (!empty($smsResult['error'])) {
    $errorMessage .= ' ' . (string) $smsResult['error'];
}

header('Location: ../signatory/tempSigHome.php?sms_status=err&sms_message=' . urlencode($errorMessage));
exit;
