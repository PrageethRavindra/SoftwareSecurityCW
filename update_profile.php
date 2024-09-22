<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];
$new_email = $_POST['email'];
$old_password = $_POST['old_password'];
$new_password = $_POST['new_password'];

// Database connection
$servername = "localhost:3305";
$dbUsername = "root";
$dbPassword = "123@prageeth";
$dbname = "student_details";

$conn = new mysqli($servername, $dbUsername, $dbPassword, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Update email
$sql = "UPDATE users SET email = ? WHERE username = ?";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Prepare failed: " . htmlspecialchars($conn->error));
}
$stmt->bind_param("ss", $new_email, $username);
$stmt->execute();
$stmt->close();

// Update password if provided
if (!empty($old_password) && !empty($new_password)) {
    $sql = "SELECT password FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($hashed_password);
    $stmt->fetch();
    $stmt->close();
    
    // Verify old password
    if (password_verify($old_password, $hashed_password)) {
        $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET password = ? WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $new_hashed_password, $username);
        $stmt->execute();
        $stmt->close();
    } else {
        echo "Old password is incorrect.";
    }
}

$conn->close();
header("Location: dashboard.php"); // Redirect to dashboard after updating
exit();
