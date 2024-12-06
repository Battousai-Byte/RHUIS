<?php
require '../include/db_connection.php'; // Your database connection file

if (isset($_GET['description'])) {
    $description = $_GET['description'];

    $stmt = $pdo->prepare("SELECT total_stock FROM medicines WHERE description = :description");
    $stmt->bindParam(':description', $description, PDO::PARAM_STR);
    $stmt->execute();
    $medicine = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode($medicine);
}
?>

