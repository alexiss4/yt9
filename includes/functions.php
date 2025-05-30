<?php
// includes/functions.php

/**
 * Sanitizes input to prevent XSS and other basic attacks.
 * Should be used for any data that will be displayed or used in non-SQL contexts.
 * For shell arguments, always use this in conjunction with escapeshellarg().
 *
 * @param string|array $data The input string or array of strings to sanitize.
 * @return string|array The sanitized string or array.
 */
function sanitize_input($data) {
    if (is_array($data)) {
        return array_map('sanitize_input', $data);
    }
    $data = trim($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Class YtDlpWrapper
 *
 * Wraps yt-dlp (or compatible executable like yt-dlp_x86) commands for fetching video information,
 * searching videos, and streaming media content.
 * It relies on the YT_DLP_PATH constant defined in config.php.
 */
class YtDlpWrapper {
    private $yt_dlp_path;

    /**
     * Constructor for YtDlpWrapper.
     * Initializes the path to the yt-dlp executable using the YT_DLP_PATH constant
     * from `config.php`, or defaults to 'yt-dlp' if the constant is not defined.
     */
    public function __construct() {
        $this->yt_dlp_path = defined('YT_DLP_PATH') ? YT_DLP_PATH : 'yt-dlp';
    }

    /**
     * Executes a given shell command.
     * Captures both stdout and stderr. Logs errors if command execution
     * appears to fail or returns specific error strings from yt-dlp.
     *
     * @param string $command The shell command to execute.
     * @return string|false The output (stdout and stderr) from the command as a string,
     *                      or false if execution fails or returns a recognized error pattern.
     */
    private function executeCommand($command) {
        $output = shell_exec($command . " 2>&1"); // Capture stderr as well

        // Check for null (command failed to run) or empty/whitespace-only output
        if ($output === null || trim($output) === '') {
            // Log if command returned empty or falsy output
            error_log("YtDlpWrapper::executeCommand: Command returned empty, null, or whitespace-only output. Command: " . $command . " Raw Output: " . var_export($output, true));
            return false; // Explicitly return false for these cases
        }
        // Check for known yt-dlp error strings, but only if output is relatively short
        // to avoid matching on video titles or descriptions that might contain "ERROR:"
        if (strpos($output, "ERROR:") !== false && strlen($output) < 200) { 
            if (defined('LOG_FILE_PATH') && LOG_FILE_PATH) { // This specific logging might be redundant if general one below is active
                error_log("YtDlpWrapper::executeCommand: yt-dlp command error explicitly indicated. Command: $command. Output: " . $output);
            }
            // Also log if output doesn't look like JSON, which is often the case for yt-dlp direct error messages
            if (!preg_match('/^\s*[{[]/', $output)) {
                 error_log("YtDlpWrapper::executeCommand: Command returned non-JSON output, possibly an error message. Command: " . $command . " Output: " . substr($output, 0, 1000));
            }
            return false; // Return false if "ERROR:" is found in short output
        }
        
        // Generic check for non-JSON output if it's not an empty string and no "ERROR:" was found
        // This is tricky because search results are JSON lines, not a single JSON object.
        // This check is more relevant for getVideoInfo. searchVideos handles line-by-line parsing.
        // We'll make this check less aggressive here and rely more on json_decode checks in calling methods.
        // if (!preg_match('/^\s*[{[]/', $output) && trim($output) !== '') {
        //     error_log("YtDlpWrapper::executeCommand: Command returned potentially non-JSON output. Command: " . $command . " Output: " . substr($output, 0, 1000));
        //     // Depending on strictness, might return false here. For now, let json_decode handle it.
        // }

        return $output;
    }
    
    /**
     * Fetches raw video information from yt-dlp for a given URL.
     * The information is returned as a JSON-decoded associative array.
     *
     * @param string $url The YouTube video URL.
     * @return array An associative array containing the raw video information on success,
     *               or an array with an 'error' key (e.g., `['error' => 'Error message']`) on failure.
     *               Failures can be due to command execution errors or JSON parsing issues.
     */
    public function getVideoInfo($url) {
        // URL should be sanitized (for XSS, etc.) by the caller if it's going to be displayed.
        // For command execution, escapeshellarg is the primary concern.
        $escaped_url = escapeshellarg($url); 
        // Using --no-playlist to ensure we only get info for a single video.
        $command = "{$this->yt_dlp_path} -j --skip-download --no-playlist {$escaped_url}";
        $json_output = $this->executeCommand($command);

        if (!$json_output) {
            // Error is logged in executeCommand if LOG_FILE_PATH is defined.
            return ['error' => _t('error_yt_dlp_execution_failed', 'Failed to execute video information command. Please check server configuration or yt-dlp installation.')];
        }

        $video_info = json_decode($json_output, true);
        // Check if decoding failed or if essential data like 'title' is missing.
        if (json_last_error() !== JSON_ERROR_NONE || !$video_info || !isset($video_info['title'])) {
            // Log the error and the problematic JSON string
            error_log("YtDlpWrapper::getVideoInfo: Failed to parse JSON. Error: " . json_last_error_msg() . ". JSON Output: " . substr($json_output, 0, 1000)); 
            return ['error' => _t('error_parsing_video_info_json', 'Failed to parse video information. The data may be malformed or incomplete.')];
        }
        return $video_info; // Success
    }

    /**
     * Retrieves and formats video information suitable for API responses or frontend display.
     * This includes the video title, thumbnail URL, and a list of available download formats.
     *
     * @param string $url The YouTube video URL.
     * @return array An associative array with 'title', 'thumbnail_url', and 'formats' keys on success.
     *               The 'formats' key holds an array of available format details.
     *               Returns an array with an 'error' key (e.g., `['error' => 'Error message']`) if fetching
     *               or processing video information fails.
     */
    public function getFormattableVideoInfo($url) {
        $video_info = $this->getVideoInfo($url);

        // Check if getVideoInfo returned an error array
        if (isset($video_info['error'])) {
            return $video_info; // Propagate the error up
        }

        // Note: The original check `if (!$video_info)` after calling $this->getVideoInfo($url)
        // in getFormattableVideoInfo becomes redundant if getVideoInfo always returns an array (either data or error).
        // Similarly, `if (!isset($video_info['title']))` is also handled by getVideoInfo now.
        // However, keeping them as a defensive check or for clarity is fine.
        // For this refactoring, we assume the structure from getVideoInfo is now consistent.

        $output_formats = [];
        // Always add MP3 as a primary option
        $output_formats[] = [
            'id' => 'mp3',
            'type' => 'mp3',
            'label' => _t('format_audio_mp3_best', 'Audio MP3 (Best Available)'),
            'ext' => 'mp3',
            'resolution' => 'Audio', // For consistency in UI
            'filesize_approx_str' => _t('filesize_unknown','N/A')
        ];
        
        $desired_video_formats = ['2160p', '1440p', '1080p', '720p', '480p', '360p'];

        if (isset($video_info['formats']) && is_array($video_info['formats'])) {
            foreach ($video_info['formats'] as $format) {
                // Robust access to format keys
                $format_note = $format['format_note'] ?? null;
                $format_id = $format['format_id'] ?? null;
                $ext = $format['ext'] ?? null;
                $protocol = $format['protocol'] ?? ''; // Default to empty string
                $height = (isset($format['height']) && is_numeric($format['height'])) ? (int)$format['height'] : null;
                $vcodec = $format['vcodec'] ?? 'none'; // Default to 'none' as per existing logic
                $acodec = $format['acodec'] ?? 'none'; // Default to 'none'

                // Updated protocol check for clarity and correctness
                if (!in_array($protocol, ['http', 'https'])) {
                    continue; // Skip if protocol is not http or https
                }

                if (!$format_id || !$ext) { // Skip if essential format_id or ext are missing
                    continue;
                }
                
                $filesize = $format['filesize'] ?? ($format['filesize_approx'] ?? null);
                $filesize_str = $filesize ? round($filesize / (1024*1024), 2) . " MB" : _t('filesize_unknown','N/A');

                // Add MP4 video formats that have both video and audio
                if ($ext === 'mp4' && $vcodec !== 'none' && $acodec !== 'none' && $height && $format_note && in_array($format_note, $desired_video_formats)) {
                     $output_formats[] = [
                        'id' => $format_id,
                        'type' => 'mp4',
                        'label' => _t('format_video_mp4_quality', "MP4 {quality}", ['quality' => $format_note]),
                        'ext' => 'mp4',
                        'resolution' => $height . "p",
                        'filesize_approx_str' => $filesize_str
                    ];
                }
                // Example of how M4A audio format could be added (currently commented out):
                /*
                if ($ext === 'm4a' && $acodec !== 'none' && $vcodec === 'none') {
                    $output_formats[] = [
                        'id' => $format_id, 
                        'type' => 'm4a', 
                        'label' => _t('format_audio_m4a_abr', "Audio M4A ({abr}k)", ['abr' => round($format['abr'] ?? 0)]),
                        'ext' => 'm4a',
                        'resolution' => 'Audio',
                        'filesize_approx_str' => $filesize_str
                    ];
                }
                */
            }
        }
        
        // Ensure unique formats by id, prioritizing those added earlier (like our manual MP3)
        $final_formats = [];
        $seen_ids = [];
        foreach($output_formats as $fmt){
            if(!isset($seen_ids[$fmt['id']])){
                $final_formats[] = $fmt;
                $seen_ids[$fmt['id']] = true;
            }
        }

        // Safely determine thumbnail URL
        $thumbnail_url_final = $video_info['thumbnail'] ?? null;
        if (!$thumbnail_url_final && isset($video_info['thumbnails']) && is_array($video_info['thumbnails']) && count($video_info['thumbnails']) > 0 && isset($video_info['thumbnails'][0]['url'])) {
            $thumbnail_url_final = $video_info['thumbnails'][0]['url'];
        }

        return [
            'title' => sanitize_input($video_info['title']), // Sanitize titles for display
            'thumbnail_url' => sanitize_input($thumbnail_url_final ?? ''), // Ensure thumbnail_url is sanitized and defaults to empty string if null
            'formats' => $final_formats, // Formats are already constructed with localization in mind
        ];
    }


    /**
     * Streams media content (video or audio) for a given YouTube URL and format ID.
     * This method directly outputs the media stream to the client and sets appropriate HTTP headers
     * for download. It terminates execution after streaming.
     *
     * @param string $url The YouTube video URL.
     * @param string $format_id The specific format ID to download (e.g., 'mp3', '137').
     * @param string $video_title The title of the video, used to generate the downloaded filename.
     * @param string $fallback_extension The file extension to use if it cannot be determined from the format ID. Defaults to 'mp4'.
     * @return void This method does not return a value as it terminates script execution.
     */
    public function streamMedia($url, $format_id, $video_title, $fallback_extension = 'mp4') {
        $escaped_url = escapeshellarg($url);
        $video_title_sanitized = preg_replace('/[^A-Za-z0-9_\-]/', '_', $video_title);
        
        $final_extension = $fallback_extension;
        $command_options = "";

        if ($format_id === 'mp3') {
            $command_options = "-f bestaudio -x --audio-format mp3";
            $final_extension = 'mp3';
        } else {
            // For specific video formats, ensure we get the one with audio if available
            // This might be complex if a format ID refers to video-only or audio-only stream
            // For simplicity, we trust the format_id provided from getFormattableVideoInfo
            // which should prefer combined streams.
            $command_options = "-f " . escapeshellarg($format_id);
            // Attempt to determine the correct extension if not MP3.
            // This re-fetches video info, which is inefficient but safer for ensuring correct extension.
            // A more optimized approach might pass the initially fetched video_info array or specific format extension.
            $video_data_for_ext_check = $this->getVideoInfo($url); 
            if (!isset($video_data_for_ext_check['error']) && isset($video_data_for_ext_check['formats']) && is_array($video_data_for_ext_check['formats'])) {
                foreach ($video_data_for_ext_check['formats'] as $fmt) {
                    if (isset($fmt['format_id']) && $fmt['format_id'] === $format_id && isset($fmt['ext'])) {
                        $final_extension = $fmt['ext'];
                        break;
                    }
                }
            }
        }

        $filename = $video_title_sanitized . '.' . $final_extension;
        $full_command = "{$this->yt_dlp_path} {$command_options} -o - {$escaped_url}";

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        
        // Ensure output buffering is off or flushed before passthru
        if (ob_get_level() > 0) {
            ob_end_flush();
        }
        
        passthru($full_command);
    }

    /**
     * Searches for videos on YouTube using yt-dlp based on a query string.
     * Returns a list of search results, each including video ID, title, thumbnail, uploader, duration, and URL.
     *
     * @param string $query The search query string.
     * @return array An array of search result items on success. Each item is an associative array.
     *               Returns an array with an 'error' key (e.g., `['error' => 'Error message']`)
     *               if the search command execution fails.
     *               Returns an empty array if the command succeeds but no results are found.
     */
    public function searchVideos($query) {
        // Using ytsearch5 to limit results, good for performance and relevance.
        // --dump-json will output one JSON object per line for each search result.
        $command = sprintf("%s \"ytsearch5:%s\" --dump-json --no-playlist", $this->yt_dlp_path, escapeshellarg($query));
        $search_output_json_lines = $this->executeCommand($command);

        if ($search_output_json_lines === false) {
            // Error logged in executeCommand if LOG_FILE_PATH is defined.
            // Return an array with error key, consistent with getVideoInfo
            return ['error' => _t('error_yt_dlp_search_execution_failed', 'Failed to execute video search command. Please check server configuration or yt-dlp installation.')];
        }

        $results = [];
        $lines = explode("\n", trim($search_output_json_lines));

        foreach ($lines as $line) {
            if (empty(trim($line))) {
                continue;
            }
            $video_data = json_decode($line, true);
            // Basic validation of video data structure
            $video_data = json_decode($line, true);
            if (json_last_error() !== JSON_ERROR_NONE && is_array($video_data) && isset($video_data['id'])) {
                // This part is fine, the issue is if $line itself is not JSON
                // The check for $video_data['id'] is good.
                $id = $video_data['id'] ?? null; // This null coalesce is redundant due to isset check above.
                // if (!$id) { // This check is also somewhat redundant.
                //     error_log("YtDlpWrapper::searchVideos: Parsed JSON line but 'id' is missing. Line: " . $line);
                //     continue; 
                // }

                $title = $video_data['title'] ?? _t('search_title_na', 'N/A');
            
                $thumbnail = $video_data['thumbnail'] ?? null;
                if (!$thumbnail && isset($video_data['thumbnails']) && is_array($video_data['thumbnails']) && count($video_data['thumbnails']) > 0 && isset($video_data['thumbnails'][0]['url'])) {
                    $thumbnail = $video_data['thumbnails'][0]['url'];
                }
            
                $uploader = $video_data['uploader'] ?? _t('search_uploader_na', 'N/A');
                $duration_string = $video_data['duration_string'] ?? _t('search_duration_na', 'N/A');

                $results[] = [
                    'id' => sanitize_input($id),
                    'title' => sanitize_input($title),
                    'thumbnail_url' => sanitize_input($thumbnail ?? ''), // Sanitize and default to empty if null
                    'uploader' => sanitize_input($uploader),
                    'duration_string' => sanitize_input($duration_string),
                    'url' => 'https://www.youtube.com/watch?v=' . sanitize_input($id)
                ];
            } else {
                // Log line that failed to parse or didn't meet structure requirements
                error_log("YtDlpWrapper::searchVideos: Failed to parse JSON line or missing 'id'. Error: " . json_last_error_msg() . ". Line: " . $line);
                continue; // Skip this line
            }
        }
        return $results; // Can be an empty array if no results found, which is not an error if search command succeeded.
    }
}

// Add other common functions here as the project evolves.
?>
