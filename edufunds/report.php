<?php
session_start();
include('db.php');

// Ensure user is logged in
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    echo "User not logged in.";
    exit;
}

// Fetch all invoices for the logged-in user
$query = "SELECT i.id, s.first_name, s.last_name, s.grade, i.total AS total_amount
          FROM invoices i
          JOIN students s ON i.student_id = s.id
          WHERE i.user_id = $user_id";
$result = $conn->query($query);

// Check if the query was successful
if (!$result) {
    die("Query failed: " . $conn->error);
}

// Calculate total of all invoices
$total_amount = 0;
if ($result->num_rows > 0) {
    while ($invoice = $result->fetch_assoc()) {
        $total_amount += $invoice['total_amount'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduFunds - Financial Reports</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <!-- Add SheetJS for Excel export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.2/xlsx.full.min.js"></script>

    <style> 

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

    </style>


</head>
<body>
    <div class="report-container">
    <nav>
            
            <h2>EduFunds Admin</h2>
                        <ul>
                            <li><a href="admin_dashboard.php">Dashboard</a></li>
                            <li><a href="admin_budgets.php">Budgets</a></li>
                            <li><a href="report.php">Reports</a></li>
                            <li><a href="logout.php">Logout</a></li>
                        </ul>
                    </nav>
            
    
        <h2>Financial Reports</h2>

        <!-- Income vs Expense Report Section -->
<section>
    <h3>Income vs Expense</h3>
    <p>Analyze your financial performance by comparing income and expenses.</p>
    <a href="income_expense.php">
        <button>View Income vs Expense Report</button>
    </a>
</section>
    
        <!-- Open Invoices Section -->
        <section>
            <h3>Open Invoices</h3>
            <!-- Button to redirect to the open invoices page -->
            <a href="open_invoices.php">
                <button>View Open Invoices</button>
            </a>
        </section>
    
        <!-- Invoices Table Section -->
        <section>
            <h3>Invoices</h3>
            <!-- Button to redirect to student invoices page -->
            <a href="student_invoices.php">
                <button>View Invoices</button>
            </a>
        </section>
    </div>

    <script>
        // Function to export the invoices table to Excel (not used in this version, but can be extended)
        function exportToExcel() {
            var table = document.getElementById('invoicesTable');
            var wb = XLSX.utils.table_to_book(table, { sheet: "Invoices" });
            XLSX.writeFile(wb, "Invoices_Report.xlsx");
        }
    </script>

    <script src="report.js"></script>
</body>
</html>
