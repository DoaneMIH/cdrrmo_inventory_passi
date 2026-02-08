<?php
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: dashboard.php');
    exit();
}

$message = '';
$error = '';

// Handle user addition
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_user'])) {
    $username = $conn->real_escape_string($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $full_name = $conn->real_escape_string($_POST['full_name']);
    $role = $conn->real_escape_string($_POST['role']);
    
    $sql = "INSERT INTO users (username, password, full_name, role) VALUES ('$username', '$password', '$full_name', '$role')";
    
    if ($conn->query($sql)) {
        $message = 'User added successfully!';
    } else {
        $error = 'Error adding user: ' . $conn->error;
    }
}

// Handle user deletion
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    if ($delete_id != $_SESSION['user_id']) {
        $sql = "DELETE FROM users WHERE id = $delete_id";
        if ($conn->query($sql)) {
            $message = 'User deleted successfully!';
        }
    } else {
        $error = 'You cannot delete your own account!';
    }
}

// Get all users
$sql = "SELECT * FROM users ORDER BY created_at DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <!-- <meta http-equiv="refresh" content="2"> Refreshes every 02 seconds -->
    <!-- <meta name="viewport" content="width=device-width, initial-scale=1.0"> -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <title>Users - CDRRMO Inventory System</title>
    <link rel="stylesheet" href="style.css">

    <!-- <script>
        // Auto-refresh every 02 seconds
        let timeLeft = 2;
        
        function updateTimer() {
            timeLeft--;
            document.getElementById('refresh-timer').innerHTML = 'Auto-refresh in: <strong>' + timeLeft + 's</strong>';
            
            if (timeLeft <= 0) {
                location.reload();
            }
        }
        
        // Update timer every second
        setInterval(updateTimer, 1000);
    </script> -->
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <h1>User Management</h1>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <!-- Add User Form -->
        <div class="form-container" style="margin-bottom: 30px;">
            <h2>Add New User</h2>
            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label for="username">Username *</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password *</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="full_name">Full Name *</label>
                        <input type="text" id="full_name" name="full_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="role">Role *</label>
                        <select id="role" name="role" required>
                            <option value="staff">Staff</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                
                <button type="submit" name="add_user" class="btn btn-primary">Add User</button>
            </form>
        </div>
        
        <!-- Users Table -->
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Full Name</th>
                        <th>Role</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><?php echo $row['username']; ?></td>
                                <td><?php echo $row['full_name']; ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $row['role'] == 'admin' ? 'danger' : 'info'; ?>">
                                        <?php echo ucfirst($row['role']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                <td class="actions">
                                    <?php if ($row['id'] != $_SESSION['user_id']): ?>
                                        <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this user?')">Delete</a>
                                    <?php else: ?>
                                        <span style="color: #95a5a6;">Current User</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center;">No users found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
function updateUsers() {
    fetch('users.php?ajax=1')
        .then(response => response.text())
        .then(html => {
            const temp = document.createElement('div');
            temp.innerHTML = html;
            
            const newTbody = temp.querySelector('.table-responsive tbody');
            if (newTbody) {
                document.querySelector('.table-responsive tbody').innerHTML = newTbody.innerHTML;
            }
        })
        .catch(error => console.error('Update failed:', error));
}

setInterval(updateUsers, 2000); // 10 seconds for user list
</script>
</body>
</html>