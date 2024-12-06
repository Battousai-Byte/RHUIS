<?php
require '../../includes/db_connection.php';

$expectedLowStockCount = 0;
$expectedNearExpiryCount = 4;

$start_time = microtime(true);
$start_memory = memory_get_usage();

// Function to check for near-expiry medicines
function checkNearExpiry($conn) {
    $currentDate = date('Y-m-d');
    $threeMonthThreshold = date('Y-m-d', strtotime('+3 months'));

    $nearExpiryQuery = "
        SELECT 
            description, 
            expiration_date, 
            unit_measurement, 
            unit_price, 
            SUM(stock_quantity) AS total_stock
        FROM medicine 
        WHERE expiration_date > ? AND expiration_date <= ?
        GROUP BY description, expiration_date, unit_measurement, unit_price
        ORDER BY expiration_date ASC
    ";

    $stmt = $conn->prepare($nearExpiryQuery);
    $stmt->bind_param("ss", $currentDate, $threeMonthThreshold);
    $stmt->execute();

    $nearExpiryResult = $stmt->get_result();
    return $nearExpiryResult->fetch_all(MYSQLI_ASSOC);
}

// Function to check for low-stock medicines
function checkLowStock($conn) {
    $lowStockQuery = "
        SELECT 
            m.description, 
            m.unit_measurement, 
            t.threshold, 
            SUM(m.stock_quantity) AS total_stock
        FROM medicine AS m
        LEFT JOIN thresholds AS t ON m.unit_measurement = t.unit_measurement
        GROUP BY m.description, m.unit_measurement, t.threshold
        HAVING total_stock <= t.threshold
    ";

    $lowStockResult = $conn->query($lowStockQuery);

    if (!$lowStockResult) {
        error_log("Error fetching low-stock medicines: " . $conn->error);
        return [];
    }

    return $lowStockResult->fetch_all(MYSQLI_ASSOC);
}

// Function to store alerts in the database
function storeAlert($conn, $medicine_id, $alert_type) {
    if (!in_array($alert_type, ['low_stock', 'near_expiry'])) {
        throw new InvalidArgumentException("Invalid alert type provided");
    }

    $query = "INSERT INTO alerts (medicine_id, alert_type, alert_date) VALUES (?, ?, NOW())";
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return false;
    }

    $stmt->bind_param("is", $medicine_id, $alert_type);

    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error);
        return false;
    }

    return true;
}

// Function to evaluate medicine stock based on thresholds
function evaluateMedicineStock($conn) {
    $lowStockMedicines = checkLowStock($conn);
    $nearExpiryMedicines = checkNearExpiry($conn);

    $alerts = [];

    // Process low stock alerts
    foreach ($lowStockMedicines as $medicine) {
        $medicine_id = getMedicineId($conn, $medicine['description'], $medicine['unit_measurement']);
        if ($medicine_id === null) {
            continue;
        }

        $alert_message = sprintf(
            "ALERT: Medicine '%s' is low in stock. Current stock: %d, Threshold: %d.",
            $medicine['description'],
            $medicine['total_stock'],
            $medicine['threshold']
        );
        $alerts[] = $alert_message;
        storeAlert($conn, $medicine_id, 'low_stock');
    }

    // Process near expiry alerts
    foreach ($nearExpiryMedicines as $medicine) {
        $medicine_id = getMedicineId($conn, $medicine['description'], $medicine['unit_measurement']);
        if ($medicine_id === null) {
            continue;
        }

        $alert_message = sprintf(
            "ALERT: Medicine '%s' is expiring soon. Expiration date: %s.",
            $medicine['description'],
            $medicine['expiration_date']
        );
        $alerts[] = $alert_message;
        storeAlert($conn, $medicine_id, 'near_expiry');
    }

    return [
        'alerts' => $alerts,
        'lowStockCount' => count($lowStockMedicines),
        'nearExpiryCount' => count($nearExpiryMedicines)
    ];
}

function getMedicineId($conn, $description, $unit_measurement) {
    $medicine_id_query = "SELECT medicine_id FROM medicine WHERE description = ? AND unit_measurement = ?";
    $stmt = $conn->prepare($medicine_id_query);
    $stmt->bind_param("ss", $description, $unit_measurement);
    $stmt->execute();
    $result = $stmt->get_result();
    $medicine_id_row = $result->fetch_assoc();
    
    if (!$medicine_id_row) {
        error_log("Warning: Medicine ID not found for description: $description, unit: $unit_measurement");
        return null;
    }
    
    return $medicine_id_row['medicine_id'];
}

function calculateAccuracy($actual, $expected) {
    if ($expected > 0) {
        return min(($actual / $expected) * 100, 100);
    }
    return ($actual === 0) ? 100 : 0;
}

// Main execution
$evaluation_results = evaluateMedicineStock($conn);

$end_time = microtime(true);
$end_memory = memory_get_usage();

$execution_time = $end_time - $start_time;
$memory_used = $end_memory - $start_memory;

$actualLowStockCount = $evaluation_results['lowStockCount'];
$actualNearExpiryCount = $evaluation_results['nearExpiryCount'];

$lowStockAccuracy = calculateAccuracy($actualLowStockCount, $expectedLowStockCount);
$nearExpiryAccuracy = calculateAccuracy($actualNearExpiryCount, $expectedNearExpiryCount);

// Prepare output
$output = "";
$output .= "<h2>Performance Metrics</h2>\n";
$output .= "Execution Time: " . number_format($execution_time, 5) . " seconds\n\n";
$output .= "Memory Used: " . number_format($memory_used / 1024, 2) . " KB\n\n";

$output .= "<h2>Accuracy Testing</h2>\n";
$output .= "Expected Low Stock Count: " . $expectedLowStockCount . "\n";
$output .= "Actual Low Stock Count: " . $actualLowStockCount . "\n";
$output .= "Low Stock Accuracy: " . number_format($lowStockAccuracy, 2) . "%\n\n";

$output .= "Expected Near Expiry Count: " . $expectedNearExpiryCount . "\n";
$output .= "Actual Near Expiry Count: " . $actualNearExpiryCount . "\n";
$output .= "Near Expiry Accuracy: " . number_format($nearExpiryAccuracy, 2) . "%\n\n";

$output .= "<h2>Alerts</h2>\n";
foreach ($evaluation_results['alerts'] as $alert) {
    $output .= $alert . "\n";
}

// Output results
echo $output;

?>
<?php
$start_time = microtime(true); 
// Executes the algorithm
$end_time = microtime(true);   
$execution_time = $end_time - $start_time;

$start_memory = memory_get_usage(); // Record the start memory usage
// Execute the algorithm
$end_memory = memory_get_usage();   // Record the end memory usage
$memory_usage = $end_memory - $start_memory; // Calculate memory usage

?>