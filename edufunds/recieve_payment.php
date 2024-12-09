<?php
session_start();
include('db.php');

// Ensure the user is logged in and has access to their school
$school_id = $_SESSION['school_id'] ?? null;
if (!$school_id) {
    echo "Unauthorized access.";
    exit;
}

// Fetch all students for the logged-in admin's school
$query = "SELECT id, first_name, last_name, grade FROM students WHERE school_id = ?";
$stmt = $conn->prepare($query);
$students = [];
if ($stmt) {
    $stmt->bind_param("i", $school_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
    $stmt->close();
} else {
    echo "Failed to fetch students.";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receive Payment</title>
    <link rel="stylesheet" href="styles.css">

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

        .container {
            padding: 30px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            margin: auto;
        }

        h2 {
            color: #333;
            text-align: center;
        }

        form label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }

        form input, form select, button {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        button {
            background-color: #4CAF50;
            color: white;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background-color: #45a049;
        }

        #message {
            text-align: center;
            font-size: 16px;
        }
    </style>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> <!-- jQuery for easier DOM manipulation -->
</head>
<body>

<nav>
    <h2>EduFunds</h2>
    <ul>
        <li><a href="accountant_dashboard.php">Dashboard</a></li>
        <li><a href="transactions.php">Transactions</a></li>
        <li><a href="recieve_payment.php">Receive Payments</a></li>
        <li><a href="budget.php">Budget Planning</a></li>
        <li><a href="logout.php">Logout</a></li>
    </ul>
</nav>

    <div class="container">
        <h2>Receive Payment</h2>
        <form id="paymentForm">
            <label for="student_id">Select Student:</label>
            <select name="student_id" id="student_id" required>
                <option value="">Select a Student</option>
                <?php foreach ($students as $student): ?>
                    <option value="<?php echo $student['id']; ?>">
                        <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name'] . ' - ' . $student['grade']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="amount">Amount:</label>
            <input type="number" name="amount" id="amount" step="0.01" required>

            <label for="description">Description (optional):</label>
            <input type="text" name="description" id="description" placeholder="Payment Description">

            <button type="submit">Submit Payment</button>
        </form>
        <div id="message" style="margin-top: 10px;"></div>
    </div>

    <script>
        $(document).ready(function () {
            $('#paymentForm').on('submit', function (event) {
                event.preventDefault();

                const formData = $(this).serialize();

                $.post('process_payment.php', formData, function (response) {
                    const data = JSON.parse(response);
                    const messageDiv = $('#message');

                    if (data.status === 'success') {
                        messageDiv.text(data.message).css('color', 'green');
                        $('#paymentForm')[0].reset(); // Clear the form
                    } else {
                        messageDiv.text(data.message).css('color', 'red');
                    }
                }).fail(function () {
                    $('#message').text('An unexpected error occurred.').css('color', 'red');
                });
            });
        });
    </script>
</body>
</html>
