<?php
session_start();
require '../../includes/db_connection.php';
require_once 'rule-based.php'; // Include the rule-based.php file

// Initialize variables for date range
$startDate = isset($_POST['start_date']) ? $_POST['start_date'] : date('Y-m-01');
$endDate = isset($_POST['end_date']) ? $_POST['end_date'] : date('Y-m-d');

// Fetch low stock medicines and near expiry medicines
$lowStockMedicines = checkLowStock($conn);
$nearExpiryMedicines = checkNearExpiry($conn);

// Store alerts for low stock and near expiry medicines
foreach ($lowStockMedicines as $medicine) {
    $medicineIdQuery = "SELECT medicine_id FROM medicine WHERE description = ? AND unit_measurement = ? LIMIT 1";
    $stmt = $conn->prepare($medicineIdQuery);
    $stmt->bind_param("ss", $medicine['description'], $medicine['unit_measurement']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($medicineRow = $result->fetch_assoc()) {
        storeAlert($conn, $medicineRow['medicine_id'], 'low_stock');
    }
}

foreach ($nearExpiryMedicines as $medicine) {
    $medicineIdQuery = "SELECT medicine_id FROM medicine WHERE description = ? AND unit_measurement = ? LIMIT 1";
    $stmt = $conn->prepare($medicineIdQuery);
    $stmt->bind_param("ss", $medicine['description'], $medicine['unit_measurement']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($medicineRow = $result->fetch_assoc()) {
        storeAlert($conn, $medicineRow['medicine_id'], 'near_expiry');
    }
}

// Fetch total cost from usage_history by multiplying quantity and unit_value
$totalCostQuery = "
    SELECT SUM(uh.quantity * m.unit_price) AS total_cost 
    FROM medicine_usage_history uh 
    JOIN medicine m ON uh.medicine_id = m.medicine_id 
    WHERE uh.activity_date BETWEEN ? AND ?
";
$stmt = $conn->prepare($totalCostQuery);
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$totalCostResult = $stmt->get_result()->fetch_assoc();
$totalCost = $totalCostResult['total_cost'];

// Fetch total medicines
$totalMedicinesQuery = "SELECT COUNT(DISTINCT description) AS total_medicines FROM medicine";
$totalMedicinesResult = $conn->query($totalMedicinesQuery)->fetch_assoc();
$totalMedicines = $totalMedicinesResult['total_medicines'];

// Fetch medicine usage history based on the date range
$usageHistoryQuery = "
    SELECT m.description, SUM(uh.quantity) AS total_quantity 
    FROM medicine_usage_history uh 
    JOIN medicine m ON uh.medicine_id = m.medicine_id
    WHERE uh.activity_date BETWEEN ? AND ? 
    GROUP BY m.description
";
$stmt = $conn->prepare($usageHistoryQuery);
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$usageHistoryData = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch overall stock by category with summed stock quantities
$categoryStockQuery = "
    SELECT description, SUM(stock_quantity) AS total_stock 
    FROM medicine 
    GROUP BY description
";
$categoryStockResult = $conn->query($categoryStockQuery)->fetch_all(MYSQLI_ASSOC);

// Save counts to session
$_SESSION['low_stock_count'] = count($lowStockMedicines);
$_SESSION['expiring_count'] = count($nearExpiryMedicines);

// Check if the alert has already been shown
if (!isset($_SESSION['alert_shown'])) {
    $_SESSION['alert_shown'] = false;
}
?>





<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="https://unpkg.com/boxicons/css/boxicons.min.css">
    <style>
       /* General Styles */
       .date-picker-container {
            display: flex;
            justify-content: space-between;
            gap: 10px;
        }
        .date-picker-container .form-group {
            flex: 1;
        }
        .date-picker-container button {
            margin-top: 18px; /* Adjust as needed */
            padding: 5px;
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 0px;
            height: 6px; /* Match this height with the input height */
            line-height: 1.5;
        }
        .form-control {
            height: 38px; /* Match this height with the button height */
        }
       .date-picker-container button:hover {
        background-color: darkblue;
       }
       .dashboard-stats {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .dashboard-item {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            flex-grow: 1;
            margin-right: 10px;
        }
        .dashboard-item:last-child {
            margin-right: 0;
        }
        .dashboard-item h2 {
            margin-bottom: 10px;
            font-size: 18px;
            color: #2C3E50;
        }

        /* Flexbox container for lists and charts */
        .container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px; /* Space between items */
            margin-bottom: 20px;
        }

        /* List Container */
        .list-container {
            display: flex;
            justify-content: space-between;
            gap: 20px; /* Space between lists */
            flex-wrap: wrap; /* Allow wrapping on smaller screens */
        }

      
        /* Chart Container */
        .chart-container {
            flex: 1 1 100%; /* Full width on small screens */
            padding: 20px;
            border-radius: 10px;
        }
        .chart-container h2 {
            color: #2C3E50;
        }
        .form-title {
            color: #2C3E50;
            text-align: center;
            margin-bottom: 20px;
        }
      /* General Modal Styles */
#alert-modal {
    position: fixed; /* Ensure it stays in place on the screen */
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%); /* Center the modal */
    background-color: #fff;
    color: black;
    padding: 20px;
    z-index: 10000; /* Ensure it's above the overlay */
    width: 400px;
    border-radius: 5px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    text-align: center;
    display: none; /* Hidden by default */
}

