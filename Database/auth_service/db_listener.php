#!/usr/bin/php
<?php

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
define('DB_HOST', '100.112.153.128'); //my ts ip
define('DB_USER', 'app_user');
define('DB_PASS', 'AppUsrPwd123!'); 
define('DB_NAME', 'noetic');
$log = new Logger('Noetic-Database-Listener');
$log->pushHandler(new StreamHandler(__DIR__ .'noetic-database.log', Logger::DEBUG));
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

//handle user reg
function handleRegistration($data) {
	global $log;

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

	if(!isset($data['password_hash'])){ //prevent crash error from a null pass
		return ['success'=>false, 'message'=>'Internal ERROR: Password data missing!'];
	}
	//$pass_hash = password_hash($data['password_hash'], PASSWORD_DEFAULT); 
	$password = $data['password_hash'];

	$stmt = $conn->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
	$stmt->bind_param("sss", $username, $email, $password);
	if ($stmt->execute()) {
		$log->info('SUCCESS: Registered:'. $username);
		$response = ['success' => true, 'message' => 'user created, registration success!'];
	} else {
		$log->warning('FAILED: Registration error'. $stmt->error);
		$response = ['success' => false, 'message' => 'user already exists!'];
	}
	$stmt->close();
	$conn->close();
	return $response;
}

//handle login and session
function handleLogin($data) {
	global $log;

	//make sure it's not null
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
		$log->warning(message: "Invalid login for". $username);
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

	$log->info(message:"Login successful ". $username);
	return ['success' => true, 'session_key' => $sessionKey, 'username' => $username, 'message' => 'Logged in!'];
}

//Function to get user_id from db
function getUserId($conn, $data) {
	global $log;
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
	global $log;
	$conn = connectDB();
	if(!$conn) {
		return ['success' => false, 'message' => 'Database connection failed.'];
	}
	/*
	if(!isset($data['search'])) {
		$log->warning("FAILED: no 'search' key.");
		return ['success' => false, 'message' => 'No search given!'];
	} */
	//why warning T^T
	$search = $data['search'] ?? ''; //CHANGED to match front end - woohoo
	//user from db
	$user_id = getUserId($conn, $data);
	if (!$user_id) {
		return ['success' => false, 'message' => 'User not authenticated (from search)!'];
	}
	while (true) {
	$stmt = $conn->prepare("SELECT book_id, title, author, cover_url FROM books WHERE title LIKE ? OR author LIKE ?");
	$like_query = '%' . $search . '%';
	$stmt->bind_param("ss", $like_query, $like_query);
	$stmt->execute();
	$result = $stmt->get_result();

	if ($result->num_rows === 0) { 
		$res = rmq_rpc('api.on_demand',["search_query" => $like_query]);
		if ($res["success"] === true) {
			
		}
		elseif ($res["success"] === false) {
			$log->debug("something went wrong");
			break;
		}

	}
		break;
	}
	$books = [];
	while ($row = $result->fetch_assoc()) {
		//TODO add more rows for other stuff needed for return
		$books[] = ['book_id' => $row['book_id'], 'title' => $row['title'], 'author' => $row['author'], 'cover_url' => $row['cover_url']];
	}
	
	//debug search
	$log->info("SUCCESS: Book search for query,".$search ." found ". count($books) ." results");
	return ['success' => true, 'books' => $books, 'message' => 'Book search completed!'];
}

//book.get - gets a single book by id and returns full details
function handleGetBook($data) {
	global $log;
	$conn = connectDB();
	if(!$conn) {
		return ['success' => false, 'message' => 'Database connection failed.'];
	}
	//ASSOCIATE USER_ID W/O FRONT END SENDING IT IN REQ (have FE send username)
	$user_id = getUserId($conn, $data);
	if (!$user_id) {
		return ['success' => false, 'message' => 'User not authenticated (from get book)!'];
	}
	$book_id = $data['book_id']; 
	
	$stmt = $conn->prepare("SELECT * FROM books WHERE book_id = ?");
	$stmt->bind_param("i", $book_id);
	$stmt->execute();
	$result = $stmt->get_result();

	if ($result->num_rows === 0) {
		return ['success' => false, 'message' => 'Book not found.'];
	}

	$book = $result->fetch_assoc();
	$log->info("SUCCESS: Retrieved details for book id $book_id");
	return ['success' => true, 'book' => $book, 'message' => 'Book details retrieved!'];
}

