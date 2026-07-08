<?php
session_start();
require '../config.php';
require_once 'notification_mailer.php';
require_once 'email_templates.php';
require_once 'SmsService.php';

function sig_set_flash_and_redirect($message, $path) {
    $_SESSION['sig_flash_message'] = (string) $message;
    header('Location: ' . $path);
    exit;
}

$currentUserID = (int) ($_SESSION['currentUserID'] ?? 0);
if ($currentUserID <= 0) {
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sig_set_flash_and_redirect('Invalid request.', '../signatory/tempSigApplication.php');
}

$action = trim((string) ($_POST['accrej'] ?? ''));
$appID = (int) ($_POST['appID'] ?? 0);

if (($action !== 'Accept' && $action !== 'Reject') || $appID <= 0) {
    sig_set_flash_and_redirect('Invalid request data.', '../signatory/tempSigApplication.php');
}

$conn = getDbConnection();
if (!$conn || $conn->connect_error) {
    sig_set_flash_and_redirect('Database connection failed.', '../signatory/tempSigApplication.php');
}

// Ensure the application belongs to this signatory and load notification details.
$lookupSql = "SELECT A.appstatus, A.verifiedBySignatory,
                     ST.upMail, ST.phone, ST.firstName, ST.lastName,
                     SC.schname
              FROM application A
              JOIN student ST ON ST.studentID = A.studentID
              JOIN scholarship SC ON SC.scholarshipID = A.scholarshipID
              WHERE A.applicationID = ? AND A.sigID = ?
              LIMIT 1";
$lookupStmt = $conn->prepare($lookupSql);

if (!$lookupStmt) {
    $conn->close();
    sig_set_flash_and_redirect('Unable to process request.', '../signatory/tempSigApplication.php');
}

$lookupStmt->bind_param('ii', $appID, $currentUserID);
$lookupStmt->execute();
$lookupRes = $lookupStmt->get_result();
$row = $lookupRes ? $lookupRes->fetch_assoc() : null;
$lookupStmt->close();

if (!$row) {
    $conn->close();
    sig_set_flash_and_redirect('Application not found.', '../signatory/tempSigApplication.php');
}

$appstatus = (string) ($row['appstatus'] ?? '');
if (strtolower($appstatus) === 'inactive') {
    $conn->close();
    if ($action === 'Accept') {
        sig_set_flash_and_redirect('Cannot approve. The application is inactive.', '../signatory/tempSigApplication.php?app=Pending');
    }
    sig_set_flash_and_redirect('Cannot reject. The application is inactive.', '../signatory/tempSigApplication.php?app=Pending');
}

$notifyEmail = (string) ($row['upMail'] ?? '');
$notifyPhone = trim((string) ($row['phone'] ?? ''));
$notifyStudentName = trim((string) (($row['firstName'] ?? '') . ' ' . ($row['lastName'] ?? '')));
if ($notifyStudentName === '') {
    $notifyStudentName = 'Student';
}
$notifyScholarship = trim((string) ($row['schname'] ?? ''));
if ($notifyScholarship === '') {
    $notifyScholarship = 'your scholarship application';
}

if ($action === 'Accept') {
    $updateSql = "UPDATE application
                  SET appstatus = 'Processing', verifiedBySignatory = 'Approved'
                  WHERE applicationID = ? AND sigID = ?";
} else {
    $updateSql = "UPDATE application
                  SET appstatus = 'Rejected', verifiedBySignatory = 'Rejected'
                  WHERE applicationID = ? AND sigID = ?";
}

$updateStmt = $conn->prepare($updateSql);
if (!$updateStmt) {
    $conn->close();
    sig_set_flash_and_redirect('Error updating record.', '../signatory/tempSigApplication.php');
}

$updateStmt->bind_param('ii', $appID, $currentUserID);
$updated = $updateStmt->execute();
$updateStmt->close();

if (!$updated) {
    $conn->close();
    sig_set_flash_and_redirect('Error updating record.', '../signatory/tempSigApplication.php');
}

// Notify student, but do not block navigation if notification fails.
if ($action === 'Accept') {
    $emailTemplate = email_tpl_application_approved($notifyStudentName, $notifyScholarship);
    $smsMsg = "ScholarConnect: Hi {$notifyStudentName}, your application for '{$notifyScholarship}' has been approved and is now processing.";
} else {
    $emailTemplate = email_tpl_application_rejected($notifyStudentName, $notifyScholarship);
    $smsMsg = "ScholarConnect: Hi {$notifyStudentName}, your application for '{$notifyScholarship}' has been rejected. Please review your details and other opportunities.";
}

if ($notifyEmail !== '') {
    sendNotificationEmail($notifyEmail, (string) ($emailTemplate['subject'] ?? ''), (string) ($emailTemplate['body'] ?? ''));
}
if ($notifyPhone !== '') {
    SmsService::sendSms($notifyPhone, $smsMsg);
}

// After approval/rejection, keep signatory on pending-queue workflow.
$pendingSql = "SELECT COUNT(*) AS pending_count
               FROM application
               WHERE sigID = ?
                 AND LOWER(appstatus) <> 'inactive'
                 AND (LOWER(verifiedBySignatory) = 'pending' OR LOWER(appstatus) = 'pending')";
$pendingStmt = $conn->prepare($pendingSql);
$pendingCount = 0;

if ($pendingStmt) {
    $pendingStmt->bind_param('i', $currentUserID);
    $pendingStmt->execute();
    $pendingRes = $pendingStmt->get_result();
    if ($pendingRes && $pendingRow = $pendingRes->fetch_assoc()) {
        $pendingCount = (int) ($pendingRow['pending_count'] ?? 0);
    }
    $pendingStmt->close();
}

$conn->close();

if ($action === 'Accept') {
    if ($pendingCount > 0) {
        sig_set_flash_and_redirect('Application approved successfully. You can continue reviewing pending applications.', '../signatory/tempSigApplication.php?app=Pending');
    }
    sig_set_flash_and_redirect('Application approved successfully. No pending applications left; returning to dashboard.', '../signatory/tempSigHome.php');
}

if ($pendingCount > 0) {
    sig_set_flash_and_redirect('Application rejected successfully. You can continue reviewing pending applications.', '../signatory/tempSigApplication.php?app=Pending');
}
sig_set_flash_and_redirect('Application rejected successfully. No pending applications left; returning to dashboard.', '../signatory/tempSigHome.php');
