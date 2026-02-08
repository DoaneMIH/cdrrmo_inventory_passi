<?php
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: inventory.php');
    exit();
}

$item_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($item_id > 0) {
    $sql = "DELETE FROM inventory_items WHERE id = $item_id";
    
    if ($conn->query($sql)) {
        header('Location: inventory.php?deleted=1');
    } else {
        header('Location: inventory.php?error=delete_failed');
    }
} else {
    header('Location: inventory.php');
}

exit();
?>