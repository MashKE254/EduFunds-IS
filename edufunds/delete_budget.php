<?php
session_start();
include('db.php');

// Ensure the user is logged in
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header("Location: login.php");
    exit;
}

// Check if an ID is provided for the entry to delete
$entry_id = $_GET['id'] ?? null;
if (!$entry_id) {
    die("No budget entry specified.");
}

// Delete the budget entry
$query = "DELETE FROM budget WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $entry_id, $user_id);

if ($stmt->execute()) {
    // Redirect back to the budget page after successful deletion
    header("Location: budget.php");
    exit;
} else {
    die("Error deleting entry: " . $stmt->error);
}
?>
