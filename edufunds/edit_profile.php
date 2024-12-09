<?php
session_start();
include('db.php');

// Ensure the user is logged in
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header("Location: login.php");
    exit;
}

// Fetch existing user details
$query_user = "SELECT username, email FROM users WHERE user_id = ?";
$stmt_user = $conn->prepare($query_user);
if (!$stmt_user) {
    die("Error preparing user query: " . $conn->error);
}
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
if ($result_user) {
    $user_data = $result_user->fetch_assoc();
    $username = $user_data['username'] ?? '';
    $email = $user_data['email'] ?? '';
} else {
    die("Error fetching user data: " . $conn->error);
}
$stmt_user->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="edit-profile-container">
        <h2>Edit Profile</h2>
        <form action="update_profile.php" method="post">
            <label for="username">Username:</label>
            <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($username); ?>" required>

            <label for="email">Email:</label>
            <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($email); ?>" required>

            <label for="password">New Password:</label>
            <input type="password" name="password" id="password" placeholder="Leave blank to keep current password">

            <button type="submit" class="btn">Save Changes</button>
            <a href="dashboard.php" class="btn cancel">Cancel</a>
        </form>
    </div>
</body>
</html>