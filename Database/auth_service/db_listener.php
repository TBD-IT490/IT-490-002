#!/usr/bin/php
<?php

require_once __DIR__ .'/vendor/autoload.php'; /** rmq library */
use PhpAmqpLib\Connection\AMQPStreamConnection; /**import RMQ classes*/
use PhpAmqpLib\Message\AMQPMessage;

define('RMQ_HOST', '100.101.27.73'); //p3 ts pass - matt
define('RMQ_PORT', 5672);
define('RMQ_USER', 'broker'); //wtv user matt made
define('RMQ_PASS', 'test'); //wtv pass matt made
define('DB_HOST', '100.112.153.128'); //my ts ip
define('DB_USER', 'app_user');
define('DB_PASS', 'AppUsrPwd123!'); 
define('DB_NAME', 'noetic');

//db connection
function connectDB() {
	$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	if ($conn->connect_error) {
		echo "ERROR: Database connection failed: " . $conn->connect_error . "\n";
		return null;
	}
	return $conn;
}

//handle user reg
function handleRegistration($data) {
	$conn = connectDB();
	if(!$conn) {
		return ['success' => false, 'message' => 'Database connection failed.'];
	}

	$username = $data['username'];	
	$email = $data['email'];

	if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
		//INVALID EMAIL
		return ['success'=>false, 'message'=>'Insert a valid email format.'];
	}
	//echo "DEBUG: " . print_r($data, true) . "\n"; //rahh pass hash error debug
	if(!isset($data['password_hash'])){ //prevent crash error from a null pass
		return ['success'=>false, 'message'=>'Internal ERROR: Password data missing!'];
	}
	//$pass_hash = password_hash($data['password_hash'], PASSWORD_DEFAULT); 
	$password = $data['password_hash'];

	$stmt = $conn->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
	$stmt->bind_param("sss", $username, $email, $password);
	if ($stmt->execute()) {
		echo "SUCCESS: Registered: $username\n";
		$response = ['success' => true, 'message' => 'user created, registration success!'];
	} else {
		echo "FAILED: Registration error: " . $stmt->error . "\n";
		$response = ['success' => false, 'message' => 'user already exists!'];
	}
	$stmt->close();
	$conn->close();
	return $response;
}

//handle login and session
function handleLogin($data) {
	//make sure it's not null
//	echo "RAHHH 2: $data\n";
        if(!isset($data['username'], $data['password'])){
                return ['success'=>false, 'message'=>'Missing credentials!'];
	}

	$conn = connectDB();
	if(!$conn) {
		return ['success' => false, 'message' => 'Database connection error!'];
	}

	//data from request
	$username = $data['username'];
	$password = $data['password'];

	//getting user hash from db
	$stmt = $conn->prepare("SELECT id, username, password_hash FROM users WHERE username = ?");
	$stmt->bind_param("s", $username);
	$stmt->execute();
	$result = $stmt->get_result();
	$user = $result->fetch_assoc();
	$stmt->close();

	//checking if user exists
	if(!$user || !password_verify($password, $user['password_hash'])) {
		echo "FAILED: Invalid login for: $username\n";
		echo "O_O...";
		$conn->close();
		return ['success' => false, 'message' => 'Invalid username or password!'];
	}

	//generate session key
	$sessionKey = bin2hex(random_bytes(16)); //edit size (?)
	$expires = date('Y-m-d H:i:s', strtotime('+24 hours'));

	//save it to the db
	$stmt = $conn->prepare("INSERT INTO sessions (user_id, session_key, expires_at) VALUES (?, ?, ?)");
	$stmt->bind_param("iss", $user['id'], $sessionKey, $expires);
	$stmt->execute();
	$stmt->close();
	$conn->close();

	echo "SUCCESS: Login successful: $username\n";
	return ['success' => true, 'session_key' => $sessionKey, 'username' => $username, 'message' => 'Logged in!'];
}

//RMQ processing
function processMessage($req) {
	echo "HELLO!!\n";
	$routing_key = $req->delivery_info['routing_key'];
	$message = json_decode($req->body, true);
	if($routing_key==='user.login') {
		$response = handleLogin($message);
	}elseif($routing_key==='user.register') {
		$response = handleRegistration($message);
	}else {
		echo "SOMEONE FORGOT ROUTING KEY >:(\n";
		echo "UNKNOWN ROUTING KEY: ". $routing_key . "\n";
	}

	//sending reply back
	$reply_msg = new AMQPMessage(json_encode($response), ['correlation_id' => $req->get('correlation_id')]);	
	$req->getChannel()->basic_publish($reply_msg, '', $req->get('reply_to'));
	
	echo "SENT: Response: " . $response['message'] . "\n";
	$req->ack(); //tell rmq we done w/ msg
}

echo "DB Listener Starting\n";

//connecting to rmq
$connection = new AMQPStreamConnection(RMQ_HOST, RMQ_PORT, RMQ_USER, RMQ_PASS);
$channel = $connection->channel();
$channel->exchange_declare('user_exchange', 'direct', false, true, false);
$channel->queue_declare('user_events_queue', false, true, false, false); //creating queue if one not existent
$channel->basic_qos(null, 1, null); //process one msg at a time
$channel->queue_bind('user_events_queue', 'user_exchange', 'user.register');
$channel->queue_bind('user_events_queue', 'user_exchange', 'user.login');
$channel->basic_consume('user_events_queue', '', false, false, false, false, 'processMessage');

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
