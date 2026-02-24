<?php
require_once 'includes/config.php';
check_login();

header('Content-Type: application/json');

$q = isset($_GET['q']) ? trim($_GET['q']) : '';

if (strlen($q) < 1) {
    echo json_encode([]);
    exit;
}

$like = '%' . $q . '%';

$stmt = $conn->prepare("
    SELECT item_code, item_description, items_on_hand, unit
    FROM inventory_items
    WHERE is_active = 1
      AND (item_code LIKE ? OR item_description LIKE ? OR brand LIKE ?)
    ORDER BY
        CASE WHEN item_code LIKE ? THEN 0
             WHEN item_description LIKE ? THEN 1
             ELSE 2 END,
        item_description ASC
    LIMIT 8
");
$stmt->bind_param('sssss', $like, $like, $like, $like, $like);
$stmt->execute();
$result = $stmt->get_result();

$suggestions = [];
while ($row = $result->fetch_assoc()) {
    $suggestions[] = [
        'item_code'        => $row['item_code'],
        'item_description' => $row['item_description'],
        'items_on_hand'    => (int)$row['items_on_hand'],
        'unit'             => $row['unit'] ?? 'pcs',
    ];
}

$stmt->close();
echo json_encode($suggestions);
