<?php
session_start();
include('db.php');

// Retrieve student ID from GET parameter
$student_id = $_GET['student_id'] ?? null;
if (!$student_id) {
    die("No student selected.");
}

// Fetch all transactions linked to invoices for the student
$query = "SELECT amount, description, date FROM transactions WHERE user_id = ? AND description LIKE ?";
$stmt = $conn->prepare($query);
$description_filter = "%Invoice%";
$stmt->bind_param("is", $student_id, $description_filter);
$stmt->execute();
$results = $stmt->get_result();

echo "<h2>Statement for Student ID: $student_id</h2>";
echo "<table><tr><th>Date</th><th>Description</th><th>Amount</th></tr>";
while ($row = $results->fetch_assoc()) {
    echo "<tr><td>{$row['date']}</td><td>{$row['description']}</td><td>{$row['amount']}</td></tr>";
}
echo "</table>";
?>
