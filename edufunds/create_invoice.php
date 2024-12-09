<?php
session_start();
include('db.php');

// Check user login status
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header("Location: login.php");
    exit;
}

// Capture form data for creating an invoice
$student_id = $_POST['student_id'] ?? null;
$amount = $_POST['amount'] ?? null;
$description = $_POST['description'] ?? null;

if (!$student_id || !$amount || !is_numeric($amount)) {
    die("Invalid input.");
}

// Insert the invoice into the invoices table
$query_invoice = "INSERT INTO invoices (student_id, amount, description, date) VALUES (?, ?, ?, NOW())";
$stmt_invoice = $conn->prepare($query_invoice);
$stmt_invoice->bind_param("ids", $student_id, $amount, $description);
$stmt_invoice->execute();
$invoice_id = $stmt_invoice->insert_id;
$stmt_invoice->close();

// Also record this invoice as income in the transactions table
$query_transaction = "INSERT INTO transactions (user_id, type, amount, description, date) VALUES (?, 'income', ?, ?, NOW())";
$stmt_transaction = $conn->prepare($query_transaction);
$income_description = "Invoice: " . $description;
$stmt_transaction->bind_param("ids", $user_id, $amount, $income_description);
$stmt_transaction->execute();
$stmt_transaction->close();

echo "Invoice created successfully!";
?>
