<?php
require_once 'config.php';
check_login();

$page_title = 'Inventory Summary Report';

// Get filter parameters
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$status_filter = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';

// Build query
$where = ["i.is_active = 1"];
$params = [];
$types = "";

if ($category_filter) {
    $where[] = "i.category_id = ?";
    $params[] = $category_filter;
    $types .= "i";
}

if ($status_filter === 'low_stock') {
    $where[] = "i.items_on_hand <= i.minimum_stock_level";
} elseif ($status_filter === 'out_of_stock') {
    $where[] = "i.items_on_hand <= 0";
}

$where_clause = implode(' AND ', $where);

// Get inventory data
$query = "
    SELECT 
        i.*,
        c.category_name,
        c.color as category_color,
        (i.items_on_hand * i.unit_cost) as total_value
    FROM inventory_items i
    JOIN categories c ON i.category_id = c.id
    WHERE $where_clause
    ORDER BY c.category_name, i.item_code
";

$stmt = $conn->prepare($query);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$items = $stmt->get_result();
$stmt->close();

// Get categories for filter
$categories = $conn->query("SELECT id, category_name FROM categories WHERE is_active = 1 ORDER BY category_name");

// Calculate totals
$totals = [
    'items' => 0,
    'on_hand' => 0,
    'value' => 0,
    'received' => 0,
    'distributed' => 0
];

$items_data = [];
while ($row = $items->fetch_assoc()) {
    $items_data[] = $row;
    $totals['items']++;
    $totals['on_hand'] += $row['items_on_hand'];
    $totals['value'] += $row['total_value'];
    $totals['received'] += $row['items_received'];
    $totals['distributed'] += $row['items_distributed'];
}

require_once 'header.php';
?>

<style>
@media print {
    .no-print { display: none !important; }
    body { background: white; }
    .card { box-shadow: none; }
}
</style>

<div class="no-print" style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <a href="reports.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Reports
        </a>
    </div>
    <div style="display: flex; gap: 10px;">
        <button onclick="window.print()" class="btn btn-primary">
            <i class="fas fa-print"></i> Print Report
        </button>
        <button onclick="exportToCSV()" class="btn btn-success">
            <i class="fas fa-file-csv"></i> Export CSV
        </button>
    </div>
</div>

