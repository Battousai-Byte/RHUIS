<?php
require '../../includes/db_connection.php';

session_start();
ob_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$medicines = [];
$dispense_message = '';

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $activity_type = strtolower($_POST['nurse']);
    $description = $_POST['medicine_id'];
    $quantity = intval($_POST['quantity']);
    $activity_date = date('Y-m-d');
    $nurse_name = $_POST['nurse_name']; 

    if (empty($activity_type) || empty($description) || $quantity <= 0) {
        $dispense_message = "Please provide valid inputs.";
    } else {
        // Fetch medicines with the same description and not expired
    // Fetch medicines with the same description and not expired
$stockQuery = "
SELECT medicine_id, SUM(stock_quantity) AS totalStock 
FROM medicine 
WHERE description = ? AND expiration_date >= CURDATE() 
GROUP BY medicine_id
ORDER BY expiration_date ASC
";
$stmt = $conn->prepare($stockQuery);
$stmt->bind_param("s", $description);
$stmt->execute();
$stockResult = $stmt->get_result();

$totalStock = 0;
$medicineStocks = [];

// Fetch all medicine stocks
while ($row = $stockResult->fetch_assoc()) {
$totalStock += $row['totalStock']; // Sum the total stock
$medicineStocks[] = $row; // Store stock information
}


        if ($quantity > $totalStock) {
            $_SESSION['dispense_message'] = "Insufficient stock for this medicine.";
        } else {
            $insertNurseQuery = "
            INSERT INTO nurse (name, activity_type, activity_date) 
            VALUES (?, ?, ?)
        ";
        $stmt = $conn->prepare($insertNurseQuery);
        $stmt->bind_param("sss", $nurse_name, $activity_type, $activity_date);
        $stmt->execute();

            $remainingQuantity = $quantity;
            $deductQuantities = []; // Array to hold medicine_id and quantity pairs

            foreach ($medicineStocks as $stock) {
                if ($remainingQuantity <= 0) {
                    break; // Exit if all required quantity has been dispensed
                }

                // Calculate how much to deduct from the current stock
                $availableStock = $stock['totalStock'];
                $deductQuantity = min($availableStock, $remainingQuantity);

                // Only proceed if there's something to deduct
                if ($deductQuantity > 0) {
                    // Store the medicine_id and quantity to deduct
                    $deductQuantities[] = [
                        'id' => $stock['medicine_id'],
                        'quantity' => $deductQuantity
                    ];
                    $remainingQuantity -= $deductQuantity;
                }
            }

            // Check if we have quantities to update
            if (!empty($deductQuantities)) {
                // Prepare the SQL statement for updating stock quantities
                $updateStockQuery = "
                    UPDATE medicine 
                    SET stock_quantity = stock_quantity - CASE medicine_id ";

                // Add each medicine_id to the query
                foreach ($deductQuantities as $item) {
                    $updateStockQuery .= "WHEN ? THEN ? ";
                }

                $updateStockQuery .= "END 
                    WHERE medicine_id IN (";

                // Create a placeholder for the medicine IDs
                $ids = [];
                foreach ($deductQuantities as $item) {
                    $ids[] = $item['id'];
                    $updateStockQuery .= "?, "; // Placeholder for medicine_id
                }
                
                // Remove the last comma and space
                $updateStockQuery = rtrim($updateStockQuery, ', ') . ")";

                // Prepare the statement
                $stmt = $conn->prepare($updateStockQuery);

                // Bind the parameters
                $bindParams = [];
                foreach ($deductQuantities as $item) {
                    $bindParams[] = $item['id'];
                    $bindParams[] = $item['quantity'];
                }
                
                // Add the medicine IDs to the bind parameters
                $bindParams = array_merge($bindParams, $ids);

                // Bind dynamically
                $stmt->bind_param(str_repeat("ii", count($deductQuantities)) . str_repeat("i", count($ids)), ...$bindParams);
                
                // Execute the update
                $stmt->execute();

                // Insert records into medicine_usage_history for each unit deducted
                foreach ($deductQuantities as $item) {
                    for ($i = 0; $i < $item['quantity']; $i++) {
                        $insertUsageQuery = "
                            INSERT INTO medicine_usage_history (medicine_id, activity_type, quantity, activity_date) 
                            VALUES (?, ?, 1, ?)
                        ";
                        $stmt = $conn->prepare($insertUsageQuery);
                        $stmt->bind_param("iss", $item['id'], $activity_type, $activity_date);
                        $stmt->execute();
                    }
                }
            }

            // Final message based on whether the full quantity was dispensed
            if ($remainingQuantity > 0) {
                $dispense_message = "Only part of the requested quantity could be dispensed. Remaining quantity: $remainingQuantity.";
            } else {
                $dispense_message = "Medicine Dispensed.";
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
    <title>Dispense Medicine</title>
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
          /* Add styles for the modal */
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
        .modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    text-align: center;
}

.modal-content {
    background-color: #fefefe;
    margin: 15% auto;
    padding: 20px;
    border: 1px solid #888;
    width: 50%;
    border-radius: 5px;
}

.close {
    position: absolute;
    top: 10px;
    right: 25px;
    color: #aaa;
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
<?php require '../sidebar.php'; ?>
<?php require '../navbar.php'; ?>
    <div class="main-content">
        <h1 class="form-title">Dispense Medicine</h1>
        
        <div class="form-container">
    <form method="POST" action="">
        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($dispense_message)): ?>
            <p class="message"><?php echo htmlspecialchars($dispense_message); ?></p>
        <?php endif; ?>

        <div class="form-group">
            <label for="activity_type">Activity Type:</label>
            <select name="activity_type" id="activity_type" required>
                <option value="nurse">Nurse</option>
                <option value="allocation">Allocation</option>
                <option value="program">Program</option>
            </select>
        </div>

        <div class="form-group">
            <label for="nurse_name">Nurse Name:</label>
            <input type="text" name="nurse_name" id="nurse_name" required>
        </div>

        <div class="form-group">
            <label for="medicine_description">Medicine:</label>
            <select name="medicine_description" id="medicine_description" class="select2" required>
                <?php foreach ($medicines as $medicine): ?>
                    <option value="<?php echo htmlspecialchars($medicine['description']); ?>" 
                            data-stock="<?php echo htmlspecialchars($medicine['total_stock']); ?>" 
                            data-unit="<?php echo htmlspecialchars($medicine['unit_measurement']); ?>">
                        <?php echo htmlspecialchars($medicine['description']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <p id="stock_`info" style="text-align: center; font-weight: bold;"></p>

        <div class="form-group">
            <label for="quantity">Quantity:</label>
            <input type="number" name="quantity" id="quantity" min="1" required>
        </div>

        <div class="form-group">
            <button type="submit">Submit</button>
        </div>
    </form>
</div>

    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-beta.1/dist/js/select2.min.js"></script>
    <script>
$(document).ready(function() {
    $('.select2').select2({
        placeholder: "Select Medicine",
        allowClear: true
    });

    $('#medicine_description').on('change', function() {
        var selectedOption = $(this).find('option:selected');
        var totalStock = selectedOption.data('stock');
        var unitMeasurement = selectedOption.data('unit');
        var description = selectedOption.val();

        if (totalStock > 0) {
            $('#stock_info').text(description + " total quantity is " + totalStock + " " + unitMeasurement);
        } else {
            $('#stock_info').text("No stock available for " + description);
        }
    });

    $('#quantity').on('input', function() {
        const selectedOption = $('#medicine_description option:selected');
        const stockQuantity = parseInt(selectedOption.data('stock')) || 0;
        const inputQuantity = parseInt($(this).val()) || 0;

        if (inputQuantity > stockQuantity) {
            alert('Insufficient stock for this medicine.');
        }
    });

    // Display the modal on form submit
    $('form').on('submit', function(event) {
        event.preventDefault();  // Prevent the form from being submitted immediately

        // Show the loading modal
        $('#loadingModal').show();
        $('#modalMessage').text('Dispensing...');

        // Simulate a delay for demo purposes
        setTimeout(function() {
            // After dispensing process completes, change the modal text
            $('#modalMessage').text('Dispensed!');

            // After 2 seconds, hide the modal and submit the form
            setTimeout(function() {
                $('#loadingModal').hide();
                $('form').unbind('submit').submit();  // Re-enable form submission and submit the form
            }, 2000);  // 2 seconds delay to simulate processing
        }, 3000);  // Simulate a delay (e.g., server processing)
    });

    // Close the modal if user clicks on the close button
    $('.close').on('click', function() {
        $('#loadingModal').hide();
    });
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
});

    </script>
</body>
</html>