/* Overlay Styles */
#overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5); /* Slightly transparent black background */
    z-index: 9999; /* Ensure it's below the modal */
    display: none; /* Hidden by default */
}

/* Close Button Styles */
#close-btn {
    background: none;
    border: none;
    color: black;
    font-size: 24px;
    position: absolute; /* Position relative to the modal */
    top: 10px; /* Distance from the top */
    right: 10px; /* Distance from the right */
    cursor: pointer;
}

#close-btn:hover {
    color: red;
}

    </style>
</head>
<body>
    <?php require '../sidebar.php'; ?>
    <?php require '../navbar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <h1 class="form-title">Dashboard</h1>

        <!-- Date Picker -->
     


        <!-- Dashboard Stats -->
        <div class="dashboard-stats">
            <div class="dashboard-item">
                <h2>Total Cost</h2>
                <p><?php echo number_format($totalCost, 2); ?> PHP</p>
            </div>
            <div class="dashboard-item">
        <h2>Low Stock Count</h2>
        <p><?php echo $_SESSION['low_stock_count']; ?></p>
    </div>
    <div class="dashboard-item">
        <h2>Near Expiry Count</h2>
        <p><?php echo $_SESSION['expiring_count']; ?></p>
    </div>
    <div class="dashboard-item">
                <h2>Total Medicines</h2>
                <p><?php echo $totalMedicines; ?></p>
            </div>
        </div>

        <!-- Lists and Charts -->
        <form method="post" action="dashboard.php" class="mb-4">
    <div class="date-picker-container">
        <div class="form-group">
            <label for="start_date">Start Date:</label>
            <input type="date" id="start_date" name="start_date" class="form-control" value="<?php echo htmlspecialchars($startDate); ?>" required>
        </div>
        <div class="form-group">
            <label for="end_date">End Date:</label>
            <input type="date" id="end_date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($endDate); ?>" required>
        </div>
    </div>
    <!-- Removed the filter button -->
</form>

<script>
    // Add event listeners to trigger form submission on date change
    document.getElementById('start_date').addEventListener('change', function() {
        this.form.submit();
    });

    document.getElementById('end_date').addEventListener('change', function() {
        this.form.submit();
    });
</script>

