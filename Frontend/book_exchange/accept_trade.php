<?php

require_once '../includes/db.php';

$tradeId = $_POST['trade_id'];

$stmt = $pdo->prepare("
    SELECT *
    FROM trade_requests
    WHERE trade_request_id = ?
");

$stmt->execute([$tradeId]);

$trade = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
    UPDATE user_books
    SET user_id = ?
    WHERE user_book_id = ?
");

$stmt->execute([
    $trade['requester_id'],
    $trade['requested_user_book_id']
]);

$stmt->execute([
    $trade['owner_id'],
    $trade['offered_user_book_id']
]);

$stmt = $pdo->prepare("
    UPDATE trade_requests
    SET status = 'Accepted'
    WHERE trade_request_id = ?
");

$stmt->execute([$tradeId]);

header("Location: bookExchange.php");