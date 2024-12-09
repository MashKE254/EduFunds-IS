<?php
session_start();
include('db.php');

// Ensure the user is logged in
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header("Location: login.php");
    exit;
}

// Check if an ID is provided for the entry to edit
$entry_id = $_GET['id'] ?? null;
if (!$entry_id) {
    die("No budget entry specified.");
}

// Retrieve the existing data for this entry
$query = "SELECT category, amount FROM budget WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $entry_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$entry = $result->fetch_assoc();

if (!$entry) {
    die("Budget entry not found.");
}

$stmt->close();

// Update the entry if the form has been submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['category']) && isset($_POST['amount'])) {
    $category = $_POST['category'];
    $amount = $_POST['amount'];

    // Check if amount is a valid positive number
    if (!is_numeric($amount) || $amount <= 0) {
        die("Amount must be a positive number.");
    }

    // Update the budget entry
    $update_query = "UPDATE budget SET category = ?, amount = ? WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("sdii", $category, $amount, $entry_id, $user_id);
    
    if ($stmt->execute()) {
        // Redirect back to the budget page after successful update
        header("Location: budget.php");
        exit;
    } else {
        die("Error updating entry: " . $stmt->error);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Budget Entry</title>
</head>
<body>
    <h2>Edit Budget Entry</h2>
    <form method="POST" action="edit_budget.php?id=<?php echo $entry_id; ?>">
        <label for="category">Category:</label>
        <input type="text" name="category" id="category" value="<?php echo htmlspecialchars($entry['category']); ?>" required>
        <br>
        <label for="amount">Amount:</label>
        <input type="number" name="amount" id="amount" value="<?php echo number_format($entry['amount'], 2); ?>" required>
        <br>
        <button type="submit">Update Entry</button>
        <a href="budget.php">Cancel</a>
    </form>
</body>
</html>