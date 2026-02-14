<?php
require_once 'config.php';
check_login();

$page_title = 'Transactions';

// Handle search and filters
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$type_filter = isset($_GET['type']) ? sanitize_input($_GET['type']) : '';
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$date_from = isset($_GET['date_from']) ? sanitize_input($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? sanitize_input($_GET['date_to']) : '';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 25;
$offset = ($page - 1) * $per_page;

// Build query
$where = ["1=1"];
$params = [];
$types = "";

if ($search) {
    $where[] = "(t.transaction_code LIKE ? OR i.item_description LIKE ? OR t.recipient_name LIKE ? OR t.recipient_organization LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "ssss";
}

if ($type_filter) {
    $where[] = "t.transaction_type = ?";
    $params[] = $type_filter;
    $types .= "s";
}

if ($category_filter) {
    $where[] = "i.category_id = ?";
    $params[] = $category_filter;
    $types .= "i";
}

if ($date_from) {
    $where[] = "t.transaction_date >= ?";
    $params[] = $date_from;
    $types .= "s";
}

if ($date_to) {
    $where[] = "t.transaction_date <= ?";
    $params[] = $date_to;
    $types .= "s";
}

$where_clause = implode(' AND ', $where);

// Get total count
$count_query = "
    SELECT COUNT(*) as total 
    FROM transactions t
    JOIN inventory_items i ON t.item_id = i.id
    WHERE $where_clause
";
$stmt = $conn->prepare($count_query);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$total = $stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total / $per_page);
$stmt->close();

// Get transactions
$query = "
    SELECT 
        t.*,
        i.item_code,
        i.item_description,
        i.unit,
        c.category_name,
        c.color as category_color,
        s.supplier_name,
        u.full_name as created_by_name
    FROM transactions t
    JOIN inventory_items i ON t.item_id = i.id
    JOIN categories c ON i.category_id = c.id
    LEFT JOIN suppliers s ON t.supplier_id = s.id
    LEFT JOIN users u ON t.created_by = u.id
    WHERE $where_clause
    ORDER BY t.transaction_date DESC, t.created_at DESC
    LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($query);
$params[] = $per_page;
$params[] = $offset;
$types .= "ii";
$stmt->bind_param($types, ...$params);
$stmt->execute();
$transactions = $stmt->get_result();
$stmt->close();

// Get categories for filter
$categories = $conn->query("SELECT id, category_name FROM categories WHERE is_active = 1 ORDER BY category_name");

// Get statistics
$stats = [
    'total_received' => 0,
    'total_distributed' => 0,
    'total_value' => 0,
    'transaction_count' => $total
];

$stats_query = "
    SELECT 
        SUM(CASE WHEN transaction_type = 'received' THEN quantity ELSE 0 END) as total_received,
        SUM(CASE WHEN transaction_type = 'distributed' THEN quantity ELSE 0 END) as total_distributed,
        SUM(total_cost) as total_value
    FROM transactions t
    JOIN inventory_items i ON t.item_id = i.id
    WHERE $where_clause
";
$stmt = $conn->prepare($stats_query);
if (count($params) > 2) {
    $temp_params = array_slice($params, 0, -2);
    $temp_types = substr($types, 0, -2);
    $stmt->bind_param($temp_types, ...$temp_params);
}
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$stats['total_received'] = $result['total_received'] ?? 0;
$stats['total_distributed'] = $result['total_distributed'] ?? 0;
$stats['total_value'] = $result['total_value'] ?? 0;
$stmt->close();

require_once 'header.php';
?>

