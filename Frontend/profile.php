<?php
session_start();

//REDIRECT TO LOGIN IF NOT LOGGED IN PROPERLY (so you can't access without signing in hehe)
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.php");
    exit();
}

//functions and headers
require_once 'includes/data.php';
require_once 'includes/header.php';

$msg = '';
$tab = $_GET['tab'] ?? 'books';


//ALL OF THIS MUST MATCH NAT'S BACKEND CODE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $result = rmq_rpc('user.update', [
        'display_name' => trim($_POST['display_name'] ?? ''), 
        'email'=> trim($_POST['email'] ?? ''),
        'bio'=> trim($_POST['bio'] ?? ''),
        'preferences' => $_POST['prefs'] ?? [],
    ]);
    $msg = ($result['success'] ?? false)
        ? 'Profile updated.'
        : 'Could not save changes. Please try again.';
}

?>


<!--HTML CODE-->

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Noetic — Profile</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=IM+Fell+English:ital@0;1&family=Crimson+Text:ital,wght@0,400;0,600;1,400&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
</html>

<!--footer code :) at least it stays consistent-->
<?php require_once 'includes/footer.php'; ?>