<?php
require_once 'config.php';
check_login();

$page_title = 'View Item';

if (!isset($_GET['id'])) {
    header('Location: inventory.php');
    exit();
}

$item_id = (int)$_GET['id'];

// Get item details with category
$query = "
    SELECT 
        i.*,
        c.category_name,
        c.color as category_color,
        sl.location_name
    FROM inventory_items i
    JOIN categories c ON i.category_id = c.id
    LEFT JOIN storage_locations sl ON i.storage_location_id = sl.id
    WHERE i.id = ?
";

$stmt = $conn->prepare($query);
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

// Get recent transactions
$transactions = $conn->query("
    SELECT 
        t.*,
        u.full_name as created_by_name,
        s.supplier_name
    FROM transactions t
    LEFT JOIN users u ON t.created_by = u.id
    LEFT JOIN suppliers s ON t.supplier_id = s.id
    WHERE t.item_id = $item_id
    ORDER BY t.transaction_date DESC, t.created_at DESC
    LIMIT 10
");

require_once 'header.php';
?>

<style>
.inventory-view {
    max-width: 900px;
    margin: 0 auto;
}
.inventory-form {
    background: white;
    padding: 40px;
    border-radius: 8px;
    box-shadow: var(--shadow);
    margin-bottom: 20px;
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
    padding: 15px;
    border: 1px solid var(--gray-300);
    vertical-align: middle;
}
.status-good {
    background: #d1fae5;
    color: #065f46;
    font-size: 20px;
    font-weight: 700;
}
.status-warning {
    background: #fef3c7;
    color: #92400e;
    font-size: 20px;
    font-weight: 700;
}
.status-danger {
    background: #fee2e2;
    color: #991b1b;
    font-size: 20px;
    font-weight: 700;
}
</style>

<div class="inventory-view">
    <div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
        <a href="inventory.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Inventory
        </a>
        <div style="display: flex; gap: 10px;">
            <a href="edit_item.php?id=<?php echo $item_id; ?>" class="btn btn-warning">
                <i class="fas fa-edit"></i> Edit Item
            </a>
            <a href="receive_items.php?item_id=<?php echo $item_id; ?>" class="btn btn-success">
                <i class="fas fa-plus-circle"></i> Receive Stock
            </a>
            <a href="distribute_items.php?item_id=<?php echo $item_id; ?>" class="btn btn-primary">
                <i class="fas fa-minus-circle"></i> Distribute Stock
            </a>
        </div>
    </div>

    <div class="inventory-form">
        <div class="form-header">
            <h1>PASSI CITY</h1>
            <h2>DISASTER RISK REDUCTION & MANAGEMENT OFFICE</h2>
            <h3>INVENTORY OF SUPPLIES (<?php echo htmlspecialchars($item['category_name']); ?>)</h3>
        </div>
        
        <div style="background: var(--light-blue); padding: 20px; border-radius: 8px; margin-bottom: 20px; text-align: center;">
            <div style="font-size: 14px; color: var(--gray-600); margin-bottom: 5px;">ITEM CODE</div>
            <div style="font-size: 32px; font-weight: 700; color: var(--primary-blue);">
                <?php echo htmlspecialchars($item['item_code']); ?>
            </div>
        </div>
        
        <table class="inventory-table">
            <thead>
                <tr>
                    <th style="width: 60px;">No.</th>
                    <th>Item Description</th>
                    <th style="width: 130px;">No. of items<br>received</th>
                    <th style="width: 130px;">No. of items<br>distributed</th>
                    <th style="width: 130px;">No. of items<br>on hand</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="text-align: center; background: var(--gray-100); font-weight: 600; font-size: 16px;">1</td>
                    <td style="font-size: 15px; line-height: 1.6;">
                        <?php echo nl2br(htmlspecialchars($item['item_description'])); ?>
                    </td>
                    <td class="status-good" style="text-align: center;">
                        <?php echo number_format($item['items_received']); ?>
                    </td>
                    <td class="status-warning" style="text-align: center;">
                        <?php echo number_format($item['items_distributed']); ?>
                    </td>
                    <td class="<?php 
                        if ($item['items_on_hand'] <= 0) echo 'status-danger';
                        elseif ($item['items_on_hand'] <= $item['minimum_stock_level']) echo 'status-warning';
                        else echo 'status-good';
                    ?>" style="text-align: center;">
                        <?php echo number_format($item['items_on_hand']); ?>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <?php if ($item['items_on_hand'] <= 0 || $item['items_on_hand'] <= $item['minimum_stock_level']): ?>
        <div style="margin-top: 20px;">
            <?php if ($item['items_on_hand'] <= 0): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <strong>OUT OF STOCK</strong> - Immediate restocking required
                </div>
            <?php elseif ($item['items_on_hand'] <= $item['minimum_stock_level']): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> <strong>LOW STOCK WARNING</strong> - Current stock (<?php echo number_format($item['items_on_hand']); ?>) is below minimum level (<?php echo number_format($item['minimum_stock_level']); ?>)
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- Item Details -->
        <div style="margin-top: 30px; padding: 20px; background: var(--gray-50); border-radius: 8px;">
            <h4 style="margin: 0 0 15px 0; color: var(--gray-700); font-weight: 600;">
                <i class="fas fa-info-circle"></i> Item Details
            </h4>
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px;">
                <div>
                    <div style="font-size: 12px; color: var(--gray-500); margin-bottom: 5px;">Unit of Measurement</div>
                    <div style="font-weight: 600; color: var(--gray-800); font-size: 15px;">
                        <?php echo htmlspecialchars($item['unit'] ?? 'pcs'); ?>
                    </div>
                </div>
                <div>
                    <div style="font-size: 12px; color: var(--gray-500); margin-bottom: 5px;">Unit Cost</div>
                    <div style="font-weight: 600; color: var(--gray-800); font-size: 15px;">
                        ₱<?php echo number_format($item['unit_cost'] ?? 0, 2); ?>
                    </div>
                </div>
                <div>
                    <div style="font-size: 12px; color: var(--gray-500); margin-bottom: 5px;">Total Value</div>
                    <div style="font-weight: 600; color: var(--primary-blue); font-size: 15px;">
                        ₱<?php echo number_format(($item['items_on_hand'] ?? 0) * ($item['unit_cost'] ?? 0), 2); ?>
                    </div>
                </div>
                <div>
                    <div style="font-size: 12px; color: var(--gray-500); margin-bottom: 5px;">Minimum Stock Level</div>
                    <div style="font-weight: 600; color: var(--gray-800); font-size: 15px;">
                        <?php echo number_format($item['minimum_stock_level'] ?? 0); ?> <?php echo htmlspecialchars($item['unit'] ?? 'pcs'); ?>
                    </div>
                </div>
                <div>
                    <div style="font-size: 12px; color: var(--gray-500); margin-bottom: 5px;">Storage Location</div>
                    <div style="font-weight: 600; color: var(--gray-800); font-size: 15px;">
                        <?php if ($item['location_name']): ?>
                            <i class="fas fa-warehouse" style="color: var(--primary-blue); margin-right: 5px;"></i>
                            <?php echo htmlspecialchars($item['location_name']); ?>
                        <?php else: ?>
                            <span style="color: var(--gray-400);">Not assigned</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div>
                    <div style="font-size: 12px; color: var(--gray-500); margin-bottom: 5px;">Expiration Date</div>
                    <div style="font-weight: 600; font-size: 15px;">
                        <?php if ($item['expiration_date']): ?>
                            <?php 
                            $expiry = strtotime($item['expiration_date']);
                            $today = time();
                            $days_diff = floor(($expiry - $today) / (60 * 60 * 24));
                            
                            if ($days_diff < 0): ?>
                                <span style="color: var(--danger);">
                                    <?php echo date('M d, Y', $expiry); ?> (Expired)
                                </span>
                            <?php elseif ($days_diff <= 30): ?>
                                <span style="color: var(--warning);">
                                    <?php echo date('M d, Y', $expiry); ?> (<?php echo $days_diff; ?> days)
                                </span>
                            <?php else: ?>
                                <span style="color: var(--gray-800);">
                                    <?php echo date('M d, Y', $expiry); ?>
                                </span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span style="color: var(--gray-400);">No expiration</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="table-container">
        <div class="table-header">
            <h3 class="table-title">Recent Transaction History</h3>
            <a href="transactions.php?item_id=<?php echo $item_id; ?>" class="btn btn-sm btn-primary">
                View All Transactions
            </a>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Transaction Code</th>
                        <th>Type</th>
                        <th>Quantity</th>
                        <th>Supplier/Recipient</th>
                        <th>Processed By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($transactions->num_rows > 0): ?>
                        <?php while ($trans = $transactions->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($trans['transaction_date'])); ?></td>
                                <td><strong><?php echo htmlspecialchars($trans['transaction_code']); ?></strong></td>
                                <td>
                                    <?php 
                                    $badge_class = 'badge-info';
                                    if ($trans['transaction_type'] === 'received') $badge_class = 'badge-success';
                                    if ($trans['transaction_type'] === 'distributed') $badge_class = 'badge-warning';
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>">
                                        <?php echo ucfirst($trans['transaction_type']); ?>
                                    </span>
                                </td>
                                <td>
                                    <strong style="color: <?php echo in_array($trans['transaction_type'], ['distributed', 'damaged', 'expired']) ? 'var(--danger)' : 'var(--success)'; ?>; font-size: 15px;">
                                        <?php echo in_array($trans['transaction_type'], ['distributed', 'damaged', 'expired']) ? '-' : '+'; ?>
                                        <?php echo number_format($trans['quantity']); ?>
                                    </strong>
                                </td>
                                <td>
                                    <?php 
                                    if ($trans['supplier_name']) {
                                        echo '<i class="fas fa-truck"></i> ' . htmlspecialchars($trans['supplier_name']);
                                    } elseif ($trans['recipient_name']) {
                                        echo '<i class="fas fa-user"></i> ' . htmlspecialchars($trans['recipient_name']);
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($trans['created_by_name'] ?? 'System'); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center" style="padding: 40px;">
                                <i class="fas fa-inbox" style="font-size: 48px; color: var(--gray-400); margin-bottom: 10px;"></i>
                                <div style="color: var(--gray-500);">No transactions recorded yet for this item</div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>