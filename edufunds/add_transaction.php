<?php
session_start();
include('db.php');

$school_id = $_SESSION['school_id'] ?? null;
if (!$school_id) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'];
    $amount = $_POST['amount'];
    $description = $_POST['description'];
    
    $query = "INSERT INTO transactions (type, amount, description, date, school_id) VALUES (?, ?, ?, NOW(), ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sdss", $type, $amount, $description, $school_id);
    $stmt->execute();
    
    header("Location: transactions.php");
    exit;
}
?>
