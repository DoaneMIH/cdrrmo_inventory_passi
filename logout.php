<?php
require_once 'includes/config.php';

if (isset($_SESSION['user_id'])) {
    log_logout($conn, $_SESSION['user_id']);
}

// Destroy session completely
$_SESSION = [];
session_unset();
session_destroy();

// Delete the session cookie from the browser
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

// Prevent back button from showing cached pages
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Sat, 01 Jan 2000 00:00:00 GMT');

header('Location: login.php');
exit();
?>