//group.books - gets all of the books associated with a certain group_id
function handleGetGroupBooks($data) {
	global $log;
	$conn = connectDB();
	if(!$conn) {
		return ['success' => false, 'message' => 'Database connection failed.'];
	}
	//ASSOCIATE USER_ID W/O FRONT END SENDING IT IN REQ (have FE send username)
	$user_id = getUserId($conn, $data);
	if (!$user_id) {
		return ['success' => false, 'message' => 'User not authenticated (from get group books)!'];
	}

	$group_id = $data['group_id'];

	$stmt = $conn->prepare("SELECT b.book_id, b.title, b.author FROM books b JOIN club_books cb ON b.book_id = cb.book_id WHERE cb.club_id = ?");
	$stmt->bind_param("i", $group_id);
	$stmt->execute();
	$result = $stmt->get_result();

	$books = [];
	while ($row = $result->fetch_assoc()) {
		$books[] = ['book_id' => $row['book_id'], 'title' => $row['title'], 'author' => $row['author']];
	}

	$log->info("SUCCESS: Retrieved " . count($books) . " books for group id: $group_id");
	return ['success' => true, 'books' => $books, 'message' => 'Group books retrieved!'];
}

//Handling creating a club*****
function handleCreateClub($data) {
	global $log;
	$conn = connectDB();
	if(!$conn) {
		return ['success' => false, 'message' => 'Database connection failed.'];
	}
	//get user from db
	$user_id = getUserId($conn, $data);
	if (!$user_id) {
		return ['success' => false, 'message' => 'User not authenticated (from create club)!'];
	}
	//$log->info("DEBUG: user id is: $user_id"); //faah

	$group_name = $data['name']; 
    $description = $data['group_desc']; 
    $book = $data['book_id'] ?? null; 
    $invite_code = strtoupper(substr(md5(uniqid(rand(), true)),0,8));
	$stmt = $conn->prepare("INSERT INTO book_clubs (club_name, group_desc, invite_code, created_by) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssi", $group_name, $description, $invite_code, $user_id);

	if ($stmt->execute()) {
		$club_id = $conn->insert_id;

		//make creator admin
		$stmt2 = $conn->prepare("INSERT INTO club_members (club_id, user_id, role) VALUES (?, ?, 'admin')");
		$stmt2->bind_param("ii", $club_id, $user_id);
		$stmt2->execute();

		$log->info("SUCCESS: Club created: $group_name by: $user_id\n");
		return ['success' => true, 'group_name' => $group_name, 'group_id' => $club_id, 'invite_code' => $invite_code, 'message' => 'Club created!'];
	}

	return ['success' => false, 'message' => 'Failed to create club.'];
}

//getting singleee group*****
function handleGetGroup($data) {
	global $log;
	$conn = connectDB();
	if(!$conn) {
		return ['success' => false, 'message' => 'Database connection failed.'];
	}
	//user from db
	$user_id = getUserId($conn, $data);
	if (!$user_id) {
		return ['success' => false, 'message' => 'User not authenticated (getting groups)!'];
	}

	$group_id = $data['group_id'];

	$stmt = $conn->prepare("SELECT club_id, club_name, group_desc, invite_code FROM book_clubs WHERE club_id = ?");
	$stmt->bind_param("i", $group_id);
	$stmt->execute();
	$result = $stmt->get_result();

	$group = [];
	if ($row = $result->fetch_assoc()) {
		$group[] = ['group_id' => $row['club_id'], 'group_name' => $row['club_name'], 'group_desc' => $row['group_desc'], 'invite_code' => $row['invite_code']];
		$log->info("SUCCESS: Group found for group: $group_id");
		return ['success' => true, 'groups' => $group, 'message' => 'Group found!'];
	} 
	$log->warning("FAILED: Group_id: $group_id not found.");
	return ['success' => false, 'message' => 'Group not found.'];
}

