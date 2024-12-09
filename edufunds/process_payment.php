<?php
session_start();
include('db.php');

// Ensure the user is logged in and has a school_id
$school_id = $_SESSION['school_id'] ?? null;
if (!$school_id) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit;
}

// Validate POST inputs
$student_id = $_POST['student_id'] ?? null;
$amount = $_POST['amount'] ?? null;
$description = $_POST['description'] ?? 'Payment Received';

if (!$student_id || !$amount || $amount <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input data.']);
    exit;
}

// Insert the transaction
$query = "
    INSERT INTO transactions (school_id, type, amount, description, date, student_id)
    VALUES (?, 'income', ?, ?, CURRENT_DATE(), ?)
";
$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->bind_param("idss", $school_id, $amount, $description, $student_id);
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Payment recorded successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to record payment.']);
    }
    $stmt->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Database query failed.']);
}
?>
