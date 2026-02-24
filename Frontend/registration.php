<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start the session at the very beginning of the script
session_start();

// Database connection details (replace with your own)
$DATABASE_HOST = 'localhost';
$DATABASE_USER = 'taryn';
$DATABASE_PASS = 'taryn490';
$DATABASE_NAME = 'user_registration';

// Try and connect using the info above
$con = mysqli_connect($DATABASE_HOST, $DATABASE_USER, $DATABASE_PASS, $DATABASE_NAME);
if (mysqli_connect_errno()) {
    die('Failed to connect to MySQL: ' . mysqli_connect_error());
}

if (isset($_POST['register'])) {
    // Get form data and sanitize it
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password']; // Get raw password for hashing

    // Validate inputs
    if (empty($username) || empty($email) || empty($password)) {
        die('Please fill all fields!');
    }

    // Hash the password securely
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Check if the username or email already exists
    if ($stmt = $con->prepare('SELECT id FROM users WHERE username = ? OR email = ?')) {
        $stmt->bind_param('ss', $username, $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            echo 'Username or Email already exists!';
        } else {
            // Insert new user into the database
            if ($insert_stmt = $con->prepare('INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)')) {
                $insert_stmt->bind_param('sss', $username, $email, $password_hash);
                $insert_stmt->execute();
                
                // Registration successful, log the user in automatically using sessions
                $_SESSION['loggedin'] = TRUE;
                $_SESSION['username'] = $username;
                $_SESSION['id'] = $insert_stmt->insert_id;
                
                echo 'Registration successful! You are now logged in.';
                // Redirect to a members area page
                header('Location: dashboard.php');
                exit();

            } else {
                echo 'Error inserting user: ' . $con->error;
            }
        }
        $stmt->close();
    } else {
        echo 'Error preparing statement: ' . $con->error;
    }
}
$con->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>User Registration</title>
</head>
<body>
    <h2>Register an Account</h2>
    <form action="regisration.php" method="POST">
        <div>
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>
        </div>
        <div>
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
        </div>
        <div>
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
        </div>
        <button type="submit" name="register">Register</button>
    </form>
</body>
</html>

