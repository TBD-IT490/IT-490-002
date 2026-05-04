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
$feed = rmq_rpc('feed.get', ['user_id' => $_SESSION['id']]);
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
    <div class="container mt-5">
        <h1 class="mb-4">Welcome to Noetic, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
        
        <div class="row">
            <div class="col-md-8">
                <h2>Your Feed</h2>
                <?php if (!empty($feed['posts'])): ?>
                    <?php foreach ($feed['posts'] as $post): ?>
                        <div class="card mb-3">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($post['author']); ?></h5>
                                <p class="card-text"><?php echo htmlspecialchars($post['content']); ?></p>
                                <p class="card-text"><small class="text-muted"><?php echo htmlspecialchars($post['timestamp']); ?></small></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No posts in your feed yet. Start following some friends!</p>
                <?php endif; ?>
            </div>
            <div class="col-md-4">
                <h2>Your Friends</h2>
                <?php if (!empty($friends)): ?>
                    <ul class="list-group">
                        <?php foreach ($friends as $friend): ?>
                            <li class="list-group-item"><?php echo htmlspecialchars($friend['username']); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>You have no friends yet. Find some to follow!</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>