<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Get selected category for report
$selected_category = isset($_GET['category']) ? $_GET['category'] : 'OFFICE';
$report_date = isset($_GET['report_date']) ? $_GET['report_date'] : date('Y-m-d');

// Get category data
$cat_sql = "SELECT id FROM categories WHERE category_name = '" . $conn->real_escape_string($selected_category) . "'";
$cat_result = $conn->query($cat_sql);
$category_id = $cat_result->fetch_assoc()['id'];

// Get inventory items for selected category
$sql = "SELECT * FROM inventory_items WHERE category_id = $category_id ORDER BY item_description";
$result = $conn->query($sql);

// Get categories for dropdown
$categories_sql = "SELECT * FROM categories ORDER BY category_name";
$categories = $conn->query($categories_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <!-- <meta http-equiv="refresh" content="2"> Refreshes every 02 seconds -->
    <!-- <meta name="viewport" content="width=device-width, initial-scale=1.0"> -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <title>Reports - CDRRMO Inventory System</title>
    <link rel="stylesheet" href="style.css">
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background: white;
            }
            .container {
                max-width: 100%;
                padding: 0;
            }
            .report-header {
                text-align: center;
                margin-bottom: 30px;
            }
            table {
                width: 100%;
                border-collapse: collapse;
            }
            table th, table td {
                border: 1px solid #000;
                padding: 8px;
            }
        }
    </style>

    <!-- <script>
        // Auto-refresh every 02 seconds
        let timeLeft = 2;
        
        function updateTimer() {
            timeLeft--;
            document.getElementById('refresh-timer').innerHTML = 'Auto-refresh in: <strong>' + timeLeft + 's</strong>';
            
            if (timeLeft <= 0) {
                location.reload();
            }
        }
        
        // Update timer every second
        setInterval(updateTimer, 1000);
    </script> -->
</head>
<body>
    <div class="no-print">
        <?php include 'header.php'; ?>
    </div>
    
    <div class="container">
        <div class="page-header no-print">
            <h1>Inventory Reports</h1>
            <button onclick="window.print()" class="btn btn-primary">🖨️ Print Report</button>
        </div>
        
        <!-- Filter Section -->
        <div class="filter-section no-print">
            <form method="GET" action="" class="filter-form">
                <select name="category" onchange="this.form.submit()">
                    <?php while ($cat = $categories->fetch_assoc()): ?>
                        <option value="<?php echo $cat['category_name']; ?>" <?php echo $selected_category == $cat['category_name'] ? 'selected' : ''; ?>>
                            <?php echo $cat['category_name']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                
                <input type="date" name="report_date" value="<?php echo $report_date; ?>">
                <button type="submit" class="btn btn-secondary">Generate Report</button>
            </form>
        </div>
        
        <!-- Report Content -->
        <div class="report-container">
            <div class="report-header">
                <h2>PASSI CITY</h2>
                <h3>DISASTER RISK REDUCTION & MANAGEMENT OFFICE</h3>
                <h3>INVENTORY OF SUPPLIES (<?php echo $selected_category; ?>)</h3>
                <p><strong>DATE: <?php echo date('F d, Y', strtotime($report_date)); ?></strong></p>
            </div>
            
            <table class="report-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">No.</th>
                        <th>Item Description</th>
                        <th style="width: 120px;">No. of items received</th>
                        <th style="width: 120px;">No. of items distributed</th>
                        <th style="width: 120px;">No. of items on hand</th>
                        <?php if ($selected_category == 'FOOD'): ?>
                            <th style="width: 120px;">Expiration Date</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $counter = 1;
                    if ($result && $result->num_rows > 0): 
                        while ($row = $result->fetch_assoc()): 
                    ?>
                        <tr>
                            <td style="text-align: center;"><?php echo $counter++; ?></td>
                            <td><?php echo $row['item_description']; ?></td>
                            <td style="text-align: center;"><?php echo $row['items_received']; ?></td>
                            <td style="text-align: center;"><?php echo $row['items_distributed']; ?></td>
                            <td style="text-align: center;">
                                <?php echo $row['items_on_hand']; ?>
                                <?php if ($row['unit']): ?>
                                    <?php echo $row['unit']; ?>
                                <?php endif; ?>
                            </td>
                            <?php if ($selected_category == 'FOOD'): ?>
                                <td style="text-align: center;">
                                    <?php echo $row['expiration_date'] ? date('M d, Y', strtotime($row['expiration_date'])) : '-'; ?>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php 
                        endwhile;
                        // Add empty rows for printing
                        for ($i = $counter; $i <= 20; $i++): 
                    ?>
                        <tr>
                            <td style="text-align: center;"><?php echo $i; ?></td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <?php if ($selected_category == 'FOOD'): ?>
                                <td>&nbsp;</td>
                            <?php endif; ?>
                        </tr>
                    <?php 
                        endfor;
                    else: 
                    ?>
                        <tr>
                            <td colspan="<?php echo $selected_category == 'FOOD' ? '6' : '5'; ?>" style="text-align: center;">
                                No items in this category
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <div style="margin-top: 50px;">
                <div style="display: flex; justify-content: space-between;">
                    <div>
                        <p>Prepared by:</p>
                        <br><br>
                        <p>_________________________</p>
                        <p>CDRRMO Staff</p>
                    </div>
                    <div>
                        <p>Noted by:</p>
                        <br><br>
                        <p>_________________________</p>
                        <p>CDRRMO Head</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
function updateReport() {
    const currentParams = new URLSearchParams(window.location.search);
    
    fetch('reports.php?' + currentParams.toString() + '&ajax=1')
        .then(response => response.text())
        .then(html => {
            const temp = document.createElement('div');
            temp.innerHTML = html;
            
            const newTbody = temp.querySelector('.report-table tbody');
            if (newTbody) {
                document.querySelector('.report-table tbody').innerHTML = newTbody.innerHTML;
            }
        })
        .catch(error => console.error('Update failed:', error));
}

setInterval(updateReport, 2000); // 2 seconds for reports
</script>
</body>
</html>