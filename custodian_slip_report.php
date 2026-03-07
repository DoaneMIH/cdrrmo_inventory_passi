<?php
require_once 'includes/config.php';
check_login();

$page_title = 'Inventory Custodian Slip';

// // Get filter parameters
// $month = isset($_GET['month']) ? $_GET['month'] : date('m');
// $year = isset($_GET['year']) ? $_GET['year'] : date('Y');
// $recipient_filter = isset($_GET['recipient']) ? sanitize_input($_GET['recipient']) : '';

// // Calculate date range
// $date_from = "$year-$month-01";
// $date_to = date('Y-m-t', strtotime($date_from));
// Get filter parameters
$month = isset($_GET['month']) ? $_GET['month'] : date('m');
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');
$day = isset($_GET['day']) ? $_GET['day'] : ''; // '' means all days
$recipient_filter = isset($_GET['recipient']) ? sanitize_input($_GET['recipient']) : '';

// Calculate date range
if ($day) {
    $date_from = "$year-$month-" . str_pad($day, 2, '0', STR_PAD_LEFT);
    $date_to = $date_from;
} else {
    $date_from = "$year-$month-01";
    $date_to = date('Y-m-t', strtotime($date_from));
}

// Build query - only borrowed items (distributed with is_borrowed = 1)
$where = ["t.transaction_date BETWEEN ? AND ?", "t.transaction_type = 'distributed'", "t.is_borrowed = 1"];
$params = [$date_from, $date_to];
$types = "ss";

if ($recipient_filter) {
    $where[] = "t.recipient_name LIKE ?";
    $params[] = "%$recipient_filter%";
    $types .= "s";
}

$where_clause = implode(' AND ', $where);

// Get borrowed transactions
$query = "
    SELECT 
        t.id,
        t.transaction_code,
        t.transaction_date,
        t.quantity,
        t.recipient_name,
        t.recipient_organization,
        i.item_code,
        i.item_description,
        i.unit,
        COALESCE(
            (SELECT SUM(quantity) 
             FROM transactions 
             WHERE parent_transaction_id = t.id 
             AND transaction_type = 'returned'),
            0
        ) as returned_quantity,
        COALESCE(
            (SELECT MAX(transaction_date)
             FROM transactions 
             WHERE parent_transaction_id = t.id 
             AND transaction_type = 'returned'),
            NULL
        ) as return_date
    FROM transactions t
    JOIN inventory_items i ON t.item_id = i.id
    WHERE $where_clause
    ORDER BY t.recipient_name, t.transaction_date
";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$transactions = [];
while ($row = $result->fetch_assoc()) {
    $transactions[] = $row;
}
$stmt->close();

