<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$message = '';
$error = '';
$item_id = isset($_GET['item_id']) ? intval($_GET['item_id']) : 0;

// Get item details
$sql = "SELECT i.*, c.category_name FROM inventory_items i 
        JOIN categories c ON i.category_id = c.id 
        WHERE i.id = $item_id";
$result = $conn->query($sql);

if (!$result || $result->num_rows == 0) {
    header('Location: inventory.php');
    exit();
}

$item = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $transaction_type = $conn->real_escape_string($_POST['transaction_type']);
    $quantity = intval($_POST['quantity']);
    $transaction_date = $conn->real_escape_string($_POST['transaction_date']);
    $notes = $conn->real_escape_string($_POST['notes']);
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Insert transaction record
        $trans_sql = "INSERT INTO transactions (item_id, transaction_type, quantity, transaction_date, notes, created_by) 
                     VALUES ($item_id, '$transaction_type', $quantity, '$transaction_date', '$notes', {$_SESSION['user_id']})";
        
        if (!$conn->query($trans_sql)) {
            throw new Exception('Error recording transaction');
        }
        
        // Update inventory
        if ($transaction_type == 'received') {
            $update_sql = "UPDATE inventory_items SET 
                          items_received = items_received + $quantity,
                          items_on_hand = items_on_hand + $quantity
                          WHERE id = $item_id";
        } else if ($transaction_type == 'distributed') {
            $update_sql = "UPDATE inventory_items SET 
                          items_distributed = items_distributed + $quantity,
                          items_on_hand = items_on_hand - $quantity
                          WHERE id = $item_id";
        } else { // adjustment
            $update_sql = "UPDATE inventory_items SET 
                          items_on_hand = items_on_hand + $quantity
                          WHERE id = $item_id";
        }
        
        if (!$conn->query($update_sql)) {
            throw new Exception('Error updating inventory');
        }
        
        $conn->commit();
        $message = 'Transaction recorded successfully!';
        
        // Refresh item data
        $result = $conn->query($sql);
        $item = $result->fetch_assoc();
        
    } catch (Exception $e) {
        $conn->rollback();
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Transaction - CDRRMO Inventory System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1>Add Transaction</h1>
            <a href="inventory.php" class="btn btn-secondary">← Back to Inventory</a>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="item-info-card">
            <h3><?php echo $item['item_description']; ?></h3>
            <p><strong>Category:</strong> <span class="badge"><?php echo $item['category_name']; ?></span></p>
            <p><strong>Current Stock:</strong> <?php echo $item['items_on_hand']; ?> <?php echo $item['unit']; ?></p>
        </div>
        
        <div class="form-container">
            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label for="transaction_type">Transaction Type *</label>
                        <select id="transaction_type" name="transaction_type" required>
                            <option value="received">Received (Add Stock)</option>
                            <option value="distributed">Distributed (Remove Stock)</option>
                            <option value="adjustment">Adjustment</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="quantity">Quantity *</label>
                        <input type="number" id="quantity" name="quantity" min="1" required>
                        <small>For adjustments, use negative numbers to decrease stock</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="transaction_date">Date *</label>
                        <input type="date" id="transaction_date" name="transaction_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="notes">Notes</label>
                    <textarea id="notes" name="notes" rows="3" placeholder="Add any relevant notes about this transaction..."></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Record Transaction</button>
                    <a href="inventory.php" class="btn btn-light">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>