//get ALL groups
function handleListGroups($data) {
	global $log;
	$conn = connectDB();
	if(!$conn) {
		return ['success' => false, 'message' => 'Database connection failed.'];
	}
	//user from db
	$user_id = getUserId($conn, $data);
	if (!$user_id) {
		return ['success' => false, 'message' => 'User not authenticated (getting groups)!'];
	}
	//$username = $data['username']; //trying it out
	//-_-
	$stmt = $conn->prepare("SELECT c.club_id, c.club_name, c.group_desc FROM book_clubs c JOIN club_members cm ON c.club_id = cm.club_id WHERE cm.user_id = ?");
	//SELECT club_id, club_name, group_desc FROM book_clubs WHERE created_by = ?
	//SELECT c.club_id, c.club_name, c.group_desc FROM book_clubs c JOIN club_members cm ON c.club_id = cm.club_id WHERE cm.user_id = ?
	$stmt->bind_param("i", $user_id);
	$stmt->execute();
	$result = $stmt->get_result();

	$groups = [];
	while ($row = $result->fetch_assoc()) {
		$groups[] = ['group_id' => $row['club_id'], 'group_name' => $row['club_name'], 'group_desc' => $row['group_desc']];
	}
	$log->info("SUCCESS: Retrieved all groups, found " . count($groups) . " results");
	return ['success' => true, 'groups' => $groups, 'message' => 'Groups retrieved!'];

	if (empty($groups)) {
		$log->warning("FAILED: Groups not found.");
		return ['success' => false, 'message' => 'Groups not found.'];
	}
	
}

//joining a club*****
function handleJoinClub($data) {
	global $log;
	$conn = connectDB();
	if(!$conn) {
		return ['success' => false, 'message' => 'Database connection failed.'];
	}
	// ASSOCIATE USER_ID W/O FE SENDING IT IN REQ (have her send username) ~~
	$user_id = getUserId($conn, $data);
	if (!$user_id) {
		return ['success' => false, 'message' => 'User not found (from getUser - join club)!'];
	}

	$invite_code = $data['invite_code'];

	//get invite code from db
	$stmt = $conn->prepare("SELECT club_id FROM book_clubs WHERE invite_code = ?");
	$stmt->bind_param("s", $invite_code);
	$stmt->execute();
	$result = $stmt->get_result();

	if ($result->num_rows === 0) {
		return ['success' => false, 'message' => 'Invalid invite code.'];
	}

	$club = $result->fetch_assoc();
	$club_id = $club['club_id']; //from db

	
	//getting group name from db
	$stmt2 = $conn->prepare("SELECT club_name FROM book_clubs WHERE club_id = ?");
	$stmt2->bind_param("i", $club_id);
	$stmt2->execute();
	$result2 = $stmt2->get_result();
	$club_info = $result2->fetch_assoc();
	$group_name = $club_info['club_name']; //from db

	//adding member - woohoo!
	$stmt = $conn->prepare("INSERT INTO club_members (club_id, user_id) VALUES (?, ?)");
	$stmt->bind_param("ii", $club_id, $user_id);

	if ($stmt->execute()) {
		
		$log->info("SUCCESS: User $user_id joined club $club_id");
		return ['success' => true, 'group_name' => $group_name, 'username' => $user_id, 'group_id' => $club_id, 'message' => 'Joined club!'];
	}

	return ['success' => false, 'message' => 'Already a member...or error :/'];
}

