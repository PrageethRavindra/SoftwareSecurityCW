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

    // Check if the email starts with 'admin@' and set the role accordingly
    $role = (strpos($email, 'admin@') === 0) ? 'admin' : 'student';

    // Validate the password: at least 8 characters, include both letters and numbers
    if (strlen($pass) < 8 || !preg_match('/[a-zA-Z]/', $pass) || !preg_match('/\d/', $pass)) {
        $error_message = "Password must be at least 8 characters long and include both letters and numbers.";
        $_SESSION['error_message'] = $error_message;

        // Redirect back to the registration page with the error message
        header("Location: /SoftwareSecurityCw/login.php");
        exit(); // Ensure the script stops after the redirect
    }

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

        // Store error message in session to display on the registration page
        $_SESSION['error_message'] = $error_message;

        // Redirect back to the registration page
        header("Location: /SoftwareSecurityCw/login.php");
        exit(); // Ensure the script stops after the redirect
    } else {
        // Hash the password before storing
        $hashed_password = password_hash($pass, PASSWORD_DEFAULT);

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
            header("Location: login.php");
            exit(); // Ensure the script stops after the redirect
        } else {
            $error_message = "Error occurred during registration. Please try again.";
            $_SESSION['error_message'] = $error_message;
            header("Location: /SoftwareSecurityCw/login.php");
            exit();
        }
    }

    $check_duplicate->closeCursor();
    $conn = null;
}
?>
