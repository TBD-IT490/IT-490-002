<?php
require_once __DIR__ .'/vendor/autoload.php'; /** rmq library */

use PhpAmqpLib\Connection\AMQPStreamConnection; /**import RMQ classes*/
use PhpAmqpLib\Message\AMQPMessage;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Formatter\LineFormatter;


//define('RMQ_HOST', '100.101.27.73'); //p3 ts pass - matt
define('RMQ_HOST', 'localhost'); //p3 ts pass - matt
define('RMQ_PORT', 5672);
define('RMQ_USER', 'broker'); //wtv user matt made
define('RMQ_PASS', 'test'); //wtv pass matt made
$log = new Logger('Noetic-dev-deploy-Listener');
$log->pushHandler(new StreamHandler(__DIR__ .'noetic-dev-deploy.log', Logger::DEBUG));
$format = "%level_name%: %message%\n";
$formatter = new LineFormatter($format);
$cli=new StreamHandler('php://stdout', Logger::DEBUG);
$cli->setFormatter($formatter);
$log->pushHandler($cli);

function rmq_rpc(string $action, array $payload = []): ?array {
    global $_DEBUG_LOG;
    try {
        $connection = new AMQPStreamConnection(
            RMQ_HOST,
            RMQ_PORT,
            RMQ_USER,
            RMQ_PASS
        );

        $channel = $connection->channel();
        $channel->exchange_declare('deploy_exchange', 'direct', false, true, false);
        $channel->queue_declare('deploy_events_queue', false, true, false, false);
        $channel->basic_qos(null, 1, null);

        
		
		list($callback_queue,,) = $channel->queue_declare('', false, false, true, false);
        $response = null;
        $corr_id = uniqid();
        $onResponse = function($msg) use($corr_id, &$response) {
            if ($msg->get('correlation_id') === $corr_id) {
                $response = $msg->getBody();
            }
        };
        $channel->basic_consume($callback_queue, '', false, true, false, false, $onResponse);

        //$payload['user_id'] = $_SESSION['id'] ?? null;
        //$payload['username'] = $_SESSION['username'] ?? null;

        $msg = new AMQPMessage(
            json_encode($payload),
            [
                'delivery_mode' => 2,
                'correlation_id' => $corr_id,
                'reply_to' => $callback_queue,
            ]
        );

        $channel->basic_publish($msg, 'deploy_exchange', $action);

        while ($response === null) {
            $channel->wait(null, false, 0); 
        }


        $decoded = json_decode($response, true);
        
        $_DEBUG_LOG[] = [
            'action' => $action,
            'request' => $payload,
            'response' => $decoded,
            'raw' => $response,
        ];
 		$channel->close();
        $connection->close();        
        return $decoded;

        //debugging because taryn sucks at php and she doesn't know if it's working or not
    } catch (\Exception $e) {
        error_log("rmq_rpc error for '$action': " . $e->getMessage());
        $_DEBUG_LOG[] = [
            'action' => $action,
            'request' => $payload,
            'error' => $e->getMessage(),
        ];
        return null;
    }
}

$hostname = gethostname();
$archive_name = "" . $hostname . "_bundle_v";
$folder = "/home/it490/IT-490-002";
if ($hostname === "broker") { // change these to the new vm names
$folder = $folder . "/Backend";
}elseif ($hostname === "") { }
else {}

$tar = new PharData($archive_name . ".tar");
$tar->compress(Phar::GZ);
$tar->buildFromDirectory($folder);
$response = rmq_rpc("deploy.request_bundle", ["host"=> $hostname]);
$success = $response["success"];
$remote = 'localhost';
if ($success) {
    $version = $response["version"];
    $path = "/home/it490/IT-490-002/Deployment/bundles/" . $archive_name . $version;

    $cmd_scp = "scp $archive_name it490@$remote:$path";
    exec($cmd_scp, $output, $status);
    if ($status === 0) {
        $response = rmq_rpc("deploy.submit_bundle", ["host"=> $hostname, "path" => $path]);

    } else {
    // explain why it broke
    }
} else {
// log the breakage
}





?>