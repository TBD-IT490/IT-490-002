<?php
require_once 'includes/data.php';
require_once 'includes/header.php';

$view_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$msg = '';

// Handle join by invite code
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['join_code'])) {
        $code = strtoupper(trim($_POST['invite_code'] ?? ''));
        $found = null;
        foreach ($groups as $g) {
            if ($g['invite_code'] === $code) { $found = $g; break; }
        }
        if ($found) {
            $msg = "success:You have joined <em>{$found['name']}</em>. Welcome to the circle.";
        } else {
            $msg = "error:Invalid invite code. Please check and try again.";
        }
    }

    if (isset($_POST['create_group'])) {
        $gname = htmlspecialchars(trim($_POST['group_name'] ?? ''));
        $gdesc = htmlspecialchars(trim($_POST['group_desc'] ?? ''));
        if ($gname) {
            $new_code = strtoupper(substr(str_replace(['+','/','='],'',base64_encode(random_bytes(6))), 0, 8));
            $msg = "success:Circle <em>$gname</em> created! Your invite code is: <strong>$new_code</strong>";
        }
    }

    if (isset($_POST['post_discussion'])) {
        $msg = "success:Your discussion post has been added.";
    }
}

list($msg_type, $msg_text) = $msg ? explode(':', $msg, 2) : ['', ''];
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
.copy-btn { cursor:pointer; color:var(--umber); background:none; border:none; padding:0; font-size:0.85rem; }
.copy-btn:hover { color:var(--blush); }
</style>

<?php if ($msg_text): ?>
<div class="n-alert mb-4 <?php echo $msg_type==='error'?'border-danger':''; ?>">
    <?php echo $msg_text; ?>
</div>
<?php endif; ?>

<?php if ($view_id):
    $group = getGroupById($view_id);
    if (!$group): ?>
    <p>Circle not found.</p>
<?php else:
    $current_book = getBookById($group['current_book_id']);
    $group_discussions = array_filter($discussions, fn($d) => $d['group_id'] == $view_id);
    $group_schedule = array_filter($schedule, fn($s) => $s['group_id'] == $view_id);
    $tab = $_GET['tab'] ?? 'discuss';
?>

