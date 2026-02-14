<?php
require_once 'config.php';
check_login();

$page_title = 'Edit Item';

if (!isset($_GET['id'])) {
    header('Location: inventory.php');
    exit();
}

$item_id = (int)$_GET['id'];

// Get item details
$stmt = $conn->prepare("SELECT * FROM inventory_items WHERE id = ?");
$stmt->bind_param("i", $item_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Item not found";
    header('Location: inventory.php');
    exit();
}

$item = $result->fetch_assoc();
$stmt->close();

// Get categories
$categories = $conn->query("SELECT id, category_name FROM categories WHERE is_active = 1 ORDER BY category_name");

// Get storage locations
$storage_locations = $conn->query("SELECT id, location_name FROM storage_locations WHERE is_active = 1 ORDER BY location_name");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_id = (int)$_POST['category_id'];
    $item_description = sanitize_input($_POST['item_description']);
    $unit = sanitize_input($_POST['unit']);
    $unit_cost = (float)$_POST['unit_cost'];
    $minimum_stock_level = (int)$_POST['minimum_stock_level'];
    $storage_location_id = !empty($_POST['storage_location_id']) ? (int)$_POST['storage_location_id'] : null;
    $expiration_date = !empty($_POST['expiration_date']) ? $_POST['expiration_date'] : null;
    
    $stmt = $conn->prepare("
        UPDATE inventory_items SET
            category_id = ?, item_description = ?, unit = ?, unit_cost = ?,
            minimum_stock_level = ?, storage_location_id = ?, expiration_date = ?, updated_by = ?
        WHERE id = ?
    ");
    
    $stmt->bind_param("issdiisii", $category_id, $item_description, $unit, $unit_cost, 
                      $minimum_stock_level, $storage_location_id, $expiration_date, $_SESSION['user_id'], $item_id);
    
    if ($stmt->execute()) {
        log_activity($_SESSION['user_id'], 'update_item', "Updated item: " . $item['item_code']);
        $_SESSION['success'] = "Item updated successfully!";
        header('Location: inventory.php');
        exit();
    } else {
        $error = "Failed to update item.";
    }
    $stmt->close();
}

require_once 'header.php';
?>

<style>
.inventory-form {
    max-width: 900px;
    margin: 0 auto;
    background: white;
    padding: 40px;
    border-radius: 8px;
    box-shadow: var(--shadow);
}
.form-header {
    text-align: center;
    margin-bottom: 30px;
    border-bottom: 3px solid var(--primary-blue);
    padding-bottom: 20px;
}
.form-header h1 {
    margin: 0;
    color: var(--primary-blue);
    font-size: 28px;
    font-weight: 700;
}
.form-header h2 {
    margin: 5px 0 0 0;
    color: var(--gray-700);
    font-size: 16px;
    font-weight: 600;
}
.form-header h3 {
    margin: 10px 0 0 0;
    color: var(--gray-600);
    font-size: 14px;
    font-weight: 600;
    text-transform: uppercase;
}
.inventory-table {
    width: 100%;
    border-collapse: collapse;
    margin: 20px 0;
}
.inventory-table th {
    background: var(--primary-blue);
    color: white;
    padding: 12px;
    text-align: center;
    font-size: 13px;
    font-weight: 600;
    border: 1px solid var(--gray-300);
}
.inventory-table td {
    padding: 10px;
    border: 1px solid var(--gray-300);
    vertical-align: middle;
}
.inventory-table textarea {
    width: 100%;
    min-height: 60px;
    resize: vertical;
    border: 1px solid var(--gray-300);
    padding: 8px;
    border-radius: 4px;
}
.readonly-field {
    background: var(--gray-100);
    font-size: 16px;
    font-weight: 600;
    color: var(--gray-700);
    text-align: center;
    padding: 12px !important;
}
.info-box {
    background: var(--light-blue);
    padding: 15px;
    border-radius: 6px;
    border-left: 4px solid var(--secondary-blue);
    margin-bottom: 20px;
}
</style>