<!-- Medicine Usage History Chart -->
<div class="chart-container">
    <h2>Medicine Usage History</h2>
    <canvas id="usageHistoryChart" width="400" height="200"></canvas>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        var ctx = document.getElementById('usageHistoryChart').getContext('2d');
        var usageHistoryChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($usageHistoryData, 'description')); ?>,
                datasets: [{
                    label: 'Quantity Used',
                    data: <?php echo json_encode(array_column($usageHistoryData, 'total_quantity')); ?>,
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.6)',  // Red
                        'rgba(54, 162, 235, 0.6)',  // Blue
                        'rgba(255, 206, 86, 0.6)',  // Yellow
                        'rgba(75, 192, 192, 0.6)',  // Green
                        'rgba(153, 102, 255, 0.6)', // Purple
                        'rgba(255, 159, 64, 0.6)',  // Orange
                        'rgba(199, 199, 199, 0.6)', // Gray
                        'rgba(255, 99, 71, 0.6)',   // Tomato
                        'rgba(135, 206, 235, 0.6)', // Sky Blue
                        'rgba(144, 238, 144, 0.6)'  // Light Green
                    ],
                    borderColor: [
                        'rgba(255, 99, 132, 1)',  // Red
                        'rgba(54, 162, 235, 1)',  // Blue
                        'rgba(255, 206, 86, 1)',  // Yellow
                        'rgba(75, 192, 192, 1)',  // Green
                        'rgba(153, 102, 255, 1)', // Purple
                        'rgba(255, 159, 64, 1)',  // Orange
                        'rgba(199, 199, 199, 1)', // Gray
                        'rgba(255, 99, 71, 1)',   // Tomato
                        'rgba(135, 206, 235, 1)', // Sky Blue
                        'rgba(144, 238, 144, 1)'  // Light Green
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</div>


       <!-- Overall Stock by Category Chart -->
<!-- Overall Stock by Category Chart -->
<div class="chart-container">
    <h2>Overall Stock by Category</h2>
    <input type="text" id="categorySearch" placeholder="Search categories...">
    <canvas id="categoryStockChart" width="400" height="200"></canvas>
    <style>
        /* Style for the search bar */
        #categorySearch {
            width: 30%; /* Adjust width as needed */
            padding: 7px; /* Padding inside the search bar */
            border: 1px solid gray; /* Gray border */
            border-radius: 8px; /* Rounded edges */
            box-sizing: border-box; /* Ensure padding and border are included in the element's total width and height */
            font-size: 14px; /* Adjust font size */
            outline: none; /* Remove default outline */
            margin-bottom: 10px; /* Add some space below the search bar */
        }

        /* Optional: Add a bit of styling on focus */
        #categorySearch:focus {
            border-color: #007bff; /* Change border color on focus */
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.5); /* Add a subtle shadow on focus */
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        var ctx = document.getElementById('categoryStockChart').getContext('2d');

        // Original dataset
        var originalLabels = <?php echo json_encode(array_column($categoryStockResult, 'description')); ?>;
        var originalData = <?php echo json_encode(array_column($categoryStockResult, 'total_stock')); ?>;

        var categoryStockData = {
            labels: originalLabels,
            datasets: [{
                label: 'Stock Quantity',
                data: originalData,
                backgroundColor: [
                    'rgba(255, 99, 132, 0.6)',  // Red
                    'rgba(54, 162, 235, 0.6)',  // Blue
                    'rgba(255, 206, 86, 0.6)',  // Yellow
                    'rgba(75, 192, 192, 0.6)',  // Green
                    'rgba(153, 102, 255, 0.6)', // Purple
                    'rgba(255, 159, 64, 0.6)',  // Orange
                    'rgba(199, 199, 199, 0.6)', // Gray
                    'rgba(255, 99, 71, 0.6)',   // Tomato
                    'rgba(135, 206, 235, 0.6)', // Sky Blue
                    'rgba(144, 238, 144, 0.6)'  // Light Green
                ],
                borderColor: [
                    'rgba(255, 99, 132, 1)',  // Red
                    'rgba(54, 162, 235, 1)',  // Blue
                    'rgba(255, 206, 86, 1)',  // Yellow
                    'rgba(75, 192, 192, 1)',  // Green
                    'rgba(153, 102, 255, 1)', // Purple
                    'rgba(255, 159, 64, 1)',  // Orange
                    'rgba(199, 199, 199, 1)', // Gray
                    'rgba(255, 99, 71, 1)',   // Tomato
                    'rgba(135, 206, 235, 1)', // Sky Blue
                    'rgba(144, 238, 144, 1)'  // Light Green
                ],
                borderWidth: 1
            }]
        };

        var categoryStockChart = new Chart(ctx, {
            type: 'bar',
            data: categoryStockData,
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Function to update chart based on search input
        function updateChart(filter) {
            if (filter === '') {
                // Show all data when the search is cleared
                categoryStockChart.data.labels = originalLabels;
                categoryStockChart.data.datasets[0].data = originalData;
            } else {
                // Filter data based on the input
                let filteredData = originalLabels.map((label, index) => {
                    return {
                        label: label,
                        value: originalData[index]
                    };
                }).filter(item => item.label.toLowerCase().startsWith(filter.toLowerCase()));

                // Update chart with filtered data
                categoryStockChart.data.labels = filteredData.map(item => item.label);
                categoryStockChart.data.datasets[0].data = filteredData.map(item => item.value);
            }
            categoryStockChart.update();
        }

        // Event listener for search input
        document.getElementById('categorySearch').addEventListener('input', function() {
            updateChart(this.value);
        });
    </script>
</div>
<div class="chart-container">
            <h2>Low Stock Medicines</h2>
            <canvas id="lowStockChart" width="400" height="200"></canvas>

            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            <script>
                var ctx = document.getElementById('lowStockChart').getContext('2d');
                var lowStockChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: <?php echo json_encode(array_column($lowStockMedicines, 'description')); ?>,
                        datasets: [{
                            label: 'Current Stock',
                            data: <?php echo json_encode(array_column($lowStockMedicines, 'total_stock')); ?>,
                            backgroundColor: 'rgba(255, 99, 132, 0.6)',
                            borderColor: 'rgba(255, 99, 132, 1)',
                            borderWidth: 1
                        }, {
                            label: 'Threshold',
                            data: <?php echo json_encode(array_column($lowStockMedicines, 'threshold')); ?>,
                            backgroundColor: 'rgba(54, 162, 235, 0.6)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            </script>
        </div>

        <!-- Near Expiry Medicines Chart -->
        <div class="chart-container">
            <h2>Near Expiry Medicines</h2>
            <canvas id="expiryChart" width="400" height="200"></canvas>

            <script>
                var ctx = document.getElementById('expiryChart').getContext('2d');
                var expiryChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: <?php echo json_encode(array_column($nearExpiryMedicines, 'description')); ?>,
                        datasets: [{
                            label: 'Days Until Expiry',
                            data: <?php echo json_encode(array_map(function($medicine) {
                                $expiry_date = new DateTime($medicine['expiration_date']);
                                $current_date = new DateTime();
                                return $current_date->diff($expiry_date)->days;
                            }, $nearExpiryMedicines)); ?>,
                            backgroundColor: 'rgba(255, 159, 64, 0.6)',
                            borderColor: 'rgba(255, 159, 64, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Days Until Expiry'
                                }
                            },
                            x: {
                                title: {
                                    display: true,
                                    text: 'Medicine'
                                }
                            }
                        }
                    }
                });
            </script>
        </div>

        
    </div>
                
    <!-- Alerts Modal -->
