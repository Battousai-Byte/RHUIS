<?php
// Include the database connection file
require '../../includes/db_connection.php';

// Start the session
session_start();
ob_start();

// Redirect to login page if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Initialize variables
$medicines = [];
$allocation_message = '';
$activity_date = date('Y-m-d'); // Default to today's date

// Fetch total stock quantities for unique medicine descriptions that are not expired
$medicineQuery = "
    SELECT m.description, MIN(m.expiration_date) AS nearest_expiry, SUM(m.stock_quantity) AS total_stock, m.unit_measurement
    FROM medicine AS m
    WHERE m.expiration_date >= CURDATE()
    GROUP BY m.description, m.unit_measurement
";
$medicineResult = $conn->query($medicineQuery);
if ($medicineResult->num_rows > 0) {
    $medicines = $medicineResult->fetch_all(MYSQLI_ASSOC);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $activity_type = 'allocation';
    $program_title = trim($_POST['program_title']); // Capture the program title from user input
    $description = $_POST['medicine_id']; // Medicine description from the dropdown
    $quantity = intval($_POST['quantity']);
    $activity_date = $_POST['activity_date'] ?? $activity_date;

    // Validate input
    if (empty($program_title) || empty($description) || $quantity <= 0) {
        $allocation_message = "Please provide valid inputs.";
    } else {
        // Fetch stock information
        $stockQuery = "SELECT * FROM medicine WHERE description = ? AND expiration_date >= CURDATE() ORDER BY expiration_date ASC";
        $stmt = $conn->prepare($stockQuery);
        $stmt->bind_param("s", $description);
        $stmt->execute();
        $stockResult = $stmt->get_result();

        // Calculate total stock
        $totalStock = 0;
        $medicineStocks = [];
        while ($row = $stockResult->fetch_assoc()) {
            $totalStock += $row['stock_quantity'];
            $medicineStocks[] = $row;
        }

        if ($quantity > $totalStock) {
            $allocation_message = "Insufficient stock for this medicine.";
        } else {
            $remainingQuantity = $quantity;

            foreach ($medicineStocks as $stock) {
                if ($remainingQuantity <= 0) break;

                // Determine how much to deduct, ensuring we don't go negative
                $deductQuantity = min($stock['stock_quantity'], $remainingQuantity);
                
                // Only proceed if we have a positive quantity to deduct
                if ($deductQuantity > 0) {
                    // Update stock quantity
                    $updateStockQuery = "UPDATE medicine SET stock_quantity = stock_quantity - ? WHERE medicine_id = ?";
                    $stmt = $conn->prepare($updateStockQuery);
                    $stmt->bind_param("ii", $deductQuantity, $stock['medicine_id']);
                    $stmt->execute();

                    // Insert into medicine_usage_history
                    for ($i = 0; $i < $deductQuantity; $i++) {
                        $insertUsageQuery = "INSERT INTO medicine_usage_history (medicine_id, activity_type, quantity, activity_date) VALUES (?, ?, 1, ?)";
                        $stmt = $conn->prepare($insertUsageQuery);
                        $stmt->bind_param("iss", $stock['medicine_id'], $activity_type, $activity_date);
                        $stmt->execute();
                    }

                    $remainingQuantity -= $deductQuantity; // Reduce the remaining quantity needed
                }
            }

            // Insert into program table
            $insertProgramQuery = "INSERT INTO program (program_title, medicine_id, quantity, activity_date) VALUES (?, ?, ?, ?)";
            foreach ($medicineStocks as $stock) {
                if ($quantity <= 0) break; // No more quantity to allocate

                // Allocate the remaining quantity
                $programQuantity = min($stock['stock_quantity'], $quantity);
                
                // Only proceed if we have a positive quantity to allocate
                if ($programQuantity > 0) {
                    $stmt = $conn->prepare($insertProgramQuery);
                    $stmt->bind_param("ssis", $program_title, $stock['medicine_id'], $programQuantity, $activity_date);
                    $stmt->execute();
                    $quantity -= $programQuantity; // Reduce the quantity to allocate
                }
            }

            $allocation_message = "Medicine Allocated Successfully.";
        }
        $stmt->close();
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medicine Allocation</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="https://unpkg.com/boxicons/css/boxicons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-beta.1/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            display: flex;
        }
        .main-content {
            margin-left: 210px;
            padding: 20px;
            background-color: #ecf0f1;
            min-height: 100vh;
            width: calc(100% - 210px);
        }
        form {
            display: flex;
            flex-direction: column;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group select,
        .form-group input {
            width: 210%;
            padding: 5px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
            box-sizing: border-box;
            margin-top: -20px;
            margin-bottom: -20px;
        }
        .form-group button {
            background-color: #007bff;
            color: #fff;
            padding: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        .form-group button:hover {
            background-color: #0056b3;
        }
        .message {
            color: #d9534f;
            text-align: center;
            margin-bottom: 20px;
        }
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.4);
        }
        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 500px;
            border-radius: 5px;
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
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
<?php require '../sidebar.php'; ?>
<?php require '../navbar.php'; ?>
<!-- Main Content -->
<div class="main-content">
    <h1 class="form-title">Dispense for Program</h1>
    
    <!-- Allocation Form -->
    <div class="form-container">
        <?php if ($allocation_message): ?>
            <p class="message"><?php echo htmlspecialchars($allocation_message); ?></p>
        <?php endif; ?>
        <form method="POST" action="allocation.php">
            <div class="form-group">
                <label for="activity_type">Activity Type:</label>
                <select name="activity_type" id="activity_type" required>
                <option value="program">Program</option>
                    <option value="allocation">Allocation</option>
                    <option value="nurse">Nurse</option>
                    
                </select>
            </div>
            <div class="form-group">
    <label for="program_title">Program Title:</label>
    <input type="text" name="program_title" id="program_title" required class="form-control" placeholder="Enter Program Title">
</div>

            <div class="form-group">
                <label for="medicine_id">Medicine:</label>
                <select name="medicine_id" id="medicine_id" class="select2" required>
                    <option value="">Select Medicine</option>
                    <?php foreach ($medicines as $medicine): ?>
                        <option value="<?php echo htmlspecialchars($medicine['description']); ?>" 
                                data-stock="<?php echo htmlspecialchars($medicine['total_stock']); ?>"
                                data-unit="<?php echo htmlspecialchars($medicine['unit_measurement']); ?>">
                            <?php echo htmlspecialchars($medicine['description']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <p id="stock_info" style="text-align: center; font-weight: bold;"></p>
            <div class="form-group">
                <label for="quantity">Quantity:</label>
                <input type="number" name="quantity" id="quantity" required min="1" />
            </div>
            <div class="form-group">
                <label for="activity_date">Activity Date:</label>
                <input type="date" name="activity_date" id="activity_date" value="<?php echo $activity_date; ?>" />
            </div>
            <div class="form-group">
                <button type="submit">Allocate Medicine</button>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-beta.1/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        $('.select2').select2({
            placeholder: "Select Medicine",
            allowClear: true
        });

        // Display total stock when a medicine is selected
        $('#medicine_id').on('change', function() {
            var selectedOption = $(this).find('option:selected');
            var totalStock = selectedOption.data('stock');
            var unitMeasurement = selectedOption.data('unit');
            var description = selectedOption.val();

            // Update the stock info paragraph
            if (totalStock > 0) {
                $('#stock_info').text(description + " total quantity is " + totalStock + " " + unitMeasurement);
            } else {
                $('#stock_info').text("No stock available for " + description);
            }
        });

        // Listen for changes in the activity_type dropdown
        $('#activity_type').on('change', function() {
            var activityType = $(this).val();

            if (activityType) {
                var redirectUrl = '';

                switch (activityType) {
                    case 'nurse':
                        redirectUrl = '/rhu_inventory_support/user/php/dispense.php';
                        break;
                    case 'program':
                        redirectUrl = '/rhu_inventory_support/user/php/program.php';
                        break;
                    case 'allocation':
                        redirectUrl = '/rhu_inventory_support/user/php/allocation.php';
                        break;
                }

                if (redirectUrl) {
                    // Redirect immediately if activity type is selected
                    window.location.href = redirectUrl;
                }
            }
        });

        // Optional: Show alert if stock is insufficient
        $('#quantity').on('input', function() {
            const selectedOption = $('#medicine_id option:selected');
            const stockQuantity = parseInt(selectedOption.data('stock')) || 0;
            const inputQuantity = parseInt($(this).val()) || 0;

            if (inputQuantity > stockQuantity) {
                alert('Insufficient stock for this medicine.');
            }
        });
    });
</script>
</body>
</html>