<div class="inventory-form">
    <div class="form-header">
        <h1>PASSI CITY</h1>
        <h2>DISASTER RISK REDUCTION & MANAGEMENT OFFICE</h2>
        <h3>INVENTORY OF SUPPLIES - EDIT ITEM</h3>
    </div>
    
    <div class="info-box">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <strong style="color: var(--primary-blue); font-size: 16px;">Item Code:</strong>
                <span style="font-size: 20px; font-weight: 700; color: var(--primary-blue); margin-left: 10px;">
                    <?php echo htmlspecialchars($item['item_code']); ?>
                </span>
            </div>
            <div style="font-size: 12px; color: var(--gray-600);">
                <i class="fas fa-info-circle"></i> Stock levels are read-only. Use "Receive" or "Distribute" to update.
            </div>
        </div>
    </div>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="form-group" style="margin-bottom: 20px;">
            <label class="form-label" style="font-weight: 600; margin-bottom: 8px; display: block;">
                Category *
            </label>
            <select name="category_id" class="form-control" required style="font-size: 14px; padding: 10px;">
                <?php 
                $categories->data_seek(0);
                while ($cat = $categories->fetch_assoc()): 
                ?>
                    <option value="<?php echo $cat['id']; ?>" <?php echo $cat['id'] == $item['category_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat['category_name']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        
        <!-- Additional Fields -->
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin-bottom: 20px;">
            <div class="form-group" style="margin: 0;">
                <label class="form-label" style="font-weight: 600; margin-bottom: 8px; display: block;">
                    Unit of Measurement *
                </label>
                <input type="text" name="unit" class="form-control" required value="<?php echo htmlspecialchars($item['unit'] ?? 'pcs'); ?>" style="padding: 10px;">
            </div>
            
            <div class="form-group" style="margin: 0;">
                <label class="form-label" style="font-weight: 600; margin-bottom: 8px; display: block;">
                    Unit Cost (₱) *
                </label>
                <input type="number" name="unit_cost" class="form-control" step="0.01" min="0" required value="<?php echo $item['unit_cost'] ?? 0; ?>" style="padding: 10px;">
            </div>
            
            <div class="form-group" style="margin: 0;">
                <label class="form-label" style="font-weight: 600; margin-bottom: 8px; display: block;">
                    Minimum Stock Level *
                </label>
                <input type="number" name="minimum_stock_level" class="form-control" min="1" required value="<?php echo $item['minimum_stock_level'] ?? 5; ?>" style="padding: 10px;">
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
            <div class="form-group" style="margin: 0;">
                <label class="form-label" style="font-weight: 600; margin-bottom: 8px; display: block;">
                    Storage Location
                </label>
                <select name="storage_location_id" class="form-control" style="padding: 10px;">
                    <option value="">-- Select Storage Location --</option>
                    <?php 
                    $storage_locations->data_seek(0);
                    while ($loc = $storage_locations->fetch_assoc()): 
                    ?>
                        <option value="<?php echo $loc['id']; ?>" <?php echo ($loc['id'] == $item['storage_location_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($loc['location_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group" style="margin: 0;">
                <label class="form-label" style="font-weight: 600; margin-bottom: 8px; display: block;">
                    Expiration Date (Optional)
                </label>
                <input type="date" name="expiration_date" class="form-control" value="<?php echo $item['expiration_date'] ?? ''; ?>" style="padding: 10px;">
                <small style="color: var(--gray-500); font-size: 12px;">Leave blank if item does not expire</small>
            </div>
        </div>
        
        <table class="inventory-table">
            <thead>
                <tr>
                    <th style="width: 60px;">No.</th>
                    <th>Item Description</th>
                    <th style="width: 120px;">No. of items<br>received</th>
                    <th style="width: 120px;">No. of items<br>distributed</th>
                    <th style="width: 120px;">No. of items<br>on hand</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="text-align: center; background: var(--gray-100); font-weight: 600;">1</td>
                    <td>
                        <textarea name="item_description" required><?php echo htmlspecialchars($item['item_description']); ?></textarea>
                    </td>
                    <td class="readonly-field">
                        <?php echo number_format($item['items_received']); ?>
                    </td>
                    <td class="readonly-field">
                        <?php echo number_format($item['items_distributed']); ?>
                    </td>
                    <td class="readonly-field" style="background: var(--light-blue); color: var(--primary-blue);">
                        <?php echo number_format($item['items_on_hand']); ?>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <div style="display: flex; gap: 10px; margin-top: 30px; padding-top: 20px; border-top: 2px solid var(--gray-200);">
            <button type="submit" class="btn btn-primary" style="font-size: 15px; padding: 12px 30px;">
                <i class="fas fa-save"></i> Update Item
            </button>
            <a href="view_item.php?id=<?php echo $item_id; ?>" class="btn btn-secondary" style="font-size: 15px; padding: 12px 30px;">
                <i class="fas fa-eye"></i> View Details
            </a>
            <a href="inventory.php" class="btn btn-secondary" style="font-size: 15px; padding: 12px 30px;">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </form>
</div>

<?php require_once 'footer.php'; ?>