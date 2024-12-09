<?php
session_start();
include('db.php');

// Ensure the user is logged in and has a school_id
$school_id = $_SESSION['school_id'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;

if (!$school_id || !$user_id) {
    header("Location: login.php");
    exit;
}

// Fetch existing budgets for the school
$budgets = [];
$query = "
    SELECT id, category, amount, term, status, created_at 
    FROM budget 
    WHERE school_id = ? AND user_id = ?
    ORDER BY created_at DESC
";
$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->bind_param("ii", $school_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $budgets[] = $row;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Budget Planning</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Reuse existing styles */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
        }
        th {
            background-color: #f4f4f4;
            text-align: left;
        }
        .main-content {
            margin-left: 240px;
            padding: 20px;
        }
    </style>
</head>
<body>
    <nav>
        <h2>EduFunds</h2>
        <ul>
            <li><a href="accountant_dashboard.php">Dashboard</a></li>
            <li><a href="transactions.php">Transactions</a></li>
            <li><a href="recieve_payment.php">Receive Payments</a></li>
            <li><a href="accountant_budget.php" class="active">Budget Planning</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>

    <div class="main-content">
        <h1>Budget Planning</h1>

        <h2>Add New Budget</h2>
        <form method="POST" action="add_budget.php">
            <label for="category">Category:</label>
            <input type="text" name="category" id="category" required>

            <label for="amount">Amount:</label>
            <input type="number" name="amount" id="amount" required>

            <label for="term">Term:</label>
            <select name="term" id="term" required>
                <option value="Term 1">Term 1</option>
                <option value="Term 2">Term 2</option>
                <option value="Term 3">Term 3</option>
            </select>

            <button type="submit">Submit Budget</button>
        </form>

        <h2>Budget History</h2>
        <table>
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Amount</th>
                    <th>Term</th>
                    <th>Status</th>
                    <th>Date Created</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($budgets as $budget): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($budget['category']); ?></td>
                        <td><?php echo number_format($budget['amount'], 2); ?></td>
                        <td><?php echo htmlspecialchars($budget['term']); ?></td>
                        <td><?php echo ucfirst($budget['status']); ?></td>
                        <td><?php echo htmlspecialchars($budget['created_at']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
