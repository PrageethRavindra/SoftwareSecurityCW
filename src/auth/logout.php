<?php
session_start(); // Start the session

// Check if the user is logged in by confirming the session user_id
if (isset($_SESSION['user_id'])) {
    // Load the database connection
    require_once __DIR__ . '/../db/connection.php';
    
    // Get the database connection
    $pdo = getDBConnection();
    
    try {
        // Log the user logout action in the activity_log table
        $stmt = $pdo->prepare('INSERT INTO activity_log (user_id, activity) VALUES (?, ?)');
        $stmt->execute([$_SESSION['user_id'], 'Logout']);
    } catch (PDOException $e) {
        // Handle any error that occurs during the insert operation
        error_log('Error logging out activity: ' . $e->getMessage());
    }
}

// Unset all session variables
$_SESSION = [];

// Destroy the session
session_destroy();

// Redirect to login page
header('Location: ../../login.php');
exit();
?>
