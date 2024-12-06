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

    // Handle formats
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
    } else {
        return NULL;
    }
    
    return $normalized;
}

// Function to get the threshold value based on unit measurement
function getThreshold($conn, $unit_measurement) {
    $sql = "SELECT threshold FROM thresholds WHERE unit_measurement = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $unit_measurement);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row['threshold'] ?? NULL;
}

// Handle manual medicine entry form submission
if (isset($_POST['submit'])) {
    // Extract data from POST request
    $description = $_POST['description'];
    $unit_measurement = $_POST['unit_measurement'];
    $unit_price = $_POST['unit_price'];
    $stock_quantity = $_POST['stock_quantity'];
    $lot_no = $_POST['lot_no'];
    $expiration_date = $_POST['expiration_date'];
    $quantity_per_carton = $_POST['quantity_per_carton'];
    
    // Set initial_stock to the same value as stock_quantity
    $initial_stock = $stock_quantity;

    // Normalize expiration date
    $expiration_date = normalizeExpirationDate($expiration_date);

    // Get the threshold value
    $threshold = getThreshold($conn, $unit_measurement);

    // Calculate the unit_value (unit_price * stock_quantity)
    $unit_value = $unit_price * $stock_quantity;

    // Prepare and execute the SQL statement for manual entry
    $stmt = $conn->prepare("
        INSERT INTO medicine (
            description, unit_measurement, unit_price, initial_stock, stock_quantity, unit_value, lot_no, expiration_date, quantity_per_carton, threshold
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param('ssddddsssd', $description, $unit_measurement, $unit_price, $initial_stock, $stock_quantity, $unit_value, $lot_no, $expiration_date, $quantity_per_carton, $threshold);
    if ($stmt->execute()) {
        $message = "Medicine added successfully!";
    } else {
        $message = "Error adding medicine: " . $stmt->error;
    }
    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medicine Entry</title>
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
      
        .form-container {
    margin: 0 auto;
    padding: 20px;
    /* Remove shadow */
    box-shadow: none;
    background-color: #ecf0f1;
}

        form {
            display: grid;
            grid-template-columns: 1fr 1fr; /* Two columns layout */
            gap: 20px;
        }

        label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }

        input[type="text"], input[type="number"], input[type="date"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px; 
            margin-bottom: 10px;
        }

        button {
            padding: 8px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 0px;
            cursor: pointer;
            height: 40px;
        }

        button:hover {
            background-color: #0056b3;
        }

        .bx {
            margin-right: 5px;
            font-size: 20px
        }

        .message {
            margin-left: 20px;
            font-size: 14px;
            color: #e74c3c;
        }

        .success {
            color: #27ae60;
        }

        .import-btn {
            margin-bottom: 20px;
            display: flex;
            justify-content: flex-end;
          
            
        }
    </style>
</head>
<body>
    <?php require '../sidebar.php'; ?>
    <div class="content-wrapper">
        <?php require '../navbar.php'; ?>
        <div class="main-content">
            <div class="form-container">
                <h1>Medicine Entry</h1>

                <!-- Button to redirect to import CSV page -->
                <div class="import-btn">
                    <a href="import_csv.php"><button type="button" title='Import CSV File'><i class='bx bx-import'></i></button></a>
                </div>

                <!-- Manual Medicine Entry Form -->
                <form method="POST">
                    <div>
                        <label for="description">Description:</label>
                        <input type="text" name="description" id="description" required>
                    </div>

                    <div>
                        <label for="unit_measurement">Unit Measurement:</label>
                        <input type="text" name="unit_measurement" id="unit_measurement" required>
                    </div>

                    <div>
                        <label for="unit_price">Unit Price:</label>
                        <input type="number" name="unit_price" id="unit_price" step="0.01" required>
                    </div>

                    <div>
                        <label for="stock_quantity">Stock Quantity:</label>
                        <input type="number" name="stock_quantity" id="stock_quantity" required>
                    </div>

                    <div>
                        <label for="lot_no">Lot No:</label>
                        <input type="text" name="lot_no" id="lot_no">
                    </div>

                    <div>
                        <label for="expiration_date">Expiration Date:</label>
                        <input type="date" name="expiration_date" id="expiration_date" placeholder="MM/YYYY or other format">
                    </div>

                    <div>
                        <label for="quantity_per_carton">Quantity per Carton:</label>
                        <input type="text" name="quantity_per_carton" id="quantity_per_carton">
                    </div>
                    <div>
                        <label for="threshold">Threshold:</label>
                        <input type="number" name="threshold" id="threshold">
                    </div>
                    <button type="submit" name="submit"><i class='bx bx-plus'></i> Add Medicine</button>

                    <?php if ($message && strpos($message, 'Medicine added successfully!') !== false): ?>
                        <p class="message success"><?php echo htmlspecialchars($message); ?></p>
                    <?php elseif ($message): ?>
                        <p class="message"><?php echo htmlspecialchars($message); ?></p>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
