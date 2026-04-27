<?php 
    require __DIR__ . '/vendor/autoload.php';
    $stripe_secret_key = "sk_test_51TLnAZGZNhqYIItSaTy1QNwK0J3xC1Lmq8r7SsGSAhl5jThqjmc9VHSn64qXipu6FWPuMfkLaq9tOhb3iC1u04sM00sdszOeK2";

    \Stripe\Stripe::setApiKey($stripe_secret_key);

    session_start();
    $cart_items = $_SESSION['cart'];
    $line_items = [];

    foreach ($cart_items as $item){
        $line_items[] = [
            'price_data' => [
                'currency' => 'usd',
                'product_data' => [
                    'name' => $item['name'],
                ],
                'unit_amount' => $item['price'] * 100, // this is the amount in cents
            ],
            'quantity' => $item['quantity'],
        ];
    }

    $checkout_session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'line_items' => $line_items,
        'mode' => 'payment',
        'success_url' => 'http://localhost:8080/cart.php',
        'cancel_url' => 'http://localhost:8080/cart.php',
    ]);

    http_response_code(303);
    header("Location: " . $checkout_session->url);
?>