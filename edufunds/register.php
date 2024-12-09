<?php
session_start();
include 'db.php'; // Database connection

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get and sanitize form input
    $school_name = trim($_POST['school_name']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT); // Hash password

    // Check if the school name already exists
    $check_school_query = "SELECT * FROM schools WHERE school_name = ?";
    $stmt_check_school = $conn->prepare($check_school_query);
    $stmt_check_school->bind_param("s", $school_name);
    $stmt_check_school->execute();
    $result_check_school = $stmt_check_school->get_result();

    if ($result_check_school->num_rows > 0) {
        // If the school already exists
        echo "This school name is already taken. Please choose a different name.";
    } else {
        // Insert new school into the schools table
        $stmt_school = $conn->prepare("INSERT INTO schools (school_name) VALUES (?)");
        $stmt_school->bind_param("s", $school_name);
        if ($stmt_school->execute()) {
            $school_id = $stmt_school->insert_id; // Get the newly inserted school_id

            // Insert admin into the users table
            $stmt_user = $conn->prepare("INSERT INTO users (first_name, last_name, username, password, role, email, school_id) 
                                         VALUES (?, ?, ?, ?, 'Admin', ?, ?)");
            $stmt_user->bind_param("sssssi", $first_name, $last_name, $school_name, $password, $email, $school_id);
            if ($stmt_user->execute()) {
                // Successful registration
                echo "Registration successful!, Login <a href='login.php'>Login here</a>";
            } else {
                // Error inserting user
                echo "Error inserting user: " . $stmt_user->error;
            }
        } else {
            // Error inserting school
            echo "Error inserting school: " . $stmt_school->error;
        }
    }
}
?>