<?php
require_once 'includes/config.php';
check_login();

$page_title = 'Supplier Report';

// Get supplier data with transaction totals
$query = "
    SELECT 
        s.*,
        COUNT(t.id) as transaction_count,
        SUM(t.quantity) as total_quantity,
        SUM(t.total_cost) as total_value,
        MAX(t.transaction_date) as last_transaction_date
    FROM suppliers s
    LEFT JOIN transactions t ON s.id = t.supplier_id AND t.transaction_type = 'received'
    WHERE s.is_active = 1
    GROUP BY s.id
    ORDER BY total_value DESC
";

$suppliers = $conn->query($query);

// Calculate grand totals
$grand_totals = [
    'suppliers' => 0,
    'transactions' => 0,
    'quantity' => 0,
    'value' => 0
];

$supplier_data = [];
while ($row = $suppliers->fetch_assoc()) {
    $supplier_data[] = $row;
    $grand_totals['suppliers']++;
    $grand_totals['transactions'] += $row['transaction_count'];
    $grand_totals['quantity'] += $row['total_quantity'];
    $grand_totals['value'] += $row['total_value'];
}

require_once 'includes/header.php';
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
    <button onclick="printProfessionalReport('supplierTable', 'SUPPLIER TRANSACTION REPORT', '')" class="btn btn-primary">
        <i class="fas fa-print"></i> Print Report
    </button>
    <button onclick="exportReportToExcel('supplierTable', 'SUPPLIER TRANSACTION REPORT', 'Supplier_Report_<?php echo date('Y-m-d'); ?>.xlsx')" class="btn btn-success">
        <i class="fas fa-file-excel"></i> Export Excel
    </button>
</div>
</div>

<!-- Report Header -->
<div class="card" style="margin-bottom: 20px;">
    <div style="padding: 40px; text-align: center; border-bottom: 3px solid var(--primary);">
        <h1 style="margin: 0; color: var(--primary); font-size: 28px;">PASSI CITY</h1>
        <h2 style="margin: 5px 0 0 0; color: var(--gray-700); font-size: 18px;">DISASTER RISK REDUCTION & MANAGEMENT OFFICE</h2>
        <h3 style="margin: 15px 0 5px 0; color: var(--gray-700); font-size: 20px; font-weight: 600;">SUPPLIER TRANSACTION REPORT</h3>
        <p style="margin: 0; color: var(--gray-600);">Generated: <?php echo date('F d, Y h:i A'); ?></p>
    </div>
</div>

<!-- Summary Statistics -->
<div class="card" style="margin-bottom: 20px;">
    <div style="padding: 20px;">
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px;">
            <div style="text-align: center; padding: 15px; background: var(--primary-50); border-radius: 6px;">
                <div style="font-size: 12px; color: var(--primary); margin-bottom: 5px;">Total Suppliers</div>
                <div style="font-size: 24px; font-weight: 700; color: var(--primary);">
                    <?php echo number_format($grand_totals['suppliers']); ?>
                </div>
            </div>
            <div style="text-align: center; padding: 15px; background: #d1fae5; border-radius: 6px;">
                <div style="font-size: 12px; color: #065f46; margin-bottom: 5px;">Total Transactions</div>
                <div style="font-size: 24px; font-weight: 700; color: #065f46;">
                    <?php echo number_format($grand_totals['transactions']); ?>
                </div>
            </div>
            <div style="text-align: center; padding: 15px; background: #fef3c7; border-radius: 6px;">
                <div style="font-size: 12px; color: #92400e; margin-bottom: 5px;">Total Items Received</div>
                <div style="font-size: 24px; font-weight: 700; color: #92400e;">
                    <?php echo number_format($grand_totals['quantity']); ?>
                </div>
            </div>
            <div style="text-align: center; padding: 15px; background: #fee2e2; border-radius: 6px;">
                <div style="font-size: 12px; color: #991b1b; margin-bottom: 5px;">Total Value</div>
                <div style="font-size: 24px; font-weight: 700; color: #991b1b;">
                    ₱<?php echo number_format($grand_totals['value'], 2); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Supplier Table -->
<div class="card">
    <div style="padding: 20px;">
        <table style="width: 100%; border-collapse: collapse;" id="supplierTable">
            <thead>
                <tr style="background: var(--primary); color: white;">
                    <th style="padding: 12px; text-align: left; border: 1px solid #ddd; color: white;">Supplier Name</th>
                    <th style="padding: 12px; text-align: left; border: 1px solid #ddd;color: white;">Contact Person</th>
                    <th style="padding: 12px; text-align: left; border: 1px solid #ddd;color: white;">Phone</th>
                    <th style="padding: 12px; text-align: center; border: 1px solid #ddd;color: white;">Transactions</th>
                    <th style="padding: 12px; text-align: center; border: 1px solid #ddd;color: white;">Items Received</th>
                    <th style="padding: 12px; text-align: right; border: 1px solid #ddd;color: white;">Total Value</th>
                    <th style="padding: 12px; text-align: left; border: 1px solid #ddd;color: white;">Last Transaction</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($supplier_data as $supplier): ?>
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd;">
                            <strong><?php echo htmlspecialchars($supplier['supplier_name']); ?></strong>
                        </td>
                        <td style="padding: 10px; border: 1px solid #ddd;">
                            <?php echo htmlspecialchars($supplier['contact_person'] ?? '-'); ?>
                        </td>
                        <td style="padding: 10px; border: 1px solid #ddd;">
                            <?php echo htmlspecialchars($supplier['phone'] ?? '-'); ?>
                        </td>
                        <td style="padding: 10px; border: 1px solid #ddd; text-align: center; font-weight: 600;">
                            <?php echo number_format($supplier['transaction_count']); ?>
                        </td>
                        <td style="padding: 10px; border: 1px solid #ddd; text-align: center; font-weight: 600;">
                            <?php echo number_format($supplier['total_quantity']); ?>
                        </td>
                        <td style="padding: 10px; border: 1px solid #ddd; text-align: right; font-weight: 700; color: var(--primary);">
                            ₱<?php echo number_format($supplier['total_value'], 2); ?>
                        </td>
                        <td style="padding: 10px; border: 1px solid #ddd;">
                            <?php 
                            if ($supplier['last_transaction_date']) {
                                echo date('M d, Y', strtotime($supplier['last_transaction_date']));
                            } else {
                                echo '-';
                            }
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                
                <?php if (empty($supplier_data)): ?>
                    <tr>
                        <td colspan="7" style="padding: 30px; text-align: center; color: var(--gray-500); border: 1px solid #ddd;">
                            No suppliers found
                        </td>
                    </tr>
                <?php endif; ?>
                
                <!-- Totals Row -->
                <tr style="background: var(--gray-100); font-weight: 700;">
                    <td colspan="3" style="padding: 12px; border: 1px solid #ddd; text-align: right;">TOTALS:</td>
                    <td style="padding: 12px; border: 1px solid #ddd; text-align: center;">
                        <?php echo number_format($grand_totals['transactions']); ?>
                    </td>
                    <td style="padding: 12px; border: 1px solid #ddd; text-align: center;">
                        <?php echo number_format($grand_totals['quantity']); ?>
                    </td>
                    <td style="padding: 12px; border: 1px solid #ddd; text-align: right; color: var(--primary);">
                        ₱<?php echo number_format($grand_totals['value'], 2); ?>
                    </td>
                    <td style="padding: 12px; border: 1px solid #ddd;"></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>


<?php require_once 'includes/report_functions.php'; ?>

<?php require_once 'includes/footer.php'; ?>