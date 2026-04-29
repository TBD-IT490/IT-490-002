<?php

require_once __DIR__ .'/vendor/autoload.php'; /** rmq library */

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Monolog\Handler\AbstractProcessingHandler;
use PhpAmqpLib\Message\AMQPMessage;
use Monolog\Formatter\LineFormatter;




define('RMQ_HOST', 'localhost'); //p3 ts pass - matt
define('RMQ_PORT', 5672);
define('RMQ_USER', 'broker'); //wtv user matt made
define('RMQ_PASS', 'test'); //wtv pass matt made
$connection = new AMQPStreamConnection(RMQ_HOST,RMQ_PORT,RMQ_USER,RMQ_PASS);
$channel = $connection->channel();
$channel->queue_declare("logs_queue", false, true, false, false);
$callback = function ($msg) {
    echo "something sent";
    $log = json_decode($msg->body, true);
    file_put_contents('central.log', $log["formatted"], FILE_APPEND);
};
$channel->basic_consume("logs_queue", "", false , true, false, false, $callback);


while ($channel->is_consuming()) {
    $channel-> wait();

}




?>