<?php
include('../../includes/db_connection.php');
session_start();

// Redirect to login page if not logged in

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables
$selected_month = isset($_POST['month']) ? $_POST['month'] : date('Y-m');

// Fetch budget allocation data based on selected month
$sql = "SELECT a.allocation_id, a.created_at, a.total_budget, a.medicine_id, a.reorder_quantity, a.total_cost, m.description,m.unit_measurement,m.unit_price 
        FROM budget_allocation a
        JOIN medicine m ON a.medicine_id = m.medicine_id
        WHERE DATE_FORMAT(a.created_at, '%Y-%m') = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $selected_month);
$stmt->execute();
$result = $stmt->get_result();

// Fetch total budget for display
$total_budget_query = "SELECT DISTINCT total_budget FROM budget_allocation WHERE DATE_FORMAT(created_at, '%Y-%m') = ?";
$budget_stmt = $conn->prepare($total_budget_query);
$budget_stmt->bind_param("s", $selected_month);
$budget_stmt->execute();
$budget_result = $budget_stmt->get_result();
$total_budget_row = $budget_result->fetch_assoc();
$total_budget = $total_budget_row ? number_format($total_budget_row['total_budget'], 2) : '0.00';

// Check if data exists
$has_data = $result->num_rows > 0;

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Budget Allocation</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="https://unpkg.com/boxicons/css/boxicons.min.css">
    <style>
        .main-content {
            margin-left: 210px;
            padding: 20px;
            background-color: #ecf0f1;
            min-height: 100vh;
            width: calc(100% - 210px);
        }
        .container {
            max-width: 100%;
        }
        .h1{
    font-size: 42px;
    color:#2C3E50;
}
    </style>
</head>
<body>
    <?php require '../sidebar.php'; ?>
    <?php require '../navbar.php'; ?>
    <div class="main-content">
        <div class="container mt-5">
            <h2>View Budget Allocation</h2>
            
            <!-- Date Selection Form -->
            <form method="post" action="view_budget_allocation.php" class="mb-4">
                <div class="form-group">
                    <label for="month">Select Month:</label>
                    <input type="month" id="month" name="month" class="form-control" value="<?php echo htmlspecialchars($selected_month); ?>" onchange="this.form.submit()" required>
                </div>
            </form>

            <!-- Display Total Budget -->
            <h4>Budget Allocated for <?php echo date('F Y', strtotime($selected_month)); ?>: Php <?php echo htmlspecialchars($total_budget); ?></h4>

            <?php if ($has_data): ?>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th>Unit Measurement</th>
                            <th>Unit Price</th>
                            <th>Reorder Quantity</th>
                            <th>Total Cost</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['description']); ?></td>
                                <td><?php echo htmlspecialchars($row['unit_measurement']); ?></td>
                                <td><?php echo htmlspecialchars($row['unit_price']); ?></td>
                                <td><?php echo htmlspecialchars($row['reorder_quantity']); ?></td>
                                <td><?php echo htmlspecialchars(number_format($row['total_cost'], 2)); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="alert alert-warning" role="alert">
                    No budget allocation data available for the selected month. Budget has not been allocated.
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
