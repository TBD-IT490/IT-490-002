<?php
require_once 'includes/data.php';
require_once 'includes/header.php';

$view_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$search = trim($_GET['search'] ?? '');
$genre_filter = $_GET['genre'] ?? '';

// Handle new review submission
$review_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review']) && $view_id) {
    // In real app: save to DB
    $review_msg = 'Your review has been recorded. Thank you.';
}

// Filter books
$filtered = $books;
if ($search) {
    $filtered = array_filter($filtered, fn($b) =>
        stripos($b['title'], $search) !== false ||
        stripos($b['author'], $search) !== false
    );
}
if ($genre_filter) {
    $filtered = array_filter($filtered, fn($b) => in_array($genre_filter, $b['genre']));
}
?>

<style>
.book-card { cursor:pointer; text-decoration:none; display:block; }
.book-card:hover .n-card { border-color:rgba(134,113,91,0.6); transform:translateY(-3px); }
.book-card .n-card { transition: transform 0.2s, border-color 0.2s; }
.book-cover-lg { width:100%; max-width:200px; border:1px solid rgba(134,113,91,0.35); box-shadow: 8px 8px 24px rgba(0,0,0,0.5); }
.review-card { border-bottom:1px solid rgba(134,113,91,0.2); padding-bottom:1rem; margin-bottom:1rem; }
.rating-input label { font-size:1.6rem; cursor:pointer; color:var(--umber); transition:color 0.1s; }
.rating-input input:checked ~ label,
.rating-input label:hover,
.rating-input label:hover ~ label { color:#c9a87c; }
.rating-input { display:flex; flex-direction:row-reverse; gap:4px; }
.rating-input input { display:none; }
</style>

<?php if ($view_id):
    $book = getBookById($view_id);
    if (!$book): ?>
        <p>Book not found.</p>
    <?php else:
        $book_reviews = array_filter($user_reviews, fn($r) => $r['book_id'] == $view_id);
        $my_rating = $user_ratings[$_SESSION['id']][$view_id] ?? 0;
    ?>

    <!-- Breadcrumb -->
    <div style="font-size:0.8rem; color:var(--text-muted); margin-bottom:1.5rem;">
        <a href="books.php" style="color:var(--umber); text-decoration:none;">Library</a>
        &nbsp;›&nbsp; <?php echo htmlspecialchars($book['title']); ?>
    </div>

    <div class="row g-5">
        <!-- Cover + actions -->
        <div class="col-md-3 text-center">
            <img src="<?php echo $book['cover']; ?>"
                 class="book-cover-lg mb-3"
                 alt="<?php echo htmlspecialchars($book['title']); ?>"
                 onerror="this.src='https://via.placeholder.com/200x300/39304A/DCBCCE?text=?'">

            <div class="d-grid gap-2">
                <button class="btn-n btn">+ Reading List</button>
                <button class="btn-n-outline btn">Mark as Read</button>
            </div>

            <!-- My rating -->
            <div class="mt-4">
                <div style="font-size:0.75rem; text-transform:uppercase; letter-spacing:0.1em; color:var(--text-muted); margin-bottom:0.5rem;">Your Rating</div>
                <div class="rating-input justify-content-center">
                    <?php for ($i=5;$i>=1;$i--): ?>
                    <input type="radio" name="my_rating" id="star<?php echo $i; ?>" value="<?php echo $i; ?>" <?php echo $my_rating==$i?'checked':''; ?>>
                    <label for="star<?php echo $i; ?>" title="<?php echo $i; ?>">★</label>
                    <?php endfor; ?>
                </div>
                <div style="font-size:0.78rem; color:var(--text-muted); margin-top:0.3rem;">
                    <?php echo $my_rating ? "You rated: $my_rating/5" : "Not yet rated"; ?>
                </div>
            </div>
        </div>

        <!-- Details -->
        <div class="col-md-9">
            <div style="font-size:0.75rem; color:var(--text-muted); letter-spacing:0.1em; text-transform:uppercase; margin-bottom:0.4rem;">
                <?php echo implode(' · ', $book['genre']); ?>
            </div>
            <h1 class="page-heading" style="font-size:2.6rem; border:none; margin-bottom:0.2rem;"><?php echo htmlspecialchars($book['title']); ?></h1>
            <div style="font-family:'Cormorant Garamond',serif; font-size:1.2rem; color:var(--text-muted); font-style:italic; margin-bottom:1rem;">
                by <?php echo htmlspecialchars($book['author']); ?>, <?php echo $book['year']; ?>
            </div>

            <div class="d-flex align-items-center gap-3 mb-3">
                <?php echo renderStars($book['rating']); ?>
                <span style="font-size:0.9rem; color:var(--text-muted);"><?php echo $book['rating']; ?> · <?php echo number_format($book['reviews']); ?> ratings</span>
                <span class="n-badge"><?php echo $book['pages']; ?> pages</span>
            </div>

            <p style="font-size:1.05rem; line-height:1.75; margin-bottom:1.5rem;">
                <?php echo htmlspecialchars($book['description']); ?>
            </p>

            <div style="font-size:0.82rem; color:var(--text-muted);">ISBN: <?php echo $book['isbn']; ?></div>

            <div class="ornament mt-4">· · ·</div>

            <!-- Reviews -->
            <h4 style="font-family:'IM Fell English',serif; margin-bottom:1.2rem;">Reader Reviews</h4>

            <?php if ($review_msg): ?>
            <div class="n-alert mb-3"><?php echo $review_msg; ?></div>
            <?php endif; ?>

            <?php if (!empty($book_reviews)): ?>
                <?php foreach ($book_reviews as $rv): ?>
                <div class="review-card">
                    <div class="d-flex gap-2 align-items-center mb-1">
                        <div class="avatar-ring"><?php echo strtoupper(substr($rv['user'],0,1)); ?></div>
                        <strong><?php echo htmlspecialchars($rv['user']); ?></strong>
                        <?php echo renderStars($rv['rating']); ?>
                        <span style="font-size:0.8rem; color:var(--text-muted);"><?php echo $rv['created']; ?></span>
                    </div>
                    <div style="font-style:italic; color:var(--blush); font-size:1rem; margin-bottom:0.3rem;">"<?php echo htmlspecialchars($rv['title']); ?>"</div>
                    <p style="font-size:0.98rem; margin:0; color:var(--text-muted);"><?php echo htmlspecialchars($rv['body']); ?></p>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color:var(--text-muted); font-style:italic;">No reviews yet. Be the first.</p>
            <?php endif; ?>

            <!-- Write a review -->
            <div class="n-card p-4 mt-4">
                <h5 style="font-family:'IM Fell English',serif; margin-bottom:1rem;">Write a Review</h5>
                <form method="post">
                    <input type="hidden" name="submit_review" value="1">
                    <div class="mb-3">
                        <label class="form-label">Rating</label>
                        <div class="rating-input">
                            <?php for ($i=5;$i>=1;$i--): ?>
                            <input type="radio" name="rating" id="nstar<?php echo $i; ?>" value="<?php echo $i; ?>">
                            <label for="nstar<?php echo $i; ?>" title="<?php echo $i; ?>">★</label>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Review Title</label>
                        <input type="text" class="form-control" name="rev_title" placeholder="A brief title for your thoughts…">
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

<!-- Library browse view -->
<div class="d-flex justify-content-between align-items-end mb-4">
    <h2 class="page-heading mb-0">The Library</h2>
    <span style="font-size:0.85rem; color:var(--text-muted);"><?php echo count($filtered); ?> volumes</span>
</div>

<!-- Search & Filter -->
<form method="get" class="row g-2 mb-4">
    <div class="col-md-6">
        <input type="text" name="search" class="form-control" placeholder="Search by title or author…" value="<?php echo htmlspecialchars($search); ?>">
    </div>
    <div class="col-md-4">
        <select name="genre" class="form-select">
            <option value="">All Genres</option>
            <?php foreach ($genres as $g): ?>
            <option value="<?php echo $g; ?>" <?php echo $genre_filter===$g?'selected':''; ?>><?php echo $g; ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2">
        <button type="submit" class="btn-n btn w-100">Search</button>
    </div>
</form>

<!-- Book grid -->
<div class="row g-3">
    <?php foreach ($filtered as $b): ?>
    <div class="col-sm-6 col-md-4 col-lg-3">
        <a href="books.php?id=<?php echo $b['id']; ?>" class="book-card">
            <div class="n-card p-3 h-100">
                <img src="<?php echo $b['cover']; ?>"
                     style="width:100%; height:200px; object-fit:cover; border-radius:1px; margin-bottom:0.75rem;"
                     alt="<?php echo htmlspecialchars($b['title']); ?>"
                     onerror="this.src='https://via.placeholder.com/180x200/39304A/DCBCCE?text=?'">
                <div style="font-family:'Cormorant Garamond',serif; font-size:1.05rem; color:var(--blush); margin-bottom:0.2rem; line-height:1.3;">
                    <?php echo htmlspecialchars($b['title']); ?>
                </div>
                <div style="font-size:0.82rem; color:var(--text-muted); margin-bottom:0.4rem; font-style:italic;">
                    <?php echo htmlspecialchars($b['author']); ?>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <?php echo renderStars($b['rating']); ?>
                    <span style="font-size:0.78rem; color:var(--text-muted);"><?php echo $b['rating']; ?></span>
                </div>
                <div class="mt-2 d-flex flex-wrap gap-1">
                    <?php foreach ($b['genre'] as $g): ?>
                    <span class="n-badge" style="font-size:0.65rem;"><?php echo $g; ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        </a>
    </div>
    <?php endforeach; ?>
    <?php if (empty($filtered)): ?>
    <div class="col-12 text-center" style="color:var(--text-muted); padding:3rem; font-style:italic;">
        No books found matching your search.
    </div>
    <?php endif; ?>
</div>

<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>