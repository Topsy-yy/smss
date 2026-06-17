<?php
session_start();
require '../config.php';
require 'security.php';
require_login(2);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../admin/tempAdmin.php');
    exit();
}

$userType = trim($_POST['userType'] ?? '');
$userID = (int) ($_POST['ID'] ?? 0);

if ($userID <= 0 || ($userType !== 'student' && $userType !== 'signatory')) {
    echo "<script>alert('Invalid user deletion request.'); window.history.back();</script>";
    exit();
}

$conn = getDbConnection();
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

$conn->begin_transaction();

try {
    if ($userType === 'student') {
        $email = '';
        $findStmt = $conn->prepare('SELECT upMail FROM student WHERE studentID = ? LIMIT 1');
        $findStmt->bind_param('i', $userID);
        $findStmt->execute();
        $row = $findStmt->get_result()->fetch_assoc();
        $findStmt->close();

        if (!$row) {
            throw new Exception('Student not found.');
        }

        $email = (string) $row['upMail'];

        $deleteUserStmt = $conn->prepare('DELETE FROM student WHERE studentID = ?');
        $deleteUserStmt->bind_param('i', $userID);
        $deleteUserStmt->execute();
        $deleteUserStmt->close();

        if ($email !== '') {
            $verifyStmt = $conn->prepare('DELETE FROM verify_signup WHERE upMail = ?');
            $verifyStmt->bind_param('s', $email);
            $verifyStmt->execute();
            $verifyStmt->close();
        }
    }

    if ($userType === 'signatory') {
        $email = '';
        $findStmt = $conn->prepare('SELECT upMail FROM signatory WHERE sigID = ? LIMIT 1');
        $findStmt->bind_param('i', $userID);
        $findStmt->execute();
        $row = $findStmt->get_result()->fetch_assoc();
        $findStmt->close();

        if (!$row) {
            throw new Exception('Signatory not found.');
        }

        $email = (string) $row['upMail'];

        $schIds = array();
        $schQueryStmt = $conn->prepare('SELECT scholarshipID FROM scholarship WHERE sigID = ?');
        $schQueryStmt->bind_param('i', $userID);
        $schQueryStmt->execute();
        $schRes = $schQueryStmt->get_result();
        while ($schRow = $schRes->fetch_assoc()) {
            $schIds[] = (int) $schRow['scholarshipID'];
        }
        $schQueryStmt->close();

        if (!empty($schIds)) {
            $deleteSchStmt = $conn->prepare('DELETE FROM scholarship WHERE sigID = ?');
            $deleteSchStmt->bind_param('i', $userID);
            $deleteSchStmt->execute();
            $deleteSchStmt->close();
        }

        $deleteSigStmt = $conn->prepare('DELETE FROM signatory WHERE sigID = ?');
        $deleteSigStmt->bind_param('i', $userID);
        $deleteSigStmt->execute();
        $deleteSigStmt->close();

        if ($email !== '') {
            $verifyStmt = $conn->prepare('DELETE FROM verify_signup WHERE upMail = ?');
            $verifyStmt->bind_param('s', $email);
            $verifyStmt->execute();
            $verifyStmt->close();
        }

        if (!empty($schIds)) {
            $xmlPath = __DIR__ . '/scholarship_data.xml';
            if (is_file($xmlPath)) {
                $xml = simplexml_load_file($xmlPath);
                if ($xml) {
                    foreach ($schIds as $schID) {
                        $i = 0;
                        foreach ($xml->children() as $scholarship) {
                            if ((string) $scholarship['scholarshipID'] === (string) $schID) {
                                unset($xml->scholarship[$i]);
                                break;
                            }
                            $i++;
                        }
                    }
                    $xml->asXML($xmlPath);
                }
            }

            $scholarshipRoot = dirname(__DIR__) . '/scholarship';
            foreach ($schIds as $schID) {
                $scholarshipDir = $scholarshipRoot . '/' . $schID;
                if (is_dir($scholarshipDir)) {
                    foreach (glob($scholarshipDir . '/*') as $filePath) {
                        if (is_file($filePath)) {
                            unlink($filePath);
                        }
                    }
                    @rmdir($scholarshipDir);
                }
            }
        }
    }

    $conn->commit();

    $redirect = ($userType === 'student') ? '../admin/tempStudentShow.php' : '../admin/tempSignatoryShow.php';
    echo "<script>alert('User profile deleted successfully.'); window.location.href='" . $redirect . "';</script>";
} catch (Exception $e) {
    $conn->rollback();
    echo "<script>alert('Unable to delete user profile.'); window.history.back();</script>";
}

$conn->close();
?>
