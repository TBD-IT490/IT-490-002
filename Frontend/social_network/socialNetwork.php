<?php
session_start();

//REDIRECT TO LOGIN IF NOT LOGGED IN PROPERLY (so you can't access without signing in hehe)
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.php");
    exit();
}

//functions and headers
require_once '../includes/data.php';
require_once '../includes/header.php';
require_once '../includes/footer.php';

$friends = rmq_rpc('friends.get', ['user_id' => $_SESSION['id']])['friends'] ?? [];


?>

<!--HTML CODE-->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Noetic — Social Network</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=IM+Fell+English:ital@0;1&family=Crimson+Text:ital,wght@0,400;0,600;1,400&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
   <div> 
    <h2>Your Friends: </h2>
    
    <?php if (!empty($friends)): ?>
        <ul class="list-group">
            <?php foreach ($friends as $friend): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <?=  htmlspecialchars($friend['username']); ?>
                <div>
                    <a href="friendActions.php?action=remove&id=<?= $friend['id']; ?>" class="btn btn-danger btn-sm">Remove</a>
                    <!--if i can do this td, we can keep block-->
                    <a href="friendActions.php?action=block&id=<?= $friend['id']; ?>" class="btn btn-warning btn-sm">Block</a>
                </div>
                </li>
                <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>You have no friends. Find some to follow!</p>
    <?php endif; ?>
    <a href="searchFriends.php" class="btn-n btn" style="text-decoration: none; color: inherit;">Find Friends!</a>
    </div>
</body>
</html>