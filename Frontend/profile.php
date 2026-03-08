<?php
require_once 'includes/data.php';
require_once 'includes/header.php';

$msg = '';
$tab = $_GET['tab'] ?? 'books';

// ── POST HANDLER ──────────────────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // RabbitMQ action: 'user.update'
    // Expected response: { success: true }
    $result = rmq_rpc('user.update', [
        'display_name' => trim($_POST['display_name'] ?? ''),
        'email'        => trim($_POST['email']        ?? ''),
        'bio'          => trim($_POST['bio']           ?? ''),
        'preferences'  => $_POST['prefs']             ?? [],
    ]);
    $msg = ($result['success'] ?? false)
        ? 'Profile updated.'
        : 'Could not save changes. Please try again.';
}

// ── DATA FETCHING ─────────────────────────────────────────────

// Full profile + stats
// RabbitMQ action: 'user.profile'
// Expected response:
// {
//   id, username, display_name, email, bio, avatar_url, member_since,
//   stats: { books_rated, reviews_written, avg_rating, pages_read },
//   genre_affinity: { "Mystery": 5, "Gothic": 3, ... }
// }
$profile_res = rmq_rpc('user.profile') ?? [];
$stats       = $profile_res['stats']          ?? [];
$genre_count = $profile_res['genre_affinity'] ?? [];
arsort($genre_count);

// Rated books — only fetched on the books tab
// RabbitMQ action: 'user.ratings'
// Expected response: { ratings: [{ book: { id, title, author, cover }, rating }, ...] }
$my_ratings = [];
if ($tab === 'books') {
    $ratings_res = rmq_rpc('user.ratings') ?? [];
    $my_ratings  = $ratings_res['ratings'] ?? [];
}

// Written reviews — only fetched on the reviews tab
// RabbitMQ action: 'user.reviews'
// Expected response: { reviews: [{ id, book: { id, title, cover }, rating, title, body, created }, ...] }
$my_reviews = [];
if ($tab === 'reviews') {
    $reviews_res = rmq_rpc('user.reviews') ?? [];
    $my_reviews  = $reviews_res['reviews'] ?? [];
}

// Settings — only fetched on the settings tab
// RabbitMQ action: 'user.settings'
// Expected response: { display_name, email, bio, preferences: [...] }
$user_settings = [];
if ($tab === 'settings') {
    $settings_res  = rmq_rpc('user.settings') ?? [];
    $user_settings = $settings_res ?? [];
}
$saved_prefs = $user_settings['preferences'] ?? [];

// $my_groups and $genres already fetched in data.php
?>

