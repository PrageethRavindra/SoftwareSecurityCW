<?php
session_start(); // Ensure session is started if you use session variables

// Load the connection file
require_once __DIR__ . '/../db/connection.php'; // Corrected path

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the database connection
    $conn = getDBConnection();

    // Sanitize user inputs
    $user = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $pass = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);

    // Hash the password before storing
    $hashed_password = password_hash($pass, PASSWORD_DEFAULT);

    // Check if the email starts with 'admin@' and set the role accordingly
    $role = (strpos($email, 'admin@') === 0) ? 'admin' : 'student';

    // Check for duplicate email or username before insertion
    $check_duplicate = $conn->prepare("SELECT username, email FROM users WHERE username = :username OR email = :email");
    $check_duplicate->bindParam(':username', $user);
    $check_duplicate->bindParam(':email', $email);
    $check_duplicate->execute();

    if ($check_duplicate->rowCount() > 0) {
        // Duplicate found, now check if it's username or email
        $result = $check_duplicate->fetch();

        if ($result['username'] == $user) {
            $error_message = "Username already exists. Please choose another username.";
        } elseif ($result['email'] == $email) {
            $error_message = "Email already exists. Please use another email.";
        }
    } else {
        // No duplicates, proceed with insertion
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (:username, :email, :password, :role)");
        $stmt->bindParam(':username', $user);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':role', $role);

        if ($stmt->execute()) {
            // Get the ID of the newly registered user
            $user_id = $conn->lastInsertId();

            // Insert activity log for user registration
            $activity = "User registered";
            $log_stmt = $conn->prepare("INSERT INTO activity_log (user_id, activity) VALUES (:user_id, :activity)");
            $log_stmt->bindParam(':user_id', $user_id);
            $log_stmt->bindParam(':activity', $activity);
            $log_stmt->execute();

            // Redirect to the login page after successful registration
            header("Location:./../login.php");
            exit(); // Ensure the script stops after the redirect
        } else {
            $error_message = "Error occurred during registration. Please try again.";
        }
    }

    $check_duplicate->closeCursor();
    $conn = null;
}
?>
