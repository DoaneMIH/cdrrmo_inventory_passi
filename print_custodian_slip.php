<?php
require_once 'includes/config.php';
check_login();

// Get transaction ID
$transaction_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get transaction details
$query = "
    SELECT 
        t.*,
        i.item_code,
        i.item_description,
        i.unit,
        u.full_name as distributed_by
    FROM transactions t
    JOIN inventory_items i ON t.item_id = i.id
    LEFT JOIN users u ON t.created_by = u.id
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

$ics_number = $transaction['transaction_code'];
$date_borrowed = date('n/j/Y', strtotime($transaction['transaction_date']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Custodian Slip - <?php echo $ics_number; ?></title>
    <style>
        @page { size: A4 portrait; margin: 0.5in; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 11pt; line-height: 1.3; color: #000; background: #fff; }
        
        .slip-container { width: 100%; max-width: 7.5in; margin: 0 auto; padding: 20px 0; }
        
        /* Header */
        .slip-header { text-align: center; margin-bottom: 15px; position: relative; }
        .header-logos { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px; }
        .logo-left, .logo-right { width: 80px; height: 80px; }
        .header-text { flex: 1; padding: 0 20px; }
        .header-title { font-size: 13pt; font-weight: bold; margin-bottom: 3px; }
        .header-subtitle { font-size: 11pt; margin-bottom: 2px; }
        .slip-title { font-size: 14pt; font-weight: bold; margin-top: 10px; border-top: 2px solid #000; border-bottom: 2px solid #000; padding: 8px 0; }
        
        /* Main Table */
        .main-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; border: 2px solid #000; }
        .main-table th, .main-table td { border: 1px solid #000; padding: 8px 10px; vertical-align: top; }
        .main-table th { background: #f5f5f5; font-weight: bold; text-align: center; font-size: 10pt; }
        .main-table td { font-size: 10pt; }
        
        .col-qty { width: 12%; text-align: center; }
        .col-unit { width: 10%; text-align: center; }
        .col-desc { width: 48%; }
        .col-ics { width: 15%; text-align: center; }
        .col-date { width: 15%; text-align: center; }
        
        .item-row { height: 80px; }
        .item-row td { vertical-align: top; padding-top: 12px; }
        
        /* Signature Section */
        .signature-section { display: flex; margin-top: 20px; border: 2px solid #000; }
        .sig-left, .sig-right { flex: 1; padding: 15px; }
        .sig-left { border-right: 1px solid #000; }
        
        .sig-box { margin-bottom: 15px; }
        .sig-label { font-size: 9pt; margin-bottom: 5px; }
        .sig-line { border-bottom: 1px solid #000; padding: 30px 5px 2px; margin-bottom: 3px; text-align: center; }
        .sig-name { font-weight: bold; text-transform: uppercase; }
        .sig-sublabel { font-size: 9pt; text-align: center; }
        
        .received-from-label { font-weight: bold; font-size: 10pt; margin-bottom: 10px; text-align: center; }
        
        /* Print Styles */
        @media print {
            body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
            .no-print { display: none !important; }
            .slip-container { padding: 0; }
        }
        
        .print-controls { position: fixed; top: 10px; right: 10px; z-index: 1000; display: flex; gap: 10px; }
        .btn-print { background: #1e3a8a; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-size: 14px; box-shadow: 0 2px 5px rgba(0,0,0,0.2); }
        .btn-print:hover { background: #1e40af; }
        .btn-secondary { background: #6b7280; }
        .btn-secondary:hover { background: #4b5563; }
    </style>
</head>
<body>
    <div class="print-controls no-print">
        <button class="btn-print" onclick="window.print()">
            <i class="fas fa-print"></i> Print Slip
        </button>
        <button class="btn-print btn-secondary" onclick="window.close()">
            Close
        </button>
    </div>

    <div class="slip-container">
        <!-- Header -->
        <div class="slip-header">
            <div class="header-logos">
                <div class="logo-left">
                    <!-- Left logo space -->
                </div>
                <div class="header-text">
                    <div class="header-title">Republic of the Philippines</div>
                    <div class="header-subtitle">Province of Iloilo</div>
                    <div class="header-title">CITY OF PASSI</div>
                </div>
                <div class="logo-right">
                    <!-- Right logo space -->
                </div>
            </div>
            <div class="slip-title">INVENTORY CUSTODIAN SLIP</div>
        </div>
        
        <!-- Main Table -->
        <table class="main-table">
            <thead>
                <tr>
                    <th class="col-qty">QUANTITY</th>
                    <th class="col-unit">UNIT</th>
                    <th class="col-desc">DESCRIPTION</th>
                    <th class="col-ics">ICS No.</th>
                    <th colspan="2" style="border-bottom: 0; padding-bottom: 2px;"></th>
                </tr>
                <tr>
                    <th colspan="3" style="border-top: 0;"></th>
                    <th style="border-top: 0;"></th>
                    <th class="col-date" style="border-top: 1px solid #000;">DATE<br>BORROWED</th>
                    <th class="col-date" style="border-top: 1px solid #000;">DATE<br>RETURNED</th>
                </tr>
            </thead>
            <tbody>
                <tr class="item-row">
                    <td class="col-qty"><?php echo number_format($transaction['quantity']); ?></td>
                    <td class="col-unit"><?php echo strtoupper(htmlspecialchars($transaction['unit'])); ?></td>
                    <td class="col-desc"><strong><?php echo htmlspecialchars($transaction['item_description']); ?></strong></td>
                    <td class="col-ics"><?php echo htmlspecialchars($ics_number); ?></td>
                    <td class="col-date"><?php echo $date_borrowed; ?></td>
                    <td class="col-date"></td>
                </tr>
                <!-- Add 4 more empty rows -->
                <?php for ($i = 0; $i < 4; $i++): ?>
                <tr class="item-row">
                    <td class="col-qty"></td>
                    <td class="col-unit"></td>
                    <td class="col-desc"></td>
                    <td class="col-ics"></td>
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
                    <div class="sig-line sig-name"><?php echo strtoupper(htmlspecialchars($transaction['recipient_name'])); ?></div>
                    <div class="sig-sublabel">SIGNATURE OVER PRINTED NAME</div>
                </div>
                
                <div class="sig-box">
                    <div class="sig-line"></div>
                    <div class="sig-sublabel">POSITION / OFFICE</div>
                </div>
                
                <div class="sig-box">
                    <div class="sig-line"></div>
                    <div class="sig-sublabel">DATE</div>
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
                    <div class="sig-sublabel">DATE</div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>