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

// Input validation for action
if (!isset($_GET['action']) || !is_string($_GET['action'])) {
    http_response_code(400);
    header('Content-Type: application/json');
    // Use _t for error messages if available, otherwise fallback
    echo json_encode(['error' => function_exists('_t') ? _t('error_no_action_param', 'Action parameter missing or invalid.') : 'Action parameter missing or invalid.']);
    exit;
}
$action = sanitize_input($_GET['action']); // Sanitize after checking existence

// Routing based on action
if ($action === 'getVideoInfo') {
    // URL validation
    if (!isset($_GET['url']) || !is_string($_GET['url'])) { // Also check if it's a string
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(['error' => function_exists('_t') ? _t('error_url_param_missing_for_getinfo', 'URL parameter missing or invalid for getVideoInfo action.') : 'URL parameter missing or invalid for getVideoInfo action.']);
        exit;
    }
    
    // Note: URL is sanitized by YtDlpWrapper::getVideoInfo before escapeshellarg.
    // Basic frontend validation for URL format can be helpful but yt-dlp is the ultimate validator.
    $url = $_GET['url']; // Pass raw URL to wrapper; wrapper should handle sanitization for shell, and internal sanitization for display values.
    
    // Further URL validation (optional, as yt-dlp will also validate)
    // It's generally good to validate early if possible.
    // The YtDlpWrapper's getVideoInfo now does sanitize_input for display parts, and escapeshellarg for command.
    // For direct use here, if we were to validate format before calling wrapper:
    $temp_sanitized_url_for_validation = sanitize_input($url); // Sanitize for filter_var and preg_match
    if (!filter_var($temp_sanitized_url_for_validation, FILTER_VALIDATE_URL)) {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(['error' => function_exists('_t') ? _t('error_invalid_url_format', 'Invalid URL format.') : 'Invalid URL format.']);
        exit;
    }
    if (!preg_match('/(youtube\.com\/(watch\?v=|shorts\/|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $temp_sanitized_url_for_validation)) {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(['error' => function_exists('_t') ? _t('error_invalid_youtube_url', 'Invalid YouTube URL.') : 'Invalid YouTube URL.']);
        exit;
    }

    $videoDetails = $ytDlp->getFormattableVideoInfo($url); // Pass original URL

    if (isset($videoDetails['error'])) {
        // Determine appropriate HTTP status code based on error type if possible
        // For now, using 500 for server-side/yt-dlp issues, 400 for bad inputs (already handled above)
        // YtDlpWrapper errors are generally server-side operational issues or yt-dlp failures.
        http_response_code(500); 
        header('Content-Type: application/json');
        echo json_encode(['error' => $videoDetails['error']]); // This error message is already from _t() in YtDlpWrapper
        exit;
    }
    header('Content-Type: application/json');
    echo json_encode($videoDetails);
    exit;

} elseif ($action === 'searchVideos') {
    if (!isset($_GET['query']) || !is_string($_GET['query'])) { // Also check if it's a string
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(['error' => function_exists('_t') ? _t('error_search_query_missing', 'Search query missing or invalid.') : 'Search query missing or invalid.']);
        exit;
    }
    
    // Note: Query is sanitized by YtDlpWrapper::searchVideos before escapeshellarg.
    $query = $_GET['query']; // Pass raw query
    $results = $ytDlp->searchVideos($query);

    // YtDlpWrapper::searchVideos returns an array with 'error' key on command execution failure
    // or an array of results (possibly empty) on success.
    if (isset($results['error'])) { 
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['error' => $results['error']]); // Error message from _t() in YtDlpWrapper
        exit;
    }
    // An empty array for $results['results'] (if search yielded nothing) is a valid success response.
    header('Content-Type: application/json');
    echo json_encode(['results' => $results]); // Ensure searchVideos returns the results directly or in a 'results' key if it makes sense
    exit;

} else {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => function_exists('_t') ? _t('error_invalid_action', 'Invalid action specified.') : 'Invalid action specified.']);
    exit;
}
?>
