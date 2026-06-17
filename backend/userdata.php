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
    $contactNo = trim($_POST['contactNo'] ?? '');

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

    $sql = "UPDATE student SET
            lastName = ?,
            firstName = ?,
            middleName = ?,
            gender = ?,
            contactNo = ?,
            current_level = ?,
            financial_need = ?,
            career_interests = ?
            WHERE studentID = ?";

    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        echo "<script>alert('Error preparing profile update.'); window.history.back();</script>";
    } else {
        $stmt->bind_param(
            "ssssssssi",
            $lastName,
            $firstName,
            $middleName,
            $gender,
            $contactNo,
            $current_level,
            $financial_need,
            $career_interests,
            $studentID
        );

        if ($stmt->execute()) {
            $_SESSION['currentUserName'] = trim($firstName . ' ' . $lastName);
            echo "<script>alert('Profile Updated Successfully!'); window.location.href='../student/tempUserProfile.php';</script>";
        } else {
            echo "<script>alert('Error updating profile.'); window.history.back();</script>";
        }

        $stmt->close();
    }
}

$conn->close();
?>
