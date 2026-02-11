<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Get filters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$transaction_type = isset($_GET['transaction_type']) ? $_GET['transaction_type'] : 'all';

// Build query
$sql = "SELECT t.*, i.item_description, c.category_name, u.full_name 
        FROM transactions t
        JOIN inventory_items i ON t.item_id = i.id
        JOIN categories c ON i.category_id = c.id
        LEFT JOIN users u ON t.created_by = u.id
        WHERE t.transaction_date BETWEEN '$start_date' AND '$end_date'";

if ($transaction_type != 'all') {
    $sql .= " AND t.transaction_type = '$transaction_type'";
}

$sql .= " ORDER BY t.transaction_date DESC, t.created_at DESC";

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <!-- <meta http-equiv="refresh" content="2"> Refreshes every 02 seconds -->
    <!-- <meta name="viewport" content="width=device-width, initial-scale=1.0"> -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <title>Transactions - CDRRMO Inventory System</title>
    <link rel="stylesheet" href="style.css">

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
    <?php include 'header_sidebar.php'; ?>
    <div class="main-content">
    <div class="container">
        <div class="page-header">
        <h1>Transaction History</h1>
        </div>
        
        <!-- Filters -->
        <div class="filter-section">
            <form method="GET" action="" class="filter-form">
                <input type="date" name="start_date" value="<?php echo $start_date; ?>" required>
                <span>to</span>
                <input type="date" name="end_date" value="<?php echo $end_date; ?>" required>
                
                <select name="transaction_type">
                    <option value="all" <?php echo $transaction_type == 'all' ? 'selected' : ''; ?>>All Types</option>
                    <option value="received" <?php echo $transaction_type == 'received' ? 'selected' : ''; ?>>Received</option>
                    <option value="distributed" <?php echo $transaction_type == 'distributed' ? 'selected' : ''; ?>>Distributed</option>
                    <option value="adjustment" <?php echo $transaction_type == 'adjustment' ? 'selected' : ''; ?>>Adjustment</option>
                </select>
                
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="transactions.php" class="btn btn-light">Reset</a>
            </form>
        </div>
        
        <!-- Transactions Table -->
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Category</th>
                        <th>Item</th>
                        <th>Quantity</th>
                        <th>Notes</th>
                        <th>User</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($row['transaction_date'])); ?></td>
                                <td>
                                    <?php 
                                    $type_colors = [
                                        'received' => 'success',
                                        'distributed' => 'warning',
                                        'adjustment' => 'info'
                                    ];
                                    $badge_color = $type_colors[$row['transaction_type']] ?? 'secondary';
                                    ?>
                                    <span class="badge badge-<?php echo $badge_color; ?>">
                                        <?php echo ucfirst($row['transaction_type']); ?>
                                    </span>
                                </td>
                                <td><span class="badge"><?php echo $row['category_name']; ?></span></td>
                                <td><?php echo substr($row['item_description'], 0, 60); ?></td>
                                <td>
                                    <strong style="color: <?php echo $row['transaction_type'] == 'received' ? '#2ecc71' : '#e74c3c'; ?>">
                                        <?php echo ($row['transaction_type'] == 'received' ? '+' : '-') . $row['quantity']; ?>
                                    </strong>
                                </td>
                                <td><?php echo $row['notes'] ?? '-'; ?></td>
                                <td><?php echo $row['full_name'] ?? 'System'; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center;">No transactions found for the selected period</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    </div>

    <script>
function updateTransactions() {
    const currentParams = new URLSearchParams(window.location.search);
    
    fetch('transactions.php?' + currentParams.toString() + '&ajax=1')
        .then(response => response.text())
        .then(html => {
            const temp = document.createElement('div');
            temp.innerHTML = html;
            
            const newTbody = temp.querySelector('.table-responsive tbody');
            if (newTbody) {
                document.querySelector('.table-responsive tbody').innerHTML = newTbody.innerHTML;
            }
        })
        .catch(error => console.error('Update failed:', error));
}

setInterval(updateTransactions, 2000);
</script>
</body>
</html>