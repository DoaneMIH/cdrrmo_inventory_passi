<?php
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}
?>
<nav class="navbar">
    <div class="nav-container">
        <div class="nav-brand">
            <!-- Logo Image -->
            <div class="nav-brand-logo"></div>
            
            <!-- Text -->
            <div class="nav-brand-text">
                <h2>PASSI CITY CDRRMO</h2>
                <p>Inventory Management System</p>
            </div>
        </div>
        
        <ul class="nav-menu">
            <li><a href="dashboard.php" <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'class="active"' : ''; ?>>Dashboard</a></li>
            <li><a href="inventory.php" <?php echo basename($_SERVER['PHP_SELF']) == 'inventory.php' ? 'class="active"' : ''; ?>>Inventory</a></li>
            <li><a href="transactions.php" <?php echo basename($_SERVER['PHP_SELF']) == 'transactions.php' ? 'class="active"' : ''; ?>>Transactions</a></li>
            <li><a href="reports.php" <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'class="active"' : ''; ?>>Reports</a></li>
            <?php if ($_SESSION['role'] == 'admin'): ?>
                <li><a href="users.php" <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'class="active"' : ''; ?>>Users</a></li>
            <?php endif; ?>
        </ul>
        
        <div class="nav-user">
            <span>Welcome, <?php echo $_SESSION['full_name']; ?></span>
            <a href="logout.php" class="btn-logout">Logout</a>
        </div>
    </div>
</nav>