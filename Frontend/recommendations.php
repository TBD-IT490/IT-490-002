<?php
require_once 'includes/data.php';
require_once 'includes/header.php';

$msg = '';

// ── POST HANDLER ──────────────────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['suggest_book'])) {
    $result = api_post('suggestions', [
        'group_id' => (int)($_POST['sug_group'] ?? 0),
        'book_id'  => (int)($_POST['sug_book']  ?? 0),
        'user_id'  => $_SESSION['id'],
        'note'     => trim($_POST['sug_note'] ?? ''),
    ]);
    // Expected API response: { success: true }
    $msg = ($result['success'] ?? false)
        ? 'success:Your suggestion has been sent to the circle.'
        : 'error:Could not send suggestion. Please try again.';
}

list($msg_type, $msg_text) = $msg ? explode(':', $msg, 2) : ['', ''];

// ── DATA FETCHING ─────────────────────────────────────────────

// Personal recommendations for the current user
// Expected API response:
// {
//   recommendations: [{ id, title, author, cover, genre[], year, rating, score }, ...],
//   genre_affinity:  { "Mystery": 22, "Gothic": 18, ... }  (genre → score, sorted desc)
// }
$personal_data  = api_get("users/{$_SESSION['id']}/recommendations") ?? [];
$personal_recs  = $personal_data['recommendations']  ?? [];
$genre_score    = $personal_data['genre_affinity']   ?? [];
$top_genres     = array_slice(array_keys($genre_score), 0, 3);

// Group recommendations — one set per circle the user belongs to
// Expected API response:
// [
//   {
//     group: { id, name, current_book_id },
//     recommendations: [{ id, title, author, cover, genre[], rating, genre_overlap }, ...]
//   },
//   ...
// ]
$group_recs_data = api_get("users/{$_SESSION['id']}/group-recommendations") ?? [];

// Books list for the "suggest to a circle" dropdown
// Expected API response: [{ id, title }, ...]
$books_for_select = api_get('books?fields=id,title') ?? [];

