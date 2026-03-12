#!/usr/bin/php
<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/fetchData.php';
require_once __DIR__ . '/fetchDataCron.php';
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

//connecting to matt
$connection = new AMQPStreamConnection(RMQ_HOST, RMQ_PORT, RMQ_USER, RMQ_PASS);
$channel = $connection->channel();

$msg = new AMQPMessage(json_encode($data), ['delivery_mode' => 2]); //make msg persistent

$channel->basic_publish($msg, '', 'api_queue');  // since queue alr made in rabbitmqpublish file right?

//thank you google
$channel->basic_consume(
    'api_queue', //queue
    '', //consumer tag
    false, //no local
    true, //no ack
    false, //exclusive
    false, //no wait
    $callback //callback function
    );
    while ($channel->is_consuming()) {
        $channel->wait();
    }
    $channel->close();
    $connection->close();

//connecting to nat, 
//ctrl c ctrl v from db listener
function connectDB(){
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	if ($conn->connect_error) {
		global $log;
		$log->error('Database connection failed'. $conn->connect_error);
		return null;
	}
	return $conn;
}

//RMQ processing
//ctrl c ctrl v from db listener
//chat am i doing this right??
function processMessage($req) {
	global $log;
	$routing_key_books = $req->delivery_info['routing_key_books'];
	$message = json_decode($req->body, true);
    //sending reply back
	$reply_msg = new AMQPMessage(json_encode($response), ['correlation_id' => $req->get('correlation_id')]);	
	$req->getChannel()->basic_publish($reply_msg, '', $req->get('reply_to'));
	$log->info("".  $response['message'] . "");
	$req->ack(); //tell rmq we done w/ msg
}

$callback = function(AMQPMessage $msg){
    echo "Received message: " . $msg->body . "\n";
    $data = json_decode($msg->body, true);
    if(!isset($data['type']) || $data['type'] !== 'search') {
        echo "Invalid message request type\n";
        return;
    }
    $searchTerm = $data['query'] ?? '';
    echo "Searching for book: " . $searchTerm . "\n";
    //hey db do we have the book?
    $raw_data=fetchBooks($searchTerm);
    $db=connectDB();
    $stmt = $db->prepare("SELECT * FROM books WHERE title LIKE ?");
    $searchParam = "%$searchTerm%";
    $stmt->bind_param("s", $searchParam);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        echo "Book found in DB for search term: " . $searchTerm . "\n";
        echo "Returning data...\n";
        $response=$results->fetch_all(MYSQLI_ASSOC);
        }
     else {
        echo "No books found for search term: " . $searchTerm . "\n";
        $response=fetchBooks($searchTerm);
        $response=["status"=>"success", "data"=>"API Results r here"];
    }  
    
    //published api
    if($searchData){
        $clean_data=cleanData($raw_data);
        processPublishBooks($clean_data);
        echo "Successfully fetched and published data for search term: " . $searchTerm . "\n";
    }
    else{
        echo "Failed to fetch data :c";
    }

    $msg->ack(); //acknowledge message

}
?>
