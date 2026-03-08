<?php
require_once 'includes/data.php';
require_once 'includes/header.php';

// Build recommendations based on dummy ratings
// Personal: find top-rated genres of current user, recommend unread books in those genres
$my_ratings = $user_ratings[$_SESSION['id']] ?? [];
$my_rated_ids = array_keys($my_ratings);
$unread_books = array_filter($books, fn($b) => !in_array($b['id'], $my_rated_ids));

// Genre affinity from ratings
$genre_score = [];
foreach ($my_ratings as $bid => $rating) {
    $b = getBookById($bid);
    if (!$b) continue;
    foreach ($b['genre'] as $g) {
        $genre_score[$g] = ($genre_score[$g] ?? 0) + $rating;
    }
}
arsort($genre_score);
$top_genres = array_slice(array_keys($genre_score), 0, 3);

// Score unread books by genre match + community rating
$scored = [];
foreach ($unread_books as $b) {
    $score = $b['rating'] * 0.6;
    foreach ($b['genre'] as $g) {
        if (isset($genre_score[$g])) $score += $genre_score[$g] * 0.1;
    }
    $b['_score'] = round($score, 2);
    $scored[] = $b;
}
usort($scored, fn($a,$b) => $b['_score'] <=> $a['_score']);
$personal_recs = array_slice($scored, 0, 4);

// Group recommendations: books not yet read by any group, scored by group genre affinity
$my_groups_list = array_filter($groups, fn($g) => in_array($_SESSION['username'], $g['members']));
$group_recs = [];
foreach ($my_groups_list as $g) {
    $current_book = getBookById($g['current_book_id']);
    $group_genres = $current_book['genre'] ?? [];

    $candidates = array_filter($books, fn($b) => $b['id'] !== $g['current_book_id']);
    $gscored = [];
    foreach ($candidates as $b) {
        $overlap = count(array_intersect($b['genre'], $group_genres));
        $b['_overlap'] = $overlap;
        $b['_gscore'] = $b['rating'] + $overlap * 0.5;
        $gscored[] = $b;
    }
    usort($gscored, fn($a,$b) => $b['_gscore'] <=> $a['_gscore']);
    $group_recs[$g['id']] = [
        'group' => $g,
        'recs' => array_slice($gscored, 0, 3),
    ];
}
?>

