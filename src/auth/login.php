<?php
session_start();
ob_start();

require_once __DIR__ . '/../db/connection.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn = getDBConnection();

    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $pass = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);

    if (empty($email) || empty($pass)) {
        $_SESSION['error_message'] = "Please fill in all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error_message'] = "Please enter a valid email address.";
    } elseif (strlen($pass) < 6) {
        $_SESSION['error_message'] = "Password must be at least 6 characters long.";
    } else {
        try {
            // Check for existing failed login attempts within the last 5 minutes
            $stmt = $conn->prepare("SELECT COUNT(*) as attempts, MAX(attempt_time) as last_attempt FROM failed_logins WHERE email = :email AND attempt_time > NOW() - INTERVAL 5 MINUTE");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $failed_attempts = $result['attempts'];
            $last_attempt_time = $result['last_attempt'];

            // Lock account if there have been 3 failed attempts within the last 5 minutes
            if ($failed_attempts >= 3) {
                // Calculate time since the last failed attempt
                $lock_duration = 300; // 5 minutes in seconds
                $time_since_last_attempt = time() - strtotime($last_attempt_time);

                if ($time_since_last_attempt < $lock_duration) {
                    $_SESSION['error_message'] = "Your account has been locked due to too many failed login attempts. Please try again after " . (5 - floor($time_since_last_attempt / 60)) . " minutes.";
                    header("Location: /SoftwareS/login.php");
                    exit;
                } else {
                    // Reset failed attempts after lock duration has expired
                    $stmt = $conn->prepare("DELETE FROM failed_logins WHERE email = :email");
                    $stmt->bindParam(':email', $email);
                    $stmt->execute();
                }
            }

            // Check if the email exists
            $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                $user_id = $user['id'];
                $username = $user['username'];
                $hashed_password = $user['password'];
                $role = $user['role'];

                // Verify the password
                if (password_verify($pass, $hashed_password)) {
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['email'] = $email;
                    $_SESSION['username'] = $username;
                    $_SESSION['role'] = $role;

                    $_SESSION['success_message'] = "Login successful. Welcome $username!";

                    // Reset any failed login attempts
                    $stmt = $conn->prepare("DELETE FROM failed_logins WHERE email = :email");
                    $stmt->bindParam(':email', $email);
                    $stmt->execute();

                    // Redirect based on role
                    if ($role === 'student') {
                        header("Location: ./dashboard.php");
                    } else {
                        header("Location: ./admin_dashboard.php");
                    }
                    exit;
                } else {
                    // Log the failed login attempt
                    $stmt = $conn->prepare("INSERT INTO failed_logins (user_id, email) VALUES (:user_id, :email)");
                    $stmt->bindParam(':user_id', $user_id);
                    $stmt->bindParam(':email', $email);
                    $stmt->execute();

                    $_SESSION['error_message'] = "Invalid password. Please try again.";
                }
            } else {
                $_SESSION['error_message'] = "No account found with that email.";
            }
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Database error: " . $e->getMessage();
        }
    }

    header("Location: /login.php");
    exit;
}

ob_end_flush();

?>
