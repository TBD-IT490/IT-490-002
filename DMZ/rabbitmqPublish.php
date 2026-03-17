#!/usr/bin/env php
<?php
//require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

function publishToRabbit( string $json): void{
	//connect to rabbit
	$connection = new AMQPStreamConnection(RABBITMQ_HOST, RABBITMQ_PORT, RABBITMQ_USER, RABBITMQ_PASS );
	$channel = $connection->channel();
	$corr_id = uniqid();
	list($callback_queue,,) = $channel->queue_declare("", false, false, true, false);
	$channel->queue_declare(RABBITMQ_QUEUE, false, true, false, false);
	$msg = new AMQPMessage($json, [
		'delivery_mode'  => 2,
        'correlation_id' => $corr_id,
        'reply_to'       => $callback_queue
	]);
	$channel -> basic_publish($msg, 'user_exchange', 'api.cache');
	$channel->close();
	$connection->close(); 
}

?>
