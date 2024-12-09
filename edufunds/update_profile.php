<?php
session_start();
include('db.php');

// Ensure the user is logged in
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header("Location: login.php");
    exit;
}

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Validate required fields
    if (empty($username) || empty($email)) {
        die("Username and email are required.");
    }

    // Hash the password if it was provided
    $hashed_password = null;
    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
    }

    // Update user details in the database
    $update_query = "UPDATE users SET username = ?, email = ?";
    $params = ["ss", $username, $email];

    // Add password to the query if provided
    if ($hashed_password) {
        $update_query .= ", password = ?";
        $params[0] .= "s";
        $params[] = $hashed_password;
    }

    $update_query .= " WHERE user_id = ?";
    $params[0] .= "i";
    $params[] = $user_id;

    // Prepare and execute the query
    $stmt = $conn->prepare($update_query);
    if (!$stmt) {
        die("Error preparing query: " . $conn->error);
    }

    $stmt->bind_param(...$params);
    if ($stmt->execute()) {
        // Redirect back to the dashboard with a success message
        $_SESSION['success_message'] = "Profile updated successfully!";
        header("Location: dashboard.php");
        exit;
    } else {
        die("Error updating profile: " . $stmt->error);
    }
} else {
    // Redirect to the edit profile page if accessed directly
    header("Location: edit_profile.php");
    exit;
}
?>
