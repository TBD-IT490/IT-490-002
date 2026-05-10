<?php

session_start();

require_once '../includes/db.php';

$userBookId = $_POST['user_book_id'];

$stmt = $pdo->prepare("
    UPDATE user_books
    SET available_for_trade = 0
    WHERE user_book_id = ?
    AND user_id = ?
");

$stmt->execute([
    $userBookId,
    $_SESSION['user_id']
]);

header("Location: bookExchange.php");
exit;