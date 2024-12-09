<?php
session_start();
include('db.php');

// Ensure the admin is logged in
$school_id = $_SESSION['school_id'] ?? null;
if (!$school_id) {
    $_SESSION['error'] = "Please log in to view and manage budgets.";
    header("Location: login.php");
    exit;
}

// Handle status update if requested
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['budget_id'], $_POST['status'])) {
    $budget_id = (int)$_POST['budget_id'];
    $status = $_POST['status']; // 'approved' or 'rejected'

    if (in_array($status, ['approved', 'rejected'], true)) {
        $query = "UPDATE budget SET status = ? WHERE id = ? AND school_id = ?";
        $stmt = $conn->prepare($query);

        if ($stmt) {
            $stmt->bind_param('sii', $status, $budget_id, $school_id);
            if ($stmt->execute()) {
                $_SESSION['success'] = "Budget status updated successfully.";
            } else {
                $_SESSION['error'] = "Failed to update budget status.";
            }
            $stmt->close();
        } else {
            $_SESSION['error'] = "Database error while updating status.";
        }
    } else {
        $_SESSION['error'] = "Invalid status.";
    }

    header("Location: admin_budgets.php");
    exit;
}

// Fetch budgets belonging to the admin's school
$query = "SELECT id, category, amount, term, status, created_at FROM budget WHERE school_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($query);
$budgets = [];

if ($stmt) {
    $stmt->bind_param('i', $school_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $budgets[] = $row;
    }
    $stmt->close();
} else {
    $_SESSION['error'] = "Failed to fetch budgets.";
}

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="budgets.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Category', 'Amount', 'Term', 'Status', 'Created At']);

    foreach ($budgets as $budget) {
        fputcsv($output, [
            $budget['category'],
            number_format($budget['amount'], 2),
            $budget['term'],
            $budget['status'],
            $budget['created_at']
        ]);
    }

    fclose($output);
    exit;
}

$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Budgets</title>
    <style>
 body {
        font-family: Arial, sans-serif;
        background-color: #f8f9fa;
        margin: 0;
        padding: 0;
    }

    h1 {
        text-align: center;
        color: #333;
        margin-top: 20px;
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

    /* Main Content */
    .content {
        margin-left: 240px;
        padding: 20px;
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


    button {
        padding: 8px 12px;
        background-color: #28a745;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        margin-right: 5px;
    }

    button:hover {
        background-color: #218838;
    }

    button[name="status"][value="rejected"] {
        background-color: #dc3545;
    }

    button[name="status"][value="rejected"]:hover {
        background-color: #c82333;
    }

    p {
        text-align: center;
        font-weight: bold;
    }

    p[style="color: green;"] {
        color: #28a745;
    }

    p[style="color: red;"] {
        color: #dc3545;
    }

    form {
        display: inline-block;
        margin: 0;
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

<div class="content">
<h1>Manage Budgets</h1>

<?php if (isset($_SESSION['success'])): ?>
    <p style="color: green;"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></p>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <p style="color: red;"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></p>
<?php endif; ?>

 <!-- Export Button -->
 <div class="export-button-container">
        <a href="admin_budgets.php?export=csv">
            <button>Export to CSV</button>
        </a>
    </div>

<table border="1">
    <thead>
        <tr>
            <th>Category</th>
            <th>Amount</th>
            <th>Term</th>
            <th>Status</th>
            <th>Created At</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($budgets)): ?>
            <tr>
                <td colspan="6">No budgets found.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($budgets as $budget): ?>
                <tr>
                    <td><?php echo htmlspecialchars($budget['category']); ?></td>
                    <td><?php echo number_format($budget['amount'], 2); ?></td>
                    <td><?php echo htmlspecialchars($budget['term']); ?></td>
                    <td><?php echo htmlspecialchars($budget['status']); ?></td>
                    <td><?php echo htmlspecialchars($budget['created_at']); ?></td>
                    <td>
                        <?php if ($budget['status'] === 'pending'): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="budget_id" value="<?php echo $budget['id']; ?>">
                                <button type="submit" name="status" value="approved">Approve</button>
                                <button type="submit" name="status" value="rejected">Reject</button>
                            </form>
                        <?php else: ?>
                            <?php echo ucfirst($budget['status']); ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

</body>
</html>