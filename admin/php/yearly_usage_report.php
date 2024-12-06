<?php
// Include the database connection file
require '../../includes/db_connection.php';

// Fetch selected year or use the current year if none selected
$selectedYear = isset($_POST['year']) ? $_POST['year'] : date("Y");

// Set the date range based on the selected year
$startDate = "$selectedYear-01-01";
$endDate = "$selectedYear-12-31";

// Query to retrieve medicine details and stock for the selected date range, grouped by description
$query = "
    SELECT m.description, m.unit_measurement, m.unit_price, 
           SUM(uh.quantity) AS dispensed_quantity, m.unit_value, m.lot_no, 
           m.expiration_date, m.quantity_per_carton
    FROM medicine m
    INNER JOIN medicine_usage_history uh ON m.medicine_id = uh.medicine_id
    WHERE uh.activity_date BETWEEN ? AND ?
    GROUP BY m.description, m.unit_measurement, m.unit_price, m.unit_value, m.lot_no, m.expiration_date, m.quantity_per_carton
    ORDER BY dispensed_quantity DESC
";
$stmt = $conn->prepare($query);
$stmt->bind_param('ss', $startDate, $endDate);
$stmt->execute();
$result = $stmt->get_result();
$medicines = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Prepare data for the chart
$labels = [];
$data = [];

foreach ($medicines as $medicine) {
    $labels[] = htmlspecialchars($medicine['description']);
    $data[] = (int)$medicine['dispensed_quantity'];
}

// Handle Export as Word Document
if (isset($_POST['export'])) {
    header("Content-Type: application/vnd.ms-word");
    header("Content-Disposition: attachment;filename=Yearly_usage_report_$selectedYear.doc");
    header("Pragma: no-cache");
    header("Expires: 0");

    echo "<html>";
    echo "<body>";
    echo "<h1>RHU-Aurora, Isabela Yearly Usage Report - " . htmlspecialchars($selectedYear) . "</h1>";
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
        echo "<p>No available medicines for the selected year.</p>";
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
    <title>Yearly Usage Report</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="https://unpkg.com/boxicons/css/boxicons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .main-content {
            margin-left: 210px; /* Adjust based on sidebar width */
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
            background-color: #0056b3; /* Darker blue on hover */
        }
        .bx {
            font-size: 16px;
        }
        .as-of-year {
            font-size: 1.2em;
            margin-bottom: 20px;
        }
        .no-medicines {
            color: #ff6b6b;
            font-size: 1.2em;
        }
        .chart-container {
            max-width: 100%;
            margin-top: 30px;
        }
    </style>
</head>
<body>

<?php require '../sidebar.php'; ?>
<?php require '../navbar.php'; ?>

<!-- Main Content -->
<div class="main-content">
    <div class="header-container" style="display: flex; justify-content: center; align-items: center; gap: 20px;">
        <button style="background: none; border: none; cursor: pointer; color:#2C3E50; font-size: 50px;" title='Monthly Usage Report' onclick="window.location.href='monthly_usage_report.php'">
            <i class='bx bx-left-arrow-alt' style="font-size: 32px;"></i>
        </button>
        <h1 style="margin: 0;">Yearly Usage Report</h1>
        <button style="background: none; border: none; color: #ecf0f1; font-size: 50px;" disabled>
            <i class='bx bx-right-arrow-alt' style="font-size: 32px;"></i>
        </button>
    </div>

    <!-- Year Selection Form -->
    <form method="post" action="yearly_usage_report.php" class="mb-4">
        <div class="form-group">
            <label for="year">Select Year:</label>
            <input type="number" id="year" name="year" class="form-control" min="2000" max="<?php echo date("Y"); ?>" value="<?php echo htmlspecialchars($selectedYear); ?>" onchange="this.form.submit()" required>
        </div>

        <button type="submit" name="export" class="btn btn-primary">
            <i class="bx bx-export"></i> Export as Word Document
        </button>
    </form>

    <!-- As of Year Label -->
    <div class="as-of-year">
        As of <?php echo htmlspecialchars($selectedYear); ?>
    </div>

    <!-- Chart Section -->
    <div class="chart-container">
        <canvas id="usageChart"></canvas>
    </div>

    <!-- Medicine Table -->
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
        <p class="no-medicines">No Available Medicines for the Selected Year</p>
    <?php endif; ?>
</div>

<!-- JavaScript for Chart Rendering -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const labels = <?php echo json_encode($labels); ?>;
        const data = <?php echo json_encode($data); ?>;

        const ctx = document.getElementById('usageChart').getContext('2d');
        const chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Dispensed Quantity',
                    data: data,
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
                responsive: true,
                maintainAspectRatio: false
            }
        });
    });
</script>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
