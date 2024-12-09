<?php
session_start();
include('db.php');

$school_id = $_SESSION['school_id'];

$income_query = "SELECT SUM(amount) AS income FROM transactions WHERE school_id = ? AND type = 'income'";
$expense_query = "SELECT SUM(amount) AS expenses FROM transactions WHERE school_id = ? AND type = 'expense'";

$stmt_income = $conn->prepare($income_query);
$stmt_expense = $conn->prepare($expense_query);

$stmt_income->bind_param("i", $school_id);
$stmt_expense->bind_param("i", $school_id);

$stmt_income->execute();
$stmt_expense->execute();

$income = $stmt_income->get_result()->fetch_assoc()['income'] ?? 0;
$expenses = $stmt_expense->get_result()->fetch_assoc()['expenses'] ?? 0;

echo json_encode(['income' => $income, 'expenses' => $expenses]);
?>
