<?php
require_once 'config.php';
check_login();

$page_title = 'Transaction Report';

// Get filter parameters
$type_filter = isset($_GET['type']) ? sanitize_input($_GET['type']) : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01');
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');

// Build query
$where = ["1=1"];
$params = [];
$types = "";

if ($type_filter) {
    $where[] = "t.transaction_type = ?";
    $params[] = $type_filter;
    $types .= "s";
}

if ($date_from) {
    $where[] = "t.transaction_date >= ?";
    $params[] = $date_from;
    $types .= "s";
}

if ($date_to) {
    $where[] = "t.transaction_date <= ?";
    $params[] = $date_to;
    $types .= "s";
}

$where_clause = implode(' AND ', $where);

// Get transactions
$query = "
    SELECT 
        t.*,
        i.item_code,
        i.item_description,
        c.category_name,
        s.supplier_name,
        u.full_name as created_by_name
    FROM transactions t
    JOIN inventory_items i ON t.item_id = i.id
    JOIN categories c ON i.category_id = c.id
    LEFT JOIN suppliers s ON t.supplier_id = s.id
    LEFT JOIN users u ON t.created_by = u.id
    WHERE $where_clause
    ORDER BY t.transaction_date DESC, t.created_at DESC
";

$stmt = $conn->prepare($query);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$transactions = $stmt->get_result();
$stmt->close();

// Calculate totals
$totals = [
    'transactions' => 0,
    'received' => 0,
    'distributed' => 0,
    'value' => 0
];

