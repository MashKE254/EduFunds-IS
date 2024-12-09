<?php
include('db.php');
session_start();

$user_id = $_SESSION['user_id'];
$school_id = $result_school->fetch_assoc()['school_id'] ?? null;

/* logic to fetch user's school_id */

// Fetch total income
$query_income = "SELECT SUM(amount) AS total FROM transactions WHERE school_id = ? AND type = 'income'";
$stmt_income = $conn->prepare($query_income);
$stmt_income->bind_param("i", $school_id);
$stmt_income->execute();
$total_income = $stmt_income->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_income->close();

// Fetch total expenses
$query_expenses = "SELECT SUM(amount) AS total FROM transactions WHERE school_id = ? AND type = 'expense'";
$stmt_expenses = $conn->prepare($query_expenses);
$stmt_expenses->bind_param("i", $school_id);
$stmt_expenses->execute();
$total_expenses = $stmt_expenses->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_expenses->close();

echo json_encode(['total_income' => $total_income, 'total_expenses' => $total_expenses]);
?>
