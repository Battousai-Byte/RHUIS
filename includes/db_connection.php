<?php
// Database configuration
$servername = "localhost";  // Typically 'localhost'
$username = "root";         // Your MySQL username
$password = "";             // Use an empty string for no password
$dbname = "rhu_aurora";     // The database name you want to connect to

// Create a connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
