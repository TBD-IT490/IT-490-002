#!/usr/bin/php
<?php
//require_once __DIR__ . '/config.php';
require_once __DIR__ . '/fetchData.php';
require_once __DIR__ .'/vendor/autoload.php'; /** rmq library */
use PhpAmqpLib\Connection\AMQPStreamConnection; /**import RMQ classes*/
use PhpAmqpLib\Message\AMQPMessage;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Formatter\LineFormatter;

use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();
$backend = $_ENV['BACKEND']; //for rabbit and db






define ('API_BASE', 'https://www.googleapis.com/books/v1/volumes');
define ('API_KEY', 'AIzaSyDYJbl3JpctqD5r3bn_qF4LkCzBvjOfdQI');

//for rabbit
define ('RABBITMQ_HOST',$backend); // change to matt's ip 100.127.138.110
define ('RABBITMQ_PORT', 5672);
define ('RABBITMQ_USER', 'broker'); //change to broker rabbit user
define ('RABBITMQ_PASS', 'test'); // change to test rabbit pass
define ('RABBITMQ_QUEUE', 'api_queue'); //change queue name to actual name


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

define('RMQ_HOST', $backend); //rabbit

define('RMQ_PORT', 5672);
define('RMQ_USER', 'broker'); //wtv user matt made
define('RMQ_PASS', 'test'); //wtv pass matt made

define('DB_HOST', $backend); //db

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
$log_handler = new RabbitMQLOG($backend, RMQ_PORT, RMQ_USER, RMQ_PASS);

$log = new Logger('Noetic-API-Listener-' . gethostname());
$log->pushHandler($log_handler);
$log->pushHandler(new StreamHandler(__DIR__ .'central.log', Logger::DEBUG));
$format = "%level_name%: %message%\n";
$formatter = new LineFormatter($format);
$cli=new StreamHandler('php://stdout', Logger::DEBUG);
$cli->setFormatter($formatter);
$log->pushHandler($cli);
echo "DMZ Listener Starting\n";
echo "[*] Connected to RMQ\n";
echo "[*] Waiting for messages...\n";
echo "[*] Press CTRL+C to exit\n";

//listen

//caching 10 random books
function refreshCache() {
    //makes 10 random search terms 
    $randomTerms=[];
    for($i=0; $i<10; $i++) {
        $len=rand(1,2);
        $word='';
        for($j=0; $j<$len; $j++) {
            $word .= chr(rand(97,122)); //a-z ascii
        }
        $randomTerms[] = $word;
    }
    //caches the 10 random terms
    //erm google?
    /*
    $cachFile='cache.log';
    if(file_exists($cachFile)) {
        $books=file_get_contents($cachFile), true;
    } else {
        $data=file_get_contents($cachFile), true;
        $books=$data['books'] ?? [];

        //logs cached books
        file_put_contents($cachFile, ['books'=>$books]);
        
    }*/
    shuffle($randomTerms);
    $terms=array_slice($randomTerms, 0, 10);
    foreach($terms as $term) {
        echo "Fetching data for term: " . $term . "\n";
        $data=fetchBooks($term);
        if($data==null){
            echo"Failed to fetch data for term: " . $term . "\n";
            continue;
        }
        try {
            $clean_data = cleanData($data);
            proccessPublishBooks($clean_data);
        } catch (Exception $e) {
            echo "Error cleaning da data: ". $e->getMessage() ."";
        }
    }
    echo "HORRAY! Cache refresh complete.\n";
}
function buildURL(string $searchTerm): string
{
	
	$query = http_build_query(['q'=>$searchTerm, 'maxResults'=>10, 'key'=>API_KEY]);
	return API_BASE . '?' . $query;
}
function cleanData($data){
	$book = [];
	for($i = 0; $i < count($data["items"] ?? []); $i++) {
	/// print array
    $book[$i]['api_book_id'] = $data["items"][$i]['id'] ?? null;
    $book[$i]['isbn'] = $data["items"][$i]['volumeInfo']['industryIdentifiers'][1]['identifier'] ?? null;
    $book[$i]['title'] = $data["items"][$i]['volumeInfo']['title'] ?? null;
    $book[$i]['subtitle'] = $data["items"][$i]['volumeInfo']['subtitle'] ?? null;
    $book[$i]['author'] = $data["items"][$i]['volumeInfo']['authors'][0] ?? null;
    $book[$i]['description'] = $data["items"][$i]['volumeInfo']['description'] ?? null;
    $book[$i]['cover_url'] = $data["items"][$i]['volumeInfo']['imageLinks']['thumbnail'] ?? null;
    $book[$i]['publisher'] = $data["items"][$i]['volumeInfo']['publisher'] ?? null;
    $published_date = $data["items"][$i]['volumeInfo']['publishedDate'] ?? null;
	if (strlen($published_date) != 4){ 
		$published_date = date("Y", strtotime($published_date));
	}
	$book[$i]['published_year'] = $published_date;
	
	$book[$i]['genre'] = $data["items"][$i]['volumeInfo']['categories'][0] ?? null;
    $book[$i]['maturity_rating'] = $data["items"][$i]['volumeInfo']['maturityRating'] ?? null;
    $book[$i]['content_version'] = $data["items"][$i]['volumeInfo']['contentVersion'] ?? null;
	$book[$i]['pages'] = $data["items"][$i]['volumeInfo']['pageCount'] ?? 0 ;
	}
	echo '' . print_r($book, true) .'';
    return $book;
}
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
	$log->info("".  $response['message'] . "");
	$req->ack(); //tell rmq we done w/ msg
}

