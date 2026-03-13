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
$search = trim($_GET['search'] );

if ($view_id) { 



} else {
    $books_res = rmq_rpc('explore.all', [
        'username' => $_SESSION['username'] ?? '',
    ]);
    $filtered = $books_res['books'] ?? [];
    $filtered = array_map(function($b) {
        return [
            'id' => $b['book_id'] ?? $b['id'],
            'title' => $b['title'] ,
            'author'=> $b['author'] ,
            'cover' => $b['cover_url'] ?? $b['cover'] ,
            'genre' => $b['genre'] ,
            'rating' => $b['rating'] ,
        ];
    }, $filtered);
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


    <div class="row g-3">
    <?php foreach ($filtered as $b): ?>
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
    <?php if (empty($filtered)): ?>
    <div class="col-12 text-center no-books-msg">
        No books found matching your search.
    </div>
    <?php endif; ?>
</div>

</html>


<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>