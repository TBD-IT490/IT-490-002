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

//send nat info hehe - idk if this should be && view_id bc theres also the logic to create
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //is creating a discussion going here instead? 
    //i think create is supposed to go here 
    
    //handleDiscussionReply -> discussion.reply
    //crashing out over this - it better work >:(
    if (isset($_POST['disc_reply'])) { // erm name of what triggers this idk ???
        $result = rmq_rpc('discussion.reply', [
            'group_id' => $view_id,
            //idk if i need book id here as well ?
            'discussion_message' => trim($_POST['discussion_message']),
            'username' => $_SESSION['username'],
        ]);
        if ($result['success'] ?? false) {
            $msg = 'success:Your reply has been posted to the discussion! Yay!';
        } else {
            $msg = 'error:Could not post reply. Please try again.';
        }
    }

    //discussion.create → group_id, book_id, content
    //handleDiscussions -> discussion.create
    if (isset($_POST['create_disc'])) { //or should this be new_thread bc it's the name of the button to trigger creating a discussion im assuming idk pls change ??
        $result = rmq_rpc('discussion.create', [
            'group_id' => $view_id,
            //add book id ?? - if so how to get book?
            'disc_message' => trim($_POST['disc_message']),
            'username' => $_SESSION['username'],
        ]);
        if ($result['success'] ?? false) {
            $msg = 'success:Discussion created!';
        } else {
            $msg = 'error:Could not create discussion. Please try again.';
        }
    }
}

//also sending nat info but getting some back
if ($view_id) {
    //should i put current book here ?? erm for now ig i will - nvm i did not
    //from book.php o_o
    // - nat realizing she dont need all this and complicate this more than she alr did
    //SHE FIGURED IT OUT - muehehehe >:)
    $group_res = rmq_rpc('group.get', [
        'group_id' => $view_id,
        'username' => $_SESSION['username'],
    ]);
    $group = $group_res['group'] ?? null;

    //does the group.get also need to run here?  
    //erm if i need to get group, smth like group and book match then queue discussion? no.
    //from books.php o_o
    if ($group) {
        //is there a disc_id instead, discussion would be js like a chat not necessarily for a specific book??
        //im loosing my marbles bro
        //im overcomplicating this for no reason smh  - FAAAH
        //handleDiscussionList -> discussion.list
        //discussion.list - gets all discussions for group_id asking for id, author, content, created, replies
        
        //hopefully this works pray for me too it is also midnight :c
       $disc_res = rmq_rpc('discussion.list',[
        'discussion_id' => $view_id,
        'discussion_message' => $discussion_message,
        'discussion_create' => $discussion_create,
        'username' => $_SESSION['username'] ?? '',
       ]);
        $discussions = $disc_res['discussions'] ?? [];
    }
}

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

<?php require_once 'includes/footer.php'; ?>
</html>