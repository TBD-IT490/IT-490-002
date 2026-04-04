#!/usr/bin/php
<?php 
require_once __DIR__ .'/vendor/autoload.php'; /** rmq library */
use PhpAmqpLib\Connection\AMQPStreamConnection; /**import RMQ classes*/
use PhpAmqpLib\Message\AMQPMessage;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Formatter\LineFormatter;
define('DB_HOST', 'localhost');
define('DB_USER', 'app_user');
define('DB_PASS', 'AppUsrPwd123!'); 
define('DB_NAME', 'deploy');
define('RMQ_HOST','localhost');
define('RMQ_PORT', 5672);
define('RMQ_USER', 'broker'); //wtv user matt made
define('RMQ_PASS', 'test'); //wtv pass matt made
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
        $channel->exchange_declare('install_exchange', 'direct', false, true, false);
        $channel->queue_declare('install_events_queue', false, true, false, false);
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

        $channel->basic_publish($msg, 'install_exchange', $action);

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
function connectDB() {
	$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	if ($conn->connect_error) {
		global $log;
		$log->error('Database connection failed'. $conn->connect_error);
		return null;
	}
	return $conn;
}
$conn = connectDB();
if(!$conn) {
    //explain why it broke and dont return anything
	return ['success' => false, 'message' => 'Database connection failed.'];
}

if ($argv[1] == "prod") {
$stmt = $conn->prepare(query:"Select * from bundle where name like CONCAT (?, '%') ORDER BY version DESC limit 1");
$stmt->bind_param('s', $argv[2]);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$log->info("Name: " . $row["name"] . " Version: " . $row["version"] . " Path: " . $row["file_path"] . " Status: " . $row["status"]);

$input = readline("Install y or n: ");

if ($input == "y") { 



} else {

exit();
}
$input = readline("Enter Package Status: ");

} else if ($argv[1] == "qa") {
$stmt = $conn->prepare(query:"Select * from bundle where name like CONCAT (?, '%') ORDER BY version DESC limit 1");
$stmt->bind_param('s', $argv[2]);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$log->info("Name: " . $row["name"] . " Version: " . $row["version"] . " Path: " . $row["file_path"] . " Status: " . $row["status"]);

$input = readline("Install y or n: ");

if ($input == "y") { 
    $path = "/home/it490/target/"  . $row["name"];
    $remote = "localhost";
    $cmd_scp = "scp " . $row["file_path"] ." it490@$remote:$path";
    exec($cmd_scp, $output, $status);
    if ($status === 0) {
        $response = rmq_rpc("install.install", ["name" => $row["name"], "path" => $path]);

        } else {
    // explain why it broke
    }

} else {

exit();
}
$input = readline("Enter Package Status: ");
} else {

//explain why broke
}







?>