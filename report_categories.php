<?php
require_once 'config.php';
check_login();

$page_title = 'Category Report';

// Get category data with totals
$query = "
    SELECT 
        c.id,
        c.category_name,
        c.category_code,
        c.color,
        COUNT(i.id) as item_count,
        SUM(i.items_on_hand) as total_on_hand,
        SUM(i.items_received) as total_received,
        SUM(i.items_distributed) as total_distributed,
        SUM(i.items_on_hand * i.unit_cost) as total_value,
        SUM(CASE WHEN i.items_on_hand <= i.minimum_stock_level THEN 1 ELSE 0 END) as low_stock_count
    FROM categories c
    LEFT JOIN inventory_items i ON c.id = i.category_id AND i.is_active = 1
    WHERE c.is_active = 1
    GROUP BY c.id, c.category_name, c.category_code, c.color
    ORDER BY c.category_name
";

$categories = $conn->query($query);

// Calculate grand totals
$grand_totals = [
    'items' => 0,
    'on_hand' => 0,
    'received' => 0,
    'distributed' => 0,
    'value' => 0,
    'low_stock' => 0
];

$cat_data = [];
while ($row = $categories->fetch_assoc()) {
    $cat_data[] = $row;
    $grand_totals['items'] += $row['item_count'];
    $grand_totals['on_hand'] += $row['total_on_hand'];
    $grand_totals['received'] += $row['total_received'];
    $grand_totals['distributed'] += $row['total_distributed'];
    $grand_totals['value'] += $row['total_value'];
    $grand_totals['low_stock'] += $row['low_stock_count'];
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

<!-- Report Header -->
<div class="card" style="margin-bottom: 20px;">
    <div style="padding: 40px; text-align: center; border-bottom: 3px solid var(--primary-blue);">
        <h1 style="margin: 0; color: var(--primary-blue); font-size: 28px;">PASSI CITY</h1>
        <h2 style="margin: 5px 0 0 0; color: var(--gray-700); font-size: 18px;">DISASTER RISK REDUCTION & MANAGEMENT OFFICE</h2>
        <h3 style="margin: 15px 0 5px 0; color: var(--gray-700); font-size: 20px; font-weight: 600;">INVENTORY BY CATEGORY REPORT</h3>
        <p style="margin: 0; color: var(--gray-600);">Generated: <?php echo date('F d, Y h:i A'); ?></p>
    </div>
</div>

<!-- Summary Statistics -->
<div class="card" style="margin-bottom: 20px;">
    <div style="padding: 20px;">
        <h3 style="margin: 0 0 15px 0; color: var(--gray-700);">Summary</h3>
        <div style="display: grid; grid-template-columns: repeat(6, 1fr); gap: 15px;">
            <div style="text-align: center; padding: 15px; background: var(--light-blue); border-radius: 6px;">
                <div style="font-size: 12px; color: var(--primary-blue); margin-bottom: 5px;">Total Items</div>
                <div style="font-size: 24px; font-weight: 700; color: var(--primary-blue);">
                    <?php echo number_format($grand_totals['items']); ?>
                </div>
            </div>
            <div style="text-align: center; padding: 15px; background: #d1fae5; border-radius: 6px;">
                <div style="font-size: 12px; color: #065f46; margin-bottom: 5px;">On Hand</div>
                <div style="font-size: 24px; font-weight: 700; color: #065f46;">
                    <?php echo number_format($grand_totals['on_hand']); ?>
                </div>
            </div>
            <div style="text-align: center; padding: 15px; background: #d1fae5; border-radius: 6px;">
                <div style="font-size: 12px; color: #065f46; margin-bottom: 5px;">Received</div>
                <div style="font-size: 24px; font-weight: 700; color: #065f46;">
                    <?php echo number_format($grand_totals['received']); ?>
                </div>
            </div>
            <div style="text-align: center; padding: 15px; background: #fef3c7; border-radius: 6px;">
                <div style="font-size: 12px; color: #92400e; margin-bottom: 5px;">Distributed</div>
                <div style="font-size: 24px; font-weight: 700; color: #92400e;">
                    <?php echo number_format($grand_totals['distributed']); ?>
                </div>
            </div>
            <div style="text-align: center; padding: 15px; background: #fee2e2; border-radius: 6px;">
                <div style="font-size: 12px; color: #991b1b; margin-bottom: 5px;">Total Value</div>
                <div style="font-size: 24px; font-weight: 700; color: #991b1b;">
                    ₱<?php echo number_format($grand_totals['value'], 2); ?>
                </div>
            </div>
            <div style="text-align: center; padding: 15px; background: #fee2e2; border-radius: 6px;">
                <div style="font-size: 12px; color: #991b1b; margin-bottom: 5px;">Low Stock</div>
                <div style="font-size: 24px; font-weight: 700; color: #991b1b;">
                    <?php echo number_format($grand_totals['low_stock']); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Category Table -->
<div class="card">
    <div style="padding: 20px;">
        <table style="width: 100%; border-collapse: collapse;" id="categoryTable">
            <thead>
                <tr style="background: var(--primary-blue); color: white;">
                    <th style="padding: 12px; text-align: left; border: 1px solid #ddd;">Category</th>
                    <th style="padding: 12px; text-align: left; border: 1px solid #ddd;">Code</th>
                    <th style="padding: 12px; text-align: center; border: 1px solid #ddd;">Item Count</th>
                    <th style="padding: 12px; text-align: center; border: 1px solid #ddd;">On Hand</th>
                    <th style="padding: 12px; text-align: center; border: 1px solid #ddd;">Received</th>
                    <th style="padding: 12px; text-align: center; border: 1px solid #ddd;">Distributed</th>
                    <th style="padding: 12px; text-align: right; border: 1px solid #ddd;">Total Value</th>
                    <th style="padding: 12px; text-align: center; border: 1px solid #ddd;">Low Stock</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cat_data as $cat): ?>
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd;">
                            <span style="display: inline-block; width: 12px; height: 12px; background: <?php echo htmlspecialchars($cat['color']); ?>; border-radius: 3px; margin-right: 8px;"></span>
                            <strong><?php echo htmlspecialchars($cat['category_name']); ?></strong>
                        </td>
                        <td style="padding: 10px; border: 1px solid #ddd;"><?php echo htmlspecialchars($cat['category_code']); ?></td>
                        <td style="padding: 10px; border: 1px solid #ddd; text-align: center; font-weight: 600;">
                            <?php echo number_format($cat['item_count']); ?>
                        </td>
                        <td style="padding: 10px; border: 1px solid #ddd; text-align: center; font-weight: 600;">
                            <?php echo number_format($cat['total_on_hand']); ?>
                        </td>
                        <td style="padding: 10px; border: 1px solid #ddd; text-align: center;">
                            <?php echo number_format($cat['total_received']); ?>
                        </td>
                        <td style="padding: 10px; border: 1px solid #ddd; text-align: center;">
                            <?php echo number_format($cat['total_distributed']); ?>
                        </td>
                        <td style="padding: 10px; border: 1px solid #ddd; text-align: right; font-weight: 600;">
                            ₱<?php echo number_format($cat['total_value'], 2); ?>
                        </td>
                        <td style="padding: 10px; border: 1px solid #ddd; text-align: center;">
                            <?php if ($cat['low_stock_count'] > 0): ?>
                                <span style="color: #dc2626; font-weight: 600;"><?php echo number_format($cat['low_stock_count']); ?></span>
                            <?php else: ?>
                                <span style="color: #059669;">0</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                
                <!-- Totals Row -->
                <tr style="background: var(--gray-100); font-weight: 700;">
                    <td colspan="2" style="padding: 12px; border: 1px solid #ddd; text-align: right;">TOTALS:</td>
                    <td style="padding: 12px; border: 1px solid #ddd; text-align: center;"><?php echo number_format($grand_totals['items']); ?></td>
                    <td style="padding: 12px; border: 1px solid #ddd; text-align: center;"><?php echo number_format($grand_totals['on_hand']); ?></td>
                    <td style="padding: 12px; border: 1px solid #ddd; text-align: center;"><?php echo number_format($grand_totals['received']); ?></td>
                    <td style="padding: 12px; border: 1px solid #ddd; text-align: center;"><?php echo number_format($grand_totals['distributed']); ?></td>
                    <td style="padding: 12px; border: 1px solid #ddd; text-align: right;">₱<?php echo number_format($grand_totals['value'], 2); ?></td>
                    <td style="padding: 12px; border: 1px solid #ddd; text-align: center;"><?php echo number_format($grand_totals['low_stock']); ?></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
function exportToCSV() {
    const table = document.getElementById('categoryTable');
    let csv = [];
    
    csv.push('"PASSI CITY - DRRMO"');
    csv.push('"INVENTORY BY CATEGORY REPORT"');
    csv.push('"Generated: <?php echo date('F d, Y h:i A'); ?>"');
    csv.push('');
    
    for (let row of table.rows) {
        let rowData = [];
        for (let cell of row.cells) {
            rowData.push('"' + cell.textContent.trim().replace(/"/g, '""') + '"');
        }
        csv.push(rowData.join(','));
    }
    
    const blob = new Blob([csv.join('\n')], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'category_report_<?php echo date('Y-m-d'); ?>.csv';
    a.click();
}
</script>

<?php require_once 'footer.php'; ?>