$trans_data = [];
while ($row = $transactions->fetch_assoc()) {
    $trans_data[] = $row;
    $totals['transactions']++;
    if ($row['transaction_type'] === 'received') {
        $totals['received'] += $row['quantity'];
    } elseif ($row['transaction_type'] === 'distributed') {
        $totals['distributed'] += $row['quantity'];
    }
    $totals['value'] += $row['total_cost'];
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
        <form method="GET" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: end;">
            <div>
                <label class="form-label">Transaction Type</label>
                <select name="type" class="form-control" style="width: 180px;">
                    <option value="">All Types</option>
                    <option value="received" <?php echo $type_filter === 'received' ? 'selected' : ''; ?>>Received</option>
                    <option value="distributed" <?php echo $type_filter === 'distributed' ? 'selected' : ''; ?>>Distributed</option>
                </select>
            </div>
            
            <div>
                <label class="form-label">Date From</label>
                <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($date_from); ?>">
            </div>
            
            <div>
                <label class="form-label">Date To</label>
                <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($date_to); ?>">
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-filter"></i> Apply Filters
            </button>
            
            <a href="report_transactions.php" class="btn btn-secondary">
                <i class="fas fa-times"></i> Clear
            </a>
        </form>
    </div>
</div>

<!-- Report Header -->
<div class="card" style="margin-bottom: 20px;">
    <div style="padding: 40px; text-align: center; border-bottom: 3px solid var(--primary-blue);">
        <h1 style="margin: 0; color: var(--primary-blue); font-size: 28px;">PASSI CITY</h1>
        <h2 style="margin: 5px 0 0 0; color: var(--gray-700); font-size: 18px;">DISASTER RISK REDUCTION & MANAGEMENT OFFICE</h2>
        <h3 style="margin: 15px 0 5px 0; color: var(--gray-700); font-size: 20px; font-weight: 600;">TRANSACTION REPORT</h3>
        <p style="margin: 0; color: var(--gray-600);">
            Period: <?php echo date('M d, Y', strtotime($date_from)); ?> to <?php echo date('M d, Y', strtotime($date_to)); ?>
        </p>
        <p style="margin: 5px 0 0 0; color: var(--gray-600);">Generated: <?php echo date('F d, Y h:i A'); ?></p>
    </div>
</div>

<!-- Summary Statistics -->
<div class="card" style="margin-bottom: 20px;">
    <div style="padding: 20px;">
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px;">
            <div style="text-align: center; padding: 15px; background: var(--light-blue); border-radius: 6px;">
                <div style="font-size: 12px; color: var(--primary-blue); margin-bottom: 5px;">Total Transactions</div>
                <div style="font-size: 24px; font-weight: 700; color: var(--primary-blue);">
                    <?php echo number_format($totals['transactions']); ?>
                </div>
            </div>
            <div style="text-align: center; padding: 15px; background: #d1fae5; border-radius: 6px;">
                <div style="font-size: 12px; color: #065f46; margin-bottom: 5px;">Items Received</div>
                <div style="font-size: 24px; font-weight: 700; color: #065f46;">
                    <?php echo number_format($totals['received']); ?>
                </div>
            </div>
            <div style="text-align: center; padding: 15px; background: #fef3c7; border-radius: 6px;">
                <div style="font-size: 12px; color: #92400e; margin-bottom: 5px;">Items Distributed</div>
                <div style="font-size: 24px; font-weight: 700; color: #92400e;">
                    <?php echo number_format($totals['distributed']); ?>
                </div>
            </div>
            <div style="text-align: center; padding: 15px; background: #fee2e2; border-radius: 6px;">
                <div style="font-size: 12px; color: #991b1b; margin-bottom: 5px;">Total Value</div>
                <div style="font-size: 24px; font-weight: 700; color: #991b1b;">
                    ₱<?php echo number_format($totals['value'], 2); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Transactions Table -->
<div class="card">
    <div style="padding: 20px;">
        <table style="width: 100%; border-collapse: collapse; font-size: 13px;" id="transactionsTable">
            <thead>
                <tr style="background: var(--primary-blue); color: white;">
                    <th style="padding: 10px; text-align: left; border: 1px solid #ddd;">Date</th>
                    <th style="padding: 10px; text-align: left; border: 1px solid #ddd;">Transaction Code</th>
                    <th style="padding: 10px; text-align: left; border: 1px solid #ddd;">Item Code</th>
                    <th style="padding: 10px; text-align: left; border: 1px solid #ddd;">Description</th>
                    <th style="padding: 10px; text-align: center; border: 1px solid #ddd;">Type</th>
                    <th style="padding: 10px; text-align: center; border: 1px solid #ddd;">Quantity</th>
                    <th style="padding: 10px; text-align: right; border: 1px solid #ddd;">Unit Cost</th>
                    <th style="padding: 10px; text-align: right; border: 1px solid #ddd;">Total</th>
                    <th style="padding: 10px; text-align: left; border: 1px solid #ddd;">Supplier/Recipient</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($trans_data as $trans): ?>
                    <tr>
                        <td style="padding: 8px; border: 1px solid #ddd;"><?php echo date('M d, Y', strtotime($trans['transaction_date'])); ?></td>
                        <td style="padding: 8px; border: 1px solid #ddd;"><strong><?php echo htmlspecialchars($trans['transaction_code']); ?></strong></td>
                        <td style="padding: 8px; border: 1px solid #ddd;"><?php echo htmlspecialchars($trans['item_code']); ?></td>
                        <td style="padding: 8px; border: 1px solid #ddd;"><?php echo htmlspecialchars(substr($trans['item_description'], 0, 40)); ?></td>
                        <td style="padding: 8px; border: 1px solid #ddd; text-align: center;">
                            <?php echo ucfirst($trans['transaction_type']); ?>
                        </td>
                        <td style="padding: 8px; border: 1px solid #ddd; text-align: center; font-weight: 600;">
                            <?php echo in_array($trans['transaction_type'], ['distributed']) ? '-' : '+'; ?>
                            <?php echo number_format($trans['quantity']); ?>
                        </td>
                        <td style="padding: 8px; border: 1px solid #ddd; text-align: right;">₱<?php echo number_format($trans['unit_cost'], 2); ?></td>
                        <td style="padding: 8px; border: 1px solid #ddd; text-align: right; font-weight: 600;">₱<?php echo number_format($trans['total_cost'], 2); ?></td>
                        <td style="padding: 8px; border: 1px solid #ddd;">
                            <?php 
                            if ($trans['supplier_name']) {
                                echo htmlspecialchars($trans['supplier_name']);
                            } elseif ($trans['recipient_name']) {
                                echo htmlspecialchars($trans['recipient_name']);
                            } else {
                                echo '-';
                            }
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                
                <?php if (empty($trans_data)): ?>
                    <tr>
                        <td colspan="9" style="padding: 30px; text-align: center; color: var(--gray-500); border: 1px solid #ddd;">
                            No transactions found for the selected period
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function exportToCSV() {
    const table = document.getElementById('transactionsTable');
    let csv = [];
    
    csv.push('"PASSI CITY - DRRMO"');
    csv.push('"TRANSACTION REPORT"');
    csv.push('"Period: <?php echo date('M d, Y', strtotime($date_from)); ?> to <?php echo date('M d, Y', strtotime($date_to)); ?>"');
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
    a.download = 'transactions_report_<?php echo date('Y-m-d'); ?>.csv';
    a.click();
}
</script>

<?php require_once 'footer.php'; ?>