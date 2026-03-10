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
$msg     = '';

// ── POST HANDLERS ─────────────────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Join by invite code
    // RabbitMQ action: 'group.join'
    // Expected response: { success: true, club_id, groups: <name string>, username, message }
    if (isset($_POST['join_code'])) {
        $result = rmq_rpc('group.join', [
            'invite_code' => strtoupper(trim($_POST['invite_code'] ?? '')),
            'username'    => $_SESSION['username'] ?? '',
        ]);
        if ($result['success'] ?? false) {
            $club_id = $result['club_id'] ?? null;
            if ($club_id) {
                header("Location: groups.php?joined=1");
                exit();
            }
        }
        $msg = 'error:Invalid invite code. Please check and try again.';
    }

    // Create a new circle
    // RabbitMQ action: 'group.create'
    // Expected response: { success: true, group_name, club_id, invite_code, message }
    if (isset($_POST['create_group'])) {
        $result = rmq_rpc('group.create', [
            'name'       => trim($_POST['group_name'] ?? ''),
            'group_desc' => trim($_POST['group_desc'] ?? ''),
            'username'   => $_SESSION['username'] ?? '',
        ]);
        if ($result['success'] ?? false) {
            $name = htmlspecialchars($result['group_name'] ?? '');
            $code = htmlspecialchars($result['invite_code'] ?? '');
            $msg  = "success:Circle <em>$name</em> created! Your invite code is: <strong>$code</strong>";
        } else {
            $msg = 'error:Could not create circle. Please try again.';
        }
    }

    // Post a new discussion
    // RabbitMQ action: 'discussion.create'
    // Expected response: { success: true }
    if (isset($_POST['post_discussion']) && $view_id) {
        $result = rmq_rpc('discussion.create', [
            'group_id' => $view_id,
            'book_id'  => (int)($_POST['discuss_book'] ?? 0),
            'content'  => trim($_POST['post_content'] ?? ''),
            'username' => $_SESSION['username'] ?? '',
        ]);
        $msg = ($result['success'] ?? false)
            ? 'success:Your discussion has been posted.'
            : 'error:Could not post. Please try again.';
    }

    // Reply to a discussion
    // RabbitMQ action: 'discussion.reply'
    // Expected response: { success: true }
    if (isset($_POST['post_reply']) && $view_id) {
        rmq_rpc('discussion.reply', [
            'discussion_id' => (int)($_POST['discussion_id'] ?? 0),
            'content'       => trim($_POST['reply'] ?? ''),
            'username'      => $_SESSION['username'] ?? '',
        ]);
    }
}

list($msg_type, $msg_text) = $msg ? explode(':', $msg, 2) : ['', ''];

// ── DATA FETCHING ─────────────────────────────────────────────

if ($view_id) {
    // Single group
    // RabbitMQ action: 'group.get'
    // Expected response: { group: { id, name, description, members[], member_count, current_book_id, invite_code, created } }
    $group_res = rmq_rpc('group.get', [
        'group_id' => $view_id,
        'username' => $_SESSION['username'] ?? '',
    ]);
    $group = $group_res['group'] ?? null;

    if ($group) {
        $current_book = getBookById((int)($group['current_book_id'] ?? 0));

        // Discussions
        // RabbitMQ action: 'discussion.list'
        // Expected response: { discussions: [{ id, book_id, author, content, created, replies[] }] }
        $disc_res          = rmq_rpc('discussion.list', [
            'group_id' => $view_id,
            'username' => $_SESSION['username'] ?? '',
        ]);
        $group_discussions = $disc_res['discussions'] ?? [];

        // Schedule
        // RabbitMQ action: 'schedule.list'
        // Expected response: { events: [{ id, book_id, title, date, time, format, notes }] }
        $sched_res      = rmq_rpc('schedule.list', [
            'group_id' => $view_id,
            'username' => $_SESSION['username'] ?? '',
        ]);
        $group_schedule = $sched_res['events'] ?? [];

        // Books this group has read (for discussion post dropdown)
        // RabbitMQ action: 'group.books'
        // Expected response: { books: [{ id, title }] }
        $gbooks_res  = rmq_rpc('group.books', [
            'group_id' => $view_id,
            'username' => $_SESSION['username'] ?? '',
        ]);
        $group_books = $gbooks_res['books'] ?? [];

        $gathering_count  = count($group_schedule);
        $discussion_count = count($group_discussions);
    }

    $tab = $_GET['tab'] ?? 'discuss';

} else {
    // All circles
    // RabbitMQ action: 'group.list'
    // Expected response: { success: true, groups: [{ club_id, club_name, group_desc }] }
    $all_groups_res = rmq_rpc('group.list', [
        'username' => $_SESSION['username'] ?? '',
    ]);
    $groups = $all_groups_res['groups'] ?? [];

    // Books for create-group modal dropdown
    // RabbitMQ action: 'book.list'
    // Expected response: { books: [{ id, title }] }
    $bselect_res      = rmq_rpc('book.list', [
        'fields'   => 'id,title',
        'username' => $_SESSION['username'] ?? '',
    ]);
    $books_for_select = $bselect_res['books'] ?? [];

    // Flash message after join redirect
    if (isset($_GET['joined'])) {
        $msg_type = 'success';
        $msg_text = 'You have joined the circle. Welcome!';
    }
}
?>