// $my_groups already fetched in data.php — used for the group selector dropdown
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
.rec-cover {
    width:56px; height:84px; object-fit:cover;
    border:1px solid rgba(134,113,91,0.3); border-radius:1px; flex-shrink:0;
}
.genre-pill {
    display:inline-block;
    background:rgba(36,46,15,0.5); border:1px solid rgba(134,113,91,0.3);
    border-radius:2px; padding:0.1rem 0.5rem; font-size:0.72rem;
    color:var(--text-muted); letter-spacing:0.06em; text-transform:uppercase;
}
.affinity-bar  { height:4px; border-radius:2px; background:rgba(134,113,91,0.2); margin-top:0.3rem; overflow:hidden; }
.affinity-fill { height:100%; background:linear-gradient(90deg, var(--umber), #c9a87c); border-radius:2px; }
</style>

<h2 class="page-heading">Discoveries</h2>
<p style="color:var(--text-muted); font-style:italic; margin-bottom:2rem;">
    Curated for you from your reading history and circle affinities.
</p>

<?php if ($msg_text): ?>
<div class="n-alert mb-4 <?php echo $msg_type === 'error' ? 'border-danger' : ''; ?>">
    <?php echo $msg_text; ?>
</div>
<?php endif; ?>

<!-- Genre affinity bars -->
<?php if (!empty($genre_score)): ?>
<div class="n-card p-4 mb-4">
    <h6 style="font-size:0.75rem; letter-spacing:0.12em; text-transform:uppercase; color:var(--text-muted); margin-bottom:1rem;">
        Your Literary Affinities
    </h6>
    <div class="row g-3">
        <?php
        $max_score = max($genre_score) ?: 1;
        foreach ($genre_score as $genre => $score):
        ?>
        <div class="col-md-4">
            <div style="display:flex; justify-content:space-between; margin-bottom:0.2rem;">
                <span style="font-size:0.85rem;"><?php echo htmlspecialchars($genre); ?></span>
                <span style="font-size:0.75rem; color:var(--text-muted);"><?php echo $score; ?>pts</span>
            </div>
            <div class="affinity-bar">
                <div class="affinity-fill" style="width:<?php echo round(($score / $max_score) * 100); ?>%"></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<div class="row g-4">

    <!-- Personal recommendations -->
    <div class="col-lg-6">
        <div class="n-card p-4 mb-4">
            <h5 style="font-family:'IM Fell English',serif; margin-bottom:0.3rem;">For You</h5>
            <p style="font-size:0.82rem; color:var(--text-muted); margin-bottom:1.2rem;">
                Based on your ratings and genre affinities.
            </p>

            <?php if (!empty($personal_recs)): ?>
                <?php foreach ($personal_recs as $i => $b): ?>
                <div class="rec-card">
                    <div class="rec-rank"><?php echo $i + 1; ?></div>
                    <a href="books.php?id=<?php echo $b['id']; ?>">
                        <img src="<?php echo htmlspecialchars($b['cover']); ?>"
                             class="rec-cover"
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
                            <span class="genre-pill"
                                  style="<?php echo in_array($g, $top_genres) ? 'border-color:var(--umber);color:var(--blush);' : ''; ?>">
                                <?php echo htmlspecialchars($g); ?>
                            </span>
                            <?php endforeach; ?>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <?php echo renderStars($b['rating']); ?>
                            <span style="font-size:0.78rem; color:var(--text-muted);"><?php echo $b['rating']; ?></span>
                            <?php if (isset($b['score'])): ?>
                            <span style="font-size:0.72rem; color:var(--umber); margin-left:auto;">
                                Score: <?php echo $b['score']; ?>
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
            <p style="color:var(--text-muted); font-style:italic;">
                Rate some books to unlock personal recommendations.
            </p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Group recommendations -->
    <div class="col-lg-6">
        <?php foreach ($group_recs_data as $gr):
            $grp      = $gr['group']           ?? [];
            $grp_recs = $gr['recommendations'] ?? [];
            $grp_book = getBookById((int)($grp['current_book_id'] ?? 0));
        ?>
        <div class="n-card p-4 mb-4">
            <h5 style="font-family:'IM Fell English',serif; margin-bottom:0.2rem;">
                For <a href="groups.php?id=<?php echo $grp['id']; ?>"
                       style="color:var(--blush); text-decoration:none;">
                    <?php echo htmlspecialchars($grp['name']); ?>
                </a>
            </h5>
            <?php if ($grp_book): ?>
            <p style="font-size:0.82rem; color:var(--text-muted); margin-bottom:1.2rem;">
                After finishing <em><?php echo htmlspecialchars($grp_book['title']); ?></em>.
            </p>
            <?php endif; ?>

            <?php foreach ($grp_recs as $i => $b): ?>
            <div class="rec-card">
                <div class="rec-rank"><?php echo $i + 1; ?></div>
                <a href="books.php?id=<?php echo $b['id']; ?>">
                    <img src="<?php echo htmlspecialchars($b['cover']); ?>"
                         class="rec-cover"
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
                        <span class="genre-pill"><?php echo htmlspecialchars($g); ?></span>
                        <?php endforeach; ?>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <?php echo renderStars($b['rating']); ?>
                        <span style="font-size:0.78rem; color:var(--text-muted);"><?php echo $b['rating']; ?></span>
                        <?php if (!empty($b['genre_overlap'])): ?>
                        <span style="font-size:0.72rem; color:var(--umber); margin-left:auto;">
                            <?php echo $b['genre_overlap']; ?> genre overlap
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

            <?php if (empty($grp_recs)): ?>
            <p style="color:var(--text-muted); font-style:italic; font-size:0.9rem;">
                No recommendations available yet.
            </p>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Suggest a book to a circle -->
<div class="n-card p-4">
    <h5 style="font-family:'IM Fell English',serif; margin-bottom:0.8rem;">Suggest a Book to a Circle</h5>
    <form class="row g-2" method="post">
        <input type="hidden" name="suggest_book" value="1">
        <div class="col-md-4">
            <label class="form-label">Circle</label>
            <select class="form-select" name="sug_group" required>
                <?php foreach ($my_groups as $g): ?>
                <option value="<?php echo $g['id']; ?>"><?php echo htmlspecialchars($g['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Book</label>
            <select class="form-select" name="sug_book" required>
                <?php foreach ($books_for_select as $b): ?>
                <option value="<?php echo $b['id']; ?>"><?php echo htmlspecialchars($b['title']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Note (optional)</label>
            <input type="text" class="form-control" name="sug_note" placeholder="Why this book?">
        </div>
        <div class="col-12">
            <button type="submit" class="btn-n btn">Send Suggestion</button>
        </div>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>