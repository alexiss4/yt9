<?php
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    include("includes/functions.php");

    // Default to error if action is not set or invalid
    if (!isset($_GET['action']) || $_GET['action'] !== 'getVideoInfo') {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Invalid action']);
        exit;
    }

    // Handle getVideoInfo action
    if ($_GET['action'] === 'getVideoInfo') {
        if (!isset($_GET['url'])) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'URL parameter missing']);
            exit;
        }

        $url = sanitize_input($_GET['url']);

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Invalid URL format']);
            exit;
        }

        if (!preg_match('/(youtube\.com\/watch\?v=|youtu\.be\/)/', $url)) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Invalid YouTube URL']);
            exit;
        }

        $validated_url = $url;
        $info_command = "yt-dlp -j --skip-download " . escapeshellarg($validated_url);
        $video_info_json = shell_exec($info_command);

        if (!$video_info_json) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Could not retrieve video information. The video may be private, deleted, or copyrighted, or yt-dlp failed.']);
            exit;
        }

        $video_info = json_decode($video_info_json, true);

        if (json_last_error() !== JSON_ERROR_NONE || !$video_info || !isset($video_info['title'])) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Failed to parse video information from yt-dlp.']);
            exit;
        }

        $output_formats = [];

        // Add MP3 option first
        $output_formats[] = [
            'id' => 'mp3',
            'type' => 'mp3',
            'label' => 'Audio MP3 (Best Available)',
            'ext' => 'mp3',
            // 'abr' could be determined if we fetch detailed audio format info, but "Best Available" is often sufficient
        ];
        
        $desired_video_formats = ['1080p', '720p', '480p', '360p'];

        foreach ($video_info['formats'] as $format) {
            $format_note = $format['format_note'] ?? null;
            $format_id = $format['format_id'] ?? null;
            $ext = $format['ext'] ?? null;
            $protocol = $format['protocol'] ?? '';

            // Consider only formats with http/https protocol and valid data
            if ($protocol !== 'http' && $protocol !== 'https') {
                continue;
            }
            if (!$format_id || !$ext) continue;


            // Process MP4 video formats
            if ($ext === 'mp4' && isset($format['height']) && $format['height'] >= 360 && $format_note && in_array($format_note, $desired_video_formats)) {
                 if (isset($format['vcodec']) && $format['vcodec'] !== 'none' && isset($format['acodec']) && $format['acodec'] !== 'none') { // Ensure it has both video and audio
                    $output_formats[] = [
                        'id' => $format_id,
                        'type' => 'mp4',
                        'label' => "MP4 {$format_note}",
                        'ext' => 'mp4',
                        'url_quality' => $format_note, // Deprecated in favor of 'label' or specific 'height' if needed
                        'height' => $format['height']
                    ];
                 }
            }
        }
        
        // Remove duplicate formats by ID, keeping the first occurrence (our manual mp3).
        $final_formats = [];
        $seen_ids = [];
        foreach($output_formats as $fmt){
            if(!isset($seen_ids[$fmt['id']])){
                $final_formats[] = $fmt;
                $seen_ids[$fmt['id']] = true;
            }
        }


        $response_data = [
            'title' => $video_info['title'],
            'thumbnail_url' => $video_info['thumbnail'] ?? ($video_info['thumbnails'][0]['url'] ?? null),
            'formats' => $final_formats,
        ];

        header('Content-Type: application/json');
        echo json_encode($response_data);
        exit;
    }
?>
