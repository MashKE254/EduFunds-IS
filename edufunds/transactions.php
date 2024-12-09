<?php
session_start();
include('db.php');

// Ensure the user is logged in and has a school_id
$school_id = $_SESSION['school_id'] ?? null;
if (!$school_id) {
    header("Location: login.php");
    exit;
}

// Fetch existing transactions
$transactions = [];
$query = "
    SELECT 
        t.id, t.type, t.amount, t.description, t.date, s.first_name, s.last_name 
    FROM 
        transactions t
    LEFT JOIN 
        students s ON t.student_id = s.id
    WHERE 
        t.school_id = ?
    ORDER BY 
        t.date DESC
";
$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->bind_param("i", $school_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $transactions[] = $row;
    }
    $stmt->close();
}

// Fetch existing students
$students = $conn->query("SELECT id, first_name, last_name, grade FROM students WHERE school_id = $school_id")->fetch_all(MYSQLI_ASSOC);

// Fetch existing items
$items = $conn->query("SELECT id, name, rate FROM items WHERE school_id = $school_id")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions & Invoices</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* General styling */
        .tab-button {
            padding: 10px 20px;
            cursor: pointer;
            background-color: #f1f1f1;
            border: none;
            border-bottom: 2px solid transparent;
            transition: 0.3s;
        }

        h1 {
            text-align: center;
            color: #333;
        }

        .tab-button.active {
            border-bottom: 2px solid #4CAF50;
            background-color: #e8e8e8;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

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

        .delete-row {
            cursor: pointer;
            color: red;
        }

        /* Sidebar Navigation */
        nav {
            background-color: #343a40;
            padding: 15px;
            width: 220px;
            position: fixed;
            height: 100%;
            top: 0;
            left: 0;
            z-index: 1000;
        }

        nav h2 {
            color: white;
            text-align: center;
            margin-bottom: 20px;
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
            transition: background-color 0.3s ease;
        }

        nav ul li a:hover {
            background-color: #212529;
        }

        /* Main Content Area */
        .main-content {
            margin-left: 240px;
            padding: 20px;
        }

        /* Modal container styling */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            align-items: center;
            justify-content: center;
        }

        /* Modal content styling */
        .modal-content {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            width: 400px;
            max-width: 90%;
            position: relative;
        }
    </style>
</head>
<body>
<div class="dashboard">
    <h1>Transactions & Invoices</h1>
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

    <div class="main-content">
        <div class="tab-buttons">
            <button class="tab-button active" onclick="switchTab('transactionsTab')">Transactions</button>
            <button class="tab-button" onclick="switchTab('invoicesTab')">Invoices</button>
        </div>

        <!-- Transactions Tab -->
        <div id="transactionsTab" class="tab-content active">
            <h2>Add Transaction</h2>
            <form method="POST" action="add_transaction.php">
                <label for="type">Type:</label>
                <select name="type" id="type" required>
                    <option value="income">Income</option>
                    <option value="expense">Expense</option>
                </select>
                <label for="amount">Amount:</label>
                <input type="number" name="amount" id="amount" required>
                <label for="description">Description:</label>
                <input type="text" name="description" id="description" required>
                <button type="submit">Add Transaction</button>
            </form>

            <h2>Transaction History</h2>
            <table>
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Description</th>
                        <th>Date</th>
                        <th>Student Name</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $transaction): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($transaction['type']); ?></td>
                            <td><?php echo number_format($transaction['amount'], 2); ?></td>
                            <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                            <td><?php echo htmlspecialchars($transaction['date']); ?></td>
                            <td>
                                <?php 
                                    if ($transaction['first_name'] && $transaction['last_name']) {
                                        echo htmlspecialchars($transaction['first_name'] . ' ' . $transaction['last_name']);
                                    } else {
                                        echo "N/A";
                                    }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Invoices Tab -->
        <div id="invoicesTab" class="tab-content">
            <h2>Create Invoice</h2>
            <form id="invoiceForm" method="POST" action="save_invoice.php">
                <label for="student_id">Select Student:</label>
                <select name="student_id" id="student_id" required>
                    <option value="">Select a Student</option>
                    <?php foreach ($students as $student): ?>
                        <option value="<?php echo $student['id']; ?>">
                            <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name'] . ' - ' . $student['grade']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="button" onclick="openAddStudentModal()">+ Add New Student</button>

                <h3>Invoice Items</h3>
                <table id="itemsTable">
                    <thead>
                        <tr>
                            <th>Product/Service</th>
                            <th>Quantity</th>
                            <th>Rate</th>
                            <th>Total</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
                <button type="button" onclick="addItemRow()">Add Item</button>
                <button type="button" onclick="openAddItemModal()">+ Add New Item</button>

        

                <h3>Invoice Summary</h3>
                <p>Subtotal: <span id="subtotal">0.00</span></p>
                <p><strong>Total: <span id="grandTotal">0.00</span></strong></p>

                <!-- Hidden field for total amount -->
                <input type="hidden" name="total_amount" id="total_amount" value="0.00">

                <button type="submit">Save Invoice</button>
            </form>
        </div>
    </div>
