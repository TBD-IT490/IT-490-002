<?php
// Start the session on every page that uses session data
session_start();

// Check if the user is logged in, otherwise redirect them to the login page
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<body>
    <h1>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
    <p>This is a different page, but your username is still available.</p>
    <a href="logout.php">Log Out</a>
</body>
</html>
