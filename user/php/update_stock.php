<?php
// Database connection
require 'db_connection.php'; // Adjust the path as necessary

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the posted data
    $medicine_id = intval($_POST['medicine_id']);
    $quantity = intval($_POST['quantity']);

    // Check if valid data
    if ($medicine_id > 0 && $quantity > 0) {
        // Update stock quantity in the database
        $updateQuery = "UPDATE medicine SET stock_quantity = stock_quantity - ? WHERE medicine_id = ?";
        
        if ($stmt = $conn->prepare($updateQuery)) {
            $stmt->bind_param("ii", $quantity, $medicine_id);
            if ($stmt->execute()) {
                // Successfully updated
                echo json_encode(["success" => true]);
            } else {
                // Error during execution
                echo json_encode(["success" => false, "error" => $stmt->error]);
            }
            $stmt->close();
        } else {
            echo json_encode(["success" => false, "error" => $conn->error]);
        }
    } else {
        echo json_encode(["success" => false, "error" => "Invalid input."]);
    }
} else {
    echo json_encode(["success" => false, "error" => "Invalid request method."]);
}

$conn->close();
?>
