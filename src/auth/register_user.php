<?php
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Load the database connection file
require_once __DIR__ . '/../db/connection.php';

// Get the database connection
$conn = getDBConnection();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $role = filter_input(INPUT_POST, 'role', FILTER_SANITIZE_STRING);
    $password = password_hash('password', PASSWORD_DEFAULT); // Set a default password

    // Insert the new user
    $stmt = $conn->prepare("INSERT INTO users (username, email, role, password) VALUES (:username, :email, :role, :password)");
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':role', $role);
    $stmt->bindParam(':password', $password);
    $stmt->execute();
    $stmt->closeCursor();

    // Redirect back to admin dashboard
    header("Location: admin_dashboard.php");
    exit();
}
