<?php
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: dashboard.php');
    exit();
}

// Get filters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-7 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$action_filter = isset($_GET['action']) ? $_GET['action'] : 'all';
$user_filter = isset($_GET['user']) ? $_GET['user'] : 'all';

// Build query
$sql = "SELECT a.*, u.full_name, u.username
        FROM audit_log a
        LEFT JOIN users u ON a.user_id = u.id
        WHERE DATE(a.created_at) BETWEEN '$start_date' AND '$end_date'";

if ($action_filter != 'all') {
    $sql .= " AND a.action = '" . $conn->real_escape_string($action_filter) . "'";
}

if ($user_filter != 'all') {
    $sql .= " AND a.user_id = " . intval($user_filter);
}

$sql .= " ORDER BY a.created_at DESC LIMIT 500";

$result = $conn->query($sql);

// Get all users for filter
$users_sql = "SELECT id, full_name FROM users ORDER BY full_name";
$users_result = $conn->query($users_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <title>Audit Log - CDRRMO Inventory System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'header_sidebar.php'; ?>
    
    <div class="main-content">
    <div class="container">
        <div class="page-header">
            <h1>System Audit Log</h1>
        </div>
        
        <!-- Filters -->
        <div class="filter-section">
            <form method="GET" action="" class="filter-form">
                <input type="date" name="start_date" value="<?php echo $start_date; ?>" required>
                <span>to</span>
                <input type="date" name="end_date" value="<?php echo $end_date; ?>" required>
                
                <select name="action">
                    <option value="all" <?php echo $action_filter == 'all' ? 'selected' : ''; ?>>All Actions</option>
                    <option value="create" <?php echo $action_filter == 'create' ? 'selected' : ''; ?>>Create</option>
                    <option value="update" <?php echo $action_filter == 'update' ? 'selected' : ''; ?>>Update</option>
                    <option value="delete" <?php echo $action_filter == 'delete' ? 'selected' : ''; ?>>Delete</option>
                    <option value="login" <?php echo $action_filter == 'login' ? 'selected' : ''; ?>>Login</option>
                    <option value="logout" <?php echo $action_filter == 'logout' ? 'selected' : ''; ?>>Logout</option>
                </select>
                
                <select name="user">
                    <option value="all" <?php echo $user_filter == 'all' ? 'selected' : ''; ?>>All Users</option>
                    <?php while ($u = $users_result->fetch_assoc()): ?>
                        <option value="<?php echo $u['id']; ?>" <?php echo $user_filter == $u['id'] ? 'selected' : ''; ?>>
                            <?php echo $u['full_name']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="audit.php" class="btn btn-light">Reset</a>
            </form>
        </div>
        
        <!-- Audit Log Table -->
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Table</th>
                        <th>Record ID</th>
                        <th>IP Address</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo date('M d, Y H:i:s', strtotime($row['created_at'])); ?></td>
                                <td>
                                    <strong><?php echo $row['full_name'] ?? 'System'; ?></strong>
                                    <br><small><?php echo $row['username'] ?? '-'; ?></small>
                                </td>
                                <td>
                                    <span class="badge badge-<?php 
                                        echo $row['action'] == 'create' ? 'success' : 
                                            ($row['action'] == 'delete' ? 'danger' : 
                                            ($row['action'] == 'login' ? 'info' : 'warning'));
                                    ?>">
                                        <?php echo strtoupper($row['action']); ?>
                                    </span>
                                </td>
                                <td><?php echo $row['table_name'] ?? '-'; ?></td>
                                <td><?php echo $row['record_id'] ?? '-'; ?></td>
                                <td><small><?php echo $row['ip_address'] ?? '-'; ?></small></td>
                                <td>
                                    <?php if ($row['old_values'] || $row['new_values']): ?>
                                        <button onclick="showDetails(<?php echo $row['id']; ?>)" class="btn btn-sm btn-info">View</button>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center;">No audit records found for the selected period</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 6px;">
            <p style="margin: 0; color: #6c757d; font-size: 13px;">
                <strong>Note:</strong> Showing last 500 records. Audit logs are automatically maintained by the system.
                <br>Total records displayed: <strong><?php echo $result ? $result->num_rows : 0; ?></strong>
            </p>
        </div>
    </div>
    </div>
    
    <script>
    function showDetails(id) {
        alert('Audit detail viewer - Feature coming soon!\nRecord ID: ' + id);
        // You can expand this to show a modal with old_values and new_values JSON
    }
    </script>
</body>
</html>