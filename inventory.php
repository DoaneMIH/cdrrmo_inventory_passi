<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Get selected category
$selected_category = isset($_GET['category']) ? $_GET['category'] : 'all';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build query
$sql = "SELECT i.*, c.category_name 
        FROM inventory_items i 
        JOIN categories c ON i.category_id = c.id 
        WHERE 1=1";

if ($selected_category != 'all') {
    $sql .= " AND c.category_name = '" . $conn->real_escape_string($selected_category) . "'";
}

if ($search) {
    $sql .= " AND i.item_description LIKE '%" . $conn->real_escape_string($search) . "%'";
}

$sql .= " ORDER BY c.category_name, i.item_description";

$result = $conn->query($sql);

// Get categories for filter
$cat_sql = "SELECT * FROM categories ORDER BY category_name";
$categories = $conn->query($cat_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <!-- <meta http-equiv="refresh" content="2"> Refreshes every 02 seconds -->
    <!-- <meta name="viewport" content="width=device-width, initial-scale=1.0"> -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <title>Inventory - CDRRMO Inventory System</title>
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
            <h1>Inventory Management</h1>
            <a href="add_item.php" class="btn btn-primary">+ Add New Item</a>
        </div>
        
        <!-- Filters -->
        <div class="filter-section">
            <form method="GET" action="" class="filter-form">
                <select name="category" onchange="this.form.submit()">
                    <option value="all" <?php echo $selected_category == 'all' ? 'selected' : ''; ?>>All Categories</option>
                    <?php while ($cat = $categories->fetch_assoc()): ?>
                        <option value="<?php echo $cat['category_name']; ?>" <?php echo $selected_category == $cat['category_name'] ? 'selected' : ''; ?>>
                            <?php echo $cat['category_name']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                
                <input type="text" name="search" placeholder="Search items..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-secondary">Search</button>
                <?php if ($search || $selected_category != 'all'): ?>
                    <a href="inventory.php" class="btn btn-light">Clear</a>
                <?php endif; ?>
            </form>
        </div>
        
        <!-- Inventory Table -->
        <div class="table-responsive">
            <table class="inventory-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Category</th>
                        <th>Item Description</th>
                        <th>Unit</th>
                        <th>Received</th>
                        <th>Distributed</th>
                        <th>On Hand</th>
                        <th>Expiry Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><span class="badge badge-<?php echo strtolower($row['category_name']); ?>"><?php echo $row['category_name']; ?></span></td>
                                <td><?php echo $row['item_description']; ?></td>
                                <td><?php echo $row['unit'] ?? '-'; ?></td>
                                <td><?php echo $row['items_received']; ?></td>
                                <td><?php echo $row['items_distributed']; ?></td>
                                <td>
                                    <strong style="color: <?php echo $row['items_on_hand'] < 5 ? '#e74c3c' : '#2ecc71'; ?>">
                                        <?php echo $row['items_on_hand']; ?>
                                    </strong>
                                </td>
                                <td>
                                    <?php 
                                    if ($row['expiration_date']) {
                                        $days_left = floor((strtotime($row['expiration_date']) - time()) / (60 * 60 * 24));
                                        $color = $days_left < 7 ? '#e74c3c' : ($days_left < 30 ? '#f39c12' : '#2ecc71');
                                        echo '<span style="color: ' . $color . '">' . date('M d, Y', strtotime($row['expiration_date'])) . '</span>';
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td class="actions">
                                    <a href="edit_item.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info">Edit</a>
                                    <a href="add_transaction.php?item_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-success">Transaction</a>
                                    <?php if ($_SESSION['role'] == 'admin'): ?>
                                        <a href="delete_item.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this item?')">Delete</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" style="text-align: center;">No items found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    </div>

    <script>
// Smooth inventory update
function updateInventory() {
    // Get current URL parameters to maintain filters
    const currentParams = new URLSearchParams(window.location.search);
    
    fetch('inventory.php?' + currentParams.toString() + '&ajax=1')
        .then(response => response.text())
        .then(html => {
            const temp = document.createElement('div');
            temp.innerHTML = html;
            
            // Update only the table body
            const newTbody = temp.querySelector('.inventory-table tbody');
            if (newTbody) {
                document.querySelector('.inventory-table tbody').innerHTML = newTbody.innerHTML;
            }
        })
        .catch(error => console.error('Update failed:', error));
}

// Update every 2 seconds
setInterval(updateInventory, 2000);
</script>
</body>
</html>