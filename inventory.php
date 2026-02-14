<?php
require_once 'config.php';
check_login();

$page_title = 'Inventory Items';

// Check if current user is admin
$is_admin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';

// Handle single item delete (soft delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $is_admin) {

    if ($_POST['action'] === 'delete' && isset($_POST['item_id'])) {
        $item_id = (int)$_POST['item_id'];
        $stmt = $conn->prepare("UPDATE inventory_items SET is_active = 0 WHERE id = ?");
        $stmt->bind_param("i", $item_id);
        $stmt->execute();
        $stmt->close();
        $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Item deleted successfully.'];
        header("Location: inventory.php?" . http_build_query(array_intersect_key($_GET, array_flip(['search','category','status','location','page']))));
        exit;
    }

    if ($_POST['action'] === 'bulk_delete' && isset($_POST['item_ids']) && is_array($_POST['item_ids'])) {
        $ids = array_map('intval', $_POST['item_ids']);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $types = str_repeat('i', count($ids));
        $stmt = $conn->prepare("UPDATE inventory_items SET is_active = 0 WHERE id IN ($placeholders)");
        $stmt->bind_param($types, ...$ids);
        $stmt->execute();
        $count = $stmt->affected_rows;
        $stmt->close();
        $_SESSION['flash_message'] = ['type' => 'success', 'text' => "$count item(s) deleted successfully."];
        header("Location: inventory.php?" . http_build_query(array_intersect_key($_GET, array_flip(['search','category','status','location','page']))));
        exit;
    }
}

// Handle search and filters
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$status_filter = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';
$location_filter = isset($_GET['location']) ? (int)$_GET['location'] : 0;

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build query
$where = ["i.is_active = 1"];
$params = [];
$types = "";

if ($search) {
    $where[] = "(i.item_code LIKE ? OR i.item_description LIKE ? OR i.brand LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "sss";
}

if ($category_filter) {
    $where[] = "i.category_id = ?";
    $params[] = $category_filter;
    $types .= "i";
}

if ($location_filter) {
    $where[] = "i.storage_location_id = ?";
    $params[] = $location_filter;
    $types .= "i";
}

if ($status_filter === 'low_stock') {
    $where[] = "i.items_on_hand <= i.minimum_stock_level";
}

$where_clause = implode(' AND ', $where);

// Get total count
$count_query = "SELECT COUNT(*) as total FROM inventory_items i WHERE $where_clause";
$stmt = $conn->prepare($count_query);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$total = $stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total / $per_page);
$stmt->close();

// Get items
$query = "
    SELECT 
        i.*,
        c.category_name,
        c.color as category_color,
        sl.location_name
    FROM inventory_items i
    JOIN categories c ON i.category_id = c.id
    LEFT JOIN storage_locations sl ON i.storage_location_id = sl.id
    WHERE $where_clause
    ORDER BY i.created_at DESC
    LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($query);
$params[] = $per_page;
$params[] = $offset;
$types .= "ii";
$stmt->bind_param($types, ...$params);
$stmt->execute();
$items = $stmt->get_result();
$stmt->close();

// Get categories for filter
$categories = $conn->query("SELECT id, category_name FROM categories WHERE is_active = 1 ORDER BY category_name");

// Get storage locations for filter
$storage_locations = $conn->query("SELECT id, location_name FROM storage_locations WHERE is_active = 1 ORDER BY location_name");

// Flash message
$flash = isset($_SESSION['flash_message']) ? $_SESSION['flash_message'] : null;
unset($_SESSION['flash_message']);

require_once 'header.php';
?>

<?php if ($flash): ?>
<div class="alert alert-<?php echo $flash['type'] === 'success' ? 'success' : 'danger'; ?>" 
     style="padding: 12px 16px; margin-bottom: 16px; border-radius: 6px; 
            background-color: <?php echo $flash['type'] === 'success' ? '#d1fae5' : '#fee2e2'; ?>; 
            color: <?php echo $flash['type'] === 'success' ? '#065f46' : '#991b1b'; ?>; 
            border: 1px solid <?php echo $flash['type'] === 'success' ? '#6ee7b7' : '#fca5a5'; ?>;">
    <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
    <?php echo htmlspecialchars($flash['text']); ?>
</div>
<?php endif; ?>

