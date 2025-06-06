<?php
// api.php

// Strict types and error reporting (handled by config.php, but good to be mindful)
// declare(strict_types=1); // Optional: consider for new projects

// Core configurations and functions
require_once __DIR__ . '/config.php';       // Site-wide config, error handling setup
require_once __DIR__ . '/includes/language.php'; // Translation functions (like _t)
require_once __DIR__ . '/includes/functions.php';  // Core functions, YtDlpWrapper

// Instantiate the YouTube Downloader Plus wrapper
$ytDlp = new YtDlpWrapper();

/**
 * Sends a JSON response with appropriate headers and HTTP status code, then exits.
 *
 * @param mixed $data The data to encode as JSON. If it's an error response,
 *                    it should typically be an array like ['error' => 'message'].
 *                    For successful responses, it can be any JSON-encodable data.
 * @param int $statusCode The HTTP status code to send. Defaults to 200.
 */
function send_json_response($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    // Ensure error messages are translated if _t function exists
    if ($statusCode >= 400 && isset($data['error']) && function_exists('_t')) {
        // Assuming the error key (e.g., 'error_no_action_param') is passed as the message
        // and the second parameter to _t is the fallback.
        // This part might need adjustment based on how error messages are structured.
        // For now, let's assume $data['error'] is a string that might be a key or a literal message.
        // If $data['error'] is intended to be a key, the caller should pre-translate it.
        // This helper will just ensure it's encoded.
    }
    echo json_encode($data);
    exit;
}

// Input validation for action
if (!isset($_GET['action']) || !is_string($_GET['action'])) {
    send_json_response(
        ['error' => function_exists('_t') ? _t('error_no_action_param', 'Action parameter missing or invalid.') : 'Action parameter missing or invalid.'],
        400
    );
}
$action = sanitize_input($_GET['action']); // Sanitize after checking existence

// Routing based on action
if ($action === 'getVideoInfo') {
    $raw_url = $_GET['url'] ?? null;

    if (!$raw_url || !is_string($raw_url)) {
        send_json_response(
            ['error' => function_exists('_t') ? _t('error_url_param_missing_for_getinfo', 'URL parameter missing or invalid for getVideoInfo action.') : 'URL parameter missing or invalid for getVideoInfo action.'],
            400
        );
    }

    // Validate URL format (general)
    // For validation, we use the raw URL. Sanitization is for preventing XSS if this URL were echoed,
    // or for preparing for shell commands (which YtDlpWrapper handles).
    if (!filter_var($raw_url, FILTER_VALIDATE_URL)) {
        send_json_response(
            ['error' => function_exists('_t') ? _t('error_invalid_url_format', 'Invalid URL format.') : 'Invalid URL format.'],
            400
        );
    }

    // Validate YouTube URL pattern more specifically
    // This pattern allows for various YouTube URL formats and extracts the video ID.
    // It checks for:
    // - youtube.com/watch?v=<ID>
    // - youtu.be/<ID>
    // - youtube.com/embed/<ID>
    // - youtube.com/shorts/<ID>
    // - Optional www. subdomain
    // - Optional http/https scheme
    // - Video ID must be 11 characters long (letters, numbers, underscores, hyphens)
    $youtube_regex = '/^(?:https?:\/\/)?(?:www\.)?(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})(?:&.*|(?:\?.*)?)?$/';
    if (!preg_match($youtube_regex, $raw_url, $matches)) {
        send_json_response(
            ['error' => function_exists('_t') ? _t('error_invalid_youtube_url', 'The provided URL does not appear to be a valid YouTube video URL.') : 'Invalid YouTube URL.'],
            400
        );
    }
    // $video_id = $matches[1]; // The extracted video ID, if needed

    // The URL is passed raw to getFormattableVideoInfo; the method itself handles sanitization for display and shellarg for commands.
    $videoDetails = $ytDlp->getFormattableVideoInfo($raw_url);

    if (isset($videoDetails['error'])) {
        // YtDlpWrapper errors are generally server-side operational issues or yt-dlp failures.
        // Consider if specific errors from YtDlpWrapper could map to 404 Not Found.
        // For now, 500 is a safe default for wrapper-level errors.
        send_json_response(['error' => $videoDetails['error']], 500); // Error message is already translated by wrapper
    }

    // Success
    send_json_response($videoDetails, 200);

} elseif ($action === 'searchVideos') {
    $raw_query = $_GET['query'] ?? null;

    if (!$raw_query || !is_string($raw_query)) {
        send_json_response(
            ['error' => function_exists('_t') ? _t('error_search_query_missing', 'Search query missing or invalid.') : 'Search query missing or invalid.'],
            400
        );
    }

    // Query is passed raw to searchVideos; the method handles sanitization.
    $results = $ytDlp->searchVideos($raw_query);

    if (isset($results['error_message'])) { // Assuming YtDlpWrapper::searchVideos uses 'error_message' like getVideoInfo
        send_json_response(['error' => $results['error_message']], 500);  // Error message is already translated by wrapper
    }

    // searchVideos returns an array of results (possibly empty) on success.
    // The API contract is to return {'results': ...}
    send_json_response(['results' => $results], 200);

} else {
    send_json_response(
        ['error' => function_exists('_t') ? _t('error_invalid_action', 'Invalid action specified.') : 'Invalid action specified.'],
        400
    );
}
?>
