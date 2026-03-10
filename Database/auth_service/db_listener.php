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

//Function to get user_id from db
function getUserId($conn, $data) {
	//using session key
	if(isset($data['session_key'])) {
		$stmt = $conn->prepare("SELECT user_id FROM sessions WHERE session_key = ?");
		$stmt->bind_param("s", $data['session_key']);
		$stmt->execute();
		$res = $stmt->get_result()->fetch_assoc();
		if ($res) return $res['user_id'];
	}

	//using username
	if(isset($data['username'])) {
		$stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
		$stmt->bind_param("s", $data['username']);
		$stmt->execute();
		$res = $stmt->get_result()->fetch_assoc();
		if ($res) return $res['id'];
	}
	return null; //if no session key or invalid session key
}

// CREATE SEARCH FUNCTION TO GET BOOKS
function handleSearchBooks($data) {
	$conn = connectDB();
	if(!$conn) {
		return ['success' => false, 'message' => 'Database connection failed.'];
	}

	//TODO: figure out why this is empty
	$search_query = $data['search']; //CHANGED to match front end - woohoo
	//ASSOCIATE USER_ID W/O FRONT END SENDING IT IN REQ (have FE send username)
	$user_id = getUserId($conn, $data);
	if (!$user_id) {
		return ['success' => false, 'message' => 'User not authenticated (from search)!'];
	}

	$stmt = $conn->prepare("SELECT book_id, title, author, cover_url FROM books WHERE title LIKE ? OR author LIKE ?");
	$search_query = 'z'; //debug
	$like_query = '%' . $search_query . '%';
	$stmt->bind_param("ss", $like_query, $like_query);
	$stmt->execute();
	$result = $stmt->get_result();

	$books = [];
	while ($row = $result->fetch_assoc()) {
		//TODO add more rows for other stuff needed for return
		$books[] = ['book_id' => $row['book_id'], 'title' => $row['title'], 'author' => $row['author'], 'cover_url' => $row['cover_url']];
	}

	//debug search
	echo "DEBUG: Search query: '$search_query'\n" . print_r($search_query, true) . "\n";
	//echo "SUCCESS: Book search for query '$search_query', found " . count($books) . " results\n";
	echo "SUCCESS: Book search for query, found " . count($books) . " results\n";
	return ['success' => true, 'books' => $books, 'message' => 'Book search completed!'];
}

