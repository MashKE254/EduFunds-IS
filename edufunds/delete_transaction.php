<?php
session_start();
include('db.php');

// Ensure the user is logged in
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header("Location: login.php");
    exit;
}

// Check if an ID is provided for the transaction to delete
$transaction_id = $_GET['id'] ?? null;
if (!$transaction_id) {
    die("No transaction specified.");
}

// Delete the transaction if it belongs to the user
$query = "DELETE FROM transactions WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $transaction_id, $user_id);

if ($stmt->execute()) {
    // Redirect back to the transactions page after successful deletion
    header("Location: transactions.php");
    exit;
} else {
    die("Error deleting transaction: " . $stmt->error);
}

$stmt->close();
?>