function tuff($searchTerms) {
    $data=fetchBooks($searchTerms);
    if($data==null){
	echo"[" . date('c'). "Failed to fetch data :c \n";
	exit(1);
    }

try {
	$clean_data = cleanData($data);
} catch (Exception $e) {
	echo "". $e->getMessage() ."";
}

    return processPublishBooks($clean_data);
}
function processPublishBooks(array $data){
	//$seenFile= __DIR__ . '/bookIDsInQueue.txt';
	//$seen = file_exists($seenFile) ? array_flip(file($seenFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)) : [];
	$count=count($data);
	$books = [];
	/*foreach($data as $book){

		//turns book entry into json string
		$json=json_encode($book);


		publishToRabbit($json);
		$count++;
		if($count > 10){ 
			break;
		}
	}*/
	$json=json_encode($data);
	return $json;
	//publishToRabbit($json);
	echo "Published $count books to RabbitMQ :D !!\n";
	//if($count > 0){return true;} else {return false;}
}
function fetchBooks(string $searchTerm): ?array{
	$url=buildURL($searchTerm);
	echo''.$url.'';
	$ch=curl_init($url);
	curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT=>10]);
	$response=curl_exec($ch);
	$httpCode=curl_getinfo($ch,CURLINFO_HTTP_CODE);	

	//trying to fix that 429 error :c
	if($httpCode==429){
		echo"Google hates us, waiting 5 seconds...\n";
		sleep(5);
		return null;//fetchBooks($searchTerm);
	}
	//deals with other errors	
	if($response==false || $httpCode!==200){
		fwrite(STDERR, "Error fetching book api :( \nHTTP code: $httpCode\n");
		curl_close($ch);
		return null;
	}
	curl_close($ch);
	$data=json_decode($response,true);
	if($data==null){
		fwrite(STDERR, "Error decoding JSON from book api :| \n");
		return null;
	} return $data;
}
function onDemandAPICall($data) {
    $searchTerm = $data['search_query'] ?? '';
    echo "Searching for book: " . $searchTerm . "\n";
    $tuff = tuff($searchTerm);    
    if ($tuff) { 
        return ["success"=> true, "books" => $tuff, "message" => 'book found'];
    }else {
        return ["success"=> false];
    }
}

//refreshCache();
$connection = new AMQPStreamConnection(RMQ_HOST, RMQ_PORT, RMQ_USER, RMQ_PASS);
$channel = $connection->channel();
$channel->exchange_declare('user_exchange', 'direct', false, true, false);
$channel->queue_declare('api_queue', false, true, false, false); //creating queue if one not existent
$channel->queue_declare("logs_queue", false, true, false, false);

$channel->queue_bind('api_queue', 'user_exchange', 'api.on_demand');
$channel->basic_consume('api_queue', '', false, false, false, false, 'processMessage');
$callback = function ($msg) {
    $log = json_decode($msg->body, true);
    file_put_contents('central.log', $log["formatted"], FILE_APPEND);
};
$channel->basic_consume("logs_queue", "", false , true, false, false, $callback);

while ($channel->is_consuming()) {
	$channel->wait();
}

//clean
$channel->close();
$connection->close();


?>