// Get unique recipients for filter
$recipients_query = $conn->query("
    SELECT DISTINCT recipient_name 
    FROM transactions 
    WHERE recipient_name IS NOT NULL AND recipient_name != '' AND is_borrowed = 1
    ORDER BY recipient_name
");

// $period_display = date('F j', strtotime($date_from)) . '-' . date('j, Y', strtotime($date_to));
$period_display = $day 
    ? date('F j, Y', strtotime($date_from)) 
    : date('F j', strtotime($date_from)) . '-' . date('j, Y', strtotime($date_to));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Custodian Slip</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @page { size: A4 portrait; margin: 0.5in; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 11pt; line-height: 1.3; color: #000; background: #fff; }
        
        .slip-container { width: 100%; max-width: 7.5in; margin: 0 auto; padding: 20px 0; }
        
        /* Filter Panel */
        .filter-panel { background: #f5f5f5; padding: 20px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #ddd; }
        .filter-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 15px; }
        .filter-group { display: flex; flex-direction: column; }
        .filter-label { font-size: 11px; font-weight: 600; margin-bottom: 5px; color: #333; }
        .filter-control { padding: 8px 12px; border: 1px solid #ccc; border-radius: 4px; font-size: 11px; }
        .filter-buttons { display: flex; gap: 10px; }
        .btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 12px; font-weight: 600; }
        .btn-primary { background: #1e3a8a; color: white; }
        .btn-primary:hover { background: #1e40af; }
        .btn-secondary { background: #6b7280; color: white; }
        
        /* Header with Logos */
        .slip-header { text-align: center; margin-bottom: 0; position: relative; }
        .header-row { display: flex; align-items: center; justify-content: center; margin-bottom: 15px; gap: 10px; }
.logo-left, .logo-right { 
    width: 70px; 
    height: 70px; 
    display: flex; 
    align-items: center; 
    justify-content: center;
    border-radius: 50%;
    color: #999;
    font-size: 10px;
    text-align: center;
}
.header-text { padding: 0 10px; text-align: center; }
        .header-title { font-size: 14pt; font-weight: bold; margin-bottom: 3px; }
        .header-subtitle { font-size: 12pt; margin-bottom: 2px; }
        
        /* Box around slip */
        .slip-box { border: 2px solid #000; }
        .slip-title { 
            font-size: 14pt; 
            font-weight: bold; 
            padding: 10px 0; 
            text-align: center;
            border-bottom: 2px solid #000;
        }
        
        /* Main Table */
        .main-table { width: 100%; border-collapse: collapse; margin-bottom: 0; }
        .main-table th, .main-table td { border: 1px solid #000; padding: 8px; vertical-align: top; }
        .main-table th { background: #fff; font-weight: bold; text-align: center; font-size: 10pt; }
        .main-table td { font-size: 10pt; }
        
        /* Column widths to match image */
        .col-qty { width: 10%; text-align: center; }
        .col-unit { width: 10%; text-align: center; }
        .col-desc { width: 50%; }
        .col-date { width: 15%; text-align: center; }
        
        .item-row { height: 80px; }
        .item-row td { vertical-align: top; padding-top: 8px; }
        
        /* Signature Section */
        .signature-section { display: flex; border-top: 0; }
        .sig-left, .sig-right { flex: 1; padding: 20px; }
        .sig-left { border-right: 1px solid #000; }
        
        .sig-box { margin-bottom: 20px; }
        .sig-label { font-size: 9pt; margin-bottom: 5px; }
        .sig-line { border-bottom: 1px solid #000; padding: 35px 5px 3px; margin-bottom: 3px; text-align: center; }
        .sig-name { font-weight: bold; text-transform: uppercase; }
        .sig-sublabel { font-size: 9pt; text-align: center; text-transform: uppercase; }
        
        .received-from-label { font-weight: bold; font-size: 11pt; margin-bottom: 15px; text-align: center; }
        
        /* Print Styles */
        @media print {
            body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
            .no-print { display: none !important; }
            .slip-container { padding: 0; }
            /* .filter-panel { display: none; } */
            /* .logo-left, .logo-right {
                border-style: solid;
                border-color: #000;
            } */
        }
        
        .print-controls { position: fixed; top: 10px; right: 10px; z-index: 1000; display: flex; gap: 10px; }
        .btn-print { background: #1e3a8a; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-size: 14px; box-shadow: 0 2px 5px rgba(0,0,0,0.2); }
        .btn-print:hover { background: #1e40af; }
        .btn-print.btn-secondary { background: #6b7280; }
        .btn-print.btn-secondary:hover { background: #4b5563; }
    </style>
</head>
<body>
    <div class="print-controls no-print">
        <button class="btn-print" onclick="window.print()">
            <i class="fas fa-print"></i> Print Slip
        </button>
        <button class="btn-print btn-secondary" onclick="window.location.href='reports.php'">
            <i class="fas fa-times"></i> Close
        </button>
    </div>

    <div class="slip-container">
        <!-- Filter Panel -->
        <div class="filter-panel no-print">
            <h3 style="margin-bottom: 15px; color: #1e3a8a; font-size: 14px;">
                <i class="fas fa-filter"></i> Filter Options
            </h3>
            <form method="GET" action="">
                <div class="filter-row">
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
    <label class="filter-label">Day</label>
    <select name="day" class="filter-control">
        <option value="">All Days</option>
        <?php 
        $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        for ($d = 1; $d <= $days_in_month; $d++): ?>
            <option value="<?php echo $d; ?>" <?php echo $day == $d ? 'selected' : ''; ?>>
                <?php echo $d; ?>
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
                        <label class="filter-label">Recipient</label>
                        <select name="recipient" class="filter-control">
                            <option value="">All Recipients</option>
                            <?php while ($rec = $recipients_query->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($rec['recipient_name']); ?>"
                                    <?php echo $recipient_filter === $rec['recipient_name'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($rec['recipient_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                
                <div class="filter-buttons">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Apply Filters
                    </button>
                    <a href="custodian_slip_report.php" class="btn btn-secondary" style="text-decoration: none; display: inline-flex; align-items: center;">
                        <i class="fas fa-redo"></i>&nbsp; Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Header with Logo Placeholders -->
        <div class="slip-header">
            <div class="header-row">
                <!-- <div class="logo-left"> -->
                    <img src="images/logo.jpg" class="logo-left" alt="">
                <!-- </div> -->
                <div class="header-text">
                    <div class="header-title">Republic of the Philippines</div>
                    <div class="header-subtitle">Province of Iloilo</div>
                    <div class="header-title">CITY OF PASSI</div>
                </div>
                <!-- <div class="logo-right"> -->
                    <img src="images/logo1.png" class="logo-right" alt="">
                <!-- </div> -->
            </div>
        </div>
        
        <!-- Slip Box -->
        <div class="slip-box">
            <div class="slip-title">INVENTORY CUSTODIAN SLIP</div>
            
            <!-- Main Table -->
            <table class="main-table">
                <thead>
                    <tr>
                        <th class="col-qty" rowspan="2">QUANTITY</th>
                        <th class="col-unit" rowspan="2">UNIT</th>
                        <th class="col-desc" rowspan="2">DESCRIPTION</th>
                        <th class="col-date">DATE<br>ISSUED</th>
                        <th class="col-date">DATE<br>RETURNED</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($transactions) > 0): ?>
                        <?php foreach ($transactions as $trans): ?>
                            <tr class="item-row">
                                <td class="col-qty"><?php echo number_format($trans['quantity']); ?></td>
                                <td class="col-unit"><?php echo strtoupper(htmlspecialchars($trans['unit'])); ?></td>
                                <td class="col-desc"><?php echo htmlspecialchars($trans['item_description']); ?></td>
                                <td class="col-date"><?php echo date('n/j/Y', strtotime($trans['transaction_date'])); ?></td>
                                <td class="col-date"><?php echo $trans['return_date'] ? date('n/j/Y', strtotime($trans['return_date'])) : ''; ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php 
                        // Fill remaining rows to reach 5 total
                        $remaining = 5 - count($transactions);
                        for ($i = 0; $i < $remaining; $i++): 
                        ?>
                            <tr class="item-row">
                                <td class="col-qty"></td>
                                <td class="col-unit"></td>
                                <td class="col-desc"></td>
                                <td class="col-date"></td>
                                <td class="col-date"></td>
                            </tr>
                        <?php endfor; ?>
                    <?php else: ?>
                        <?php for ($i = 0; $i < 5; $i++): ?>
                            <tr class="item-row">
                                <td class="col-qty"></td>
                                <td class="col-unit"></td>
                                <td class="col-desc"></td>
                                <td class="col-date"></td>
                                <td class="col-date"></td>
                            </tr>
                        <?php endfor; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <!-- Signature Section -->
            <div class="signature-section">
                <!-- Left: Borrower -->
                <div class="sig-left">
                    <div class="sig-box">
                        <div class="sig-line sig-name">
                            <?php 
                            // Get first recipient name if filtered
                            if (count($transactions) > 0) {
                                echo strtoupper(htmlspecialchars($transactions[0]['recipient_name']));
                            } else {
                                echo '&nbsp;';
                            }
                            ?>
                        </div>
                        <div class="sig-sublabel">Signature Over Printed Name</div>
                    </div>
                    
                    <div class="sig-box">
                        <div class="sig-line"></div>
                        <div class="sig-sublabel">Position / Office</div>
                    </div>
                    
                    <div class="sig-box">
                        <div class="sig-line"></div>
                        <div class="sig-sublabel">Date</div>
                    </div>
                </div>
                
                <!-- Right: Received From -->
                <div class="sig-right">
                    <div class="received-from-label">RECEIVED FROM:</div>
                    
                    <div class="sig-box">
                        <div class="sig-line"></div>
                        <div class="sig-sublabel"></div>
                    </div>
                    
                    <div class="sig-box">
                        <div class="sig-line"></div>
                        <div class="sig-sublabel">Date</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>