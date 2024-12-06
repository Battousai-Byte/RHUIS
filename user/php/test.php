<?php
require '../../includes/db_connection.php'; 
$expectedLowStockCount = 0; 
$expectedNearExpiryCount = 4; 

$start_time = microtime(true); 
$start_memory = memory_get_usage(); 

$lowStockQuery = "
    SELECT m.description, m.unit_measurement, t.threshold, SUM(m.stock_quantity) AS total_stock
    FROM medicine AS m
    LEFT JOIN thresholds AS t ON m.unit_measurement = t.unit_measurement
    GROUP BY m.description, m.unit_measurement, t.threshold
    HAVING total_stock <= t.threshold
    LIMIT 2
";
$lowStockResult = mysqli_query($conn, $lowStockQuery); 

if (!$lowStockResult) {
    echo "Error fetching low-stock medicines: " . mysqli_error($conn) . "\n";
    exit;
}

$lowStockMedicines = mysqli_fetch_all($lowStockResult, MYSQLI_ASSOC); 
$currentDate = date('Y-m-d');
$threeMonthThreshold = date('Y-m-d', strtotime('+3 months'));

// Define the SQL query to select medicines near expiry within the next three months
$nearExpiryQuery = "
    SELECT 
        description, 
        expiration_date, 
        unit_measurement, 
        unit_price, 
        SUM(stock_quantity) AS total_stock
    FROM medicine 
    WHERE expiration_date IS NOT NULL
    AND expiration_date BETWEEN ? AND ?
    GROUP BY description, expiration_date, unit_measurement, unit_price
    ORDER BY expiration_date ASC
";

// Prepare the SQL statement
$stmt = $conn->prepare($nearExpiryQuery);
$stmt->bind_param("ss", $currentDate, $threeMonthThreshold);
$stmt->execute();

// Fetch the result
$nearExpiryResult = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Close the statement
$stmt->close();




// Iterate through results and classify based on date ranges (1-month, 2-month, and 3-month)




$end_time = microtime(true); 
$end_memory = memory_get_usage(); 


$execution_time = $end_time - $start_time; 
$memory_used = $end_memory - $start_memory; 


$actualLowStockCount = count($lowStockMedicines); 
$actualNearExpiryCount = count($nearExpiryResult); 


// Calculate Low Stock Accuracy
if ($expectedLowStockCount > 0) {
    $lowStockAccuracy = ($actualLowStockCount / $expectedLowStockCount) * 100;
} else {
    // If no expected low stock, set accuracy to 100%
    $lowStockAccuracy = ($actualLowStockCount === 0) ? 100 : 0;
}

// Calculate Near Expiry Accuracy
if ($expectedNearExpiryCount > 0) {
    $nearExpiryAccuracy = ($actualNearExpiryCount / $expectedNearExpiryCount) * 100;
} else {
    // If no expected near expiry, set accuracy to 100%
    $nearExpiryAccuracy = ($actualNearExpiryCount === 0) ? 100 : 0;
}





echo "<h2>Performance Metrics</h2>\n";
echo "Execution Time: " . number_format($execution_time, 5) . " seconds\n\n"; 

echo "Memory Used: " . number_format($memory_used / 1024, 2) . " KB\n\n"; 

echo "<h2>Accuracy Testing</h2>\n";
echo "Expected Low Stock Count: " . $expectedLowStockCount . "\n"; 
echo "Actual Low Stock Count: " . $actualLowStockCount . "\n"; 
echo "Low Stock Accuracy: " . $lowStockAccuracy . "%\n\n"; 

echo "Expected Near Expiry Count: " . $expectedNearExpiryCount . "\n"; 
echo "Actual Near Expiry Count: " . $actualNearExpiryCount . "\n";
echo "Near Expiry Accuracy: " . $nearExpiryAccuracy . "%\n\n";

?>
