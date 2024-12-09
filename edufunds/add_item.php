<?php
session_start();
include('db.php');

// Ensure the user is logged in and has a school_id
$school_id = $_SESSION['school_id'] ?? null;
if (!$school_id) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit;
}

// Check if the form has been submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve the form data
    $name = $_POST['name'] ?? '';
    $rate = $_POST['rate'] ?? 0;

    // Validate input
    if (empty($name) || $rate <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid item name or rate']);
        exit;
    }

    // Insert the item into the database
    $query = "INSERT INTO items (name, rate, school_id) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("sdi", $name, $rate, $school_id);
        $stmt->execute();

        // Fetch the newly inserted item to return as a response
        $item_id = $stmt->insert_id;
        $item = [
            'id' => $item_id,
            'name' => $name,
            'rate' => number_format($rate, 2)
        ];

        $stmt->close();

        // Return the newly added item as a JSON response
        echo json_encode(['status' => 'success', 'item' => $item]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to add item']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>
