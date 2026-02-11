<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Get inventory summary
$stats = [];
$categories = ['OFFICE', 'MEDICAL', 'SRR', 'FOOD'];

foreach ($categories as $cat) {
    $sql = "SELECT 
                c.category_name,
                COUNT(i.id) as total_items,
                SUM(i.items_on_hand) as total_on_hand,
                SUM(i.items_received) as total_received,
                SUM(i.items_distributed) as total_distributed
            FROM categories c
            LEFT JOIN inventory_items i ON c.id = i.category_id
            WHERE c.category_name = '$cat'
            GROUP BY c.id";
    
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $stats[$cat] = $result->fetch_assoc();
    }
}

// Get low stock items (items with on_hand < 5)
$low_stock_sql = "SELECT i.*, c.category_name 
                  FROM inventory_items i 
                  JOIN categories c ON i.category_id = c.id 
                  WHERE i.items_on_hand < 5 AND i.items_on_hand > 0
                  ORDER BY i.items_on_hand ASC 
                  LIMIT 10";
$low_stock_result = $conn->query($low_stock_sql);

// Get items expiring soon (within 30 days)
$expiring_sql = "SELECT i.*, c.category_name 
                 FROM inventory_items i 
                 JOIN categories c ON i.category_id = c.id 
                 WHERE i.expiration_date IS NOT NULL 
                 AND i.expiration_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                 AND i.expiration_date >= CURDATE()
                 ORDER BY i.expiration_date ASC";
$expiring_result = $conn->query($expiring_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <!-- <meta name="viewport" content="width=device-width, initial-scale=1.0"> -->
    <!-- <meta http-equiv="refresh" content="2"> Refreshes every 02 seconds -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <title>Dashboard - CDRRMO Inventory System</title>
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
        <h1>Dashboard</h1>
        </div>
        
        <div class="stats-grid">
            <?php 
            $colors = ['#3498db', '#e74c3c', '#f39c12', '#2ecc71'];
            $icons = ['📄', '🏥', '🚨', '🍱'];
            $index = 0;
            foreach ($stats as $cat => $data): 
            ?>
                <div class="stat-card" style="border-left: 4px solid <?php echo $colors[$index]; ?>">
                    <div class="stat-icon"><?php echo $icons[$index]; ?></div>
                    <div class="stat-details">
                        <h3><?php echo $cat; ?></h3>
                        <p class="stat-number"><?php echo $data['total_items'] ?? 0; ?> Items</p>
                        <p class="stat-sub">On Hand: <?php echo $data['total_on_hand'] ?? 0; ?></p>
                    </div>
                </div>
            <?php 
                $index++;
            endforeach; 
            ?>
        </div>
        
        <div class="dashboard-grid">
            <!-- Low Stock Alert -->
            <div class="dashboard-card">
                <h2>⚠️ Low Stock Items</h2>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Item</th>
                                <th>On Hand</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($low_stock_result && $low_stock_result->num_rows > 0): ?>
                                <?php while ($row = $low_stock_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><span class="badge"><?php echo $row['category_name']; ?></span></td>
                                        <td><?php echo substr($row['item_description'], 0, 50); ?>...</td>
                                        <td><strong style="color: #e74c3c;"><?php echo $row['items_on_hand']; ?></strong></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" style="text-align: center; color: #2ecc71;">All items well stocked!</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Expiring Soon -->
            <div class="dashboard-card">
                <h2>📅 Expiring Soon (30 Days)</h2>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Expiry Date</th>
                                <th>Days Left</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($expiring_result && $expiring_result->num_rows > 0): ?>
                                <?php while ($row = $expiring_result->fetch_assoc()): 
                                    $days_left = floor((strtotime($row['expiration_date']) - time()) / (60 * 60 * 24));
                                ?>
                                    <tr>
                                        <td><?php echo substr($row['item_description'], 0, 40); ?>...</td>
                                        <td><?php echo date('M d, Y', strtotime($row['expiration_date'])); ?></td>
                                        <td><strong style="color: <?php echo $days_left < 7 ? '#e74c3c' : '#f39c12'; ?>"><?php echo $days_left; ?> days</strong></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" style="text-align: center; color: #2ecc71;">No items expiring soon!</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

    <script>

// Smooth auto-update without page reload AJAX
function updateDashboard() {
    // Only update the dynamic content, not the whole page
    fetch('dashboard.php?ajax=1')
        .then(response => response.text())
        .then(html => {
            // Create a temporary div to parse the response
            const temp = document.createElement('div');
            temp.innerHTML = html;
            
            // Update stats grid
            const newStats = temp.querySelector('.stats-grid');
            if (newStats) {
                document.querySelector('.stats-grid').innerHTML = newStats.innerHTML;
            }
            
            // Update low stock table
            const newLowStock = temp.querySelector('.dashboard-grid .dashboard-card:first-child tbody');
            if (newLowStock) {
                document.querySelector('.dashboard-grid .dashboard-card:first-child tbody').innerHTML = newLowStock.innerHTML;
            }
            
            // Update expiring items table
            const newExpiring = temp.querySelector('.dashboard-grid .dashboard-card:last-child tbody');
            if (newExpiring) {
                document.querySelector('.dashboard-grid .dashboard-card:last-child tbody').innerHTML = newExpiring.innerHTML;
            }
        })
        .catch(error => console.error('Update failed:', error));
}

// Update every 2 seconds
setInterval(updateDashboard, 2000);
</script>
</body>
</html>