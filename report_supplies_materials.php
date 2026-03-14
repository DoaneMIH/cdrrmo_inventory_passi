<?php
require_once 'includes/config.php';
check_login();

$page_title = 'Report of Supplies and Materials Issued';

// Get filter parameters
$month = isset($_GET['month']) ? $_GET['month'] : date('m');
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');
$fund_code = isset($_GET['fund']) ? sanitize_input($_GET['fund']) : '1192';
$control_no = isset($_GET['control_no']) ? sanitize_input($_GET['control_no']) : '';
$transaction_filter = isset($_GET['type']) ? sanitize_input($_GET['type']) : 'distributed'; // NEW: default to distributed

// Calculate date range
$date_from = "$year-$month-01";
$date_to = date('Y-m-t', strtotime($date_from));

// Get items based on filter (distributed or received)
$query = "
    SELECT 
        t.transaction_code,
        i.item_code,
        i.item_description,
        i.unit,
        t.quantity,
        t.unit_cost,
        t.total_cost,
        t.transaction_date,
        s.supplier_name,
        t.recipient_name
    FROM transactions t
    JOIN inventory_items i ON t.item_id = i.id
    LEFT JOIN suppliers s ON t.supplier_id = s.id
    WHERE t.transaction_type = ?
    AND t.transaction_date BETWEEN ? AND ?
    ORDER BY t.transaction_date, i.item_code
";

$stmt = $conn->prepare($query);
$stmt->bind_param("sss", $transaction_filter, $date_from, $date_to);
$stmt->execute();
$result = $stmt->get_result();
$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}
$stmt->close();

// Format period display  
$period_display = date('F j', strtotime($date_from)) . '-' . date('j, Y', strtotime($date_to));

