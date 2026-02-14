<?php
/**
 * Database Migration Script
 * Run this file ONCE to add plain_password column to users table
 * 
 * HOW TO RUN:
 * 1. Place this file in your project root directory
 * 2. Open in browser: http://localhost/cdrrmo_inventory_system/run_migration.php
 * 3. Delete this file after successful migration for security
 */

require_once 'config.php';

// Security check - only run if logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die('ERROR: Only administrators can run migrations. Please login as admin first.');
}

$migration_name = 'Add plain_password column';
$migration_date = date('Y-m-d H:i:s');

echo "<!DOCTYPE html>
<html>
<head>
    <title>Database Migration</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #1e3a8a;
            margin-top: 0;
        }
        .success {
            background: #d1fae5;
            color: #065f46;
            padding: 15px;
            border-radius: 6px;
            border-left: 4px solid #059669;
            margin: 15px 0;
        }
        .error {
            background: #fee2e2;
            color: #991b1b;
            padding: 15px;
            border-radius: 6px;
            border-left: 4px solid #dc2626;
            margin: 15px 0;
        }
        .info {
            background: #dbeafe;
            color: #1e40af;
            padding: 15px;
            border-radius: 6px;
            border-left: 4px solid #3b82f6;
            margin: 15px 0;
        }
        .step {
            background: #f3f4f6;
            padding: 10px 15px;
            margin: 10px 0;
            border-radius: 4px;
            font-family: monospace;
        }
        .btn {
            display: inline-block;
            background: #1e3a8a;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            margin-top: 20px;
        }
        .btn:hover {
            background: #1e40af;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔧 Database Migration</h1>
        <h2>{$migration_name}</h2>
        <p><strong>Date:</strong> {$migration_date}</p>
        <hr>
";

try {
    // Step 1: Check if column already exists
    echo "<div class='info'><strong>Step 1:</strong> Checking if plain_password column exists...</div>";
    
    $check = $conn->query("SHOW COLUMNS FROM users LIKE 'plain_password'");
    
    if ($check->num_rows > 0) {
        echo "<div class='error'>❌ Column 'plain_password' already exists! Migration already completed.</div>";
        echo "<a href='users.php' class='btn'>Go to User Management</a>";
    } else {
        echo "<div class='success'>✓ Column does not exist. Proceeding with migration...</div>";
        
        // Step 2: Add the column
        echo "<div class='info'><strong>Step 2:</strong> Adding plain_password column...</div>";
        
        $sql1 = "ALTER TABLE users ADD COLUMN plain_password VARCHAR(255) DEFAULT NULL AFTER password";
        
        if ($conn->query($sql1)) {
            echo "<div class='success'>✓ Column added successfully!</div>";
            
            // Step 3: Update existing users
            echo "<div class='info'><strong>Step 3:</strong> Updating existing users...</div>";
            
            $sql2 = "UPDATE users SET plain_password = 'contact_admin' WHERE plain_password IS NULL OR plain_password = ''";
            
            if ($conn->query($sql2)) {
                $affected_rows = $conn->affected_rows;
                echo "<div class='success'>✓ Updated {$affected_rows} existing user(s) with temporary password notice.</div>";
                
                // Log the migration
                log_activity($_SESSION['user_id'], 'database_migration', "Completed migration: {$migration_name}");
                
                // Success summary
                echo "<div style='background: #d1fae5; padding: 20px; border-radius: 8px; margin-top: 30px;'>
                    <h3 style='color: #065f46; margin-top: 0;'>✅ Migration Completed Successfully!</h3>
                    <p style='color: #065f46;'><strong>What was done:</strong></p>
                    <ul style='color: #065f46;'>
                        <li>Added 'plain_password' column to users table</li>
                        <li>Set existing users' passwords to 'contact_admin'</li>
                        <li>New users will have their passwords stored automatically</li>
                    </ul>
                    <p style='color: #065f46;'><strong>Next Steps:</strong></p>
                    <ol style='color: #065f46;'>
                        <li>Go to User Management and reset passwords for existing users</li>
                        <li><strong>DELETE this migration file (run_migration.php) for security!</strong></li>
                    </ol>
                </div>";
                
                echo "<a href='users.php' class='btn'>Go to User Management</a>";
                
            } else {
                throw new Exception("Failed to update existing users: " . $conn->error);
            }
            
        } else {
            throw new Exception("Failed to add column: " . $conn->error);
        }
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ <strong>Migration Failed!</strong><br>" . $e->getMessage() . "</div>";
    echo "<p>Please check your database connection and try again.</p>";
    echo "<a href='dashboard.php' class='btn'>Go to Dashboard</a>";
}

echo "
    </div>
</body>
</html>";

$conn->close();
?>