<!-- Statistics Cards -->
<div class="dashboard-cards" style="margin-bottom: 30px;">
    <div class="card stat-card">
        <div class="stat-icon green">
            <i class="fas fa-arrow-down"></i>
        </div>
        <div class="stat-info">
            <div class="stat-label">Total Received</div>
            <div class="stat-value"><?php echo number_format($stats['total_received']); ?></div>
        </div>
    </div>
    
    <div class="card stat-card">
        <div class="stat-icon yellow">
            <i class="fas fa-arrow-up"></i>
        </div>
        <div class="stat-info">
            <div class="stat-label">Total Distributed</div>
            <div class="stat-value"><?php echo number_format($stats['total_distributed']); ?></div>
        </div>
    </div>
    
    <div class="card stat-card">
        <div class="stat-icon blue">
            <i class="fas fa-exchange-alt"></i>
        </div>
        <div class="stat-info">
            <div class="stat-label">Total Transactions</div>
            <div class="stat-value"><?php echo number_format($stats['transaction_count']); ?></div>
        </div>
    </div>
    
    <div class="card stat-card">
        <div class="stat-icon red">
            <i class="fas fa-peso-sign"></i>
        </div>
        <div class="stat-info">
            <div class="stat-label">Total Value</div>
            <div class="stat-value">₱<?php echo number_format($stats['total_value'], 2); ?></div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card" style="margin-bottom: 20px; padding: 20px;">
    <form method="GET" action="transactions.php">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
            <div class="form-group" style="margin: 0;">
                <label class="form-label" style="margin-bottom: 5px;">Search</label>
                <input 
                    type="text" 
                    name="search" 
                    class="form-control" 
                    placeholder="Transaction code, item, recipient..."
                    value="<?php echo htmlspecialchars($search); ?>"
                >
            </div>
            
            <div class="form-group" style="margin: 0;">
                <label class="form-label" style="margin-bottom: 5px;">Transaction Type</label>
                <select name="type" class="form-control">
                    <option value="">All Types</option>
                    <option value="received" <?php echo $type_filter === 'received' ? 'selected' : ''; ?>>Received</option>
                    <option value="distributed" <?php echo $type_filter === 'distributed' ? 'selected' : ''; ?>>Distributed</option>
                    <option value="adjustment" <?php echo $type_filter === 'adjustment' ? 'selected' : ''; ?>>Adjustment</option>
                    <!-- <option value="return" <?php echo $type_filter === 'return' ? 'selected' : ''; ?>>Return</option> -->
                    <!-- <option value="damaged" <?php echo $type_filter === 'damaged' ? 'selected' : ''; ?>>Damaged</option> -->
                    <!-- <option value="expired" <?php echo $type_filter === 'expired' ? 'selected' : ''; ?>>Expired</option> -->
                </select>
            </div>
            
            <div class="form-group" style="margin: 0;">
                <label class="form-label" style="margin-bottom: 5px;">Category</label>
                <select name="category" class="form-control">
                    <option value="">All Categories</option>
                    <?php 
                    $categories->data_seek(0);
                    while ($cat = $categories->fetch_assoc()): 
                    ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $category_filter == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['category_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group" style="margin: 0;">
                <label class="form-label" style="margin-bottom: 5px;">Date From</label>
                <input 
                    type="date" 
                    name="date_from" 
                    class="form-control"
                    value="<?php echo htmlspecialchars($date_from); ?>"
                >
            </div>
            
            <div class="form-group" style="margin: 0;">
                <label class="form-label" style="margin-bottom: 5px;">Date To</label>
                <input 
                    type="date" 
                    name="date_to" 
                    class="form-control"
                    value="<?php echo htmlspecialchars($date_to); ?>"
                >
            </div>
            
            <div style="display: flex; align-items: flex-end; gap: 10px;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">
                    <i class="fas fa-filter"></i> Filter
                </button>
                <?php if ($search || $type_filter || $category_filter || $date_from || $date_to): ?>
                    <a href="transactions.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>

<!-- Transactions Table -->
<div class="table-container">
    <div class="table-header">
        <h3 class="table-title">Transaction History (<?php echo number_format($total); ?> records)</h3>
        <div style="display: flex; gap: 10px;">
            <button class="btn btn-sm btn-primary" onclick="printTable('transactionsTable')">
                <i class="fas fa-print"></i> Print
            </button>
            <button class="btn btn-sm btn-success" onclick="exportTableToCSV('transactionsTable', 'transactions_<?php echo date('Y-m-d'); ?>.csv')">
                <i class="fas fa-download"></i> Export CSV
            </button>
        </div>
    </div>
    <div class="table-responsive">
        <table id="transactionsTable">
            <thead>
                <tr>
                    <th>Transaction Code</th>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Item Code</th>
                    <th>Description</th>
                    <th>Category</th>
                    <th>Quantity</th>
                    <th>Unit Cost</th>
                    <th>Total Cost</th>
                    <th>Supplier/Recipient</th>
                    <th>Created By</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($transactions->num_rows > 0): ?>
                    <?php while ($row = $transactions->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <strong style="color: var(--primary-blue);">
                                    <?php echo htmlspecialchars($row['transaction_code']); ?>
                                </strong>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($row['transaction_date'])); ?></td>
                            <td>
                                <?php 
                                $badge_class = 'badge-info';
                                $icon = 'fa-exchange-alt';
                                switch ($row['transaction_type']) {
                                    case 'received':
                                        $badge_class = 'badge-success';
                                        $icon = 'fa-arrow-down';
                                        break;
                                    case 'distributed':
                                        $badge_class = 'badge-warning';
                                        $icon = 'fa-arrow-up';
                                        break;
                                    case 'adjustment':
                                        $badge_class = 'badge-info';
                                        $icon = 'fa-edit';
                                        break;
                                    case 'return':
                                        $badge_class = 'badge-primary';
                                        $icon = 'fa-undo';
                                        break;
                                    case 'damaged':
                                        $badge_class = 'badge-danger';
                                        $icon = 'fa-exclamation-triangle';
                                        break;
                                    case 'expired':
                                        $badge_class = 'badge-danger';
                                        $icon = 'fa-clock';
                                        break;
                                }
                                ?>
                                <span class="badge <?php echo $badge_class; ?>">
                                    <i class="fas <?php echo $icon; ?>"></i>
                                    <?php echo ucfirst($row['transaction_type']); ?>
                                </span>
                            </td>
                            <td><strong><?php echo htmlspecialchars($row['item_code']); ?></strong></td>
                            <td>
                                <?php 
                                $desc = htmlspecialchars($row['item_description']);
                                echo strlen($desc) > 50 ? substr($desc, 0, 50) . '...' : $desc;
                                ?>
                            </td>
                            <td>
                                <span class="badge" style="background-color: <?php echo htmlspecialchars($row['category_color'] ?? '#3b82f6'); ?>20; color: <?php echo htmlspecialchars($row['category_color'] ?? '#3b82f6'); ?>;">
                                    <?php echo htmlspecialchars($row['category_name']); ?>
                                </span>
                            </td>
                            <td>
                                <strong>
                                    <?php 
                                    if (in_array($row['transaction_type'], ['distributed', 'damaged', 'expired'])) {
                                        echo '<span style="color: var(--danger);">-' . number_format($row['quantity']) . '</span>';
                                    } else {
                                        echo '<span style="color: var(--success);">+' . number_format($row['quantity']) . '</span>';
                                    }
                                    ?>
                                </strong>
                                <?php echo htmlspecialchars($row['unit']); ?>
                            </td>
                            <td>₱<?php echo number_format($row['unit_cost'], 2); ?></td>
                            <td>
                                <strong style="color: var(--primary-blue);">
                                    ₱<?php echo number_format($row['total_cost'], 2); ?>
                                </strong>
                            </td>
                            <td>
                                <?php 
                                if ($row['transaction_type'] === 'received') {
                                    echo '<i class="fas fa-truck" style="color: var(--gray-400);"></i> ';
                                    echo htmlspecialchars($row['supplier_name'] ?? '-');
                                } elseif ($row['transaction_type'] === 'distributed') {
                                    echo '<i class="fas fa-user" style="color: var(--gray-400);"></i> ';
                                    $recipient = $row['recipient_name'] ?? '-';
                                    if ($row['recipient_organization']) {
                                        $recipient .= ' (' . $row['recipient_organization'] . ')';
                                    }
                                    echo htmlspecialchars(strlen($recipient) > 30 ? substr($recipient, 0, 30) . '...' : $recipient);
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['created_by_name'] ?? 'System'); ?></td>
                            <td>
                                <button class="btn btn-sm btn-primary" onclick="viewTransaction(<?php echo $row['id']; ?>)" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="12" class="text-center">
                            <div style="padding: 40px;">
                                <i class="fas fa-inbox" style="font-size: 48px; color: var(--gray-400); margin-bottom: 15px;"></i>
                                <h3 style="color: var(--gray-500); margin: 0;">No transactions found</h3>
                                <p style="color: var(--gray-400); margin-top: 10px;">Try adjusting your filters or add new transactions</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo $type_filter; ?>&category=<?php echo $category_filter; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>">
                    <i class="fas fa-chevron-left"></i> Previous
                </a>
            <?php endif; ?>
            
            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo $type_filter; ?>&category=<?php echo $category_filter; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>" 
                   class="<?php echo $i === $page ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo $type_filter; ?>&category=<?php echo $category_filter; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>">
                    Next <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Transaction Detail Modal -->
<div class="modal" id="transactionModal">
    <div class="modal-content" style="max-width: 800px;">
        <div class="modal-header">
            <h3 class="modal-title">Transaction Details</h3>
            <button class="modal-close" onclick="hideTransactionModal()">&times;</button>
        </div>
        <div class="modal-body" id="transactionDetails">
            <div class="spinner"></div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="hideTransactionModal()">Close</button>
            <button type="button" class="btn btn-primary" onclick="printTransaction()">
                <i class="fas fa-print"></i> Print
            </button>
        </div>
    </div>
</div>

<script>
function viewTransaction(transactionId) {
    document.getElementById('transactionModal').classList.add('show');
    document.getElementById('transactionDetails').innerHTML = '<div class="spinner"></div>';
    
    fetch('get_transaction_details.php?id=' + transactionId)
        .then(response => response.text())
        .then(html => {
            document.getElementById('transactionDetails').innerHTML = html;
        })
        .catch(error => {
            document.getElementById('transactionDetails').innerHTML = 
                '<div class="alert alert-error">Failed to load transaction details</div>';
        });
}

function hideTransactionModal() {
    document.getElementById('transactionModal').classList.remove('show');
}

function printTransaction() {
    const content = document.getElementById('transactionDetails').innerHTML;
    const printWindow = window.open('', '', 'height=600,width=800');
    
    printWindow.document.write('<html><head><title>Transaction Details</title>');
    printWindow.document.write('<link rel="stylesheet" href="css/style.css">');
    printWindow.document.write('<style>@media print { body { padding: 20px; } .no-print { display: none; } }</style>');
    printWindow.document.write('</head><body>');
    printWindow.document.write('<h1>CDRRMO Inventory System</h1>');
    printWindow.document.write('<h2>Passi City</h2>');
    printWindow.document.write('<hr>');
    printWindow.document.write(content);
    printWindow.document.write('</body></html>');
    
    printWindow.document.close();
    setTimeout(() => {
        printWindow.print();
    }, 250);
}

window.onclick = function(event) {
    if (event.target.id === 'transactionModal') {
        hideTransactionModal();
    }
}

// CSV Export Function with proper peso formatting
function exportTableToCSV(tableId, filename) {
    const table = document.getElementById(tableId);
    const rows = table.querySelectorAll('tr');
    let csv = [];
    
    // Add header row
    csv.push([
        'TRANSACTION CODE',
        'DATE',
        'TYPE',
        'ITEM CODE',
        'DESCRIPTION',
        'CATEGORY',
        'QUANTITY',
        'UNIT COST',
        'TOTAL COST',
        'SUPPLIER/RECIPIENT',
        'CREATED BY'
    ].join(','));
    
    // Process data rows
    for (let i = 1; i < rows.length; i++) {
        const row = rows[i];
        const cols = row.querySelectorAll('td');
        
        if (cols.length === 0) continue; // Skip empty rows
        
        let rowData = [];
        
        // Transaction Code
        rowData.push('"' + (cols[1]?.textContent.trim() || '') + '"');
        
        // Date
        rowData.push('"' + (cols[2]?.textContent.trim() || '') + '"');
        
        // Type
        rowData.push('"' + (cols[3]?.textContent.trim() || '') + '"');
        
        // Item Code
        rowData.push('"' + (cols[4]?.textContent.trim() || '') + '"');
        
        // Description
        rowData.push('"' + (cols[5]?.textContent.trim() || '') + '"');
        
        // Category
        rowData.push('"' + (cols[6]?.textContent.trim() || '') + '"');
        
        // Quantity (with + or -)
        rowData.push('"' + (cols[7]?.textContent.trim() || '') + '"');
        
        // Unit Cost - Format with peso sign
        const unitCost = cols[8]?.textContent.trim() || '₱0.00';
        if (!unitCost.startsWith('₱')) {
            rowData.push('"₱' + unitCost + '"');
        } else {
            rowData.push('"' + unitCost + '"');
        }
        
        // Total Cost - Format with peso sign
        const totalCost = cols[9]?.textContent.trim() || '₱0.00';
        if (!totalCost.startsWith('₱')) {
            rowData.push('"₱' + totalCost + '"');
        } else {
            rowData.push('"' + totalCost + '"');
        }
        
        // Supplier/Recipient
        rowData.push('"' + (cols[10]?.textContent.trim() || '') + '"');
        
        // Created By
        rowData.push('"' + (cols[11]?.textContent.trim() || '') + '"');
        
        csv.push(rowData.join(','));
    }
    
    // Create downloadable file
    const csvContent = csv.join('\n');
    const blob = new Blob(['\ufeff' + csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    
    if (link.download !== undefined) {
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', filename);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
}
</script>

<?php require_once 'footer.php'; ?>