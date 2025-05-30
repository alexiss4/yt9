<?php
    require_once __DIR__ . '/config.php';
    require_once __DIR__ . '/includes/functions.php';

    // Initialize YtDlpWrapper
    $ytDlp = new YtDlpWrapper();

    // Basic input validation for action
    if (!isset($_GET['action'])) {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Action parameter missing']);
        exit;
    }

    $action = sanitize_input($_GET['action']);

    // Action: getVideoInfo
    if ($action === 'getVideoInfo') {
        // Validate URL parameter
        if (!isset($_GET['url'])) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'URL parameter missing for getVideoInfo']);
            exit;
        }

        $url = sanitize_input($_GET['url']); 
        
        // Further URL validation
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['error' => _t('error_invalid_url_format', 'Invalid URL format.')]);
            exit;
        }
        if (!preg_match('/(youtube\.com\/(watch\?v=|shorts\/|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $url)) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['error' => _t('error_invalid_youtube_url', 'Invalid YouTube URL.')]);
            exit;
        }

        // Get video information using YtDlpWrapper
        $videoDetails = $ytDlp->getFormattableVideoInfo($url);

        // Handle errors from wrapper
        if (isset($videoDetails['error'])) {
            http_response_code(500); 
            header('Content-Type: application/json');
            echo json_encode(['error' => $videoDetails['error']]);
            exit;
        }
        
        // Send successful response
        header('Content-Type: application/json');
        echo json_encode($videoDetails);
        exit;

    // Action: searchVideos
    } elseif ($action === 'searchVideos') {
        // Validate query parameter
        if (!isset($_GET['query'])) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['error' => _t('error_search_query_missing', 'Search query missing.')]);
            exit;
        }

        $query = sanitize_input($_GET['query']); 
        
        // Perform search using YtDlpWrapper
        $results = $ytDlp->searchVideos($query);

        // Handle errors from wrapper (false indicates execution error, an array with 'error' key for other errors)
        if ($results === false || isset($results['error'])) { 
            http_response_code(500);
            header('Content-Type: application/json');
            $error_message = isset($results['error']) ? $results['error'] : _t('error_search_failed_yt_dlp', 'Could not retrieve search results or yt-dlp command failed.');
            echo json_encode(['error' => $error_message]);
            exit;
        }
        
        // Send successful response (can be an empty array if no results found)
        header('Content-Type: application/json');
        echo json_encode(['results' => $results]);
        exit;

    // Unknown action
    } else {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Invalid action specified']);
        exit;
    }
?>
