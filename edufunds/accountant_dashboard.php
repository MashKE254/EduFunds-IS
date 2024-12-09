<?php
session_start();
include('db.php');

// Check if the user is logged in and has the 'Accountant' role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Accountant') {
    header("Location: login.php");
    exit;
}

// Fetch the logged-in accountant's school details
$school_id = $_SESSION['school_id'];
$query_school = "SELECT school_name FROM schools WHERE school_id = ?";
$stmt_school = $conn->prepare($query_school);
$stmt_school->bind_param("i", $school_id);
$stmt_school->execute();
$result_school = $stmt_school->get_result();
$school = $result_school->fetch_assoc();
$stmt_school->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <title>Accountant Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 0;
        }

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

        .main-content {
            margin-left: 240px;
            padding: 20px;
        }

        .dashboard-container {
            padding: 30px;
        }

        .dashboard-container h1 {
            color: #333;
        }

        .dashboard-cards {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
            margin-top: 20px;
        }

        .card {
            flex: 1 1 calc(50% - 40px); /* Two cards per row, adjusting for margins */
            max-width: calc(50% - 40px); /* Limits max width for larger screens */
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 30px;
            text-align: center;
        }

        .card h3 {
            margin-top: 0;
            color: #4CAF50;
        }

        .card p {
            font-size: 18px;
            color: #555;
        }

        .btn {
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 16px;
        }

        .btn:hover {
            background-color: #45a049;
        }

        @media (max-width: 768px) {
            .card {
                flex: 1 1 100%; /* Make cards take full width on smaller screens */
                max-width: 100%;
            }
        }
    </style>
</head>
<body>

<nav>
    <h2>EduFunds</h2>
    <ul>
        <li><a href="dashboard.php">Dashboard</a></li>
        <li><a href="transactions.php">Transactions</a></li>
        <li><a href="recieve_payment.php">Receive Payments</a></li>
        <li><a href="budget.php">Budget Planning</a></li>
        <li><a href="logout.php">Logout</a></li>
    </ul>
</nav>

<div class="main-content">
    <h1>Welcome to the Accountant Dashboard</h1>
    <div class="dashboard-cards">
        <div class="card">
            <h3>School: <?php echo htmlspecialchars($school['school_name']); ?></h3>
            <p>As an accountant, you can manage financial transactions for your school.</p>
        </div>

        <div class="card">
            <h3>Manage Transactions</h3>
            <a href="transactions.php" class="btn">Add Transaction</a>
            <p>View and manage transactions related to your school.</p>
        </div>
    </div>
</div>

</body>
</html>
