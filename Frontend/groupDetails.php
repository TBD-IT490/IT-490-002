<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.php");
    exit();
}

require_once 'includes/data.php';
require_once 'includes/header.php';

//sending info to nat lol
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $view_id) {

    if (isset($_POST['submit_review'])) {
        $result = rmq_rpc('review.create', [
            'book_id'=> $view_id,
            'rating'=> (int)($_POST['rating'] ),
            'review_text'=> trim($_POST['rev_body'] ),
            'username'=> $_SESSION['username'],
        ]);
        $review_msg = ($result['success'])
            ? 'Your review has been recorded. Thank you.'
            : 'Something went wrong. Please try again.';
    }
}

if ($view_id) {
    $group_res = rmq_rpc('group.get', [
        'group_id' => $view_id,
        'username' => $_SESSION['username'],
    ]);
    $group = $group_res['group'] ?? null;
}
else{
    echo("No group found at "+$view_id);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=IM+Fell+English:ital@0;1&family=Crimson+Text:ital,wght@0,400;0,600;1,400&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <title>Noetic - Discussions</title>

    <h1>YOUR BUTTON WORKS YAY</h1>
</html>

<?php require_once 'includes/footer.php'; ?>