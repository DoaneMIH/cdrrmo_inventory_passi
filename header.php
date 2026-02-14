<?php
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>CDRRMO Inventory System</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <div class="main-wrapper">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <!-- <div class="sidebar-header">
                <div class="sidebar-brand">
                    <img src="images/logo.jpg" alt="CDRRMO Logo" class="sidebar-logo">
                    <div class="sidebar-brand-text">
                        <h2>PASSI CITY CDRRMO</h2>
                        <p>Inventory Management System</p>
                    </div>
                </div>
            </div> -->
            <div class="sidebar-header">
                <img src="images/logo.jpg" alt="CDRRMO Logo" class="sidebar-logo">
                <div class="sidebar-title">PASSI CITY CDRRMO</div>
                <div class="sidebar-subtitle">Inventory Management System</div>
            </div>

            <nav class="sidebar-menu">
                <div class="menu-section">Main Menu</div>
                <a href="dashboard.php"
                    class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i> Dashboard
                </a>

                <div class="menu-section">Inventory</div>
                <a href="inventory.php"
                    class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'inventory.php' ? 'active' : ''; ?>">
                    <i class="fas fa-boxes"></i> All Items
                </a>
                <a href="categories.php"
                    class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tags"></i> Categories
                </a>
                <a href="low_stock.php"
                    class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'low_stock.php' ? 'active' : ''; ?>">
                    <i class="fas fa-exclamation-triangle"></i> Low Stock
                </a>

                <div class="menu-section">Transactions</div>
                <a href="receive_items.php"
                    class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'receive_items.php' ? 'active' : ''; ?>">
                    <i class="fas fa-plus-circle"></i> Receive Items
                </a>
                <a href="distribute_items.php"
                    class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'distribute_items.php' ? 'active' : ''; ?>">
                    <i class="fas fa-minus-circle"></i> Distribute Items
                </a>
                <a href="transactions.php"
                    class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'transactions.php' ? 'active' : ''; ?>">
                    <i class="fas fa-exchange-alt"></i> All Transactions
                </a>

                <div class="menu-section">Management</div>
                <a href="suppliers.php"
                    class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'suppliers.php' ? 'active' : ''; ?>">
                    <i class="fas fa-truck"></i> Suppliers
                </a>
                <a href="locations.php"
                    class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'locations.php' ? 'active' : ''; ?>">
                    <i class="fas fa-warehouse"></i> Storage Locations
                </a>

                <div class="menu-section">Reports</div>
                <a href="reports.php"
                    class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-bar"></i> Reports
                </a>

                <?php if ($_SESSION['user_role'] === 'admin'): ?>
                <div class="menu-section">Administration</div>
                <a href="users.php"
                    class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i> User Management
                </a>
                <a href="activity_logs.php"
                    class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'activity_logs.php' ? 'active' : ''; ?>">
                    <i class="fas fa-history"></i> Activity Logs
                </a>
                <?php endif; ?>

                <div class="menu-section">Account</div>
                <a href="change_password.php"
                    class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'change_password.php' ? 'active' : ''; ?>">
                    <i class="fas fa-key"></i> Change Password
                </a>
                <a href="logout.php" class="menu-item">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Navigation -->
            <nav class="topnav">
                <div class="topnav-left">
                    <h1><?php echo isset($page_title) ? $page_title : 'Dashboard'; ?></h1>
                </div>
                <div class="topnav-right">
                    <div class="user-info">
                        <div class="user-avatar">
                            <?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?>
                        </div>
                        <div class="user-details">
                            <div class="user-name"><?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
                            <div class="user-role"><?php echo ucfirst($_SESSION['user_role']); ?></div>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Page Content -->
            <div class="content"></div>