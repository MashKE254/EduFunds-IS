<?php
session_start();
include('db.php');

// Ensure the user is logged in and has the 'Admin' role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit;
}

// Fetch the admin's school details
$school_id = $_SESSION['school_id'];
$query_school = "SELECT school_name FROM schools WHERE school_id = ?";
$stmt_school = $conn->prepare($query_school);
$stmt_school->bind_param("i", $school_id);
$stmt_school->execute();
$result_school = $stmt_school->get_result();
$school = $result_school->fetch_assoc();
$stmt_school->close();

// Fetch total income
$query_income = "SELECT SUM(amount) AS total FROM transactions WHERE school_id = ? AND type = 'income'";
$stmt_income = $conn->prepare($query_income);
$stmt_income->bind_param("i", $school_id);
$stmt_income->execute();
$result_income = $stmt_income->get_result();
$total_income = $result_income->fetch_assoc()['total'] ?? 0;
$stmt_income->close();

// Fetch total expenses
$query_expenses = "SELECT SUM(amount) AS total FROM transactions WHERE school_id = ? AND type = 'expense'";
$stmt_expenses = $conn->prepare($query_expenses);
$stmt_expenses->bind_param("i", $school_id);
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
    <title>Admin Dashboard</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
    /* General Styles */
    body {
        font-family: Arial, sans-serif;
        background-color: #f4f4f9;
        margin: 0;
        padding: 0;
    }

   /* Sidebar Navigation */
nav {
    width: 220px;
    background-color: #343a40;
    color: white;
    padding: 20px;
    height: 100vh;
    box-sizing: border-box;
    position: fixed;
    top: 0;
    left: 0;
}

nav h2 {
    text-align: center;
    margin: 0;
    padding-bottom: 20px;
}

nav ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

nav ul li {
    margin: 20px 0;
}

nav ul li a {
    color: white;
    text-decoration: none;
    display: block;
    background-color: #495057;
    border-radius: 4px;
    padding: 10px;
    text-align: center;
}

nav ul li a:hover {
    background-color: #212529;
}

    /* Dashboard Container */
    .dashboard-container {
        margin-left: 240px;
        padding: 30px;
        background-color: white;
    }

    .dashboard-container h1 {
        color: #333;
        font-size: 28px;
        margin-bottom: 20px;
    }

    /* Card Styles */
    .card {
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        margin-bottom: 20px;
        padding: 20px;
        text-align: center;
    }

    .card h3 {
        color: #4CAF50;
        font-size: 22px;
        margin-bottom: 10px;
    }

    .card p {
        font-size: 20px;
        font-weight: bold;
        color: #555;
    }

    /* Button Styles */
    .btn {
        padding: 10px 20px;
        background-color: #4CAF50;
        color: white;
        text-decoration: none;
        border-radius: 4px;
        transition: background-color 0.3s ease;
    }

    .btn:hover {
        background-color: #45a049;
    }

    /* Responsive Styles */
    @media (max-width: 768px) {
        nav {
            width: 100%;
            height: auto;
            position: relative;
        }

        .navbar {
            padding: 10px;
        }

        .dashboard-container {
            margin-left: 0;
            padding: 15px;
        }

        .card {
            margin-bottom: 15px;
        }
    }
</style>

</head>
<body>
<nav>
            
<h2>EduFunds Admin</h2>
            <ul>
                <li><a href="admin_dashboard.php">Dashboard</a></li>
                <li><a href="admin_budgets.php">Budgets</a></li>
                <li><a href="report.php">Reports</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>

    <div class="dashboard-container">
        <h1>Welcome, Admin</h1>
        <div class="card">
            <h3>School: <?php echo htmlspecialchars($school['school_name']); ?></h3>
            <p>Manage your school's finances.</p>
        </div>

        <div class="card">
            <h3>Total Income</h3>
            <p id="totalIncome">$<?php echo number_format($total_income, 2); ?></p>
        </div>

        <div class="card">
            <h3>Total Expenses</h3>
            <p id="totalExpenses">$<?php echo number_format($total_expenses, 2); ?></p>
        </div>

        <div class="card">
            <a href="add_accountant.php" class="btn">Add Accountant</a>
        </div>
    </div>

    <script>
        function updateDashboard() {
            $.get("fetch_totals.php", function(data) {
                const totals = JSON.parse(data);
                $("#totalIncome").text(`$${parseFloat(totals.income).toFixed(2)}`);
                $("#totalExpenses").text(`$${parseFloat(totals.expense).toFixed(2)}`);
            });
        }

        // Poll for updates every 5 seconds
        setInterval(updateDashboard, 5000);
    </script>
</body>
</html>
