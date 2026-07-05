<?php
session_start();
require '../config.php';
require 'security.php';
require_login(2);
require_once 'SmsService.php';
require_once 'MatchingEngine.php';
require_once 'IRRecommendationEngine.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../admin/tempAdmin.php');
    exit();
}

$conn = getDbConnection();
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

$sigID = (int) ($_POST['sigID'] ?? 0);
$schname = trim($_POST['schname'] ?? '');
$schlocation = trim($_POST['schlocation'] ?? '');
$degree = trim($_POST['degree'] ?? '');
$gender = trim($_POST['gender'] ?? '');
$targetFinancialNeed = trim($_POST['target_financial_need'] ?? 'Any');
$scholarshipType = trim($_POST['scholarship'] ?? '');
$appdeadline = trim($_POST['appdeadline'] ?? '');
$funding = trim($_POST['funding'] ?? '');
$description = trim($_POST['description'] ?? '');
$eligibility = trim($_POST['eligibility'] ?? '');

if ($sigID <= 0 || $schname === '' || $degree === '' || $gender === '' || $scholarshipType === '' || $appdeadline === '' || $description === '' || $eligibility === '') {
    echo "<script>alert('Please fill all required fields.'); window.history.back();</script>";
    $conn->close();
    exit();
}

$checkSig = $conn->prepare('SELECT sigID FROM signatory WHERE sigID = ? LIMIT 1');
$checkSig->bind_param('i', $sigID);
$checkSig->execute();
$sigExists = $checkSig->get_result()->fetch_assoc();
$checkSig->close();

if (!$sigExists) {
    echo "<script>alert('Selected signatory does not exist.'); window.history.back();</script>";
    $conn->close();
    exit();
}

$religion = '';
$schlocationfrom = '';
$granteesNum = 0;
$benefits = '';
$apply = '';
$links = '';
$contact = '';
$adminapproval = 'Approved';
$previousAdminApproval = 'Approved';

$sql = "INSERT INTO scholarship (
            sigID, schname, schlocation, schlocationfrom, degree, gender, religion,
            target_financial_need, sch, appDeadline, granteesNum, funding, description,
            eligibility, benefits, apply, links, contact, adminapproval, previous_adminapproval
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    echo "<script>alert('Unable to create opportunity right now.'); window.location.href='../admin/tempAdmin.php';</script>";
    $conn->close();
    exit();
}

$stmt->bind_param(
    'isssssssssisssssssss',
    $sigID,
    $schname,
    $schlocation,
    $schlocationfrom,
    $degree,
    $gender,
    $religion,
    $targetFinancialNeed,
    $scholarshipType,
    $appdeadline,
    $granteesNum,
    $funding,
    $description,
    $eligibility,
    $benefits,
    $apply,
    $links,
    $contact,
    $adminapproval,
    $previousAdminApproval
);

if ($stmt->execute()) {
    $schID = (int) $conn->insert_id;

    if (class_exists('IRRecommendationEngine')) {
        IRRecommendationEngine::markScholarshipCorpusDirty($conn);
    }

    $xmlPath = __DIR__ . '/scholarship_data.xml';
    if (is_file($xmlPath)) {
        $xml = new DOMDocument('1.0', 'UTF-8');
        $xml->load($xmlPath);
        $rootTag = $xml->getElementsByTagName('scholarships')->item(0);
        if ($rootTag) {
            $dataTag = $xml->createElement('scholarship');
            $dataTag->setAttribute('scholarshipID', $schID);

            $dataTag->appendChild($xml->createElement('sigID', (string) $sigID));
            $dataTag->appendChild($xml->createElement('schname', $schname));
            $dataTag->appendChild($xml->createElement('schlocation', $schlocation));
            $dataTag->appendChild($xml->createElement('schlocationfrom', $schlocationfrom));
            $dataTag->appendChild($xml->createElement('degree', $degree));
            $dataTag->appendChild($xml->createElement('gender', $gender));
            $dataTag->appendChild($xml->createElement('religion', $religion));
            $dataTag->appendChild($xml->createElement('target_financial_need', $targetFinancialNeed));
            $dataTag->appendChild($xml->createElement('sch', $scholarshipType));
            $dataTag->appendChild($xml->createElement('appDeadline', $appdeadline));
            $dataTag->appendChild($xml->createElement('granteesNum', (string) $granteesNum));
            $dataTag->appendChild($xml->createElement('funding', $funding));
            $dataTag->appendChild($xml->createElement('description', $description));
            $dataTag->appendChild($xml->createElement('eligibility', $eligibility));
            $dataTag->appendChild($xml->createElement('benefits', $benefits));
            $dataTag->appendChild($xml->createElement('apply', $apply));
            $dataTag->appendChild($xml->createElement('links', $links));
            $dataTag->appendChild($xml->createElement('contact', $contact));

            $rootTag->appendChild($dataTag);
            $xml->save($xmlPath);
        }
    }

    // --- SMS Notifications for students eligible by listing threshold (>=30% match) ---
    $matchedStudents = MatchingEngine::getMatchedStudentsForScholarship($schID);
    foreach ($matchedStudents as $ms) {
        $score = (int) ($ms['score'] ?? 0);
        $phone = trim((string) ($ms['phone'] ?? ''));
        if ($score < 30 || $phone === '') {
            continue;
        }

        $studentName = trim((string) ($ms['name'] ?? 'Student'));
        if ($studentName === '') {
            $studentName = 'Student';
        }

        $reasons = [];
        if (!empty($ms['reasons']) && is_array($ms['reasons'])) {
            $reasons = array_slice(array_values($ms['reasons']), 0, 2);
        }
        $whyMatched = '';
        if (!empty($reasons)) {
            $whyMatched = ' Why matched: ' . implode(', ', $reasons) . '.';
        }

        $smsMsg = "Hi {$studentName}, you are eligible for '{$schname}'. Log in to ScholarConnect and apply before {$appdeadline}.{$whyMatched}";
        SmsService::sendSms($phone, $smsMsg);
    }

    echo "<script>alert('Opportunity created successfully.'); window.location.href='../admin/tempAdmin.php';</script>";
} else {
    echo "<script>alert('Failed to create opportunity.'); window.location.href='../admin/tempAdmin.php';</script>";
}

$stmt->close();
$conn->close();
?>
