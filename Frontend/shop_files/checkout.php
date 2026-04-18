<?php 
    require __DIR__ . '/vendor/autoload.php';
    $stripe_secret_key = "sk_test_51TLnAZGZNhqYIItSaTy1QNwK0J3xC1Lmq8r7SsGSAhl5jThqjmc9VHSn64qXipu6FWPuMfkLaq9tOhb3iC1u04sM00sdszOeK2";

    \Stripe\Stripe::setApiKey($stripe_secret_key);

    $checkout_session = \Stripe\Checkout\Session::create([
        "mode" => "payment",
        "success_url" => "http://localhost:8080/shop_files/success.php",
        "cancel_url" => "http://localhost:8080/shop_files/cart.php",
        "locale" => "en",
        "line_items" => [
            [
                "quantity" => 1,
                "price_data" => [
                    "currency" => "usd",
                    "unit_amount" => 2000,
                    "product_data" => [
                        "name" => "Mystery Novel"
                    ]
                ]
            ]
        ]
    ]);

    http_response_code(303);
    header("Location: " . $checkout_session->url);