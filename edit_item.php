<?php
require_once 'config.php';
require_once 'audit_helper.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$message = '';
$error = '';
$item_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get item details
$sql = "SELECT * FROM inventory_items WHERE id = $item_id";
$result = $conn->query($sql);

if (!$result || $result->num_rows == 0) {
    header('Location: inventory.php');
    exit();
}

$item = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // GET OLD VALUES BEFORE UPDATE - ADD THIS
    $old_data = get_item_data($conn, $item_id);
    
    $category_id = $conn->real_escape_string($_POST['category_id']);
    $item_description = $conn->real_escape_string($_POST['item_description']);
    $unit = $conn->real_escape_string($_POST['unit']);
    $items_received = intval($_POST['items_received']);
    $items_distributed = intval($_POST['items_distributed']);
    $items_on_hand = $items_received - $items_distributed;
    $expiration_date = !empty($_POST['expiration_date']) ? $_POST['expiration_date'] : NULL;
    
    $update_sql = "UPDATE inventory_items SET 
                   category_id = '$category_id',
                   item_description = '$item_description',
                   unit = '$unit',
                   items_received = $items_received,
                   items_distributed = $items_distributed,
                   items_on_hand = $items_on_hand,
                   expiration_date = " . ($expiration_date ? "'$expiration_date'" : "NULL") . ",
                   updated_by = {$_SESSION['user_id']}
                   WHERE id = $item_id";
    
    if ($conn->query($update_sql)) {
        // GET NEW VALUES AFTER UPDATE - ADD THIS
        $new_data = [
            'category_id' => $category_id,
            'item_description' => $item_description,
            'unit' => $unit,
            'items_received' => $items_received,
            'items_distributed' => $items_distributed,
            'items_on_hand' => $items_on_hand,
            'expiration_date' => $expiration_date,
            'updated_by' => $_SESSION['user_id']
        ];
        
        // LOG THE UPDATE WITH BEFORE/AFTER VALUES - ADD THIS
        log_item_update($conn, $_SESSION['user_id'], $item_id, $old_data, $new_data);
        
        $message = 'Item updated successfully!';
        
        // Refresh item data
        $result = $conn->query($sql);
        $item = $result->fetch_assoc();
    } else {
        $error = 'Error updating item: ' . $conn->error;
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
    <title>Edit Item - CDRRMO Inventory System</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <?php include 'header_sidebar.php'; ?>

    <div class="main-content">
    <div class="container">
        <div class="page-header">
            <h1>Edit Inventory Item</h1>
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
                            <?php while ($cat = $categories->fetch_assoc()): ?>
                            <option value="<?php echo $cat['id']; ?>"
                                <?php echo $cat['id'] == $item['category_id'] ? 'selected' : ''; ?>>
                                <?php echo $cat['category_name']; ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="unit">Unit</label>
                        <input type="text" id="unit" name="unit" value="<?php echo htmlspecialchars($item['unit']); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="item_description">Item Description *</label>
                    <textarea id="item_description" name="item_description" rows="3"
                        required><?php echo htmlspecialchars($item['item_description']); ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="items_received">Items Received *</label>
                        <input type="number" id="items_received" name="items_received"
                            value="<?php echo $item['items_received']; ?>" min="0" required>
                    </div>

                    <div class="form-group">
                        <label for="items_distributed">Items Distributed</label>
                        <input type="number" id="items_distributed" name="items_distributed"
                            value="<?php echo $item['items_distributed']; ?>" min="0">
                    </div>

                    <div class="form-group">
                        <label for="expiration_date">Expiration Date</label>
                        <input type="date" id="expiration_date" name="expiration_date"
                            value="<?php echo $item['expiration_date']; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Calculated On Hand: <strong><?php echo $item['items_on_hand']; ?></strong></label>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Update Item</button>
                    <a href="inventory.php" class="btn btn-light">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    </div>
</body>

</html>