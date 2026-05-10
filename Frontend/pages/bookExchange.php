<?php
session_start();
require_once '../includes/header.php';
require_once '../includes/data.php';

//DUMMY DATA TO TEST AND MAKE SURE EVERYTHING WORKS
$availableBooks = [
    [
        "id" => "t1",
        "title" => "Being and Time",
        "author" => "Martin Heidegger",
        "coverImage" => "https://images.unsplash.com/photo-1543002588-bfa74002ed7e?w=400",
        "condition" => "Good",
        "ownerName" => "Elena Chen",
        "ownerLocation" => "Cambridge, MA",
    ],
    [
        "id" => "t2",
        "title" => "The Second Sex",
        "author" => "Simone de Beauvoir",
        "coverImage" => "https://images.unsplash.com/photo-1512820790803-83ca734da794?w=400",
        "condition" => "Like New",
        "ownerName" => "Marcus Torres",
        "ownerLocation" => "Boston, MA",
    ],
    [
        "id" => "t3",
        "title" => "The Stranger",
        "author" => "Albert Camus",
        "coverImage" => "https://images.unsplash.com/photo-1544947950-fa07a98d237f?w=400",
        "condition" => "Fair",
        "ownerName" => "Sophia Laurent",
        "ownerLocation" => "New Haven, CT",
    ],
];

$myBooks = [
    [
        "title" => "Meditations",
        "author" => "Marcus Aurelius",
        "coverImage" => "https://images.unsplash.com/photo-1481627834876-b7833e8f5570?w=400",
        "condition" => "Good",
    ],
    [
        "title" => "The Republic",
        "author" => "Plato",
        "coverImage" => "https://images.unsplash.com/photo-1524995997946-a1c2e315a42f?w=400",
        "condition" => "Like New",
    ],
];

$tradeRequests = [
    [
        "offeredBook" => "Meditations",
        "requestedBook" => "Being and Time",
        "status" => "Pending",
        "message" => "Would love to trade! Been looking for this book.",
    ],
];
?>

<!DOCTYPE html>
<html lang="en">
    <style></style>
    <head>
    <link rel="stylesheet" href="styles.css">
    <h1 class="my-4">Trade and Buy Books with Noetic</h1>
    </head> 
    <body>
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

                        <h3><?= $book['title']; ?></h3>
                        <p><?= $book['author']; ?></p>

                        <div class="owner">
                            Owner: <? $book['ownerName']; ?><br>
                            <?= $book['ownerLocation']; ?>
                        </div>

                        <button class="ex-btn">
                            Request Trade
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div> 
            </div>
                <br>
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
                    
                        <h3><?= $book['title']; ?></h3>
                        <p><?= $book['author']; ?></p>

                        <div class="owner">
                            Owner: <? $book['ownerName']; ?><br>
                            <?= $book['ownerLocation']; ?>
                        </div>

                        <button class="ex-btn"> Remove Listing</button>
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
                        <?= $request['offeredBook']; ?></p>

                        <p><strong>Requested Book:</strong>
                        <?= $request['requestedBook']; ?></p>

                        <p><strong>Message:</strong><br>
                        <?= $request['message']; ?></p>

                        <button class="ex-btn">Accept</button>
                        <button class="ex-btn" style="background:#999;">Decline</button>

                    </div>
                    <?php endforeach; ?>
            </div>
            </body>
</html>


<?php require_once '../includes/footer.php'; ?>