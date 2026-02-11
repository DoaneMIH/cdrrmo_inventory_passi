<?php
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Get user initials for avatar
$initials = strtoupper(substr($_SESSION['full_name'], 0, 1));
?>

<!-- Sidebar Overlay (for mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<!-- Modern Sidebar Navigation -->
<aside class="sidebar" id="sidebar">
    <!-- Sidebar Header with Toggle -->
    <div class="sidebar-header">
        <div class="sidebar-brand">
            <div class="sidebar-logo" id="sidebarLogo" onclick="expandSidebar()"></div>
            <div class="sidebar-brand-text">
                <h2>PASSI CITY CDRRMO</h2>
                <p>Inventory Management System</p>
            </div>
        </div>
        <!-- <button class="sidebar-toggle" id="sidebarToggle" onclick="collapseSidebar()">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </button> -->

    </div>

    <!-- Navigation Section Label -->
    <div class="nav-section-label">OVERVIEW</div>

    <!-- Navigation Menu -->
    <ul class="sidebar-nav">
        <li>
            <a href="dashboard.php" title="Dashboard"
                class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                <!-- <span class="icon">📊</span> -->
                <svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                    width="24" height="24" fill="#1e5ba8" viewBox="0 0 24 24">
                    <path fill-rule="evenodd"
                        d="M4.857 3A1.857 1.857 0 0 0 3 4.857v4.286C3 10.169 3.831 11 4.857 11h4.286A1.857 1.857 0 0 0 11 9.143V4.857A1.857 1.857 0 0 0 9.143 3H4.857Zm10 0A1.857 1.857 0 0 0 13 4.857v4.286c0 1.026.831 1.857 1.857 1.857h4.286A1.857 1.857 0 0 0 21 9.143V4.857A1.857 1.857 0 0 0 19.143 3h-4.286Zm-10 10A1.857 1.857 0 0 0 3 14.857v4.286C3 20.169 3.831 21 4.857 21h4.286A1.857 1.857 0 0 0 11 19.143v-4.286A1.857 1.857 0 0 0 9.143 13H4.857Zm10 0A1.857 1.857 0 0 0 13 14.857v4.286c0 1.026.831 1.857 1.857 1.857h4.286A1.857 1.857 0 0 0 21 19.143v-4.286A1.857 1.857 0 0 0 19.143 13h-4.286Z"
                        clip-rule="evenodd" />
                </svg>

                <span>Dashboard</span>
            </a>
        </li>

        <li
            class="has-submenu <?php echo in_array(basename($_SERVER['PHP_SELF']), ['inventory.php', 'add_item.php', 'edit_item.php']) ? 'open' : ''; ?>">
            <a href="#" title="Inventory"
                class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['inventory.php', 'add_item.php', 'edit_item.php']) ? 'active' : ''; ?>">
                <!-- <span class="icon">📦</span> -->
                <svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                    width="24" height="24" fill="#1e5ba8" viewBox="0 0 24 24">
                    <path fill-rule="evenodd"
                        d="M20 10H4v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8ZM9 13v-1h6v1a1 1 0 0 1-1 1h-4a1 1 0 0 1-1-1Z"
                        clip-rule="evenodd" />
                    <path d="M2 6a2 2 0 0 1 2-2h16a2 2 0 1 1 0 4H4a2 2 0 0 1-2-2Z" />
                </svg>
                <span>Inventory</span>
            </a>
            <ul class="submenu">
                <li>
                    <a href="inventory.php" title="View All Items"
                        class="<?php echo basename($_SERVER['PHP_SELF']) == 'inventory.php' ? 'active' : ''; ?>">
                        <!-- <span class="icon">📋</span> -->
                        <svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true"
                            xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="#1e5ba8"
                            viewBox="0 0 24 24">
                            <path fill-rule="evenodd"
                                d="M8 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1h2a2 2 0 0 1 2 2v15a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h2Zm6 1h-4v2H9a1 1 0 0 0 0 2h6a1 1 0 1 0 0-2h-1V4Zm-6 8a1 1 0 0 1 1-1h6a1 1 0 1 1 0 2H9a1 1 0 0 1-1-1Zm1 3a1 1 0 1 0 0 2h6a1 1 0 1 0 0-2H9Z"
                                clip-rule="evenodd" />
                        </svg>

                        <span>View All Items</span>
                    </a>
                </li>
                <li>
                    <a href="add_item.php" title="Add New Item"
                        class="<?php echo basename($_SERVER['PHP_SELF']) == 'add_item.php' ? 'active' : ''; ?>">
                        <!-- <span class="icon">➕</span> -->
                        <svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true"
                            xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="#1e5ba8"
                            viewBox="0 0 24 24">
                            <path fill-rule="evenodd"
                                d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm11-4.243a1 1 0 1 0-2 0V11H7.757a1 1 0 1 0 0 2H11v3.243a1 1 0 1 0 2 0V13h3.243a1 1 0 1 0 0-2H13V7.757Z"
                                clip-rule="evenodd" />
                        </svg>

                        <span>Add New Item</span>
                    </a>
                </li>
            </ul>
            <!-- Tooltip for collapsed sidebar -->
            <ul class="submenu-tooltip">
                <li><a href="inventory.php"><span class="icon">📋</span> View All Items</a></li>
                <li><a href="add_item.php"><span class="icon">➕</span> Add New Item</a></li>
            </ul>
        </li>

        <li>
            <a href="transactions.php" title="Transactions"
                class="<?php echo basename($_SERVER['PHP_SELF']) == 'transactions.php' ? 'active' : ''; ?>">
                <!-- <span class="icon">📋</span> -->
                <svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                    width="24" height="24" fill="#1e5ba8" viewBox="0 0 24 24">
                    <path fill-rule="evenodd"
                        d="M8 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1h2a2 2 0 0 1 2 2v15a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h2Zm6 1h-4v2H9a1 1 0 0 0 0 2h6a1 1 0 1 0 0-2h-1V4Zm-3 8a1 1 0 0 1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm-2-1a1 1 0 1 0 0 2h.01a1 1 0 1 0 0-2H9Zm2 5a1 1 0 0 1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm-2-1a1 1 0 1 0 0 2h.01a1 1 0 1 0 0-2H9Z"
                        clip-rule="evenodd" />
                </svg>

                <span>Transactions</span>
            </a>
        </li>


        <!-- <li>
            <a href="suppliers.php" title="Suppliers"
                class="<?php echo basename($_SERVER['PHP_SELF']) == 'suppliers.php' ? 'active' : ''; ?>">
                <span class="icon">🏢</span>
                <span>Suppliers</span>
            </a>
        </li> -->

        <!-- <li>
            <a href="storage.php" title="Storage"
                class="<?php echo basename($_SERVER['PHP_SELF']) == 'storage.php' ? 'active' : ''; ?>">
                <span class="icon">🏭</span>
                <span>Storage</span>
            </a>
        </li> -->

        <!-- <li>
            <a href="alerts.php" title="Alerts" class="<?php echo basename($_SERVER['PHP_SELF']) == 'alerts.php' ? 'active' : ''; ?>">
                <span class="icon">🔔</span>
                <span>Alerts</span>
            </a>
        </li> -->

        <li>
            <a href="reports.php" title="Reports"
                class="<?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
                <!-- <span class="icon">📄</span> -->
                <svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                    width="24" height="24" fill="#1e5ba8" viewBox="0 0 24 24">
                    <path fill-rule="evenodd"
                        d="M9 2.221V7H4.221a2 2 0 0 1 .365-.5L8.5 2.586A2 2 0 0 1 9 2.22ZM11 2v5a2 2 0 0 1-2 2H4v11a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2h-7ZM8 16a1 1 0 0 1 1-1h6a1 1 0 1 1 0 2H9a1 1 0 0 1-1-1Zm1-5a1 1 0 1 0 0 2h6a1 1 0 1 0 0-2H9Z"
                        clip-rule="evenodd" />
                </svg>

                <span>Reports</span>
            </a>
        </li>
    </ul>

    <?php if ($_SESSION['role'] == 'admin'): ?>
    <!-- Admin Section Label -->
    <div class="nav-section-label">ADMIN TOOLS</div>

    <ul class="sidebar-nav">
        <li>
            <a href="users.php" title="Users"
                class="<?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">
                <!-- <span class="icon">👥</span> -->
                <svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                    width="24" height="24" fill="#1e5ba8" viewBox="0 0 24 24">
                    <path fill-rule="evenodd"
                        d="M8 4a4 4 0 1 0 0 8 4 4 0 0 0 0-8Zm-2 9a4 4 0 0 0-4 4v1a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2v-1a4 4 0 0 0-4-4H6Zm7.25-2.095c.478-.86.75-1.85.75-2.905a5.973 5.973 0 0 0-.75-2.906 4 4 0 1 1 0 5.811ZM15.466 20c.34-.588.535-1.271.535-2v-1a5.978 5.978 0 0 0-1.528-4H18a4 4 0 0 1 4 4v1a2 2 0 0 1-2 2h-4.535Z"
                        clip-rule="evenodd" />
                </svg>

                <span>Users</span>
            </a>
        </li>
        <!-- <li>
                <a href="settings.php" title="Settings" class="<?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
                    <span class="icon">⚙️</span>
                    <span>Settings</span>
                </a>
            </li> -->
        <li>
            <a href="audit.php" title="Audit Log"
                class="<?php echo basename($_SERVER['PHP_SELF']) == 'audit.php' ? 'active' : ''; ?>">
                <!-- <span class="icon">🔍</span> -->
                <svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                    width="24" height="24" fill="#1e5ba8" viewBox="0 0 24 24">
                    <path d="M10 2a8 8 0 1 0 0 16 8 8 0 0 0 0-16Z" />
                    <path fill-rule="evenodd"
                        d="M21.707 21.707a1 1 0 0 1-1.414 0l-3.5-3.5a1 1 0 0 1 1.414-1.414l3.5 3.5a1 1 0 0 1 0 1.414Z"
                        clip-rule="evenodd" />
                </svg>

                <span>Audit Log</span>
            </a>
        </li>
    </ul>
    <?php endif; ?>

    <!-- User Section at Bottom -->
    <div class="sidebar-user">
        <div class="sidebar-user-info">
            <div class="sidebar-user-avatar"><?php echo $initials; ?></div>
            <div class="sidebar-user-name">
                <strong><?php echo $_SESSION['full_name']; ?></strong>
                <small><?php echo ucfirst($_SESSION['role']); ?></small>
            </div>
        </div>
        <button onclick="window.location.href='logout.php'" class="sidebar-logout">
            <span class="icon">🚪</span>
            <span>Log out</span>
        </button>
    </div>
