<?php
require_once 'includes/config.php';
check_login();

// Get transaction ID
$transaction_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get transaction details with return information
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
    WHERE t.id = ? AND t.transaction_type = 'distributed'
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $transaction_id);
$stmt->execute();
$result = $stmt->get_result();
$transaction = $result->fetch_assoc();
$stmt->close();

if (!$transaction) {
    die("Transaction not found");
}
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
        <button class="btn-print btn-secondary" onclick="window.close()">
            <i class="fas fa-times"></i> Close
        </button>
    </div>

    <div class="slip-container">
        <!-- Header with Logo Placeholders -->
        <div class="slip-header">
            <div class="header-row">
                <img src="images/logo.jpg" class="logo-left" alt="">
                <div class="header-text">
                    <div class="header-title">Republic of the Philippines</div>
                    <div class="header-subtitle">Province of Iloilo</div>
                    <div class="header-title">CITY OF PASSI</div>
                </div>
                <img src="images/logo1.png" class="logo-right" alt="">
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
                    <!-- First row with actual data -->
                    <tr class="item-row">
                        <td class="col-qty"><?php echo number_format($transaction['quantity']); ?></td>
                        <td class="col-unit"><?php echo strtoupper(htmlspecialchars($transaction['unit'])); ?></td>
                        <td class="col-desc"><?php echo htmlspecialchars($transaction['item_description']); ?></td>
                        <td class="col-date"><?php echo date('n/j/Y', strtotime($transaction['transaction_date'])); ?></td>
                        <td class="col-date"><?php echo $transaction['return_date'] ? date('n/j/Y', strtotime($transaction['return_date'])) : ''; ?></td>
                    </tr>
                    <!-- Empty rows to fill up to 5 total -->
                    <?php for ($i = 1; $i < 5; $i++): ?>
                        <tr class="item-row">
                            <td class="col-qty"></td>
                            <td class="col-unit"></td>
                            <td class="col-desc"></td>
                            <td class="col-date"></td>
                            <td class="col-date"></td>
                        </tr>
                    <?php endfor; ?>
                </tbody>
            </table>
            
            <!-- Signature Section -->
            <div class="signature-section">
                <!-- Left: Borrower -->
                <div class="sig-left">
                    <div class="sig-box">
                        <div class="sig-line sig-name">
                            <?php echo strtoupper(htmlspecialchars($transaction['recipient_name'])); ?>
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