<style>
.member-pill {
    display:inline-flex; align-items:center; gap:0.4rem;
    background:rgba(134,113,91,0.15); border:1px solid rgba(134,113,91,0.3);
    border-radius:20px; padding:0.2rem 0.7rem;
    font-size:0.82rem; color:var(--blush);
}
.tab-nav { display:flex; gap:0; border-bottom:1px solid rgba(134,113,91,0.3); margin-bottom:1.5rem; }
.tab-link {
    padding:0.5rem 1.2rem; font-size:0.8rem; letter-spacing:0.1em;
    text-transform:uppercase; color:var(--text-muted); text-decoration:none;
    border-bottom:2px solid transparent; margin-bottom:-1px;
    transition:color 0.2s, border-color 0.2s;
}
.tab-link.active, .tab-link:hover { color:var(--blush); border-bottom-color:var(--umber); }
</style>

<?php if ($msg_text): ?>
<div class="n-alert mb-4 <?php echo $msg_type === 'error' ? 'border-danger' : ''; ?>">
    <?php echo $msg_text; ?>
</div>
<?php endif; ?>

<?php if ($view_id): ?>

    <?php if (!$group): ?>
        <p style="color:var(--text-muted); font-style:italic;">Circle not found.</p>
    <?php else: ?>

    <div style="font-size:0.8rem; color:var(--text-muted); margin-bottom:1.5rem;">
        <a href="groups.php" style="color:var(--umber); text-decoration:none;">Circles</a>
        &nbsp;›&nbsp; <?php echo htmlspecialchars($group['name']); ?>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <h2 class="page-heading"><?php echo htmlspecialchars($group['name']); ?></h2>
            <p style="color:var(--text-muted); font-style:italic; margin-bottom:1rem;">
                <?php echo htmlspecialchars($group['description']); ?>
            </p>

            <div class="mb-3 d-flex flex-wrap gap-2">
                <?php foreach ($group['members'] as $m): ?>
                <span class="member-pill">
                    <span style="width:20px;height:20px;border-radius:50%;background:var(--moss);display:flex;align-items:center;justify-content:center;font-size:0.65rem;">
                        <?php echo strtoupper(substr($m, 0, 1)); ?>
                    </span>
                    <?php echo htmlspecialchars($m); ?>
                </span>
                <?php endforeach; ?>
                <span class="member-pill" style="cursor:pointer; border-style:dashed;"
                      data-bs-toggle="modal" data-bs-target="#inviteModal">
                    <i class="bi bi-plus"></i> Invite
                </span>
            </div>

            <div class="tab-nav">
                <a href="?id=<?php echo $view_id; ?>&tab=discuss"  class="tab-link <?php echo $tab === 'discuss'  ? 'active' : ''; ?>">Discussion Board</a>
                <a href="?id=<?php echo $view_id; ?>&tab=schedule" class="tab-link <?php echo $tab === 'schedule' ? 'active' : ''; ?>">Gatherings</a>
                <a href="?id=<?php echo $view_id; ?>&tab=members"  class="tab-link <?php echo $tab === 'members'  ? 'active' : ''; ?>">Members</a>
            </div>

            <?php if ($tab === 'discuss'): ?>

            <div class="mb-4">
                <?php if (!empty($group_discussions)): ?>
                    <?php foreach ($group_discussions as $d):
                        $dbook = getBookById((int)$d['book_id']);
                    ?>
                    <div class="n-card p-4 mb-3">
                        <div class="d-flex gap-3">
                            <div class="avatar-ring"><?php echo strtoupper(substr($d['author'], 0, 1)); ?></div>
                            <div class="flex-grow-1">
                                <div style="font-size:0.8rem; color:var(--text-muted); margin-bottom:0.4rem;">
                                    <strong style="color:var(--blush);"><?php echo htmlspecialchars($d['author']); ?></strong>
                                    <?php if ($dbook): ?>
                                    &nbsp;·&nbsp; <em><?php echo htmlspecialchars($dbook['title']); ?></em>
                                    <?php endif; ?>
                                    &nbsp;·&nbsp; <?php echo $d['created']; ?>
                                </div>
                                <p style="margin-bottom:0.8rem;"><?php echo htmlspecialchars($d['content']); ?></p>

                                <?php if (!empty($d['replies'])): ?>
                                <div style="border-left:2px solid rgba(134,113,91,0.25); padding-left:1rem; margin-top:0.5rem;">
                                    <?php foreach ($d['replies'] as $r): ?>
                                    <div class="d-flex gap-2 mb-2">
                                        <div class="avatar-ring" style="width:28px;height:28px;font-size:0.7rem;">
                                            <?php echo strtoupper(substr($r['author'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <span style="font-size:0.82rem; color:var(--blush);"><?php echo htmlspecialchars($r['author']); ?></span>
                                            <span style="font-size:0.78rem; color:var(--text-muted);"> · <?php echo $r['created']; ?></span>
                                            <p style="margin:0.2rem 0 0; font-size:0.95rem; color:var(--text-muted);"><?php echo htmlspecialchars($r['content']); ?></p>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>

                                <form method="post" class="mt-2 d-flex gap-2">
                                    <input type="hidden" name="post_reply" value="1">
                                    <input type="hidden" name="discussion_id" value="<?php echo $d['id']; ?>">
                                    <input type="text" class="form-control" name="reply"
                                           placeholder="Add a reply…" style="font-size:0.88rem;">
                                    <button type="submit" class="btn-n-outline btn" style="white-space:nowrap;">Reply</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color:var(--text-muted); font-style:italic;">No discussions yet. Begin one below.</p>
                <?php endif; ?>
            </div>

            <div class="n-card p-4">
                <h6 style="font-family:'IM Fell English',serif; margin-bottom:0.8rem;">Start a Discussion</h6>
                <form method="post">
                    <input type="hidden" name="post_discussion" value="1">
                    <select class="form-select mb-2" name="discuss_book">
                        <?php foreach ($group_books as $b): ?>
                        <option value="<?php echo $b['id']; ?>"
                                <?php echo $b['id'] == ($group['current_book_id'] ?? 0) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($b['title']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <textarea class="form-control mb-2" name="post_content" rows="3"
                              placeholder="What's on your mind about this book?"></textarea>
                    <button type="submit" class="btn-n btn">Post</button>
                </form>
            </div>

            <?php elseif ($tab === 'schedule'): ?>

            <?php foreach ($group_schedule as $ev):
                $evb = getBookById((int)$ev['book_id']);
            ?>
            <div class="n-card p-4 mb-3">
                <div class="row align-items-center g-3">
                    <div class="col-auto" style="min-width:60px; text-align:center;">
                        <div style="font-family:'IM Fell English',serif; font-size:1.6rem; color:var(--blush); line-height:1;">
                            <?php echo date('d', strtotime($ev['date'])); ?>
                        </div>
                        <div style="font-size:0.72rem; text-transform:uppercase; letter-spacing:0.1em; color:var(--text-muted);">
                            <?php echo date('M', strtotime($ev['date'])); ?>
                        </div>
                    </div>
                    <div class="col">
                        <div style="font-family:'Cormorant Garamond',serif; font-size:1.1rem; margin-bottom:0.2rem;">
                            <?php echo htmlspecialchars($ev['title']); ?>
                        </div>
                        <div style="font-size:0.82rem; color:var(--text-muted); margin-bottom:0.3rem;">
                            <i class="bi bi-clock"></i> <?php echo $ev['time']; ?>
                            &nbsp;·&nbsp;
                            <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($ev['format']); ?>
                        </div>
                        <div style="font-size:0.9rem; font-style:italic; color:var(--text-muted);">
                            <?php echo htmlspecialchars($ev['notes']); ?>
                        </div>
                    </div>
                    <?php if ($evb): ?>
                    <div class="col-auto">
                        <img src="<?php echo htmlspecialchars($evb['cover']); ?>"
                             style="width:40px; height:60px; object-fit:cover; border:1px solid rgba(134,113,91,0.3); border-radius:1px;" alt="">
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($group_schedule)): ?>
            <p style="color:var(--text-muted); font-style:italic;">No gatherings scheduled yet.</p>
            <?php endif; ?>
            <a href="schedule.php?group_id=<?php echo $view_id; ?>" class="btn-n-outline btn mt-2">
                <i class="bi bi-calendar-plus"></i> Schedule a Gathering
            </a>

            <?php elseif ($tab === 'members'): ?>

            <div class="row g-3">
                <?php foreach ($group['members'] as $m): ?>
                <div class="col-md-6">
                    <div class="n-card p-3 d-flex align-items-center gap-3">
                        <div class="avatar-ring" style="width:46px;height:46px;font-size:1rem;">
                            <?php echo strtoupper(substr($m, 0, 1)); ?>
                        </div>
                        <div>
                            <div style="font-family:'Cormorant Garamond',serif; font-size:1rem;"><?php echo htmlspecialchars($m); ?></div>
                            <div style="font-size:0.78rem; color:var(--text-muted);">Member since <?php echo $group['created']; ?></div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php endif; ?>
        </div>

        <div class="col-lg-4">
            <?php if ($current_book): ?>
            <div class="n-card p-4 mb-3">
                <h6 style="letter-spacing:0.1em; font-size:0.75rem; text-transform:uppercase; color:var(--text-muted); margin-bottom:0.8rem;">Currently Reading</h6>
                <a href="books.php?id=<?php echo $current_book['id']; ?>" class="d-flex gap-3 text-decoration-none">
                    <img src="<?php echo htmlspecialchars($current_book['cover']); ?>"
                         style="width:60px; height:90px; object-fit:cover; border:1px solid rgba(134,113,91,0.3); border-radius:1px;" alt="">
                    <div>
                        <div style="font-style:italic; color:var(--blush); font-family:'Cormorant Garamond',serif; font-size:1.05rem;">
                            <?php echo htmlspecialchars($current_book['title']); ?>
                        </div>
                        <div style="font-size:0.82rem; color:var(--text-muted);"><?php echo htmlspecialchars($current_book['author']); ?></div>
                        <?php echo renderStars($current_book['rating']); ?>
                    </div>
                </a>
            </div>
            <?php endif; ?>

            <div class="n-card p-4 mb-3">
                <h6 style="letter-spacing:0.1em; font-size:0.75rem; text-transform:uppercase; color:var(--text-muted); margin-bottom:0.8rem;">Invite Code</h6>
                <div style="font-family:'IM Fell English',serif; font-size:1.6rem; letter-spacing:0.3em; color:var(--blush); text-align:center; padding:0.5rem; border:1px dashed rgba(134,113,91,0.4); border-radius:2px; margin-bottom:0.6rem;">
                    <?php echo htmlspecialchars($group['invite_code']); ?>
                </div>
                <button class="w-100 btn-n-outline btn"
                        onclick="navigator.clipboard.writeText('<?php echo $group['invite_code']; ?>'); this.textContent='Copied!'">
                    <i class="bi bi-clipboard"></i> Copy Code
                </button>
            </div>

            <div class="n-card p-4">
                <div class="d-flex justify-content-around">
                    <div class="text-center">
                        <div style="font-family:'IM Fell English',serif; font-size:1.6rem;"><?php echo $group['member_count']; ?></div>
                        <div style="font-size:0.72rem; text-transform:uppercase; letter-spacing:0.1em; color:var(--text-muted);">Members</div>
                    </div>
                    <div class="text-center">
                        <div style="font-family:'IM Fell English',serif; font-size:1.6rem;"><?php echo $gathering_count; ?></div>
                        <div style="font-size:0.72rem; text-transform:uppercase; letter-spacing:0.1em; color:var(--text-muted);">Gatherings</div>
                    </div>
                    <div class="text-center">
                        <div style="font-family:'IM Fell English',serif; font-size:1.6rem;"><?php echo $discussion_count; ?></div>
                        <div style="font-size:0.72rem; text-transform:uppercase; letter-spacing:0.1em; color:var(--text-muted);">Threads</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php endif; ?>

<?php else: ?>

<div class="d-flex justify-content-between align-items-end mb-4">
    <h2 class="page-heading mb-0">Reading Circles</h2>
    <button class="btn-n btn" data-bs-toggle="modal" data-bs-target="#createModal">
        <i class="bi bi-plus-lg"></i> New Circle
    </button>
</div>

<div class="n-card p-4 mb-4">
    <h6 style="font-family:'IM Fell English',serif; margin-bottom:0.8rem;">Join a Circle</h6>
    <form method="post" class="d-flex gap-2">
        <input type="hidden" name="join_code" value="1">
        <input type="text" class="form-control" name="invite_code"
               placeholder="Enter invite code (e.g. OBS-7X2K)"
               style="max-width:300px; letter-spacing:0.15em; text-transform:uppercase;">
        <button type="submit" class="btn-n btn">Join</button>
    </form>
</div>

<div class="row g-4">
    <?php foreach ($groups as $g):
        $cb        = getBookById((int)($g['current_book_id'] ?? 0));
        $is_member = in_array($_SESSION['username'], $g['members'] ?? []);
    ?>
    <div class="col-md-6 col-lg-4">
        <div class="n-card p-4 h-100" style="position:relative;">
            <?php if ($is_member): ?>
            <span class="n-badge" style="position:absolute; top:1rem; right:1rem;">Member</span>
            <?php endif; ?>
            <h5 style="font-family:'IM Fell English',serif; margin-bottom:0.3rem;">
                <?php echo htmlspecialchars($g['club_name'] ?? $g['name'] ?? ''); ?>
            </h5>
            <p style="font-size:0.9rem; color:var(--text-muted); font-style:italic; margin-bottom:1rem;">
                <?php echo htmlspecialchars($g['group_desc'] ?? $g['description'] ?? ''); ?>
            </p>
            <?php if ($cb): ?>
            <div class="d-flex gap-3 align-items-center mb-3">
                <img src="<?php echo htmlspecialchars($cb['cover']); ?>"
                     style="width:44px;height:66px;object-fit:cover;border:1px solid rgba(134,113,91,0.3);border-radius:1px;" alt="">
                <div>
                    <div style="font-size:0.75rem; text-transform:uppercase; letter-spacing:0.08em; color:var(--text-muted);">Reading Now</div>
                    <div style="font-style:italic; font-size:0.95rem;"><?php echo htmlspecialchars($cb['title']); ?></div>
                    <div style="font-size:0.8rem; color:var(--text-muted);"><?php echo htmlspecialchars($cb['author']); ?></div>
                </div>
            </div>
            <?php endif; ?>
            <div class="d-flex align-items-center justify-content-between">
                <div style="font-size:0.82rem; color:var(--text-muted);">
                    <i class="bi bi-people"></i> <?php echo $g['member_count'] ?? 0; ?> members
                </div>
                <a href="groups.php?id=<?php echo $g['club_id'] ?? $g['id']; ?>" class="btn-n-outline btn btn-sm">Enter Circle</a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php if (empty($groups)): ?>
    <div class="col-12 text-center" style="color:var(--text-muted); padding:3rem; font-style:italic;">
        No circles found. Create one or join with an invite code.
    </div>
    <?php endif; ?>
</div>

<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="background:var(--card); border:1px solid rgba(134,113,91,0.3); border-radius:4px;">
            <div class="modal-header" style="border-bottom:1px solid rgba(134,113,91,0.2);">
                <h5 class="modal-title" style="font-family:'IM Fell English',serif; color:var(--blush);">Found a New Circle</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="post">
                    <input type="hidden" name="create_group" value="1">
                    <div class="mb-3">
                        <label class="form-label">Circle Name</label>
                        <input type="text" class="form-control" name="group_name" placeholder="e.g. The Somnambulist Society" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="group_desc" rows="3" placeholder="What does your circle read?"></textarea>
                    </div>
                    <button type="submit" class="btn-n btn w-100">Create Circle</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>