<div style="margin-bottom: 20px; margin-right: 5px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
        <form method="GET" style="display: flex; gap: 10px; flex-wrap: wrap;">
            <div class="search-bar">
                <i class="fas fa-search search-icon"></i>
                <input 
                    type="text" 
                    name="search" 
                    class="search-input" 
                    placeholder="Search items..."
                    value="<?php echo htmlspecialchars($search); ?>"
                >
            </div>
            
            <select name="category" class="form-control" style="width: 200px;">
                <option value="">All Categories</option>
                <?php while ($cat = $categories->fetch_assoc()): ?>
                    <option value="<?php echo $cat['id']; ?>" <?php echo $category_filter == $cat['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat['category_name']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
            
            <select name="location" class="form-control" style="width: 200px;">
                <option value="">All Storage Locations</option>
                <?php while ($loc = $storage_locations->fetch_assoc()): ?>
                    <option value="<?php echo $loc['id']; ?>" <?php echo $location_filter == $loc['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($loc['location_name']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
            
            <select name="status" class="form-control" style="width: 150px;">
                <option value="">All Status</option>
                <option value="low_stock" <?php echo $status_filter === 'low_stock' ? 'selected' : ''; ?>>Low Stock</option>
            </select>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-filter"></i> Filter
            </button>
            
            <?php if ($search || $category_filter || $status_filter || $location_filter): ?>
                <a href="inventory.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Clear
                </a>
            <?php endif; ?>
        </form>
    </div>
    
    <div style="display: flex; gap: 10px; align-items: center;">
        <?php if ($is_admin): ?>
            <button id="bulkDeleteBtn" class="btn btn-danger" style="display: none;" onclick="confirmBulkDelete()">
                <i class="fas fa-trash-alt"></i> Delete Selected (<span id="selectedCount">0</span>)
            </button>
        <?php endif; ?>
        <a href="add_item.php" class="btn btn-success">
            <i class="fas fa-plus"></i> Add New Item
        </a>
    </div>
</div>

<!-- Bulk delete form (hidden, submitted via JS) -->
<?php if ($is_admin): ?>
<form id="bulkDeleteForm" method="POST" action="inventory.php?<?php echo http_build_query(array_intersect_key($_GET, array_flip(['search','category','status','page']))); ?>">
    <input type="hidden" name="action" value="bulk_delete">
    <div id="bulkIdsContainer"></div>
</form>
<?php endif; ?>

<div class="table-container">
    <div class="table-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h3 class="table-title">Inventory Items (<?php echo number_format($total); ?> total)</h3>
        <?php if ($is_admin && $total > 0): ?>
            <label style="font-size: 13px; color: #6b7280; cursor: pointer; user-select: none;">
                <input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)" style="margin-right: 6px;">
                Select All on Page
            </label>
        <?php endif; ?>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <?php if ($is_admin): ?>
                        <th style="width: 36px;"></th>
                    <?php endif; ?>
                    <th>Item Code</th>
                    <th>Description</th>
                    <!-- <th>Category</th> -->
                    <th>On Hand</th>
                    <th>Unit</th>
                    <th>Received</th>
                    <th>Distributed</th>
                    <th>Expiry Date</th>
                    <th>Min Level</th>
                    <!-- <th>Unit Cost</th>
                    <th>Total Value</th> -->
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($items->num_rows > 0): ?>
                    <?php while ($row = $items->fetch_assoc()): ?>
                        <tr id="row-<?php echo $row['id']; ?>">
                            <?php if ($is_admin): ?>
                                <td>
                                    <input 
                                        type="checkbox" 
                                        class="row-checkbox" 
                                        value="<?php echo $row['id']; ?>" 
                                        onchange="updateBulkDeleteBtn()"
                                        style="cursor: pointer;"
                                    >
                                </td>
                            <?php endif; ?>
                            <td><strong><?php echo htmlspecialchars($row['item_code']); ?></strong></td>
                            <td>
                                <?php echo htmlspecialchars(substr($row['item_description'], 0, 50)) . (strlen($row['item_description']) > 50 ? '...' : ''); ?>
                            </td>
                            <!-- <td>
                                <span class="badge badge-primary" style="background-color: <?php echo htmlspecialchars($row['category_color'] ?? '#3b82f6'); ?>20; color: <?php echo htmlspecialchars($row['category_color'] ?? '#3b82f6'); ?>;">
                                    <?php echo htmlspecialchars($row['category_name']); ?>
                                </span>
                            </td> -->
                            <td>
                                <?php if ($row['items_on_hand'] <= 0): ?>
                                    <span class="badge badge-danger"><?php echo number_format($row['items_on_hand']); ?></span>
                                <?php elseif ($row['items_on_hand'] <= $row['minimum_stock_level']): ?>
                                    <span class="badge badge-warning"><?php echo number_format($row['items_on_hand']); ?></span>
                                <?php else: ?>
                                    <span class="badge badge-success"><?php echo number_format($row['items_on_hand']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['unit'] ?? 'pcs'); ?></td>
                            <td><?php echo number_format($row['items_received']); ?></td>
                            <td><?php echo number_format($row['items_distributed']); ?></td>
                            <td>
                                <?php if ($row['expiration_date']): ?>
                                    <?php 
                                    $expiry = strtotime($row['expiration_date']);
                                    $today = time();
                                    $days_diff = floor(($expiry - $today) / (60 * 60 * 24));
                                    
                                    if ($days_diff < 0): ?>
                                        <span class="badge badge-danger" title="Expired <?php echo abs($days_diff); ?> days ago">
                                            <?php echo date('M d, Y', $expiry); ?>
                                        </span>
                                    <?php elseif ($days_diff <= 30): ?>
                                        <span class="badge badge-warning" title="Expires in <?php echo $days_diff; ?> days">
                                            <?php echo date('M d, Y', $expiry); ?>
                                        </span>
                                    <?php else: ?>
                                        <?php echo date('M d, Y', $expiry); ?>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span style="color: var(--gray-400);">-</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo number_format($row['minimum_stock_level']); ?></td>
                            <!-- <td>₱<?php echo number_format($row['unit_cost'], 2); ?></td>
                            <td>₱<?php echo number_format($row['items_on_hand'] * $row['unit_cost'], 2); ?></td> -->
                            <td>
                                <?php if ($row['items_on_hand'] <= 0): ?>
                                    <span class="badge badge-danger">Out of Stock</span>
                                <?php elseif ($row['items_on_hand'] <= $row['minimum_stock_level']): ?>
                                    <span class="badge badge-warning">Low Stock</span>
                                <?php else: ?>
                                    <span class="badge badge-success">In Stock</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display: flex; gap: 8px; align-items: center;">
                                    <a href="view_item.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary" title="View Details" style="padding: 6px 12px; display: inline-flex; align-items: center; justify-content: center; border-radius: 6px;">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="edit_item.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning" title="Edit" style="padding: 6px 12px; display: inline-flex; align-items: center; justify-content: center; border-radius: 6px;">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    
                                    <?php if ($is_admin): ?>
                                        <button onclick="confirmDelete(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['item_code'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars(substr($row['item_description'], 0, 50), ENT_QUOTES); ?>')" 
                                            class="btn btn-sm btn-danger" title="Delete" 
                                            style="padding: 6px 12px; display: inline-flex; align-items: center; justify-content: center; border-radius: 6px; border: none; cursor: pointer;">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="<?php echo $is_admin ? 11 : 10; ?>" class="text-center">No items found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $category_filter; ?>&status=<?php echo $status_filter; ?>">
                    <i class="fas fa-chevron-left"></i> Previous
                </a>
            <?php endif; ?>
            
            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $category_filter; ?>&status=<?php echo $status_filter; ?>" 
                   class="<?php echo $i === $page ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $category_filter; ?>&status=<?php echo $status_filter; ?>">
                    Next <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php if ($is_admin): ?>

<!-- Single Delete Confirmation Modal -->
<div id="deleteModal" style="display:none; position:fixed; inset:0; z-index:1000; align-items:center; justify-content:center;">
    <!-- Backdrop -->
    <div onclick="closeDeleteModal()" style="position:absolute; inset:0; background:rgba(0,0,0,0.45);"></div>
    <!-- Dialog -->
    <div style="position:relative; background:#fff; border-radius:10px; padding:28px 28px 20px; max-width:420px; width:90%; box-shadow:0 10px 40px rgba(0,0,0,0.2);">
        <div style="display:flex; align-items:center; gap:14px; margin-bottom:14px;">
            <div style="background:#fee2e2; border-radius:50%; width:44px; height:44px; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <i class="fas fa-trash" style="color:#dc2626; font-size:18px;"></i>
            </div>
            <div>
                <h4 style="margin:0; font-size:16px; font-weight:600; color:#111827;">Delete Item</h4>
                <p style="margin:4px 0 0; font-size:13px; color:#6b7280;">This action cannot be undone.</p>
            </div>
        </div>
        <p id="deleteModalBody" style="font-size:14px; color:#374151; margin:0 0 20px; line-height:1.5;"></p>
        <div style="display:flex; justify-content:flex-end; gap:10px;">
            <button type="button" onclick="closeDeleteModal()" class="btn btn-secondary">Cancel</button>
            <a href="#" id="deleteConfirmBtn" class="btn btn-danger" style="text-decoration: none;">
                <i class="fas fa-trash"></i> Delete
            </a>
        </div>
    </div>
</div>

<!-- Bulk Delete Confirmation Modal -->
<div id="bulkDeleteModal" style="display:none; position:fixed; inset:0; z-index:1000; align-items:center; justify-content:center;">
    <div onclick="closeBulkDeleteModal()" style="position:absolute; inset:0; background:rgba(0,0,0,0.45);"></div>
    <div style="position:relative; background:#fff; border-radius:10px; padding:28px 28px 20px; max-width:420px; width:90%; box-shadow:0 10px 40px rgba(0,0,0,0.2);">
        <div style="display:flex; align-items:center; gap:14px; margin-bottom:14px;">
            <div style="background:#fee2e2; border-radius:50%; width:44px; height:44px; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <i class="fas fa-trash-alt" style="color:#dc2626; font-size:18px;"></i>
            </div>
            <div>
                <h4 style="margin:0; font-size:16px; font-weight:600; color:#111827;">Delete Selected Items</h4>
                <p style="margin:4px 0 0; font-size:13px; color:#6b7280;">This action cannot be undone.</p>
            </div>
        </div>
        <p id="bulkDeleteModalBody" style="font-size:14px; color:#374151; margin:0 0 20px; line-height:1.5;"></p>
        <div style="display:flex; justify-content:flex-end; gap:10px;">
            <button type="button" onclick="closeBulkDeleteModal()" class="btn btn-secondary">Cancel</button>
            <button type="button" onclick="submitBulkDelete()" class="btn btn-danger">
                <i class="fas fa-trash-alt"></i> Delete All Selected
            </button>
        </div>
    </div>
</div>

<script>
// ── Single delete ──────────────────────────────────────────────
function confirmDelete(id, code, description) {
    document.getElementById('deleteModalBody').innerHTML =
        'Are you sure you want to delete item <strong>' + code + '</strong> &ndash; ' + description + '?';
    
    // Build the delete URL with current filters
    const params = new URLSearchParams(window.location.search);
    let deleteUrl = 'delete_item.php?id=' + id;
    if (params.has('search')) deleteUrl += '&search=' + encodeURIComponent(params.get('search'));
    if (params.has('category')) deleteUrl += '&category=' + encodeURIComponent(params.get('category'));
    if (params.has('status')) deleteUrl += '&status=' + encodeURIComponent(params.get('status'));
    
    document.getElementById('deleteConfirmBtn').href = deleteUrl;
    document.getElementById('deleteModal').style.display = 'flex';
}
function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
}

