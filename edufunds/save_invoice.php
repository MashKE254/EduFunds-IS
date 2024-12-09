<?php 
session_start();
include('db.php');  // Ensure this contains the correct database connection

// Ensure user is logged in
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header("Location: login.php");
    exit;
}

// Validate form data (basic validation)
if (isset($_POST['student_id'], $_POST['total_amount'])) {
    $student_id = $_POST['student_id'];
    $total = $_POST['total_amount']; // Correct the key name

    // Check that the data is valid (e.g., student_id is a valid integer, total_amount is numeric)
    if (!is_numeric($student_id) || !is_numeric($total) || $total <= 0) {
        echo "Invalid data provided.";
        exit;
    }

    $subtotal = $total; // Assuming no tax for simplicity; adjust as needed
    $tax = 0; // Set tax to 0 for now
    $date = date('Y-m-d');  // Get the current date

    // Insert the invoice into the database
    $query = "INSERT INTO invoices (user_id, student_id, subtotal, tax, total, created_at) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);

    if ($stmt) {
        $stmt->bind_param("iiddds", $user_id, $student_id, $subtotal, $tax, $total, $date);

        // Execute and check if the query was successful
        if ($stmt->execute()) {
            // Redirect to the report page after successfully saving the invoice
            header("Location: report.php");
            exit;
        } else {
            // Error handling in case of failed query execution
            echo "Error: " . $stmt->error;
        }

        $stmt->close();
    } else {
        // Error handling if the prepared statement fails
        echo "Failed to prepare the SQL statement.";
    }
} else {
    // Handle missing POST data
    echo "Required data is missing.";
}
?>