// Update title based on filter
$report_type_title = $transaction_filter === 'received' ? 'RECEIVED' : 'ISSUED';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="print-controls no-print">
        <button class="btn-print" onclick="window.print()"><i class="fas fa-print"></i> Print Report</button>
        <button class="btn-print btn-secondary" onclick="window.location.href='reports.php'"><i class="fas fa-times"></i> Close</button>
    </div>

    <div class="report-container">
        <!-- Filter Panel -->
        <div class="filter-panel no-print">
            <h3 class="txn-card-title">
                <i class="fas fa-filter"></i> Report Filters
            </h3>
            <form method="GET" action="">
                <div class="filter-row">
                    <div class="filter-group">
                        <label class="filter-label">Report Type *</label>
                        <select name="type" class="filter-control">
                            <option value="distributed" <?php echo $transaction_filter === 'distributed' ? 'selected' : ''; ?>>Items Issued (Distributed)</option>
                            <option value="received" <?php echo $transaction_filter === 'received' ? 'selected' : ''; ?>>Items Received</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label class="filter-label">Month</label>
                        <select name="month" class="filter-control">
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?php echo str_pad($m, 2, '0', STR_PAD_LEFT); ?>" 
                                    <?php echo $m == $month ? 'selected' : ''; ?>>
                                    <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label class="filter-label">Year</label>
                        <select name="year" class="filter-control">
                            <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                                <option value="<?php echo $y; ?>" <?php echo $y == $year ? 'selected' : ''; ?>>
                                    <?php echo $y; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label class="filter-label">Fund Code</label>
                        <input type="text" name="fund" class="filter-control" value="<?php echo htmlspecialchars($fund_code); ?>" placeholder="1192">
                    </div>
                    
                    <div class="filter-group">
                        <label class="filter-label">Control No.</label>
                        <input type="text" name="control_no" class="filter-control" value="<?php echo htmlspecialchars($control_no); ?>" placeholder="Optional">
                    </div>
                </div>
                
                <div class="filter-buttons">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-sync"></i> Generate Report
                    </button>
                    <a href="report_supplies_materials.php" class="btn btn-secondary">
                        <i class="fas fa-redo"></i>&nbsp; Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Logo Header -->
        <div class="logo-header">
            <div class="header-row">
                <img src="images/logo.jpg" class="logo-left" alt="Logo">
                <div class="header-text">
                    <div class="header-title">Republic of the Philippines</div>
                    <div class="header-subtitle">Province of Iloilo</div>
                    <div class="header-title">CITY OF PASSI</div>
                </div>
                <img src="images/logo1.png" class="logo-right" alt="Logo">
            </div>
        </div>

        <div class="report-header">
            <div class="report-title">REPORT OF SUPPLIES AND MATERIALS <?php echo $report_type_title; ?></div>
            <div class="report-period">For the Period of <?php echo $period_display; ?></div>
        </div>
        
        <div class="report-info">
            <div class="info-left">
                <div><span class="info-label">LGU - PASSI CITY, CDRRMO</span></div>
                <div><span class="info-label">Fund:</span> <?php echo htmlspecialchars($fund_code); ?></div>
            </div>
            <div class="info-right">
                <div><span class="info-label">Control No.:</span> <?php echo htmlspecialchars($control_no); ?></div>
                <div><span class="info-label">Date:</span> <?php echo date('F d, Y'); ?></div>
            </div>
        </div>
        
        <table class="report-table">
            <thead>
                <tr>
                    <th colspan="8" class="section-header">To be filled up by the Supply and/or Property Division/Unit</th>
                    <th colspan="1" class="section-header">To be filled up by the Accounting Division/Unit</th>
                </tr>
                <tr>
                    <th class="col-ris"><?php echo $transaction_filter === 'received' ? 'DR/PO No.' : 'RIS No.'; ?></th>
                    <th class="col-center">Responsibi<br>lity Center<br>Code</th>
                    <th class="col-stock">Stock No</th>
                    <th class="col-item">Item</th>
                    <th class="col-source"><?php echo $transaction_filter === 'received' ? 'Supplier' : 'Recipient'; ?></th>
                    <th class="col-unit">Unit</th>
                    <th class="col-qty">Quantity<br><?php echo $transaction_filter === 'received' ? 'Received' : 'Issued'; ?></th>
                    <th class="col-cost">Unit Cost</th>
                    <th class="col-amount">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($items) > 0): ?>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td class="col-ris"><?php echo htmlspecialchars($item['transaction_code']); ?></td>
                            <td class="col-center"><?php echo htmlspecialchars($fund_code); ?></td>
                            <td class="col-stock"></td>
                            <td class="col-item"><?php echo htmlspecialchars($item['item_description']); ?></td>
                            <td class="col-source">
                                <?php 
                                if ($transaction_filter === 'received') {
                                    echo htmlspecialchars($item['supplier_name'] ?? '-');
                                } else {
                                    echo htmlspecialchars($item['recipient_name'] ?? '-');
                                }
                                ?>
                            </td>
                            <td class="col-unit"><?php echo htmlspecialchars($item['unit']); ?></td>
                            <td class="col-qty"><?php echo number_format($item['quantity']); ?></td>
                            <td class="col-cost"><?php echo $item['unit_cost'] > 0 ? number_format($item['unit_cost'], 2) : ''; ?></td>
                            <td class="col-amount"><?php echo $item['total_cost'] > 0 ? number_format($item['total_cost'], 2) : ''; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php for ($i = count($items); $i < 15; $i++): ?>
                        <tr><td>&nbsp;</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
                    <?php endfor; ?>
                <?php else: ?>
                    <?php for ($i = 0; $i < 15; $i++): ?>
                        <tr><td>&nbsp;</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
                    <?php endfor; ?>
                <?php endif; ?>
            </tbody>
        </table>
        
        <div class="recap-section">
            <div class="recap-left">
                <div class="recap-title">Recapitulation:</div>
                <table class="recap-table">
                    <thead><tr><th>Stock No.</th><th>Quantity</th></tr></thead>
                    <tbody>
                        <?php if (count($items) > 0): ?>
                            <?php 
                            $recap = [];
                            foreach ($items as $item) {
                                $code = $item['item_code'];
                                if (!isset($recap[$code])) $recap[$code] = 0;
                                $recap[$code] += $item['quantity'];
                            }
                            foreach ($recap as $code => $qty): 
                            ?>
                                <tr><td></td><td><?php echo number_format($qty); ?></td></tr>
                            <?php endforeach; ?>
                            <?php for ($i = count($recap); $i < 10; $i++): ?>
                                <tr><td>&nbsp;</td><td></td></tr>
                            <?php endfor; ?>
                        <?php else: ?>
                            <?php for ($i = 0; $i < 10; $i++): ?>
                                <tr><td>&nbsp;</td><td></td></tr>
                            <?php endfor; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="recap-right">
                <div class="recap-title">Recapitulation:</div>
                <table class="recap-table">
                    <thead><tr><th>Unit Cost</th><th>Total Cost</th><th>Account Code</th></tr></thead>
                    <tbody>
                        <?php for ($i = 0; $i < 10; $i++): ?>
                            <tr><td class="text-right">&nbsp;</td><td class="text-right"></td><td></td></tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="certification">I hereby certify to the correctness of the above information.</div>
        
        <div class="signature-section">
            <div class="signature-box">
                <div class="signature-name">JOFFEL RAYMUND P. BOSQUE</div>
                <div class="signature-title">Signature over Printed Name of Supply and/or Property Custodian</div>
            </div>
            <div class="signature-box">
                <div class="signature-label">Posted by:</div>
                <div class="signature-name">&nbsp;</div>
                <div class="signature-title">Signature over Printed Name of Designated<br>Accounting Staff</div>
                <div class="signature-date-box">
                    <div class="signature-date"><div class="signature-date-label">Date</div></div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>