<?php

require_once '../includes/db.php';

$tradeId = $_POST['trade_id'];

$stmt = $pdo->prepare("
    UPDATE trade_requests
    SET status = 'Declined'
    WHERE trade_request_id = ?
");

$stmt->execute([$tradeId]);

header("Location: exchange.php");