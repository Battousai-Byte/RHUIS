<?php
// Include the database connection file
require '../../includes/db_connection.php';

// Initialize the message variable
$message = '';

// Function to normalize expiration date
function normalizeExpirationDate($date) {
    if (empty($date) || strtolower($date) === 'null') {
        return NULL;
    }

    $date = trim($date);
    
    if (preg_match('/^\d{2}-\d{4}$/', $date)) {
        $normalized = date('Y-m-d', strtotime('01-' . $date));
    } elseif (preg_match('/^\d{2}\/\d{4}$/', $date)) {
        $normalized = date('Y-m-d', strtotime('01/' . $date));
    } elseif (preg_match('/^\d{2}\/\d{2}$/', $date)) {
        $normalized = date('Y-m-d', strtotime('01/' . $date));
    } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        $normalized = date('Y-m-d', strtotime($date));
    } elseif (preg_match('/^\d{4}-\d{2}$/', $date)) {
        $normalized = date('Y-m-d', strtotime($date . '-01'));
    } elseif (preg_match('/^\d{4}\/\d{2}$/', $date)) {
        $normalized = date('Y-m-d', strtotime($date . '/01'));
    } elseif (preg_match('/^\d{4}$/', $date)) {
        $normalized = date('Y-m-d', strtotime($date . '-01-01'));
    } elseif (preg_match('/^\d{1,2}\/\d{1,2}\/\d{2,4}$/', $date)) {
        $normalized = date('Y-m-d', strtotime($date));
    } elseif (preg_match('/^\d{1,2}-\d{4}$/', $date)) {
        $normalized = date('Y-m-d', strtotime('01-' . $date));
    } else {
        return NULL;
    }
    
    return $normalized;
}

// Handle CSV file upload
if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == 0) {
    $file = $_FILES['csv_file']['tmp_name'];
    $handle = fopen($file, 'r');
    fgetcsv($handle);
    // Prepare the SQL statement for inserting data into the database
    $stmt = $conn->prepare("INSERT INTO medicine (
        description, unit_measurement, unit_price, initial_stock, stock_quantity, unit_value, lot_no, expiration_date, quantity_per_carton, threshold
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    // Iterate over each row in the CSV file
    while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
        if (empty(array_filter($data))) {
            continue; // Skip empty rows
        }

        $description = $data[0];
        $unit_measurement = $data[1];
        $unit_price = $data[2];
        $stock_quantity = $data[3];
        $initial_stock = $stock_quantity;
        $unit_value = $data[4];
        $lot_no = $data[5];
        $expiration_date = normalizeExpirationDate($data[6]);
        $quantity_per_carton = $data[7];

        // Fetch the threshold ID from the thresholds table based on the unit measurement
        $threshold_stmt = $conn->prepare("SELECT threshold_id FROM thresholds WHERE unit_measurement = ?");
        $threshold_stmt->bind_param("s", $unit_measurement);
        $threshold_stmt->execute();
        $threshold_result = $threshold_stmt->get_result();
        $threshold_row = $threshold_result->fetch_assoc();
        $threshold_id = $threshold_row ? $threshold_row['threshold_id'] : NULL;
        $threshold_stmt->close();

        // Bind the parameters to the SQL statement
        $stmt->bind_param('ssssssssss', $description, $unit_measurement, $unit_price, $initial_stock, $stock_quantity, $unit_value, $lot_no, $expiration_date, $quantity_per_carton, $threshold_id);
        $stmt->execute();
    }

    // Close file and statement
    fclose($handle);
    $stmt->close();
    $message = "CSV data imported successfully!";
} else {
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] != 0) {
        $message = "Error uploading file.";
    }
}

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import CSV</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/styles.css">
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/boxicons/css/boxicons.min.css">
    <style>
        /* General styling for main content */
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

        h1 {
            font-size: 24px;
            margin-bottom: 20px;
            text-align: center;
        }

        .manual-button-container {
            margin-bottom: 20px;
        }

        .manual-button {
            display: inline-block;
            padding: 10px 20px;
            background-color: blue;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        .manual-button:hover {
            background-color: darkblue;
        }

        form {
            margin-top: 20px;
        }

        label {
            display: block;
            margin-bottom: 10px;
            font-weight: bold;
        }

        input[type="file"] {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            width: 50%;
            margin-bottom: 20px;
        }

        button[type="submit"] {
            padding: 10px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            justify-content: center;
            height: 40px;
        }

        button[type="submit"]:hover {
            background-color: #218838;
        }

        .message {
            display: block;
            margin-top: 20px;
            color: green;
            font-weight: bold;
        }

        .back-button-container {
            display: flex;
            justify-content: flex-start;
            margin-bottom: 20px;
        }

        .back-button {
            padding: 10px 20px;
            background-color: blue;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 14px;
            transition: background-color 0.3s ease;
        }

        .back-button:hover {
            background-color: darkblue;
        }
        .modal {
    z-index: 1050; /* Ensure the modal is above the overlay */
}

    </style>
</head>
<body>
    <?php require '../sidebar.php'; ?>
    <div class="content-wrapper">
        <?php require '../navbar.php'; ?>
        <div class="main-content">
            <h1>Import CSV</h1>
            <div class="manual-button-container">
                <a href="medicine_entry.php" class="manual-button">Manual Entry</a>
            </div>
            <form method="POST" enctype="multipart/form-data" id="csvForm">
                <label for="csv_file">Select CSV File:</label>
                <input type="file" name="csv_file" id="csv_file">
                <button type="submit" name="import">Import CSV</button>
                <?php if ($message): ?>
                    <span class="message"><?php echo htmlspecialchars($message); ?></span>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Modal for Importing -->
    <div class="modal fade" id="importingModal" tabindex="-1" role="dialog" aria-labelledby="importingModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="importingModalLabel">Importing...</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="text-center">
                        <i class="boxicon-import" style="font-size: 48px;"></i>
                        <p>Your CSV file is being imported. Please wait...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Success -->
    <div class="modal fade" id="successModal" tabindex="-1" role="dialog" aria-labelledby="successModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="successModalLabel">Import Successful!</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p><?php echo htmlspecialchars($message); ?></p>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function () {
            // Show importing modal on form submission
            $('#csvForm').on('submit', function () {
                $('#importingModal').modal('show');
            });

            // Show success modal if message is set
            <?php if ($message): ?>
                $('#importingModal').on('hidden.bs.modal', function () {
                    $('#successModal').modal('show');
                });
            <?php endif; ?>
        });
    </script>
</body>
</html>
