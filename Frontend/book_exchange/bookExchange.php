<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../includes/header.php';
require_once '../includes/db.php';

/*available books for trade*/
$stmt = $pdo->query("SELECT ub.user_book_id, ub.`condition`, b.title, b.author, b.cover_url, u.username FROM user_books ub
    JOIN books b ON ub.book_id = b.book_id
    JOIN users u ON ub.user_id = u.id
    WHERE ub.available_for_trade = 1");

$availableBooks = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*owned books for trade (my own books)*/
$stmt = $pdo->prepare("SELECT ub.user_book_id, ub.`condition`, b.title, b.author, b.cover_url FROM user_books ub
    JOIN books b ON ub.book_id = b.book_id
    WHERE ub.user_id = ?");

$stmt->execute([$_SESSION['user_id']]);

$myBooks = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*trade requests for the user*/

$stmt = $pdo->prepare("SELECT tr.trade_request_id, tr.status, tr.message, b1.title AS offered_title, b2.title AS requested_title 
FROM trade_requests tr
JOIN user_books ub1 ON tr.offered_user_book_id = ub1.user_book_id
JOIN books b1 ON ub1.book_id = b1.book_id
JOIN user_books ub2 ON tr.requested_user_book_id = ub2.user_book_id
JOIN books b2 ON ub2.book_id = b2.book_id
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
                                <?= htmlspecialchars($book['condition']); ?>
                            </div>

                            <?php if (!empty($book['cover_url'])): ?>
                                <img src="<?= htmlspecialchars($book['cover_url']); ?>" alt="Book Cover" class="book-cover">
                            <?php endif; ?>

                            <h3><?= htmlspecialchars($book['title']); ?></h3>

                            <p><?= htmlspecialchars($book['author']); ?></p>

                            <div class="owner">
                                Owner: <?= htmlspecialchars($book['username']); ?>
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
        
    <!--MY OWNED BOOKS-->
    <h2>My Books</h2>

    <div class="ex-section">
        <div class="book-ex-grid">

            <?php foreach ($myBooks as $book): ?>

                <div class="book-ex-card">
                    <div class="book-ex-content">

                        <div class="condition">
                            <?= htmlspecialchars($book['condition']); ?>
                        </div>

                        <?php if (!empty($book['cover_url'])): ?>
                            <img src="<?= htmlspecialchars($book['cover_url']); ?>" alt="Book Cover" class="book-cover">
                        <?php endif; ?>

                        <h3><?= htmlspecialchars($book['title']); ?></h3>

                        <p><?= htmlspecialchars($book['author']); ?></p>

                        <form action="remove_listing.php" method="POST">
                            <input type="hidden" name="user_book_id" value="<?= $book['user_book_id']; ?>">

                            <button class="ex-btn">
                                Remove Listing
                            </button>
                        </form>

                    </div>
                </div>

            <?php endforeach; ?>

        </div>
    </div>

    <!--TRADE REQUESTS-->
    <div class="ex-section">
        <h2>Trade Requests</h2>

        <?php if (count($tradeRequests) > 0): ?>

            <?php foreach ($tradeRequests as $request): ?>

                <div class="trade-box">

                    <h3>Status: <?= htmlspecialchars($request['status']); ?></h3>

                    <p>
                        <strong>Offered Book:</strong>
                        <?= htmlspecialchars($request['offered_title']); ?>
                    </p>

                    <p>
                        <strong>Requested Book:</strong>
                        <?= htmlspecialchars($request['requested_title']); ?>
                    </p>

                    <p>
                        <strong>Message:</strong><br>
                        <?= htmlspecialchars($request['message']); ?>
                    </p>

                    <form action="accept_trade.php" method="POST">
                        <input type="hidden" name="trade_id" value="<?= $request['trade_request_id']; ?>">

                        <button class="ex-btn">
                            Accept
                        </button>
                    </form>

                    <form action="decline_trade.php" method="POST">
                        <input type="hidden" name="trade_id" value="<?= $request['trade_request_id']; ?>">

                        <button class="ex-btn" style="background:#999;">
                            Decline
                        </button>
                    </form>

                </div>

            <?php endforeach; ?>

        <?php else: ?>

            <p>No pending trade requests.</p>

        <?php endif; ?>

    </div>

    </body>
</html>

<?php require_once '../includes/footer.php'; ?>