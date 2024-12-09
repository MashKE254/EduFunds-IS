<?php
session_start();
include('db.php');

header('Content-Type: application/json'); // Ensure JSON response

$school_id = $_SESSION['school_id'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;

if (!$school_id || !$user_id) {
    echo json_encode(['status' => 'error', 'message' => 'Missing school or user ID.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $grade = trim($_POST['grade'] ?? '');

    if (empty($first_name) || empty($last_name) || empty($grade)) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
        exit;
    }

    $query = "INSERT INTO students (user_id, school_id, first_name, last_name, grade) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);

    if (!$stmt) {
        echo json_encode(['status' => 'error', 'message' => 'SQL prepare failed: ' . $conn->error]);
        exit;
    }

    $stmt->bind_param('iisss', $user_id, $school_id, $first_name, $last_name, $grade);

    if (!$stmt->execute()) {
        echo json_encode(['status' => 'error', 'message' => 'SQL execute failed: ' . $stmt->error]);
        exit;
    }

    $new_student_id = $stmt->insert_id;
    $stmt->close();
    $conn->close();

    echo json_encode([
        'status' => 'success',
        'message' => 'Student added successfully!',
        'student' => [
            'id' => $new_student_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'grade' => $grade
        ]
    ]);
    exit;
}
?>
