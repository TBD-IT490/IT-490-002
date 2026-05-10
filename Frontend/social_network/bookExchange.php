<?php
session_start();
require_once '../includes/header.php';
//functions and headers
require_once 'includes/data.php';
?>

<!DOCTYPE html>
<html lang="en">
    <h1 class="my-4">Trade and Buy Books with Noetic</h1>
    <div class="container">
        <div class ="row g-4">
            <div class="col-sm-6 col-md-4 col-lg-3">
                <div class="n-card p-3 h-100 d-flex flex-column">
                    <h5 class="n-card-title">Book Exchange</h5>
                    <p class="n-card-text"> Trade wth friends and other book lovers! </p>
                </div>
            </div>
        </div>
    </div>  
</html>


<?php require_once '../includes/footer.php'; ?>