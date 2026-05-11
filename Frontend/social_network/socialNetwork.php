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


$msg = '';
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $action = $_POST['action'] ?? '';
    $friendID = $_POST['friend_id'] ?? '';
    if($friendID >0){
        if($action === 'remove') {
            rmq_rpc('friends.remove', ['user_id' => $_SESSION['id'], 'friend_id' => $friendID]);
            $msg = 'Friend removed.';
        } elseif ($action === 'block') {
            rmq_rpc('friends.block', ['user_id' => $_SESSION['id'], 'friend_id' => $friendID]);
            $msg = 'Friend blocked.';
        } elseif ($action === 'add') {
            rmq_rpc('friends.add', ['user_id' => $_SESSION['id'], 'friend_id' => $friendID]);
            $msg = 'Friend added.';
        } else{
            $msg = 'action failed';
        }
    }
}

$friendsResult = rmq_rpc('friends.list', ['user_id' => $_SESSION['id']]);
$friends = $friendsResult['friends'] ?? [];

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
    </div>
    <div class="container mt-3">
        <?php if ($msg): ?>
            <div class="alert alert-info"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>
    <?php if (!empty($friends)): ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Bio</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($friends as $friend): ?>
                    <tr>
                        <td><?= htmlspecialchars($friend['username'] ?? '') ?></td>
                        <td><?= htmlspecialchars($friend['email'] ?? '') ?></td>
                        <td><?= htmlspecialchars($friend['bio'] ?? '') ?></td>
                        <td><?= htmlspecialchars($friend['isBlocked'] ? 'Yes' : 'No') ?></td>
                        <td>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="friend_id" value="<?= (int)$friend['id'] ?>">
                                <button type="submit" name="action" value="remove" class="btn btn-sm btn-danger">Remove</button>
                            </form>
                            <?php if (!$friend['isBlocked']): ?>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="friend_id" value="<?= (int)$friend['id'] ?>">
                                    <button type="submit" name="action" value="block" class="btn btn-sm btn-success">Block</button>
                                </form>
                            <?php else: ?>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="friend_id" value="<?= (int)$friend['id'] ?>">
                                    <button type="submit" name="action" value="add" class="btn btn-sm btn-primary">Unblock</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
    </table>
    <?php else: ?>
        <p>You have no friends. Find some to follow!</p>
    <?php endif; ?>
    <a href="../pages/profile.php" class="btn-n btn" style="text-decoration: none; color: inherit;">Back</a>
    <a href="searchFriends.php" class="btn-n btn" style="text-decoration: none; color: inherit;">Find Friends!</a>
    </div>
</body>
</html>
<!--footer code :) at least it stays consistent-->
<?php require_once '../includes/footer.php'; ?>