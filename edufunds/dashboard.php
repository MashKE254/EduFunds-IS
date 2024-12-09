<?php
session_start();
include('db.php');

// Ensure the user is logged in
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header("Location: login.php");
    exit;
}

// Fetch user details for display
$query_user = "SELECT username, email FROM users WHERE user_id = ?";
$stmt_user = $conn->prepare($query_user);
if (!$stmt_user) {
    die("Error preparing user query: " . $conn->error);
}
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
if ($result_user) {
    $user_data = $result_user->fetch_assoc();
    $username = $user_data['username'] ?? '';
    $email = $user_data['email'] ?? '';
} else {
    die("Error fetching user data: " . $conn->error);
}
$stmt_user->close();

// Fetch total income and expenses for the user
$total_income = 0;
$total_expenses = 0;

// Income query
$query_income = "SELECT SUM(amount) AS total FROM transactions WHERE user_id = ? AND type = 'income'";
$stmt_income = $conn->prepare($query_income);
if (!$stmt_income) {
    die("Error preparing income query: " . $conn->error);
}
$stmt_income->bind_param("i", $user_id);
$stmt_income->execute();
$result_income = $stmt_income->get_result();
$total_income = $result_income->fetch_assoc()['total'] ?? 0;
$stmt_income->close();

// Expenses query
$query_expenses = "SELECT SUM(amount) AS total FROM transactions WHERE user_id = ? AND type = 'expense'";
$stmt_expenses = $conn->prepare($query_expenses);
if (!$stmt_expenses) {
    die("Error preparing expenses query: " . $conn->error);
}
$stmt_expenses->bind_param("i", $user_id);
$stmt_expenses->execute();
$result_expenses = $stmt_expenses->get_result();
$total_expenses = $result_expenses->fetch_assoc()['total'] ?? 0;
$stmt_expenses->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduFunds Dashboard</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Improved styling for username and edit profile section */
        .user-info {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background-color: #f4f4f4;
            padding: 10px 15px;
            border-radius: 8px;
            margin-top: 15px;
            color: #333;
        }

        .user-info .username {
            font-size: 18px;
            font-weight: bold;
            color: #555;
        }

        .user-info .btn {
            padding: 8px 12px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
            transition: background-color 0.3s ease;
        }

        .user-info .btn:hover {
            background-color: #45a049;
        }

        .dashboard {
            font-family: Arial, sans-serif;
            margin: 20px auto;
        }

        .dashboard-cards {
            display: flex;
            gap: 20px;
            margin-top: 20px;
        }

        .card {
            flex: 1;
            background-color: #fff;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            text-align: center;
        }

        .card h3 {
            margin: 0 0 10px;
            color: #4CAF50;
        }

        .card p {
            font-size: 20px;
            font-weight: bold;
            color: #555;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <nav>
            <h2>EduFunds</h2>
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="transactions.php">Transactions</a></li>
                <li><a href="recieve_payment.php">Receive Payments</a></li>
                <li><a href="budget.php">Budget Planning</a></li>
                <li><a href="report.php">Reports</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
        <!-- User Info Section -->
        <div class="user-info">
            <span class="username"><?php echo htmlspecialchars($username); ?></span>
            <a href="edit_profile.php" class="btn">Edit Profile</a>
        </div>
        <section class="main-content">
            <h1>Welcome to EduFunds</h1>
            <p>Manage your school's finances with ease.</p>

            <!-- Dashboard summary section -->
            <div class="dashboard-cards">
                <div class="card">
                    <h3>Total Income</h3>
                    <p id="totalIncome">$<?php echo number_format($total_income, 2); ?></p>
                </div>
                <div class="card">
                    <h3>Total Expenses</h3>
                    <p id="totalExpenses">$<?php echo number_format($total_expenses, 2); ?></p>
                </div>
            </div>
        </section>
    </div>
</body>
</html>
