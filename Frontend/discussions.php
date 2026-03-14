<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.php");
    exit();
}

//data functions and header file
require_once 'includes/data.php';
require_once 'includes/header.php';

//variables go here for nat
$view_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
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
            $disc_name = htmlspecialchars($result['disc_name']);
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
            'disc_name' => trim($_POST['disc_name']),
            //add book id ?? - if so how to get book?
            //'disc_book' => trim($_POST['club_book']),
            'disc_message' => trim($_POST['disc_message']),
            'username' => $_SESSION['username'],
        ]);
        if ($result['success'] ?? false) {
            $disc_name = htmlspecialchars($result['disc_name']);
            //add replies too?
            //do i need to add anything else?
            $msg = 'success:Discussion created!';
        } else {
            $msg = 'error:Could not create discussion. Please try again.';
        }
    }
}

list($msg_type, $msg_text) = $msg ? explode(':', $msg, 2) : ['', ''];

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
    /* erm test without if else see if i need to add the new key or not
    $disc_res = rmq_rpc('discussion.get', [  //nat add new key function on db listener 
        'discussion_id' => $view_id,
        'username' => $_SESSION['username'],
    ]);
    $discussion = $disc_res['discussion'] ?? null;
} else {
    $all_disc_res = rmq_rpc('discussion.list', [
        'discussion_id' => $view_id,
        'discussion_message' => $discussion_message,
        'discussion_create' => $discussion_create,
        'username' => $_SESSION['username'] ?? '',
    ]);
    $discussions = $all_disc_res['discussions'] ?? [];
        //handleDiscussionList -> discussion.list
        //discussion.list - gets all discussions for group_id asking for id, author, content, created, replies
        
        //hopefully this works pray for me too it is also midnight :c  
        */ 
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Noetic — Threads</title>
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
 

 
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<?php require_once 'includes/footer.php'; ?>
</body>
</html>