<!-- Filters -->
<div class="card no-print" style="margin-bottom: 20px;">
    <div style="padding: 20px;">
        <h3 style="margin: 0 0 15px 0;">Filters</h3>
        <form method="GET" style="display: flex; gap: 15px; flex-wrap: wrap;">
            <select name="category" class="form-control" style="width: 200px;">
                <option value="">All Categories</option>
                <?php 
                $categories->data_seek(0);
                while ($cat = $categories->fetch_assoc()): 
                ?>
                    <option value="<?php echo $cat['id']; ?>" <?php echo $category_filter == $cat['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat['category_name']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
            
            <select name="status" class="form-control" style="width: 180px;">
                <option value="">All Status</option>
                <option value="low_stock" <?php echo $status_filter === 'low_stock' ? 'selected' : ''; ?>>Low Stock</option>
                <option value="out_of_stock" <?php echo $status_filter === 'out_of_stock' ? 'selected' : ''; ?>>Out of Stock</option>
            </select>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-filter"></i> Apply Filters
            </button>
            
            <?php if ($category_filter || $status_filter): ?>
                <a href="report_inventory.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Clear
                </a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Report Header -->
<div class="card" style="margin-bottom: 20px;">
    <div style="padding: 40px; text-align: center; border-bottom: 3px solid var(--primary-blue);">
        <h1 style="margin: 0; color: var(--primary-blue); font-size: 28px;">PASSI CITY</h1>
        <h2 style="margin: 5px 0 0 0; color: var(--gray-700); font-size: 18px;">DISASTER RISK REDUCTION & MANAGEMENT OFFICE</h2>
        <h3 style="margin: 15px 0 5px 0; color: var(--gray-700); font-size: 20px; font-weight: 600;">INVENTORY SUMMARY REPORT</h3>
        <p style="margin: 0; color: var(--gray-600);">Generated: <?php echo date('F d, Y h:i A'); ?></p>
    </div>
</div>

<!-- Summary Statistics -->
<div class="card" style="margin-bottom: 20px;">
    <div style="padding: 20px;">
        <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 15px;">
            <div style="text-align: center; padding: 15px; background: var(--light-blue); border-radius: 6px;">
                <div style="font-size: 12px; color: var(--primary-blue); margin-bottom: 5px;">Total Items</div>
                <div style="font-size: 24px; font-weight: 700; color: var(--primary-blue);">
                    <?php echo number_format($totals['items']); ?>
                </div>
            </div>
            <div style="text-align: center; padding: 15px; background: #d1fae5; border-radius: 6px;">
                <div style="font-size: 12px; color: #065f46; margin-bottom: 5px;">Items on Hand</div>
                <div style="font-size: 24px; font-weight: 700; color: #065f46;">
                    <?php echo number_format($totals['on_hand']); ?>
                </div>
            </div>
            <div style="text-align: center; padding: 15px; background: #fef3c7; border-radius: 6px;">
                <div style="font-size: 12px; color: #92400e; margin-bottom: 5px;">Total Value</div>
                <div style="font-size: 24px; font-weight: 700; color: #92400e;">
                    ₱<?php echo number_format($totals['value'], 2); ?>
                </div>
            </div>
            <div style="text-align: center; padding: 15px; background: #d1fae5; border-radius: 6px;">
                <div style="font-size: 12px; color: #065f46; margin-bottom: 5px;">Total Received</div>
                <div style="font-size: 24px; font-weight: 700; color: #065f46;">
                    <?php echo number_format($totals['received']); ?>
                </div>
            </div>
            <div style="text-align: center; padding: 15px; background: #fee2e2; border-radius: 6px;">
                <div style="font-size: 12px; color: #991b1b; margin-bottom: 5px;">Total Distributed</div>
                <div style="font-size: 24px; font-weight: 700; color: #991b1b;">
                    <?php echo number_format($totals['distributed']); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Inventory Table -->
<div class="card">
    <div style="padding: 20px;">
        <table style="width: 100%; border-collapse: collapse;" id="inventoryTable">
            <thead>
                <tr style="background: var(--primary-blue); color: white;">
                    <th style="padding: 12px; text-align: left; border: 1px solid #ddd;">Item Code</th>
                    <th style="padding: 12px; text-align: left; border: 1px solid #ddd;">Description</th>
                    <th style="padding: 12px; text-align: center; border: 1px solid #ddd;">Category</th>
                    <th style="padding: 12px; text-align: center; border: 1px solid #ddd;">On Hand</th>
                    <th style="padding: 12px; text-align: center; border: 1px solid #ddd;">Unit</th>
                    <th style="padding: 12px; text-align: right; border: 1px solid #ddd;">Unit Cost</th>
                    <th style="padding: 12px; text-align: right; border: 1px solid #ddd;">Total Value</th>
                    <th style="padding: 12px; text-align: center; border: 1px solid #ddd;">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items_data as $item): ?>
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd;"><strong><?php echo htmlspecialchars($item['item_code']); ?></strong></td>
                        <td style="padding: 10px; border: 1px solid #ddd;"><?php echo htmlspecialchars($item['item_description']); ?></td>
                        <td style="padding: 10px; border: 1px solid #ddd; text-align: center;"><?php echo htmlspecialchars($item['category_name']); ?></td>
                        <td style="padding: 10px; border: 1px solid #ddd; text-align: center; font-weight: 600;">
                            <?php echo number_format($item['items_on_hand']); ?>
                        </td>
                        <td style="padding: 10px; border: 1px solid #ddd; text-align: center;"><?php echo htmlspecialchars($item['unit'] ?? 'pcs'); ?></td>
                        <td style="padding: 10px; border: 1px solid #ddd; text-align: right;">₱<?php echo number_format($item['unit_cost'], 2); ?></td>
                        <td style="padding: 10px; border: 1px solid #ddd; text-align: right; font-weight: 600;">₱<?php echo number_format($item['total_value'], 2); ?></td>
                        <td style="padding: 10px; border: 1px solid #ddd; text-align: center;">
                            <?php if ($item['items_on_hand'] <= 0): ?>
                                <span style="color: #dc2626; font-weight: 600;">Out of Stock</span>
                            <?php elseif ($item['items_on_hand'] <= $item['minimum_stock_level']): ?>
                                <span style="color: #f59e0b; font-weight: 600;">Low Stock</span>
                            <?php else: ?>
                                <span style="color: #059669; font-weight: 600;">In Stock</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                
                <?php if (empty($items_data)): ?>
                    <tr>
                        <td colspan="8" style="padding: 30px; text-align: center; color: var(--gray-500); border: 1px solid #ddd;">
                            No items found
                        </td>
                    </tr>
                <?php endif; ?>
                
                <!-- Totals Row -->
                <tr style="background: var(--gray-100); font-weight: 700;">
                    <td colspan="3" style="padding: 12px; border: 1px solid #ddd; text-align: right;">TOTALS:</td>
                    <td style="padding: 12px; border: 1px solid #ddd; text-align: center;"><?php echo number_format($totals['on_hand']); ?></td>
                    <td style="padding: 12px; border: 1px solid #ddd;"></td>
                    <td style="padding: 12px; border: 1px solid #ddd;"></td>
                    <td style="padding: 12px; border: 1px solid #ddd; text-align: right;">₱<?php echo number_format($totals['value'], 2); ?></td>
                    <td style="padding: 12px; border: 1px solid #ddd;"></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
function exportToCSV() {
    const table = document.getElementById('inventoryTable');
    let csv = [];
    
    // Add report header
    csv.push('"PASSI CITY - DRRMO"');
    csv.push('"INVENTORY SUMMARY REPORT"');
    csv.push('"Generated: <?php echo date('F d, Y h:i A'); ?>"');
    csv.push('');
    
    // Get table data
    for (let row of table.rows) {
        let rowData = [];
        for (let cell of row.cells) {
            rowData.push('"' + cell.textContent.trim().replace(/"/g, '""') + '"');
        }
        csv.push(rowData.join(','));
    }
    
    // Download
    const blob = new Blob([csv.join('\n')], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'inventory_report_<?php echo date('Y-m-d'); ?>.csv';
    a.click();
}
</script>

<?php require_once 'footer.php'; ?>