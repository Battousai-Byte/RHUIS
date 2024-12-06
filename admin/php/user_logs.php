<?php
session_start();
include '../../includes/db_connection.php'; // Include your database connection

// Query to retrieve user logs with full name, email, role, and timestamp
$query = "
    SELECT  
        ua.email, 
        ua.role, 
        ul.action, 
        ul.timestamp
    FROM 
        user_logs ul
    JOIN 
        user_account ua ON ul.user_account_id = ua.id
    ORDER BY 
        ul.timestamp DESC
";

$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Logs</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/styles.css"> <!-- Ensure this path is correct -->
    <link rel="stylesheet" href="https://unpkg.com/boxicons/css/boxicons.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            background-color: #f7f7f7;
        }

        .navbar {
            background-color: #2C3E50;
            color: white;
            padding: 10px 20px;
            display: flex;
            align-items: center;
            width: calc(100% - 210px); /* Adjust for sidebar width */
            margin-left: 210px; /* Match the width of the sidebar */
            position: fixed;
            top: 0;
            z-index: 1000; /* Ensure the navbar is on top */
            height: 40px;
        }

        .navbar .navbar-title {
            font-size: 20px;
            font-weight: bold;
            color: white;
            position: absolute;
            left: 30.5%; /* Center the title */
            transform: translateX(-50%);
        }

        .main-content {
            margin-left: 210px; /* Space for the fixed sidebar */
            margin-top: 40px; /* Space for the fixed navbar */
            width: calc(100% - 210px); /* Adjust width to fit content area excluding the sidebar */
            padding: 20px;
            background-color: #ecf0f1; /* White background for content area */
            overflow-y: auto; /* Enable vertical scrolling if content overflows */
            height: calc(100vh - 40px); /* Full viewport height minus navbar height */
            box-sizing: border-box; /* Include padding and border in element's total width and height */
        }

        /* Optional: Style the scrollbars */
        .main-content::-webkit-scrollbar {
            width: 8px;
        }

        .main-content::-webkit-scrollbar-thumb {
            background: #ccc; /* Color of the scrollbar thumb */
            border-radius: 4px;
        }

        .main-content::-webkit-scrollbar-track {
            background: #f1f1f1; /* Color of the scrollbar track */
        }
    </style>
</head>
<body>
<div class="sidebar">
        <?php require '../sidebar.php'; ?>
    </div>
    <div class="navbar">
        <?php require '../navbar.php'; ?>
        <div class="navbar-title">User Logs</div>
    </div>
    <div class="main-content">
        <h2>User Logs</h2>
        <table class="table table-striped">
            <thead>
                <tr>
                    
                    <th>Email</th>
                    <th>Role</th>
                    <th>Action</th>
                    <th>Date & Time</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['role']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['action']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['timestamp']) . "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='5' style='text-align:center;'>No logs found.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</body>
</html>

<?php
$conn->close(); // Close the database connection
?>
