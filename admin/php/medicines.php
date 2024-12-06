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

// Generate the table content


// Close the database connection
$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medicines</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="https://unpkg.com/boxicons/css/boxicons.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
        }

        .container {
            margin-left: 210px; /* Adjust based on sidebar width */
            padding: 20px;
        }

       

        .search-bar {
            display: flex;
            justify-content: left;
            margin-bottom: 20px;
            position: relative;
        }

        .search-bar input {
            width: 300px;
            padding: 7px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
        }

        .search-bar button {
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            margin-left: 10px;
        }

        .search-bar button:hover {
            background-color: #0056b3;
        }


        .no-medicines {
            text-align: center;
            font-size: 18px;
            color: #777;
            margin-top: 20px;
        }

        .button-container {
            display: flex;
            justify-content: center;
            gap: 10px; /* Adds space between buttons */
            margin-bottom: 20px;
        }

        .button-container a {
            padding: 10px 20px;
            
            color: black;
            text-decoration: none;
            border-radius: 5px;
            font-size: 16px;
            transition: background-color 0.3s ease;
            display: inline-block;
        }

        .button-container a:hover {
            color:#007bff;
        }

    </style>
</head>
<body>
    <?php require '../sidebar.php'; ?>
    <?php require '../navbar.php'; ?>

    <div class="main-content">
        <h1>Medicines</h1>

        <!-- Buttons Container -->
        <div class="button-container">
            <a href="low_stock.php">Low Stock Medicines</a>
            <a href="near_expiry.php">Near Expiry Medicines</a>
            <a href="expired.php">Expired Medicines</a>
        </div>

        <!-- Search Bar -->
        <div class="search-bar">
            <input type="text" id="search" placeholder="Search Medicines..." autocomplete="off">
        </div>

        <!-- Medicines Table -->
        <div class="table-container" id="medicines-table-container">
    <?php if (count($medicines) > 0): ?>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Unit Measurement</th>
                    <th>Unit Price</th>
                    <th>Stock Quantity</th>
                    <th>Unit Value</th>
                    <th>Lot No</th>
                    <th>Expiration Date</th>
                    <th>Quantity/Carton</th>
                </tr>
            </thead>
            <tbody id="medicine-tbody">
                <?php foreach ($medicines as $medicine): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($medicine['description']); ?></td>
                        <td><?php echo htmlspecialchars($medicine['unit_measurement']); ?></td>
                        <td><?php echo htmlspecialchars($medicine['unit_price']); ?></td>
                        <td><?php echo htmlspecialchars($medicine['total_stock_quantity']); ?></td>
                        <td><?php echo htmlspecialchars($medicine['unit_value']); ?></td>
                        <td><?php echo htmlspecialchars($medicine['lot_no']); ?></td>
                        <td><?php echo !empty($medicine['expiration_date']) && $medicine['expiration_date'] !== '0000-00-00' ? date('M d, Y', strtotime($medicine['expiration_date'])) : 'No Expiration'; ?></td>
                        <td><?php echo htmlspecialchars($medicine['quantity_per_carton']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="no-medicines">No medicines found.</div>
    <?php endif; ?>
</div>

<!-- JavaScript for handling search -->
<script>
    document.getElementById('search').addEventListener('input', function() {
        const searchTerm = this.value;

        // Make AJAX request to fetch filtered medicines
        const xhr = new XMLHttpRequest();
        xhr.open('GET', `fetch_medicines.php?search=${encodeURIComponent(searchTerm)}`, true);
        xhr.onload = function() {
            if (xhr.status === 200) {
                // Replace the table body content with the response
                document.querySelector('#medicine-tbody').innerHTML = xhr.responseText;
            }
        };
        xhr.send();
    });
</script>
</body>
</html>
