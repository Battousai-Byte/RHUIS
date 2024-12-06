<?php
// Include the database connection file
require '../../includes/db_connection.php';

// Fetch selected month or use the current month if none selected
$selectedMonth = isset($_POST['month']) ? $_POST['month'] : date("Y-m");

// Set the start and end of the selected month
$startOfMonth = date("Y-m-01", strtotime($selectedMonth));
$endOfMonth = date("Y-m-t", strtotime($selectedMonth));

// Check if the "Export to Word" button was clicked
if (isset($_POST['export'])) {
    // Query to retrieve initial stock quantity and total usage for the selected month
   // Query to retrieve aggregated data for display in the table
$query = "
SELECT m.description, m.unit_measurement, m.unit_price, 
       SUM(m.initial_stock) AS total_initial_stock, 
       (SUM(m.initial_stock) - COALESCE(SUM(uh.quantity), 0)) AS total_end_stock, 
       m.unit_value, m.lot_no, 
       m.expiration_date, m.quantity_per_carton
FROM medicine m
LEFT JOIN medicine_usage_history uh ON m.medicine_id = uh.medicine_id AND uh.activity_date BETWEEN ? AND ?
WHERE m.created_at BETWEEN ? AND ?
GROUP BY m.description, m.unit_measurement, m.unit_price, m.unit_value, m.lot_no, m.expiration_date, m.quantity_per_carton
";
$stmt = $conn->prepare($query);
$stmt->bind_param('ssss', $startOfMonth, $endOfMonth, $startOfMonth, $endOfMonth);
$stmt->execute();
$result = $stmt->get_result();
$medicines = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Prepare data for chart
$descriptions = [];
$initialStocks = [];
$endStocks = [];

foreach ($medicines as $medicine) {
$descriptions[] = htmlspecialchars($medicine['description']);
$initialStocks[] = (int)$medicine['total_initial_stock'];
$endStocks[] = (int)$medicine['total_end_stock'];
}

    // Set headers to force download the Word document
    header("Content-type: application/vnd.ms-word");
    header("Content-Disposition: attachment;Filename=Monthly_Usage_Report_{$selectedMonth}.doc");

    // Output the data in a format suitable for a Word document with landscape mode
    echo "<html>";
    echo "<meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>";
    
    // Landscape mode using a CSS @page rule (note: not all Word versions will respect this, but it's the best approach for HTML)
    echo "<style>";
    echo "@page { layout: landscape; margin-left: 0cm; margin-right: 2cm; }";
    echo "body { font-family: Arial, sans-serif; margin-left: 0cm; margin-right: 2cm;}";
    echo "h1 { text-align: center; }";
    echo "table { width: 100%; border-collapse: collapse; }";
    echo "table, th, td { border: 1px solid black; padding: 5px; }";
    echo "th { background-color: #f2f2f2; }";
    echo ".title { page-break-before: always; }";
    echo "</style>";

    echo "<body>";
    echo "<div style='text-align: center; font-size:20;'>"; // Centering the content
    echo "<h1>REPORT ON THE PHYSICAL COUNT OF INVENTORY</h1>";
    
    // Displaying the selected month along with the current date and time in the format "Month Day, Year, Time"
    $currentDate = date("F j, Y");
    echo "<h2>AS OF ". "" . $currentDate . "</h2>";
    echo "</div>";
    

    if (count($medicines) > 0) {
        echo "<table>";
        echo "<tr>
                <th>Description</th>
                <th>Unit Measurement</th>
                <th>Unit Price</th>
                <th>Stock Quantity</th>
                <th>Unit Value</th>
                <th>Lot No</th>
                <th>Expiration Date</th>
                <th>Quantity Per Carton</th>
                <th>End Stock</th>
                <th>Remarks</th>
              </tr>";

        foreach ($medicines as $medicine) {
            echo "<tr>
                    <td>" . htmlspecialchars($medicine['description']) . "</td>
                    <td>" . htmlspecialchars($medicine['unit_measurement']) . "</td>
                    <td>" . htmlspecialchars($medicine['unit_price']) . "</td>
                    <td>" . htmlspecialchars($medicine['initial_stock']) . "</td>
                    <td>" . htmlspecialchars($medicine['unit_value']) . "</td>
                    <td>" . htmlspecialchars($medicine['lot_no']) . "</td>
                    <td>" . htmlspecialchars($medicine['expiration_date']) . "</td>
                    <td>" . htmlspecialchars($medicine['quantity_per_carton']) . "</td>
                     <td>" . htmlspecialchars($medicine['end_stock']) . "</td>
                    <td>" . htmlspecialchars($medicine['remarks']) . "</td>
                    
                  </tr>";
        }

        echo "</table>";
    } else {
        echo "<p>No report available for the selected month.</p>";
    }

    echo "</body>";
    echo "</html>";
    exit; // Exit after outputting the Word document
}

// Query to retrieve data for display in the table on the page
$query = "
SELECT m.description, m.unit_measurement, m.unit_price, 
       SUM(m.initial_stock) AS initial_stock, 
       SUM(stock_quantity) AS end_stock, 
       m.unit_value, m.lot_no, 
       m.expiration_date, m.quantity_per_carton
FROM medicine m
WHERE m.created_at BETWEEN ? AND ?
Group by m.description, m.unit_measurement
";

$stmt = $conn->prepare($query);
$stmt->bind_param('ss', $startOfMonth, $endOfMonth);
$stmt->execute();
$result = $stmt->get_result();
$medicines = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();


