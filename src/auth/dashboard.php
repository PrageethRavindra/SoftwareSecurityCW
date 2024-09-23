<?php
session_start(); // Start the session

// Load the connection file (adjust the path based on your structure)
require_once __DIR__ . '/../db/connection.php';

// Display errors for debugging purposes (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if the user is logged in
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

// Get the user's email and role from the session
$email = $_SESSION['email'];
$role = $_SESSION['role'];

// Get the database connection
$conn = getDBConnection();

// Prepare and execute query to fetch user details
$stmt = $conn->prepare("SELECT id, username, email FROM users WHERE email = :email");
$stmt->bindParam(':email', $email);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if the user exists
if ($user) {
    $username = $user['username'];
    $userEmail = $user['email'];
    $userId = $user['id']; // Get user ID for the Edit Profile link
} else {
    // Redirect to login page if user is not found
    header("Location: login.php");
    exit();
}

// Fetch user activities if the user is an admin
$activities = [];
if ($role === 'admin') {
    $activity_stmt = $conn->prepare("
        SELECT activity, activity_time 
        FROM activity_log 
        WHERE user_id = :user_id
    ");
    $activity_stmt->bindParam(':user_id', $userId);
    $activity_stmt->execute();
    $activities = $activity_stmt->fetchAll(PDO::FETCH_ASSOC);
}

$conn = null; // Close the database connection
?>

<!-- HTML Content for the Dashboard -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .navbar {
            background-color: #4CAF50;
            padding: 15px;
            color: white;
            text-align: center;
        }
        .navbar h1 {
            margin: 0;
            font-size: 24px;
        }
        .container {
            padding: 20px;
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }
        .welcome-message {
            font-size: 20px;
            margin-bottom: 20px;
        }
        .details {
            font-size: 16px;
            margin-bottom: 20px;
        }
        .logout-button,
        .edit-profile-button {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-right: 10px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
        }
        .logout-button {
            background-color: #f44336;
            color: white;
        }
        .logout-button:hover {
            background-color: #e53935;
        }
        .edit-profile-button {
            background-color: #4CAF50;
            color: white;
        }
        .edit-profile-button:hover {
            background-color: #45a049;
        }
    </style>
    <script>
        function confirmLogout(event) {
            if (!confirm('Are you sure you want to logout?')) {
                event.preventDefault(); // Prevent form submission if not confirmed
            }
        }
    </script>
</head>
<body>

    <div class="navbar">
        <h1>Student Dashboard</h1>
    </div>

    <div class="container">
        <p class="welcome-message">Welcome, <?php echo htmlspecialchars($username); ?>!</p>
        <p class="details">Your email: <?php echo htmlspecialchars($userEmail); ?></p>

        <!-- Make sure the Edit Profile button correctly links to the edit_user.php page -->
        <a href="edit_user.php?user_id=<?php echo $userId; ?>" class="edit-profile-button">Edit Profile</a>
        <a href="../auth/logout.php" class="logout-button" onclick="confirmLogout(event)">Logout</a>

        <!-- Display admin activities if the user is an admin -->
        <?php if ($role === 'admin' && !empty($activities)): ?>
            <h3>Activity Log</h3>
            <ul>
                <?php foreach ($activities as $activity): ?>
                    <li><?php echo htmlspecialchars($activity['activity']) . ' at ' . htmlspecialchars($activity['activity_time']); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

</body>
</html>
