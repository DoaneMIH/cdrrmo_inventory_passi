<?php
require_once 'config.php';
check_login();

$page_title = 'Dashboard';

// Get statistics
$stats = [
    'total_items' => 0,
    'total_categories' => 0,
    'low_stock' => 0,
    'total_value' => 0
];

// Total active items
$result = $conn->query("SELECT COUNT(*) as count FROM inventory_items WHERE is_active = 1");
$stats['total_items'] = $result->fetch_assoc()['count'];

// Total categories
$result = $conn->query("SELECT COUNT(*) as count FROM categories WHERE is_active = 1");
$stats['total_categories'] = $result->fetch_assoc()['count'];

// Low stock items
$result = $conn->query("SELECT COUNT(*) as count FROM inventory_items WHERE items_on_hand <= minimum_stock_level AND is_active = 1");
$stats['low_stock'] = $result->fetch_assoc()['count'];

// Total inventory value
$result = $conn->query("SELECT SUM(items_on_hand * unit_cost) as total FROM inventory_items WHERE is_active = 1");
$stats['total_value'] = $result->fetch_assoc()['total'] ?? 0;

// Get recent transactions
$recent_transactions = $conn->query("
    SELECT 
        t.transaction_code,
        t.transaction_type,
        t.transaction_date,
        i.item_description,
        t.quantity,
        u.full_name as created_by_name
    FROM transactions t
    JOIN inventory_items i ON t.item_id = i.id
    LEFT JOIN users u ON t.created_by = u.id
    ORDER BY t.created_at DESC
    LIMIT 10
");

// Get low stock items
$low_stock_items = $conn->query("
    SELECT 
        i.item_code,
        i.item_description,
        c.category_name,
        i.items_on_hand,
        i.minimum_stock_level
    FROM inventory_items i
    JOIN categories c ON i.category_id = c.id
    WHERE i.items_on_hand <= i.minimum_stock_level
    AND i.is_active = 1
    ORDER BY i.items_on_hand ASC
    LIMIT 5
");

// Get expiring items
$expiring_items = $conn->query("
    SELECT 
        i.item_code,
        i.item_description,
        i.expiration_date,
        DATEDIFF(i.expiration_date, CURDATE()) as days_until_expiry
    FROM inventory_items i
    WHERE i.expiration_date IS NOT NULL
    AND i.expiration_date >= CURDATE()
    AND DATEDIFF(i.expiration_date, CURDATE()) <= 30
    AND i.is_active = 1
    ORDER BY i.expiration_date ASC
    LIMIT 5
");

require_once 'header.php';
?>

<!-- Dashboard Cards -->
<div class="dashboard-cards">
    <div class="card stat-card">
        <div class="stat-icon blue">
            <i class="fas fa-boxes"></i>
        </div>
        <div class="stat-info">
            <div class="stat-label">Total Items</div>
            <div class="stat-value"><?php echo number_format($stats['total_items']); ?></div>
        </div>
    </div>
    
    <div class="card stat-card">
        <div class="stat-icon yellow">
            <i class="fas fa-tags"></i>
        </div>
        <div class="stat-info">
            <div class="stat-label">Categories</div>
            <div class="stat-value"><?php echo number_format($stats['total_categories']); ?></div>
        </div>
    </div>
    
    <div class="card stat-card">
        <div class="stat-icon red">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div class="stat-info">
            <div class="stat-label">Low Stock Items</div>
            <div class="stat-value"><?php echo number_format($stats['low_stock']); ?></div>
        </div>
    </div>
    
    <div class="card stat-card">
        <div class="stat-icon green">
            <i class="fas fa-peso-sign"></i>
        </div>
        <div class="stat-info">
            <div class="stat-label">Total Value</div>
            <div class="stat-value">₱<?php echo number_format($stats['total_value'], 2); ?></div>
        </div>
    </div>
</div>

<!-- Recent Transactions and Low Stock -->
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 20px;">
    <!-- Recent Transactions -->
    <div class="table-container">
        <div class="table-header">
            <h3 class="table-title">Recent Transactions</h3>
            <a href="transactions.php" class="btn btn-sm btn-primary">View All</a>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Transaction Code</th>
                        <th>Type</th>
                        <th>Item</th>
                        <th>Quantity</th>
                        <th>Date</th>
                        <th>By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recent_transactions->num_rows > 0): ?>
                        <?php while ($row = $recent_transactions->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($row['transaction_code']); ?></strong></td>
                                <td>
                                    <?php 
                                    $badge_class = 'badge-info';
                                    if ($row['transaction_type'] === 'received') $badge_class = 'badge-success';
                                    if ($row['transaction_type'] === 'distributed') $badge_class = 'badge-warning';
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>">
                                        <?php echo ucfirst($row['transaction_type']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars(substr($row['item_description'], 0, 40)) . (strlen($row['item_description']) > 40 ? '...' : ''); ?></td>
                                <td><?php echo number_format($row['quantity']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($row['transaction_date'])); ?></td>
                                <td><?php echo htmlspecialchars($row['created_by_name']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center">No transactions yet</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Low Stock Alert -->
    <div class="table-container">
        <div class="table-header">
            <h3 class="table-title">Low Stock Alert</h3>
            <a href="low_stock.php" class="btn btn-sm btn-danger">View All</a>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Stock</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($low_stock_items->num_rows > 0): ?>
                        <?php while ($row = $low_stock_items->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 600;"><?php echo htmlspecialchars($row['item_code']); ?></div>
                                    <div style="font-size: 12px; color: #6b7280;">
                                        <?php echo htmlspecialchars(substr($row['item_description'], 0, 30)) . (strlen($row['item_description']) > 30 ? '...' : ''); ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge <?php echo $row['items_on_hand'] == 0 ? 'badge-danger' : 'badge-warning'; ?>">
                                        <?php echo $row['items_on_hand']; ?> / <?php echo $row['minimum_stock_level']; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="2" class="text-center">All items are well stocked!</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Expiring Items -->
<?php if ($expiring_items->num_rows > 0): ?>
<div class="table-container">
    <div class="table-header">
        <h3 class="table-title">Expiring Soon (Within 30 Days)</h3>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Item Code</th>
                    <th>Description</th>
                    <th>Expiration Date</th>
                    <th>Days Until Expiry</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $expiring_items->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($row['item_code']); ?></strong></td>
                        <td><?php echo htmlspecialchars($row['item_description']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($row['expiration_date'])); ?></td>
                        <td><?php echo $row['days_until_expiry']; ?> days</td>
                        <td>
                            <?php if ($row['days_until_expiry'] <= 7): ?>
                                <span class="badge badge-danger">Critical</span>
                            <?php elseif ($row['days_until_expiry'] <= 14): ?>
                                <span class="badge badge-warning">Warning</span>
                            <?php else: ?>
                                <span class="badge badge-info">Monitor</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php require_once 'footer.php'; ?>