<?php
require_once 'config.php';
check_login();

$page_title = 'Valuation Report';

// Get valuation data
$query = "
    SELECT 
        i.*,
        c.category_name,
        c.color as category_color,
        (i.items_on_hand * i.unit_cost) as current_value,
        (i.items_received * i.unit_cost) as total_received_value,
        (i.items_distributed * i.unit_cost) as total_distributed_value
    FROM inventory_items i
    JOIN categories c ON i.category_id = c.id
    WHERE i.is_active = 1
    ORDER BY current_value DESC
";

$items = $conn->query($query);

// Calculate totals
$totals = [
    'current_value' => 0,
    'received_value' => 0,
    'distributed_value' => 0,
    'items' => 0
];

$items_data = [];
while ($row = $items->fetch_assoc()) {
    $items_data[] = $row;
    $totals['current_value'] += $row['current_value'];
    $totals['received_value'] += $row['total_received_value'];
    $totals['distributed_value'] += $row['total_distributed_value'];
    $totals['items']++;
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
        <h3 style="margin: 15px 0 5px 0; color: var(--gray-700); font-size: 20px; font-weight: 600;">INVENTORY VALUATION REPORT</h3>
        <p style="margin: 0; color: var(--gray-600);">Generated: <?php echo date('F d, Y h:i A'); ?></p>
    </div>
</div>

<!-- Summary Statistics -->
<div class="card" style="margin-bottom: 20px;">
    <div style="padding: 20px;">
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px;">
            <div style="text-align: center; padding: 20px; background: var(--light-blue); border-radius: 8px;">
                <div style="font-size: 13px; color: var(--primary-blue); margin-bottom: 8px;">Current Inventory Value</div>
                <div style="font-size: 32px; font-weight: 700; color: var(--primary-blue);">
                    ₱<?php echo number_format($totals['current_value'], 2); ?>
                </div>
                <div style="font-size: 12px; color: var(--gray-600); margin-top: 5px;">Based on current stock</div>
            </div>
            <div style="text-align: center; padding: 20px; background: #d1fae5; border-radius: 8px;">
                <div style="font-size: 13px; color: #065f46; margin-bottom: 8px;">Total Received Value</div>
                <div style="font-size: 32px; font-weight: 700; color: #065f46;">
                    ₱<?php echo number_format($totals['received_value'], 2); ?>
                </div>
                <div style="font-size: 12px; color: var(--gray-600); margin-top: 5px;">All time</div>
            </div>
            <div style="text-align: center; padding: 20px; background: #fef3c7; border-radius: 8px;">
                <div style="font-size: 13px; color: #92400e; margin-bottom: 8px;">Total Distributed Value</div>
                <div style="font-size: 32px; font-weight: 700; color: #92400e;">
                    ₱<?php echo number_format($totals['distributed_value'], 2); ?>
                </div>
                <div style="font-size: 12px; color: var(--gray-600); margin-top: 5px;">All time</div>
            </div>
            <div style="text-align: center; padding: 20px; background: #fee2e2; border-radius: 8px;">
                <div style="font-size: 13px; color: #991b1b; margin-bottom: 8px;">Total Items</div>
                <div style="font-size: 32px; font-weight: 700; color: #991b1b;">
                    <?php echo number_format($totals['items']); ?>
                </div>
                <div style="font-size: 12px; color: var(--gray-600); margin-top: 5px;">Active items</div>
            </div>
        </div>
    </div>
</div>

<!-- Valuation Table -->
<div class="card">
    <div style="padding: 20px;">
        <table style="width: 100%; border-collapse: collapse;" id="valuationTable">
            <thead>
                <tr style="background: var(--primary-blue); color: white;">
                    <th style="padding: 12px; text-align: left; border: 1px solid #ddd;">Item Code</th>
                    <th style="padding: 12px; text-align: left; border: 1px solid #ddd;">Description</th>
                    <th style="padding: 12px; text-align: center; border: 1px solid #ddd;">Category</th>
                    <th style="padding: 12px; text-align: center; border: 1px solid #ddd;">On Hand</th>
                    <th style="padding: 12px; text-align: right; border: 1px solid #ddd;">Unit Cost</th>
                    <th style="padding: 12px; text-align: right; border: 1px solid #ddd;">Current Value</th>
                    <th style="padding: 12px; text-align: right; border: 1px solid #ddd;">% of Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items_data as $item): ?>
                    <?php $percentage = $totals['current_value'] > 0 ? ($item['current_value'] / $totals['current_value']) * 100 : 0; ?>
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd;"><strong><?php echo htmlspecialchars($item['item_code']); ?></strong></td>
                        <td style="padding: 10px; border: 1px solid #ddd;"><?php echo htmlspecialchars($item['item_description']); ?></td>
                        <td style="padding: 10px; border: 1px solid #ddd; text-align: center;"><?php echo htmlspecialchars($item['category_name']); ?></td>
                        <td style="padding: 10px; border: 1px solid #ddd; text-align: center; font-weight: 600;">
                            <?php echo number_format($item['items_on_hand']); ?>
                        </td>
                        <td style="padding: 10px; border: 1px solid #ddd; text-align: right;">₱<?php echo number_format($item['unit_cost'], 2); ?></td>
                        <td style="padding: 10px; border: 1px solid #ddd; text-align: right; font-weight: 700; color: var(--primary-blue);">
                            ₱<?php echo number_format($item['current_value'], 2); ?>
                        </td>
                        <td style="padding: 10px; border: 1px solid #ddd; text-align: right;">
                            <?php echo number_format($percentage, 2); ?>%
                        </td>
                    </tr>
                <?php endforeach; ?>
                
                <!-- Totals Row -->
                <tr style="background: var(--gray-100); font-weight: 700;">
                    <td colspan="5" style="padding: 12px; border: 1px solid #ddd; text-align: right;">TOTALS:</td>
                    <td style="padding: 12px; border: 1px solid #ddd; text-align: right; color: var(--primary-blue);">
                        ₱<?php echo number_format($totals['current_value'], 2); ?>
                    </td>
                    <td style="padding: 12px; border: 1px solid #ddd; text-align: right;">100.00%</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
function exportToCSV() {
    const table = document.getElementById('valuationTable');
    let csv = [];
    
    csv.push('"PASSI CITY - DRRMO"');
    csv.push('"INVENTORY VALUATION REPORT"');
    csv.push('"Generated: <?php echo date('F d, Y h:i A'); ?>"');
    csv.push('');
    csv.push('"Current Inventory Value","₱<?php echo number_format($totals['current_value'], 2); ?>"');
    csv.push('"Total Received Value","₱<?php echo number_format($totals['received_value'], 2); ?>"');
    csv.push('"Total Distributed Value","₱<?php echo number_format($totals['distributed_value'], 2); ?>"');
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
    a.download = 'valuation_report_<?php echo date('Y-m-d'); ?>.csv';
    a.click();
}
</script>

<?php require_once 'footer.php'; ?>