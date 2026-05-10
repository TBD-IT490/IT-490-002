<?php

session_start();
require_once '../includes/db.php';

$requesterId = $_SESSION['user_id'];

$requestedUserBookId =
    $_POST['requested_user_book_id'];

$offeredUserBookId = 1;

$stmt = $pdo->prepare("
    SELECT user_id
    FROM user_books
    WHERE user_book_id = ?
");

$stmt->execute([$requestedUserBookId]);

$ownerId = $stmt->fetchColumn();

$stmt = $pdo->prepare("
    INSERT INTO trade_requests
    (
        requester_id,
        owner_id,
        offered_user_book_id,
        requested_user_book_id
    )

    VALUES (?, ?, ?, ?)
");

$stmt->execute([
    $requesterId,
    $ownerId,
    $offeredUserBookId,
    $requestedUserBookId
]);

header("Location: bookExchange.php");
exit;