<?php
// Function to check for near-expiry medicines
// Function to check for near-expiry medicines (90 days or less)
function checkNearExpiry($conn) {
    $currentDate = date('Y-m-d');

    // SQL query to select medicines expiring in 90 days or less
    $nearExpiryQuery = "
        SELECT 
            description, 
            expiration_date, 
            unit_measurement, 
            unit_price, 
            SUM(stock_quantity) AS total_stock,
            DATEDIFF(expiration_date, ?) AS days_until_expiry
        FROM medicine 
        WHERE expiration_date > ? AND DATEDIFF(expiration_date, ?) <= 90
        GROUP BY description, expiration_date, unit_measurement, unit_price
        ORDER BY expiration_date ASC
    ";

    // Prepare and bind the parameters for the query
    $stmt = $conn->prepare($nearExpiryQuery);
    $stmt->bind_param("sss", $currentDate, $currentDate, $currentDate);
    $stmt->execute();

    $nearExpiryResult = $stmt->get_result();
    $data = $nearExpiryResult->fetch_all(MYSQLI_ASSOC);

    return $data;
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
        echo "Error fetching low-stock medicines: " . $conn->error . "\n";
        return [];
    }

    $data = $lowStockResult->fetch_all(MYSQLI_ASSOC);

    return $data;
}

// Function to store alerts in the database
function storeAlert($conn, $medicine_id, $alert_type) {
    // Ensure alert_type is either 'low_stock' or 'near_expiry'
    if (!in_array($alert_type, ['low_stock', 'near_expiry'])) {
        throw new InvalidArgumentException("Invalid alert type provided");
    }

    $query = "INSERT INTO alerts (medicine_id, alert_type, alert_date) VALUES (?, ?, NOW())";
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("is", $medicine_id, $alert_type);

    if (!$stmt->execute()) {
        die("Execute failed: " . $stmt->error);
    }
}

// Function to evaluate medicine stock based on thresholds
function evaluateMedicineStock($conn) {
    // Get low stock medicines
    $lowStockMedicines = checkLowStock($conn);

    // Get near-expiry medicines
    $nearExpiryMedicines = checkNearExpiry($conn);

    $alerts = [];

    // Process low stock alerts
    foreach ($lowStockMedicines as $medicine) {
        $description = $medicine['description'];
        $unit_measurement = $medicine['unit_measurement'];
        $threshold = $medicine['threshold'];
        $total_stock = $medicine['total_stock'];

        // Fetch medicine ID for the alert
        $medicine_id_query = "SELECT medicine_id FROM medicine WHERE description = ? AND unit_measurement = ?";
        $stmt = $conn->prepare($medicine_id_query);
        $stmt->bind_param("ss", $description, $unit_measurement);
        $stmt->execute();
        $result = $stmt->get_result();
        $medicine_id_row = $result->fetch_assoc();
        $medicine_id = $medicine_id_row['medicine_id'];

        if (is_null($medicine_id)) {
            echo "Warning: Medicine ID is null for low stock alert.\n";
            continue;
        }

        $alert_message = "ALERT: Medicine '$description' is low in stock. Current stock: $total_stock, Threshold: $threshold.";
        $alerts[] = $alert_message;
        storeAlert($conn, $medicine_id, 'low_stock');
    }

    // Process near expiry alerts
    foreach ($nearExpiryMedicines as $medicine) {
        $description = $medicine['description'];
        $expiration_date = $medicine['expiration_date'];
        $unit_measurement = $medicine['unit_measurement'];
        $total_stock = $medicine['total_stock'];

        // Fetch medicine ID for the alert
        $medicine_id_query = "SELECT medicine_id FROM medicine WHERE description = ? AND unit_measurement = ?";
        $stmt = $conn->prepare($medicine_id_query);
        $stmt->bind_param("ss", $description, $unit_measurement);
        $stmt->execute();
        $result = $stmt->get_result();
        $medicine_id_row = $result->fetch_assoc();
        $medicine_id = $medicine_id_row['medicine_id'];

        if (is_null($medicine_id)) {
            echo "Warning: Medicine ID is null for near expiry alert.\n";
            continue;
        }

        $alert_message = "ALERT: Medicine '$description' is expiring soon. Expiration date: $expiration_date.";
        $alerts[] = $alert_message;
        storeAlert($conn, $medicine_id, 'near_expiry');
    }

    return $alerts;
}
?>
