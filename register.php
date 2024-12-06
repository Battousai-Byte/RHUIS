<?php
session_start();
include 'includes/db_connection.php'; // Include your database connection

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM user_account WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        // Email does not exist, proceed to register
        $hashed_password = password_hash($password, PASSWORD_DEFAULT); // Hash the password

        $stmt = $conn->prepare("INSERT INTO user_account (email, password, role) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $email, $hashed_password, $role);

        if ($stmt->execute()) {
            $_SESSION['message'] = "Registration successful. You can now log in.";
            header('Location: index.php');
            exit();
        } else {
            $error = "Registration failed. Please try again.";
        }
    } else {
        $error = "Email already exists. Please use a different email.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        /* General Styles */
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-color: #ECF0F1;
        }

        .register-container {
            width: 80%;
            max-width: 350px;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            text-align: center;
        }

        .register-container h2 {
            margin-bottom: 10px;
            font-size: 18px;
            color: #2C3E50;
        }

        .register-container form {
            margin-top: 20px;
        }

        .register-container input[type="email"], 
        .register-container input[type="password"],
        .register-container select {
            width: calc(100% - 20px);
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #BDC3C7;
            border-radius: 4px;
            padding-left: 10px;
            font-size: 14px;
            box-sizing: border-box;
        }

        .register-container input[type="email"]::placeholder, 
        .register-container input[type="password"]::placeholder {
            color: #95A5A6;
        }

        .register-container button {
            width: 100%;
            padding: 10px;
            background-color: #3498DB;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }

        .register-container button:hover {
            background-color: #2980B9;
        }

        .error {
            color: red;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <h2>Register</h2>

        <?php if (isset($error)) { echo "<p class='error'>$error</p>"; } ?>
        <form action="register.php" method="POST">
            <input type="email" id="email" name="email" placeholder="Email" required>
            <input type="password" id="password" name="password" placeholder="Password" required>
            <select name="role" id="role" required>
                <option value="" disabled selected>Select Role</option>
                <option value="admin">Admin</option>
                <option value="nurse">Nurse</option>
            </select>
            <button type="submit">Register</button>
        </form>
    </div>
</body>
</html>