</div>

<!-- Modal for adding student -->
<div id="addStudentModal" class="modal">
    <div class="modal-content">
        <h2>Add New Student</h2>
        <form id="addStudentForm" onsubmit="addStudent(event)">
            <label for="student_first_name">First Name:</label>
            <input type="text" id="student_first_name" name="first_name" required>
            <label for="student_last_name">Last Name:</label>
            <input type="text" id="student_last_name" name="last_name" required>
            <label for="student_grade">Grade:</label>
            <input type="text" id="student_grade" name="grade" required>
            <button type="submit">Add Student</button>
        </form>
        <button onclick="closeAddStudentModal()">Close</button>
    </div>
</div>

<!-- Modal for adding item -->
<div id="addItemModal" class="modal">
    <div class="modal-content">
        <h2>Add New Item</h2>
        < id="addItemForm" onsubmit="addItem(event)">
            <label for="item_name">Item Name:</label>
            <input type="text" id="item_name" name="name" required>
            <label for="item_rate">Rate:</label>
            <input type="number" id="item_rate" name="rate" required>
            <button type="submit">Add Item</button>
        </form>
        <button onclick="closeAddItemModal()">Close</button>
    </div>
</div>


<script>
// Function to open the Add Student Modal
function openAddStudentModal() {
    document.getElementById('addStudentModal').style.display = 'block';
}

// Function to close the Add Student Modal
function closeAddStudentModal() {
    document.getElementById('addStudentModal').style.display = 'none';
}

// Function to handle adding a new student via AJAX
document.getElementById('addStudentForm').addEventListener('submit', function (event) {
    event.preventDefault(); // Prevent default form submission

    const formData = new FormData(this);

    fetch('add_student.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            // Add new student to the dropdown
            const studentDropdown = document.getElementById('student_id');
            const newOption = document.createElement('option');
            newOption.value = data.student.id;
            newOption.textContent = `${data.student.first_name} ${data.student.last_name} - ${data.student.grade}`;
            studentDropdown.appendChild(newOption);

            // Close the modal and reset the form
            closeAddStudentModal();
            this.reset();
            alert('Student added successfully!');
        } else {
            alert(data.message || 'Failed to add student.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An unexpected error occurred.');
    });
});

// Function to add a new item row in the invoice
function addItemRow() {
            const tableBody = document.querySelector('#itemsTable tbody');
            const newRow = document.createElement('tr');

            newRow.innerHTML = `
                <td>
                    <select class="itemSelect" required>
                        <option value="">Select Item</option>
                        <?php foreach ($items as $item): ?>
                            <option value="<?php echo $item['id']; ?>" data-rate="<?php echo $item['rate']; ?>">
                                <?php echo htmlspecialchars($item['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td><input type="number" class="quantity" value="1" required /></td>
                <td><span class="rate">0.00</span></td>
                <td><span class="total">0.00</span></td>
                <td><button type="button" onclick="deleteItemRow(this)">Delete</button></td>
            `;
            
            tableBody.appendChild(newRow);
            updateInvoiceSummary();
        }

        // Function to delete an item row
        function deleteItemRow(button) {
            const row = button.closest('tr');
            row.remove();
            updateInvoiceSummary();
        }

        // Function to update the invoice summary
        function updateInvoiceSummary() {
            let subtotal = 0;

            document.querySelectorAll('#itemsTable tbody tr').forEach(row => {
                const quantity = parseFloat(row.querySelector('.quantity').value) || 0;
                const rate = parseFloat(row.querySelector('.itemSelect').selectedOptions[0].dataset.rate) || 0;
                const total = quantity * rate;

                row.querySelector('.rate').textContent = rate.toFixed(2);
                row.querySelector('.total').textContent = total.toFixed(2);

                subtotal += total;
            });

            const grandTotal = subtotal;

            document.getElementById('subtotal').textContent = subtotal.toFixed(2);
            document.getElementById('grandTotal').textContent = grandTotal.toFixed(2);

            // Update the hidden total_amount input field
            document.getElementById('total_amount').value = grandTotal.toFixed(2);
        }

        // Modal handling functions
        function openAddItemModal() {
            document.getElementById('addItemModal').style.display = 'flex';
        }

        function closeAddItemModal() {
            document.getElementById('addItemModal').style.display = 'none';
        }


// Function to switch between tabs
function switchTab(tabId) {
    document.querySelectorAll('.tab-button').forEach(button => {
        button.classList.remove('active');
    });

    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });

    document.querySelector(`#${tabId}`).classList.add('active');
    document.querySelector(`button[onclick="switchTab('${tabId}')"]`).classList.add('active');
}
</script>

</body>
</html>
