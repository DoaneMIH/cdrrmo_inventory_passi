<?php
require_once 'includes/config.php';
check_login();

$page_title = 'Return Borrowed Items';

$success = '';
$error = '';

// Get search parameter
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';

// Build query with search
$where = ["t.transaction_type = 'distributed'", "t.is_borrowed = 1"];
$params = [];
$types = "";

if ($search) {
    $where[] = "(t.transaction_code LIKE ? OR i.item_code LIKE ? OR i.item_description LIKE ? OR t.recipient_name LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "ssss";
}

$where_clause = implode(' AND ', $where);

// Get borrowed items that haven't been returned
$query = "
    SELECT 
        t.id,
        t.transaction_code,
        t.transaction_date,
        t.quantity,
        t.recipient_name,
        t.recipient_organization,
        t.purpose,
        i.item_code,
        i.item_description,
        i.unit,
        c.category_name,
        COALESCE(
            (SELECT SUM(quantity) 
             FROM transactions 
             WHERE parent_transaction_id = t.id 
             AND transaction_type = 'returned'),
            0
        ) as returned_quantity
    FROM transactions t
    JOIN inventory_items i ON t.item_id = i.id
    JOIN categories c ON i.category_id = c.id
    WHERE $where_clause
    ORDER BY t.transaction_date DESC
";

