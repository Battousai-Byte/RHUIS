<?php
// alert.php

// Include the database connection file
require '../../includes/db_connection.php';

// Start the session
session_start();
ob_start(); // Start output buffering

// Redirect to login page if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Initialize the alert message
$alert_message = '';

// Check if there is an alert message to display
if (isset($_GET['message'])) {
    $alert_message = htmlspecialchars($_GET['message']);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alert</title>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .modal {
            display: block;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.4);
            overflow: auto;
        }

        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            text-align: center;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
    </style>
</head>
<body>

<div class="modal">
    <div class="modal-content">
        <span class="close" id="closeModal">&times;</span>
        <p><?php echo $alert_message; ?></p>
    </div>
</div>

<script>
    // Function to close the modal
    function closeModal() {
        window.location.href = 'dispense.php'; // Redirect back to dispense.php after closing the modal
    }

    // Event listener for closing the modal
    document.getElementById('closeModal').addEventListener('click', closeModal);

    // Event listener for closing the modal when clicking outside of it
    window.addEventListener('click', function(event) {
        var modal = document.querySelector('.modal');
        if (event.target === modal) {
            closeModal();
        }
    });
</script>

</body>
</html>
