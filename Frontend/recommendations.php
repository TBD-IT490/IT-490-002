<?php
session_start();

// If user is NOT logged in, redirect to login page
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.php");
    exit();
}

require_once 'includes/data.php';
require_once 'includes/header.php';

$msg = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['suggest_book'])) {
    
    $result = rmq_rpc('suggestion.create', [
        'group_id'=> (int)($_POST['sug_group'] ?? 0),
        'book_id'=> (int)($_POST['sug_book']  ?? 0),
        'note'=> trim($_POST['sug_note']   ?? ''),
    ]);
    $msg = ($result['success'] ?? false)
        ? 'success:Your suggestion has been sent to the circle.'
        : 'error:Could not send suggestion. Please try again.';
}

list($msg_type, $msg_text) = $msg ? explode(':', $msg, 2) : ['', ''];

$personal_res = rmq_rpc('recommendation.personal') ?? [];
$personal_recs = $personal_res['recommendations'] ?? [];
$genre_score = $personal_res['genre_score']  ?? [];
$top_genres = array_slice(array_keys($genre_score), 0, 3);

$group_recs_res  = rmq_rpc('recommendation.groups') ?? [];
$group_recs_data = $group_recs_res['groups'] ?? [];

$bselect_res = rmq_rpc('book.list', ['fields' => 'id,title']);
$books_for_select = $bselect_res['books'] ?? [];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=IM+Fell+English:ital@0;1&family=Crimson+Text:ital,wght@0,400;0,600;1,400&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="styles.css">
</head>
<h2 class="page-heading">Discoveries</h2>
<p style="color:var(--text-muted); font-style:italic; margin-bottom:2rem;">
    Curated for you from your reading history and circle affinities.
</p>

<?php if ($msg_text): ?>
<div class="n-alert mb-4 <?php echo $msg_type === 'error' ? 'border-danger' : ''; ?>">
    <?php echo $msg_text; ?>
</div>
<?php endif; ?>

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
                             onerror="this.src='<?php echo htmlspecialchars($b['cover_url']); ?>'">
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
                            <?php if (!empty($b['score'])): ?>
                            <span style="font-size:0.72rem; color:var(--umber); margin-left:auto;">Score: <?php echo $b['score']; ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
            <p style="color:var(--text-muted); font-style:italic;">Rate some books to unlock personal recommendations.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-6">
        <?php foreach ($group_recs_data as $gr):
            $grp      = $gr['group']           ?? [];
            $grp_recs = $gr['recommendations'] ?? [];
            $grp_book = !empty($grp['current_book_id']) ? getBookById((int)$grp['current_book_id']) : null;
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
                         onerror="this.src='<?php echo htmlspecialchars($b['cover_url']); ?>'">
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
            <p style="color:var(--text-muted); font-style:italic; font-size:0.9rem;">No recommendations available yet.</p>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>

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
</html>

<?php require_once 'includes/footer.php'; ?>