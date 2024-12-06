<?php
// Include database connection file
include('../../includes/db_connection.php');
session_start();

// Redirect to login page if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Handle password change request
if (isset($_POST['change_password'])) {
    $user_id = $_SESSION['user_id'];
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate input
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "All fields are required.";
    } elseif ($new_password !== $confirm_password) {
        $error = "New password and confirm password do not match.";
    } else {
        // Check if current password is correct
        $sql = "SELECT password FROM user_account WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if (!password_verify($current_password, $user['password'])) {
            $error = "Current password is incorrect.";
        } else {
            // Update password
            $new_password_hash = password_hash($new_password, PASSWORD_BCRYPT);
            $update_sql = "UPDATE user_account SET password = ? WHERE id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param('si', $new_password_hash, $user_id);
            
            if ($stmt->execute()) {
                $success = "Password changed successfully.";
            } else {
                $error = "Error changing password: " . $stmt->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/styles.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        .change-password-container {
            max-width: 350px;
            margin: 70px auto 20px; /* Adjusted for spacing from the top bar */
            padding: 20px;
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        

        .change-password-container form {
            display: flex;
            flex-direction: column;
        }

        .change-password-container input[type="password"] {
            margin-bottom: 10px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .change-password-container button {
            padding: 10px;
            background-color: #2C3E50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .change-password-container button:hover {
            background-color: #34495E;
        }

        .error, .success {
            color: red;
            text-align: center;
            margin-bottom: 20px;
        }

        .success {
            color: green;
        }
    </style>
</head>
<body>
<?php require '../sidebar.php' ?>
<?php require '../navbar.php' ?>

<div class="main-content">
        <div class="change-password-container">
            <h2>Change Password</h2>

            <?php if (isset($error)) { echo "<p class='error'>$error</p>"; } ?>
            <?php if (isset($success)) { echo "<p class='success'>$success</p>"; } ?>

            <form action="change_password.php" method="POST">
                <input type="password" name="current_password" placeholder="Current Password" required>
                <input type="password" name="new_password" placeholder="New Password" required>
                <input type="password" name="confirm_password" placeholder="Confirm New Password" required>
                <button type="submit" name="change_password">Change Password</button>
            </form>
        </div>
    </div>
    <script src="../js/script.js"></script>

</body>
</html>
