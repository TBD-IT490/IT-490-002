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
//define('RMQ_HOST','localhost');
define('RMQ_HOST','100.114.131.27');

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
$host;
$cluster;
$choice;
$input = readline("Install or Rollback: ");
if($input == "install") { 
    $choice = "install";
} else if($input == "rollback") {
    $choice = "rollback";
} else {
    echo "input rollback or install\n";
    exit();
}
$input = readline("qa or prod: ");
if($input == "qa") {
    $cluster = "qa";
} elseif ($input == "prod") {
    $cluster = "prod";
} else {
    echo "qa or prod\n";
    exit();
}
$input = readline("front|back|dmz: ");
if($input == "front") {
    $host = "front";
} elseif ($input == "back") {
    $host = "back"; // change these later
} elseif ($input == "dmz") {
    $host = "dmz";
} else {
    echo "front or back or dmz\n";
    exit();
}
$stmt = $conn->prepare(query:"Select * from bundle where name like CONCAT (?, '%') ORDER BY version DESC");
$stmt->bind_param('s', $host);
$stmt->execute();
$result = $stmt->get_result();
$bundles = [];
$i = 1;
while($row = $result->fetch_assoc()) {
    echo "[$i] " . $row['name'] . " - version " . $row['version'] . " - Status " . $row["status"]."\n";
    $bundles[$i] = $row; // store for later    
    $i++;
}
if (empty($bundles)) {
    echo "No results found\n";
    exit;
}
$bundle = readline("Pick a number: ");
if (!isset($bundles[$bundle])) {
    echo "Invalid selection\n";
    exit;
}
$selected = $bundles[$bundle];
$log->info("You selected: " . $selected['name'] . " version " . $selected['version'] . "\n");
$input = readline("Install/rollback y or n: ");
if ($input == "y") { 
    $path = "/home/it490/target/"  . $selected["name"];
    $remote = "localhost";
    if ($cluster == "qa") {
        if ($host == "front"){
            $remote = "100.122.99.69";
        } elseif ($host == "back") {
            $remote = "100.107.210.121";
        } elseif ($host == "dmz") {
            $remote = "100.114.131.27";
        } else {}
    } elseif ($cluster == "prod") {
        if ($host == "front"){
            $remote = "100.109.181.25";
        } elseif ($host == "back") {
            $remote = "100.91.21.90";
        } elseif ($host == "dmz") {
            $remote = "100.70.132.110";
        } else {}
    } else {

    }
    $cmd_scp = "scp " . $selected["file_path"] ." it490@$remote:$path";
    exec($cmd_scp, $output, $status);
    if ($status === 0) {
        $response = rmq_rpc("install.install", ["name" => $selected["name"], "path" => $path]);
    } else {
        echo "something broke with install/rollback";
    }
    $input = readline("Enter Package Status(new|passed|failed): ");
    if ($input == "new") { 
        echo "everything is cool just change status later";
        exit();
    } elseif ($input == "passed") { 
    echo "everything is cool, package passed so send to prod";
    $stmt = $conn->prepare(query:"UPDATE bundle SET status = 'passed' WHERE name = ?");
    $stmt->bind_param('s', $selected['name']);
    $stmt->execute();
    $result = $stmt->get_result();
} elseif ($input == "failed") {
    echo "everything is cool, roll it back";
    $stmt = $conn->prepare(query:"UPDATE bundle SET status = 'failed' WHERE name = ?");
    $stmt->bind_param('s', $selected['name']);
    $stmt->execute();
    $result = $stmt->get_result();
}
} else {
//explain why broke
}



?>