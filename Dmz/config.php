#!/usr/bin/env php
<?php
//require_once __DIR__ . '/apiListener.php';
require_once __DIR__ .'/vendor/autoload.php'; /** rmq library */
use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();
$backend = $_ENV['BACKEND']; //for rabbit and db

define ('API_BASE', 'https://www.googleapis.com/books/v1/volumes');
define ('API_KEY', 'AIzaSyDYJbl3JpctqD5r3bn_qF4LkCzBvjOfdQI');

//for rabbit
define ('RABBITMQ_HOST',$backend); // change to matt's ip 100.127.138.110
define ('RABBITMQ_PORT', 5672);
define ('RABBITMQ_USER', 'broker'); //change to broker rabbit user
define ('RABBITMQ_PASS', 'test'); // change to test rabbit pass
define ('RABBITMQ_QUEUE', 'api_queue'); //change queue name to actual name

?>
