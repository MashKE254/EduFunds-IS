<?php
session_start();
include('db.php');

$admin_id = $_SESSION['user_id'] ?? null;
$school_id = $_SESSION['school_id'] ?? null;

if (!$admin_id || !$school_id) {
    header("Location: login.php");
    exit;
}

$budget_id = $_GET['id'] ?? null;
$new_status = $_GET['status'] ?? null;

if ($budget_id && in_array($new_status, ['approved', 'rejected'])) {
    $query = "UPDATE budget SET status = ? WHERE id = ? AND school_id = ?";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("sii", $new_status, $budget_id, $school_id);
        if ($stmt->execute()) {
            $_SESSION['success'] = "Budget status updated successfully!";
        } else {
            $_SESSION['error'] = "Failed to update budget status.";
        }
        $stmt->close();
    }
}

$conn->close();
header("Location: admin_budgets.php");
exit;
?>
