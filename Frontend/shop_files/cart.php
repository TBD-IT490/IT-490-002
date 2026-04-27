<?php 
  require_once '../includes/header.php'; 
  session_start();

  
?>

<doctype html>
<h1 class="my-4">Noetic's Blind Date with a Book</h1>
<div class="container">

<!--book 1-->
  <div class="row g-4">
    <div class="col-sm-6 col-md-4 col-lg-3">
      <div class="n-card p-3 h-100 d-flex flex-column">
        <h5 class="n-card-title">Mystery Novel</h5>
        <img src="mystery_book.jpg" alt="Mystery Novel" class="n-card-img mb-3">
        <p class="n-card-text">
          A thrilling mystery novel that will keep you on the edge of your seat.
        </p>
        <div class="mt-auto">
          <p><strong>$19.99</strong></p>
          <form method="post" action="add_to_cart.php">
            <button type="submit" class="btn btn-primary">Add To Cart</button>
          </form>
        </div>
      </div>
    </div>

    <!--book 2-->
    <div class="col-sm-6 col-md-4 col-lg-3">
      <div class="n-card p-3 h-100 d-flex flex-column">
        <h5 class="n-card-title">Romance Novel</h5>
        <img src="mystery_book.jpg" alt="Mystery Novel" class="n-card-img mb-3">
        <p class="n-card-text">
          An exciting love story for the ages, it will leave you wanting more!
        </p>
        <div class="mt-auto">
          <p><strong>$19.99</strong></p>
          <form method="post" action="add_to_cart.php">
            <button type="submit" class="btn btn-primary">Add To Cart</button>
          </form>
        </div>
      </div>
    </div>

    <!--book 3-->
    <div class="col-sm-6 col-md-4 col-lg-3">
      <div class="n-card p-3 h-100 d-flex flex-column">
        <h5 class="n-card-title">Action Novel</h5>
        <img src="mystery_book.jpg" alt="Mystery Novel" class="n-card-img mb-3">
        <p class="n-card-text">
          A breathtaking action novel that will leave you jumping out of your seat!
        </p>
        <div class="mt-auto">
          <p><strong>$19.99</strong></p>
          <form method="post" action="add_to_cart.php">
            <button type="submit" class="btn btn-primary">Add To Cart</button>
          </form>
        </div>
      </div>
    </div>

    <!--book 4-->
    <div class="col-sm-6 col-md-4 col-lg-3">
      <div class="n-card p-3 h-100 d-flex flex-column">
        <h5 class="n-card-title">Comedy Novel</h5>
        <img src="mystery_book.jpg" alt="Mystery Novel" class="n-card-img mb-3">
        <p class="n-card-text">
            A knee slapping comedy that will make you laugh till you cry!
        </p>
        <div class="mt-auto">
          <p><strong>$19.99</strong></p>
          <form method="post" action="add_to_cart.php">
            <button type="submit" class="btn btn-primary">Add To Cart</button>
          </form>
        </div>
      </div>
    </div>

    <!--book 5-->
    <div class="col-sm-6 col-md-4 col-lg-3">
      <div class="n-card p-3 h-100 d-flex flex-column">
        <h5 class="n-card-title">Sci-Fi Novel</h5>
        <img src="mystery_book.jpg" alt="Mystery Novel" class="n-card-img mb-3">
        <p class="n-card-text">
          Aliens?? Monsters?? Find out more in this epic sci-fi adventure!
        </p>
        <div class="mt-auto">
          <p><strong>$19.99</strong></p>
          <form method="post" action="add_to_cart.php">
            <button type="submit" class="btn btn-primary">Add To Cart</button>
          </form>
        </div>
      </div>
    </div>

  </div>
</div>
</html>

<?php require_once '../includes/footer.php'; ?>