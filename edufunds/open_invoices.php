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

// Fetch all open invoices with a remaining balance, filtered by admin's school_id
$query = "
    SELECT 
        i.id AS invoice_id,
        s.first_name,
        s.last_name,
        s.grade,
        i.total - i.amount_paid AS balance
    FROM 
        invoices i
    JOIN 
        students s 
    ON 
        i.student_id = s.id
    WHERE 
        i.amount_paid < i.total
    AND 
        s.school_id = ?
    ORDER BY 
        s.last_name ASC, s.first_name ASC
";

$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->bind_param("i", $school_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Fetch data into an array and calculate the total balance
    $open_invoices = $result->fetch_all(MYSQLI_ASSOC);
    $total_balance = 0;

    foreach ($open_invoices as $invoice) {
        $total_balance += $invoice['balance'];
    }

    $stmt->close();
} else {
    die("Failed to prepare the SQL query: " . $conn->error);
}

// Handle delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_invoice_id'])) {
    $delete_invoice_id = $_POST['delete_invoice_id'];

    // SQL to delete the invoice
    $delete_query = "DELETE FROM invoices WHERE id = ?";
    $delete_stmt = $conn->prepare($delete_query);

    if ($delete_stmt) {
        $delete_stmt->bind_param("i", $delete_invoice_id);
        $delete_stmt->execute();
        $delete_stmt->close();

        // Redirect to refresh the page and show updated data
        header("Location: open_invoices.php");
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
    <title>Open Invoices</title>
    <style>
          body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex; /* Use flex layout for nav and content */
        }

        nav {
            width: 220px; /* Fixed width for navigation */
            background-color: #343a40;
            color: white;
            padding: 20px;
            height: 100vh; /* Full viewport height */
            box-sizing: border-box;
            position: fixed; /* Make it fixed to the left */
            top: 0;
            left: 0;
        }

        nav h2 {
            color: white;
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
            padding: 10px;
            display: block;
            border-radius: 4px;
            background-color: #495057;
            text-align: center;
            transition: background-color 0.3s;
        }

        nav ul li a:hover {
            background-color: #212529;
        }

        .container {
            margin-left: 240px; /* Offset content to the right of the nav */
            padding: 20px;
            width: calc(100% - 240px); /* Adjust content width */
            box-sizing: border-box;
        }

        h1 {
            text-align: center;
            color: #333;
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

        .no-data {
            text-align: center;
            font-size: 18px;
            color: #666;
        }

        .grand-total-container {
            text-align: right;
            margin-top: 10px;
            font-size: 16px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Open Invoices</h1>
        <nav>
            <h2>Admin Navigation</h2>
            <ul>
                <li><a href="admin_dashboard.php">Dashboard</a></li>
                <li><a href="admin_budgets.php">Budgets</a></li>
                <li><a href="report.php">Reports</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>

         <!-- Filter Inputs -->
         <div class="filter-container">
            <input type="text" id="filterStudent" class="filter-input" placeholder="Filter by Student Name">
            <input type="text" id="filterGrade" class="filter-input" placeholder="Filter by Grade">
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
                    <th onclick="sortTable(2)">Balance</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($open_invoices)): ?>
                    <?php foreach ($open_invoices as $invoice): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($invoice['first_name'] . ' ' . $invoice['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($invoice['grade']); ?></td>
                            <td><?php echo number_format($invoice['balance'], 2); ?></td>
                            <td>
                                <form method="POST" action="open_invoices.php" onsubmit="return confirm('Are you sure you want to delete this invoice?');">
                                    <input type="hidden" name="delete_invoice_id" value="<?php echo $invoice['invoice_id']; ?>">
                                    <button type="submit">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="no-data">No open invoices found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Grand Total -->
        <div class="grand-total-container">
    Grand Total: <span id="grandTotal"><?php echo number_format($total_balance, 2); ?></span>
</div>

    </div>

    <script>
        // Sort Table Functionality
        function sortTable(columnIndex) {
            const table = document.getElementById("invoiceTable");
            const rows = Array.from(table.rows).slice(1); // Exclude header row
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


    // Filter Functionality with Grand Total Update
    function updateGrandTotal() {
        const rows = document.querySelectorAll("#invoiceTable tbody tr");
        let newGrandTotal = 0;

        rows.forEach(row => {
            if (row.style.display !== "none") {
                const balanceCell = row.cells[2]; // Balance is in the 3rd column
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
            let csvContent = "data:text/csv;charset=utf-8,Student Name,Grade,Balance\n";

            rows.forEach(row => {
                const cells = Array.from(row.querySelectorAll('td:not(:last-child)'));
                const rowData = cells.map(cell => cell.textContent.trim());
                csvContent += rowData.join(",") + "\n";
            });

            const encodedUri = encodeURI(csvContent);
            const link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", "open_invoices.csv");
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });

    </script>
</body>
</html>
