<?php
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    include("includes/functions.php");

    if (!isset($_GET['action'])) {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Action parameter missing']);
        exit;
    }

    $action = $_GET['action'];

    if ($action === 'getVideoInfo') {
        if (!isset($_GET['url'])) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'URL parameter missing for getVideoInfo']);
            exit;
        }

        $url = sanitize_input($_GET['url']);

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Invalid URL format for getVideoInfo']);
            exit;
        }
        
        // Updated regex to also match shorts and embed URLs for getVideoInfo
        if (!preg_match('/(youtube\.com\/(watch\?v=|shorts\/|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $url)) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Invalid YouTube URL for getVideoInfo']);
            exit;
        }

        $validated_url = $url;
        // Use --no-playlist for single video info to avoid issues if a video is part of a playlist
        $info_command = "yt-dlp -j --skip-download --no-playlist " . escapeshellarg($validated_url);
        $video_info_json = shell_exec($info_command);

        if (!$video_info_json) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Could not retrieve video information. The video may be private, deleted, copyrighted, or yt-dlp command failed.']);
            exit;
        }

        $video_info = json_decode($video_info_json, true);

        if (json_last_error() !== JSON_ERROR_NONE || !$video_info || !isset($video_info['title'])) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Failed to parse video information from yt-dlp output.']);
            exit;
        }

        $output_formats = [];
        $output_formats[] = [
            'id' => 'mp3', 'type' => 'mp3', 'label' => 'Audio MP3 (Best Available)', 'ext' => 'mp3',
        ];
        
        $desired_video_formats = ['1080p', '720p', '480p', '360p'];
        if (isset($video_info['formats']) && is_array($video_info['formats'])) {
            foreach ($video_info['formats'] as $format) {
                $format_note = $format['format_note'] ?? null;
                $format_id = $format['format_id'] ?? null;
                $ext = $format['ext'] ?? null;
                $protocol = $format['protocol'] ?? '';

                if (($protocol !== 'http' && $protocol !== 'https') || !$format_id || !$ext) {
                    continue;
                }

                if ($ext === 'mp4' && isset($format['height']) && $format['height'] >= 360 && $format_note && in_array($format_note, $desired_video_formats)) {
                     if (isset($format['vcodec']) && $format['vcodec'] !== 'none' && isset($format['acodec']) && $format['acodec'] !== 'none') {
                        $output_formats[] = [
                            'id' => $format_id, 'type' => 'mp4', 'label' => "MP4 {$format_note}",
                            'ext' => 'mp4', 'url_quality' => $format_note, 'height' => $format['height']
                        ];
                     }
                }
            }
        }
        
        $final_formats = []; $seen_ids = [];
        foreach($output_formats as $fmt){ if(!isset($seen_ids[$fmt['id']])){ $final_formats[] = $fmt; $seen_ids[$fmt['id']] = true; } }

        $response_data = [
            'title' => $video_info['title'],
            'thumbnail_url' => $video_info['thumbnail'] ?? ($video_info['thumbnails'][0]['url'] ?? null),
            'formats' => $final_formats,
        ];
        header('Content-Type: application/json');
        echo json_encode($response_data);
        exit;

    } elseif ($action === 'searchVideos') {
        if (!isset($_GET['query'])) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Search query missing']);
            exit;
        }

        $query = sanitize_input($_GET['query']);
        // Using ytsearch5 to limit results, good for performance and relevance
        $search_command = sprintf("yt-dlp \"ytsearch5:%s\" --dump-json --no-playlist", escapeshellarg($query));
        $search_output_json_lines = shell_exec($search_command);

        if (!$search_output_json_lines) {
            http_response_code(500);
            header('Content-Type: application/json');
            // It's possible yt-dlp returns nothing for a query, but we'll treat empty shell_exec as an error for now
            // or if the command itself failed.
            echo json_encode(['error' => 'Could not retrieve search results or no results found.']);
            exit;
        }

        $results = [];
        $lines = explode("\n", trim($search_output_json_lines));

        foreach ($lines as $line) {
            if (empty(trim($line))) {
                continue;
            }
            $video_data = json_decode($line, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($video_data) && isset($video_data['id'])) {
                $results[] = [
                    'id' => $video_data['id'],
                    'title' => $video_data['title'] ?? 'N/A',
                    'thumbnail' => $video_data['thumbnail'] ?? ($video_data['thumbnails'][0]['url'] ?? null), // Prioritize 'thumbnail' if available
                    'uploader' => $video_data['uploader'] ?? 'N/A',
                    'duration_string' => $video_data['duration_string'] ?? 'N/A',
                    'url' => 'https://www.youtube.com/watch?v=' . $video_data['id'] // Construct full URL
                ];
            }
        }

        if (empty($results)) {
             // This case might be hit if yt-dlp returns JSON that doesn't match our expectations or truly no results.
            http_response_code(500); // Or 404 if we are sure it means "no results found" vs "error parsing"
            header('Content-Type: application/json');
            echo json_encode(['error' => 'No valid search results processed or yt-dlp output format unexpected.']);
            exit;
        }

        header('Content-Type: application/json');
        echo json_encode(['results' => $results]);
        exit;

    } else {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Invalid action specified']);
        exit;
    }
?>