// Format the selected month for display
$formattedMonth = date("F Y", strtotime($selectedMonth));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="https://unpkg.com/boxicons/css/boxicons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> <!-- Include Chart.js -->
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
            background-color: #0056b3;
        }
        .bx {
            font-size: 16px;
        }
        .as-of-month {
            font-size: 1.2em;
            margin-bottom: 20px;
        }
        .btn-success {
            background-color: #007bff;
        }
        .btn-success:hover {
            background-color: #0056b3;
        }
        #searchBar {
            margin-bottom: 20px;
            padding: 8px;
            width: 100%;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
    </style>
</head>
<body>

<?php require '../sidebar.php'; ?>
<?php require '../navbar.php'; ?>

<div class="main-content">
    <div class="header-container" style="display: flex; justify-content: center; align-items: center; gap: 20px;">
        <button style="background: none; border: none; cursor: pointer; color:#2C3E50;font-size: 50px;" title='Daily Usage Report' onclick="window.location.href='daily_usage_report.php'">
        <i class='bx bx-left-arrow-alt' style="font-size: 32px;"></i>
        </button>
        <h1 style="margin: 0;">Monthly Usage Report</h1>
        <button style="background: none; border: none; cursor: pointer;color:#2C3E50;font-size: 50px;" title='Yearly Usage Report' onclick="window.location.href='yearly_usage_report.php'">
            <i class='bx bx-right-arrow-alt' style="font-size: 32px;"></i>
        </button>
    </div>

    <!-- Date Selector -->
    <form method="post" action="monthly_usage_report.php" class="mb-4">
        <div class="form-group">
            <label for="month">Select Month:</label>
            <input type="month" id="month" name="month" class="form-control" value="<?php echo htmlspecialchars($selectedMonth); ?>" onchange="this.form.submit()" required>
        </div>
        <button type="submit" name="export" class="btn btn-success background-color:#007bff;">
            <i class='bx bx-export'></i> Export as Word Document
        </button>
    </form>

    <!-- Search Bar -->
    <input type="text" id="searchBar" placeholder="Search for medicine..." onkeyup="filterMedicines()">

    <!-- As of Month Label -->
    <div class="as-of-month">
        As of <?php echo $formattedMonth; ?>
    </div>

    <!-- Chart -->
    <canvas id="medicineChart" width="400" height="200"></canvas>

    <!-- Medicines Table -->
    <?php if (count($medicines) > 0): ?>
        <table class="table table-striped" id="medicineTable">
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Unit Measurement</th>
                    <th>Unit Price</th>
                    <th>Initial Stock</th>
                    <th>End Stock</th>
                    <th>Unit Value</th>
                    <th>Lot No</th>
                    <th>Expiration Date</th>
                    <th>Quantity Per Carton</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $descriptions = [];
                $initialStocks = [];
                $endStocks = [];

                foreach ($medicines as $medicine):
                    // Store data for chart
                    $descriptions[] = htmlspecialchars($medicine['description']);
                    $initialStocks[] = (int)$medicine['initial_stock'];
                    $endStocks[] = (int)$medicine['end_stock'];
                ?>
                    <tr>
                        <td><?php echo htmlspecialchars($medicine['description']); ?></td>
                        <td><?php echo htmlspecialchars($medicine['unit_measurement']); ?></td>
                        <td><?php echo htmlspecialchars($medicine['unit_price']); ?></td>
                        <td><?php echo htmlspecialchars($medicine['initial_stock']); ?></td>
                        <td><?php echo htmlspecialchars($medicine['end_stock']); ?></td>
                        <td><?php echo htmlspecialchars($medicine['unit_value']); ?></td>
                        <td><?php echo htmlspecialchars($medicine['lot_no']); ?></td>
                        <td><?php echo htmlspecialchars($medicine['expiration_date']); ?></td>
                        <td><?php echo htmlspecialchars($medicine['quantity_per_carton']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No report available for the selected month.</p>
    <?php endif; ?>
</div>
<script>
    // Initialize chart object globally so we can update it
    var medicineChart;

    window.onload = function () {
        // Create the chart on page load
        var ctx = document.getElementById('medicineChart').getContext('2d');
        medicineChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($descriptions); ?>,
                datasets: [
                    {
                        label: 'Initial Stock',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1,
                        data: <?php echo json_encode($initialStocks); ?>
                    },
                    {
                        label: 'End Stock',
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1,
                        data: <?php echo json_encode($endStocks); ?>
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    x: {
                        beginAtZero: true
                    }
                }
            }
        });
    };

    // Function to filter both the table and chart data
    function filterMedicines() {
        var searchQuery = document.getElementById('searchBar').value.toLowerCase();
        var table = document.getElementById('medicineTable');
        var rows = table.getElementsByTagName('tr');
        var filteredDescriptions = [];
        var filteredInitialStocks = [];
        var filteredEndStocks = [];

        // Loop through table rows to show/hide based on search query
        for (var i = 1; i < rows.length; i++) { // Start at 1 to skip the header
            var descriptionCell = rows[i].getElementsByTagName('td')[0];
            if (descriptionCell) {
                var description = descriptionCell.textContent.toLowerCase();
                if (description.indexOf(searchQuery) > -1) {
                    rows[i].style.display = ""; // Show the row
                    // Store data for filtered chart
                    filteredDescriptions.push(descriptionCell.textContent);
                    filteredInitialStocks.push(parseInt(rows[i].getElementsByTagName('td')[3].textContent));
                    filteredEndStocks.push(parseInt(rows[i].getElementsByTagName('td')[4].textContent));
                } else {
                    rows[i].style.display = "none"; // Hide the row
                }
            }
        }

        // Update the chart with filtered data
        medicineChart.data.labels = filteredDescriptions;
        medicineChart.data.datasets[0].data = filteredInitialStocks;
        medicineChart.data.datasets[1].data = filteredEndStocks;
        medicineChart.update(); // Redraw the chart
    }
</script>

</body>
</html>
