#!/usr/bin/php
<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/fetchData.php';
//require_once __DIR__ . '/fetchDataCron.php';
require_once __DIR__ .'/vendor/autoload.php'; /** rmq library */
use PhpAmqpLib\Connection\AMQPStreamConnection; /**import RMQ classes*/
use PhpAmqpLib\Message\AMQPMessage;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Formatter\LineFormatter;

define('RMQ_HOST', '100.101.27.73'); //p3 ts pass - matt
define('RMQ_PORT', 5672);
define('RMQ_USER', 'broker'); //wtv user matt made
define('RMQ_PASS', 'test'); //wtv pass matt made

define('DB_HOST', '100.112.153.128'); //nat ip 4 db
define('DB_USER', 'app_user');
define('DB_PASS', 'AppUsrPwd123!'); 
define('DB_NAME', 'noetic');

//$log = new Logger('Noetic-DMZ-Listener');
//$log->pushHandler(new StreamHandler(__DIR__ .'noetic-dmz.log', Logger::DEBUG));
//$format = "%level_name%: %message%\n";
//$formatter = new LineFormatter($format);
//$cli=new StreamHandler('php://stdout', Logger::DEBUG);
//$cli->setFormatter($formatter);
//$log->pushHandler($cli);
//connecting to matt
echo "DMZ Listener Starting\n";

echo "[*] Connected to RMQ\n";
echo "[*] Waiting for messages...\n";
echo "[*] Press CTRL+C to exit\n";

//listen

//RMQ processing
//ctrl c ctrl v from db listener
//chat am i doing this right??
function processMessage($req) {
	global $log;
    $routing_key = $req->delivery_info['routing_key'];
	$message = json_decode($req->body, true);
    if ($routing_key == 'api.on_demand') {
        $response = onDemandAPICall($message);
    } else {
		$log->error('SOMEONE FORGOT ROUTING KEY >:( ' . $routing_key ."");
	}
    //sending reply back
	$reply_msg = new AMQPMessage(json_encode($response), ['correlation_id' => $req->get('correlation_id')]);	
	$req->getChannel()->basic_publish($reply_msg, '', $req->get('reply_to'));
	//$log->info("".  $response['message'] . "");
	$req->ack(); //tell rmq we done w/ msg
}

function tuff($searchTerms) {
    $data=fetchBooks($searchTerms);
    if($data==null){
	echo"[" . date('c'). "] Cron failed to fetch data :c \n";
	exit(1);
    }

try {
	$clean_data = cleanData($data);
} catch (Exception $e) {
	echo "". $e->getMessage() ."";
}

    return processPublishBooks($clean_data);
}
function onDemandAPICall($data) {

    $searchTerm = $data['search_query'] ?? '';
    echo "Searching for book: " . $searchTerm . "\n";
    $tuff = tuff($searchTerm);    



    if ($tuff) { 
        return ["success"=> true, "books" => $tuff];
    }else {
        return ["success"=> false];
    }

}
$connection = new AMQPStreamConnection(RMQ_HOST, RMQ_PORT, RMQ_USER, RMQ_PASS);
$channel = $connection->channel();
$channel->exchange_declare('user_exchange', 'direct', false, true, false);
$channel->queue_declare('api_queue', false, true, false, false); //creating queue if one not existent
$channel->queue_bind('api_queue', 'user_exchange', 'api.on_demand');
$channel->basic_consume('api_queue', '', false, false, false, false, 'processMessage');



while ($channel->is_consuming()) {
	$channel->wait();
}

//clean
$channel->close();
$connection->close();




?>
