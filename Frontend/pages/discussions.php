<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.php");
    exit();
}

//data functions and header file
require_once '../includes/data.php';
require_once '../includes/header.php';

$msg = '';

//send nat info hehe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['disc_create'])) {
    //handleDiscussions -> discussion.create
    //update discussion.create → disc_name, club_id, content
    $result = rmq_rpc('discussion.create', [
        'discussion_name'    => trim($_POST['discussion_name']),
        'discussion_message' => trim($_POST['discussion_message']),
        'username'           => $_SESSION['username'],
        'group_id'           => (int)$_POST['group_id'],
    ]);

    if ($result['success'] ?? false) {
        $msg = 'success:Discussion created!';
    } else {
        $msg = 'error:Could not create discussion. Please try again.';
    }
}

list($msg_type, $msg_text) = $msg ? explode(':', $msg, 2) : ['', ''];

$groups_res      = rmq_rpc('group.list', ['username' => $_SESSION['username']]);
$groups_for_select = $groups_res['groups'] ?? [];

$all_disc_res = rmq_rpc('discussion.list', [
    //handleDiscussionList -> discussion.list
    //discussion.list - gets all discussions for group_id asking for id, author, content, created, replies
    'username' => $_SESSION['username'],
]);
$discussions = $all_disc_res['discussions'] ?? [];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Noetic — Discussions</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=IM+Fell+English:ital@0;1&family=Crimson+Text:ital,wght@0,400;0,600;1,400&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    
<?php if ($msg_text): ?>
    <div class="alert alert-<?php echo $msg_type === 'success' ? 'success' : 'danger'; ?>">
        <?php echo htmlspecialchars($msg_text); ?>
    </div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-end mb-4">
    <h2 class="page-heading mb-0">Reading Discussions</h2>
    <button class="btn-n btn" data-bs-toggle="modal" data-bs-target="#createModal">
        <i class="bi bi-plus-lg"></i> New Discussion
    </button>
</div>

<div class="row g-4">
    <?php if (empty($discussions)): ?>
        <div class="col-12 text-center" style="color:var(--text-muted); padding:3rem; font-style:italic;">
            No discussions found. Create one!
        </div>
    <?php else: ?>
        <?php foreach ($discussions as $d): ?>
        <div class="col-md-6 col-lg-4">
            <div class="n-card p-4 h-100" style="position:relative;">
                <h5 style="font-family:'IM Fell English',serif; margin-bottom:0.3rem;">
                    <?php echo htmlspecialchars($d['discussion_name'] ?? ''); ?>
                </h5>
                <p style="font-size:0.9rem; color:var(--text-muted); font-style:italic; margin-bottom:1rem;">
                    <?php echo htmlspecialchars($d['discussion_message'] ?? ''); ?>
                </p>
                <div class="d-flex align-items-center justify-content-between">
                    <div style="font-size:0.82rem; color:var(--text-muted);">
                        <i class="bi bi-chat-left-text"></i>
                        <?php echo (int)($d['reply_count'] ?? 0); ?> replies
                    </div>
                    <a href="discussionBody.php?id=<?php echo (int)$d['discussion_id']; ?>" class="btn-n-outline btn btn-sm">
                        Enter Discussion
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="background:var(--card); border:1px solid rgba(134,113,91,0.3); border-radius:4px;">
            <div class="modal-header" style="border-bottom:1px solid rgba(134,113,91,0.2);">
                <h5 class="modal-title" style="font-family:'IM Fell English',serif; color:var(--blush);">
                    Found a New Thread
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="disc_create" value="1">
                    <div class="mb-3">
                        <label class="form-label">Group</label>
                        <select class="form-select" name="group_id" required>
                            <?php foreach ($groups_for_select as $g): ?>
                                <option value="<?php echo (int)$g['id']; ?>">
                                    <?php echo htmlspecialchars($g['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Discussion Name</label>
                        <input type="text" class="form-control" name="discussion_name"
                               placeholder="e.g. Who is the better Jojo?" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Message</label>
                        <textarea class="form-control" name="discussion_message" rows="3"
                                  placeholder="Post message..."></textarea>
                    </div>
                    <button type="submit" class="btn-n btn w-100">Create Discussion</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<?php require_once '../includes/footer.php'; ?>
</body>
</html>