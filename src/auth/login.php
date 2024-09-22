<?php
session_start();  // Always start session at the top of the script
ob_start();  // Start output buffering

require_once __DIR__ . '/../db/connection.php';  // Ensure correct path to db connection file

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$error_message = '';  // Initialize error message variable

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the database connection
    $conn = getDBConnection();

    // Sanitize user inputs
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $pass = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format.";
    } else {
        try {
            // Prepare the SELECT statement to check for the email
            $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();

            // Check if a user exists with that email
            if ($stmt->rowCount() > 0) {
                // Fetch the user data
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                $user_id = $user['id'];
                $username = $user['username'];
                $hashed_password = $user['password'];
                $role = $user['role'];

                // Verify the password
                if (password_verify($pass, $hashed_password)) {
                    // Regenerate session ID
                    session_regenerate_id(true);

                    // Store session data
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['email'] = $email;
                    $_SESSION['username'] = $username;
                    $_SESSION['role'] = $role;

                    // Set a success message
                    $_SESSION['success_message'] = "Login successful. Welcome $username!";

                    // Redirect based on role
                    if ($role === 'student') {
                        header("Location: ./dashboard.php");  // Use relative path
                    } else {
                        header("Location: ./admin_dashboard.php");
                    }
                    exit;  // Important: stop script execution after header
                } else {
                    $error_message = "Invalid email or password.";
                }
            } else {
                $error_message = "Invalid email or password.";
            }
        } catch (PDOException $e) {
            $error_message = "Database error: " . $e->getMessage();
        }
    }
}
ob_end_flush();  // End output buffering
?>