<!-- Breadcrumb -->
<div style="font-size:0.8rem; color:var(--text-muted); margin-bottom:1.5rem;">
    <a href="groups.php" style="color:var(--umber); text-decoration:none;">Circles</a>
    &nbsp;›&nbsp; <?php echo htmlspecialchars($group['name']); ?>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <h2 class="page-heading"><?php echo htmlspecialchars($group['name']); ?></h2>
        <p style="color:var(--text-muted); font-style:italic; margin-bottom:1rem;"><?php echo htmlspecialchars($group['description']); ?></p>

        <!-- Members -->
        <div class="mb-3 d-flex flex-wrap gap-2">
            <?php foreach ($group['members'] as $m): ?>
            <span class="member-pill">
                <span style="width:20px;height:20px;border-radius:50%;background:var(--moss);display:flex;align-items:center;justify-content:center;font-size:0.65rem;">
                    <?php echo strtoupper(substr($m,0,1)); ?>
                </span>
                <?php echo htmlspecialchars($m); ?>
            </span>
            <?php endforeach; ?>
            <span class="member-pill" style="cursor:pointer; border-style:dashed;" onclick="document.getElementById('inviteModal').style.display='flex'">
                <i class="bi bi-plus"></i> Invite
            </span>
        </div>

        <!-- Tabs -->
        <div class="tab-nav">
            <a href="?id=<?php echo $view_id; ?>&tab=discuss" class="tab-link <?php echo $tab==='discuss'?'active':''; ?>">Discussion Board</a>
            <a href="?id=<?php echo $view_id; ?>&tab=schedule" class="tab-link <?php echo $tab==='schedule'?'active':''; ?>">Gatherings</a>
            <a href="?id=<?php echo $view_id; ?>&tab=members" class="tab-link <?php echo $tab==='members'?'active':''; ?>">Members</a>
        </div>

        <?php if ($tab==='discuss'): ?>

        <!-- Discussion board -->
        <div class="mb-4">
            <?php if (!empty($group_discussions)): ?>
                <?php foreach ($group_discussions as $d):
                    $dbook = getBookById($d['book_id']);
                ?>
                <div class="n-card p-4 mb-3">
                    <div class="d-flex gap-3">
                        <div class="avatar-ring"><?php echo strtoupper(substr($d['author'],0,1)); ?></div>
                        <div class="flex-grow-1">
                            <div style="font-size:0.8rem; color:var(--text-muted); margin-bottom:0.4rem;">
                                <strong style="color:var(--blush);"><?php echo $d['author']; ?></strong>
                                &nbsp;·&nbsp; <em><?php echo $dbook['title']; ?></em>
                                &nbsp;·&nbsp; <?php echo $d['created']; ?>
                            </div>
                            <p style="margin-bottom:0.8rem;"><?php echo htmlspecialchars($d['content']); ?></p>

                            <?php if (!empty($d['replies'])): ?>
                            <div style="border-left:2px solid rgba(134,113,91,0.25); padding-left:1rem; margin-top:0.5rem;">
                                <?php foreach ($d['replies'] as $r): ?>
                                <div class="d-flex gap-2 mb-2">
                                    <div class="avatar-ring" style="width:28px;height:28px;font-size:0.7rem;"><?php echo strtoupper(substr($r['author'],0,1)); ?></div>
                                    <div>
                                        <span style="font-size:0.82rem; color:var(--blush);"><?php echo $r['author']; ?></span>
                                        <span style="font-size:0.78rem; color:var(--text-muted);"> · <?php echo $r['created']; ?></span>
                                        <p style="margin:0.2rem 0 0; font-size:0.95rem; color:var(--text-muted);"><?php echo htmlspecialchars($r['content']); ?></p>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>

                            <!-- Reply box -->
                            <form method="post" class="mt-2 d-flex gap-2">
                                <input type="hidden" name="post_discussion" value="1">
                                <input type="text" class="form-control" name="reply" placeholder="Add a reply…" style="font-size:0.88rem;">
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

        <!-- New post -->
        <div class="n-card p-4">
            <h6 style="font-family:'IM Fell English',serif; margin-bottom:0.8rem;">Start a Discussion</h6>
            <form method="post">
                <input type="hidden" name="post_discussion" value="1">
                <div class="mb-2">
                    <select class="form-select mb-2" name="discuss_book">
                        <?php foreach ($books as $b): ?>
                        <option value="<?php echo $b['id']; ?>" <?php echo $b['id']==$group['current_book_id']?'selected':''; ?>>
                            <?php echo htmlspecialchars($b['title']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <textarea class="form-control mb-2" name="post_content" rows="3" placeholder="What's on your mind about this book?"></textarea>
                <button type="submit" class="btn-n btn">Post</button>
            </form>
        </div>

        <?php elseif ($tab==='schedule'): ?>

        <?php foreach ($group_schedule as $ev):
            $evb = getBookById($ev['book_id']);
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
                    <div style="font-family:'Cormorant Garamond',serif; font-size:1.1rem; margin-bottom:0.2rem;"><?php echo htmlspecialchars($ev['title']); ?></div>
                    <div style="font-size:0.82rem; color:var(--text-muted); margin-bottom:0.3rem;">
                        <i class="bi bi-clock"></i> <?php echo $ev['time']; ?>
                        &nbsp;·&nbsp; <i class="bi bi-geo-alt"></i> <?php echo $ev['format']; ?>
                    </div>
                    <div style="font-size:0.9rem; font-style:italic; color:var(--text-muted);"><?php echo htmlspecialchars($ev['notes']); ?></div>
                </div>
                <div class="col-auto">
                    <img src="<?php echo $evb['cover']; ?>" style="width:40px; height:60px; object-fit:cover; border:1px solid rgba(134,113,91,0.3); border-radius:1px;" alt="">
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <a href="schedule.php?group_id=<?php echo $view_id; ?>" class="btn-n-outline btn mt-2">
            <i class="bi bi-calendar-plus"></i> Schedule a Gathering
        </a>

        <?php elseif ($tab==='members'): ?>

        <div class="row g-3">
            <?php foreach ($group['members'] as $m): ?>
            <div class="col-md-6">
                <div class="n-card p-3 d-flex align-items-center gap-3">
                    <div class="avatar-ring" style="width:46px;height:46px;font-size:1rem;"><?php echo strtoupper(substr($m,0,1)); ?></div>
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

    <!-- Right sidebar -->
    <div class="col-lg-4">
        <div class="n-card p-4 mb-3">
            <h6 style="letter-spacing:0.1em; font-size:0.75rem; text-transform:uppercase; color:var(--text-muted); margin-bottom:0.8rem;">Currently Reading</h6>
            <a href="books.php?id=<?php echo $current_book['id']; ?>" class="d-flex gap-3 text-decoration-none">
                <img src="<?php echo $current_book['cover']; ?>" style="width:60px; height:90px; object-fit:cover; border:1px solid rgba(134,113,91,0.3); border-radius:1px;" alt="">
                <div>
                    <div style="font-style:italic; color:var(--blush); font-family:'Cormorant Garamond',serif; font-size:1.05rem;"><?php echo htmlspecialchars($current_book['title']); ?></div>
                    <div style="font-size:0.82rem; color:var(--text-muted);"><?php echo $current_book['author']; ?></div>
                    <?php echo renderStars($current_book['rating']); ?>
                </div>
            </a>
        </div>

        <!-- Invite -->
        <div class="n-card p-4 mb-3">
            <h6 style="letter-spacing:0.1em; font-size:0.75rem; text-transform:uppercase; color:var(--text-muted); margin-bottom:0.8rem;">Invite Code</h6>
            <div style="font-family:'IM Fell English',serif; font-size:1.6rem; letter-spacing:0.3em; color:var(--blush); text-align:center; padding:0.5rem; border:1px dashed rgba(134,113,91,0.4); border-radius:2px; margin-bottom:0.6rem;">
                <?php echo $group['invite_code']; ?>
            </div>
            <button class="copy-btn w-100 btn-n-outline btn"
                onclick="navigator.clipboard.writeText('<?php echo $group['invite_code']; ?>'); this.textContent='Copied!'">
                <i class="bi bi-clipboard"></i> Copy Code
            </button>
            <div style="font-size:0.78rem; color:var(--text-muted); margin-top:0.5rem; text-align:center;">
                Share this code with friends to invite them.
            </div>
        </div>

        <!-- Stats -->
        <div class="n-card p-4">
            <div class="d-flex justify-content-around">
                <div class="text-center">
                    <div style="font-family:'IM Fell English',serif; font-size:1.6rem;"><?php echo $group['member_count']; ?></div>
                    <div style="font-size:0.72rem; text-transform:uppercase; letter-spacing:0.1em; color:var(--text-muted);">Members</div>
                </div>
                <div class="text-center">
                    <div style="font-family:'IM Fell English',serif; font-size:1.6rem;"><?php echo count(array_filter($schedule, fn($s) => $s['group_id']==$view_id)); ?></div>
                    <div style="font-size:0.72rem; text-transform:uppercase; letter-spacing:0.1em; color:var(--text-muted);">Gatherings</div>
                </div>
                <div class="text-center">
                    <div style="font-family:'IM Fell English',serif; font-size:1.6rem;"><?php echo count($group_discussions); ?></div>
                    <div style="font-size:0.72rem; text-transform:uppercase; letter-spacing:0.1em; color:var(--text-muted);">Threads</div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>

<?php else: ?>

<!-- All groups list -->
<div class="d-flex justify-content-between align-items-end mb-4">
    <h2 class="page-heading mb-0">Reading Circles</h2>
    <button class="btn-n btn" data-bs-toggle="modal" data-bs-target="#createModal">
        <i class="bi bi-plus-lg"></i> New Circle
    </button>
</div>

<!-- Join by code -->
<div class="n-card p-4 mb-4">
    <h6 style="font-family:'IM Fell English',serif; margin-bottom:0.8rem;">Join a Circle</h6>
    <form method="post" class="d-flex gap-2">
        <input type="hidden" name="join_code" value="1">
        <input type="text" class="form-control" name="invite_code" placeholder="Enter invite code (e.g. OBS-7X2K)" style="max-width:300px; letter-spacing:0.15em; text-transform:uppercase;">
        <button type="submit" class="btn-n btn">Join</button>
    </form>
</div>

<div class="row g-4">
    <?php foreach ($groups as $g):
        $cb = getBookById($g['current_book_id']);
        $is_member = in_array($_SESSION['username'], $g['members']);
    ?>
    <div class="col-md-6 col-lg-4">
        <div class="n-card p-4 h-100" style="position:relative;">
            <?php if ($is_member): ?>
            <span class="n-badge" style="position:absolute; top:1rem; right:1rem;">Member</span>
            <?php endif; ?>

            <h5 style="font-family:'IM Fell English',serif; margin-bottom:0.3rem;"><?php echo htmlspecialchars($g['name']); ?></h5>
            <p style="font-size:0.9rem; color:var(--text-muted); font-style:italic; margin-bottom:1rem;"><?php echo htmlspecialchars($g['description']); ?></p>

            <div class="d-flex gap-3 align-items-center mb-3">
                <img src="<?php echo $cb['cover']; ?>" style="width:44px;height:66px;object-fit:cover;border:1px solid rgba(134,113,91,0.3);border-radius:1px;" alt="">
                <div>
                    <div style="font-size:0.75rem; text-transform:uppercase; letter-spacing:0.08em; color:var(--text-muted);">Reading Now</div>
                    <div style="font-style:italic; font-size:0.95rem;"><?php echo htmlspecialchars($cb['title']); ?></div>
                    <div style="font-size:0.8rem; color:var(--text-muted);"><?php echo $cb['author']; ?></div>
                </div>
            </div>

            <div class="d-flex align-items-center justify-content-between">
                <div style="font-size:0.82rem; color:var(--text-muted);">
                    <i class="bi bi-people"></i> <?php echo $g['member_count']; ?> members
                </div>
                <a href="groups.php?id=<?php echo $g['id']; ?>" class="btn-n-outline btn btn-sm">Enter Circle</a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Create group modal -->
<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="background:var(--card); border:1px solid rgba(134,113,91,0.3); border-radius:4px;">
            <div class="modal-header" style="border-bottom:1px solid rgba(134,113,91,0.2);">
                <h5 class="modal-title" style="font-family:'IM Fell English',serif; color:var(--blush);">Found a New Circle</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="post" id="createForm">
                    <input type="hidden" name="create_group" value="1">
                    <div class="mb-3">
                        <label class="form-label">Circle Name</label>
                        <input type="text" class="form-control" name="group_name" placeholder="e.g. The Somnambulist Society" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="group_desc" rows="3" placeholder="What does your circle read?"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">First Book (optional)</label>
                        <select class="form-select" name="first_book">
                            <option value="">Choose a book…</option>
                            <?php foreach ($books as $b): ?>
                            <option value="<?php echo $b['id']; ?>"><?php echo htmlspecialchars($b['title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn-n btn w-100">Create Circle</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>