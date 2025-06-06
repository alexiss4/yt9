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
        $escaped_url = escapeshellarg($url);
        $command = "{$this->yt_dlp_path} -j --skip-download --no-playlist --no-warnings {$escaped_url}";
        $json_output = $this->executeCommand($command);

        if (!$json_output) {
            return ['error' => _t('error_yt_dlp_execution_failed', 'Failed to execute video information command. Please check server configuration or yt-dlp installation.')];
        }

        $video_info = json_decode($json_output, true);
        if (json_last_error() !== JSON_ERROR_NONE || !$video_info || !isset($video_info['title'])) {
            error_log("YtDlpWrapper::getVideoInfo: Failed to parse JSON. Error: " . json_last_error_msg() . ". JSON Output: " . substr($json_output, 0, 1000));
            return ['error' => _t('error_parsing_video_info_json', 'Failed to parse video information. The data may be malformed or incomplete.')];
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
        $video_info = $this->getVideoInfo($url);
        if (isset($video_info['error'])) {
            return $video_info; // Propagate error
        }

        $output_formats = [];
        $raw_formats = $video_info['formats'] ?? [];
        // error_log("YtDlpWrapper - Raw formats from yt-dlp (before filtering): " . substr(print_r($raw_formats, true), 0, 10000)); // Per instruction, this one should be commented/deleted

        // Helper function to format filesize
        $format_filesize = function($bytes) {
            if (!is_numeric($bytes) || $bytes <= 0) return 'N/A';
            $sz = 'BKMGTP';
            $factor = floor((strlen((string)$bytes) - 1) / 3);
            return sprintf("%.2f%s", $bytes / pow(1024, $factor), $sz[(int)$factor]);
        };

        // 1. Add MP3 Option (explicit conversion)
        $output_formats[] = [
            'id' => 'mp3',
            'type' => 'audioonly',
            'category' => 'audio',
            'label' => _t('format_mp3_download', 'MP3 Audio'),
            'ext' => 'mp3',
            'resolution_or_bitrate' => _t('best_available_audio', 'Best Available'),
            'filesize_str' => 'N/A',
            'has_audio' => true,
            'has_video' => false,
            'yt_dlp_format_obj' => ['note' => 'MP3 conversion via yt-dlp -x']
        ];

        // 2. Process raw formats from yt-dlp
        $desired_resolutions = ['2160p', '1440p', '1080p', '720p', '480p', '360p'];
        $seen_video_audio_muxed = [];

        foreach ($raw_formats as $format) {
            $format_id = $format['format_id'] ?? null;
            if (!$format_id) continue;

            $ext = $format['ext'] ?? 'N/A';
            $protocol = $format['protocol'] ?? '';
            if (!in_array($protocol, ['http', 'https'])) {
                continue;
            }

            $vcodec = $format['vcodec'] ?? 'none';
            $acodec = $format['acodec'] ?? 'none';
            $format_note = $format['format_note'] ?? ($format['resolution'] ?? null);
            $height = (isset($format['height']) && is_numeric($format['height'])) ? (int)$format['height'] : null;

            $filesize = $format['filesize'] ?? ($format['filesize_approx'] ?? null);
            $filesize_str = $format_filesize($filesize);

            $current_format_entry = null;

            if ($ext === 'mp4' && $vcodec !== 'none' && $acodec !== 'none' && $height && $format_note && in_array($format_note, $desired_resolutions)) {
                if (!isset($seen_video_audio_muxed[$format_note])) {
                     $current_format_entry = [
                        'id' => $format_id,
                        'type' => 'audiovideo', // Pre-muxed by YouTube
                        'category' => 'video',
                        'label' => 'MP4 ' . $format_note,
                        'ext' => 'mp4',
                        'resolution_or_bitrate' => $format_note,
                        'filesize_str' => $filesize_str,
                        'has_audio' => true,
                        'has_video' => true,
                        'yt_dlp_format_obj' => $format
                    ];
                    $seen_video_audio_muxed[$format_note] = true;
                }
            }
            else if ($ext === 'mp4' && $vcodec !== 'none' && $acodec === 'none' && $height && $format_note && in_array($format_note, $desired_resolutions)) {
                $current_format_entry = [
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
            }
            /* // Start of block comment for M4A/Opus audio-only streams
            else if ($vcodec === 'none' && $acodec !== 'none' && ($ext === 'm4a' || ($ext === 'webm' && strpos($acodec, 'opus') !== false))) {
                $bitrate = $format['abr'] ?? null;
                $label_detail = $ext === 'm4a' ? 'M4A' : 'Opus';
                if ($bitrate) {
                    $label_detail .= ' ~' . round($bitrate) . 'kbps';
                }
                // This $current_format_entry would be for M4A/Opus audio if not commented out
                // $current_format_entry = [
                //     'id' => $format_id,
                //     'type' => 'audioonly',
                //     'category' => 'audio',
                //     'label' => $label_detail . ' (' . _t('audio_only_label', 'Audio Only') . ')',
                //     'ext' => $ext,
                //     'resolution_or_bitrate' => $bitrate ? round($bitrate) . 'kbps' : 'N/A',
                //     'filesize_str' => $filesize_str,
                //     'has_audio' => true,
                //     'has_video' => false,
                //     'yt_dlp_format_obj' => $format
                // ];
                // if ($current_format_entry) { $output_formats[] = $current_format_entry; }
            }
            */ // End of block comment

            // Add the $current_format_entry to $output_formats if it was set (i.e., for video types from previous if/else if)
            if ($current_format_entry) {
                $output_formats[] = $current_format_entry;
            }
        }

        // New section to create muxable video+audio combinations:
        $muxable_video_formats = [];
        // Search for the best M4A audio stream from RAW formats for muxing purposes
        $best_m4a_for_muxing = null;
        foreach ($raw_formats as $fmt_check) { // Iterate raw_formats
            if (($fmt_check['ext'] ?? '') === 'm4a' && ($fmt_check['vcodec'] ?? 'none') === 'none' && ($fmt_check['acodec'] ?? 'none') !== 'none') {
                if ($best_m4a_for_muxing === null) { // Simple selection: first M4A found
                    $best_m4a_for_muxing = [ // Store only essential details needed for muxing logic
                        'id' => $fmt_check['format_id'] ?? null,
                        'filesize' => $fmt_check['filesize'] ?? ($fmt_check['filesize_approx'] ?? 0),
                        'yt_dlp_format_obj' => $fmt_check // Keep original object
                    ];
                    // Ideally, compare bitrates (abr) to find the actual best M4A here.
                    // For now, first one found is fine for testing the logic.
                    // break; // Found one, use it. // Let's iterate all to find best based on ABR
                } else {
                    $current_abr_check = $fmt_check['abr'] ?? 0;
                    $best_abr_so_far = $best_m4a_for_muxing['yt_dlp_format_obj']['abr'] ?? 0;
                    if ($current_abr_check > $best_abr_so_far) {
                         $best_m4a_for_muxing = [
                            'id' => $fmt_check['format_id'] ?? null,
                            'filesize' => $fmt_check['filesize'] ?? ($fmt_check['filesize_approx'] ?? 0),
                            'yt_dlp_format_obj' => $fmt_check
                        ];
                    }
                }
            }
        }

        // Populate $muxable_video_formats from $output_formats (which now only contains video types after audio filtering)
        foreach ($output_formats as $fmt) {
            if ($fmt['type'] === 'videoonly' && $fmt['ext'] === 'mp4') {
                preg_match('/(\d+)p/', $fmt['resolution_or_bitrate'], $matches);
                $height_mux = isset($matches[1]) ? (int)$matches[1] : 0;
                if ($height_mux > 0) {
                    $muxable_video_formats[] = ['height' => $height_mux, 'format' => $fmt];
                }
            }
        }


        if ($best_m4a_for_muxing && !empty($muxable_video_formats)) {
            usort($muxable_video_formats, function($a, $b) {
                return $b['height'] - $a['height'];
            });

            $resolutions_to_mux = ['1080p', '720p', '480p', '360p'];
            $muxed_formats_added_resolution = [];

            foreach ($muxable_video_formats as $video_entry) {
                $video_format = $video_entry['format'];
                $video_resolution_note = $video_format['resolution_or_bitrate'];

                if (in_array($video_resolution_note, $resolutions_to_mux) && !isset($muxed_formats_added_resolution[$video_resolution_note])) {
                    $combined_id = $video_format['id'] . '+' . $best_m4a_for_muxing['id'];

                    $video_filesize_bytes = $video_format['yt_dlp_format_obj']['filesize'] ?? ($video_format['yt_dlp_format_obj']['filesize_approx'] ?? 0);
                    $audio_filesize_bytes = $best_m4a_for_muxing['filesize']; // Use filesize from our prepared $best_m4a_for_muxing
                    $combined_filesize_str = ($video_filesize_bytes > 0 && $audio_filesize_bytes > 0) ? $format_filesize($video_filesize_bytes + $audio_filesize_bytes) : 'N/A';

                    $output_formats[] = [ // Add to the main $output_formats array before final filtering and sorting
                        'id' => $combined_id,
                        'type' => 'audiovideo_muxed',
                        'category' => 'video',
                        'label' => 'MP4 ' . $video_resolution_note . ' (' . _t('muxed_audio_label', 'Best Audio') . ')',
                        'ext' => 'mp4',
                        'resolution_or_bitrate' => $video_resolution_note,
                        'filesize_str' => $combined_filesize_str,
                        'has_audio' => true,
                        'has_video' => true,
                        'yt_dlp_format_obj' => ['video_stream' => $video_format['yt_dlp_format_obj'], 'audio_stream' => $best_m4a_for_muxing['yt_dlp_format_obj']]
                    ];
                    $muxed_formats_added_resolution[$video_resolution_note] = true;
                }
            }
        }

        $final_formats = [];
        $seen_ids = [];
        foreach ($output_formats as $fmt) {
            if (!isset($seen_ids[$fmt['id']])) {
                $final_formats[] = $fmt;
                $seen_ids[$fmt['id']] = true;
            }
        }

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

        // Ensure the temporary log for JS formats is active for testing this new structure
        // error_log("YtDlpWrapper - Final Formats (for JS with Muxing): " . print_r($final_formats, true)); // Per instruction, this one should be commented/deleted

        // Safely determine thumbnail URL and duration string from the original $video_info
        $thumb_from_video_info = $video_info['thumbnail'] ?? null;
        if (!$thumb_from_video_info && isset($video_info['thumbnails']) && is_array($video_info['thumbnails']) && count($video_info['thumbnails']) > 0 && isset($video_info['thumbnails'][0]['url'])) {
            $thumb_from_video_info = $video_info['thumbnails'][0]['url'];
        }

        return [
            'title' => sanitize_input($video_info['title']),
            'thumbnail_url' => sanitize_input($thumb_from_video_info ?? ''),
            'duration_string' => sanitize_input($video_info['duration_string'] ?? _t('duration_not_available', 'N/A')),
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
        $escaped_url = escapeshellarg($url);
        $video_title_sanitized = preg_replace('/[^A-Za-z0-9_\-]/', '_', $video_title);

        $final_extension = $fallback_extension;
        $command_options = "";

        if ($format_id === 'mp3') {
            $command_options = "-f bestaudio -x --audio-format mp3";
            $final_extension = 'mp3';
        } else {
            // If $format_id contains '+', it's a muxed request (e.g., "VIDEO_ID+AUDIO_ID")
            if (strpos($format_id, '+') !== false) {
                // yt-dlp handles "ID+ID" format string directly for muxing
                $command_options = "-f " . escapeshellarg($format_id);
                $final_extension = 'mp4'; // Muxed output is typically MP4
            } else {
                // Standard single format ID
                $command_options = "-f " . escapeshellarg($format_id);
                // Attempt to determine the correct extension if not MP3 or muxed
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
        }

        $filename = $video_title_sanitized . '.' . $final_extension;
        $full_command = "{$this->yt_dlp_path} {$command_options} -o - {$escaped_url}";

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
        $command = sprintf("%s \"ytsearch5:%s\" --dump-json --no-playlist --no-warnings", $this->yt_dlp_path, escapeshellarg($query));
        $search_output_json_lines = $this->executeCommand($command);

        if ($search_output_json_lines === false) {
            return ['error' => _t('error_yt_dlp_search_execution_failed', 'Failed to execute video search command. Please check server configuration or yt-dlp installation.')];
        }

        $results = [];
        $lines = explode("\n", trim($search_output_json_lines));

        foreach ($lines as $line) {
            if (empty(trim($line))) {
                continue;
            }
            $video_data = json_decode($line, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($video_data) && isset($video_data['id'])) {
                $id = $video_data['id'] ?? null;

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
                    'thumbnail_url' => sanitize_input($thumbnail ?? ''),
                    'uploader' => sanitize_input($uploader),
                    'duration_string' => sanitize_input($duration_string),
                    'url' => 'https://www.youtube.com/watch?v=' . sanitize_input($id)
                ];
            } else {
                error_log("YtDlpWrapper::searchVideos: Failed to parse JSON line or missing 'id'. Error: " . json_last_error_msg() . ". Line: " . $line);
                continue;
            }
        }
        return $results;
    }
}

// Add other common functions here as the project evolves.
