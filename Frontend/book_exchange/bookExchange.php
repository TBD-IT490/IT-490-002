<?php
session_start();
require_once '../includes/header.php';
require_once '../includes/db.php';

/*available books for trade*/
$stmt = $pdo->query("SELECT ub.user_book_id, b.title, b.author, ub.condition, u.username FROM user_books ub
    JOIN books b ON ub.book_id = b.book_id
    JOIN users u ON ub.user_id = u.user_id
    WHERE ub.available_for_trade = 1");

$availableBooks = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*owned books for trade (my own books)*/
$stmt = $pdo->prepage("SELECT ub.user_book_id, b.title, b.author, b.cover_url FROM user_books ub JOIN books b ON ub.book_id = b.book_id WHERE ub.user_id = ?");

$stmt->execute([$_SESSION['user_id']]);

$myBooks = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*trade requests for the user*/

$stmt = $pdo-> prepare("SELECT tr.trade_request_id, tr.status, tr.message, b1.title AS offered_title, b2.title AS requested_title 
FROM trade_requests tr JOIN user books ub1 ON tr.offered_user_book_id = ub1.user_book_id JOIN books b1 ON ub1.book_id = b1.book_id
JOIN user_books ub2 ON tr.requested_user_book_id = ub2.user_book_id JOIN books b2 ON ub2.book_id = b2.book_id
WHERE tr.owner_id = ? AND tr.status = 'Pending'");

$stmt->execute([$_SESSION['user_id']]);

$tradeRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
    <style></style>
    <head>
    <link rel="stylesheet" href="styles.css">
    </head> 
    <body>
       <h1 class="my-4">Trade and Buy Books with Noetic</h1>
    <div class="container">
        <!-- ALL BOOKS UP FOR TRADE -->
    <h2>Browse Books</h2>
    <div class="ex-section">
        <div class="book-ex-grid">
            <?php foreach ($availableBooks as $book): ?>
                <div class="book-ex-card">
                    <div class="book-ex-content">
                        <div class="condition">
                            <?= $book['condition']; ?>
                        </div>

                        <img src="<?= htmlspecialchars($book['cover_url']); ?>" alt="Book Cover" class="book-cover">

                        <h3><?= htmlspecialchars($book['title']); ?></h3>
                        <p><?= htmlspecialchars($book['author']); ?></p>

                
                        <div class="owner">
                            Owner: <? htmlspecialchars($book['username']); ?><br>
                            <?= $book['ownerLocation']; ?>
                        </div>

                        <form action="request_trade.php" method="POST">
                            <input type="hidden" name="requested_user_book_id" value="<?= $book['user_book_id']; ?>">
                        <button class="ex-btn">
                            Request Trade
                        </button>
            </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div> 
            </div>
                <br>
    <a href="add_book.php">
        <button class="ex-btn">
            Add Book for Trade
        </button>
    </a>
        
    <!--MY OWNED BOOKS or my books up for trade i forgot-->
    <h2>My Books</h2>
<div class="ex-section">
        <div class="book-ex-grid">
            <?php foreach ($myBooks as $book): ?>
                <div class="book-ex-card">
                    <div class="book-ex-content">
                        <div class="condition">
                            <?= $book['condition']; ?>
                        </div>
                    <h3><?= htmlspecialchars($book['title']); ?></h3>
                        <p><?= htmlspecialchars($book['author']); ?></p>

                        <form action="remove_listing.php" method="POST">
                            <input type="hidden" name="user_book_id" value="<?= $book['user_book_id']; ?>">
                        <button class="ex-btn"> Remove Listing</button>
            </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
</div>
            <!--TRADE REQUESTSSSSSSSSSSS-->
            <div class="ex-section">
                <h2>Trade Requests</h2>

                <?php foreach ($tradeRequests as $request): ?>
                    <div class="trade-box">
                        <h3>Status: <?= $request['status']; ?></h3>

                        <p><strong>Offered Book:</strong>
                        <?= htmlspecialchars($request['offered_title']); ?></p>

                        <p><strong>Requested Book:</strong>
                        <?= htmlspecialchars($request['requested_title']); ?></p>

                        <p><strong>Message:</strong><br>
                        <?= $request['message']; ?></p>

                        <form action="accept_trade.php" method="POST">
                            <input type="hidden" name="trade_id" value="<?= $request['trade_request_id']; ?>">

                        <button class="ex-btn">Accept</button>
                </form>

                <form action="decline_trade.php" method="POST">
                            <input type="hidden" name="trade_id" value="<?= $request['trade_request_id']; ?>">
                        <button class="ex-btn" style="background:#999;">Decline</button>
                </form>
                    </div>
                    <?php endforeach; ?>
            </div>
            </body>
</html>


<?php require_once '../includes/footer.php'; ?>