<?php
require_once 'config.php';
check_login();

$page_title = 'Add New Item';

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
    $items_received = (int)$_POST['items_received'];
    $items_distributed = (int)$_POST['items_distributed'];
    $items_on_hand = $items_received - $items_distributed;
    
    // Generate item code
    $stmt = $conn->prepare("CALL sp_generate_item_code(?, @item_code)");
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $stmt->close();
    
    $result = $conn->query("SELECT @item_code as item_code");
    $item_code = $result->fetch_assoc()['item_code'];
    
    // Insert item
    $stmt = $conn->prepare("
        INSERT INTO inventory_items (
            item_code, category_id, item_description, unit, unit_cost,
            minimum_stock_level, storage_location_id, expiration_date,
            items_received, items_distributed, items_on_hand,
            created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->bind_param(
        "sissdiisiiii",
        $item_code, $category_id, $item_description, $unit, $unit_cost,
        $minimum_stock_level, $storage_location_id, $expiration_date,
        $items_received, $items_distributed, $items_on_hand,
        $_SESSION['user_id']
    );
    
    if ($stmt->execute()) {
        log_activity($_SESSION['user_id'], 'create_item', "Created item: $item_code - $item_description");
        $_SESSION['success'] = "Item added successfully!";
        header('Location: inventory.php');
        exit();
    } else {
        $error = "Failed to add item. Please try again.";
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
.inventory-table input,
.inventory-table textarea,
.inventory-table select {
    width: 100%;
    border: 1px solid var(--gray-300);
    padding: 8px;
    border-radius: 4px;
}
.inventory-table textarea {
    min-height: 60px;
    resize: vertical;
}
.calculated-field {
    background: var(--light-blue);
    font-size: 18px;
    font-weight: 700;
    color: var(--primary-blue);
    text-align: center;
    padding: 12px !important;
}
</style>

<div class="inventory-form">
    <div class="form-header">
        <h1>PASSI CITY</h1>
        <h2>DISASTER RISK REDUCTION & MANAGEMENT OFFICE</h2>
        <h3>INVENTORY OF SUPPLIES</h3>
    </div>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="form-group" style="margin-bottom: 20px;">
            <label class="form-label" style="font-weight: 600; margin-bottom: 8px; display: block;">
                Select Category *
            </label>
            <select name="category_id" class="form-control" required style="font-size: 14px; padding: 10px;">
                <option value="">-- Select Category --</option>
                <?php 
                $categories->data_seek(0);
                while ($cat = $categories->fetch_assoc()): 
                ?>
                    <option value="<?php echo $cat['id']; ?>">
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
                <input type="text" name="unit" class="form-control" required placeholder="pcs, boxes, bottles, kg, etc." style="padding: 10px;">
            </div>
            
            <div class="form-group" style="margin: 0;">
                <label class="form-label" style="font-weight: 600; margin-bottom: 8px; display: block;">
                    Unit Cost (₱) *
                </label>
                <input type="number" name="unit_cost" class="form-control" step="0.01" min="0" required placeholder="0.00" style="padding: 10px;">
            </div>
            
            <div class="form-group" style="margin: 0;">
                <label class="form-label" style="font-weight: 600; margin-bottom: 8px; display: block;">
                    Minimum Stock Level *
                </label>
                <input type="number" name="minimum_stock_level" class="form-control" min="1" required value="5" style="padding: 10px;">
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
                        <option value="<?php echo $loc['id']; ?>">
                            <?php echo htmlspecialchars($loc['location_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group" style="margin: 0;">
                <label class="form-label" style="font-weight: 600; margin-bottom: 8px; display: block;">
                    Expiration Date (Optional)
                </label>
                <input type="date" name="expiration_date" class="form-control" style="padding: 10px;">
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
                        <textarea name="item_description" required placeholder="Enter item description&#10;e.g., Book paper 70 GSM 8.5 inch x 13 inch"></textarea>
                    </td>
                    <td style="text-align: center;">
                        <input type="number" name="items_received" id="items_received" min="0" value="0" required 
                            onchange="calculateOnHand()" style="text-align: center; font-size: 16px;">
                    </td>
                    <td style="text-align: center;">
                        <input type="number" name="items_distributed" id="items_distributed" min="0" value="0" required 
                            onchange="calculateOnHand()" style="text-align: center; font-size: 16px;">
                    </td>
                    <td class="calculated-field">
                        <span id="items_on_hand">0</span>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <div style="display: flex; gap: 10px; margin-top: 30px; padding-top: 20px; border-top: 2px solid var(--gray-200);">
            <button type="submit" class="btn btn-success" style="font-size: 15px; padding: 12px 30px;">
                <i class="fas fa-save"></i> Add Item to Inventory
            </button>
            <a href="inventory.php" class="btn btn-secondary" style="font-size: 15px; padding: 12px 30px;">
                <i class="fas fa-times"></i> Cancel
            </a>
        </div>
    </form>
</div>

<script>
function calculateOnHand() {
    const received = parseInt(document.getElementById('items_received').value) || 0;
    const distributed = parseInt(document.getElementById('items_distributed').value) || 0;
    const onHand = received - distributed;
    document.getElementById('items_on_hand').textContent = onHand;
}
</script>

<?php require_once 'footer.php'; ?>