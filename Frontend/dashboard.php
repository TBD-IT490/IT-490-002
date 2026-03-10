<?php

session_start();

// If user is NOT logged in, redirect to login page
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== TRUE) {
    header("Location: index.php");
    exit();
}

// Get username from session (NOT from $_GET)
$username = $_SESSION['username'];
require_once 'includes/data.php';
require_once 'includes/header.php';

// Fetch dashboard data via RabbitMQ
// Recent reviews across all users
$reviews_res = rmq_rpc(action: 'review.recent') ?? [];
$user_reviews = $reviews_res['reviews'] ?? [];
$recent_reviews = array_slice($user_reviews, -3);

// Schedule/gatherings
$schedule_res = rmq_rpc('schedule.list') ?? [];
$schedule = $schedule_res['events'] ?? [];

// Books for the library strip
$books_res = rmq_rpc('book.list') ?? [];
$books = $books_res['books'] ?? [];

// Groups for create
$groups_response = rmq_rpc('group.list_all');
$my_groups = $groups_response['groups'] ?? [];


// Recent discussions
$discussions_res = rmq_rpc('discussion.recent') ?? [];
$discussions = $discussions_res['discussions'] ?? [];

// User ratings count
$ratings_res = rmq_rpc('user.ratings') ?? [];
$user_ratings = $ratings_res['ratings'] ?? [];

// Upcoming gathering
$next_event = $schedule[0] ?? null;
$next_book = $next_event ? getBookById($next_event['book_id']) : null;
$next_group = $next_event ? getGroupById($next_event['group_id']) : null;

?>

<style>
.hero-quote {
    border-left: 2px solid var(--umber);
    padding-left: 1.5rem;
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.35rem;
    font-style: italic;
    color: var(--blush);
    opacity: 0.8;
    margin-bottom: 2rem;
}
.stat-num {
    font-family: 'IM Fell English', serif;
    font-size: 2.4rem;
    color: var(--blush);
    line-height: 1;
}
.stat-label { font-size: 0.75rem; letter-spacing: 0.12em; text-transform: uppercase; color: var(--text-muted); }
.book-strip {
    display: flex; gap: 1rem; overflow-x: auto; padding-bottom: 0.5rem;
    scrollbar-width: thin;
}
.book-strip-item {
    flex-shrink: 0; width: 90px; cursor: pointer;
    transition: transform 0.2s;
}
.book-strip-item:hover { transform: translateY(-4px); }
.book-strip-item img {
    width: 90px; height: 134px; object-fit: cover;
    border: 1px solid rgba(134,113,91,0.3);
    border-radius: 2px;
}
.book-strip-item .title {
    font-size: 0.72rem; color: var(--text-muted);
    margin-top: 0.35rem; line-height: 1.3;
}
.activity-dot {
    width: 8px; height: 8px; border-radius: 50%;
    background: var(--umber); flex-shrink: 0; margin-top: 6px;
}
</style>

<div class="row g-4 mb-5">
    <div class="col-12">
        <div class="hero-quote">
            "The books that the world calls immoral are books that show the world its own shame."
            <div style="font-family:'Crimson Text',serif; font-size:0.85rem; font-style:normal; color:var(--text-muted); margin-top:0.4rem; letter-spacing:0.08em;">
                — Oscar Wilde
            </div>
        </div>
    </div>
</div>

<!-- Stats row -->
<div class="row g-3 mb-4">
    <?php
    // $my_groups is already fetched in data.php via 'group.list_for_user'
    $my_rated = count($user_ratings);
    ?>
    <div class="col-6 col-md-3">
        <div class="n-card p-3 text-center">
            <div class="stat-num"><?php echo $my_rated; ?></div>
            <div class="stat-label">Books Rated</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="n-card p-3 text-center">
            <div class="stat-num"><?php echo count($my_groups); ?></div>
            <div class="stat-label">Circles Joined</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="n-card p-3 text-center">
            <div class="stat-num"><?php echo count($user_reviews); ?></div>
            <div class="stat-label">Reviews Written</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="n-card p-3 text-center">
            <div class="stat-num"><?php echo count($schedule); ?></div>
            <div class="stat-label">Gatherings Planned</div>
        </div>
    </div>
