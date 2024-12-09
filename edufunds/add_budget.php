<?php
session_start();
include('db.php');

$school_id = $_SESSION['school_id'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;

if (!$school_id || !$user_id) {
    $_SESSION['error'] = "Missing school or user ID. Please log in again.";
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $category = $_POST['category'] ?? '';
    $amount = $_POST['amount'] ?? 0;
    $term = $_POST['term'] ?? '';
    
    // Validation
    if (empty($category) || empty($amount) || empty($term)) {
        $_SESSION['error'] = "All fields are required.";
        header("Location: budget.php");
        exit;
    }

    $query = "INSERT INTO budget (user_id, school_id, category, amount, term, status) VALUES (?, ?, ?, ?, ?, 'pending')";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param('iisds', $user_id, $school_id, $category, $amount, $term);
        if ($stmt->execute()) {
            $_SESSION['success'] = "Budget submitted successfully!";
        } else {
            $_SESSION['error'] = "Error executing query: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $_SESSION['error'] = "Failed to prepare statement: " . $conn->error;
    }
    $conn->close();
    header("Location: budget.php");
    exit;
}
?>
