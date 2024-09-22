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

// Fetch the user data for the user to be edited
if (isset($_POST['user_id'])) {
    $user_id = $_POST['user_id'];

    // Handle form submission to update user details
    if (isset($_POST['edit_user'])) {
        $username = $_POST['username'];
        $email = $_POST['email'];
        $role = $_POST['role'];

        try {
            // Update the user's details in the database
            $stmt = $conn->prepare("UPDATE users SET username = :username, email = :email, role = :role WHERE id = :id");
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':role', $role);
            $stmt->bindParam(':id', $user_id);
            $stmt->execute();
            
            // Redirect back to the dashboard after updating
            header("Location: admin_dashboard.php");
            exit();
        } catch (PDOException $e) {
            echo "Error updating record: " . $e->getMessage();
        }
    }

    // Fetch the user's existing data to pre-fill the form
    $stmt = $conn->prepare("SELECT id, username, email, role FROM users WHERE id = :id");
    $stmt->bindParam(':id', $user_id);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();
} else {
    // Redirect if no user_id is provided
    header("Location: admin_dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 0;
            line-height: 1.6;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        label {
            font-size: 16px;
            color: #555;
            margin-bottom: 5px;
        }
        input[type="text"],
        input[type="email"],
        select {
            padding: 12px;
            font-size: 14px;
            border-radius: 6px;
            border: 1px solid #ccc;
            width: 100%;
            box-sizing: border-box;
            background-color: #f9f9f9;
            transition: background-color 0.2s ease;
        }
        input[type="text"]:focus,
        input[type="email"]:focus,
        select:focus {
            background-color: #e8f0fe;
            outline: none;
            border-color: #4CAF50;
        }
        button {
            padding: 12px;
            font-size: 16px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }
        button:hover {
            background-color: #45a049;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #007BFF;
            text-decoration: none;
            font-weight: bold;
            transition: color 0.2s ease;
        }
        .back-link:hover {
            text-decoration: underline;
            color: #0056b3;
        }
        @media (max-width: 768px) {
            .container {
                width: 90%;
                margin: 20px auto;
            }
            button {
                font-size: 14px;
                padding: 10px;
            }
            input[type="text"],
            input[type="email"],
            select {
                padding: 10px;
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Edit User</h2>
        <form action="edit_user.php" method="post">
            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['id']); ?>">
            <label for="username">Username</label>
            <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
            <label for="email">Email</label>
            <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
            <label for="role">Role</label>
            <select name="role" required>
                <option value="student" <?php if ($user['role'] === 'student') echo 'selected'; ?>>Student</option>
                <option value="admin" <?php if ($user['role'] === 'admin') echo 'selected'; ?>>Admin</option>
            </select>
            <button type="submit" name="edit_user">Save Changes</button>
        </form>
        <a href="admin_dashboard.php" class="back-link">Back to Dashboard</a>
    </div>
</body>
</html>
