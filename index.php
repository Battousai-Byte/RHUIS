<?php

include 'includes/db_connection.php'; // Include your database connection
session_start();

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Prepare and execute query to check user credentials and activation/deactivation status
    $stmt = $conn->prepare("SELECT id, email, password, role, is_activated, is_deactivated FROM user_account WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Check if the account is activated and not deactivated
        if ($user['is_deactivated'] == 1) {
            $error = "Your Account has been deactivated. Contact Admin For Reactivation.";
        } elseif ($user['is_activated'] == 0) {
            $error = "Your Account has not been activated yet.";
        } else {
            // Verify password
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];

                // Insert user log
                $action = 'Logged in';
                $stmt_log = $conn->prepare("INSERT INTO user_logs (user_account_id, action) VALUES (?, ?)");
                $stmt_log->bind_param("is", $user['id'], $action);
                $stmt_log->execute();

                // Redirect based on user role
                if ($user['role'] === 'admin') {
                    header('Location: admin/php/dashboard.php');
                } else if ($user['role'] === 'nurse') {
                    header('Location: user/php/dashboard.php');
                } else {
                    // Handle other roles or redirect to a default page
                    header('Location: login.php');
                }
                exit();
            } else {
                $error = "Invalid password.";
            }
        }
    } else {
        $error = "No user found with this email.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-color: #ECF0F1;
        }

        .login-container {
            width: 80%;
            max-width: 300px;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            text-align: center;
        }

        .login-container img {
            width: 100px;
            height: auto;
            margin-bottom: 20px;
        }

        .login-container h2 {
            margin-bottom: 10px;
            font-size: 18px;
            color: #2C3E50;
        }

        .login-container p {
            margin: 10px 0;
            font-size: 14px;
            color: #7F8C8D;
        }

        .login-container form {
            margin-top: 20px;
        }

        .login-container input[type="email"], 
        .login-container input[type="password"] {
            width: calc(100% - 20px);
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #BDC3C7;
            border-radius: 4px;
            padding-left: 10px;
            font-size: 14px;
            box-sizing: border-box;
        }

        .login-container input[type="email"]::placeholder, 
        .login-container input[type="password"]::placeholder {
            color: #95A5A6;
        }

        .login-container button {
            width: 95%;
            padding: 10px;
            background-color: #3498DB;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }

        .login-container button:hover {
            background-color: #2980B9;
        }

        .error {
            color: red !important;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <img src="images/municipal-logo.png" alt="Logo">
        <h2>Rural Health Unit Inventory Support System</h2>
        <p>Bayan ng Aurora | Lalawigan ng Isabela</p>

        <?php if (isset($error)) { echo "<p class='error'>$error</p>"; } ?>
        <form action="index.php" method="POST">
            <input type="email" id="email" name="email" placeholder="Email" required>
            <input type="password" id="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>