//scheduling a meeting*****
function handleScheduleMeeting($data) {
	global $log;
	$conn = connectDB();
	if(!$conn) {
		return ['success' => false, 'message' => 'Database connection failed.'];
	}/*
	//validate fields
	if(!isset($data['club_id'], $data['event_title'], $data['event_date'], $data['event_time'], $data['event_format'], $data['notes'])) {
		return ['success' => false, 'message' => 'Missing required fields!'];
	}*/
	//user from db
	$user_id = getUserId($conn, $data);
	if (!$user_id) {
		return ['success' => false, 'message' => 'User not authenticated (tryna schedule meeting)!]'];
	}

	//var from front end
	$club_id = $data['club_id']; 
	$event_title = $data['event_title'];
	$event_date = $data['event_date'];
	$event_time = $data['event_time'];
	$event_format = $data['event_format'];
	$created_by = $data['created_by'];
	$book = $data['book_id'];
	$notes = $data['notes'];

	//change club_id to group_id once db gets updated ~~
	$stmt = $conn->prepare("INSERT INTO club_meetings (club_id, event_title, event_date, event_time, event_format, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
	$stmt->bind_param("isssssi", $club_id, $event_title, $event_date, $event_time, $event_format, $notes, $user_id);
	
	if ($stmt->execute()) {
		$log->info("SUCCESS: Meeting scheduled for club $club_id");
		return ['success' => true, 'message' => 'Meeting scheduled!'];
	}
	
	return ['success' => false, 'message' => 'Failed to schedule meeting.'];
}

//geting all meetings for a club_id, returning arraay of id, book_id, title, date, time, format, notes
function handleScheduleList($data) {
	global $log;
	$conn = connectDB();
	if(!$conn) {
		return ['success' => false, 'message' => 'Database connection failed.'];
	}
	if(!isset($data['club_id'])) {
		return ['success' => false, 'message' => 'Missing required fields!'];
	}
	//useer from db
	$user_id = getUserId($conn, $data);
	if (!$user_id) {
		return ['success' => false, 'message' => 'User not authenticated (from getUser - tryna list meetings)!]'];
	}

	$club_id = $data['club_id']; //TODO change to group_id once db gets updated

	$stmt = $conn->prepare("SELECT meeting_id, book_id, event_title, event_date, event_time, event_format, notes FROM club_meetings WHERE club_id = ?");
	$stmt->bind_param("i", $club_id);
	$stmt->execute();
	$result = $stmt->get_result();

	$meetings = [];
	while ($row = $result->fetch_assoc()) {
		$meetings[] = [
			'meeting_id' => $row['meeting_id'],
			'book_id' => $row['book_id'],
			'title' => $row['event_title'],
			'date' => $row['event_date'],
			'time' => $row['event_time'],
			'format' => $row['event_format'],
			'notes' => $row['notes']
		];
	}
	$log->info("SUCCESS: Retrieved " . count($meetings) . " meetings for club id: $club_id");
	return ['success' => true, 'meetings' => $meetings, 'message' => 'Meetings retrieved!'];
}

//creating a review*****
function handleCreateReview($data) {
	global $log;
	$conn = connectDB();
	if(!$conn) {
		return ['success' => false, 'message' => 'Database connection failed.'];
	}
	//user from db
	$user_id = getUserId($conn, $data);
	if (!$user_id) {
		return ['success' => false, 'message' => 'User not authenticated (tryna create review)!]'];
	}

	$book_id = $data['book_id'];
	$rating = $data['rating'];
	$review_text = $data['review_text'];

	$stmt = $conn->prepare("INSERT INTO book_reviews (user_id, book_id, rating, review_text) VALUES (?, ?, ?, ?)");
	$stmt->bind_param("iiis", $user_id, $book_id, $rating, $review_text);

	if ($stmt->execute()) {
		$log->info("SUCCESS: Review created for book $book_id by user $user_id");
		return ['success' => true, 'message' => 'Review submitted!'];
	}

	return ['success' => false, 'message' => 'Failed to submit review.'];
}

//getting all reviews from a single book based on book_id returning user, rating, review_text, created
function handleReviewList($data) {
	global $log;
	$conn = connectDB();
	if(!$conn) {
		return ['success' => false, 'message' => 'Database connection failed.'];
	}
	//validate fields
	if(!isset($data['book_id'])) {
		return ['success' => false, 'message' => 'Missing required fields!'];
	}
	//user from db
	$user_id = getUserId($conn, $data);
	if (!$user_id) {
		return ['success' => false, 'message' => 'User not authenticated (tryna list reviews)!]'];
	}

	$book_id = $data['book_id'];

	$stmt = $conn->prepare("SELECT r.rating, r.review_text, r.created_at, u.username FROM book_reviews r JOIN users u ON r.user_id = u.id WHERE r.book_id = ?");
	$stmt->bind_param("i", $book_id);
	$stmt->execute();
	$result = $stmt->get_result();

	$reviews = [];
	while ($row = $result->fetch_assoc()) {
		$reviews[] = [
			'rating' => $row['rating'],
			'review_text' => $row['review_text'],
			'created_at' => $row['created_at'],
			'username' => $row['username']
		];
	}

	$log->info("SUCCESS: Retrieved " . count($reviews) . " reviews for book id: $book_id");
	return ['success' => true, 'reviews' => $reviews, 'message' => 'Reviews retrieved!'];
}

//FAAHH suggestion.create based on group_id, book_id, and sug_note
function handleCreateSuggestion($data) {
	global $log;
	$conn = connectDB();
	if(!$conn) {
		return ['success' => false, 'message' => 'Database connection failed.'];
	}
	//user from db
	$user_id = getUserId($conn, $data);
	if (!$user_id) {
		return ['success' => false, 'message' => 'User not logged in or authenticated (creating book sug)!]'];
	}

	$group_id = $data['group_id'];
	$book_id = $data['book_id'];
	$sug_note = $data['note'];

	$stmt = $conn->prepare("INSERT INTO book_suggestions (group_id, book_id, sug_note) VALUES (?, ?, ?)");
	$stmt->bind_param("iis", $group_id, $book_id, $sug_note);

	if ($stmt->execute()) {
		$log->info("SUCCESS: Book suggestion sent by user $user_id");
		return ['success' => true, 'message' => 'Book suggestion created!'];
	}
	return ['success' => false, 'message' => 'Failed to create suggestion.'];
}

//HHAARR recommendation.personal
function handlePersonalBookRecs($data) {
	global $log;
	$conn = connectDB();
	if(!$conn) {
		return ['success' => false, 'message' => 'Database connection failed.'];
	}
	//useer from db
	$user_id = getUserId($conn, $data);
	if (!$user_id) {
		return ['success' => false, 'message' => 'User not authenticated (from getUser - tryna list meetings)!]'];
	}

	$club_id = $data['group_id'];
	$book_id = $data['book_id'];
	$note = $data['note'];

	$genre_score = [];
	$stmt = $conn->prepare("SELECT b.genre, COUNT(*) as score FROM book_reviews r JOIN books b ON r.book_id = b.book_id WHERE r.user_id = ? GROUP BY b.genre");
	$stmt->bind_param("i", $user_id);

	return ['success' => true, 'message' => 'Book rec here!'];
}

function handleGroupBookRecs($data) { //faaaahh
	global $log;
	$conn = connectDB();
	if(!$conn) {
		return ['success' => false, 'message' => 'Database connection failed.'];
	}
	//useer from db
	$user_id = getUserId($conn, $data);
	if (!$user_id) {
		return ['success' => false, 'message' => 'User not authenticated (from getUser - tryna list meetings)!]'];
	}

	$club_id = $data['group_id'];
	$book_id = $data['book_id'];
	$note = $data['note'];

	$genre_score = [];
	$stmt = $conn->prepare("SELECT b.genre, COUNT(*) as score FROM book_reviews r JOIN books b ON r.book_id = b.book_id WHERE r.user_id = ? GROUP BY b.genre");
	$stmt->bind_param("i", $user_id);

	return ['success' => true, 'message' => 'Book rec here!'];
}

//posting discussions*****
function handleDiscussions($data) {
	global $log;
	$conn = connectDB();
	if(!$conn) {
		return ['success' => false, 'message' => 'Database connection failed.'];
	}
	//user from db
	$user_id = getUserId($conn, $data);
	if (!$user_id) {
		return ['success' => false, 'message' => 'User not logged in or authenticated (tryna post discussion message)!]'];
	}

	//from front end
	$club_id = $data['group_id']; 
	//$user_id = $data['user_id'];
	$message = $data['disc_message']; //changed tryna match front end

	//change variable u need to insert into db the club_book_id, user_id, and post_text
	$stmt = $conn->prepare("INSERT INTO discussions (club_book_id, user_id, message) VALUES (?, ?, ?)");
	$stmt->bind_param("iis", $club_id, $user_id, $message);

	if ($stmt->execute()) {
		$log->info("SUCCESS: Discussion message posted in club $club_id by user $user_id");
		return ['success' => true, 'message' => 'Message posted!']; //return stuff
	}
	return ['success' => false, 'message' => 'Failed to post discussion message.'];
}

//discussion.reply - inserts a reply to the discussion by discussion_id
function handleDiscussionReply($data) {
	global $log;
	$conn = connectDB();
	if(!$conn) {
		return ['success' => false, 'message' => 'Database connection failed.'];
	}
	//user from db
	$user_id = getUserId($conn, $data);
	if (!$user_id) {
		return ['success' => false, 'message' => 'User not logged in or authenticated (tryna post discussion reply)!]'];
	}

	//from front end
	$discussion_id = $data['discussion_id'];
	//$user_id = $data['user_id'];
	$message = $data['message'];

	$stmt = $conn->prepare("INSERT INTO discussion_replies (discussion_id, user_id, message) VALUES (?, ?, ?)");
	$stmt->bind_param("iis", $discussion_id, $user_id, $message);
	if ($stmt->execute()) {
		$log->info("SUCCESS: Discussion reply posted for discussion $discussion_id by user $user_id");
		return ['success' => true, 'message' => 'Reply posted!'];
	}
	return ['success' => false, 'message' => 'Failed to post discussion reply.'];
}

//discussion.list - gets all discussions for group_id returns id, author, content, created, replies
function handleDiscussionList($data) {
	global $log;
	$conn = connectDB();
	if(!$conn) {
		return ['success' => false, 'message' => 'Database connection failed.'];
	}
	if(!isset($data['club_id'])) {
		return ['success' => false, 'message' => 'Missing required fields!'];
	}
	//user from db
	$user_id = getUserId($conn, $data);
	if (!$user_id) {
		return ['success' => false, 'message' => 'User not authenticated (tryna list discussions)!]'];
	}

	$club_id = $data['club_id'];

	$stmt = $conn->prepare("SELECT d.discussion_id, d.post_text AS discussion_message, d.created_at AS discussion_created, u.user_id FROM discussions d JOIN users u ON d.user_id = u.id WHERE d.club_id = ?");
	$stmt->bind_param("i", $club_id);
	$stmt->execute();
	$result = $stmt->get_result();

	$discussions = [];
	while ($row = $result->fetch_assoc()) {
		$discussions[] = [
			'discussion_id' => $row['discussion_id'],
			'message' => $row['discussion_message'],
			'created_at' => $row['discussion_created'],
			'username' => $row['username']
		];
	}
	$log->info("SUCCESS: Retrieved " . count($discussions) . " discussions for club id: $club_id");
	return ['success' => true, 'discussions' => $discussions, 'message' => 'Discussions retrieved!'];
}

function handleBookCache($data) {
	global $log;

	$conn = connectDB();
	if(!$conn) {
		return ['success' => false, 'message' => 'Database connection failed.'];
	}
	$api_book_id = $data['api_book_id'];
	$isbn = $data['isbn'];
	$title = $data['title'];
	$subtitle = $data['subtitle'];
	$author = $data['author'];
	$description = $data['description'];
	$cover_url = $data['cover_url'];
	$publisher = $data['publisher'];
	$published_year = $data['published_year'];
	$genre = $data['genre'];
	$maturity_rating = $data['maturity_rating'];
	$content_version = $data['content_version'];
	$pages = $data['pages'];

	$stmt = $conn->prepare(query: "INSERT INTO books (
    isbn,
    title,
    author,
    description,
    cover_url,
    api_book_id,
    subtitle,
    publisher,
    published_year,
    genre,
    maturity_rating,
    content_version,
	pages
	) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
	$stmt->bind_param("ssssssssssssi", $isbn, $title, $author, $description, $cover_url, $api_book_id, $subtitle, $publisher, $published_year, $genre, $maturity_rating, $content_version, $pages);
	try {
		if ($stmt->execute()) {
			$log->info("SUCCESS: Books have been cached");
			return ['success' => true, 'message' => 'Books have been cached!'];
		}
	} catch (mysqli_sql_exception $e) { //fix
		// Handle duplicate entry error (error code 1062)
		if ($e->getCode() == 1062) {
			$log->warning("INFO: Book with API ID $api_book_id already exists in cache");
			return ['success' => true, 'message' => 'Book already cached.'];
		} else {
			$log->error("ERROR: Failed to cache book: " . $e->getMessage() . "\n");
			return ['success' => false, 'message' => 'Failed to cache book due to database error.'];
		}
	}
	
	return ['success' => false, 'message' => 'Failed to cache books.'];
}

// based off the one nat showed me from stack, whyy :(
function recommendBooks($data)  {
	global $log;
	$conn = connectDB();
	if(!$conn) {
		return ['success' => false, 'message' => 'Database connection failed.'];
	}
	$user_id = getUserId($conn, $data);
	$stmt = $conn->prepare(query:'Select book_id from book_reviews where user_id = ? AND rating >= 4');
	$stmt->bind_param('i', $user_id);
	$stmt->execute();
	$result = $stmt->get_result();

	$liked_books = [];
	while ($row = $result->fetch_assoc()) { 
		$liked_books = $row['book_id'];
	}	
	if (count($liked_books) == 0) {

		// give them everything cause they lame and have no opinion 
		// log this someone pls
		return ['success'=> true, 'message'=> 'giving you everything'];
	}
	$placeholders = implode(',', array_fill(0, count($liked_books), '?'));
	$stmt = $conn->prepare(query:"SELECT DISTINCT user_id
        FROM book_reviews
        WHERE book_id IN ($placeholders) AND rating >= 4
        AND user_id != ?");
	$types = str_repeat('i', count($liked_books)) . 'i';
	$others = array_merge($liked_books, [$user_id]);
	// its like rust
	$stmt->bind_param($types, ...$others);
	$stmt->execute();
    $result = $stmt->get_result();
	$others = [];
	while ($row = $result->fetch_assoc()) {
		$others[] = $row['user_id'];
	}
	if (count($others) == 0) {
        // give them everything cause they lame and have no opinion 
		// log this someone pls
		return ['success'=> true, 'message'=> 'giving you everything'];
    }
	$placeholders = implode(',', array_fill(0, count($others), '?'));
	$types = str_repeat('i', count($others)) . 'i';
	$stmt = $conn->prepare("SELECT books.book_id,
        FROM book_reviews
        JOIN books ON book_reviews.book_id = books.book_id
        WHERE book_reviews.user_id IN ($placeholders)
        AND book_reviews.rating >= 4
        AND books.book_id NOT IN (
            SELECT book_id FROM book_reviews WHERE user_id = ?
        )
        GROUP BY books.books_id
        LIMIT 10");
		$stmt->bind_param($types, ...$others);
	$stmt->execute();
    $result = $stmt->get_result();
 	$recommendations = [];

    while ($row = $result->fetch_assoc()) {
		$recommendations[] = $row["book_id"];
    }
	$stmt = $conn->prepare("SELECT book_id, title, author, cover_url FROM books WHERE title LIKE ? OR author LIKE ?");
	$stmt->bind_param("ss", $like_query, $like_query);
	$stmt->execute();
	$result = $stmt->get_result();


	return ["success"=> true,"message"=> "all good twin heres the books", "recommendations" => $recommendations];

}
//RMQ processing
function processMessage($req) {
	global $log;
	$routing_key = $req->delivery_info['routing_key'];
	$message = json_decode($req->body, true);
	if($routing_key==='user.login') {
		$response = handleLogin($message);

	}elseif($routing_key==='user.register') {
		$response = handleRegistration($message);

	}elseif($routing_key==='book.list') { //book search
		$response = handleSearchBooks($message);

	}elseif($routing_key==='book.get') { //getting single book
		$response = handleGetBook($message);

	}elseif($routing_key==='group.books') { //getting books for a group
		$response = handleGetGroupBooks($message);

	}elseif($routing_key==='group.get') { //list all groups
		$response = handleGetGroup($message);

	}elseif($routing_key==='group.create') { //creating club
		$response = handleCreateClub($message);

	}elseif($routing_key==='group.join') { //joining club
		$response = handleJoinClub($message);

	}elseif($routing_key==='group.list') {
		$response = handleListGroups($message); //adding to fix front end popups rahhh
	
		}elseif($routing_key==='schedule.create') { //scheduling meeting
		$response = handleScheduleMeeting($message);

	}elseif($routing_key==='schedule.list') { //TODO add new route for pulling up scheduled meetings*****
		//$response = handleScheduleList($message);
	
	}elseif($routing_key==='suggestion.create') { //creating book suggestion
		$response = handleCreateSuggestion($message);

	}elseif($routing_key==='recommendation.personal') { //creating personal rec - FAAAHHH
		$response = handlePersonalBookRecs($message);

	}elseif($routing_key==='recommendation.groups') { //creating group rec - FAAHHH
		$response = handleGroupBookRecs($message);

	}elseif($routing_key==='review.create') { //creating review
		$response = handleCreateReview($message);

	}elseif($routing_key==='review.list') { //pulling up the reviews
		$response = handleReviewList($message);
	
	}elseif($routing_key==='discussion.create') { //creating discussion
		$response = handleDiscussions($message);

	}elseif($routing_key==='discussion.list') { //listing discussions
		$response = handleDiscussionList($message);
	
	}elseif($routing_key==='discussion.reply') { //replying to discussions
		$response = handleDiscussionReply($message);

	}elseif($routing_key === 'explore.all'){
        $response = recommendBooks($message);
    }elseif($routing_key==='api.cache') {
		$response = handleBookCache($message);
	} else {
		$log->error('SOMEONE FORGOT ROUTING KEY >:( ' . $routing_key ."");
	}

	//sending reply back
	$reply_msg = new AMQPMessage(json_encode($response), ['correlation_id' => $req->get('correlation_id')]);	
	$req->getChannel()->basic_publish($reply_msg, '', $req->get('reply_to'));
	$log->info("".  $response['message'] . "");
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
$channel->queue_bind('user_events_queue', 'user_exchange', 'book.list');
$channel->queue_bind('user_events_queue', 'user_exchange', 'book.get');
$channel->queue_bind('user_events_queue', 'user_exchange', 'group.books');
$channel->queue_bind('user_events_queue', 'user_exchange', 'group.get');
$channel->queue_bind('user_events_queue', 'user_exchange', 'group.list'); //may or may not need ¯\_(ツ)_/¯
$channel->queue_bind('user_events_queue', 'user_exchange', 'group.create');
$channel->queue_bind('user_events_queue', 'user_exchange', 'group.join');
$channel->queue_bind('user_events_queue', 'user_exchange', 'schedule.create');
//$channel->queue_bind('user_events_queue', 'user_exchange', 'schedule.list'); //TODO once review and discussions has been tested
$channel->queue_bind('user_events_queue', 'user_exchange', 'suggestion.create');
$channel->queue_bind('user_events_queue', 'user_exchange', 'recommendation.personal');
$channel->queue_bind('user_events_queue', 'user_exchange', 'recommendation.groups');
$channel->queue_bind('user_events_queue', 'user_exchange', 'review.create');
$channel->queue_bind('user_events_queue', 'user_exchange', 'review.list'); 
$channel->queue_bind('user_events_queue', 'user_exchange', 'discussion.create');
$channel->queue_bind('user_events_queue', 'user_exchange', 'discussion.list'); 
$channel->queue_bind('user_events_queue', 'user_exchange', 'discussion.reply'); 
$channel->queue_bind('user_events_queue', 'user_exchange', 'explore.all'); //for personal book recs
$channel->queue_bind('user_events_queue', 'user_exchange', 'api.cache');
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