<style>
.rec-card {
    display:flex; gap:1rem; padding:1rem 0;
    border-bottom:1px solid rgba(134,113,91,0.15);
}
.rec-rank {
    font-family:'IM Fell English',serif;
    font-size:1.8rem; color:rgba(134,113,91,0.35);
    min-width:28px; text-align:center; line-height:1.2;
}
.rec-cover { width:56px; height:84px; object-fit:cover; border:1px solid rgba(134,113,91,0.3); border-radius:1px; flex-shrink:0; }
.genre-pill {
    display:inline-block;
    background:rgba(36,46,15,0.5); border:1px solid rgba(134,113,91,0.3);
    border-radius:2px; padding:0.1rem 0.5rem; font-size:0.72rem;
    color:var(--text-muted); letter-spacing:0.06em; text-transform:uppercase;
}
.affinity-bar {
    height:4px; border-radius:2px; background:rgba(134,113,91,0.2);
    margin-top:0.3rem; overflow:hidden;
}
.affinity-fill { height:100%; background: linear-gradient(90deg, var(--umber), #c9a87c); border-radius:2px; }
</style>

<h2 class="page-heading">Discoveries</h2>
<p style="color:var(--text-muted); font-style:italic; margin-bottom:2rem;">
    Curated for you from your reading history and circle affinities.
</p>

<!-- Genre affinities -->
<?php if (!empty($top_genres)): ?>
<div class="n-card p-4 mb-4">
    <h6 style="font-size:0.75rem; letter-spacing:0.12em; text-transform:uppercase; color:var(--text-muted); margin-bottom:1rem;">Your Literary Affinities</h6>
    <div class="row g-3">
        <?php foreach ($genre_score as $genre => $score): ?>
        <div class="col-md-4">
            <div style="display:flex; justify-content:space-between; margin-bottom:0.2rem;">
                <span style="font-size:0.85rem;"><?php echo htmlspecialchars($genre); ?></span>
                <span style="font-size:0.75rem; color:var(--text-muted);"><?php echo $score; ?>pts</span>
            </div>
            <div class="affinity-bar">
                <div class="affinity-fill" style="width:<?php echo min(100, ($score / 25) * 100); ?>%"></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-6">
        <!-- Personal recommendations -->
        <div class="n-card p-4 mb-4">
            <h5 style="font-family:'IM Fell English',serif; margin-bottom:0.3rem;">For You</h5>
            <p style="font-size:0.82rem; color:var(--text-muted); margin-bottom:1.2rem;">
                Based on your ratings and genre affinities.
            </p>
            <?php foreach ($personal_recs as $i => $b): ?>
            <div class="rec-card">
                <div class="rec-rank"><?php echo $i+1; ?></div>
                <a href="books.php?id=<?php echo $b['id']; ?>">
                    <img src="<?php echo $b['cover']; ?>" class="rec-cover"
                         alt="<?php echo htmlspecialchars($b['title']); ?>"
                         onerror="this.src='https://via.placeholder.com/56x84/39304A/DCBCCE?text=?'">
                </a>
                <div class="flex-grow-1">
                    <a href="books.php?id=<?php echo $b['id']; ?>" style="text-decoration:none;">
                        <div style="font-family:'Cormorant Garamond',serif; font-size:1.05rem; color:var(--blush); margin-bottom:0.1rem;">
                            <?php echo htmlspecialchars($b['title']); ?>
                        </div>
                    </a>
                    <div style="font-size:0.82rem; color:var(--text-muted); font-style:italic; margin-bottom:0.3rem;">
                        <?php echo htmlspecialchars($b['author']); ?> · <?php echo $b['year']; ?>
                    </div>
                    <div class="d-flex flex-wrap gap-1 mb-2">
                        <?php foreach ($b['genre'] as $g): ?>
                        <span class="genre-pill <?php echo in_array($g, $top_genres)?'':''; ?>"
                              style="<?php echo in_array($g,$top_genres)?'border-color:var(--umber);color:var(--blush);':''; ?>">
                            <?php echo $g; ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <?php echo renderStars($b['rating']); ?>
                        <span style="font-size:0.78rem; color:var(--text-muted);"><?php echo $b['rating']; ?></span>
                        <span style="font-size:0.72rem; color:var(--umber); margin-left:auto;">Score: <?php echo $b['_score']; ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($personal_recs)): ?>
            <p style="color:var(--text-muted); font-style:italic;">Rate some books to unlock personal recommendations.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-6">
        <!-- Group recommendations -->
        <?php foreach ($group_recs as $gr): ?>
        <div class="n-card p-4 mb-4">
            <h5 style="font-family:'IM Fell English',serif; margin-bottom:0.2rem;">
                For <a href="groups.php?id=<?php echo $gr['group']['id']; ?>" style="color:var(--blush); text-decoration:none;">
                    <?php echo htmlspecialchars($gr['group']['name']); ?>
                </a>
            </h5>
            <p style="font-size:0.82rem; color:var(--text-muted); margin-bottom:1.2rem;">
                After finishing <em><?php echo htmlspecialchars(getBookById($gr['group']['current_book_id'])['title']); ?></em>.
            </p>
            <?php foreach ($gr['recs'] as $i => $b): ?>
            <div class="rec-card">
                <div class="rec-rank"><?php echo $i+1; ?></div>
                <a href="books.php?id=<?php echo $b['id']; ?>">
                    <img src="<?php echo $b['cover']; ?>" class="rec-cover"
                         alt="<?php echo htmlspecialchars($b['title']); ?>"
                         onerror="this.src='https://via.placeholder.com/56x84/39304A/DCBCCE?text=?'">
                </a>
                <div class="flex-grow-1">
                    <a href="books.php?id=<?php echo $b['id']; ?>" style="text-decoration:none;">
                        <div style="font-family:'Cormorant Garamond',serif; font-size:1.05rem; color:var(--blush); margin-bottom:0.1rem;">
                            <?php echo htmlspecialchars($b['title']); ?>
                        </div>
                    </a>
                    <div style="font-size:0.82rem; color:var(--text-muted); font-style:italic; margin-bottom:0.3rem;">
                        <?php echo htmlspecialchars($b['author']); ?>
                    </div>
                    <div class="d-flex flex-wrap gap-1 mb-2">
                        <?php foreach ($b['genre'] as $g): ?>
                        <span class="genre-pill"><?php echo $g; ?></span>
                        <?php endforeach; ?>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <?php echo renderStars($b['rating']); ?>
                        <span style="font-size:0.78rem; color:var(--text-muted);"><?php echo $b['rating']; ?></span>
                        <?php if ($b['_overlap'] > 0): ?>
                        <span style="font-size:0.72rem; color:var(--umber); margin-left:auto;">
                            <?php echo $b['_overlap']; ?> genre overlap
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Suggest to group -->
<div class="n-card p-4">
    <h5 style="font-family:'IM Fell English',serif; margin-bottom:0.8rem;">Suggest a Book to a Circle</h5>
    <form class="row g-2" method="post">
        <div class="col-md-4">
            <select class="form-select" name="sug_group">
                <?php foreach ($my_groups_list as $g): ?>
                <option value="<?php echo $g['id']; ?>"><?php echo htmlspecialchars($g['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <select class="form-select" name="sug_book">
                <?php foreach ($books as $b): ?>
                <option value="<?php echo $b['id']; ?>"><?php echo htmlspecialchars($b['title']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <input type="text" class="form-control" name="sug_note" placeholder="Why this book?">
        </div>
        <div class="col-12">
            <button type="submit" class="btn-n btn">Send Suggestion</button>
        </div>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>