//Handling creating a club*****
function handleCreateClub($data) {
	$conn = connectDB();
	if(!$conn) {
		return ['success' => false, 'message' => 'Database connection failed.'];
	}

	//VARIABLES CHANGED - 3/8 8:16PM ;-;
	//ASSOCIATE USER_ID W/O FRONT END SENDING IT IN REQ (have FE send username)
	$user_id = getUserId($conn, $data);
	if (!$user_id) {
		return ['success' => false, 'message' => 'User not authenticated (from getUser - from create club)!'];
	}
	
	$group_name = $data['name']; 
    $description = $data['group_desc']; 
    $book = $data['book_id'] ?? null; 
    $invite_code = strtoupper(substr(md5(uniqid(rand(), true)),0,8));

	echo "DEBUG: Create Club Data: " . print_r($data, true) . "\n"; //debug
	$stmt = $conn->prepare("INSERT INTO book_clubs (club_name, group_desc, invite_code) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $group_name, $description, $invite_code);

	if ($stmt->execute()) {
		$club_id = $conn->insert_id;

		//make creator admin
		$stmt2 = $conn->prepare("INSERT INTO club_members (club_id, user_id, role) VALUES (?, ?, 'admin')");
		//$stmt2 = $conn->prepare("INSERT INTO club_members (club_id, role) VALUES (?, 'admin')"); //CHANGE TO FE'S VARIABLES (NO USER ID??)

		$stmt2->bind_param("ii", $club_id, $user_id);
		$stmt2->execute();

		echo "SUCCESS: Club created: $group_name by: $user_id\n";
		return ['success' => true, 'group_name' => $group_name, 'club_id' => $club_id, 'invite_code' => $invite_code, 'message' => 'Club created!'];
	}

	return ['success' => false, 'message' => 'Failed to create club.'];
}

//handle get all groups*****
function handleGetGroups($data) {
	$conn = connectDB();
	if(!$conn) {
		return ['success' => false, 'message' => 'Database connection failed.'];
	}
	//ASSOCIATE USER_ID W/O FRONT END SENDING IT IN REQ (have FE send username)
	$user_id = getUserId($conn, $data);
	if (!$user_id) {
		return ['success' => false, 'message' => 'User not authenticated (from getUser -- getting groups)!'];
	}

	$stmt = $conn->prepare("SELECT club_id, club_name, group_desc FROM book_clubs");
	$stmt->execute();
	$result = $stmt->get_result();
	$groups = [];
	while ($row = $result->fetch_assoc()) {
		$groups[] = ['club_id' => $row['group_id'], 'club_name' => $row['group_name'], 'group_desc' => $row['group_desc']];
	}
	echo "SUCCESS: Retrieved all groups, found " . count($groups) . " results\n";
	return ['success' => true, 'groups' => $groups, 'message' => 'Groups retrieved!'];
}

//Handling joining a club*****
function handleJoinClub($data) {
	$conn = connectDB();
	if(!$conn) {
		return ['success' => false, 'message' => 'Database connection failed.'];
	}
	//changed variables
	$invite_code = $data['invite_code'];
	
	// ASSOCIATE USER_ID W/O FE SENDING IT IN REQ (have her send session_key) ~~
	$user_id = getUserId($conn, $data);
	if (!$user_id) {
		return ['success' => false, 'message' => 'User not found (from getUser - join club)!'];
	}

	//get invite code
	$stmt = $conn->prepare("SELECT club_id FROM book_clubs WHERE invite_code = ?");
	$stmt->bind_param("s", $invite_code);
	$stmt->execute();
	$result = $stmt->get_result();

	if ($result->num_rows === 0) {
		return ['success' => false, 'message' => 'Invalid invite code.'];
	}

	// TODO getting club id from db ->but change this to group_id once db gets updated
	$club = $result->fetch_assoc();
	$club_id = $club['club_id'];

	
	//getting group name from db
	$stmt2 = $conn->prepare("SELECT club_name FROM book_clubs WHERE club_id = ?");
	$stmt2->bind_param("i", $club_id);
	$stmt2->execute();
	$result2 = $stmt2->get_result();
	$club_info = $result2->fetch_assoc();
	$group_name = $club_info['club_name'];

	//adding member - woohoo!
	$stmt = $conn->prepare("INSERT INTO club_members (club_id, user_id) VALUES (?, ?)");
	$stmt->bind_param("ii", $club_id, $user_id);

	if ($stmt->execute()) {
		echo "SUCCESS: User $user_id joined club $club_id\n"; //CHANGED VARIABLES -> once changed make it User $user_id joined club $club_id\n
		return ['success' => true, 'groups' => $group_name, 'username' => $user_id, 'club_id' => $club_id, 'message' => 'Joined club!'];
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

	//GETTING USER_ID FROM DB
	$user_id = getUserId($conn, $data);
	if (!$user_id) {
		return ['success' => false, 'message' => 'User not authenticated (from getUser - tryna schedule meeting)!]'];
	}

	//CHANGE ACCORDING TO TARYN'S VARIABLES (ANY THAT ARE LEFT)
	$club_id = $data['club_id']; //TODO make group_id once db gets updated
	$event_title = $data['event_title'];
	$event_date = $data['event_date'];
	$event_time = $data['event_time'];
	$event_format = $data['event_format'];
	$created_by = $data['created_by'];
	//added these 2 from what taryn sends, but lowkey idk where to put them ;-;
	$book = $data['book_id'];
	$notes = $data['notes'];

	//change club_id to group_id once db gets updated ~~
	$stmt = $conn->prepare("INSERT INTO meetings (club_id, event_title, event_date, event_time, event_format, created_by) VALUES (?, ?, ?, ?, ?, ?)");
	$stmt->bind_param("issssi", $club_id, $event_title, $event_date, $event_time, $event_format, $user_id);
	
	if ($stmt->execute()) {
		echo "SUCCESS: Meeting scheduled for club $club_id\n"; //CHANGE VARIABLES
		return ['success' => true, 'message' => 'Meeting scheduled!']; // TODO: add if needed, am i returning title, date, time, format, book, notes back to taryn?
	}
	
	return ['success' => false, 'message' => 'Failed to schedule meeting.'];
}

//handle creating a review****
function handleCreateReview($data) {
	$conn = connectDB();
	if(!$conn) {
		return ['success' => false, 'message' => 'Database connection failed.'];
	}

	$user_id = getUserId($conn, $data);
	if (!$user_id) {
		return ['success' => false, 'message' => 'User not logged in or authenticated (tryna create review)!]'];
	}

	//CHANGE TO TARYN'S VARIABLES
	//$user_id = $data['user_id'];
	$book_id = $data['book_id'];
	$rating = $data['rating'];
	$review_text = $data['review_text'];

	$stmt = $conn->prepare("INSERT INTO reviews (user_id, book_id, rating, review_text) VALUES (?, ?, ?, ?)");
	$stmt->bind_param("iiis", $user_id, $book_id, $rating, $review_text);

	if ($stmt->execute()) {
		echo "SUCCESS: Review created for book $book_id by user $user_id\n"; //CHANGE VARIABLES
		//TODO ADD RETURN VARIABLES THAT FRONT END NEEDS
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

	$user_id = getUserId($conn, $data);
	if (!$user_id) {
		return ['success' => false, 'message' => 'User not logged in or authenticated (tryna post discussion message)!]'];
	}

	//CHANGE TO TARYN'S VARIABLES
	$club_id = $data['club_id'];
	$user_id = $data['user_id'];
	$message = $data['message'];

	$stmt = $conn->prepare("INSERT INTO discussions (club_id, user_id, message) VALUES (?, ?, ?)");
	$stmt->bind_param("iis", $club_id, $user_id, $message);

	if ($stmt->execute()) {
		echo "SUCCESS: Discussion message posted in club $club_id by user $user_id\n"; //CHANGE VARIABLES
		//TODO add any variables front end needs
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

	}elseif($routing_key==='book.list') { //add new route for book search*****
		$response = handleSearchBooks($message);

	}elseif($routing_key==='group.list') { //add new route for list all groups*****
		$response = handleGetGroups($message);

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
$channel->queue_bind('user_events_queue', 'user_exchange', 'book.list');
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
