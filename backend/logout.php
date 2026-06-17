<?php
session_start();

// Clear all session data and remove the session cookie to prevent stale auth state.
$_SESSION = array();

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

session_unset();
session_destroy();

header('Location: ../index.php');
exit;
?>
