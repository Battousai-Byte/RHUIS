<?php
// Include the database connection
require '../../includes/db_connection.php';
// Include the rule-based functions
require 'rule-based.php';

// Fetch medicines with expiration dates within the next 3 months using the function from rule_based.php
$nearExpiryMedicines = checkNearExpiry($conn);

// Ensure the data is not duplicated and process each entry only once
$processedMedicines = [];

// Calculate days remaining until expiration and assign urgency
foreach ($nearExpiryMedicines as $medicine) {
    $medicineKey = $medicine['description'] . $medicine['expiration_date'];

    // Check if we already processed this medicine
    if (!isset($processedMedicines[$medicineKey])) {
        $expiration_date = new DateTime($medicine['expiration_date']);
        $current_date = new DateTime();
        $interval = $current_date->diff($expiration_date);
        $medicine['days_remaining'] = $interval->days;

        // Add a negative sign if the expiration date has already passed
        if ($expiration_date < $current_date) {
            $medicine['days_remaining'] = -$interval->days;
        }

        // Assign urgency based on days remaining
        if ($medicine['days_remaining'] <= 30) {
            $medicine['urgency'] = 'Urgent'; // Expiring within 1 month
            $medicine['urgency_class'] = 'urgent';
        } elseif ($medicine['days_remaining'] <= 60) {
            $medicine['urgency'] = 'Distribution'; // Expiring within 2 months
            $medicine['urgency_class'] = 'distribution';
        } elseif ($medicine['days_remaining'] <= 90) {
            $medicine['urgency'] = 'Monitor'; // Expiring within 3 months
            $medicine['urgency_class'] = 'monitor';
        }

        // Store the processed medicine in the array
        $processedMedicines[$medicineKey] = $medicine;
    }
}

// Sort the processed medicines by days remaining if needed
usort($processedMedicines, function($a, $b) {
    return $a['days_remaining'] <=> $b['days_remaining'];
});
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Near Expiry Medicines</title>
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

        .urgent {
            background-color: #f8d7da;
        }

        .distribution {
            background-color: #fff3cd;
        }

        .monitor {
            background-color: #d1ecf1;
        }

        .no-data {
            text-align: center;
            color: #888;
            padding: 20px;
        }
    </style>
</head>
<body>
    <?php require '../sidebar.php'; ?>
    <div class="content-wrapper">
        <?php require '../navbar.php'; ?>
        <div class="main-content">
            <div class="near-expiry-container">
                <h1>Medicines Near Expiry</h1>
                <?php if (!empty($processedMedicines)): ?>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Description</th>
                                <th>Unit Measurement</th>
                                <th>Unit Price</th>
                                <th>Total Stock</th>
                                <th>Expiration Date</th>
                                <th>Days Remaining</th>
                            </tr>
                        </thead>
                        <tbody>
    <?php foreach ($processedMedicines as $medicine): ?>
        <tr class="<?php echo isset($medicine['urgency_class']) ? htmlspecialchars($medicine['urgency_class']) : ''; ?>">
            <td><?php echo isset($medicine['description']) ? htmlspecialchars($medicine['description']) : 'No description'; ?></td>
            <td><?php echo isset($medicine['unit_measurement']) ? htmlspecialchars($medicine['unit_measurement']) : 'N/A'; ?></td>
            <td><?php echo isset($medicine['unit_price']) ? htmlspecialchars($medicine['unit_price']) : 'N/A'; ?></td>
            <td><?php echo isset($medicine['total_stock']) ? htmlspecialchars($medicine['total_stock']) : 'N/A'; ?></td>
            <td><?php echo isset($medicine['expiration_date']) ? htmlspecialchars($medicine['expiration_date']) : 'N/A'; ?></td>
            <td><?php echo isset($medicine['days_remaining']) ? htmlspecialchars($medicine['days_remaining']) : 'N/A'; ?></td>
        </tr>
    <?php endforeach; ?>
</tbody>

                    </table>
                <?php else: ?>
                    <div class="no-data">No medicines are near expiry.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- JavaScript for Dropdown -->
    <script src="../js/script.js"></script>
</body>
</html>
