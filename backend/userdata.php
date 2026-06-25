<?php
session_start();
require '../config.php';

if (!isset($_SESSION['currentUserID'])) {
    header("Location: ../index.php");
    exit();
}

$conn = getDbConnection();
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $studentID = (int) $_SESSION['currentUserID'];

    // Core profile fields from student edit form.
    $lastName = trim($_POST['lastName'] ?? '');
    $firstName = trim($_POST['firstName'] ?? '');
    $middleName = trim($_POST['middleName'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $nationality = trim($_POST['nationality'] ?? '');
    $birthDate = trim($_POST['birthDate'] ?? '');
    $birthPlace = trim($_POST['birthPlace'] ?? '');
    $contactNo = trim($_POST['contactNo'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $college = trim($_POST['college'] ?? '');
    $dept = trim($_POST['dept'] ?? '');
    $status = trim($_POST['status'] ?? '');
    $presStreetAddr = trim($_POST['presStreetAddr'] ?? '');
    $presProvCity = trim($_POST['presProvCity'] ?? '');
    $presRegion = trim($_POST['presRegion'] ?? '');
    $permStreetAddr = trim($_POST['permStreetAddr'] ?? '');
    $permProvCity = trim($_POST['permProvCity'] ?? '');
    $permRegion = trim($_POST['permRegion'] ?? '');

    // Matching fields used by recommendation logic.
    $current_level = trim($_POST['current_level'] ?? '');
    $financial_need = trim($_POST['financial_need'] ?? '');
    $careerInterestRaw = $_POST['career_interests'] ?? [];
    if (is_array($careerInterestRaw)) {
        $careerInterestRaw = array_filter(array_map('trim', $careerInterestRaw), function ($value) {
            return $value !== '';
        });
        $career_interests = implode(', ', $careerInterestRaw);
    } else {
        $career_interests = trim((string) $careerInterestRaw);
    }

    if ($status === '') {
        $statusStmt = $conn->prepare("SELECT status FROM student WHERE studentID = ? LIMIT 1");
        if ($statusStmt) {
            $statusStmt->bind_param("i", $studentID);
            $statusStmt->execute();
            $statusRes = $statusStmt->get_result();
            if ($statusRes && $statusRes->num_rows > 0) {
                $statusRow = $statusRes->fetch_assoc();
                $status = trim((string) ($statusRow['status'] ?? ''));
            }
            $statusStmt->close();
        }

        if ($status === '') {
            $status = 'active';
        }
    }

    if (
        $lastName === '' ||
        $firstName === '' ||
        $gender === '' ||
        $nationality === '' ||
        $birthDate === '' ||
        $contactNo === '' ||
        $college === '' ||
        $dept === '' ||
        $presStreetAddr === '' ||
        $presProvCity === '' ||
        $presRegion === '' ||
        $current_level === '' ||
        $financial_need === '' ||
        $career_interests === ''
    ) {
        echo "<script>alert('Please fill in all required profile fields before submitting.'); window.history.back();</script>";
        $conn->close();
        exit();
    }

    $sql = "UPDATE student SET
            lastName = ?,
            firstName = ?,
            middleName = ?,
            gender = ?,
            nationality = ?,
            birthDate = ?,
            birthPlace = ?,
            college = ?,
            dept = ?,
            status = ?,
            contactNo = ?,
            phone = ?,
            presStreetAddr = ?,
            presProvCity = ?,
            presRegion = ?,
            permStreetAddr = ?,
            permProvCity = ?,
            permRegion = ?,
            current_level = ?,
            financial_need = ?,
            career_interests = ?
            WHERE studentID = ?";

    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        echo "<script>alert('Error preparing profile update.'); window.history.back();</script>";
    } else {
        $stmt->bind_param(
            "sssssssssssssssssssssi",
            $lastName,
            $firstName,
            $middleName,
            $gender,
            $nationality,
            $birthDate,
            $birthPlace,
            $college,
            $dept,
            $status,
            $contactNo,
            $phone,
            $presStreetAddr,
            $presProvCity,
            $presRegion,
            $permStreetAddr,
            $permProvCity,
            $permRegion,
            $current_level,
            $financial_need,
            $career_interests,
            $studentID
        );

        if ($stmt->execute()) {
            $_SESSION['currentUserName'] = trim($firstName . ' ' . $lastName);
            echo "<script>alert('Profile Updated Successfully!'); window.location.href='../student/tempUserHome.php';</script>";
        } else {
            echo "<script>alert('Error updating profile.'); window.history.back();</script>";
        }

        $stmt->close();
    }
}

$conn->close();
?>
