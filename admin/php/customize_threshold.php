<?php
include '../../includes/db_connection.php';

// Initialize the message variable
$message = '';

// Handle form submission to update thresholds
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['threshold']) && is_array($_POST['threshold'])) {
        foreach ($_POST['threshold'] as $unit_measurement => $new_threshold) {
            // Sanitize threshold input
            $new_threshold = filter_var($new_threshold, FILTER_VALIDATE_INT);

            // Update the threshold for each unit measurement
            $sql = "UPDATE thresholds SET threshold = ? WHERE unit_measurement = ?";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("is", $new_threshold, $unit_measurement);
                if (!$stmt->execute()) {
                    // If there is an execution error, log it
                    $message = "Error executing statement: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $message = "Error preparing statement: " . $conn->error;
            }
        }

        if (!$message) {
            $message = "Thresholds updated successfully!";
        }
    } else {
        $message = "No thresholds to update.";
    }
}

// Fetch all unique unit measurements from the medicine table
$unit_measurements = [];
$sql = "SELECT DISTINCT unit_measurement FROM medicine";
$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $unit_measurements[] = $row['unit_measurement'];
    }
} else {
    $message = "Error fetching unit measurements: " . $conn->error;
}

// Check and insert missing unit measurements into the thresholds table
foreach ($unit_measurements as $unit_measurement) {
    // Check if unit_measurement already exists in thresholds table
    $sql_check = "SELECT COUNT(*) AS count FROM thresholds WHERE unit_measurement = ?";
    $stmt_check = $conn->prepare($sql_check);
    if ($stmt_check) {
        $stmt_check->bind_param("s", $unit_measurement);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        $row_check = $result_check->fetch_assoc();
        
        if ($row_check['count'] == 0) {
            // Insert new unit_measurement with a default threshold value
            $sql_insert = "INSERT INTO thresholds (unit_measurement, threshold) VALUES (?, 0)";
            $stmt_insert = $conn->prepare($sql_insert);
            if ($stmt_insert) {
                $stmt_insert->bind_param("s", $unit_measurement);
                if (!$stmt_insert->execute()) {
                    $message = "Error inserting unit measurement: " . $stmt_insert->error;
                }
                $stmt_insert->close();
            } else {
                $message = "Error preparing insert statement: " . $conn->error;
            }
        }
        $stmt_check->close();
    } else {
        $message = "Error preparing check statement: " . $conn->error;
    }
}

// Fetch current thresholds for the unit measurements
$thresholds = [];
foreach ($unit_measurements as $unit_measurement) {
    $sql_threshold = "SELECT threshold FROM thresholds WHERE unit_measurement = ?";
    $stmt_threshold = $conn->prepare($sql_threshold);
    if ($stmt_threshold) {
        $stmt_threshold->bind_param("s", $unit_measurement);
        $stmt_threshold->execute();
        $result_threshold = $stmt_threshold->get_result();
        $row_threshold = $result_threshold->fetch_assoc();
        $thresholds[$unit_measurement] = $row_threshold['threshold'];
        $stmt_threshold->close();
    } else {
        $message = "Error preparing threshold statement: " . $conn->error;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customize Thresholds</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="https://unpkg.com/boxicons/css/boxicons.min.css">
    <style>
        body {
            display: flex;
            margin: 0;
            font-family: Arial, sans-serif;
        }

        .main-content {
            margin-left: 210px; /* Adjust margin to account for sidebar width */
            padding: 20px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .container {
            flex: 1;
        }

        .btn-primary {
            margin-top: 20px;
        }

        .message {
            color: green;
            font-weight: bold;
            margin-top: 20px;
            text-align: center;
        }
        .error {
            color: red;
            font-weight: bold;
            margin-top: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
<?php require '../sidebar.php'; ?>
<?php require '../navbar.php'; ?>
    <div class="main-content">
        <div class="container">
            <h1>Customize Thresholds</h1>
            <?php if ($message): ?>
                <div class="<?php echo strpos($message, 'Error') === false ? 'message' : 'error'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            <form method="POST" action="customize_threshold.php">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Unit Measurement</th>
                            <th>Current Threshold</th>
                            <th>New Threshold</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($unit_measurements as $unit_measurement): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($unit_measurement); ?></td>
                            <td>
                                <?php echo htmlspecialchars(isset($thresholds[$unit_measurement]) ? $thresholds[$unit_measurement] : 'N/A'); ?>
                            </td>
                            <td>
                                <input type="number" name="threshold[<?php echo htmlspecialchars($unit_measurement); ?>]" value="<?php echo htmlspecialchars(isset($thresholds[$unit_measurement]) ? $thresholds[$unit_measurement] : ''); ?>" class="form-control" min="0">
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <button type="submit" class="btn btn-primary">Update Thresholds</button>
            </form>
        </div>
    </div>
</body>
</html>

