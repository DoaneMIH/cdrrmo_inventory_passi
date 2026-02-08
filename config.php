<?php
// Database Configuration for CDRRMO Inventory System
define('DB_HOST', 'localhost');     //Own localhost only
// define('DB_HOST', '10.39.61.188');      //Access by other devices     
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'cdrrmo_inventory');

// Create database connection
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set charset to utf8
mysqli_set_charset($conn, "utf8");

// Session start
session_start();
?>