</aside>

<script>
// Function to expand sidebar (called when logo is clicked in collapsed state)
function expandSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');

    // Only expand if currently collapsed
    if (sidebar.classList.contains('collapsed')) {
        sidebar.classList.remove('collapsed');

        // Show overlay on mobile when sidebar is open
        if (window.innerWidth <= 768) {
            overlay.classList.add('active');
        }

        // Save state to localStorage
        localStorage.setItem('sidebarCollapsed', false);
    }
}

// Function to collapse sidebar (called when arrow button is clicked)
function collapseSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');

    sidebar.classList.add('collapsed');
    overlay.classList.remove('active');

    // Save state to localStorage
    localStorage.setItem('sidebarCollapsed', true);
}

// Legacy toggle function for overlay clicks
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');

    sidebar.classList.toggle('collapsed');

    // Show overlay on mobile when sidebar is open
    if (window.innerWidth <= 768) {
        overlay.classList.toggle('active');
    }

    // Save state to localStorage
    const isCollapsed = sidebar.classList.contains('collapsed');
    localStorage.setItem('sidebarCollapsed', isCollapsed);
}

// Restore sidebar state on page load
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');

    // On desktop, restore saved state. On mobile, start collapsed.
    if (window.innerWidth > 768) {
        const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        if (isCollapsed) {
            sidebar.classList.add('collapsed');
        }
    } else {
        // On mobile, always start collapsed
        sidebar.classList.add('collapsed');
    }

    // Initialize submenu functionality
    initSubmenuToggle();
});

// Submenu toggle functionality
function initSubmenuToggle() {
    const submenuParents = document.querySelectorAll('.sidebar-nav .has-submenu > a');

    submenuParents.forEach(parent => {
        parent.addEventListener('click', function(e) {
            e.preventDefault(); // Always prevent default

            const sidebar = document.getElementById('sidebar');
            const li = this.parentElement;

            // Only toggle submenu if sidebar is NOT collapsed
            if (!sidebar.classList.contains('collapsed')) {
                const wasOpen = li.classList.contains('open');

                // Close all other submenus
                document.querySelectorAll('.sidebar-nav .has-submenu').forEach(item => {
                    if (item !== li) {
                        item.classList.remove('open');
                    }
                });

                // Toggle current submenu
                li.classList.toggle('open');
            }
        });
    });
}

// Handle window resize
window.addEventListener('resize', function() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');

    if (window.innerWidth <= 768) {
        // Mobile: remove overlay if sidebar is collapsed
        if (sidebar.classList.contains('collapsed')) {
            overlay.classList.remove('active');
        }
    } else {
        // Desktop: always remove overlay
        overlay.classList.remove('active');
    }
});
</script>