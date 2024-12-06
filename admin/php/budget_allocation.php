<?php 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require '../../includes/db_connection.php';
    $budget = $_POST['budget'];
    $remaining_budget = $budget;

    $thresholds = [
        'box' => 10,
        'btls' => 100,
        'btl' => 100,
        'amp' => 100,
        'pc' => 200,
        'tablet' => 100,
        'tab' => 100
    ];

    // Query to get the average usage from history with medicine_id
    $sql_usage_history = "SELECT m.medicine_id, AVG(uh.quantity) AS avg_usage
                          FROM medicine_usage_history uh
                          JOIN medicine m ON uh.medicine_id = m.medicine_id
                          GROUP BY m.medicine_id";

    $result_usage_history = mysqli_query($conn, $sql_usage_history);
    $avg_usage = [];
    
    while ($row = mysqli_fetch_assoc($result_usage_history)) {
        $avg_usage[$row['medicine_id']] = (float)$row['avg_usage'];
    }

    // Main query to get medicine details
    $sql = "SELECT m.medicine_id, m.description, SUM(m.stock_quantity) AS total_stock_quantity, 
            m.unit_price, m.quantity_per_carton, m.unit_measurement
            FROM medicine m
            GROUP BY m.medicine_id, m.description, m.unit_price, m.quantity_per_carton, m.unit_measurement";

    $result = mysqli_query($conn, $sql);
    $medicines_to_order = [];
    $total_order_cost = 0;

    // Calculate preliminary reorder quantities and costs
    while ($row = mysqli_fetch_assoc($result)) {
        $medicine_id = $row['medicine_id'];
        $description = $row['description'];
        $quantity_per_carton = $row['quantity_per_carton'];
        $unit_price = (float)$row['unit_price'];
        $unit_measurement = strtoupper($row['unit_measurement']);
        $total_stock = (int)$row['total_stock_quantity'];

        if (isset($thresholds[strtolower($unit_measurement)])) {
            $threshold = $thresholds[strtolower($unit_measurement)];

            if ($total_stock <= $threshold) {
                $average_usage = isset($avg_usage[$medicine_id]) ? $avg_usage[$medicine_id] : $threshold;
                $preliminary_reorder_quantity = max($average_usage * 2, $threshold);

                $total_cost = $preliminary_reorder_quantity * $unit_price;

                if ($total_cost > 0) {
                    $medicines_to_order[$medicine_id] = [
                        'medicine_id' => $medicine_id,
                        'description' => $description,
                        'unit_price' => $unit_price,
                        'quantity_per_carton' => $quantity_per_carton,
                        'unit_measurement' => $unit_measurement,
                        'preliminary_reorder_quantity' => $preliminary_reorder_quantity,
                        'total_cost' => $total_cost
                    ];

                    $total_order_cost += $total_cost;
                }
            }
        }
    }

    // Distribute budget proportionally and adjust to fit exact budget
    $adjusted_medicines = [];
    $total_cost_adjusted = 0;

    foreach ($medicines_to_order as $medicine) {
        $proportion = $medicine['total_cost'] / $total_order_cost;
        $adjusted_total_cost = $proportion * $budget;
        $adjusted_reorder_quantity = floor($adjusted_total_cost / $medicine['unit_price']);
        $final_total_cost = $adjusted_reorder_quantity * $medicine['unit_price'];

        if ($adjusted_reorder_quantity > 0) {
            // Group by description
            $description = $medicine['description'];
            if (!isset($adjusted_medicines[$description])) {
                $adjusted_medicines[$description] = [
                    'medicine_id' => $medicine['medicine_id'],
                    'description' => $description,
                    'unit_measurement' => $medicine['unit_measurement'],
                    'unit_price' => $medicine['unit_price'],
                    'reorder_quantity' => 0,
                    'total_cost' => 0
                ];
            }
            $adjusted_medicines[$description]['reorder_quantity'] += $adjusted_reorder_quantity;
            $adjusted_medicines[$description]['total_cost'] += $final_total_cost;

            $total_cost_adjusted += $final_total_cost;
        }
    }

    // Adjust final total cost to match exactly the budget
    if ($total_cost_adjusted < $budget) {
        $remaining_budget = $budget - $total_cost_adjusted;
        
        usort($adjusted_medicines, function($a, $b) {
            return ($b['total_cost'] / $b['reorder_quantity']) - ($a['total_cost'] / $a['reorder_quantity']);
        });

        foreach ($adjusted_medicines as &$medicine) {
            if ($remaining_budget <= 0) break;
            
            $unit_price = $medicine['total_cost'] / $medicine['reorder_quantity'];
            $additional_quantity = floor($remaining_budget / $unit_price);
            
            if ($additional_quantity > 0) {
                $additional_cost = $additional_quantity * $unit_price;
                $medicine['reorder_quantity'] += $additional_quantity;
                $medicine['total_cost'] += $additional_cost;
                $remaining_budget -= $additional_cost;
                $total_cost_adjusted += $additional_cost;
            }
        }
    }

    // Insert allocated budget data into the database only once
    $insert_sql = "INSERT INTO budget_allocation (total_budget, medicine_id, reorder_quantity, total_cost) VALUES (?, ?, ?, ?)";

    // Prepare the statement once
    $stmt = mysqli_prepare($conn, $insert_sql);

    // Loop through adjusted medicines and insert
    foreach ($adjusted_medicines as $medicine) {
        // Bind parameters and execute the statement for each medicine
        mysqli_stmt_bind_param($stmt, "idii", 
            $budget,
            $medicine['medicine_id'],
            $medicine['reorder_quantity'],
            $medicine['total_cost']
        );

        // Execute the prepared statement
        if (!mysqli_stmt_execute($stmt)) {
            // Handle error if insert fails
            echo "Error inserting data: " . mysqli_error($conn);
        }
    }

    // Close the statement after the loop
    mysqli_stmt_close($stmt);

    // Insert total budget only if not already present
    $check_sql = "SELECT COUNT(*) AS count FROM budget_allocation WHERE total_budget = ?";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "d", $budget);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    $check_row = mysqli_fetch_assoc($check_result);
    
    if ($check_row['count'] == 0) {
        // Insert total budget allocation record
        $insert_budget_sql = "INSERT INTO budget_allocation (total_budget) VALUES (?)";
        $insert_budget_stmt = mysqli_prepare($conn, $insert_budget_sql);
        mysqli_stmt_bind_param($insert_budget_stmt, "d", $budget);
        mysqli_stmt_execute($insert_budget_stmt);
        mysqli_stmt_close($insert_budget_stmt);
    }

    mysqli_stmt_close($check_stmt);
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Budget Allocation</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/styles.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        body {
            display: flex;
            margin: 0;
            font-family: Arial, sans-serif;
        }

        .content-wrapper {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .main-content {
            margin-left: 210px;
            padding: 20px;
            background-color: #ecf0f1;
            min-height: 100vh;
        }
        .h1 {
            font-size: 42px;
            color: #2C3E50;
        }
        
        .form-container input[type="number"] {
            width: 150px;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            margin-right: 10px;
        }

        .form-container button {
            background-color: #007bff;
            color: #fff;
            padding: 10px 20px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            border-radius: 4px;
            transition: background-color 0.3s ease;
        }

        .form-container button:hover {
            background-color: #0056b3;
        }

        .no-medicines {
            text-align: center;
            font-size: 18px;
            color: #777;
            margin-top: 20px;
        }

        .budget-info {
            margin-top: 20px;
            font-size: 18px;
            font-weight: bold;
        }
    </style>
</head>
<body>
<?php require '../sidebar.php'; ?>

    <div class="content-wrapper">
    <?php require '../navbar.php'; ?>
        <div class="main-content">
            <h1>Budget Allocation for Medicines</h1>
            <div class="form-container">
                <form method="POST" action="">
                    <label for="budget">Total Budget:</label>
                    <input type="number" name="budget" id="budget" step="0.01" required>
                    <button type="submit">Submit</button>
                </form>
            </div>
            <div class="result-container">
            <?php 
                // Assume $total_budget is set from the form submission
                $total_budget = isset($_POST['budget']) ? $_POST['budget'] : 0;
                $total_cost = isset($adjusted_medicines) ? array_sum(array_column($adjusted_medicines, 'total_cost')) : 0;
                $remaining_budget = $total_budget - $total_cost;
            ?>
            <?php if (isset($adjusted_medicines) && count($adjusted_medicines) > 0): ?>
                <h2>Recommended Medicines to Reorder</h2>
                <p>As of <?php echo date('F Y'); ?></p>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th>Reorder Quantity</th>
                            <th>Total Cost</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($adjusted_medicines as $medicine): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($medicine['description']); ?></td>
                                <td><?php echo htmlspecialchars($medicine['reorder_quantity']); ?></td>
                                <td><?php echo htmlspecialchars(number_format($medicine['total_cost'], 2)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-medicines">No medicines need to be reordered at this time.</p>
            <?php endif; ?>

            <!-- Display budget information -->
            <div class="budget-info">
                <p>Total Budget Allocated: <?php echo number_format($total_budget, 2); ?> PHP</p>
                <p>Total Cost: <?php echo number_format($total_cost, 2); ?> PHP</p>
                <?php if ($remaining_budget > 0): ?>
                    <p>Remaining Budget: <?php echo number_format($remaining_budget, 2); ?> PHP</p>
                <?php else: ?>
                    <p style="color: red;">Over Budget by: <?php echo number_format(abs($remaining_budget), 2); ?> PHP</p>
                <?php endif; ?>
            </div>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
