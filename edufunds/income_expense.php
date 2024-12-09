<?php
session_start();
include('db.php');

// Ensure the user is logged in
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header("Location: login.php");
    exit;
}

// Fetch the admin's school_id
$query_school_id = "SELECT school_id FROM users WHERE user_id = ? AND role = 'Admin'";
$stmt_school_id = $conn->prepare($query_school_id);
if ($stmt_school_id) {
    $stmt_school_id->bind_param("i", $user_id);
    $stmt_school_id->execute();
    $result_school_id = $stmt_school_id->get_result();
    $admin = $result_school_id->fetch_assoc();
    $school_id = $admin['school_id'] ?? null;
    $stmt_school_id->close();
}

if (!$school_id) {
    die("Error: Admin's school_id not found.");
}

// Fetch income and expense totals filtered by school_id
$income_query = "SELECT SUM(amount) AS total_income FROM transactions WHERE type = 'income' AND school_id = ?";
$expense_query = "SELECT SUM(amount) AS total_expense FROM transactions WHERE type = 'expense' AND school_id = ?";

// Prepare and execute income query
$income_stmt = $conn->prepare($income_query);
if (!$income_stmt) {
    die("Failed to prepare income statement: " . $conn->error);
}
$income_stmt->bind_param("i", $school_id);
if (!$income_stmt->execute()) {
    die("Failed to execute income query: " . $income_stmt->error);
}
$income_result = $income_stmt->get_result();
$income_row = $income_result->fetch_assoc();
$income = $income_row['total_income'] ?? 0;
$income_result->free();
$income_stmt->close();

// Prepare and execute expense query
$expense_stmt = $conn->prepare($expense_query);
if (!$expense_stmt) {
    die("Failed to prepare expense statement: " . $conn->error);
}
$expense_stmt->bind_param("i", $school_id);
if (!$expense_stmt->execute()) {
    die("Failed to execute expense query: " . $expense_stmt->error);
}
$expense_result = $expense_stmt->get_result();
$expense_row = $expense_result->fetch_assoc();
$expense = $expense_row['total_expense'] ?? 0;
$expense_result->free();
$expense_stmt->close();

// Handle delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_transaction_id'])) {
    $delete_transaction_id = $_POST['delete_transaction_id'];

    // SQL to delete the transaction
    $delete_query = "DELETE FROM transactions WHERE id = ?";
    $delete_stmt = $conn->prepare($delete_query);

    if ($delete_stmt) {
        $delete_stmt->bind_param("i", $delete_transaction_id);
        $delete_stmt->execute();
        $delete_stmt->close();

        // Redirect to refresh the page and show updated data
        header("Location: income_expense.php");
        exit;
    } else {
        die("Failed to prepare delete query: " . $conn->error);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Income vs Expense Report</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
         nav {
            background-color: #343a40;
            padding: 15px;
            width: 220px;
            position: fixed;
            height: 100%;
            top: 0;
            left: 0;
        }

        nav ul {
            list-style: none;
            padding: 0;
        }

        nav ul li {
            margin: 15px 0;
        }

        nav ul li a {
            color: white;
            text-decoration: none;
            display: block;
            padding: 12px;
            background-color: #495057;
            border-radius: 4px;
            text-align: center;
        }

        nav ul li a:hover {
            background-color: #212529;
        }
        .container {
            margin: 20px auto;
            max-width: 800px;
            text-align: center;
            font-family: Arial, sans-serif;
        }
        .chart-container {
            margin: 20px auto;
            width: 100%;
            max-width: 600px;
        }
        table {
            margin: 20px auto;
            width: 100%;
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 8px;
            text-align: center;
        }
        th {
            background-color: #f4f4f4;
        }
        button {
            font-size: 12px;
            padding: 4px 8px;
            border-radius: 4px;
            background-color: red;
            color: white;
            border: none;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="container">
        <nav>
        <h2>Admin Navigation</h2>
            <ul>
                <li><a href="admin_dashboard.php">Dashboard</a></li>
                <li><a href="admin_budgets.php">Budgets</a></li>
                <li><a href="report.php">Reports</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>

        <div class="chart-container">
            <canvas id="incomeExpenseChart"></canvas>
        </div>

        <h2>Transaction Details</h2>
        <table>
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Description</th>
                    <th>Date</th>
                    <th>Student Name</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $transactions_query = "
                    SELECT 
                        t.id, 
                        t.type, 
                        t.description, 
                        t.amount, 
                        t.date, 
                        s.first_name, 
                        s.last_name 
                    FROM 
                        transactions t 
                    LEFT JOIN 
                        students s 
                    ON 
                        t.student_id = s.id 
                    WHERE 
                        t.school_id = ? 
                    ORDER BY 
                        t.date DESC";
                $transactions_stmt = $conn->prepare($transactions_query);
                $transactions_stmt->bind_param("i", $school_id);
                $transactions_stmt->execute();
                $transactions_result = $transactions_stmt->get_result();
                while ($row = $transactions_result->fetch_assoc()): 
                ?>
                    <tr>
                        <td><?= htmlspecialchars($row['type']); ?></td>
                        <td><?= number_format($row['amount'], 2); ?></td>
                        <td><?= htmlspecialchars($row['description']); ?></td>
                        <td><?= htmlspecialchars($row['date']); ?></td>
                        <td>
                            <?= $row['first_name'] && $row['last_name'] 
                                ? htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) 
                                : 'N/A'; ?>
                        </td>
                        <td>
                            <form method="POST" action="income_expense.php" onsubmit="return confirm('Are you sure you want to delete this transaction?');">
                                <input type="hidden" name="delete_transaction_id" value="<?= $row['id']; ?>">
                                <button type="submit">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
                <?php $transactions_stmt->close(); ?>
            </tbody>
        </table>
    </div>

    <script>
        const income = <?= $income; ?>;
        const expense = <?= $expense; ?>;

        const ctx = document.getElementById('incomeExpenseChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Income', 'Expense'],
                datasets: [{
                    label: 'Amount',
                    data: [income, expense],
                    backgroundColor: ['#4CAF50', '#FF5733'],
                    borderColor: ['#4CAF50', '#FF5733'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Amount'
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
