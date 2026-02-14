<?php
require_once 'config.php';
check_login();

if (!isset($_GET['id'])) {
    echo '<div class="alert alert-error">Invalid transaction ID</div>';
    exit();
}

$transaction_id = (int)$_GET['id'];

// Get transaction details
$query = "
    SELECT 
        t.*,
        i.item_code,
        i.item_description,
        i.brand,
        i.model,
        i.unit,
        c.category_name,
        c.color as category_color,
        s.supplier_name,
        s.contact_person as supplier_contact,
        s.phone as supplier_phone,
        sl.location_name,
        creator.full_name as created_by_name,
        approver.full_name as approved_by_name
    FROM transactions t
    JOIN inventory_items i ON t.item_id = i.id
    JOIN categories c ON i.category_id = c.id
    LEFT JOIN suppliers s ON t.supplier_id = s.id
    LEFT JOIN storage_locations sl ON i.storage_location_id = sl.id
    LEFT JOIN users creator ON t.created_by = creator.id
    LEFT JOIN users approver ON t.approved_by = approver.id
    WHERE t.id = ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $transaction_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo '<div class="alert alert-error">Transaction not found</div>';
    exit();
}

$t = $result->fetch_assoc();
$stmt->close();
?>

<div style="padding: 10px;">
    <!-- Transaction Header -->
    <div style="background: var(--gray-50); padding: 20px; border-radius: 8px; margin-bottom: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px;">
            <div>
                <h2 style="margin: 0; color: var(--primary-blue); font-size: 24px;">
                    <?php echo htmlspecialchars($t['transaction_code']); ?>
                </h2>
                <p style="margin: 5px 0 0 0; color: var(--gray-600);">
                    <?php echo date('F d, Y', strtotime($t['transaction_date'])); ?>
                </p>
            </div>
            <div>
                <?php 
                $badge_class = 'badge-info';
                $icon = 'fa-exchange-alt';
                switch ($t['transaction_type']) {
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
                <span class="badge <?php echo $badge_class; ?>" style="padding: 8px 16px; font-size: 14px;">
                    <i class="fas <?php echo $icon; ?>"></i>
                    <?php echo ucfirst($t['transaction_type']); ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Item Information -->
    <div style="margin-bottom: 25px;">
        <h3 style="color: var(--gray-800); margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid var(--gray-200);">
            <i class="fas fa-box"></i> Item Information
        </h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
            <div>
                <div style="color: var(--gray-500); font-size: 13px; margin-bottom: 5px;">Item Code</div>
                <div style="font-weight: 600; color: var(--gray-800);">
                    <?php echo htmlspecialchars($t['item_code']); ?>
                </div>
            </div>
            <div>
                <div style="color: var(--gray-500); font-size: 13px; margin-bottom: 5px;">Category</div>
                <div>
                    <span class="badge" style="background-color: <?php echo htmlspecialchars($t['category_color'] ?? '#3b82f6'); ?>20; color: <?php echo htmlspecialchars($t['category_color'] ?? '#3b82f6'); ?>;">
                        <?php echo htmlspecialchars($t['category_name']); ?>
                    </span>
                </div>
            </div>
            <div style="grid-column: 1 / -1;">
                <div style="color: var(--gray-500); font-size: 13px; margin-bottom: 5px;">Description</div>
                <div style="font-weight: 500; color: var(--gray-800);">
                    <?php echo htmlspecialchars($t['item_description']); ?>
                </div>
            </div>
            <?php if ($t['brand']): ?>
            <div>
                <div style="color: var(--gray-500); font-size: 13px; margin-bottom: 5px;">Brand</div>
                <div style="color: var(--gray-800);"><?php echo htmlspecialchars($t['brand']); ?></div>
            </div>
            <?php endif; ?>
            <?php if ($t['model']): ?>
            <div>
                <div style="color: var(--gray-500); font-size: 13px; margin-bottom: 5px;">Model</div>
                <div style="color: var(--gray-800);"><?php echo htmlspecialchars($t['model']); ?></div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Transaction Details -->
    <div style="margin-bottom: 25px;">
        <h3 style="color: var(--gray-800); margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid var(--gray-200);">
            <i class="fas fa-info-circle"></i> Transaction Details
        </h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
            <div>
                <div style="color: var(--gray-500); font-size: 13px; margin-bottom: 5px;">Quantity</div>
                <div style="font-size: 24px; font-weight: 700; color: <?php echo in_array($t['transaction_type'], ['distributed', 'damaged', 'expired']) ? 'var(--danger)' : 'var(--success)'; ?>">
                    <?php echo in_array($t['transaction_type'], ['distributed', 'damaged', 'expired']) ? '-' : '+'; ?>
                    <?php echo number_format($t['quantity']); ?> <?php echo htmlspecialchars($t['unit']); ?>
                </div>
            </div>
            <div>
                <div style="color: var(--gray-500); font-size: 13px; margin-bottom: 5px;">Unit Cost</div>
                <div style="font-size: 20px; font-weight: 600; color: var(--gray-800);">
                    ₱<?php echo number_format($t['unit_cost'], 2); ?>
                </div>
            </div>
            <div style="grid-column: 1 / -1; background: var(--light-blue); padding: 15px; border-radius: 6px;">
                <div style="color: var(--primary-blue); font-size: 13px; margin-bottom: 5px; font-weight: 600;">Total Cost</div>
                <div style="font-size: 28px; font-weight: 700; color: var(--primary-blue);">
                    ₱<?php echo number_format($t['total_cost'], 2); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Supplier/Recipient Information -->
    <?php if ($t['transaction_type'] === 'received' && $t['supplier_name']): ?>
    <div style="margin-bottom: 25px;">
        <h3 style="color: var(--gray-800); margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid var(--gray-200);">
            <i class="fas fa-truck"></i> Supplier Information
        </h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
            <div>
                <div style="color: var(--gray-500); font-size: 13px; margin-bottom: 5px;">Supplier Name</div>
                <div style="font-weight: 600; color: var(--gray-800);">
                    <?php echo htmlspecialchars($t['supplier_name']); ?>
                </div>
            </div>
            <?php if ($t['supplier_contact']): ?>
            <div>
                <div style="color: var(--gray-500); font-size: 13px; margin-bottom: 5px;">Contact Person</div>
                <div style="color: var(--gray-800);"><?php echo htmlspecialchars($t['supplier_contact']); ?></div>
            </div>
            <?php endif; ?>
            <?php if ($t['supplier_phone']): ?>
            <div>
                <div style="color: var(--gray-500); font-size: 13px; margin-bottom: 5px;">Phone</div>
                <div style="color: var(--gray-800);"><?php echo htmlspecialchars($t['supplier_phone']); ?></div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php elseif ($t['transaction_type'] === 'distributed' && $t['recipient_name']): ?>
    <div style="margin-bottom: 25px;">
        <h3 style="color: var(--gray-800); margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid var(--gray-200);">
            <i class="fas fa-user"></i> Recipient Information
        </h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
            <div>
                <div style="color: var(--gray-500); font-size: 13px; margin-bottom: 5px;">Recipient Name</div>
                <div style="font-weight: 600; color: var(--gray-800);">
                    <?php echo htmlspecialchars($t['recipient_name']); ?>
                </div>
            </div>
            <?php if ($t['recipient_organization']): ?>
            <div>
                <div style="color: var(--gray-500); font-size: 13px; margin-bottom: 5px;">Organization</div>
                <div style="color: var(--gray-800);"><?php echo htmlspecialchars($t['recipient_organization']); ?></div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Additional Information -->
    <?php if ($t['reference_number'] || $t['batch_number'] || $t['serial_number'] || $t['expiration_date'] || $t['purpose'] || $t['notes']): ?>
    <div style="margin-bottom: 25px;">
        <h3 style="color: var(--gray-800); margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid var(--gray-200);">
            <i class="fas fa-clipboard"></i> Additional Information
        </h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
            <?php if ($t['reference_number']): ?>
            <div>
                <div style="color: var(--gray-500); font-size: 13px; margin-bottom: 5px;">Reference Number</div>
                <div style="color: var(--gray-800);"><?php echo htmlspecialchars($t['reference_number']); ?></div>
            </div>
            <?php endif; ?>
            
            <?php if ($t['batch_number']): ?>
            <div>
                <div style="color: var(--gray-500); font-size: 13px; margin-bottom: 5px;">Batch Number</div>
                <div style="color: var(--gray-800);"><?php echo htmlspecialchars($t['batch_number']); ?></div>
            </div>
            <?php endif; ?>
            
            <?php if ($t['serial_number']): ?>
            <div>
                <div style="color: var(--gray-500); font-size: 13px; margin-bottom: 5px;">Serial Number</div>
                <div style="color: var(--gray-800);"><?php echo htmlspecialchars($t['serial_number']); ?></div>
            </div>
            <?php endif; ?>
            
            <?php if ($t['expiration_date']): ?>
            <div>
                <div style="color: var(--gray-500); font-size: 13px; margin-bottom: 5px;">Expiration Date</div>
                <div style="color: var(--gray-800);"><?php echo date('F d, Y', strtotime($t['expiration_date'])); ?></div>
            </div>
            <?php endif; ?>
            
            <?php if ($t['location_name']): ?>
            <div>
                <div style="color: var(--gray-500); font-size: 13px; margin-bottom: 5px;">Storage Location</div>
                <div style="color: var(--gray-800);"><?php echo htmlspecialchars($t['location_name']); ?></div>
            </div>
            <?php endif; ?>
            
            <?php if ($t['purpose']): ?>
            <div style="grid-column: 1 / -1;">
                <div style="color: var(--gray-500); font-size: 13px; margin-bottom: 5px;">Purpose</div>
                <div style="color: var(--gray-800);"><?php echo nl2br(htmlspecialchars($t['purpose'])); ?></div>
            </div>
            <?php endif; ?>
            
            <?php if ($t['notes']): ?>
            <div style="grid-column: 1 / -1;">
                <div style="color: var(--gray-500); font-size: 13px; margin-bottom: 5px;">Notes</div>
                <div style="color: var(--gray-800);"><?php echo nl2br(htmlspecialchars($t['notes'])); ?></div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Audit Information -->
    <div style="background: var(--gray-50); padding: 15px; border-radius: 6px; border-left: 4px solid var(--secondary-blue);">
        <h4 style="margin: 0 0 10px 0; color: var(--gray-700); font-size: 14px;">
            <i class="fas fa-history"></i> Audit Information
        </h4>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 13px;">
            <div>
                <span style="color: var(--gray-500);">Created By:</span>
                <strong style="color: var(--gray-800); margin-left: 5px;">
                    <?php echo htmlspecialchars($t['created_by_name'] ?? 'System'); ?>
                </strong>
            </div>
            <div>
                <span style="color: var(--gray-500);">Created At:</span>
                <strong style="color: var(--gray-800); margin-left: 5px;">
                    <?php echo date('M d, Y h:i A', strtotime($t['created_at'])); ?>
                </strong>
            </div>
            <?php if ($t['approved_by']): ?>
            <div>
                <span style="color: var(--gray-500);">Approved By:</span>
                <strong style="color: var(--gray-800); margin-left: 5px;">
                    <?php echo htmlspecialchars($t['approved_by_name']); ?>
                </strong>
            </div>
            <div>
                <span style="color: var(--gray-500);">Approved At:</span>
                <strong style="color: var(--gray-800); margin-left: 5px;">
                    <?php echo date('M d, Y h:i A', strtotime($t['approved_at'])); ?>
                </strong>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>