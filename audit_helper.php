<?php
/**
 * CDRRMO Inventory System - Audit Logging Helper
 * 
 * This file contains all audit logging functions.
 * Include this file in any page that needs audit logging:
 * require_once 'audit_helper.php';
 */

/**
 * Main audit logging function
 * Logs any action to the audit_log table
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User performing the action
 * @param string $action Type of action (create, update, delete, login, logout)
 * @param string $table_name Affected table name (optional)
 * @param int $record_id Affected record ID (optional)
 * @param array $old_values Old values before change (optional)
 * @param array $new_values New values after change (optional)
 * @return bool Success status
 */
function log_audit($conn, $user_id, $action, $table_name = null, $record_id = null, $old_values = null, $new_values = null) {
    // Get IP address
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    
    // Convert IPv6 localhost to IPv4 for consistency
    if ($ip_address == '::1') {
        $ip_address = '127.0.0.1';
    }
    
    // Get user agent (browser info)
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';
    
    // Convert arrays to JSON format
    $old_json = $old_values ? json_encode($old_values, JSON_UNESCAPED_UNICODE) : null;
    $new_json = $new_values ? json_encode($new_values, JSON_UNESCAPED_UNICODE) : null;
    
    // Prepare values for SQL (escape to prevent SQL injection)
    $user_id = intval($user_id);
    $action = $conn->real_escape_string($action);
    $table_name = $table_name ? "'" . $conn->real_escape_string($table_name) . "'" : "NULL";
    $record_id = $record_id ? intval($record_id) : "NULL";
    $old_json = $old_json ? "'" . $conn->real_escape_string($old_json) . "'" : "NULL";
    $new_json = $new_json ? "'" . $conn->real_escape_string($new_json) . "'" : "NULL";
    $ip_address = $conn->real_escape_string($ip_address);
    $user_agent = $conn->real_escape_string($user_agent);
    
    // Build and execute SQL
    $sql = "INSERT INTO audit_log 
            (user_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent)
            VALUES 
            ($user_id, '$action', $table_name, $record_id, $old_json, $new_json, '$ip_address', '$user_agent')";
    
    $result = $conn->query($sql);
    
    // Optional: Log errors for debugging
    if (!$result) {
        error_log("Audit log error: " . $conn->error);
    }
    
    return $result;
}

/**
 * Log user login
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User ID who logged in
 * @return bool Success status
 */
function log_login($conn, $user_id) {
    return log_audit($conn, $user_id, 'login', 'users', $user_id, null, [
        'login_time' => date('Y-m-d H:i:s'),
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN'
    ]);
}

/**
 * Log user logout
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User ID who logged out
 * @return bool Success status
 */
function log_logout($conn, $user_id) {
    return log_audit($conn, $user_id, 'logout', 'users', $user_id, null, [
        'logout_time' => date('Y-m-d H:i:s')
    ]);
}

/**
 * Log failed login attempt
 * 
 * @param mysqli $conn Database connection
 * @param string $username Username that failed
 * @return bool Success status
 */
function log_failed_login($conn, $username) {
    return log_audit($conn, 0, 'login_failed', 'users', null, null, [
        'username' => $username,
        'attempt_time' => date('Y-m-d H:i:s'),
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN'
    ]);
}

/**
 * Log inventory item creation
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User who created the item
 * @param int $item_id Newly created item ID
 * @param array $item_data Item data (category_id, description, etc.)
 * @return bool Success status
 */
function log_item_create($conn, $user_id, $item_id, $item_data) {
    return log_audit($conn, $user_id, 'create', 'inventory_items', $item_id, null, $item_data);
}

/**
 * Log inventory item update
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User who updated the item
 * @param int $item_id Updated item ID
 * @param array $old_data Old item data before update
 * @param array $new_data New item data after update
 * @return bool Success status
 */
function log_item_update($conn, $user_id, $item_id, $old_data, $new_data) {
    // Calculate what actually changed
    $changes = array();
    foreach ($new_data as $key => $new_value) {
        if (isset($old_data[$key]) && $old_data[$key] != $new_value) {
            $changes[$key] = [
                'old' => $old_data[$key],
                'new' => $new_value
            ];
        }
    }
    
    return log_audit($conn, $user_id, 'update', 'inventory_items', $item_id, $old_data, $new_data);
}

/**
 * Log inventory item deletion
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User who deleted the item
 * @param int $item_id Deleted item ID
 * @param array $item_data Item data that was deleted
 * @return bool Success status
 */
function log_item_delete($conn, $user_id, $item_id, $item_data) {
    return log_audit($conn, $user_id, 'delete', 'inventory_items', $item_id, $item_data, null);
}

/**
 * Log transaction creation (received, distributed, etc.)
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User who created the transaction
 * @param int $transaction_id New transaction ID
 * @param array $transaction_data Transaction data
 * @return bool Success status
 */
function log_transaction_create($conn, $user_id, $transaction_id, $transaction_data) {
    return log_audit($conn, $user_id, 'create', 'transactions', $transaction_id, null, $transaction_data);
}

/**
 * Log transaction update
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User who updated the transaction
 * @param int $transaction_id Transaction ID
 * @param array $old_data Old transaction data
 * @param array $new_data New transaction data
 * @return bool Success status
 */
function log_transaction_update($conn, $user_id, $transaction_id, $old_data, $new_data) {
    return log_audit($conn, $user_id, 'update', 'transactions', $transaction_id, $old_data, $new_data);
}

/**
 * Log transaction deletion
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User who deleted the transaction
 * @param int $transaction_id Transaction ID
 * @param array $transaction_data Transaction data that was deleted
 * @return bool Success status
 */
