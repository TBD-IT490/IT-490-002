#!/usr/bin/php
<?php

require_once __DIR__ .'/vendor/autoload.php'; /** rmq library */
use PhpAmqpLib\Connection\AMQPStreamConnection; /**import RMQ classes*/
use PhpAmqpLib\Message\AMQPMessage;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Formatter\LineFormatter;

//define('RMQ_HOST', '100.101.27.73'); //p3 ts pass - matt
define('RMQ_HOST','localhost');
define('RMQ_PORT', 5672);
define('RMQ_USER', 'broker'); //wtv user matt made
define('RMQ_PASS', 'test'); //wtv pass matt made
//define('DB_HOST', '100.112.153.128'); //my ts ip
define('DB_HOST', 'localhost');
define('DB_USER', 'app_user');
define('DB_PASS', 'AppUsrPwd123!'); 
define('DB_NAME', 'deploy');
$log = new Logger('Noetic-Deploy-Listener');
$log->pushHandler(new StreamHandler(__DIR__ .'noetic-Deploy.log', Logger::DEBUG));
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
        $channel->exchange_declare('user_exchange', 'direct', false, true, false);
        $channel->queue_declare('user_events_queue', false, true, false, false);
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

        $channel->basic_publish($msg, 'user_exchange', $action);

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


//db connection
function connectDB() {
	$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	if ($conn->connect_error) {
		global $log;
		$log->error('Database connection failed'. $conn->connect_error);
		return null;
	}
	return $conn;
}

function handle_request_bundle($data) {
    global $log;
	$conn = connectDB();
	if(!$conn) {
		return ['success' => false, 'message' => 'Database connection failed.'];
	}
    $stmt = $conn->prepare(query:"Select version from bundle where name like CONCAT (?, '%')");
    $stmt->bind_param('s', $data["host"]);
    $stmt->execute();
	$result = $stmt->get_result();
    if($result->num_rows > 0) { 
        $rows = $result->fetch_all();
        $count = count($rows);
        return ['success'=> true, 'version'=> $count + 1, "message"=> "version given" . $count + 1];
    } else {
    return ['success'=> true,'version'=> '1', "message"=> "version not given"];
    }
}

function handle_submit_bundle($data) {
    global $log;
	$conn = connectDB();
	if(!$conn) {
		return ['success' => false, 'message' => 'Database connection failed.'];
	}
    $name = $data['name'];
    $version = $data['version'];
    $status = "new";
    $path = $data["path"];


    $stmt = $conn->prepare("INSERT INTO bundle (name, version, status, file_path) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("siss", $name, $version, $status, $path);
    $stmt->execute();
    return ["success"=> true,"message"=> "bundle added"];
}

function processMessage($req) {
	global $log;
	$routing_key = $req->delivery_info['routing_key'];
	$message = json_decode($req->body, true);
	echo print_r("RAHHH $routing_key\n", true);

	if($routing_key==='deploy.request_bundle') {
		$response = handle_request_bundle($message);

	}elseif($routing_key==='deploy.submit_bundle') {
		$response = handle_submit_bundle($message);

	}else {
		$log->error('SOMEONE FORGOT ROUTING KEY >:( ' . $routing_key ."");
	}

	//sending reply back
	$reply_msg = new AMQPMessage(json_encode($response), ['correlation_id' => $req->get('correlation_id')]);	
	$req->getChannel()->basic_publish($reply_msg, '', $req->get('reply_to'));
	$log->info("".  $response['message'] . "");
	$req->ack(); //tell rmq we done w/ msg
}

echo "Deploy Listener Starting\n";

//connecting to rmq
$connection = new AMQPStreamConnection(RMQ_HOST, RMQ_PORT, RMQ_USER, RMQ_PASS);
$channel = $connection->channel();
$channel->exchange_declare('deploy_exchange', 'direct', false, true, false);
$channel->queue_declare('deploy_events_queue', false, true, false, false); //creating queue if one not existent
$channel->basic_qos(null, 1, null); //process one msg at a time
$channel->queue_bind('deploy_events_queue', 'deploy_exchange', 'deploy.request_bundle');
$channel->queue_bind('deploy_events_queue', 'deploy_exchange', 'deploy.submit_bundle');

$channel->basic_consume('deploy_events_queue', '', false, false, false, false, 'processMessage');

echo "[*] Connected to RMQ\n";
echo "[*] Waiting for messages...\n";
echo "[*] Press CTRL+C to exit\n";
//listen
while ($channel->is_consuming()) {
	$channel->wait();
}
//clean
$channel->close();
$connection->close();
?>