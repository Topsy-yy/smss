<?php
session_start();
require '../config.php';

$_SESSION['selectedAppID'] = 0;
$_SESSION['appList'] = null;

function jsRedirect($path, $message = null) {
    if ($message !== null) {
        echo '<script>alert(' . json_encode($message) . ');location.replace(' . json_encode($path) . ');</script>';
    } else {
        echo '<script>location.replace(' . json_encode($path) . ');</script>';
    }
    exit;
}

$currentUserID = $_SESSION['currentUserID'] ?? null;
$schid = $_SESSION['schid'] ?? null;
$sigID = $_SESSION['sigID'] ?? null;

if ($currentUserID === null) {
    jsRedirect('../index.php');
}

if ($schid === null || $sigID === null) {
    jsRedirect('../student/tempUserApply.php', 'Please select a scholarship first.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsRedirect('../student/applyprocess.php', 'Invalid request.');
}

// Accept any POST submit from apply process to avoid value-mismatch issues.
if (!isset($_POST['apply'])) {
    jsRedirect('../student/applyprocess.php', 'Invalid application submission.');
}

$conn = getDbConnection();
if ($conn->connect_error) {
    jsRedirect('../student/applyprocess.php', 'Database connection failed. Please try again.');
}

$date1 = date('Y-m-d H:i:s');
$sql = "INSERT INTO application(studentID,sigID,scholarshipID,appDate,appstatus,verifiedBySignatory,previous_appstatus,previous_verifiedBySignatory) VALUES ('$currentUserID','$sigID','$schid','$date1','Pending','Pending','Pending','Pending')";
try {
    if (!mysqli_query($conn, $sql)) {
        $conn->close();
        jsRedirect('../student/applyprocess.php', 'Application submission failed. You may have already applied.');
    }
} catch (Throwable $e) {
    $conn->close();
    jsRedirect('../student/applyprocess.php', 'Application submission failed. Please try again.');
}

$total = (isset($_FILES['file']['name']) && is_array($_FILES['file']['name'])) ? count($_FILES['file']['name']) : 0;
if ($total === 0) {
    $conn->close();
    jsRedirect('../student/applyprocess.php', 'Please upload the required documents.');
}

$folder = $currentUserID . '_' . $schid;
$targetDir = "../applications/$folder/";

if (!is_dir($targetDir) && !mkdir($targetDir, 0777, true)) {
    $conn->close();
    jsRedirect('../student/applyprocess.php', 'Could not create application folder.');
}

$uploadedCount = 0;
for ($i = 0; $i < $total; $i++) {
    if (!isset($_FILES['file']['tmp_name'][$i], $_FILES['file']['name'][$i])) {
        continue;
    }

    if (!is_uploaded_file($_FILES['file']['tmp_name'][$i])) {
        continue;
    }

    $safeName = basename($_FILES['file']['name'][$i]);
    if ($safeName === '') {
        continue;
    }

    if (copy($_FILES['file']['tmp_name'][$i], $targetDir . $safeName)) {
        $uploadedCount++;
    }
}

$conn->close();

if ($uploadedCount === $total) {
    jsRedirect('../student/tempUserHome.php', 'Your Application is Submitted Successfully!');
}

jsRedirect('../student/applyprocess.php', 'Application was saved, but one or more files failed to upload. Please try again.');
