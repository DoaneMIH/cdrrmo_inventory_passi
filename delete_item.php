<?php
require_once 'config.php';
require_once 'audit_helper.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: inventory.php');
    exit();
}

$item_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($item_id > 0) {
    // GET ITEM DATA BEFORE DELETION 
    // This is important because once deleted, you can't get the data anymore!
    $item_data = get_item_data($conn, $item_id);
    
    $sql = "DELETE FROM inventory_items WHERE id = $item_id";
    
    if ($conn->query($sql)) {
        // LOG THE DELETION WITH DELETED DATA - ADD THIS
        if ($item_data) {
            log_item_delete($conn, $_SESSION['user_id'], $item_id, $item_data);
        }
        
        header('Location: inventory.php?deleted=1');
    } else {
        header('Location: inventory.php?error=delete_failed');
    }
} else {
    header('Location: inventory.php');
}

exit();
?>