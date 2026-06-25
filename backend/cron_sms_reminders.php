<?php
// backend/cron_sms_reminders.php
// Run this script daily via cron, e.g., 0 8 * * * php /path/to/backend/cron_sms_reminders.php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/SmsService.php';
require_once __DIR__ . '/MatchingEngine.php';

$conn = getDbConnection();
if (!$conn) {
    die("Database connection failed\n");
}

$today = date('Y-m-d');
$targetDate = date('Y-m-d', strtotime('+3 days'));

$schSql = "SELECT scholarshipID, schname FROM scholarship WHERE appDeadline = ? AND schstatus = 'active' AND adminapproval = 'Approved'";
$stmt = $conn->prepare($schSql);
$stmt->bind_param("s", $targetDate);
$stmt->execute();
$schRes = $stmt->get_result();

$scholarshipsClosingSoon = [];
while ($row = $schRes->fetch_assoc()) {
    $scholarshipsClosingSoon[] = $row;
}
$stmt->close();

$totalSmsSent = 0;

foreach ($scholarshipsClosingSoon as $sch) {
    $schId = $sch['scholarshipID'];
    $schName = $sch['schname'];

    // Get matched students for this scholarship
    $matchedStudents = MatchingEngine::getMatchedStudentsForScholarship($schId);
    
    // Check their application status
    $appliedSql = "SELECT studentID, appstatus FROM application WHERE scholarshipID = ?";
    $appStmt = $conn->prepare($appliedSql);
    $appStmt->bind_param("i", $schId);
    $appStmt->execute();
    $appRes = $appStmt->get_result();
    
    $applications = [];
    while ($app = $appRes->fetch_assoc()) {
        $applications[$app['studentID']] = $app['appstatus'];
    }
    $appStmt->close();

    $phonesToAlert = [];
    $phonesDraft = [];

    foreach ($matchedStudents as $student) {
        $studentId = $student['studentID'];
        $phone = $student['phone'];

        if (empty($phone)) continue;

        if (!isset($applications[$studentId])) {
            // Deadline Reminder / Eligibility
            $phonesToAlert[] = $phone;
        } elseif ($applications[$studentId] === 'Draft' || $applications[$studentId] === 'Pending') {
            // Application Readiness
            $phonesDraft[] = $phone;
        }
    }

    if (!empty($phonesToAlert)) {
        $msg = "ScholarConnect: The deadline for '{$schName}' is in 3 days! Don't miss out on this matching opportunity. Apply now.";
        SmsService::sendSms($phonesToAlert, $msg);
        $totalSmsSent += count($phonesToAlert);
    }

    if (!empty($phonesDraft)) {
        $msg = "ScholarConnect: Your application for '{$schName}' is incomplete. Please submit it before the deadline in 3 days!";
        SmsService::sendSms($phonesDraft, $msg);
        $totalSmsSent += count($phonesDraft);
    }
}

$conn->close();
echo "Cron run completed successfully. Total SMS triggered: $totalSmsSent\n";
?>
