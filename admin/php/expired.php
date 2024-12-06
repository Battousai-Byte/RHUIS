<?php
// Include the database connection
require '../../includes/db_connection.php';
session_start(); // Start the session

// Fetch expired medicines
$query = "SELECT medicine_id, description, unit_measurement, unit_price, stock_quantity 
          FROM medicine 
          WHERE expiration_date < NOW()";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$expired_medicines = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Close the statement
mysqli_stmt_close($stmt);

if (isset($_POST['dispose']) && !empty($_POST['selected_medicines'])) {
    // Handle the disposal process
    $selected_medicines = $_POST['selected_medicines'];

    foreach ($selected_medicines as $medicine_id) {
        // Step 3: Remove the medicine from the `medicine` table
        $delete_query = "DELETE FROM medicine WHERE medicine_id = ?";
        $delete_stmt = mysqli_prepare($conn, $delete_query);
        mysqli_stmt_bind_param($delete_stmt, "i", $medicine_id);
        mysqli_stmt_execute($delete_stmt);
    }

    // Set session variable for success
    $_SESSION['disposal_success'] = true;

    // Redirect to the same page to avoid form resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Check if disposal was successful
$disposal_success = isset($_SESSION['disposal_success']) ? $_SESSION['disposal_success'] : false;

// Unset the session variable after checking
unset($_SESSION['disposal_success']);

// Close the database connection
mysqli_close($conn);
?>




<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expired Medicines</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="https://unpkg.com/boxicons/css/boxicons.min.css">
    <style>
        body {
            display: flex;
            margin: 0;
            font-family: Arial, sans-serif;
        }

        .content-wrapper {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .main-content {
            margin-left: 210px; /* Adjust margin to account for sidebar width */
            padding: 20px;
        }

        .no-data {
            text-align: center;
            color: #888;
            padding: 20px;
        }

        .back-button-container {
            display: flex;
            justify-content: flex-start;
            margin-bottom: 20px; /* Adds some space between the button and the rest of the content */
        }
        /* Center the modal */
.modal {
    display: flex !important; 
    align-items: center; 
    justify-content: center; 
    height: 100%; /* Ensure it takes full height */
}

        /* Modal Customization */
        .modal-content {
            text-align: center;
            padding: 20px;
            border-radius: 10px; /* Rounded corners for the modal */
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1); 
        }

        .modal-content .bx {
            font-size: 40px;
            color: #d9534f; /* Trash icon color */
        }

        .modal-body h5 {
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <?php require '../sidebar.php'; ?>
    <div class="content-wrapper">
        <?php require '../navbar.php'; ?>
        <div class="main-content">
            <div class="expired-container">
                <h1>Expired Medicines</h1>

                <?php if (!empty($expired_medicines)): ?>
                    <!-- Form to handle the selection and disposal -->
                    <form method="post" action="">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Select</th> <!-- Checkbox column for selecting medicines -->
                                    <th>Description</th>
                                    <th>Unit Measurement</th>
                                    <th>Unit Price</th>
                                    <th>Stock Quantity</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($expired_medicines as $medicine): ?>
                                    <tr class="expired">
                                        <td>
                                            <input type="checkbox" name="selected_medicines[]" value="<?php echo $medicine['medicine_id']; ?>">
                                        </td>
                                        <td><?php echo htmlspecialchars($medicine['description']); ?></td>
                                        <td><?php echo htmlspecialchars($medicine['unit_measurement']); ?></td>
                                        <td><?php echo htmlspecialchars($medicine['unit_price']); ?></td>
                                        <td><?php echo htmlspecialchars($medicine['stock_quantity']); ?></td>
                                        <td>Expired</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <!-- Disposal Button -->
                        <button type="submit" name="dispose" class="btn btn-danger" onclick="return confirm('Are you sure you want to dispose of the selected medicines?')">Send for Disposal</button>
                    </form>
                <?php else: ?>
                    <div class="no-data">No expired medicines found.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

   <!-- Success Modal -->
<?php if ($disposal_success): ?>
    <div class="modal fade show" id="disposalModal" tabindex="-1" role="dialog" aria-labelledby="disposalModalLabel" aria-hidden="true" style="display: block;">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-body">
                    <i class='bx bx-trash'></i>
                    <h5>Medicines Successfully Disposed!</h5>
                </div>
            </div>
        </div>
    </div>
    <script>
        // Automatically close the modal after 3 seconds
        setTimeout(function() {
            $('#disposalModal').modal('hide'); // Use jQuery to hide the modal
            window.location.reload(); // Refresh the page after modal closes
        }, 1000);
    </script>
<?php endif; ?>


    <!-- JavaScript for Dropdown -->
    <script src="../js/script.js"></script>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