<div id="overlay" style="display: <?php echo $_SESSION['alert_shown'] ? 'none' : 'block'; ?>;"></div>
<div id="alert-modal" style="display: <?php echo $_SESSION['alert_shown'] ? 'none' : 'block'; ?>;">
    <button id="close-btn"><i class='bx bx-x'></i></button>
    <h2>Alert!</h2> 
    <p>
        <?php
        $lowStockCount = $_SESSION['low_stock_count'];
        $expiringCount = $_SESSION['expiring_count'];

        if ($lowStockCount > 0) {
            echo "There are $lowStockCount low stock medicines.";
        }
        if ($expiringCount > 0) {
            if ($lowStockCount > 0) {
                echo " and ";
            }
            echo "$expiringCount medicines are nearing its expiration.";
        }
        if ($lowStockCount == 0 && $expiringCount == 0) {
            echo "No alerts.";
        }
        ?>
    </p>
</div>

<script>
    // Display the alert modal if there are any alerts
    document.getElementById('close-btn').addEventListener('click', function() {
        document.getElementById('alert-modal').style.display = 'none';
        document.getElementById('overlay').style.display = 'none';
        // Update session variable to indicate alert has been shown
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'update_alert_status.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.send('alert_shown=true');
    });
</script>
</body>
</html>
