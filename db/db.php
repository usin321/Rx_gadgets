<?php
// Database credentials
$servername = "localhost";
$username = "root";         // default XAMPP username
$password = "";             // default XAMPP password (empty)
$database = "rx_gadgets_db"; // your actual database name

// Create the connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
}

// Set charset to support special characters
$conn->set_charset("utf8mb4");
?>
