<?php
// Include the database connection file
require '../../includes/db_connection.php';

// Fetch selected month or use the current month if none selected
// Fetch selected date or use the current date if none selected
$selectedDate = isset($_POST['date']) ? $_POST['date'] : date("Y-m-d"); // Now this will have the full date
$formattedDate = date("F j, Y", strtotime($selectedDate)); // Format the date nicely

// Set end date as the same day (you can modify this based on the date range you need)
$endDate = $selectedDate; 


// Query to retrieve medicine details and stock for the selected month, grouped by description
$query = "
    SELECT m.description, m.unit_measurement, m.unit_price, 
           SUM(uh.quantity) AS dispensed_quantity, m.unit_value, m.lot_no, 
           m.expiration_date, m.quantity_per_carton
    FROM medicine m
    INNER JOIN medicine_usage_history uh ON m.medicine_id = uh.medicine_id
    WHERE uh.activity_date BETWEEN ? AND ?
    GROUP BY m.description, m.unit_measurement, m.unit_price, m.unit_value, m.lot_no, m.expiration_date, m.quantity_per_carton
";
$stmt = $conn->prepare($query);
$stmt->bind_param('ss', $selectedDate, $endDate);
$stmt->execute();
$result = $stmt->get_result();
$medicines = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Handle Export as Word Document
if (isset($_POST['export'])) {
    header("Content-Type: application/vnd.ms-word");
    header("Content-Disposition: attachment;filename=Daily_usage_report.doc");
    header("Pragma: no-cache");
    header("Expires: 0");

    echo "<html>";
    echo "<body>";
    echo "<h1>Daily Usage Report - " . $formattedMonth . "</h1>";
    if (count($medicines) > 0) {
        echo "<table border='1'>
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Unit Measurement</th>
                        <th>Unit Price</th>
                        <th>Unit Value</th>
                        <th>Lot No</th>
                        <th>Expiration Date</th>
                        <th>Quantity Per Carton</th>
                        <th>Dispensed Quantity</th>
                    </tr>
                </thead>
                <tbody>";
        foreach ($medicines as $medicine) {
            echo "<tr>
                    <td>" . htmlspecialchars($medicine['description']) . "</td>
                    <td>" . htmlspecialchars($medicine['unit_measurement']) . "</td>
                    <td>" . htmlspecialchars($medicine['unit_price']) . "</td>
                    <td>" . htmlspecialchars($medicine['unit_value']) . "</td>
                    <td>" . htmlspecialchars($medicine['lot_no']) . "</td>
                    <td>" . htmlspecialchars($medicine['expiration_date']) . "</td>
                    <td>" . htmlspecialchars($medicine['quantity_per_carton']) . "</td>
                    <td>" . htmlspecialchars($medicine['dispensed_quantity']) . "</td>
                  </tr>";
        }
        echo "</tbody>
              </table>";
    } else {
        echo "<p>No available medicines for the selected date.</p>";
    }
    echo "</body>";
    echo "</html>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Usage Report</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="https://unpkg.com/boxicons/css/boxicons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> <!-- Include Chart.js -->
    <style>
        .main-content {
            margin-left: 210px;
            padding: 20px;
        }
       
        button {
            padding: 8px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin: 20px auto;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        button:hover {
            background-color: #0056b3;
        }
        .bx {
            font-size: 16px;
        }
        .as-of-date {
            font-size: 1.2em;
            margin-bottom: 20px;
        }
        .no-medicines {
            color: #ff6b6b;
            font-size: 1.2em;
        }
    </style>
</head>
<body>

<?php require '../sidebar.php'; ?>
<?php require '../navbar.php'; ?>

<!-- Main Content -->
<div class="main-content">
    <div class="header-container" style="display: flex; justify-content: center; align-items: center; gap: 20px;">
        <button style="background: none; border: none; color: #ecf0f1; font-size: 50px;" disabled>
            <i class='bx bx-left-arrow-alt' style="font-size: 32px;"></i>
        </button>
        <h1 style="margin: 0;">Daily Usage Report</h1>
        <button style="background: none; border: none; cursor: pointer; color:#2C3E50; font-size: 50px;" title='Monthly Usage Report' onclick="window.location.href='monthly_usage_report.php'">
            <i class='bx bx-right-arrow-alt' style="font-size: 32px;"></i>
        </button>
    </div>

    <!-- Month Selection Form -->
    <form method="post" action="daily_usage_report.php" class="mb-4">
        <div class="form-group">
            <label for="date">Select Month:</label>
            <input type="date" id="date" name="date" class="form-control" value="<?php echo htmlspecialchars($selectedMonth); ?>" onchange="this.form.submit()" required>
        </div>
        <button type="submit" name="export" class="btn btn-primary">
            <i class="bx bx-export"></i> Export as Word Document
        </button>
    </form>

    <!-- As of Date Label -->
    <div class="as-of-date">
    As of <?php echo $formattedDate; ?>
</div>


    <!-- Chart for Daily Usage -->
    <canvas id="dailyUsageChart" width="400" height="200"></canvas>

    <?php if (count($medicines) > 0): ?>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Unit Measurement</th>
                    <th>Unit Price</th>
                    <th>Unit Value</th>
                    <th>Lot No</th>
                    <th>Expiration Date</th>
                    <th>Quantity Per Carton</th>
                    <th>Dispensed Quantity</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($medicines as $medicine): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($medicine['description']); ?></td>
                        <td><?php echo htmlspecialchars($medicine['unit_measurement']); ?></td>
                        <td><?php echo htmlspecialchars($medicine['unit_price']); ?></td>
                        <td><?php echo htmlspecialchars($medicine['unit_value']); ?></td>
                        <td><?php echo htmlspecialchars($medicine['lot_no']); ?></td>
                        <td><?php echo htmlspecialchars($medicine['expiration_date']); ?></td>
                        <td><?php echo htmlspecialchars($medicine['quantity_per_carton']); ?></td>
                        <td><?php echo htmlspecialchars($medicine['dispensed_quantity']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="no-medicines">No Available Medicines for the Selected Date</p>
    <?php endif; ?>
</div>

<!-- Chart.js Script -->
<script>
    var medicines = <?php echo json_encode($medicines); ?>;
    var ctx = document.getElementById('dailyUsageChart').getContext('2d');
    var descriptions = medicines.map(medicine => medicine.description);
    var dispensedQuantities = medicines.map(medicine => medicine.dispensed_quantity);
    
    var dailyUsageChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: descriptions,
            datasets: [{
                label: 'Dispensed Quantity',
                data: dispensedQuantities,
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            },
            responsive: true
        }
    });
</script>

<!-- JavaScript for Dropdown -->
<script src="../js/script.js"></script>
</body>
</html>
