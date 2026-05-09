<?php


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require __DIR__ . '/../vendor/autoload.php';
session_start();

$stripe_secret_key = "sk_test_51TLnAZGZNhqYIItSaTy1QNwK0J3xC1Lmq8r7SsGSAhl5jThqjmc9VHSn64qXipu6FWPuMfkLaq9tOhb3iC1u04sM00sdszOeK2";

\Stripe\Stripe::setApiKey($stripe_secret_key);

if (!isset($_SESSION['shopping_cart']) || empty($_SESSION['shopping_cart'])) {
    die("Cart is empty");
}

$cart_items = $_SESSION['shopping_cart'];

$line_items = [];

foreach ($cart_items as $item) {

    $line_items[] = [
        'price_data' => [
            'currency' => 'usd',
            'product_data' => [
                'name' => $item['product_name'],
            ],
            'unit_amount' => intval($item['product_price'] * 100),
        ],
        'quantity' => $item['product_quantity'],
    ];
}

try {

    $checkout_session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'line_items' => $line_items,
        'mode' => 'payment',
        'success_url' => 'http://localhost:8080/shop_files/cart.php',
        'cancel_url' => 'http://localhost:8080/shop_files/cart.php',
    ]);

    header("HTTP/1.1 303 See Other");
    header("Location: " . $checkout_session->url);
    exit();

} catch (Exception $e) {

    die("Stripe Error: " . $e->getMessage());
}