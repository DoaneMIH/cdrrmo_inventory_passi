CDRRMO INVENTORY SYSTEM (PASSI)

The CDRRMO Inventory Management System is a web-based application designed for the Passi City Disaster Risk Reduction & Management Office to track and manage emergency supplies, equipment, and resources.

Key Features:
    Real-time inventory tracking
    Transaction recording (Receive & Distribute)
    User management with role-based access
    Comprehensive reporting
    Activity logging & audit trail
    Low stock alerts
    Category-based organization

USER ROLES
1. ADMIN
Full System Access:
    All Staff permissions
    User management (create, edit, delete users)
    View user passwords
    Activity logs access
    System configuration
2. STAFF
Operational Access:
    View inventory
    Add/Edit items
    Receive items (incoming stock)
    Distribute items (outgoing stock)
    View transactions
    Generate reports
    Manage categories, suppliers, locations
    Change own password
    ❌ Cannot access user management
    ❌ Cannot view activity logs

🔍 ACTIVITY LOGS (Admin Only)
What's Logged:
    User login/logout
    Item creation/updates/deletion
    Transactions (receive/distribute)
    User management actions
    Password changes
    Report generation


📊 REPORTS SYSTEM
1. Inventory Summary Report
    Shows: Complete list of all items with stock and values
    Filters: Category, Status (Low Stock, Out of Stock)
    Use Case: Monthly inventory review
2. Transaction Report
    Shows: All received and distributed items
    Filters: Transaction Type, Date Range
    Use Case: Audit trail, accountability
3. Category Report
    Shows: Inventory breakdown by category
    Data: Item count, stock levels, values per category
    Use Case: Resource allocation planning
4. Valuation Report
    Shows: Financial value of inventory
    Data: Current value, received value, distributed value
    Use Case: Budget planning, asset reporting
5. Low Stock Report
    Shows: Items needing restock
    Auto-alerts: Items at or below minimum level
    Use Case: Procurement planning
6. Supplier Report
    Shows: Transaction history per supplier
    Data: Transaction count, items received, total value
    Use Case: Supplier performance evaluation
All Reports Feature:
•   Print functionality
•   CSV export
•   Date filters
•   Summary statistics

SUPPORT & TROUBLESHOOTING
Problem: Cannot login
Solution:
    Verify username/password
    Check if account is active (admin can check)
    Clear browser cache and cookies
    Contact admin to reset password
Problem: Item not showing
Solution:
    Check if item is marked as "Active"
    Check category filter
    Check search terms
    Refresh page
Problem: Cannot distribute items
Solution:
    Verify sufficient stock available
    Check if item is active
    Ensure all required fields filled (recipient info)
Problem: Report not generating
Solution:
    Check date range filters
    Ensure transactions exist for selected period
    Try clearing filters
    Refresh page


Core Functionality
Dashboard: Real-time statistics, recent transactions, low stock alerts, expiring items
Inventory Management: Track all items with categories, stock levels, locations
Transaction Tracking: Receive and distribute items with complete audit trail
Low Stock Alerts: Automatic notifications with priority levels
User Management (Admin only): Create, edit, activate/deactivate users, change roles
Category Management: Color-coded categories matching emergency services
Supplier Management: Track suppliers and transaction history
Password Management: Secure password changes for all users


⚠️ ALERTS & NOTIFICATIONS
Low Stock Alert

    Triggered when: Items On Hand ≤ Minimum Stock Level
    Shows on: Dashboard, Low Stock Report, Inventory List
    Color: Yellow badge

Out of Stock Alert
    Triggered when: Items On Hand = 0
    Shows on: Dashboard, Inventory List
    Color: Red badge

Expiration Warnings
    Red: Expired items
    Yellow: Expiring within 30 days
    Shows on: Inventory table

🔐 SECURITY FEATURES
Password Protection
    Passwords hashed (bcrypt)
    Plain passwords stored for admin recovery
    Minimum 6 characters required

Role-Based Access Control
    Admin: Full access
    Staff: Limited access (no user management)

Activity Logging
    All actions tracked
    IP address recorded
    Audit trail maintained

Session Management
    Auto-logout on browser close
    Session validation on each page


📞 SUPPORT & TROUBLESHOOTING
Problem: Cannot login
Solution:
    Verify username/password
    Check if account is active (admin can check)
    Clear browser cache and cookies
    Contact admin to reset password

Problem: Item not showing
Solution:
    Check if item is marked as "Active"
    Check category filter
    Check search terms
    Refresh page

Problem: Cannot distribute items
Solution:
    Verify sufficient stock available
    Check if item is active
    Ensure all required fields filled (recipient info)

Problem: Report not generating
Solution:
    Check date range filters
    Ensure transactions exist for selected period
    Try clearing filters
    Refresh page

📈BEST PRACTICES

Regular Backups
    Export database weekly
    Store backups securely

Password Management
    Change default passwords immediately
    Use strong passwords
    Don't share accounts

Inventory Accuracy
    Record transactions promptly
    Conduct regular physical counts
    Investigate discrepancies

Documentation
    Use reference numbers (PO, DR, Invoice)
    Add detailed notes
    Keep transaction records

User Training
    Train all users on system
    Document procedures
    Review activity logs regularly
    

Core Inventory Management:
inventory.php: Acts as the primary interface for managing the inventory list. It allows users to view, search, and filter items by category, location, or stock status. For administrators, it also provides functionality for "soft" deleting individual items or performing bulk deletions.

categories.php: Manages the classification of items. It provides a system to create, update, and toggle the status of different item categories (e.g., assigning specific names, codes, and colors for visual organization).

locations.php: Used to manage physical or logical storage areas. It includes features to add or update storage locations, including their names, codes, descriptions, and storage capacities.

suppliers.php: Handles the directory of vendors. It allows the system to store and update contact information for suppliers, such as contact persons, phone numbers, emails, and physical addresses.

Transaction Processing:

receive_items.php: Facilitates the addition of stock to the system. It handles "Receive" transactions, allowing users to select an item, specify the quantity and unit cost, link it to a supplier, and record batch or expiration details.

distribute_items.php: Manages the removal or distribution of stock. It validates whether enough stock is available before allowing a user to record recipient details, the purpose of the distribution, and the quantity being moved.

transactions.php: Serves as a historical ledger for all movements. It allows users to search and filter through past "Receive" and "Distribute" transactions using criteria like date ranges, transaction types, or specific items.


Monitoring and Administration:

dashboard.php: Provides a high-level overview of the system's status. It calculates and displays key statistics, such as the total number of active items, total inventory value, and counts for low-stock items.

low_stock.php: A dedicated alert page for inventory maintenance. It specifically queries items that have fallen below their defined "minimum stock level" and provides a summary of "Critical" or "Out of Stock" items.

activity_logs.php: A security and audit tool restricted to administrators. It records system actions (like creating a category or updating a supplier) and tracks which user performed the action and when.
