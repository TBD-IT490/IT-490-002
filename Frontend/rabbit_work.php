<?php

require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPStreamConnection('100.127.138.110', 5672, 'database', 'test');
$channel = $connection->channel();

$channel->queue_declare('test_queue', false, true, false, false);

$msg = new AMQPMessage('Hello from PHP!', ['delivery_mode' => 2]);

$channel->basic_publish($msg, '', 'test_queue');

echo "Message sent!\n";

$channel->close();
$connection->close();
?>
