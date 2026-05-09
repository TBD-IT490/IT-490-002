<?php

try {

    $connect = new PDO(
        "mysql:host=127.0.0.1;dbname=stripe;charset=utf8",
        "stripeUser",
        "stripe12345"
    );

    $connect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch(PDOException $e) {

    die("Connection failed: " . $e->getMessage());

}
?>