<style>
.profile-banner {
    height:120px;
    background:linear-gradient(135deg, var(--moss) 0%, var(--card) 40%, #2a1f35 100%);
    border-radius:4px 4px 0 0;
    border:1px solid rgba(134,113,91,0.2);
    border-bottom:none;
}
.profile-avatar {
    width:80px; height:80px; border-radius:50%;
    background:var(--moss); border:3px solid var(--card);
    display:flex; align-items:center; justify-content:center;
    font-family:'IM Fell English',serif; font-size:2rem; color:var(--blush);
    position:absolute; bottom:-40px; left:1.5rem; overflow:hidden;
}
.profile-avatar img { width:100%; height:100%; object-fit:cover; }
.rated-book { display:flex; align-items:center; gap:0.8rem; padding:0.6rem 0; border-bottom:1px solid rgba(134,113,91,0.12); }
.tab-nav { display:flex; gap:0; border-bottom:1px solid rgba(134,113,91,0.3); margin-bottom:1.5rem; }
.tab-link {
    padding:0.5rem 1.2rem; font-size:0.8rem; letter-spacing:0.1em;
    text-transform:uppercase; color:var(--text-muted); text-decoration:none;
    border-bottom:2px solid transparent; margin-bottom:-1px;
    transition:color 0.2s, border-color 0.2s;
}
.tab-link.active, .tab-link:hover { color:var(--blush); border-bottom-color:var(--umber); }
</style>

<?php if ($msg): ?>
<div class="n-alert mb-3"><?php echo htmlspecialchars($msg); ?></div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-8">

        <!-- Profile header -->
        <div style="position:relative; margin-bottom:3rem;">
            <div class="profile-banner"></div>
            <div class="n-card p-4 pt-0" style="border-top:none; border-radius:0 0 4px 4px;">
                <div class="profile-avatar">
                    <?php if (!empty($profile_res['avatar_url'])): ?>
                        <img src="<?php echo htmlspecialchars($profile_res['avatar_url']); ?>" alt="">
                    <?php else: ?>
                        <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                    <?php endif; ?>
                </div>
                <div style="margin-left:calc(80px + 1.5rem + 0.8rem); padding-top:0.5rem;" class="d-flex justify-content-between align-items-start">
                    <div>
                        <h3 style="font-family:'IM Fell English',serif; margin:0;">
                            <?php echo htmlspecialchars($profile_res['display_name'] ?? $_SESSION['username']); ?>
                        </h3>
                        <div style="font-size:0.82rem; color:var(--text-muted);">
                            @<?php echo htmlspecialchars($_SESSION['username']); ?>
                            &nbsp;·&nbsp; Member since <?php echo $profile_res['member_since'] ?? '—'; ?>
                        </div>
                        <?php if (!empty($profile_res['bio'])): ?>
                        <div style="font-size:0.95rem; color:var(--text-muted); font-style:italic; margin-top:0.4rem;">
                            <?php echo htmlspecialchars($profile_res['bio']); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <a href="logout.php"
                       style="font-family:'Crimson Text',serif; font-size:0.78rem; letter-spacing:0.1em; text-transform:uppercase; color:var(--text-muted); text-decoration:none; border:1px solid rgba(134,113,91,0.3); border-radius:2px; padding:0.3rem 0.8rem; transition:color 0.2s, border-color 0.2s; white-space:nowrap;"
                       onmouseover="this.style.color='var(--blush)'; this.style.borderColor='var(--umber)'"
                       onmouseout="this.style.color='var(--text-muted)'; this.style.borderColor='rgba(134,113,91,0.3)'">
                        <i class="bi bi-box-arrow-right"></i> Log Out
                    </a>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="tab-nav">
            <a href="?tab=books"    class="tab-link <?php echo $tab === 'books'    ? 'active' : ''; ?>">Rated Books</a>
            <a href="?tab=reviews"  class="tab-link <?php echo $tab === 'reviews'  ? 'active' : ''; ?>">Reviews</a>
            <a href="?tab=circles"  class="tab-link <?php echo $tab === 'circles'  ? 'active' : ''; ?>">Circles</a>
            <a href="?tab=settings" class="tab-link <?php echo $tab === 'settings' ? 'active' : ''; ?>">Settings</a>
        </div>

        <!-- ── RATED BOOKS ── -->
        <?php if ($tab === 'books'): ?>

        <?php if (empty($my_ratings)): ?>
        <p style="color:var(--text-muted); font-style:italic;">
            No books rated yet. <a href="books.php" style="color:var(--umber);">Browse the library.</a>
        </p>
        <?php else: ?>
            <?php foreach ($my_ratings as $entry):
                $b      = $entry['book']   ?? [];
                $rating = $entry['rating'] ?? 0;
            ?>
            <div class="rated-book">
                <a href="books.php?id=<?php echo $b['id']; ?>">
                    <img src="<?php echo htmlspecialchars($b['cover']); ?>"
                         style="width:36px;height:54px;object-fit:cover;border:1px solid rgba(134,113,91,0.3);border-radius:1px;" alt="">
                </a>
                <div class="flex-grow-1">
                    <a href="books.php?id=<?php echo $b['id']; ?>"
                       style="text-decoration:none; color:var(--blush); font-family:'Cormorant Garamond',serif; font-size:1rem;">
                        <?php echo htmlspecialchars($b['title']); ?>
                    </a>
                    <div style="font-size:0.8rem; color:var(--text-muted); font-style:italic;"><?php echo htmlspecialchars($b['author']); ?></div>
                </div>
                <div><?php echo renderStars($rating); ?></div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- ── REVIEWS ── -->
        <?php elseif ($tab === 'reviews'): ?>

        <?php if (empty($my_reviews)): ?>
        <p style="color:var(--text-muted); font-style:italic;">No reviews written yet.</p>
        <?php else: ?>
            <?php foreach ($my_reviews as $rv):
                $rvb = $rv['book'] ?? [];
            ?>
            <div style="border-bottom:1px solid rgba(134,113,91,0.2); padding-bottom:1.2rem; margin-bottom:1.2rem;">
                <div class="d-flex gap-3 align-items-start">
                    <img src="<?php echo htmlspecialchars($rvb['cover'] ?? ''); ?>"
                         style="width:50px;height:75px;object-fit:cover;border:1px solid rgba(134,113,91,0.3);border-radius:1px;flex-shrink:0;" alt="">
                    <div>
                        <div style="font-size:0.8rem; color:var(--text-muted); margin-bottom:0.2rem;">
                            <a href="books.php?id=<?php echo $rvb['id']; ?>"
                               style="color:var(--umber); text-decoration:none; font-style:italic;">
                                <?php echo htmlspecialchars($rvb['title'] ?? ''); ?>
                            </a>
                            &nbsp;·&nbsp; <?php echo $rv['created']; ?>
                        </div>
                        <?php echo renderStars($rv['rating']); ?>
                        <div style="font-style:italic; color:var(--blush); margin-top:0.3rem; font-size:1rem;">
                            "<?php echo htmlspecialchars($rv['title']); ?>"
                        </div>
                        <p style="font-size:0.95rem; color:var(--text-muted); margin-top:0.3rem;">
                            <?php echo htmlspecialchars($rv['body']); ?>
                        </p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- ── CIRCLES ── -->
        <?php elseif ($tab === 'circles'): ?>

        <div class="row g-3">
            <?php foreach ($my_groups as $g):
                $cb = getBookById((int)$g['current_book_id']);
            ?>
            <div class="col-md-6">
                <a href="groups.php?id=<?php echo $g['id']; ?>" class="text-decoration-none">
                    <div class="n-card p-3 d-flex gap-3 align-items-start"
                         style="transition:border-color 0.2s;"
                         onmouseover="this.style.borderColor='rgba(134,113,91,0.5)'"
                         onmouseout="this.style.borderColor='rgba(134,113,91,0.2)'">
                        <?php if ($cb): ?>
                        <img src="<?php echo htmlspecialchars($cb['cover']); ?>"
                             style="width:40px;height:60px;object-fit:cover;border:1px solid rgba(134,113,91,0.3);border-radius:1px;flex-shrink:0;" alt="">
                        <?php endif; ?>
                        <div>
                            <div style="font-family:'Cormorant Garamond',serif; font-size:1.05rem; color:var(--blush);">
                                <?php echo htmlspecialchars($g['name']); ?>
                            </div>
                            <?php if ($cb): ?>
                            <div style="font-size:0.8rem; color:var(--text-muted); font-style:italic;"><?php echo htmlspecialchars($cb['title']); ?></div>
                            <?php endif; ?>
                            <div style="font-size:0.75rem; color:var(--text-muted);"><?php echo $g['member_count']; ?> members</div>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
            <?php if (empty($my_groups)): ?>
            <div class="col-12">
                <p style="color:var(--text-muted); font-style:italic;">
                    Not a member of any circles yet.
                    <a href="groups.php" style="color:var(--umber);">Find one to join.</a>
                </p>
            </div>
            <?php endif; ?>
        </div>

        <!-- ── SETTINGS ── -->
        <?php elseif ($tab === 'settings'): ?>

        <div class="n-card p-4">
            <h5 style="font-family:'IM Fell English',serif; margin-bottom:1.2rem;">Profile Settings</h5>
            <form method="post">
                <div class="mb-3">
                    <label class="form-label">Display Name</label>
                    <input type="text" class="form-control" name="display_name"
                           value="<?php echo htmlspecialchars($user_settings['display_name'] ?? $_SESSION['username']); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" name="email"
                           value="<?php echo htmlspecialchars($user_settings['email'] ?? ''); ?>"
                           placeholder="your@email.com">
                </div>
                <div class="mb-3">
                    <label class="form-label">Reading Preferences</label>
                    <div class="d-flex flex-wrap gap-2 mt-1">
                        <?php foreach ($genres as $g): ?>
                        <label style="cursor:pointer;">
                            <input type="checkbox" name="prefs[]" value="<?php echo htmlspecialchars($g); ?>"
                                   <?php echo in_array($g, $saved_prefs) ? 'checked' : ''; ?>
                                   style="display:none;"
                                   onchange="this.nextElementSibling.style.borderColor = this.checked ? 'var(--umber)' : '';
                                             this.nextElementSibling.style.color = this.checked ? 'var(--blush)' : '';">
                            <span class="n-badge" style="cursor:pointer;
                                  <?php echo in_array($g, $saved_prefs) ? 'border-color:var(--umber);color:var(--blush);' : ''; ?>">
                                <?php echo htmlspecialchars($g); ?>
                            </span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Bio</label>
                    <textarea class="form-control" rows="3" name="bio"
                              placeholder="A few words about your reading life…"><?php echo htmlspecialchars($user_settings['bio'] ?? ''); ?></textarea>
                </div>
                <button type="submit" class="btn-n btn">Save Changes</button>
            </form>
        </div>

        <?php endif; ?>
    </div>

    <!-- Stats sidebar -->
    <div class="col-lg-4">
        <div class="n-card p-4 mb-4">
            <h6 style="font-size:0.75rem; text-transform:uppercase; letter-spacing:0.12em; color:var(--text-muted); margin-bottom:1.2rem;">Reading Statistics</h6>
            <div class="row g-3 text-center">
                <div class="col-6">
                    <div style="font-family:'IM Fell English',serif; font-size:2rem; line-height:1;"><?php echo $stats['books_rated'] ?? 0; ?></div>
                    <div style="font-size:0.72rem; text-transform:uppercase; color:var(--text-muted);">Books Rated</div>
                </div>
                <div class="col-6">
                    <div style="font-family:'IM Fell English',serif; font-size:2rem; line-height:1;"><?php echo $stats['reviews_written'] ?? 0; ?></div>
                    <div style="font-size:0.72rem; text-transform:uppercase; color:var(--text-muted);">Reviews</div>
                </div>
                <div class="col-6">
                    <div style="font-family:'IM Fell English',serif; font-size:2rem; line-height:1;"><?php echo $stats['avg_rating'] ?? '—'; ?></div>
                    <div style="font-size:0.72rem; text-transform:uppercase; color:var(--text-muted);">Avg Rating</div>
                </div>
                <div class="col-6">
                    <div style="font-family:'IM Fell English',serif; font-size:2rem; line-height:1;"><?php echo number_format($stats['pages_read'] ?? 0); ?></div>
                    <div style="font-size:0.72rem; text-transform:uppercase; color:var(--text-muted);">Pages Read</div>
                </div>
            </div>
        </div>

        <?php if (!empty($genre_count)): ?>
        <div class="n-card p-4 mb-4">
            <h6 style="font-size:0.75rem; text-transform:uppercase; letter-spacing:0.12em; color:var(--text-muted); margin-bottom:1rem;">Genre Affinities</h6>
            <?php
            $max_gc = max($genre_count) ?: 1;
            foreach (array_slice($genre_count, 0, 5, true) as $genre => $count):
            ?>
            <div style="margin-bottom:0.6rem;">
                <div style="display:flex; justify-content:space-between; font-size:0.85rem;">
                    <span><?php echo htmlspecialchars($genre); ?></span>
                    <span style="color:var(--text-muted);"><?php echo $count; ?></span>
                </div>
                <div style="height:3px; background:rgba(134,113,91,0.2); border-radius:2px; margin-top:3px;">
                    <div style="height:100%; width:<?php echo round(($count / $max_gc) * 100); ?>%; background:linear-gradient(90deg,var(--umber),#c9a87c); border-radius:2px;"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="n-card p-4">
            <h6 style="font-size:0.75rem; text-transform:uppercase; letter-spacing:0.12em; color:var(--text-muted); margin-bottom:0.8rem;">My Circles</h6>
            <?php foreach ($my_groups as $g): ?>
            <a href="groups.php?id=<?php echo $g['id']; ?>"
               style="display:flex; align-items:center; gap:0.5rem; text-decoration:none; margin-bottom:0.5rem;">
                <div class="avatar-ring" style="width:28px;height:28px;font-size:0.7rem;">
                    <?php echo strtoupper(substr($g['name'], 0, 1)); ?>
                </div>
                <span style="font-size:0.9rem; color:var(--text-muted); transition:color 0.2s;"
                      onmouseover="this.style.color='var(--blush)'"
                      onmouseout="this.style.color='var(--text-muted)'">
                    <?php echo htmlspecialchars($g['name']); ?>
                </span>
            </a>
            <?php endforeach; ?>
            <?php if (empty($my_groups)): ?>
            <p style="font-size:0.85rem; color:var(--text-muted); font-style:italic;">No circles yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>