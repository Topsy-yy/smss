<?php
/**
 * Application Configuration
 */

/**
 * Load key/value pairs from a local .env file into process environment.
 */
function loadEnvFile($filePath) {
    if (!is_readable($filePath)) {
        return;
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || strpos($trimmed, '#') === 0) {
            continue;
        }

        $separatorPos = strpos($trimmed, '=');
        if ($separatorPos === false) {
            continue;
        }

        $key = trim(substr($trimmed, 0, $separatorPos));
        $value = trim(substr($trimmed, $separatorPos + 1));

        if ($key === '') {
            continue;
        }

        if (
            (strpos($value, '"') === 0 && strrpos($value, '"') === strlen($value) - 1) ||
            (strpos($value, "'") === 0 && strrpos($value, "'") === strlen($value) - 1)
        ) {
            $value = substr($value, 1, -1);
        }

        putenv($key . '=' . $value);
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}

/**
 * Read an environment value with fallback default.
 */
function envValue($key, $default = null) {
    $value = getenv($key);
    if ($value === false || $value === null || $value === '') {
        return $default;
    }
    return $value;
}

loadEnvFile(__DIR__ . '/.env');

// Database Configuration
define('DB_HOST', envValue('DB_HOST', '127.0.0.1'));
define('DB_PORT', (int) envValue('DB_PORT', '3306'));
define('DB_USER', envValue('DB_USER', 'root'));
define('DB_PASS', envValue('DB_PASS', ''));
define('DB_NAME', envValue('DB_NAME', 'sms'));

// SMTP / Email Configuration
define('SMTP_HOST', envValue('SMTP_HOST', 'smtp.gmail.com'));
define('SMTP_PORT', (int) envValue('SMTP_PORT', '587'));
define('SMTP_AUTH', filter_var(envValue('SMTP_AUTH', 'true'), FILTER_VALIDATE_BOOLEAN));
define('SMTP_SECURE', envValue('SMTP_SECURE', 'tls'));
define('SMTP_USER', envValue('SMTP_USER', ''));
define('SMTP_PASS', envValue('SMTP_PASS', ''));
define('SMTP_FROM_NAME', envValue('SMTP_FROM_NAME', 'SMS Portal'));

// Africa's Talking API Configuration
define('AT_USERNAME', envValue('AT_USERNAME', 'sandbox'));
define('AT_API_KEY', envValue('AT_API_KEY', ''));
define('AT_SENDER_ID', envValue('AT_SENDER_ID', ''));
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
