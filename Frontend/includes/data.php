<?php
// ── API CONFIGURATION ────────────────────────────────────────
// Set your API base URL here. All requests go through api_get(), api_post(), api_put().

define('API_BASE', 'https://your-api-base-url.com/api'); // ← CHANGE THIS

// ── HTTP HELPERS ─────────────────────────────────────────────

/**
 * GET request to the API.
 * Returns decoded array on success, or null on failure.
 */
function api_get(string $endpoint): ?array {
    $token = $_SESSION['api_token'] ?? '';
    $url   = API_BASE . '/' . ltrim($endpoint, '/');

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $token,
        ],
    ]);

    $res  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($res === false || $code >= 400) {
        error_log("API GET $endpoint failed with HTTP $code");
        return null;
    }

    return json_decode($res, true);
}

/**
 * POST request to the API.
 * Returns decoded array on success, or ['success' => false] on failure.
 */
function api_post(string $endpoint, array $data = []): array {
    $token = $_SESSION['api_token'] ?? '';
    $url   = API_BASE . '/' . ltrim($endpoint, '/');

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($data),
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $token,
        ],
    ]);

    $res  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($res === false || $code >= 400) {
        error_log("API POST $endpoint failed with HTTP $code");
        return ['success' => false, 'error' => "HTTP $code"];
    }

    return json_decode($res, true) ?? ['success' => false];
}

/**
 * PUT request to the API (used for profile updates, ratings, etc.).
 */
function api_put(string $endpoint, array $data = []): array {
    $token = $_SESSION['api_token'] ?? '';
    $url   = API_BASE . '/' . ltrim($endpoint, '/');

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => 'PUT',
        CURLOPT_POSTFIELDS     => json_encode($data),
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $token,
        ],
    ]);

    $res  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($res === false || $code >= 400) {
        error_log("API PUT $endpoint failed with HTTP $code");
        return ['success' => false, 'error' => "HTTP $code"];
    }

    return json_decode($res, true) ?? ['success' => false];
}

// ── GLOBAL DATA ───────────────────────────────────────────────
// Fetched once per page load; used across multiple pages.

// All available genres for filter dropdowns.
// Expected API response: ['Literary Fiction', 'Mystery', ...]
$genres = api_get('genres') ?? [];

// The current user's groups — used in nav sidebar, schedule filter, recommendations.
// Expected API response: array of group objects
$my_groups = api_get("users/{$_SESSION['id']}/groups") ?? [];

// ── PER-REQUEST LOOKUP CACHE ──────────────────────────────────
// Prevents fetching the same book or group more than once per page load.

$_book_cache  = [];
$_group_cache = [];

/**
 * Fetch a single book by ID. Results are cached in-memory for this request.
 * Expected API response: { id, title, author, cover, genre[], year, pages, rating, reviews, description, isbn }
 */
function getBookById(int $id): ?array {
    global $_book_cache;
    if (isset($_book_cache[$id])) return $_book_cache[$id];
    $book = api_get("books/$id");
    if ($book) $_book_cache[$id] = $book;
    return $book;
}

/**
 * Fetch a single group by ID. Results are cached in-memory for this request.
 * Expected API response: { id, name, description, members[], member_count, current_book_id, invite_code, created }
 */
function getGroupById(int $id): ?array {
    global $_group_cache;
    if (isset($_group_cache[$id])) return $_group_cache[$id];
    $group = api_get("groups/$id");
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