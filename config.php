<?php
/**
 * Application Configuration
 */

// Database Configuration
define('DB_HOST', '127.0.0.1');
define('DB_PORT', 3306);
define('DB_USER', 'root');
define('DB_PASS', 'qwe');
define('DB_NAME', 'sms');

// SMTP / Email Configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_AUTH', true);
define('SMTP_SECURE', 'tls');
define('SMTP_USER', 'incredibleingridius@gmail.com'); // Replace with your Gmail
define('SMTP_PASS', 'ysnr ludd befg hvst');          // Replace with your 16-character App Password
define('SMTP_FROM_NAME', 'SMS Portal');

/**
 * Get a database connection
 */
function getDbConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    return $conn;
}

/**
 * Get a PDO database connection
 */
function getPdoConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;
        return new PDO($dsn, DB_USER, DB_PASS);
    } catch (PDOException $e) {
        die("PDO Connection failed: " . $e->getMessage());
    }
}
?>
