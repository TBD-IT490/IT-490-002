<?php
session_start();
require_once '../includes/header.php';
require_once '../includes/data.php';
?>

<!DOCTYPE html>
<html lang="en">
    <head>
    <h1 class="my-4">Trade and Buy Books with Noetic</h1>
    </head> 
    <div class="container">
        <div class ="row g-4">
            <div class="col-sm-6 col-md-4 col-lg-3">
                <div class="n-card p-3 h-100 d-flex flex-column">
                    <h5 class="n-card-title">Book Exchange</h5>
                    <p class="n-card-text"> Trade wth friends and other book lovers! </p>
                </div>
            </div>
        </div>
        <br></br>
    <h2>Browse Books</h2>
        <div class="book-ex-grid">
            <?php foreach ($availableBooks as $book): ?>
                <div class="book-ex-card">
                    <div class="book-ex-content">
                        <div class="condition">
                            <? = $book['condition']; ?>
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

    <!--MY OWNED BOOKS or my books up for trade i forgot-->
    <h2>My Books</h2>

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

</html>


<?php require_once '../includes/footer.php'; ?>