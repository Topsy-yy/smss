<?php
session_start();
require '../config.php';
require 'security.php';
require_login(2);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../admin/tempAdmin.php');
    exit();
}

$schID = (int) ($_POST['schID'] ?? 0);
if ($schID <= 0) {
    echo "<script>alert('Invalid scholarship selected.'); window.history.back();</script>";
    exit();
}

$conn = getDbConnection();
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

$conn->begin_transaction();

try {
    $deleteStmt = $conn->prepare('DELETE FROM scholarship WHERE scholarshipID = ?');
    $deleteStmt->bind_param('i', $schID);
    $deleteStmt->execute();
    $affected = $deleteStmt->affected_rows;
    $deleteStmt->close();

    if ($affected < 1) {
        throw new Exception('Scholarship not found.');
    }

    $conn->commit();

    $xmlPath = __DIR__ . '/scholarship_data.xml';
    if (is_file($xmlPath)) {
        $xml = simplexml_load_file($xmlPath);
        if ($xml) {
            $i = 0;
            foreach ($xml->children() as $scholarship) {
                if ((string) $scholarship['scholarshipID'] === (string) $schID) {
                    unset($xml->scholarship[$i]);
                    break;
                }
                $i++;
            }
            $xml->asXML($xmlPath);
        }
    }

    $scholarshipDir = dirname(__DIR__) . '/scholarship/' . $schID;
    if (is_dir($scholarshipDir)) {
        foreach (glob($scholarshipDir . '/*') as $filePath) {
            if (is_file($filePath)) {
                unlink($filePath);
            }
        }
        @rmdir($scholarshipDir);
    }

    echo "<script>alert('Scholarship deleted successfully.'); window.location.href='../admin/tempAdmin.php';</script>";
} catch (Exception $e) {
    $conn->rollback();
    echo "<script>alert('Unable to delete scholarship.'); window.history.back();</script>";
}

$conn->close();
?>
