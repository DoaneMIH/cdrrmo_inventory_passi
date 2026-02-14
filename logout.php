<?php
require_once 'config.php';

if (isset($_SESSION['user_id'])) {
    log_logout($conn, $_SESSION['user_id']);
}

// Destroy session
session_destroy();

// Redirect to login
header('Location: login.php');
exit();
?>