</div>

<div class="row g-4">

    <!-- Left column -->
    <div class="col-lg-8">

        <!-- Currently reading strip -->
        <div class="n-card p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 style="font-family:'IM Fell English',serif; margin:0;">The Library</h5>
                <a href="books.php" class="btn-n-outline btn btn-sm">Browse All</a>
            </div>
            <div class="book-strip">
                <?php foreach (array_slice($books, 0, 8) as $b): ?>
                <a href="books.php?id=<?php echo $b['id']; ?>" class="book-strip-item text-decoration-none">
                    <img src="<?php echo $b['cover']; ?>" alt="<?php echo htmlspecialchars($b['title']); ?>"
                         onerror="this.src='<?php echo htmlspecialchars($b['cover_url']); ?>'">
                    <div class="title"><?php echo htmlspecialchars($b['title']); ?></div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Recent discussions -->
        <div class="n-card p-4">
            <h5 style="font-family:'IM Fell English',serif; margin-bottom:1.2rem;">Recent Discussions</h5>
            <?php foreach ($discussions as $d):
                $dbook = getBookById($d['book_id']);
                $dgroup = getGroupById($d['group_id']);
            ?>
            <div class="mb-4" style="border-bottom:1px solid rgba(134,113,91,0.15); padding-bottom:1rem;">
                <div class="d-flex gap-2 align-items-start">
                    <div class="activity-dot"></div>
                    <div class="flex-grow-1">
                        <div style="font-size:0.8rem; color:var(--text-muted); margin-bottom:0.3rem;">
                            <strong style="color:var(--blush);"><?php echo htmlspecialchars($d['author']); ?></strong>
                            &nbsp;in&nbsp;
                            <a href="groups.php?id=<?php echo $d['group_id']; ?>" style="color:var(--umber); text-decoration:none;">
                                <?php echo htmlspecialchars($dgroup['name']); ?>
                            </a>
                            &nbsp;·&nbsp;
                            <a href="books.php?id=<?php echo $d['book_id']; ?>" style="color:var(--umber); text-decoration:none; font-style:italic;">
                                <?php echo htmlspecialchars($dbook['title']); ?>
                            </a>
                            &nbsp;·&nbsp; <?php echo $d['created']; ?>
                        </div>
                        <p style="margin:0; font-size:1rem;"><?php echo htmlspecialchars($d['content']); ?></p>
                        <?php if (!empty($d['replies'])): ?>
                        <div style="margin-top:0.6rem; padding-left:1rem; border-left:1px solid rgba(134,113,91,0.2);">
                            <?php foreach (array_slice($d['replies'],0,1) as $r): ?>
                            <div style="font-size:0.9rem; color:var(--text-muted);">
                                <strong style="color:var(--blush);"><?php echo $r['author']; ?></strong>:
                                <?php echo htmlspecialchars(substr($r['content'],0,120)); ?>…
                            </div>
                            <?php endforeach; ?>
                            <?php if (count($d['replies'])>1): ?>
                            <a href="groups.php?id=<?php echo $d['group_id']; ?>" style="font-size:0.8rem; color:var(--umber);">
                                +<?php echo count($d['replies'])-1; ?> more replies
                            </a>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

    </div>

    <!-- Right column -->
    <div class="col-lg-4">

        <!-- Next gathering -->
        <div class="n-card p-4 mb-4">
            <h6 style="letter-spacing:0.12em; text-transform:uppercase; font-size:0.75rem; color:var(--text-muted); margin-bottom:1rem;">Next Gathering</h6>
            <?php if ($next_event && $next_book): ?>
            <div style="font-family:'IM Fell English',serif; font-size:1.2rem; margin-bottom:0.4rem;">
                <?php echo htmlspecialchars($next_event['title']); ?>
            </div>
            <div style="font-size:0.85rem; color:var(--text-muted); margin-bottom:0.8rem;">
                <?php echo date('F j, Y', strtotime($next_event['date'])); ?> at <?php echo $next_event['time']; ?>
            </div>
            <div class="d-flex align-items-center gap-2 mb-2">
                <img src="<?php echo $next_book['cover']; ?>" style="width:40px; height:60px; object-fit:cover; border:1px solid rgba(134,113,91,0.3); border-radius:1px;" alt="">
                <div>
                    <div style="font-style:italic;"><?php echo htmlspecialchars($next_book['title']); ?></div>
                    <div style="font-size:0.8rem; color:var(--text-muted);"><?php echo $next_book['author']; ?></div>
                </div>
            </div>
            <div style="font-size:0.82rem; color:var(--text-muted);"><i class="bi bi-geo-alt"></i> <?php echo $next_event['format']; ?></div>
            <?php else: ?>
            <p style="color:var(--text-muted); font-style:italic;">No upcoming gatherings scheduled.</p>
            <?php endif; ?>
            <a href="schedule.php" class="btn-n-outline btn w-100 mt-3">View All Gatherings</a>
        </div>

        <!-- My circles -->
        <div class="n-card p-4 mb-4">
            <h6 style="letter-spacing:0.12em; text-transform:uppercase; font-size:0.75rem; color:var(--text-muted); margin-bottom:1rem;">My Circles</h6>
            <?php foreach ($my_groups as $g):
                $cb = getBookById($g['current_book_id']);
            ?>
            <a href="groups.php?id=<?php echo $g['id']; ?>" class="d-flex align-items-center gap-3 text-decoration-none mb-3"
               style="padding:0.6rem; border:1px solid rgba(134,113,91,0.15); border-radius:2px; transition:border-color 0.2s;"
               onmouseover="this.style.borderColor='rgba(134,113,91,0.4)'" onmouseout="this.style.borderColor='rgba(134,113,91,0.15)'">
                <div style="width:36px; height:54px; flex-shrink:0;">
                    <img src="<?php echo $cb['cover']; ?>" style="width:100%; height:100%; object-fit:cover; border-radius:1px;" alt="">
                </div>
                <div>
                    <div style="color:var(--blush); font-family:'Cormorant Garamond',serif; font-size:1rem;"><?php echo htmlspecialchars($g['name']); ?></div>
                    <div style="font-size:0.78rem; color:var(--text-muted);"><?php echo $g['member_count']; ?> members · <em><?php echo htmlspecialchars($cb['title']); ?></em></div>
                </div>
            </a>
            <?php endforeach; ?>
            <a href="groups.php" class="btn-n-outline btn w-100 mt-1">All Circles</a>
        </div>

        <!-- Recent reviews -->
        <div class="n-card p-4">
            <h6 style="letter-spacing:0.12em; text-transform:uppercase; font-size:0.75rem; color:var(--text-muted); margin-bottom:1rem;">Recent Reviews</h6>
            <?php foreach ($recent_reviews as $rv):
                $rvb = getBookById($rv['book_id']);
            ?>
            <div style="border-bottom:1px solid rgba(134,113,91,0.15); padding-bottom:0.8rem; margin-bottom:0.8rem;">
                <div style="font-size:0.8rem; color:var(--text-muted);"><?php echo $rv['user']; ?> · <em><?php echo $rvb['title']; ?></em></div>
                <?php echo renderStars($rv['rating']); ?>
                <div style="font-style:italic; font-size:0.95rem; color:var(--blush);">"<?php echo htmlspecialchars($rv['title']); ?>"</div>
            </div>
            <?php endforeach; ?>
        </div>

    </div>
</div>

<?php require_once 'includes/footer.php'; ?>