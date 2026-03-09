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
	//echo "RAHHH 2: $data\n";
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

//Handling creating a club*****
function handleCreateClub($data) {
	$conn = connectDB();
	if(!$conn) {
		return ['success' => false, 'message' => 'Database connection failed.'];
	}

	//VARIABLES CHANGED - 3/8 8:16PM ;-;
	$group_name = $data['name']; 
    $description = $data['group_desc']; 
    $book = $data['book_id']; 
	//$user_id = $data['created_by'];
    $invite_code = strtoupper(substr(md5(uniqid(rand(), true)),0,8));

	echo "DEBUG: Create Club Data: " . print_r($data, true) . "\n"; //debug
	$stmt = $conn->prepare("INSERT INTO book_clubs (club_name, group_desc, invite_code) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $group_name, $description, $invite_code);

	if ($stmt->execute()) {
		$club_id = $conn->insert_id;

		//make creator admin
		//$stmt2 = $conn->prepare("INSERT INTO club_members (club_id, user_id, role) VALUES (?, ?, ?)");
		$stmt2 = $conn->prepare("INSERT INTO club_members (club_id, role) VALUES (?, 'admin')"); //CHANGE TO TARYN'S VARIABLES (NO USER ID??)

		$stmt2->bind_param("i", $club_id);//, $user_id);
		$stmt2->execute();

		echo "SUCCESS: Club created: $group_name\n";
		return ['success' => true, 'club_id' => $club_id, 'invite_code' => $invite_code, 'message' => 'Club created!'];
	}

	return ['success' => false, 'message' => 'Failed to create club.'];
}

//Handling joining a club*****
function handleJoinClub($data) {
	$conn = connectDB();
	if(!$conn) {
		return ['success' => false, 'message' => 'Database connection failed.'];
	}
	//change variablessss
	$invite_code = $data['invite_code'];
    $user_id = $data['user_id'];

	//get invite code
	$stmt = $conn->prepare("SELECT club_id FROM book_clubs WHERE invite_code = ?");
	$stmt->bind_param("s", $invite_code);
	$stmt->execute();
	$result = $stmt->get_result();

	if ($result->num_rows === 0) {
		return ['success' => false, 'message' => 'Invalid invite code.'];
	}

	$club = $result->fetch_assoc();
	$club_id = $club['club_id'];

	//adding member - woohoo!
	$stmt = $conn->prepare("INSERT INTO club_members (club_id, user_id) VALUES (?, ?)");
	$stmt->bind_param("ii", $club_id, $user_id);

	if ($stmt->execute()) {
		echo "SUCCESS: User $user_id joined club $club_id\n"; //CHANGE VARIABLES
		return ['success' => true, 'club_id' => $club_id, 'message' => 'Joined club!'];
	}

	return ['success' => false, 'message' => 'Already a member...or error :/'];
}

//handle scheduling a meeting*****
function handleScheduleMeeting($data) {
	$conn = connectDB();
	if(!$conn) {
		return ['success' => false, 'message' => 'Database connection failed.'];
	}

	//validate fields
	if(!isset($data['club_id'], $data['scheduled_time'], $data['agenda'])) {
		return ['success' => false, 'message' => 'Missing required fields!'];
	}

	//CHANGE ACCORDING TO TARYN'S VARIABLES (ANY THAT ARE LEFT)
	$club_id = $data['club_id'];
	$event_title = $data['event_title'];
	$event_date = $data['event_date'];
	$event_time = $data['event_time'];
	$event_format = $data['event_format'];
	$created_by = $data['created_by'];

	$stmt = $conn->prepare("INSERT INTO meetings (club_id, event_title, event_date, event_time, event_format, created_by) VALUES (?, ?, ?, ?, ?, ?)");
	$stmt->bind_param("issssi", $club_id, $event_title, $event_date, $event_time, $event_format, $created_by);
	
	if ($stmt->execute()) {
		echo "SUCCESS: Meeting scheduled for club $club_id\n"; //CHANGE VARIABLES
		return ['success' => true, 'message' => 'Meeting scheduled!'];
	}
	
	return ['success' => false, 'message' => 'Failed to schedule meeting.'];
}

//handle creating a review****
function handleCreateReview($data) {
	$conn = connectDB();
	if(!$conn) {
		return ['success' => false, 'message' => 'Database connection failed.'];
	}

	//CHANGE TO TARYN'S VARIABLES
	$user_id = $data['user_id'];
	$book_id = $data['book_id'];
	$rating = $data['rating'];
	$review_text = $data['review_text'];

	$stmt = $conn->prepare("INSERT INTO reviews (user_id, book_id, rating, review_text) VALUES (?, ?, ?, ?)");
	$stmt->bind_param("iiis", $user_id, $book_id, $rating, $review_text);

	if ($stmt->execute()) {
		echo "SUCCESS: Review created for book $book_id by user $user_id\n"; //CHANGE VARIABLES
		return ['success' => true, 'message' => 'Review submitted!'];
	}

	return ['success' => false, 'message' => 'Failed to submit review.'];
}

//handle discussions*****
function handleDiscussions($data) {
	$conn = connectDB();
	if(!$conn) {
		return ['success' => false, 'message' => 'Database connection failed.'];
	}

	//CHANGE TO TARYN'S VARIABLES
	$club_id = $data['club_id'];
	$user_id = $data['user_id'];
	$message = $data['message'];

	$stmt = $conn->prepare("INSERT INTO discussions (club_id, user_id, message) VALUES (?, ?, ?)");
	$stmt->bind_param("iis", $club_id, $user_id, $message);

	if ($stmt->execute()) {
		echo "SUCCESS: Discussion message posted in club $club_id by user $user_id\n"; //CHANGE VARIABLES
		return ['success' => true, 'message' => 'Message posted!'];
	}
	return ['success' => false, 'message' => 'Failed to post discussion message.'];
}

//RMQ processing
//PROCESS MSG HAS BEEN UPDATED TO HANDLE MAIN KEYS FROM TARYN'S VARIABLES - 3/8 7:52PM ;-;
function processMessage($req) {
	echo "HELLO!!\n";
	$routing_key = $req->delivery_info['routing_key'];
	$message = json_decode($req->body, true);
	if($routing_key==='user.login') {
		$response = handleLogin($message);
	}elseif($routing_key==='user.register') {
		$response = handleRegistration($message);
	}elseif($routing_key==='group.create') { //add new route for creating club*****
		$response = handleCreateClub($message);
	}elseif($routing_key==='group.join') { //add new route for joining club*****
		$response = handleJoinClub($message);
	}elseif($routing_key==='schedule.create') { //add new route for scheduling meeting*****
		$response = handleScheduleMeeting($message);
	}elseif($routing_key==='review.create') { //add new route for creating review*****
		$response = handleCreateReview($message);
	}elseif($routing_key==='discussion.create') { //add new route for discussions*****
		$response = handleDiscussions($message);
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
//ADDED ALL QUEUE BINDS FOR NEW ROUTES - 3/8 7:53PM ;-;
$channel->queue_bind('user_events_queue', 'user_exchange', 'group.create');
$channel->queue_bind('user_events_queue', 'user_exchange', 'group.join');
$channel->queue_bind('user_events_queue', 'user_exchange', 'schedule.create');
$channel->queue_bind('user_events_queue', 'user_exchange', 'review.create');
$channel->queue_bind('user_events_queue', 'user_exchange', 'discussion.create');
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
