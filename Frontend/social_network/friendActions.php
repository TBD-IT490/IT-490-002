<?php
session_start();

//REDIRECT TO LOGIN IF NOT LOGGED IN PROPERLY (so you can't access without signing in hehe)
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.php");
    exit();
}

//made this separate so i dont get confused while ctrl c/ving
require_once '../includes/data.php';

$action = $_GET['action'] ?? '';
$target = $_GET['id'] ?? '';

if(!$action || !$target) {
    header("Location: searchFriends.php");
    exit();
}

//need to create backend stuff for this to work
//and create table for friends
switch ($action) {
    case 'add':
        rmq_rpc('friends.add', ['user_id' => $_SESSION['id'], 'friend_id' => $target]);
        break;
    case 'remove':
        rmq_rpc('friends.remove', ['user_id' => $_SESSION['id'], 'friend_id' => $target]);
        break;
    case 'block':
        rmq_rpc('friends.block', ['user_id' => $_SESSION['id'], 'friend_id' => $target]);
        break;
    
}
hearder("Location: " . $_SERVER['HTTP_REFERER']);
exit();
?>
