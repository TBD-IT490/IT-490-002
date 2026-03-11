<?php
session_start();

//REDIRECT TO LOGIN IF NOT LOGGED IN PROPERLY (so you can't access without signing in hehe)
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.php");
    exit();
}

//data functions and header file
require_once 'includes/data.php';
require_once 'includes/header.php';

$view_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$search = trim($_GET['search'] ?? '');
$genre_filter = $_GET['genre'] ?? '';
$review_msg = '';

//sending info to nat lol
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $view_id) {

    if (isset($_POST['submit_review'])) {
        $result = rmq_rpc('review.create', [
            'book_id'=> $view_id,
            'rating'=> (int)($_POST['rating'] ?? 0),
            'review_text'=> trim($_POST['rev_body'] ?? ''),
            'username'=> $_SESSION['username'] ?? '',
        ]);
        $review_msg = ($result['success'] ?? false)
            ? 'Your review has been recorded. Thank you.'
            : 'Something went wrong. Please try again.';
    }
}


//also sending info to nat but getting info back as well
if ($view_id) {

    $book_res = rmq_rpc('book.get', [
        'book_id'=> $view_id,
        'username' => $_SESSION['username'] ?? '',
    ]);
    $book = $book_res['book'] ?? null;

    if ($book) {

        $book['id'] = $book['book_id'] ?? $view_id;
        $book['cover'] = $book['cover_url'] ?? '';
        $book['year'] = $book['published_year'] ?? '';

        $book_reviews = [];
        $my_rating    = 0;  
    }

    //book list function, nat has her own on her side
} else {

    $books_res = rmq_rpc('book.list', [
        'search' => $search,
        'username' => $_SESSION['username'] ?? '',
    ]);

    $filtered = $books_res['books'] ?? [];

    $filtered = array_map(function($b) {
        return [
            'id' => $b['book_id'] ?? $b['id'] ?? null,
            'title' => $b['title'] ?? '',
            'author'=> $b['author'] ?? '',
            'cover' => $b['cover_url'] ?? $b['cover'] ?? '',
            'genre' => $b['genre'] ?? [],
            'rating' => $b['rating'] ?? 0,
        ];
    }, $filtered);

    if ($genre_filter) {
        $filtered = array_filter($filtered, function($b) use ($genre_filter) {
            return in_array($genre_filter, (array)$b['genre']);
        });
        $filtered = array_values($filtered);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=IM+Fell+English:ital@0;1&family=Crimson+Text:ital,wght@0,400;0,600;1,400&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<?php if ($view_id): ?>

    <?php if (!$book): ?>
        <p class="not-found-msg">Book not found.</p>
    <?php else: ?>

      <!--from https://www.w3schools.com/php/func_string_htmlspecialchars.asp -->
    <div class="breadcrumb-text">
        <a href="books.php" class="breadcrumb-link">Library</a>
        &nbsp;›&nbsp; <?php echo htmlspecialchars($book['title']); ?>
    </div>

    <div class="row g-5">
        <div class="col-md-3 text-center">
            <img src="<?php echo htmlspecialchars($book['cover'] ?? $book['cover_url'] ?? ''); ?>"
                 class="book-cover-lg mb-3"
                 alt="<?php echo htmlspecialchars($book['title']); ?>"
                 onerror="this.src=''">

            <div style="rating-label">Your Rating</div>
            <div class="rating-input justify-content-center">
                <?php for ($i = 5; $i >= 1; $i--): ?>
                <input type="radio" name="my_rating_display" 
                       id="star<?php echo $i; ?>"
                       value="<?php echo $i; ?>"
                       <?php echo $my_rating == $i ? 'checked' : ''; ?>>
                <label for="star<?php echo $i; ?>" title="<?php echo $i; ?>">★</label>
                <?php endfor; ?>
            </div>
            <div class="rating-status">
                <?php echo $my_rating ? "You rated: $my_rating / 5" : 'Not yet rated'; ?>
            </div>
        </div>

        <div class="col-md-9">
            <div class="genre-line">
                <?php echo implode(' · ', (array)($book['genre'] ?? [])); ?>
            </div>
            <h1 class="page-heading book-title-large">
                <?php echo htmlspecialchars($book['title']); ?>
            </h1>
            <div class="book-author-line">
                by <?php echo htmlspecialchars($book['author']); ?>
                <?php if (!empty($book['year'])): ?>, <?php echo $book['year']; ?><?php endif; ?>
            </div>
            <div class="d-flex align-items-center gap-3 mb-3">
                <?php echo renderStars($book['rating'] ?? 0); ?>
                <span style="font-size:0.9rem; color:var(--text-muted);">
                    <?php echo $book['rating'] ?? 0; ?>
                    <?php if (!empty($book['reviews'])): ?>
                        <?php echo number_format($book['reviews']); ?> ratings
                    <?php endif; ?>
                </span>
                <?php if (!empty($book['pages'])): ?>
                <span class="n-badge"><?php echo $book['pages']; ?> pages</span>
                <?php endif; ?>
            </div>
            <p style="book-description">
                <?php echo htmlspecialchars($book['description'] ?? ''); ?>
            </p>
            <?php if (!empty($book['isbn'])): ?>
            <div class="n-badge">ISBN: <?php echo $book['isbn']; ?></div>
            <?php endif; ?>

            <div class="ornament mt-4">· · ·</div>

            <h4 style="review-heading">Reader Reviews</h4>

            <?php if ($review_msg): ?>
            <div class="n-alert mb-3"><?php echo htmlspecialchars($review_msg); ?></div>
            <?php endif; ?>

            <?php if (!empty($book_reviews)): ?>
                <?php foreach ($book_reviews as $rv): ?>
                <div class="review-card">
                    <div class="d-flex gap-2 align-items-center mb-1">
                        <div class="avatar-ring"><?php echo strtoupper(substr($rv['user'], 0, 1)); ?></div>
                        <strong><?php echo htmlspecialchars($rv['user']); ?></strong>
                        <?php echo renderStars($rv['rating']); ?>
                        <span class="review-date"><?php echo $rv['created']; ?></span>
                    </div>
                    <div class="review-title">
                        "<?php echo htmlspecialchars($rv['title'] ?? ''); ?>"
                    </div>
                    <p class="review-body">
                        <?php echo htmlspecialchars($rv['body'] ?? $rv['review_text'] ?? ''); ?>
                    </p>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="no-reviews-msg">No reviews yet. Be the first.</p>
            <?php endif; ?>

            <div class="n-card p-4 mt-4">
                <h5 class="write-review-heading">Write a Review</h5>
                <form method="post">
                    <input type="hidden" name="submit_review" value="1">
                    <div class="mb-3">
                        <label class="form-label">Rating</label>
                        <div class="rating-input">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                            <input type="radio" name="rating" id="nstar<?php echo $i; ?>" value="<?php echo $i; ?>">
                            <label for="nstar<?php echo $i; ?>" title="<?php echo $i; ?>">★</label>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Your Review</label>
                        <textarea class="form-control" name="rev_body" rows="4" placeholder="What did you think?"></textarea>
                    </div>
                    <button type="submit" class="btn-n btn">Submit Review</button>
                </form>
            </div>
        </div>
    </div>

    <?php endif; ?>

<?php else: ?>

<div class="d-flex justify-content-between align-items-end mb-4">
    <h2 class="page-heading mb-0">The Library</h2>
    <span class="library-count"><?php echo count($filtered); ?> volumes</span>
</div>

<form method="get" class="row g-2 mb-4">
    <div class="col-md-6">
        <input type="text" name="search" class="form-control"
               placeholder="Search by title or author…"
               value="<?php echo htmlspecialchars($search); ?>">
    </div>
    <div class="col-md-4">
        <select name="genre" class="form-select">
            <option value="">All Genres</option>
            <?php foreach ($genres as $g): ?>
            <option value="<?php echo htmlspecialchars($g); ?>"
                    <?php echo $genre_filter === $g ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($g); ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2">
        <button type="submit" class="btn-n btn w-100">Search</button>
    </div>
</form>

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
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <?php echo renderStars($b['rating']); ?>
                    <span class="book-rating-small"><?php echo $b['rating']; ?></span>
                </div>
                <div class="mt-2 d-flex flex-wrap gap-1">
                    <?php foreach ((array)$b['genre'] as $g): ?>
                    <span class="n-badge" style="font-size:0.65rem;"><?php echo htmlspecialchars($g); ?></span>
                    <?php endforeach; ?>
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