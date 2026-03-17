<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.php");
    exit();
}

//data functions and header file
require_once 'includes/data.php';
require_once 'includes/header.php';

$view_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
//variables go here for nat
//$view_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$msg = '';

//send nat info hehe - should add && view_id ??
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //handleDiscussionReply -> discussion.reply
    //crashing out over this - it better work >:(
    if (isset($_POST['disc_reply'])) { 
        $result = rmq_rpc('discussion.reply', [
            'discussion_id' => $view_id, //change to discussion_id
            //idk if i need book id here as well ?
            'discussion_message' => trim($_POST['discussion_message']),
            'username' => $_SESSION['username'],
        ]);
        if ($result['success'] ?? false) {
            $disc_name = htmlspecialchars($result['discussion_name']);
            $msg = 'success:Your reply has been posted to the discussion! Yay!';
        } else {
            $msg = 'error:Could not post reply. Please try again.';
        }
    }

    //discussion.create → group_id, book_id, content
    //update discussion.create → disc_name, book_id, content 
    //handleDiscussions -> discussion.create
    if (isset($_POST['disc_create'])) {
        $result = rmq_rpc('discussion.create', [
            'discussion_name' => trim($_POST['discussion_name']),
            //add book id ?? - if so how to get book?
            //'disc_book' => trim($_POST['club_book']),
            'discussion_message' => trim($_POST['discussion_message']),
            'username' => $_SESSION['username'],
            'group_id' => $_POST['book_id']
        ]);
        if ($result['success'] ?? false) {
            $disc_name = htmlspecialchars($result['discussion_name']);
            //add replies too?
            //do i need to add anything else?
            $msg = 'success:Discussion created!';
        } else {
            $msg = 'error:Could not create discussion. Please try again.';
        }
    }
}

list($msg_type, $msg_text) = $msg ? explode(':', $msg, 2) : ['', ''];
$groups = rmq_rpc("group.list",['username' => $_SESSION['username']]);
$groups_for_select = $groups['groups'];

//also sending nat info but getting some back
if ($view_id) {
    //from book.php o_o
     $all_disc_res = rmq_rpc('discussion.list', [
        'discussion_id' => $view_id,
        'discussion_message' => $discussion_message,
        'discussion_create' => $discussion_create,
        'username' => $_SESSION['username'] ?? '',
    ]);
    $discussions = $all_disc_res['discussions'] ?? [];
    //erm test without if else see if i need to add the new key or not
    //$disc_res = rmq_rpc('discussion.get', [  //nat add new key function on db listener 
    //    'discussion_id' => $view_id,
    //    'username' => $_SESSION['username'],
    //]);
    //$discussion = $disc_res['discussion'] ?? null;
} else {
    $all_disc_res = rmq_rpc('discussion.list', [
        'discussion_id' => $view_id,
        'discussion_message' => $discussion_message,
        'discussion_create' => $discussion_create,
        'username' => $_SESSION['username'] ?? '',
    ]);
    $discussions = $all_disc_res['discussions'] ?? [];
    $msg = "j" . count($discussions);
        //handleDiscussionList -> discussion.list
        //discussion.list - gets all discussions for group_id asking for id, author, content, created, replies
        
        //hopefully this works pray for me too it is also midnight :c  
         
}

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
        <?php echo $msg_text; ?>
    </div>
<?php endif; ?>
 
<?php if ($view_id): ?>
     <?php if (!$discussions): ?>
        <p style="color:var(--text-muted); font-style:italic;">No discussions.</p>
    <?php else: ?>
    <div style="font-size:0.8rem; color:var(--text-muted); margin-bottom:1.5rem;">
        <a href="groups.php" style="color:var(--umber); text-decoration:none;">Discussions</a>
        &nbsp;›&nbsp; <?php echo htmlspecialchars($discussions['name']); ?>
    </div>
    <?php endif; ?>
<?php else: ?>
<div class="d-flex justify-content-between align-items-end mb-4">
    <h2 class="page-heading mb-0">Reading Discussions</h2>
    <button class="btn-n btn" data-bs-toggle="modal" data-bs-target="#createModal">
        <i class="bi bi-plus-lg"></i> New Discussions
    </button>
</div>
 

<div class="row g-4">
    <?php foreach ($discussions as $d):
        $cb        = getBookById((int)($g['current_book_id'] ?? 0));
        $is_member = in_array($_SESSION['username'], $g['members'] ?? []);
    ?>
    <div class="col-md-6 col-lg-4">
        <div class="n-card p-4 h-100" style="position:relative;">
            <?php if ($is_member): ?>
            <span class="n-badge" style="position:absolute; top:1rem; right:1rem;">Member</span>
            <?php endif; ?>
            <h5 style="font-family:'IM Fell English',serif; margin-bottom:0.3rem;">
                <?php echo htmlspecialchars($g['discussion_name']); ?>
            </h5>
            <p style="font-size:0.9rem; color:var(--text-muted); font-style:italic; margin-bottom:1rem;">
                <?php echo htmlspecialchars($g['discussion_message'] ?? $g['discussion_message']); ?>
            </p>
            <?php if ($cb): ?>
            <div class="d-flex gap-3 align-items-center mb-3">
                <img src="<?php echo htmlspecialchars($cb['cover']); ?>"
                     style="width:44px;height:66px;object-fit:cover;border:1px solid rgba(134,113,91,0.3);border-radius:1px;" alt="">
                <div>
                    <div style="font-size:0.75rem; text-transform:uppercase; letter-spacing:0.08em; color:var(--text-muted);"><?php echo htmlspecialchars($d['discussion_name']); ?></div>
                    <div style="font-style:italic; font-size:0.95rem;"><?php echo htmlspecialchars($d['discussion_name']); ?></div>
                </div>
            </div>
            <?php endif; ?>
            <div class="d-flex align-items-center justify-content-between">
                <div style="font-size:0.82rem; color:var(--text-muted);">
                    <i class="bi bi-people"></i> <?php echo $g['member_count'] ?? 0; ?> members
                </div>
                <a href="groups.php?id=<?php echo $g['group_id']; ?>" class="btn-n-outline btn btn-sm">Enter Discussion</a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php if (empty($discussions)): ?>
    <div class="col-12 text-center" style="color:var(--text-muted); padding:3rem; font-style:italic;">
        No discussions found. Create one or join one.
    </div>
    <?php endif; ?>
</div>



<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="background:var(--card); border:1px solid rgba(134,113,91,0.3); border-radius:4px;">
            <div class="modal-header" style="border-bottom:1px solid rgba(134,113,91,0.2);">
                <h5 class="modal-title" style="font-family:'IM Fell English',serif; color:var(--blush);">Found a New Thread</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="post">
                    <input type="hidden" name="disc_create" value="1">
                    <div class="mb-3">
                        <label class="form-label">Group</label>
                        <select class="form-select" name="book_id" required>
                            <?php foreach ($groups_for_select as $b): ?>
                            <option value="<?php echo $b['id']; ?>"><?php echo htmlspecialchars($b['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Discussion Name</label>
                        <input type="text" class="form-control" name="discussion_name" placeholder="e.g. Who is the better Jojo?" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Message</label>
                        <textarea class="form-control" name="discussion_message" rows="3" placeholder="Post message..."></textarea>
                    </div>
                    <button type="submit" class="btn-n btn w-100">Create Discussion</button>
                </form>
            </div>
        </div>
    </div>
</div>


<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<?php require_once 'includes/footer.php'; ?>
</body>
</html>