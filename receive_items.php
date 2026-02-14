<?php
require_once 'config.php';
check_login();

$page_title = 'Receive Items';

$success = '';
$error = '';

// Get items
$items = $conn->query("
    SELECT i.id, i.item_code, i.item_description, c.category_name, i.unit, i.unit_cost
    FROM inventory_items i
    JOIN categories c ON i.category_id = c.id
    WHERE i.is_active = 1
    ORDER BY i.item_code
");

// Get suppliers
$suppliers = $conn->query("SELECT id, supplier_name FROM suppliers WHERE is_active = 1 ORDER BY supplier_name");

// Pre-select item if coming from item page
$selected_item_id = isset($_GET['item_id']) ? (int)$_GET['item_id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_id = (int)$_POST['item_id'];
    $quantity = (int)$_POST['quantity'];
    $unit_cost = (float)$_POST['unit_cost'];
    $transaction_date = sanitize_input($_POST['transaction_date']);
    $supplier_id = !empty($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : null;
    $reference_number = sanitize_input($_POST['reference_number']);
    $batch_number = sanitize_input($_POST['batch_number']);
    $expiration_date = !empty($_POST['expiration_date']) ? sanitize_input($_POST['expiration_date']) : null;
    $notes = sanitize_input($_POST['notes']);
    
    if ($quantity <= 0) {
        $error = "Quantity must be greater than 0";
    } else {
        // Generate transaction code
        $year = date('Y', strtotime($transaction_date));
        $count_result = $conn->query("SELECT COUNT(*) as count FROM transactions WHERE YEAR(transaction_date) = $year");
        $count = $count_result->fetch_assoc()['count'] + 1;
        $transaction_code = "REC-$year-" . str_pad($count, 4, '0', STR_PAD_LEFT);
        
        // Insert transaction (total_cost is auto-generated)
        $stmt = $conn->prepare("
            INSERT INTO transactions (
                transaction_code, item_id, transaction_type, quantity, unit_cost,
                transaction_date, supplier_id, reference_number, batch_number,
                expiration_date, notes, created_by
            ) VALUES (?, ?, 'received', ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->bind_param(
            "siidssssssi",
            $transaction_code, $item_id, $quantity, $unit_cost,
            $transaction_date, $supplier_id, $reference_number, $batch_number, 
            $expiration_date, $notes, $_SESSION['user_id']
        );
        
        if ($stmt->execute()) {
            // Update inventory
            $update_stmt = $conn->prepare("
                UPDATE inventory_items 
                SET items_received = items_received + ?,
                    items_on_hand = items_on_hand + ?,
                    unit_cost = ?,
                    updated_by = ?
                WHERE id = ?
            ");
            $update_stmt->bind_param("iidii", $quantity, $quantity, $unit_cost, $_SESSION['user_id'], $item_id);
            $update_stmt->execute();
            $update_stmt->close();
            
            log_activity($_SESSION['user_id'], 'receive_items', "Received $quantity items - Transaction: $transaction_code");
            
            $_SESSION['success'] = "Items received successfully! Transaction Code: $transaction_code";
            header('Location: receive_items.php');
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
            <i class="fas fa-plus-circle"></i> Receive Items
        </h2>
        
        <p style="color: var(--gray-600); margin-bottom: 30px;">
            Record incoming inventory items from suppliers or other sources.
        </p>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" id="receiveForm">
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
                            data-unit-cost="<?php echo $item['unit_cost']; ?>"
                            <?php echo $item['id'] == $selected_item_id ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($item['item_code']); ?> - 
                            <?php echo htmlspecialchars(substr($item['item_description'], 0, 60)); ?>
                            (<?php echo htmlspecialchars($item['category_name']); ?>)
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label class="form-label">Quantity Received *</label>
                    <input type="number" name="quantity" class="form-control" min="1" required 
                        placeholder="Enter quantity" onchange="calculateTotal()">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Unit <span id="unitDisplay"></span></label>
                    <div style="background: var(--gray-100); padding: 12px 15px; border-radius: 6px; font-weight: 600; color: var(--gray-700);">
                        <span id="unitText">-</span>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Unit Cost (₱) *</label>
                    <input type="number" name="unit_cost" class="form-control" step="0.01" min="0" required 
                        placeholder="0.00" onchange="calculateTotal()">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Total Cost (₱)</label>
                    <div style="background: var(--light-blue); padding: 12px 15px; border-radius: 6px; font-weight: 700; color: var(--primary-blue); font-size: 18px;">
                        ₱<span id="totalCost">0.00</span>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Transaction Date *</label>
                <input type="date" name="transaction_date" class="form-control" required 
                    value="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d'); ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label">Supplier</label>
                <select name="supplier_id" class="form-control">
                    <option value="">-- Select supplier (optional) --</option>
                    <?php 
                    $suppliers->data_seek(0);
                    while ($supplier = $suppliers->fetch_assoc()): 
                    ?>
                        <option value="<?php echo $supplier['id']; ?>">
                            <?php echo htmlspecialchars($supplier['supplier_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label class="form-label">Reference Number</label>
                    <input type="text" name="reference_number" class="form-control" 
                        placeholder="PO/DR/Invoice number">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Batch Number</label>
                    <input type="text" name="batch_number" class="form-control" 
                        placeholder="Batch/Lot number">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Expiration Date</label>
                    <input type="date" name="expiration_date" class="form-control">
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-control" rows="3" 
                    placeholder="Additional notes or comments about this transaction"></textarea>
            </div>
            
            <div style="display: flex; gap: 10px; margin-top: 30px; padding-top: 20px; border-top: 1px solid var(--gray-200);">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-check"></i> Receive Items
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
    const unitCost = selectedOption.getAttribute('data-unit-cost') || '0.00';
    
    document.getElementById('unitText').textContent = unit;
    
    // Auto-fill unit cost
    const unitCostInput = document.querySelector('input[name="unit_cost"]');
    unitCostInput.value = parseFloat(unitCost).toFixed(2);
    
    calculateTotal();
}

function calculateTotal() {
    const quantity = parseFloat(document.querySelector('input[name="quantity"]').value) || 0;
    const unitCost = parseFloat(document.querySelector('input[name="unit_cost"]').value) || 0;
    const total = quantity * unitCost;
    
    document.getElementById('totalCost').textContent = total.toFixed(2);
}

function resetForm() {
    document.getElementById('receiveForm').reset();
    document.getElementById('unitText').textContent = '-';
    document.getElementById('totalCost').textContent = '0.00';
}

// Initialize if item is pre-selected
<?php if ($selected_item_id): ?>
updateItemDetails();
<?php endif; ?>
</script>

<?php require_once 'footer.php'; ?>