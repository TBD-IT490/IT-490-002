<?php
session_start();

// If user is NOT logged in, redirect to login page
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.php");
    exit();
}

require_once 'includes/data.php';
require_once 'includes/header.php';

$view_id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if ($view_id) { 



} else {
    $books_res = rmq_rpc('recommendation.personal',['username' => $_SESSION['username'] ?? '']);
    $rec = $books_res['recommendations'] ?? [];
    $rec_you = array_map(function($b) {
        return [
            'id' => $b['book_id'] ?? $b['id'],
            'title' => $b['title'] ,
            'author'=> $b['author'] ,
            'cover' => $b['cover_url'] ?? $b['cover'] ,
            'genre' => $b['genre'] ,
            'rating' => $b['rating'] ,
        ];
    }, $rec);
    $books_res = rmq_rpc('recommendation.personal',['username' => $_SESSION['username'] ?? '']);
    $rec = $books_res['recommendations'] ?? [];
    $rec_trend = array_map(function($b) {
        return [
            'id' => $b['book_id'] ?? $b['id'],
            'title' => $b['title'] ,
            'author'=> $b['author'] ,
            'cover' => $b['cover_url'] ?? $b['cover'] ,
            'genre' => $b['genre'] ,
            'rating' => $b['rating'] ,
        ];
    }, $rec);
    $all_groups_res = rmq_rpc('group.list', [
        'username' => $_SESSION['username'],
    ]);

    $groups = $all_groups_res['groups'];
    $books_res = rmq_rpc('recommendation.groups',['username' => $_SESSION['username'] ?? '', 'group_id'=> $groups[0]['id']]);
    $rec = $books_res['recommendations'] ?? [];
    $rec_group = array_map(function($b) {
        return [
            'id' => $b['book_id'] ?? $b['id'],
            'title' => $b['title'] ,
            'author'=> $b['author'] ,
            'cover' => $b['cover_url'] ?? $b['cover'] ,
            'genre' => $b['genre'] ,
            'rating' => $b['rating'] ,
        ];
    }, $rec);
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
    <title>Noetic - Recommendations</title>
</head>
<?php if($view_id): ?>

<?php else: ?>


<div class="d-flex justify-content-between align-items-end mb-4">
    <h2 class="page-heading mb-0">Discoveries</h2>
</div>

<div class="d-flex justify-content-between align-items-end mb-4">
    <h2 class="page-heading mb-0">For You</h2>
    <p class="d-flex align-items-center gap-1 ms-2 flex-grow-1">Based </p>
</div>


<div class="row g-3">
    <?php foreach ($rec_you as $b): ?>
    <div class="col-sm-6 col-md-4 col-lg-3">
        <a href="books.php?id=<?php echo $b['id']; ?>" class="book-card">
            <div class="n-card p-3 h-100">
                <img src="<?php echo htmlspecialchars($b['cover']); ?>"
                     class="book-cover-card"
                     alt="<?php echo htmlspecialchars($b['title']); ?>"
                     onerror="this.style.display='none'">
                <div class= "book-card-title">
                </div>
                <div class="book-card-author">
                    <?php echo htmlspecialchars($b['author']); ?>
                </div>
            </div>
        </a>
    </div>
    <?php endforeach; ?>
    <?php if (empty($rec_you)): ?>
    <div class="col-12 text-center no-books-msg">
        No books found, try reviewing books.
    </div>
    <?php endif; ?>
</div>

<div class="d-flex justify-content-between align-items-end mb-4">
    <h2 class="page-heading mb-0">From Your Groups</h2>
</div>


<div class="row g-3">
    <?php foreach ($rec_group as $b): ?>
    <div class="col-sm-6 col-md-4 col-lg-3">
        <a href="books.php?id=<?php echo $b['id']; ?>" class="book-card">
            <div class="n-card p-3 h-100">
                <img src="<?php echo htmlspecialchars($b['cover']); ?>"
                     class="book-cover-card"
                     alt="<?php echo htmlspecialchars($b['title']); ?>"
                     onerror="this.style.display='none'">
                <div class= "book-card-title">
                </div>
                <div class="book-card-author">
                    <?php echo htmlspecialchars($b['author']); ?>
                </div>
            </div>
        </a>
    </div>
    <?php endforeach; ?>
    <?php if (empty($rec_group)): ?>
    <div class="col-12 text-center no-books-msg">
        No books found, try reviewing books.
    </div>
    <?php endif; ?>
</div>


<div class="d-flex justify-content-between align-items-end mb-4">
    <h2 class="page-heading mb-0">Trending in Noetic</h2>
</div>


<div class="row g-3">
    <?php foreach ($rec_you as $b): ?>
    <div class="col-sm-6 col-md-4 col-lg-3">
        <a href="books.php?id=<?php echo $b['id']; ?>" class="book-card">
            <div class="n-card p-3 h-100">
                <img src="<?php echo htmlspecialchars($b['cover']); ?>"
                     class="book-cover-card"
                     alt="<?php echo htmlspecialchars($b['title']); ?>"
                     onerror="this.style.display='none'">
                <div class= "book-card-title">
                </div>
                <div class="book-card-author">
                    <?php echo htmlspecialchars($b['author']); ?>
                </div>
            </div>
        </a>
    </div>
    <?php endforeach; ?>
    <?php if (empty($rec_you)): ?>
    <div class="col-12 text-center no-books-msg">
        No books found, try reviewing books.
    </div>
    <?php endif; ?>
</div>
</html>

<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>