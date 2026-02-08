<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $category_id = $conn->real_escape_string($_POST['category_id']);
    $item_description = $conn->real_escape_string($_POST['item_description']);
    $unit = $conn->real_escape_string($_POST['unit']);
    $items_received = intval($_POST['items_received']);
    $items_distributed = intval($_POST['items_distributed']);
    $items_on_hand = $items_received - $items_distributed;
    $expiration_date = !empty($_POST['expiration_date']) ? $_POST['expiration_date'] : NULL;
    
    $sql = "INSERT INTO inventory_items (category_id, item_description, unit, items_received, items_distributed, items_on_hand, expiration_date) 
            VALUES ('$category_id', '$item_description', '$unit', $items_received, $items_distributed, $items_on_hand, " . 
            ($expiration_date ? "'$expiration_date'" : "NULL") . ")";
    
    if ($conn->query($sql)) {
        $item_id = $conn->insert_id;
        
        // Record initial transaction
        if ($items_received > 0) {
            $trans_sql = "INSERT INTO transactions (item_id, transaction_type, quantity, transaction_date, notes, created_by) 
                        VALUES ($item_id, 'received', $items_received, CURDATE(), 'Initial stock', {$_SESSION['user_id']})";
            $conn->query($trans_sql);
        }
        
        $message = 'Item added successfully!';
    } else {
        $error = 'Error adding item: ' . $conn->error;
    }
}

// Get categories
$cat_sql = "SELECT * FROM categories ORDER BY category_name";
$categories = $conn->query($cat_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Item - CDRRMO Inventory System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1>Add New Inventory Item</h1>
            <a href="inventory.php" class="btn btn-secondary">← Back to Inventory</a>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="form-container">
            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label for="category_id">Category *</label>
                        <select id="category_id" name="category_id" required>
                            <option value="">Select Category</option>
                            <?php while ($cat = $categories->fetch_assoc()): ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo $cat['category_name']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="unit">Unit</label>
                        <input type="text" id="unit" name="unit" placeholder="e.g., pcs, boxes, bottles">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="item_description">Item Description *</label>
                    <textarea id="item_description" name="item_description" rows="3" required></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="items_received">Items Received *</label>
                        <input type="number" id="items_received" name="items_received" value="0" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="items_distributed">Items Distributed</label>
                        <input type="number" id="items_distributed" name="items_distributed" value="0" min="0">
                    </div>
                    
                    <div class="form-group">
                        <label for="expiration_date">Expiration Date</label>
                        <input type="date" id="expiration_date" name="expiration_date">
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Add Item</button>
                    <a href="inventory.php" class="btn btn-light">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>