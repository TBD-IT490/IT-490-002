<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

//connecting to matt's rabbitmq server, running on different vm
define('RABBITMQ_HOST', '100.101.27.73');
define('RABBITMQ_PORT', 5672);
define('RABBITMQ_USER', 'broker');
define('RABBITMQ_PASS', 'test');
define('RABBITMQ_EXCHANGE', 'user_exchange');

$_DEBUG_LOG = [];

function rmq_rpc(string $action, array $payload = []): ?array {
    global $_DEBUG_LOG;
    try {
        $connection = new AMQPStreamConnection(
            RABBITMQ_HOST,
            RABBITMQ_PORT,
            RABBITMQ_USER,
            RABBITMQ_PASS
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

        $payload['user_id'] = $_SESSION['id'] ?? null;
        $payload['username'] = $_SESSION['username'] ?? null;

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
            $channel->wait(null, false, 5); 
        }

        $channel->close();
        $connection->close();

        $decoded = json_decode($response, true);
        
        $_DEBUG_LOG[] = [
            'action' => $action,
            'request' => $payload,
            'response' => $decoded,
            'raw' => $response,
        ];
        
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

$genres_response = rmq_rpc('genre.list');
$genres = $genres_response['genres'] ?? [];


$groups_response = rmq_rpc('group.list_for_user');
$my_groups = $groups_response['groups'] ?? [];

$_book_cache = [];
$_group_cache = [];


//getting books by their id in the database
function getBookById(int $id): ?array {
    global $_book_cache;
    if (isset($_book_cache[$id])) return $_book_cache[$id];
    $result = rmq_rpc('book.get', ['book_id' => $id]);
    $book = $result['book'] ?? $result ?? null;
    if ($book) $_book_cache[$id] = $book;
    return $book;
}

//getting groups by their id in the database
function getGroupById(int $id): ?array {
    global $_group_cache;
    if (isset($_group_cache[$id])) return $_group_cache[$id];
    $result = rmq_rpc('group.get', ['group_id' => $id]);
    $group = $result['group'] ?? $result ?? null;
    if ($group) $_group_cache[$id] = $group;
    return $group;
}

//filling the silly little stars
function renderStars(float $rating): string {
    $full = (int) floor($rating);
    $half = ($rating - $full) >= 0.5;
    $empty = 5 - $full - ($half ? 1 : 0);

    $out = '<span class="stars">';
    for ($i = 0; $i < $full;  $i++) $out .= '<i class="bi bi-star-fill filled"></i>';
    if ($half) $out .= '<i class="bi bi-star-half filled"></i>';
    for ($i = 0; $i < $empty; $i++) $out .= '<i class="bi bi-star"></i>';
    $out .= '</span>';

    return $out;
}