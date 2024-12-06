<?php
// Include the database connection
require '../../includes/db_connection.php';

// Final query to calculate total stock including thresholds
$lowStockQuery = "
    SELECT m.description, m.unit_measurement, t.threshold, SUM(m.stock_quantity) AS total_stock
    FROM medicine AS m
    LEFT JOIN thresholds AS t ON m.unit_measurement = t.unit_measurement
    GROUP BY m.description, m.unit_measurement, t.threshold
";

// Execute query
$result = mysqli_query($conn, $lowStockQuery);

// Check if there was an error with the query
if (!$result) {
    echo "Error: " . mysqli_error($conn);
    exit; // Exit if there's an error
}

// Fetch the results and filter them based on thresholds
$lowStockMedicines = [];
if ($result) {
    $medicines = mysqli_fetch_all($result, MYSQLI_ASSOC);

    foreach ($medicines as $medicine) {
        $totalStock = $medicine['total_stock'];
        $threshold = $medicine['threshold']; // Get threshold from database

        // Check if the total stock is less than or equal to the threshold
        if ($totalStock <= $threshold) {
            $lowStockMedicines[] = $medicine;
        }
    }
}

// Initialize the date ranges for near-expiry logic
$currentDate = date('Y-m-d'); // Current date
$threeMonthThreshold = date('Y-m-d', strtotime('+3 months')); // 3 months later

// Fetch medicines with expiration dates within the next 3 months
$nearExpiryQuery = "
    SELECT description, expiration_date, unit_measurement, unit_price, SUM(stock_quantity) AS total_stock
    FROM medicine 
    WHERE expiration_date > ? AND expiration_date <= ?
    GROUP BY description, expiration_date, unit_measurement, unit_price
";

$stmt = $conn->prepare($nearExpiryQuery);
$stmt->bind_param("ss", $currentDate, $threeMonthThreshold);
$stmt->execute();
$nearExpiryResult = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Close the statement
$stmt->close();

// Process each entry for near-expiry medicines
$nearExpiryMedicines = [];
foreach ($nearExpiryResult as $medicine) {
    $expiration_date = new DateTime($medicine['expiration_date']);
    $current_date = new DateTime();
    $interval = $current_date->diff($expiration_date);
    
    $daysRemaining = $interval->days * ($expiration_date < $current_date ? -1 : 1);
    $urgency = 'Normal'; // Default value
    $urgencyClass = 'normal'; // Default CSS class

    // Assign urgency based on days remaining
    if ($daysRemaining <= 30) {
        $urgency = 'Urgent';
        $urgencyClass = 'urgent';
    } elseif ($daysRemaining <= 60) {
        $urgency = 'Distribution';
        $urgencyClass = 'distribution';
    } elseif ($daysRemaining <= 90) {
        $urgency = 'Monitor';
        $urgencyClass = 'monitor';
    }

    // Store the processed medicine in the array
    $nearExpiryMedicines[] = [
        'description' => htmlspecialchars($medicine['description']),
        'unit_measurement' => htmlspecialchars($medicine['unit_measurement']),
        'days_remaining' => $daysRemaining,
        'urgency' => $urgency,
        'urgency_class' => $urgencyClass
    ];
}

// Function to generate alerts based on stock levels
function generateAlert($description, $total_stock, $unit_measurement, $threshold) {
    $unit = strtolower($unit_measurement);

    if ($total_stock == 0) {
        return ['CRITICAL ALERT', "CRITICAL ALERT! No stock available for $description ($unit). Immediate restock is advised!"];
    } elseif ($total_stock <= $threshold) {
        return ['RESTOCK ALERT', "RESTOCK ALERT! Stock for $description ($unit) is within the critical threshold of $threshold ($unit). Restock is advised."];
    } else {
        return ['STOCK OK', "STOCK OK: Stock for $description ($unit) is sufficient."];
    }
}

// Arrays to hold categorized alerts
$criticalAlerts = [];
$restockAlerts = [];
$stockOkAlerts = [];

