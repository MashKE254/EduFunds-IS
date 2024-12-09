<?php
session_start();
include('db.php');

// Ensure the accountant is logged in and has a school_id
$school_id = $_SESSION['school_id'] ?? null;
if (!$school_id) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category = $_POST['category'];
    $amount = $_POST['amount'];
    $term = $_POST['term'];
    $status = 'pending';  // Default status

    $stmt = $conn->prepare("INSERT INTO budget (school_id, category, amount, term, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param('isdss', $school_id, $category, $amount, $term, $status);
    
    if ($stmt->execute()) {
        header("Location: budget.php");
        exit;
    } else {
        echo "Failed to create budget.";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Budget</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            padding: 20px;
        }
        form {
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        label, input, select {
            display: block;
            margin-bottom: 10px;
        }
        input, select {
            width: 100%;
            padding: 10px;
        }
        button {
            background-color: #28a745;
            color: white;
            padding: 10px;
            border: none;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <h1>Create Budget</h1>
    <form method="POST" action="">
        <label for="category">Category:</label>
        <input type="text" name="category" id="category" required>
        
        <label for="amount">Amount:</label>
        <input type="number" name="amount" id="amount" step="0.01" required>
        
        <label for="term">Term:</label>
        <select name="term" id="term" required>
            <option value="Term 1">Term 1</option>
            <option value="Term 2">Term 2</option>
            <option value="Term 3">Term 3</option>
        </select>
        
        <button type="submit">Create Budget</button>
    </form>
</body>
</html>
