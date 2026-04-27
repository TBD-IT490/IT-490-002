<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['book_id'];
    $title = $_POST['book_title'];
    $price = $_POST['book_price'];

  
    $item = [
        'id' => $id,
        'title' => $title,
        'price' => $price,
        'quantity' => 1 
    ];

    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    
    $found = false;
    foreach ($_SESSION['cart'] as &$cartItem) {
        if ($cartItem['id'] == $id) {
            $cartItem['quantity'] += 1;
            $found = true;
            break;
        }
    }

    if (!$found) {
        $_SESSION['cart'][] = $item;
    }

    header('Location: cart.php');
    exit();
}
?>