function log_transaction_delete($conn, $user_id, $transaction_id, $transaction_data) {
    return log_audit($conn, $user_id, 'delete', 'transactions', $transaction_id, $transaction_data, null);
}

/**
 * Log category creation
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User who created the category
 * @param int $category_id New category ID
 * @param array $category_data Category data
 * @return bool Success status
 */
function log_category_create($conn, $user_id, $category_id, $category_data) {
    return log_audit($conn, $user_id, 'create', 'categories', $category_id, null, $category_data);
}

/**
 * Log category update
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User who updated the category
 * @param int $category_id Category ID
 * @param array $old_data Old category data
 * @param array $new_data New category data
 * @return bool Success status
 */
function log_category_update($conn, $user_id, $category_id, $old_data, $new_data) {
    return log_audit($conn, $user_id, 'update', 'categories', $category_id, $old_data, $new_data);
}

/**
 * Log category deletion
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User who deleted the category
 * @param int $category_id Category ID
 * @param array $category_data Category data that was deleted
 * @return bool Success status
 */
function log_category_delete($conn, $user_id, $category_id, $category_data) {
    return log_audit($conn, $user_id, 'delete', 'categories', $category_id, $category_data, null);
}

/**
 * Log user creation (when admin creates new user)
 * 
 * @param mysqli $conn Database connection
 * @param int $admin_id Admin who created the user
 * @param int $new_user_id New user ID
 * @param array $user_data User data (DO NOT include password!)
 * @return bool Success status
 */
function log_user_create($conn, $admin_id, $new_user_id, $user_data) {
    // Remove password from audit log for security
    unset($user_data['password']);
    
    return log_audit($conn, $admin_id, 'create', 'users', $new_user_id, null, $user_data);
}

/**
 * Log user update
 * 
 * @param mysqli $conn Database connection
 * @param int $admin_id Admin who updated the user
 * @param int $user_id User ID being updated
 * @param array $old_data Old user data
 * @param array $new_data New user data
 * @return bool Success status
 */
function log_user_update($conn, $admin_id, $user_id, $old_data, $new_data) {
    // Remove passwords from audit log
    unset($old_data['password']);
    unset($new_data['password']);
    
    return log_audit($conn, $admin_id, 'update', 'users', $user_id, $old_data, $new_data);
}

/**
 * Log user deletion/deactivation
 * 
 * @param mysqli $conn Database connection
 * @param int $admin_id Admin who deleted the user
 * @param int $user_id User ID being deleted
 * @param array $user_data User data that was deleted
 * @return bool Success status
 */
function log_user_delete($conn, $admin_id, $user_id, $user_data) {
    // Remove password from audit log
    unset($user_data['password']);
    
    return log_audit($conn, $admin_id, 'delete', 'users', $user_id, $user_data, null);
}

/**
 * Helper function: Get current inventory item data
 * Useful for getting old_data before updates
 * 
 * @param mysqli $conn Database connection
 * @param int $item_id Item ID
 * @return array|null Item data or null if not found
 */
function get_item_data($conn, $item_id) {
    $item_id = intval($item_id);
    $sql = "SELECT * FROM inventory_items WHERE id = $item_id";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

/**
 * Helper function: Get current transaction data
 * 
 * @param mysqli $conn Database connection
 * @param int $transaction_id Transaction ID
 * @return array|null Transaction data or null if not found
 */
function get_transaction_data($conn, $transaction_id) {
    $transaction_id = intval($transaction_id);
    $sql = "SELECT * FROM transactions WHERE id = $transaction_id";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

/**
 * Helper function: Get current category data
 * 
 * @param mysqli $conn Database connection
 * @param int $category_id Category ID
 * @return array|null Category data or null if not found
 */
function get_category_data($conn, $category_id) {
    $category_id = intval($category_id);
    $sql = "SELECT * FROM categories WHERE id = $category_id";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

/**
 * Helper function: Get current user data
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User ID
 * @return array|null User data (without password) or null if not found
 */
function get_user_data($conn, $user_id) {
    $user_id = intval($user_id);
    $sql = "SELECT id, username, full_name, email, phone, role, is_active, created_at FROM users WHERE id = $user_id";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

/**
 * Log general system action
 * For actions that don't fit other categories
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User performing the action
 * @param string $action_name Custom action name
 * @param array $action_data Data related to the action
 * @return bool Success status
 */
function log_system_action($conn, $user_id, $action_name, $action_data = null) {
    return log_audit($conn, $user_id, $action_name, null, null, null, $action_data);
}

/**
 * Get recent audit logs for a specific user
 * Useful for showing "Your Recent Activity"
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User ID
 * @param int $limit Number of records to return
 * @return array Array of audit log records
 */
function get_user_audit_logs($conn, $user_id, $limit = 10) {
    $user_id = intval($user_id);
    $limit = intval($limit);
    
    $sql = "SELECT * FROM audit_log 
            WHERE user_id = $user_id 
            ORDER BY created_at DESC 
            LIMIT $limit";
    
    $result = $conn->query($sql);
    $logs = array();
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $logs[] = $row;
        }
    }
    
    return $logs;
}

/**
 * Get audit logs for a specific item
 * Shows complete history of an item
 * 
 * @param mysqli $conn Database connection
 * @param int $item_id Item ID
 * @return array Array of audit log records
 */
function get_item_audit_history($conn, $item_id) {
    $item_id = intval($item_id);
    
    $sql = "SELECT a.*, u.full_name, u.username 
            FROM audit_log a
            LEFT JOIN users u ON a.user_id = u.id
            WHERE a.table_name = 'inventory_items' 
            AND a.record_id = $item_id 
            ORDER BY a.created_at DESC";
    
    $result = $conn->query($sql);
    $logs = array();
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $logs[] = $row;
        }
    }
    
    return $logs;
}

?>