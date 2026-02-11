<?php
require_once 'config.php';
require_once 'audit_helper.php'; // ADD THIS

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];
    
    $sql = "SELECT * FROM users WHERE username = '$username' AND is_active = 1";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            // Login successful
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            
            // LOG SUCCESSFUL LOGIN - ADD THIS
            log_login($conn, $user['id']);
            
            // Update last login time
            $update_sql = "UPDATE users SET last_login = NOW() WHERE id = {$user['id']}";
            $conn->query($update_sql);
            
            header('Location: dashboard.php');
            exit();
        } else {
            // LOG FAILED LOGIN - ADD THIS
            log_failed_login($conn, $username);
            $error = 'Invalid username or password';
        }
    } else {
        // LOG FAILED LOGIN - ADD THIS
        log_failed_login($conn, $username);
        $error = 'Invalid username or password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <!-- <meta name="viewport" content="width=device-width, initial-scale=1.0"> -->
     <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <title>CDRRMO Inventory System - Login</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            background: linear-gradient(135deg, #1e5ba8 0%, #154580 100%);
        }
    </style>
</head>
<body class="index-body">
    <div class="login-container">
        <div class="logo-section">
            <!-- Logo Image -->
            <div class="logo-section-image"></div>
            
            <h1>PASSI CITY CDRRMO</h1>
            <p>Inventory Management System</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autofocus>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%;">Login</button>
        </form>
        
        <div class="footer" style="text-align: center; margin-top: 20px; color: #6c757d; font-size: 12px;">
            <p>Default login: admin / password</p>
        </div>
    </div>
</body>
</html>