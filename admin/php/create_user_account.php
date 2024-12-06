<?php
// Start the session
session_start();

// Include your database connection script
include('../../includes/db_connection.php');

// Initialize error variable
$error = "";

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the form data
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);

    // Hash the password for security
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Prepare the SQL statement to insert the new user
    $sql = "INSERT INTO user_account (email, password, role) VALUES ('$email', '$hashed_password', '$role')";

    // Execute the SQL query
    if (mysqli_query($conn, $sql)) {
        // Redirect to the users list page with a success message
        header("Location: users.php?success=User created successfully");
        exit();
    } else {
        // If there was an error, display it
        $error = "Error: " . $sql . "<br>" . mysqli_error($conn);
    }

    // Close the database connection
    mysqli_close($conn);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create User Account</title>
    
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="https://unpkg.com/boxicons/css/boxicons.min.css">
    <style>
        .main-content{
            background-color: #ecf0f1;
        }
        .register-container {
            background-color: #f9f9f9;
            width: 400px;
            margin: 100px auto;
            padding: 30px;
            border: 1px solid #ccc;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .register-container h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        .register-container input,
        .register-container select {
            width: calc(100% - 20px);
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            box-shadow: none;
            outline: none;
            font-size: 16px;
        }

        .register-container select {
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            background-color: #fff;
            background-image: url("data:image/svg+xml;charset=US-ASCII,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 4 5'%3E%3Cpath fill='%23aaa' d='M2 0L0 2h4zM2 5L0 3h4z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 10px;
        }
    
        .register-container button {
            width: 100%;
            padding: 10px;
            background-color: #2C3E50;
            color: white;
            border: none;
            font-size: 16px;
            cursor: pointer;
        }

        .register-container button:hover {
            background-color: #34495E;
        }

        .error {
            color: red;
            text-align: center;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <?php require '../sidebar.php' ?>
    <?php require '../navbar.php' ?>
    <!-- Register Container -->
     <div class="main-content">
    <div class="register-container">
        <h2>Create User Account</h2>

        <?php if (isset($error)) { echo "<p class='error'>$error</p>"; } ?>
        <form action="create_user_account.php" method="POST">
            <input type="email" id="email" name="email" placeholder="Email" required>
            <input type="password" id="password" name="password" placeholder="Password" required>
            <select name="role" id="role" required>
                <option value="" disabled selected>Select Role</option>
                <option value="admin">Admin</option>
                <option value="nurse">Nurse</option>
            </select>
            <button type="submit">Create</button>
        </form>
    </div>
    </div>
    <!-- JavaScript for Dropdown -->
    <script src="../js/script.js"></script>
</body>
</html>
