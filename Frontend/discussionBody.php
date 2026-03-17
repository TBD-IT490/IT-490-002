<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.php");
    exit();
}

//data functions and header file
require_once 'includes/data.php';
require_once 'includes/header.php';

//$view_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$discussion_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
if (!$discussion_id) {
    header("Location: discussions.php");
    exit();
}

$msg = '';

//handleDiscussionReply -> discussion.reply
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['disc_reply'])) {
    //crashing out over this - it better work >:(
    $disc_lookup = rmq_rpc('discussion.list', [
        'discussion_id' => $discussion_id,
        'username'      => $_SESSION['username'],
    ]);
    $group_id = $disc_lookup['discussions'][0]['group_id'];

    $result = rmq_rpc('discussion.reply', [
        'discussion_id'      => $discussion_id,
        'group_id'           => $group_id,
        'discussion_message' => trim($_POST['discussion_message']),
        'username'           => $_SESSION['username'],
    ]);

    if ($result['success'] ?? false) {
        $msg = 'success:Reply posted!';
    } else {
        $msg = 'error:Could not post reply.';
    }
}

//handleDiscussionGet -> discussion.get
$disc_res   = rmq_rpc('discussion.list', [
    'discussion_id' => $discussion_id,
    'username'      => $_SESSION['username'],
]);

$discussion = $disc_res['discussions'][0] ?? null;
//$replies    = $discussion['replies'] ?? [];
$replies = rmq_rpc("replies.list",['discussion_id' => $discussion_id,'username'=> $_SESSION['username']]);

list($msg_type, $msg_text) = $msg ? explode(':', $msg, 2) : ['', ''];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Noetic — Discussion</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=IM+Fell+English:ital@0;1&family=Crimson+Text:ital,wght@0,400;0,600;1,400&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container mt-4">

    <div style="font-size:0.8rem; color:var(--text-muted); margin-bottom:1.5rem;">
        <a href="discussions.php" style="color:var(--umber); text-decoration:none;">Discussions</a>
        &nbsp;›&nbsp;
        <?php if ($discussion): ?>
            <?php echo htmlspecialchars($discussion['discussion_name']); ?>
        <?php endif; ?>
    </div>

    <?php if ($msg_text): ?>
        <div class="alert alert-<?php echo $msg_type === 'success' ? 'success' : 'danger'; ?>">
            <?php echo htmlspecialchars($msg_text); ?>
        </div>
    <?php endif; ?>

    <?php if ($discussion): ?>

        <div class="n-card p-4 mb-4">
            <h3 style="font-family:'IM Fell English',serif; color:var(--blush);">
                <?php echo htmlspecialchars($discussion['discussion_name']); ?>
            </h3>
            <p style="margin-bottom:0.5rem;">
                <?php echo htmlspecialchars($discussion['discussion_message']); ?>
            </p>
            <small style="color:var(--text-muted);">
                Posted by <?php echo htmlspecialchars($discussion['username']); ?>
            </small>
        </div>

        <h5 style="font-family:'IM Fell English',serif; margin-bottom:1rem;">
            Replies
            <span style="font-size:0.8rem; font-family:inherit; color:var(--text-muted);">
                (<?php echo count($replies["replies"]); ?>)
            </span>
        </h5>

        <?php if (empty($replies)): ?>
            <p style="color:var(--text-muted); font-style:italic;">No replies yet. Be the first!</p>
        <?php else: ?>
            <?php foreach ($replies["replies"] as $reply): ?>
                <div class="border rounded p-3 mb-2" style="border-color:rgba(134,113,91,0.25) !important;">
                    <strong style="font-size:0.9rem;">
                        <?php echo htmlspecialchars($reply['user_id']); ?>
                    </strong>
                    <p class="mb-0 mt-1">
                        <?php echo htmlspecialchars($reply['message']); ?>
                    </p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <hr style="border-color:rgba(134,113,91,0.2); margin:2rem 0;">
        <form method="POST">
            <div class="mb-3">
                <textarea name="discussion_message" class="form-control" rows="3"
                          placeholder="Write a reply..." required></textarea>
            </div>
            <button type="submit" name="disc_reply" class="btn-n btn">
                Post Reply
            </button>
        </form>

    <?php else: ?>
        <div class="text-center" style="padding:3rem; color:var(--text-muted); font-style:italic;">
            Discussion not found. <a href="discussions.php" style="color:var(--umber);">Go back</a>.
        </div>
    <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<?php require_once 'includes/footer.php'; ?>
</body>
</html>