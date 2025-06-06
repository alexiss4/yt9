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
        $output = shell_exec($command . " 2>&1");

        if ($output === null) {
            // This means shell_exec failed to run the command (e.g., process could not be created).
            error_log("YtDlpWrapper::executeCommand: Failed to execute command. `shell_exec` returned null. Command: " . $command);
            return ['output' => null, 'error' => 'command_execution_failed', 'message' => 'Server failed to execute the command.' ];
        }

        if (trim($output) === '') {
            // Command ran but produced no output. This might be an error or expected for some commands.
            // For yt-dlp, it usually indicates an issue if JSON output was expected.
            error_log("YtDlpWrapper::executeCommand: Command produced no output. Command: " . $command);
            // Returning a specific error structure
            return ['output' => '', 'error' => 'empty_output', 'message' => 'Command produced no output.'];
        }

        // Check for common yt-dlp error indicators.
        // yt-dlp often outputs "ERROR:" at the beginning of a line for actual errors.
        // It might also output plain text messages not in JSON format when errors occur.
        $is_likely_json = preg_match('/^\s*[{[]/', $output); // Check if output starts like JSON
        $contains_error_keyword = stripos($output, "ERROR:") !== false;

        if ($contains_error_keyword && !$is_likely_json) {
            // If "ERROR:" is present and output is NOT JSON, it's highly likely a yt-dlp direct error message.
            $error_message_excerpt = substr(trim($output), 0, 255); // Log a snippet of the error
            error_log("YtDlpWrapper::executeCommand: yt-dlp command indicated an error. Command: $command. Output: " . $error_message_excerpt);
            return ['output' => $output, 'error' => 'yt_dlp_error', 'message' => 'yt-dlp reported an error: ' . $error_message_excerpt ];
        }

        // If output looks like JSON but contains "ERROR:", it might be part of a description or title.
        // However, if it's short and contains "ERROR:", it's still suspicious.
        // This condition is more nuanced. For now, we rely on the JSON parsing in calling methods
        // to be the final judge if the output is supposed to be JSON.

        return ['output' => $output, 'error' => null]; // Success, return the output
    }

    /**
     * Fetches raw video information from yt-dlp for a given URL.
     * The information is returned as a JSON-decoded associative array.
     *
     * @param string $url The YouTube video URL. Input is sanitized.
     * @return array An associative array containing the raw video information on success,
     *               or an array with an 'error_message' key (e.g., `['error_message' => 'Error message']`) on failure.
     */
    public function getVideoInfo($url) {
        // Sanitize URL before using it with escapeshellarg
        $sanitized_url = sanitize_input($url);
        if (empty($sanitized_url)) {
            return ['error_message' => _t('error_invalid_url', 'Invalid or empty URL provided.')];
        }
        // Validate URL format (basic validation)
        if (!filter_var($sanitized_url, FILTER_VALIDATE_URL)) {
             return ['error_message' => _t('error_invalid_url_format', 'The provided URL is not valid.')];
        }

        $escaped_url = escapeshellarg($sanitized_url);
        // Optimized options: -j for JSON, --skip-download, --no-playlist (if single video expected), --no-warnings
        // Added --extractor-retries 3 and --socket-timeout 30 for robustness
        $command = "{$this->yt_dlp_path} -j --skip-download --no-playlist --no-warnings --extractor-retries 3 --socket-timeout 30 {$escaped_url}";

        $exec_result = $this->executeCommand($command);

        if ($exec_result['error']) {
            // Specific error from executeCommand
            $error_key = 'error_yt_dlp_execution_failed';
            if ($exec_result['error'] === 'yt_dlp_error') {
                $error_key = 'error_yt_dlp_reported';
            }
             error_log("YtDlpWrapper::getVideoInfo: Error executing command. Type: {$exec_result['error']}. Message: {$exec_result['message']}. URL: {$sanitized_url}");
            return ['error_message' => _t($error_key, $exec_result['message'])];
        }

        $json_output = $exec_result['output'];
        if (empty($json_output)) {
             error_log("YtDlpWrapper::getVideoInfo: Received empty JSON output from yt-dlp for URL: {$sanitized_url}");
            return ['error_message' => _t('error_parsing_video_info_json', 'Failed to parse video information: No data received.')];
        }

        $video_info = json_decode($json_output, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("YtDlpWrapper::getVideoInfo: Failed to parse JSON. Error: " . json_last_error_msg() . ". URL: {$sanitized_url}. JSON Output: " . substr($json_output, 0, 1000));
            return ['error_message' => _t('error_parsing_video_info_json_details', 'Failed to parse video information: ' . json_last_error_msg())];
        }

        if (!$video_info || !isset($video_info['id']) || !isset($video_info['title'])) {
             error_log("YtDlpWrapper::getVideoInfo: Essential video metadata (id, title) missing. URL: {$sanitized_url}. Data: " . substr($json_output, 0, 1000));
            return ['error_message' => _t('error_video_info_incomplete', 'Video information is incomplete or missing essential data (like ID or title).')];
        }

        return $video_info;
    }

    /**
     * Retrieves and formats video information suitable for API responses or frontend display.
     * This includes the video title, thumbnail URL, and a list of available download formats.
     *
     * @param string $url The YouTube video URL.
     * @return array An associative array with 'title', 'thumbnail_url', 'duration_string', and 'formats' keys on success.
     *               The 'formats' key holds an array of available format details.
     *               Returns an array with an 'error' key (e.g., `['error' => 'Error message']`) if fetching
     *               or processing video information fails.
     */
    public function getFormattableVideoInfo($url) {
        // Input URL is sanitized by getVideoInfo
        $video_info = $this->getVideoInfo($url);
        if (isset($video_info['error_message'])) {
            return ['error' => $video_info['error_message']];
        }

        $raw_formats = $video_info['formats'] ?? [];
        $processed_formats = [];

        // Helper function to format filesize
        $format_filesize = function($bytes) {
            if (!is_numeric($bytes) || $bytes <= 0) return 'N/A';
            $sz = 'BKMGTP';
            $factor = floor((strlen((string)$bytes) - 1) / 3);
            return sprintf("%.2f%s", $bytes / pow(1024, $factor), $sz[(int)$factor]);
        };

        // 1. Add MP3 Option (explicit conversion)
        $processed_formats['mp3'] = [ // Use ID as key to prevent duplicates naturally
            'id' => 'mp3',
            'type' => 'audioonly',
            'category' => 'audio',
            'label' => _t('format_mp3_download', 'MP3 Audio'),
            'ext' => 'mp3',
            'resolution_or_bitrate' => _t('best_available_audio', 'Best Available'),
            'filesize_str' => 'N/A', // Filesize for MP3 is unknown until conversion
            'has_audio' => true,
            'has_video' => false,
            'yt_dlp_format_obj' => ['note' => 'MP3 conversion via yt-dlp -x']
        ];

        $desired_resolutions = ['2160p', '1440p', '1080p', '720p', '480p', '360p'];
        $video_only_mp4_formats = []; // Store video-only MP4s for potential muxing
        $best_m4a_for_muxing = null;
        $seen_premuxed_resolutions = []; // Tracks resolutions for pre-muxed MP4s (e.g., "1080p")

        // 2. Process raw formats from yt-dlp
        foreach ($raw_formats as $format) {
            $format_id = $format['format_id'] ?? null;
            if (!$format_id || !isset($format['protocol']) || !in_array($format['protocol'], ['http', 'https'])) {
                continue;
            }

            $ext = $format['ext'] ?? 'N/A';
            $vcodec = $format['vcodec'] ?? 'none';
            $acodec = $format['acodec'] ?? 'none';
            $format_note = $format['format_note'] ?? ($format['resolution'] ?? null); // Resolution string like "1080p"
            $height = (isset($format['height']) && is_numeric($format['height'])) ? (int)$format['height'] : null;
            $filesize = $format['filesize'] ?? ($format['filesize_approx'] ?? null);
            $filesize_str = $format_filesize($filesize);

            // Collect best M4A audio stream for muxing
            if ($ext === 'm4a' && $vcodec === 'none' && $acodec !== 'none') {
                $current_abr = $format['abr'] ?? 0;
                if ($best_m4a_for_muxing === null || $current_abr > ($best_m4a_for_muxing['yt_dlp_format_obj']['abr'] ?? 0)) {
                    $best_m4a_for_muxing = [
                        'id' => $format_id,
                        'filesize' => $filesize,
                        'yt_dlp_format_obj' => $format
                    ];
                }
            }

            // Process MP4 Video Formats
            if ($ext === 'mp4' && $vcodec !== 'none' && $height && $format_note && in_array($format_note, $desired_resolutions)) {
                if ($acodec !== 'none') { // Pre-muxed MP4 (video + audio)
                    if (!isset($seen_premuxed_resolutions[$format_note])) {
                        $processed_formats[$format_id] = [
                            'id' => $format_id,
                            'type' => 'audiovideo',
                            'category' => 'video',
                            'label' => 'MP4 ' . $format_note,
                            'ext' => 'mp4',
                            'resolution_or_bitrate' => $format_note,
                            'filesize_str' => $filesize_str,
                            'has_audio' => true,
                            'has_video' => true,
                            'yt_dlp_format_obj' => $format
                        ];
                        $seen_premuxed_resolutions[$format_note] = true;
                    }
                } else { // Video-only MP4
                    // Store it, we might mux it later. Also add as a video-only option.
                    $video_only_entry = [
                        'id' => $format_id,
                        'type' => 'videoonly',
                        'category' => 'video',
                        'label' => 'MP4 ' . $format_note . ' (' . _t('video_only_label', 'Video Only') . ')',
                        'ext' => 'mp4',
                        'resolution_or_bitrate' => $format_note,
                        'filesize_str' => $filesize_str,
                        'has_audio' => false,
                        'has_video' => true,
                        'yt_dlp_format_obj' => $format
                    ];
                    $processed_formats[$format_id] = $video_only_entry;
                    // Keep a reference for muxing, keyed by resolution for easy lookup
                    if (!isset($video_only_mp4_formats[$format_note]) ||
                        ($filesize ?? 0) > ($video_only_mp4_formats[$format_note]['yt_dlp_format_obj']['filesize'] ?? ($video_only_mp4_formats[$format_note]['yt_dlp_format_obj']['filesize_approx'] ?? 0))) {
                        // Prefer higher quality (larger filesize) video-only stream for a given resolution if multiple exist
                        $video_only_mp4_formats[$format_note] = $video_only_entry;
                    }
                }
            }
        }

        // 3. Create muxed formats if a suitable M4A audio stream was found
        if ($best_m4a_for_muxing && !empty($video_only_mp4_formats)) {
            $resolutions_to_mux_preference = ['1080p', '720p', '480p', '360p']; // Desired muxed outputs

            foreach ($resolutions_to_mux_preference as $res_pref) {
                if (isset($video_only_mp4_formats[$res_pref])) {
                    $video_format_entry = $video_only_mp4_formats[$res_pref];
                    $video_stream_data = $video_format_entry['yt_dlp_format_obj'];
                    $audio_stream_data = $best_m4a_for_muxing['yt_dlp_format_obj'];

                    $combined_id = $video_stream_data['format_id'] . '+' . $audio_stream_data['format_id'];

                    // Avoid adding if a pre-muxed version of this resolution already exists and is preferred
                    if (isset($seen_premuxed_resolutions[$res_pref])) {
                        // Potentially add logic here if muxed should override pre-muxed, or be an alternative
                        // For now, if a pre-muxed one exists, we assume it's good enough.
                        continue;
                    }

                    $video_filesize_bytes = $video_stream_data['filesize'] ?? ($video_stream_data['filesize_approx'] ?? 0);
                    $audio_filesize_bytes = $audio_stream_data['filesize'] ?? ($audio_stream_data['filesize_approx'] ?? 0);
                    $combined_filesize_str = ($video_filesize_bytes > 0 && $audio_filesize_bytes > 0)
                                             ? $format_filesize($video_filesize_bytes + $audio_filesize_bytes)
                                             : 'N/A';

                    $processed_formats[$combined_id] = [
                        'id' => $combined_id,
                        'type' => 'audiovideo_muxed',
                        'category' => 'video',
                        'label' => 'MP4 ' . $res_pref . ' (' . _t('muxed_audio_label', 'Best Audio') . ')',
                        'ext' => 'mp4',
                        'resolution_or_bitrate' => $res_pref,
                        'filesize_str' => $combined_filesize_str,
                        'has_audio' => true,
                        'has_video' => true,
                        'yt_dlp_format_obj' => ['video_stream' => $video_stream_data, 'audio_stream' => $audio_stream_data]
                    ];
                }
            }
        }

        $final_formats = array_values($processed_formats); // Convert associative array to indexed array

        // 4. Sort the formats
        usort($final_formats, function($a, $b) {
            if ($a['category'] !== $b['category']) {
                return $a['category'] === 'audio' ? -1 : 1;
            }
            if ($a['category'] === 'video') {
                preg_match('/(\d+)p/', $a['resolution_or_bitrate'], $a_matches);
                preg_match('/(\d+)p/', $b['resolution_or_bitrate'], $b_matches);
                $a_height = isset($a_matches[1]) ? (int)$a_matches[1] : 0;
                $b_height = isset($b_matches[1]) ? (int)$b_matches[1] : 0;

                if ($a_height !== $b_height) {
                    return $b_height - $a_height;
                }
                // Prioritize combined/muxed formats
                $a_is_combined = ($a['type'] === 'audiovideo_muxed' || $a['type'] === 'audiovideo');
                $b_is_combined = ($b['type'] === 'audiovideo_muxed' || $b['type'] === 'audiovideo');
                if ($a_is_combined && !$b_is_combined) return -1;
                if (!$a_is_combined && $b_is_combined) return 1;
            }
            if ($a['category'] === 'audio') {
                if ($a['id'] === 'mp3') return -1;
                if ($b['id'] === 'mp3') return 1;
                $a_br = (int)filter_var($a['resolution_or_bitrate'], FILTER_SANITIZE_NUMBER_INT);
                $b_br = (int)filter_var($b['resolution_or_bitrate'], FILTER_SANITIZE_NUMBER_INT);
                if ($a_br !== $b_br) {
                    return $b_br - $a_br;
                }
            }
            return 0;
        });

        // Safely determine thumbnail URL and duration string from the original $video_info
        $thumb_from_video_info = $video_info['thumbnail'] ?? null;
        if (!$thumb_from_video_info && isset($video_info['thumbnails']) && is_array($video_info['thumbnails']) && count($video_info['thumbnails']) > 0 && isset($video_info['thumbnails'][0]['url'])) {
            $thumb_from_video_info = $video_info['thumbnails'][0]['url'];
        }

        return [
            // Title, thumbnail, and duration_string are sanitized if they come from $video_info,
            // which should now consistently use sanitize_input on its sources or be trusted yt-dlp JSON structure.
            // The $video_info itself comes from yt-dlp and is generally structured data.
            // Title specifically needs sanitization if displayed.
            'title' => isset($video_info['title']) ? sanitize_input($video_info['title']) : _t('title_not_available', 'N/A'),
            'thumbnail_url' => isset($thumb_from_video_info) ? sanitize_input($thumb_from_video_info) : '',
            'duration_string' => isset($video_info['duration_string']) ? sanitize_input($video_info['duration_string']) : _t('duration_not_available', 'N/A'),
            'formats' => $final_formats,
        ];
    }


    /**
     * Streams media content (video or audio) for a given YouTube URL and format ID.
     * This method directly outputs the media stream to the client and sets appropriate HTTP headers
     * for download. It terminates execution after streaming.
     *
     * @param string $url The YouTube video URL.
     * @param string $format_id The specific format ID to download (e.g., 'mp3', '137', 'VIDEO_ID+AUDIO_ID').
     * @param string $video_title The title of the video, used to generate the downloaded filename.
     * @param string $fallback_extension The file extension to use if it cannot be determined from the format ID. Defaults to 'mp4'.
     * @return void This method does not return a value as it terminates script execution.
     */
    public function streamMedia($url, $format_id, $video_title, $fallback_extension = 'mp4') {
        // Sanitize inputs
        $sanitized_url = sanitize_input($url);
        if (empty($sanitized_url) || !filter_var($sanitized_url, FILTER_VALIDATE_URL)) {
            error_log("YtDlpWrapper::streamMedia: Invalid URL provided: " . $url);
            // Optionally, display an error page or message here instead of a raw die.
            die(_t('error_invalid_url_streaming', 'Invalid URL provided for streaming.'));
        }

        $sanitized_format_id = sanitize_input($format_id);
        // Basic validation for format_id (alphanumeric, '+', '-', '_').
        // This is important as it's used in a shell command. escapeshellarg is used later for the format string part.
        if (!preg_match('/^[a-zA-Z0-9_+\-]+$/', $sanitized_format_id)) {
             error_log("YtDlpWrapper::streamMedia: Invalid format_id provided: " . $format_id);
             die(_t('error_invalid_format_id_streaming', 'Invalid format ID.'));
        }

        $sanitized_video_title = sanitize_input($video_title); // Already good for filename
        $video_title_for_filename = preg_replace('/[^A-Za-z0-9_\-.]/', '_', $sanitized_video_title);


        $escaped_url = escapeshellarg($sanitized_url);
        $final_extension = sanitize_input($fallback_extension); // Sanitize fallback_extension
        $command_options = "";

        if ($sanitized_format_id === 'mp3') {
            $command_options = "-f bestaudio -x --audio-format mp3";
            $final_extension = 'mp3';
        } else {
            // Format ID is now part of $command_options string that will be escaped as a whole if needed,
            // but individual parts like format_id should be safe.
            // Using escapeshellarg on the format_id part of the -f option.
            $escaped_format_id_option = escapeshellarg($sanitized_format_id);
            if (strpos($sanitized_format_id, '+') !== false) {
                $command_options = "-f {$escaped_format_id_option}";
                $final_extension = 'mp4';
            } else {
                $command_options = "-f {$escaped_format_id_option}";
                // Attempt to determine the correct extension
                // Note: Calling getVideoInfo here again is inefficient.
                // Consider passing extension or relying on fallback for non-standard/muxed cases.
                // For now, kept original logic but with sanitization.
                $video_data_for_ext_check = $this->getVideoInfo($sanitized_url); // uses sanitized URL
                if (!isset($video_data_for_ext_check['error_message']) && isset($video_data_for_ext_check['formats']) && is_array($video_data_for_ext_check['formats'])) {
                    foreach ($video_data_for_ext_check['formats'] as $fmt) {
                        if (isset($fmt['format_id']) && $fmt['format_id'] === $sanitized_format_id && isset($fmt['ext'])) {
                            $final_extension = sanitize_input($fmt['ext']); // Sanitize extension from yt-dlp
                            break;
                        }
                    }
                }
            }
        }

        $filename = $video_title_for_filename . '.' . $final_extension;
        // Added --no-warnings, --socket-timeout, --retries for streaming
        $full_command = "{$this->yt_dlp_path} {$command_options} --no-warnings --socket-timeout 30 --retries 3 -o - {$escaped_url}";

        // Ensure headers are not already sent
        if (headers_sent($file, $line)) {
            error_log("YtDlpWrapper::streamMedia: Headers already sent in $file on line $line. Cannot stream file.");
            die(_t('error_headers_sent_streaming', 'Cannot stream file: Headers already sent.'));
        }

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');

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
        $sanitized_query = sanitize_input($query);
        if (empty($sanitized_query)) {
            return ['error_message' => _t('error_empty_search_query', 'Search query cannot be empty.')];
        }

        // Use escapeshellarg for the query part that is embedded in the ytsearch string.
        // The number of results "ytsearch5:" can be made configurable if needed.
        // Added --extractor-retries 3 and --socket-timeout 30
        $command = sprintf("%s \"ytsearch5:%s\" --dump-json --no-playlist --no-warnings --extractor-retries 3 --socket-timeout 30", $this->yt_dlp_path, escapeshellarg($sanitized_query));

        $exec_result = $this->executeCommand($command);

        if ($exec_result['error']) {
            error_log("YtDlpWrapper::searchVideos: Error executing search. Type: {$exec_result['error']}. Message: {$exec_result['message']}. Query: {$sanitized_query}");
            return ['error_message' => _t('error_yt_dlp_search_execution_failed', 'Failed to execute video search: ' . $exec_result['message'])];
        }

        $search_output_json_lines = $exec_result['output'];
        if (empty(trim($search_output_json_lines))) {
            // This is not necessarily an error, could be no results found.
            // Log for debugging, but return empty results array.
            error_log("YtDlpWrapper::searchVideos: yt-dlp returned empty output for query: " . $sanitized_query);
            return [];
        }

        $results = [];
        $lines = explode("\n", trim($search_output_json_lines));

        foreach ($lines as $line) {
            if (empty(trim($line))) {
                continue;
            }
            $video_data = json_decode($line, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($video_data) && isset($video_data['id'])) {
                // All individual fields from yt-dlp output that are displayed to user should be sanitized.
                $id = sanitize_input($video_data['id'] ?? null);

                $title = sanitize_input($video_data['title'] ?? _t('search_title_na', 'N/A'));

                $thumbnail_url = ''; // Default empty
                if (!empty($video_data['thumbnail'])) {
                    $thumbnail_url = sanitize_input($video_data['thumbnail']);
                } elseif (isset($video_data['thumbnails']) && is_array($video_data['thumbnails']) && !empty($video_data['thumbnails'])) {
                    // Try to get the last thumbnail in the list (often highest quality)
                    $last_thumbnail = end($video_data['thumbnails']);
                    if (isset($last_thumbnail['url'])) {
                        $thumbnail_url = sanitize_input($last_thumbnail['url']);
                    } elseif (isset($video_data['thumbnails'][0]['url'])) { // Fallback to first if 'url' in last is not set
                        $thumbnail_url = sanitize_input($video_data['thumbnails'][0]['url']);
                    }
                }

                $uploader = sanitize_input($video_data['uploader'] ?? _t('search_uploader_na', 'N/A'));
                $duration_string = sanitize_input($video_data['duration_string'] ?? _t('search_duration_na', 'N/A'));
                $youtube_url = 'https://www.youtube.com/watch?v=' . $id; // id is already sanitized

                $results[] = [
                    'id' => $id,
                    'title' => $title,
                    'thumbnail_url' => $thumbnail_url,
                    'uploader' => $uploader,
                    'duration_string' => $duration_string,
                    'url' => $youtube_url
                ];
            } else {
                error_log("YtDlpWrapper::searchVideos: Failed to parse JSON line or missing 'id' for query '{$sanitized_query}'. Error: " . json_last_error_msg() . ". Line: " . substr($line, 0, 200));
                // Optionally, skip this entry or add a placeholder with an error.
                // For now, just skip.
                continue;
            }
        }
        return $results; // Contains sanitized data
    }
}

// Add other common functions here as the project evolves.
