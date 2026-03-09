<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

// ── RABBITMQ CONFIGURATION ───────────────────────────────────
define('RABBITMQ_HOST',     '100.101.27.73');
define('RABBITMQ_PORT',     5672);
define('RABBITMQ_USER',     'broker');
define('RABBITMQ_PASS',     'test');
define('RABBITMQ_EXCHANGE', 'user_exchange');

// ── DEBUG LOG ─────────────────────────────────────────────────
// Stores all RPC calls and responses for console debugging
$_DEBUG_LOG = [];

// ── RPC HELPER ────────────────────────────────────────────────
/**
 * Send a message to RabbitMQ and wait for a reply.
 *
 * $action    — the routing key, e.g. 'book.get', 'group.list'
 * $payload   — associative array, will be JSON-encoded
 *
 * Returns the decoded response array, or null on failure.
 */
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

        // Exclusive auto-delete callback queue for this request
        list($callback_queue,,) = $channel->queue_declare('', false, false, true, false);

        $response = null;
        $corr_id  = uniqid();
        $onResponse = function($msg) use($corr_id, &$response) {
            if ($msg->get('correlation_id') === $corr_id) {
                $response = $msg->getBody();
            }
        };
        $channel->basic_consume($callback_queue, '', false, true, false, false, $onResponse);

        // Add session info to payload
        $payload['user_id']  = $_SESSION['id'] ?? null;
        $payload['username'] = $_SESSION['username'] ?? null;

        $msg = new AMQPMessage(
            json_encode($payload),
            [
                'delivery_mode'  => 2,
                'correlation_id' => $corr_id,
                'reply_to'       => $callback_queue,
            ]
        );

        $channel->basic_publish($msg, 'user_exchange', $action);

        // Wait for reply with timeout
        while ($response === null) {
            $channel->wait(null, false, 5); // 5 second timeout
        }

        $channel->close();
        $connection->close();

        $decoded = json_decode($response, true);
        
        // Log the RPC call for debugging
        $_DEBUG_LOG[] = [
            'action'   => $action,
            'request'  => $payload,
            'response' => $decoded,
            'raw'      => $response,
        ];
        
        return $decoded;

    } catch (\Exception $e) {
        error_log("rmq_rpc error for '$action': " . $e->getMessage());
        $_DEBUG_LOG[] = [
            'action'  => $action,
            'request' => $payload,
            'error'   => $e->getMessage(),
        ];
        return null;
    }
}

// ── GLOBAL DATA ───────────────────────────────────────────────
// Fetched once per page load, used across multiple pages.

// All genres for filter dropdowns
// RabbitMQ action: 'genre.list'
// Expected response: { "genres": ["Literary Fiction", "Mystery", ...] }

//$genres_response = rmq_rpc('genre.list');
//$genres = $genres_response['genres'] ?? [];

// Current user's groups — used in nav, schedule filter, recommendations
// RabbitMQ action: 'group.list_for_user'
// Expected response: { "groups": [{ id, name, description, member_count, current_book_id, invite_code }, ...] }

//$groups_response = rmq_rpc('group.list_for_user');
$my_groups = $groups_response['groups'] ?? [];

// ── IN-MEMORY CACHE ───────────────────────────────────────────
// Prevents the same book/group being fetched twice in one page load.
$_book_cache  = [];
$_group_cache = [];

/**
 * Fetch a single book by ID, with caching.
 * RabbitMQ action: 'book.get'
 * Expected response: { id, title, author, cover, genre[], year, pages, rating, reviews, description, isbn }
 */
function getBookById(int $id): ?array {
    global $_book_cache;
    if (isset($_book_cache[$id])) return $_book_cache[$id];
    $result = rmq_rpc('book.get', ['book_id' => $id]);
    $book   = $result['book'] ?? $result ?? null;
    if ($book) $_book_cache[$id] = $book;
    return $book;
}

/**
 * Fetch a single group by ID, with caching.
 * RabbitMQ action: 'group.get'
 * Expected response: { id, name, description, members[], member_count, current_book_id, invite_code, created }
 */
function getGroupById(int $id): ?array {
    global $_group_cache;
    if (isset($_group_cache[$id])) return $_group_cache[$id];
    $result = rmq_rpc('group.get', ['group_id' => $id]);
    $group  = $result['group'] ?? $result ?? null;
    if ($group) $_group_cache[$id] = $group;
    return $group;
}

// ── RENDER HELPERS ────────────────────────────────────────────

function renderStars(float $rating): string {
    $full  = (int) floor($rating);
    $half  = ($rating - $full) >= 0.5;
    $empty = 5 - $full - ($half ? 1 : 0);

    $out = '<span class="stars">';
    for ($i = 0; $i < $full;  $i++) $out .= '<i class="bi bi-star-fill filled"></i>';
    if ($half)                       $out .= '<i class="bi bi-star-half filled"></i>';
    for ($i = 0; $i < $empty; $i++) $out .= '<i class="bi bi-star"></i>';
    $out .= '</span>';

    return $out;
}