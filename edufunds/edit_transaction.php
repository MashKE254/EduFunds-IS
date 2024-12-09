<?php
session_start();
include('db.php');

// Ensure the user is logged in
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header("Location: login.php");
    exit;
}

// Check if an ID is provided for the transaction to edit
$transaction_id = $_GET['id'] ?? null;
if (!$transaction_id) {
    die("No transaction specified.");
}

// Retrieve the existing data for this transaction
$query = "SELECT type, amount, description FROM transactions WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $transaction_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$transaction = $result->fetch_assoc();

if (!$transaction) {
    die("Transaction not found.");
}

$stmt->close();

// Update the transaction if the form has been submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['type']) && isset($_POST['amount']) && isset($_POST['description'])) {
    $type = $_POST['type'];
    $amount = $_POST['amount'];
    $description = $_POST['description'];

    // Validate amount
    if (!is_numeric($amount) || $amount <= 0) {
        die("Amount must be a positive number.");
    }

    // Update the transaction in the database
    $update_query = "UPDATE transactions SET type = ?, amount = ?, description = ? WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("sdssi", $type, $amount, $description, $transaction_id, $user_id);

    if ($stmt->execute()) {
        // Redirect back to the transactions page after successful update
        header("Location: transactions.php");
        exit;
    } else {
        die("Error updating transaction: " . $stmt->error);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Transaction</title>
</head>
<body>
    <h2>Edit Transaction</h2>
    <form method="POST" action="edit_transaction.php?id=<?php echo $transaction_id; ?>">
        <label for="type">Type:</label>
        <select name="type" id="type" required>
            <option value="income" <?php if ($transaction['type'] === 'income') echo 'selected'; ?>>Income</option>
            <option value="expense" <?php if ($transaction['type'] === 'expense') echo 'selected'; ?>>Expense</option>
        </select>
        <br>
        
        <label for="amount">Amount:</label>
        <input type="number" name="amount" id="amount" value="<?php echo htmlspecialchars($transaction['amount']); ?>" required>
        <br>
        
        <label for="description">Description:</label>
        <input type="text" name="description" id="description" value="<?php echo htmlspecialchars($transaction['description']); ?>" required>
        <br>
        
        <button type="submit">Update Transaction</button>
        <a href="transactions.php">Cancel</a>
    </form>
</body>
</html>
