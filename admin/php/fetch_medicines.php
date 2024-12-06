<?php
// Include the database connection file
require '../../includes/db_connection.php';

// Initialize variables
$searchTerm = '';
$medicines = [];

// Fetch medicines from the database based on the search term
$query = "SELECT description, unit_measurement, unit_price, SUM(stock_quantity) as total_stock_quantity, unit_value, lot_no, MAX(expiration_date) as expiration_date, quantity_per_carton 
          FROM medicine 
          WHERE (expiration_date IS NULL OR expiration_date >= CURDATE())"; // Include NULL expiration dates and future dates

if (isset($_GET['search']) && $_GET['search'] != '') {
    $searchTerm = $_GET['search'];
    $query .= " AND description LIKE ?"; // Add search condition
}

$query .= " GROUP BY description"; // Group by description

$stmt = $conn->prepare($query);
if ($searchTerm) {
    $searchParam = "%$searchTerm%";
    $stmt->bind_param('s', $searchParam);
}

$stmt->execute();
$result = $stmt->get_result();
$medicines = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Generate the table rows content
if (count($medicines) > 0) {
    foreach ($medicines as $medicine) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($medicine['description']) . "</td>";
        echo "<td>" . htmlspecialchars($medicine['unit_measurement']) . "</td>";
        echo "<td>" . htmlspecialchars($medicine['unit_price']) . "</td>";
        echo "<td>" . htmlspecialchars($medicine['total_stock_quantity']) . "</td>";
        echo "<td>" . htmlspecialchars($medicine['unit_value']) . "</td>";
        echo "<td>" . htmlspecialchars($medicine['lot_no']) . "</td>";
        echo "<td>" . (!empty($medicine['expiration_date']) ? date('M d, Y', strtotime($medicine['expiration_date'])) : 'No Expiration') . "</td>";
        echo "<td>" . htmlspecialchars($medicine['quantity_per_carton']) . "</td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='8' class='no-medicines'>No medicines found.</td></tr>";
}

// Close the database connection
$conn->close();
?>