// ── Bulk delete ────────────────────────────────────────────────
function updateBulkDeleteBtn() {
    const checked = document.querySelectorAll('.row-checkbox:checked');
    const btn = document.getElementById('bulkDeleteBtn');
    document.getElementById('selectedCount').textContent = checked.length;
    btn.style.display = checked.length > 0 ? 'inline-flex' : 'none';

    // Keep "select all" checkbox in sync
    const all = document.querySelectorAll('.row-checkbox');
    const selectAll = document.getElementById('selectAll');
    if (selectAll) {
        selectAll.indeterminate = checked.length > 0 && checked.length < all.length;
        selectAll.checked = all.length > 0 && checked.length === all.length;
    }
}

function toggleSelectAll(checkbox) {
    document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = checkbox.checked);
    updateBulkDeleteBtn();
}

function confirmBulkDelete() {
    const checked = document.querySelectorAll('.row-checkbox:checked');
    if (checked.length === 0) return;
    document.getElementById('bulkDeleteModalBody').innerHTML =
        'You are about to delete <strong>' + checked.length + ' item(s)</strong>. They will be removed from the active inventory.';
    document.getElementById('bulkDeleteModal').style.display = 'flex';
}
function closeBulkDeleteModal() {
    document.getElementById('bulkDeleteModal').style.display = 'none';
}
function submitBulkDelete() {
    const container = document.getElementById('bulkIdsContainer');
    container.innerHTML = '';
    document.querySelectorAll('.row-checkbox:checked').forEach(cb => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'item_ids[]';
        input.value = cb.value;
        container.appendChild(input);
    });
    document.getElementById('bulkDeleteForm').submit();
}

// Close modals on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeDeleteModal();
        closeBulkDeleteModal();
    }
});
</script>
<?php endif; ?>

<?php require_once 'footer.php'; ?>