// Categorize the low stock alerts based on their type
foreach ($lowStockMedicines as $medicine) {
    $alertData = generateAlert(
        htmlspecialchars($medicine['description']),
        intval($medicine['total_stock']),
        htmlspecialchars($medicine['unit_measurement']),
        intval($medicine['threshold']) // Pass the threshold from the database
    );

    if ($alertData[0] == 'CRITICAL ALERT') {
        $criticalAlerts[] = $alertData[1];
    } elseif ($alertData[0] == 'RESTOCK ALERT') {
        $restockAlerts[] = $alertData[1];
    } elseif ($alertData[0] == 'STOCK OK') {
        $stockOkAlerts[] = $alertData[1];
    }
}

// Filter the near expiry medicines to include only those with 90 days or fewer remaining
$filteredMedicines = array_filter($nearExpiryMedicines, function($medicine) {
    return $medicine['days_remaining'] <= 90;
});

// Sort the filtered medicines by days remaining
usort($filteredMedicines, function($a, $b) {
    return $a['days_remaining'] <=> $b['days_remaining'];
});
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medicine Alerts</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="https://unpkg.com/boxicons/css/boxicons.min.css">
    <style>
        .alerts-container {
            margin-top: 20px;
            background-color: #f2f2f2; /* Light gray background */
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        /* Alert sections */
        .alert-section {
            background-color: #a9a9a9;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        h2 {
            font-size: 22px;
            color: #34495E;
            border-bottom: 2px solid #f1f1f1;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        /* Alert types styling */
        .critical-alert li {
            background-color: #f8d7da; /* Critical Red */
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 10px;
            list-style-type: none;
        }

        .restock-alert li {
            background-color: #fff3cd; /* Restock Yellow */
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 10px;
            list-style-type: none;
        }

        .stock-ok-alert li {
            background-color: rgba(46, 204, 113, 0.8); /* OK Green */
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 10px;
            list-style-type: none;
        }

        .near-expiry-alert li {
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 10px;
            list-style-type: none;
        }

        .near-expiry-alert .urgent {
            background-color: #f8d7da; /* Urgent Red */
        }

        .near-expiry-alert .distribution {
            background-color: #fff3cd; /* Distribution Yellow */
        }

        .near-expiry-alert .monitor {
            background-color: #d1ecf1; /* Monitor Blue */
        }

        /* Optional: responsive tweaks */
        @media only screen and (max-width: 768px) {
            .main-content {
                padding: 10px;
            }

            h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
<?php require '../sidebar.php'; ?>
<?php require '../navbar.php'; ?>
<div class="main-content">
    <h1>Medicine Stock Alerts</h1>

    <div class="alerts-container">
        <?php if (!empty($lowStockMedicines)): ?>
            <div class="critical-alert">
                <h2>Low Stock</h2>
                <ul>
                    <?php foreach ($criticalAlerts as $alert): ?>
                        <li><?php echo htmlspecialchars($alert); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Restock Alerts -->
            <?php if (!empty($restockAlerts)): ?>
                <div class="restock-alert">
                    <ul>
                        <?php foreach ($restockAlerts as $alert): ?>
                            <li><?php echo htmlspecialchars($alert); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Stock OK Alerts -->
            <?php if (!empty($stockOkAlerts)): ?>
                <div class="stock-ok-alert">
                    <h2>Stock OK</h2>
                    <ul>
                        <?php foreach ($stockOkAlerts as $alert): ?>
                            <li><?php echo htmlspecialchars($alert); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <p>No low stock alerts at the moment.</p>
        <?php endif; ?>
    </div> <!-- alerts-container -->

    <div class="alerts-container">
        <h2>Near Expiry Medicines</h2>
        <ul class="near-expiry-alert">
            <?php foreach ($filteredMedicines as $medicine): ?>
                <li class="<?= $medicine['urgency_class'] ?>">
                    <?php echo htmlspecialchars($medicine['description']) . " - " . htmlspecialchars($medicine['unit_measurement']) . ": " . $medicine['days_remaining'] . " days remaining (Urgency: " . $medicine['urgency'] . ")"; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div> <!-- alerts-container -->
</div> <!-- main-content -->
</body>
</html>
