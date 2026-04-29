<?php
require_once __DIR__ .'/vendor/autoload.php'; /** rmq library */

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Monolog\Handler\AbstractProcessingHandler;
use PhpAmqpLib\Message\AMQPMessage;
use Monolog\Formatter\LineFormatter;

class RabbitMQLOG extends AbstractProcessingHandler {
    // oop sucks
    private $channel;
    public function __construct($host, $port, $user, $pass) {
        parent::__construct(Logger::DEBUG);
        $connection = new AMQPStreamConnection($host,$port, $user, $pass);
        $this->channel = $connection->channel();
        $this->channel->queue_declare("logs_queue", false, true, false, false);
        $this->channel->exchange_declare('logs_exchange', 'fanout', false, false, false);
        $this->queue = "logs_queue"; // not needed
    }
    protected function write($info): void {     
        $msg = new AMQPMessage(json_encode($info));
        $this->channel->basic_publish($msg,'',$this->queue);

    }

}

define('RMQ_HOST', 'localhost'); //p3 ts pass - matt
define('RMQ_PORT', 5672);
define('RMQ_USER', 'broker'); //wtv user matt made
define('RMQ_PASS', 'test'); //wtv pass matt made


$log_handler = new RabbitMQLOG(RMQ_HOST, RMQ_PORT, RMQ_USER, RMQ_PASS);



$log = new Logger('Noetic-Database-Listener');
$log->pushHandler($log_handler);
$log->pushHandler(new StreamHandler(__DIR__ .'noetic-database.log', Logger::DEBUG));
$format = "%level_name%: %message%\n";
$formatter = new LineFormatter($format);
$cli = new StreamHandler('php://stdout', Logger::DEBUG);
$cli->setFormatter($formatter);
$log->pushHandler($cli);

$output = shell_exec("tailscale status --json");
$data = json_decode($output, true);
$Selfhost = $data['Self']['HostName'];
$Selfip = $data['Self']['TailscaleIPs'][0];

echo "SELF: $Selfhost - $Selfip\n";

$log->info("testtest" . " | ".gethostname());

$ips = [];

foreach ($data['Peer'] as $peer){
    $host = $peer['HostName'];
    $ip   = $peer['TailscaleIPs'][0];
    if (str_contains("","qa")) {

    } elseif (str_contains("","prod")) {

    } elseif(str_contains("","dev")) {

    } else {


    }
    echo "$host - $ip\n";
}







?>