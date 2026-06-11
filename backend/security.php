<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function app_log($level, $message, $context = array()) {
    $entry = array(
        'time' => date('c'),
        'level' => $level,
        'message' => $message,
        'context' => $context
    );
    error_log(json_encode($entry));
}

function csrf_token($formKey) {
    if (!isset($_SESSION['csrf_tokens'])) {
        $_SESSION['csrf_tokens'] = array();
    }
    if (empty($_SESSION['csrf_tokens'][$formKey])) {
        $_SESSION['csrf_tokens'][$formKey] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_tokens'][$formKey];
}

function csrf_input($formKey) {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token($formKey), ENT_QUOTES, 'UTF-8') . '">';
}

function validate_csrf_or_redirect($formKey, $redirectPath) {
    $token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    $expected = isset($_SESSION['csrf_tokens'][$formKey]) ? $_SESSION['csrf_tokens'][$formKey] : '';
    if ($token === '' || $expected === '' || !hash_equals($expected, $token)) {
        $_SESSION['errMsg'] = 'Invalid request. Please try again.';
        header('Location: ' . $redirectPath);
        exit;
    }
}

function rate_limit_timestamps($key) {
    if (!isset($_SESSION['rate_limits'])) {
        $_SESSION['rate_limits'] = array();
    }
    if (!isset($_SESSION['rate_limits'][$key])) {
        $_SESSION['rate_limits'][$key] = array();
    }
    return $_SESSION['rate_limits'][$key];
}

function save_rate_limit_timestamps($key, $timestamps) {
    $_SESSION['rate_limits'][$key] = $timestamps;
}

function is_rate_limited($key, $maxAttempts, $windowSeconds) {
    $now = time();
    $timestamps = rate_limit_timestamps($key);
    $fresh = array();
    foreach ($timestamps as $attemptTime) {
        if (($now - (int)$attemptTime) < $windowSeconds) {
            $fresh[] = (int)$attemptTime;
        }
    }
    save_rate_limit_timestamps($key, $fresh);
    return count($fresh) >= $maxAttempts;
}

function register_attempt($key) {
    $timestamps = rate_limit_timestamps($key);
    $timestamps[] = time();
    save_rate_limit_timestamps($key, $timestamps);
}

function clear_attempts($key) {
    if (isset($_SESSION['rate_limits'][$key])) {
        unset($_SESSION['rate_limits'][$key]);
    }
}

function require_login($roleId = null) {
    if (empty($_SESSION['isLoggedIn']) || empty($_SESSION['currentUserID']) || empty($_SESSION['currentUserTYPE'])) {
        header('Location: ../index.php');
        exit;
    }
    if ($roleId !== null && (int)$_SESSION['currentUserTYPE'] !== (int)$roleId) {
        $_SESSION['errMsg'] = 'Unauthorized access.';
        header('Location: ../index.php');
        exit;
    }
}
