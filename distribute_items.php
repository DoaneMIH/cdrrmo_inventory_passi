<?php
require_once 'config.php';
check_login();

$page_title = 'Distribute Items';

$success = '';
$error = '';

// Get items with available stock
$items = $conn->query("
    SELECT i.id, i.item_code, i.item_description, c.category_name, i.unit, i.items_on_hand
    FROM inventory_items i
    JOIN categories c ON i.category_id = c.id
    WHERE i.is_active = 1 AND i.items_on_hand > 0
    ORDER BY i.item_code
");

// Pre-select item if coming from item page
$selected_item_id = isset($_GET['item_id']) ? (int)$_GET['item_id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_id = (int)$_POST['item_id'];
    $quantity = (int)$_POST['quantity'];
    $transaction_date = sanitize_input($_POST['transaction_date']);
    $recipient_name = sanitize_input($_POST['recipient_name']);
    $recipient_organization = sanitize_input($_POST['recipient_organization']);
    $purpose = sanitize_input($_POST['purpose']);
    $reference_number = sanitize_input($_POST['reference_number']);
    $notes = sanitize_input($_POST['notes']);
    
    // Check available stock
    $stock_check = $conn->prepare("SELECT items_on_hand, unit_cost FROM inventory_items WHERE id = ?");
    $stock_check->bind_param("i", $item_id);
    $stock_check->execute();
    $stock_result = $stock_check->get_result();
    $item_data = $stock_result->fetch_assoc();
    $stock_check->close();
    
    if ($quantity <= 0) {
        $error = "Quantity must be greater than 0";
    } elseif ($quantity > $item_data['items_on_hand']) {
        $error = "Insufficient stock. Available: " . $item_data['items_on_hand'];
    } else {
        // Generate transaction code
        $year = date('Y', strtotime($transaction_date));
        $count_result = $conn->query("SELECT COUNT(*) as count FROM transactions WHERE YEAR(transaction_date) = $year");
        $count = $count_result->fetch_assoc()['count'] + 1;
        $transaction_code = "DIS-$year-" . str_pad($count, 4, '0', STR_PAD_LEFT);
        
        $unit_cost = $item_data['unit_cost'];
        
        // Insert transaction (total_cost is auto-generated)
        $stmt = $conn->prepare("
            INSERT INTO transactions (
                transaction_code, item_id, transaction_type, quantity, unit_cost,
                transaction_date, recipient_name, recipient_organization, purpose,
                reference_number, notes, created_by
            ) VALUES (?, ?, 'distributed', ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->bind_param(
            "siidssssssi",
            $transaction_code, $item_id, $quantity, $unit_cost, $transaction_date,
            $recipient_name, $recipient_organization, $purpose, $reference_number,
            $notes, $_SESSION['user_id']
        );
        
        if ($stmt->execute()) {
            // Update inventory
            $update_stmt = $conn->prepare("
                UPDATE inventory_items 
                SET items_distributed = items_distributed + ?,
                    items_on_hand = items_on_hand - ?,
                    updated_by = ?
                WHERE id = ?
            ");
            $update_stmt->bind_param("iiii", $quantity, $quantity, $_SESSION['user_id'], $item_id);
            $update_stmt->execute();
            $update_stmt->close();
            
            log_activity($_SESSION['user_id'], 'distribute_items', "Distributed $quantity items - Transaction: $transaction_code");
            
            $_SESSION['success'] = "Items distributed successfully! Transaction Code: $transaction_code";
            header('Location: distribute_items.php');
            exit();
        } else {
            $error = "Failed to record transaction.";
        }
        $stmt->close();
    }
}

require_once 'header.php';
?>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
    </div>
<?php endif; ?>

<div class="card" style="max-width: 800px; margin: 0 auto;">
    <div style="padding: 30px;">
        <h2 style="margin-bottom: 20px; color: var(--primary-blue);">
            <i class="fas fa-minus-circle"></i> Distribute Items
        </h2>
        
        <p style="color: var(--gray-600); margin-bottom: 30px;">
            Record outgoing inventory items distributed to beneficiaries or other departments.
        </p>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" id="distributeForm">
            <div class="form-group">
                <label class="form-label">Select Item *</label>
                <select name="item_id" id="item_id" class="form-control" required onchange="updateItemDetails()">
                    <option value="">-- Select an item --</option>
                    <?php 
                    $items->data_seek(0);
                    while ($item = $items->fetch_assoc()): 
                    ?>
                        <option value="<?php echo $item['id']; ?>" 
                            data-unit="<?php echo htmlspecialchars($item['unit']); ?>"
                            data-stock="<?php echo $item['items_on_hand']; ?>"
                            <?php echo $item['id'] == $selected_item_id ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($item['item_code']); ?> - 
                            <?php echo htmlspecialchars(substr($item['item_description'], 0, 50)); ?>
                            (Stock: <?php echo number_format($item['items_on_hand']); ?> <?php echo $item['unit']; ?>)
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label class="form-label">Quantity to Distribute *</label>
                    <input type="number" name="quantity" id="quantity" class="form-control" min="1" required 
                        placeholder="Enter quantity">
                    <small style="color: var(--gray-500); font-size: 12px;">
                        Available: <strong id="availableStock">-</strong>
                    </small>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Unit</label>
                    <div style="background: var(--gray-100); padding: 12px 15px; border-radius: 6px; font-weight: 600; color: var(--gray-700);">
                        <span id="unitText">-</span>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Transaction Date *</label>
                <input type="date" name="transaction_date" class="form-control" required 
                    value="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d'); ?>">
            </div>
            
            <div style="background: var(--light-blue); padding: 20px; border-radius: 8px; border-left: 4px solid var(--secondary-blue); margin: 20px 0;">
                <h3 style="margin: 0 0 15px 0; color: var(--primary-blue); font-size: 16px;">
                    <i class="fas fa-user"></i> Recipient Information
                </h3>
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label class="form-label">Recipient Name *</label>
                    <input type="text" name="recipient_name" class="form-control" required 
                        placeholder="Name of person receiving items">
                </div>
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label class="form-label">Organization/Department</label>
                    <input type="text" name="recipient_organization" class="form-control" 
                        placeholder="Organization, department, or barangay">
                </div>
                
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Purpose of Distribution *</label>
                    <textarea name="purpose" class="form-control" rows="2" required 
                        placeholder="Reason or purpose for distributing these items"></textarea>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Reference Number</label>
                <input type="text" name="reference_number" class="form-control" 
                    placeholder="Distribution slip or reference number">
            </div>
            
            <div class="form-group">
                <label class="form-label">Additional Notes</label>
                <textarea name="notes" class="form-control" rows="3" 
                    placeholder="Additional notes or comments about this distribution"></textarea>
            </div>
            
            <div style="display: flex; gap: 10px; margin-top: 30px; padding-top: 20px; border-top: 1px solid var(--gray-200);">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-check"></i> Distribute Items
                </button>
                <button type="reset" class="btn btn-secondary" onclick="resetForm()">
                    <i class="fas fa-redo"></i> Reset Form
                </button>
                <a href="inventory.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script>
function updateItemDetails() {
    const select = document.getElementById('item_id');
    const selectedOption = select.options[select.selectedIndex];
    const unit = selectedOption.getAttribute('data-unit') || '-';
    const stock = selectedOption.getAttribute('data-stock') || '0';
    
    document.getElementById('unitText').textContent = unit;
    document.getElementById('availableStock').textContent = parseInt(stock).toLocaleString() + ' ' + unit;
    
    // Set max quantity
    const quantityInput = document.getElementById('quantity');
    quantityInput.max = stock;
}

function resetForm() {
    document.getElementById('distributeForm').reset();
    document.getElementById('unitText').textContent = '-';
    document.getElementById('availableStock').textContent = '-';
}

// Initialize if item is pre-selected
<?php if ($selected_item_id): ?>
updateItemDetails();
<?php endif; ?>
</script>

<?php require_once 'footer.php'; ?>