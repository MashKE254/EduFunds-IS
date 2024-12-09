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

// Fetch student invoices grouped by student and filtered by admin's school_id
$query = "
    SELECT 
        i.id AS invoice_id,
        s.first_name,
        s.last_name,
        s.grade,
        SUM(i.total) AS total_amount
    FROM 
        invoices i
    JOIN 
        students s 
    ON 
        i.student_id = s.id
    WHERE 
        s.school_id = ?
    GROUP BY 
        s.id, s.first_name, s.last_name, s.grade, i.id
    ORDER BY 
        s.last_name ASC, s.first_name ASC
";

$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->bind_param("i", $school_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $student_invoices = $result->fetch_all(MYSQLI_ASSOC);
    $grand_total = 0;

    foreach ($student_invoices as $invoice) {
        $grand_total += $invoice['total_amount'];
    }

    $stmt->close();
} else {
    die("SQL Query preparation failed: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Invoices</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
        }

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

        .container {
            margin-left: 240px;
            padding: 20px;
            width: calc(100% - 240px);
            box-sizing: border-box;
        }

        h1 {
            text-align: center;
        }

        .export-button-container {
            text-align: right;
            margin-bottom: 10px;
        }

        button {
            padding: 8px 12px;
            border: none;
            background-color: #007bff;
            color: white;
            border-radius: 4px;
            cursor: pointer;
        }

        button:hover {
            background-color: #0056b3;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            text-align: left;
            padding: 8px;
            border: 1px solid #ddd;
        }

        th {
            background-color: #4CAF50;
            color: white;
            cursor: pointer;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        tr:hover {
            background-color: #f1f1f1;
        }

        .grand-total-container {
            text-align: right;
            margin-top: 10px;
            font-size: 16px;
            font-weight: bold;
        }

        .no-data {
            text-align: center;
            color: #666;
        }
    </style>
</head>
<body>
    <nav>
        <h2>Admin Navigation</h2>
        <ul>
            <li><a href="admin_dashboard.php">Dashboard</a></li>
            <li><a href="admin_budgets.php">Budgets</a></li>
            <li><a href="report.php">Reports</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>

    <div class="container">
        <h1>Student Invoices</h1>

        <!-- Filter Inputs -->
        <div class="filter-container">
            <input type="text" id="filterStudent" placeholder="Filter by Student Name">
            <input type="text" id="filterGrade" placeholder="Filter by Grade">
        </div>

        <!-- Export Button -->
        <div class="export-button-container">
            <button id="exportCSV">Export to CSV</button>
        </div>

        <!-- Invoice Table -->
        <table id="invoiceTable">
            <thead>
                <tr>
                    <th onclick="sortTable(0)">Student Name</th>
                    <th onclick="sortTable(1)">Grade</th>
                    <th onclick="sortTable(2)">Total Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($student_invoices)): ?>
                    <?php foreach ($student_invoices as $invoice): ?>
                        <tr>
                            <td><?= htmlspecialchars($invoice['first_name'] . ' ' . $invoice['last_name']); ?></td>
                            <td><?= htmlspecialchars($invoice['grade']); ?></td>
                            <td><?= number_format($invoice['total_amount'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" class="no-data">No invoices found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Grand Total -->
        <div class="grand-total-container">
            Grand Total: <span id="grandTotal"><?= number_format($grand_total, 2); ?></span>
        </div>
    </div>

    <script>
        // Sort Table Functionality
        function sortTable(columnIndex) {
            const table = document.getElementById("invoiceTable");
            const rows = Array.from(table.rows).slice(1);
            const isAscending = table.dataset.sortOrder !== "asc";

            rows.sort((a, b) => {
                const aText = a.cells[columnIndex].innerText.toLowerCase();
                const bText = b.cells[columnIndex].innerText.toLowerCase();
                return isAscending
                    ? aText.localeCompare(bText, undefined, { numeric: true })
                    : bText.localeCompare(aText, undefined, { numeric: true });
            });

            rows.forEach(row => table.tBodies[0].appendChild(row));
            table.dataset.sortOrder = isAscending ? "asc" : "desc";
        }

        // Filter and Update Grand Total
        function updateGrandTotal() {
            const rows = document.querySelectorAll("#invoiceTable tbody tr");
            let newGrandTotal = 0;

            rows.forEach(row => {
                if (row.style.display !== "none") {
                    const balanceCell = row.cells[2];
                    const balance = parseFloat(balanceCell.innerText.replace(/,/g, '')) || 0;
                    newGrandTotal += balance;
                }
            });

            document.getElementById("grandTotal").innerText = newGrandTotal.toFixed(2);
        }

        document.getElementById("filterStudent").addEventListener("input", function () {
            const filter = this.value.toLowerCase();
            const rows = document.querySelectorAll("#invoiceTable tbody tr");

            rows.forEach(row => {
                const studentName = row.cells[0].innerText.toLowerCase();
                row.style.display = studentName.includes(filter) ? "" : "none";
            });

            updateGrandTotal();
        });

        document.getElementById("filterGrade").addEventListener("input", function () {
            const filter = this.value.toLowerCase();
            const rows = document.querySelectorAll("#invoiceTable tbody tr");

            rows.forEach(row => {
                const grade = row.cells[1].innerText.toLowerCase();
                row.style.display = grade.includes(filter) ? "" : "none";
            });

            updateGrandTotal();
        });

        // Export to CSV Functionality
        document.getElementById('exportCSV').addEventListener('click', function () {
            const table = document.getElementById('invoiceTable');
            const rows = Array.from(table.querySelectorAll('tbody tr'));
            let csvContent = "data:text/csv;charset=utf-8,Student Name,Grade,Total Amount\n";

            rows.forEach(row => {
                const cells = Array.from(row.querySelectorAll('td'));
                const rowData = cells.map(cell => cell.textContent.trim());
                csvContent += rowData.join(",") + "\n";
            });

            const encodedUri = encodeURI(csvContent);
            const link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", "student_invoices.csv");
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });
    </script>
</body>
</html>
