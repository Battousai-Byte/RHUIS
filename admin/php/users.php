<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database connection file
include('../../includes/db_connection.php');

// Start session for notifications
session_start();

// Fetch users from the database
$sql = "SELECT ua.email, ua.role, ua.is_activated, ua.is_deactivated, ua.id AS user_account_id
        FROM user_account ua
        WHERE role ='nurse'";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Error fetching users: " . mysqli_error($conn));
}

// Handle activation/deactivation request
if (isset($_POST['toggle_status'])) {
    $user_account_id = $_POST['user_account_id'];

    // Fetch current activation status for the user
    $current_status_sql = "SELECT is_activated FROM user_account WHERE id = ?";
    $stmt = $conn->prepare($current_status_sql);
    $stmt->bind_param('i', $user_account_id);
    $stmt->execute();
    $stmt->bind_result($current_status);
    $stmt->fetch();
    $stmt->close();

    // Toggle the activation status
    $new_status = $current_status == 1 ? 0 : 1; // Toggle between 1 (active) and 0 (inactive)
    $is_deactivated = $new_status == 1 ? 0 : 1; // Set is_deactivated opposite to is_activated

    // Update user activation status
    $update_sql = "UPDATE user_account SET is_activated = ?, is_deactivated = ? WHERE id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param('iii', $new_status, $is_deactivated, $user_account_id);

    if ($stmt->execute()) {
        $_SESSION['message'] = "User status updated successfully.";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error updating user status.";
        $_SESSION['message_type'] = "error";
    }

    // Redirect after processing
    header("Location: users.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>
    
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="https://unpkg.com/boxicons/css/boxicons.min.css">
    
    <style>
        .manage-users-title {
            margin-top: 20px;
            font-size: 28px;
        }
        .custom-control-input:checked ~ .custom-control-label::before {
            background-color: green; /* Green for activated */
            border-color: green;
        }
        .custom-control-input:not(:checked) ~ .custom-control-label::before {
            background-color: red; /* Red for deactivated */
            border-color: red;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .toggle-switch {
            font-size: 16px;
            color: #000;
        }
    </style>
</head>
<body>
<?php require '../sidebar.php'; ?>
<?php require '../navbar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <div class="manage-users-title">Manage Users</div>

        <!-- Display notification message -->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['message_type'] === 'success' ? 'success' : 'danger'; ?>">
                <?php
                echo $_SESSION['message'];
                unset($_SESSION['message']);
                unset($_SESSION['message_type']);
                ?>
            </div>
        <?php endif; ?>

        <div class="users-container">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Activation Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td><?php echo htmlspecialchars($row['role']); ?></td>
                            <td class="action-buttons">
                                <form action="users.php" method="POST">
                                    <input type="hidden" name="user_account_id" value="<?php echo $row['user_account_id']; ?>">
                                    <input type="hidden" name="toggle_status" value="1">
                                    <div class="custom-control custom-switch">
                                        <!-- Switch is styled with green when active and red when inactive -->
                                        <input type="checkbox" class="custom-control-input" id="switch<?php echo $row['user_account_id']; ?>" name="is_activated" onchange="this.form.submit()" <?php echo $row['is_activated'] ? 'checked' : ''; ?>>
                                        <label class="custom-control-label" for="switch<?php echo $row['user_account_id']; ?>">
                                            <?php echo $row['is_activated'] ? 'Activated' : 'Deactivated'; ?>
                                        </label>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- JavaScript for handling the toggle -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
