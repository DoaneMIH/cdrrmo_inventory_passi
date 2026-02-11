<?php
session_start();
require_once 'config.php';
require_once 'audit_helper.php'; // ADD THIS

if (isset($_SESSION['user_id'])) {
    log_logout($conn, $_SESSION['user_id']);
}

session_destroy();
header('Location: index.php');
exit();
?>