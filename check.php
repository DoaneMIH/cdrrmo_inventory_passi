<?php
session_start();

echo "<h2>Session Debug Information</h2>";
echo "<pre>";
echo "User ID: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NOT SET') . "\n";
echo "Username: " . (isset($_SESSION['username']) ? $_SESSION['username'] : 'NOT SET') . "\n";
echo "Role: " . (isset($_SESSION['role']) ? $_SESSION['role'] : 'NOT SET') . "\n";
echo "Full Name: " . (isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'NOT SET') . "\n";
echo "\n";
echo "Is Admin Check: " . (isset($_SESSION['role']) && $_SESSION['role'] === 'admin' ? 'YES - ADMIN' : 'NO - NOT ADMIN') . "\n";
echo "</pre>";

echo "<h3>All Session Data:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<hr>";
echo "<p><a href='inventory.php'>Go to Inventory</a></p>";
echo "<p><a href='login.php'>Go to Login</a></p>";
?>