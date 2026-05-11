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

$results=[];
$query = trim($_GET['query'] ?? '');

//noquery? dont care send empty string to get nonblocked ppl twin
$results = rmq_rpc('friends.search', [
    'user_id' => $_SESSION['id'], 
    'query' => $query
])['results'] ?? [];

/*
if (isset($_GET['query'])) {
    $results = rmq_rpc('friends.search', [
        'user_id' => $_SESSION['id'], 
        'query' => trim($_GET['query'])
        ])['results'] ?? [];
}
*/

/*
users to test search: urmoms, faah, test2020
*/ 

?>

<!--HTML CODE-->

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Noetic — Search Friends</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=IM+Fell+English:ital@0;1&family=Crimson+Text:ital,wght@0,400;0,600;1,400&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>

<body>
    <div class="brand">
        <h1>Search Friends</h1>
    </div>
    
    <form method="GET" class="mb-4">
        <div class="input-group">
            <input type="text" name="query" class="form-control" placeholder="Search for friends..." value="<?= htmlspecialchars($_GET['query'] ?? '') ?>">
            <button class="btn btn-primary" type="submit">Search</button>
        </div>
    </form>

    <?php if (!empty($results)): ?>
        <ul class="list-group">
            <?php foreach ($results as $user): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <?= htmlspecialchars($user['username']) ?>
                    <a href="social_network/friendActions.php?action=add&id=<?= $user['id'] ?>" class="btn btn-sm btn-success">Add Friend</a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php elseif (isset($_GET['query'])): ?>
        <p>No users found. Try a different search.</p>
    <?php endif; ?>
    <a href="../social_network/socialNetwork.php" class="btn-n btn" style="text-decoration: none; color: inherit;">View Your Friends</a>
</body>

</html>

<!--footer code :) at least it stays consistent-->
<?php require_once '../includes/footer.php'; ?>