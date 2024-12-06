<?php
session_start();
include '../../includes/db_connection.php'; // Include your database connection

// Check if session variables are set
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_email']) || !isset($_SESSION['user_role'])) {
    die('Error: Session variables are not set.');
}

// Get user details from session
$user_id = $_SESSION['user_id']; // This will be used as user_account_id
$user_email = $_SESSION['user_email'];
$user_role = $_SESSION['user_role'];

// Prepare statement to check if user_info record exists

// Prepare statement for inserting log entry

    
    // Insert with both user_info_id and user_account_id
    $stmt = $conn->prepare("INSERT INTO user_logs ( user_account_id, action, timestamp) VALUES ( ?, ?, NOW())");
    if (!$stmt) {
        die('Prepare Error: ' . $conn->error);
    }
    $stmt->bind_param("is",  $user_id, $action);


// Define action
$action = 'Logout';

// Execute statement
if (!$stmt->execute()) {
    die('Execution Error: ' . $stmt->error);
}

// Close the statement and connection
$stmt->close();
$conn->close();

// Destroy session and redirect
session_unset();
session_destroy();
header('Location: ../../index.php'); // Redirect to index.php
exit();
?>
