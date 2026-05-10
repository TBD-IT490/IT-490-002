<?php

session_start();
require_once '../includes/db.php';

$userId = $_SESSION['user_id'];

$title = trim($_POST['title']);
$author = trim($_POST['author']);
$isbn = trim($_POST['isbn']);
$condition = $_POST['condition'];


$stmt = $pdo->prepare("
    SELECT book_id
    FROM books
    WHERE isbn = ?
");

$stmt->execute([$isbn]);

$existingBook = $stmt->fetch(PDO::FETCH_ASSOC);


if ($existingBook) {

    $bookId = $existingBook['book_id'];

} else {

    $stmt = $pdo->prepare("
        INSERT INTO books
        (isbn, title, author)

        VALUES (?, ?, ?)
    ");

    $stmt->execute([
        $isbn,
        $title,
        $author
    ]);

    $bookId = $pdo->lastInsertId();
}


$stmt = $pdo->prepare("
    INSERT INTO user_books
    (user_id, book_id, `condition`)

    VALUES (?, ?, ?)
");

$stmt->execute([
    $userId,
    $bookId,
    $condition
]);

header("Location: bookExchange.php");
exit;