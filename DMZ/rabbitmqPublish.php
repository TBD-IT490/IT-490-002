#!/usr/bin/env php
<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

function publishToRabbit( string $json): void{
	//connect to rabbit
	$connection = new AMQPStreamConnection(RABBITMQ_HOST, RABBITMQ_PORT, RABBITMQ_USER, RABBITMQ_PASS );
	$channel = $connection->channel();
	$channel->queue_declare(RABBITMQ_QUEUE, false, true, false, false);
	$msg = new AMQPMessage($json, ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]);
	$channel -> basic_publish($msg, 'user_exchange', 'api.cache');

	$channel->close();
	$connection->close(); 
}

?>
