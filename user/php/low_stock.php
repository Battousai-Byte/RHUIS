<?php
// Include the database connection
require '../../includes/db_connection.php';

// Include the rule-based functions
require_once 'rule-based.php';

// Fetch low stock medicines using the function from rule-based.php
$lowStockMedicines = checkLowStock($conn);

// Separate into two arrays
$availableMedicines = [];
$zeroStockMedicines = [];

foreach ($lowStockMedicines as $medicine) {
    if ($medicine['total_stock'] > 0) {
        $availableMedicines[] = $medicine;
    } else {
        $zeroStockMedicines[] = $medicine;
    }
}

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Low Stock Medicines</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="https://unpkg.com/boxicons/css/boxicons.min.css">
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
            margin-left: 210px; /* Adjust margin to account for sidebar width */
            padding: 20px;
        }

        .no-data {
            text-align: center;
            color: #888;
            padding: 20px;
        }
    </style>
</head>
<body>
    <?php require '../sidebar.php'; ?>
    <div class="content-wrapper">
        <?php require '../navbar.php'; ?>
        <div class="main-content">
            <div class="low-stock-container">
                <h1>Low Stock Medicines</h1>
                <?php if (!empty($availableMedicines)): ?>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Description</th>
                                <th>Unit Measurement</th>
                                <th>Remaining Quantity</th>
                                <th>Threshold</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($availableMedicines as $medicine): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($medicine['description']); ?></td>
                                    <td><?php echo htmlspecialchars($medicine['unit_measurement']); ?></td>
                                    <td><?php echo htmlspecialchars($medicine['total_stock']); ?></td>
                                    <td><?php echo htmlspecialchars($medicine['threshold']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-data">No available low stock medicines.</div>
                <?php endif; ?>

                <?php if (!empty($zeroStockMedicines)): ?>
                    <h2>No Available Stocks</h2>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Description</th>
                                <th>Unit Measurement</th>
                                <th>Remaining Quantity</th>
                                <th>Threshold</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($zeroStockMedicines as $medicine): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($medicine['description']); ?></td>
                                    <td><?php echo htmlspecialchars($medicine['unit_measurement']); ?></td>
                                    <td><?php echo htmlspecialchars($medicine['total_stock']); ?></td>
                                    <td><?php echo htmlspecialchars($medicine['threshold']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-data">All medicines are currently stocked.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- JavaScript for Dropdown -->
    <script src="../js/script.js"></script>
</body>
</html>