if ($types) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $borrowed_items = $stmt->get_result();
} else {
    $borrowed_items = $conn->query($query);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $transaction_id = (int)$_POST['transaction_id'];
    $return_quantity = (int)$_POST['return_quantity'];
    $return_date = sanitize_input($_POST['return_date']);
    $return_condition = sanitize_input($_POST['return_condition']);
    $return_notes = sanitize_input($_POST['return_notes']);
    
    // Get original transaction details
    $check_stmt = $conn->prepare("
        SELECT t.*, i.unit_cost, i.id as item_id,
            COALESCE(
                (SELECT SUM(quantity) 
                 FROM transactions 
                 WHERE parent_transaction_id = t.id 
                 AND transaction_type = 'returned'),
                0
            ) as already_returned
        FROM transactions t
        JOIN inventory_items i ON t.item_id = i.id
        WHERE t.id = ? AND t.transaction_type = 'distributed' AND t.is_borrowed = 1
    ");
    $check_stmt->bind_param("i", $transaction_id);
    $check_stmt->execute();
    $original = $check_stmt->get_result()->fetch_assoc();
    $check_stmt->close();
    
    if (!$original) {
        $error = "Original transaction not found or not borrowable";
    } else {
        $remaining = $original['quantity'] - $original['already_returned'];
        
        if ($return_quantity <= 0) {
            $error = "Return quantity must be greater than 0";
        } elseif ($return_quantity > $remaining) {
            $error = "Cannot return more than borrowed. Remaining: $remaining";
        } else {
            // Generate return transaction code
            $year = date('Y', strtotime($return_date));
            $count_result = $conn->query("SELECT COUNT(*) as count FROM transactions WHERE YEAR(transaction_date) = $year");
            $count = $count_result->fetch_assoc()['count'] + 1;
            $return_code = "RET-$year-" . str_pad($count, 4, '0', STR_PAD_LEFT);
            
            // Insert return transaction
            $stmt = $conn->prepare("
                INSERT INTO transactions (
                    transaction_code, item_id, transaction_type, quantity, unit_cost,
                    transaction_date, parent_transaction_id, return_condition,
                    notes, created_by
                ) VALUES (?, ?, 'returned', ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->bind_param(
                "siidsissi",
                $return_code, $original['item_id'], $return_quantity, $original['unit_cost'],
                $return_date, $transaction_id, $return_condition, $return_notes, $_SESSION['user_id']
            );

            if ($stmt->execute()) {
                // Update inventory - add back to stock
                $update_stmt = $conn->prepare("
                    UPDATE inventory_items 
                    SET items_on_hand = items_on_hand + ?,
                        items_distributed = items_distributed - ?,
                        updated_by = ?
                    WHERE id = ?
                ");
                $update_stmt->bind_param("iiii", $return_quantity, $return_quantity, $_SESSION['user_id'], $original['item_id']);
                $update_stmt->execute();
                $update_stmt->close();
                
                log_activity($_SESSION['user_id'], 'return_items', "Returned $return_quantity items - Transaction: $return_code");
                
                $_SESSION['success'] = "Items returned successfully! Transaction Code: $return_code";
                header('Location: return_items.php');
                exit();
            } else {
                $error = "Failed to record return transaction.";
            }
            $stmt->close();
        }
    }
}

require_once 'includes/header.php';
?>

<style>
/* Custom Modal Styles for Centered Display */
#returnModal.modal {
    display: none;
    opacity: 0;
    transition: opacity 0.2s ease;
}

#returnModal.modal.show {
    display: flex !important;
    opacity: 1;
}

#returnModal .modal-content {
    animation: fadeInCenter 0.2s ease;
}

@keyframes fadeInCenter {
    from {
        opacity: 0;
        transform: scale(0.95);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}
</style>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
    </div>
<?php endif; ?>

<div class="card">
    <div style="padding: 30px;">
        <h2 style="margin-bottom: 20px; color: var(--primary);">
            <i class="fas fa-undo-alt"></i> Return Borrowed Items
        </h2>
        
        <p style="color: var(--gray-600); margin-bottom: 20px;">
            Record the return of borrowed inventory items.
        </p>
        
        <!-- Search Bar -->
        <div style="margin-bottom: 25px;">
            <form method="GET" action="" style="display: flex; gap: 10px; max-width: 600px;">
                <div style="flex: 1; position: relative;">
                    <i class="fas fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--gray-400);"></i>
                    <input 
                        type="text" 
                        name="search" 
                        class="form-control" 
                        placeholder="Search by transaction code, item, or borrower name..." 
                        value="<?php echo htmlspecialchars($search); ?>"
                        style="padding-left: 40px;"
                    >
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Search
                </button>
                <?php if ($search): ?>
                    <a href="return_items.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Clear
                    </a>
                <?php endif; ?>
            </form>
            <?php if ($search): ?>
                <p style="margin-top: 10px; color: var(--gray-600); font-size: 14px;">
                    <i class="fas fa-filter"></i> Searching for: <strong><?php echo htmlspecialchars($search); ?></strong>
                </p>
            <?php endif; ?>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <div style="margin-bottom: 30px;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: var(--primary); color: white;">
                        <th style="padding: 12px; text-align: left; border: 1px solid #ddd; color: white;">Transaction Code</th>
                        <th style="padding: 12px; text-align: left; border: 1px solid #ddd; color: white;">Item</th>
                        <th style="padding: 12px; text-align: left; border: 1px solid #ddd;color: white;">Borrower</th>
                        <th style="padding: 12px; text-align: center; border: 1px solid #ddd;color: white;">Borrowed</th>
                        <th style="padding: 12px; text-align: center; border: 1px solid #ddd;color: white;">Returned</th>
                        <th style="padding: 12px; text-align: center; border: 1px solid #ddd;color: white;">Remaining</th>
                        <th style="padding: 12px; text-align: center; border: 1px solid #ddd;color: white;">Date</th>
                        <th style="padding: 12px; text-align: center; border: 1px solid #ddd;color: white;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $borrowed_items->data_seek(0);
                    $has_items = false;
                    $total_count = 0;
                    while ($item = $borrowed_items->fetch_assoc()): 
                        $remaining = $item['quantity'] - $item['returned_quantity'];
                        if ($remaining <= 0) continue;
                        $has_items = true;
                        $total_count++;
                    ?>
                        <tr>
                            <td style="padding: 10px; border: 1px solid #ddd;">
                                <strong><?php echo htmlspecialchars($item['transaction_code']); ?></strong>
                            </td>
                            <td style="padding: 10px; border: 1px solid #ddd;">
                                <div style="font-weight: 600; color: var(--gray-800); margin-bottom: 2px;">
                                    <?php echo htmlspecialchars($item['item_code']); ?>
                                </div>
                                <div style="font-size: 13px; color: var(--gray-600);">
                                    <?php echo htmlspecialchars($item['item_description']); ?>
                                </div>
                            </td>
                            <td style="padding: 10px; border: 1px solid #ddd;">
                                <?php echo htmlspecialchars($item['recipient_name']); ?>
                                <?php if ($item['recipient_organization']): ?>
                                    <br><small style="color: var(--gray-500);"><?php echo htmlspecialchars($item['recipient_organization']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 10px; border: 1px solid #ddd; text-align: center;">
                                <strong><?php echo number_format($item['quantity']); ?></strong> <?php echo $item['unit']; ?>
                            </td>
                            <td style="padding: 10px; border: 1px solid #ddd; text-align: center;">
                                <strong style="color: var(--success);"><?php echo number_format($item['returned_quantity']); ?></strong> <?php echo $item['unit']; ?>
                            </td>
                            <td style="padding: 10px; border: 1px solid #ddd; text-align: center;">
                                <strong style="color: var(--warning); font-size: 15px;"><?php echo number_format($remaining); ?></strong> <?php echo $item['unit']; ?>
                            </td>
                            <td style="padding: 10px; border: 1px solid #ddd; text-align: center;">
                                <?php echo date('M d, Y', strtotime($item['transaction_date'])); ?>
                            </td>
                            <td style="padding: 10px; border: 1px solid #ddd; text-align: center;">
                                <button onclick="openReturnModal(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars(addslashes($item['item_description'])); ?>', <?php echo $remaining; ?>, '<?php echo $item['unit']; ?>')" 
                                    class="btn btn-primary btn-sm">
                                    <i class="fas fa-undo"></i> Return
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    
                    <?php if (!$has_items): ?>
                        <tr>
                            <td colspan="8" style="padding: 30px; text-align: center; color: var(--gray-500); border: 1px solid #ddd;">
                                <?php if ($search): ?>
                                    <i class="fas fa-search" style="font-size: 48px; opacity: 0.3; margin-bottom: 15px;"></i>
                                    <p style="margin: 0;">No borrowed items found matching "<?php echo htmlspecialchars($search); ?>"</p>
                                    <a href="return_items.php" style="color: var(--primary); margin-top: 10px; display: inline-block;">Clear search</a>
                                <?php else: ?>
                                    <i class="fas fa-inbox" style="font-size: 48px; opacity: 0.3; margin-bottom: 15px;"></i>
                                    <p style="margin: 0;">No borrowed items pending return</p>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <?php if ($has_items): ?>
                <div style="margin-top: 15px; padding: 10px; background: var(--gray-50); border-radius: 6px; text-align: right;">
                    <strong>Total Items Pending Return:</strong> <?php echo $total_count; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Return Modal -->
<div id="returnModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-undo-alt"></i> Return Borrowed Items</h3>
            <button class="modal-close" onclick="closeReturnModal()">&times;</button>
        </div>
        <form method="POST" id="returnForm">
            <div class="modal-body">
                <input type="hidden" name="transaction_id" id="return_transaction_id">
                
                <div class="form-group">
                    <label class="form-label">Item</label>
                    <div style="background: var(--gray-100); padding: 12px; border-radius: 6px; font-weight: 600;">
                        <span id="return_item_name"></span>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label class="form-label">Quantity to Return *</label>
                        <input type="number" name="return_quantity" id="return_quantity" class="form-control" min="1" required>
                        <small style="color: var(--gray-500); font-size: 12px;">
                            Maximum: <strong id="max_return">0</strong>
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Return Date *</label>
                        <input type="date" name="return_date" class="form-control" required 
                            value="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Condition *</label>
                    <select name="return_condition" class="form-control" required>
                        <option value="">-- Select condition --</option>
                        <option value="Good">Good - No damage</option>
                        <option value="Fair">Fair - Minor wear and tear</option>
                        <option value="Damaged">Damaged - Needs repair/replacement</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Return Notes</label>
                    <textarea name="return_notes" class="form-control" rows="3" 
                        placeholder="Any notes about the returned items"></textarea>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-check"></i> Record Return
                </button>
                <button type="button" class="btn btn-secondary" onclick="closeReturnModal()">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openReturnModal(transactionId, itemName, maxQuantity, unit) {
    document.getElementById('return_transaction_id').value = transactionId;
    document.getElementById('return_item_name').textContent = itemName;
    document.getElementById('return_quantity').max = maxQuantity;
    document.getElementById('max_return').textContent = maxQuantity + ' ' + unit;
    
    // Show modal with smooth fade-in centered
    const modal = document.getElementById('returnModal');
    modal.classList.add('show');
    modal.style.display = 'flex';
}

function closeReturnModal() {
    const modal = document.getElementById('returnModal');
    modal.classList.remove('show');
    
    // Wait for animation to complete before hiding
    setTimeout(() => {
        modal.style.display = 'none';
        document.getElementById('returnForm').reset();
    }, 200);
}
</script>

<?php require_once 'includes/footer.php'; ?>