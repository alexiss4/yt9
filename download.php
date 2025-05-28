<?php
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    include("includes/functions.php");
    include("includes/header.php"); // For consistent UI, will only output if no download

    if (!isset($_GET['url'])) {
        echo "<main class='container mx-auto mt-8 p-4'><div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative' role='alert'>No URL provided.</div></main>";
        include("includes/footer.php");
        exit;
    }

    $url = sanitize_input($_GET['url']);

    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        echo "<main class='container mx-auto mt-8 p-4'><div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative' role='alert'>Invalid URL format.</div></main>";
        include("includes/footer.php");
        exit;
    }

    if (!preg_match('/(youtube\.com\/watch\?v=|youtu\.be\/)/', $url)) {
        echo "<main class='container mx-auto mt-8 p-4'><div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative' role='alert'>Invalid YouTube URL provided. Please use a valid YouTube video link (e.g., youtube.com/watch?v=...).</div></main>";
        include("includes/footer.php");
        exit;
    }

    $validated_url = $url;

    // Check for JSON request
    if (isset($_GET['json']) && $_GET['json'] == '1' || isset($_GET['ajax']) && $_GET['ajax'] == '1') {
        $info_command = "yt-dlp -j --skip-download " . escapeshellarg($validated_url);
        $video_info_json = shell_exec($info_command);

        if (!$video_info_json) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Could not retrieve video information. The video may be private, deleted, copyrighted, or the URL is incorrect.']);
            exit;
        }

        $video_info = json_decode($video_info_json, true);

        if (!$video_info || !isset($video_info['title'])) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Failed to parse video information. The video data might be malformed.']);
            exit;
        }

        $output = [
            'title' => $video_info['title'],
            'thumbnail' => $video_info['thumbnail'] ?? ($video_info['thumbnails'][0]['url'] ?? null), // Get first thumbnail if 'thumbnail' key doesn't exist
            'formats' => []
        ];

        // Add MP3 option
        $output['formats'][] = [
            'format_id' => 'mp3',
            'ext' => 'mp3',
            'resolution' => 'Audio',
            'format_note' => 'MP3 (Best Available Audio)',
            'description' => 'Audio MP3 (Best Available)',
            'filesize_approx' => null // Filesize for MP3 is not easily predetermined without conversion
        ];

        foreach ($video_info['formats'] as $format) {
            $format_note = $format['format_note'] ?? 'N/A';
            $resolution = $format['resolution'] ?? ($format['abr'] ? $format['abr'] . 'kbps' : 'Audio');
            $ext = $format['ext'];
            $format_id = $format['format_id'];
            $filesize_approx = isset($format['filesize_approx']) ? round($format['filesize_approx'] / (1024*1024), 2) . " MB" : (isset($format['filesize']) ? round($format['filesize'] / (1024*1024), 2) . " MB" : "N/A");
            
            $description = "{$ext} - {$resolution}";
            if ($format_note !== 'N/A') {
                $description .= " ({$format_note})";
            }
            if ($filesize_approx !== "N/A") {
                 $description .= " [{$filesize_approx}]";
            }


            // Add common video formats
            if (strpos($ext, 'mp4') !== false && strpos($resolution, 'x') !== false) { // Video format
                if (in_array($format_note, ['360p', '480p', '720p', '1080p', '1440p', '2160p'])) {
                     $output['formats'][] = [
                        'format_id' => $format_id,
                        'ext' => $ext,
                        'resolution' => $resolution,
                        'format_note' => $format_note,
                        'description' => "Video {$ext} {$format_note} ({$resolution}) - {$filesize_approx}",
                        'filesize_approx' => $filesize_approx
                    ];
                }
            } elseif (strpos($format_id, 'm4a') !== false || strpos($ext, 'm4a') !== false) {
                 $output['formats'][] = [
                    'format_id' => $format_id,
                    'ext' => $ext,
                    'resolution' => $resolution,
                    'format_note' => $format_note,
                    'description' => "Audio {$ext} {$resolution} - {$filesize_approx}",
                    'filesize_approx' => $filesize_approx
                ];
            }
        }
        
        // Ensure unique formats by format_id, preferring earlier entries (like our manual MP3)
        $unique_formats = [];
        foreach ($output['formats'] as $fmt) {
            if (!isset($unique_formats[$fmt['format_id']])) {
                $unique_formats[$fmt['format_id']] = $fmt;
            }
        }
        $output['formats'] = array_values($unique_formats);


        header('Content-Type: application/json');
        echo json_encode($output);
        exit;
    }
    // End of JSON request check

    if (isset($_GET['format_id'])) {
        $format_id = sanitize_input($_GET['format_id']);
        // Fetch title again for the download, or ideally, pass it through
        // For now, let's re-fetch, though this is inefficient
        $info_command = "yt-dlp -j --skip-download " . escapeshellarg($validated_url);
        $video_info_json = shell_exec($info_command);
        
        if (!$video_info_json) {
            // Error: Could not execute yt-dlp or video not found
            // Since this is inside format_id check, we assume URL was valid before
            // This might happen if video becomes unavailable between page loads
            header("HTTP/1.1 500 Internal Server Error");
            echo "<main class='container mx-auto mt-8 p-4'><div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative' role='alert'>Error: Could not re-fetch video information for download.</div></main>";
            include("includes/footer.php");
            exit;
        }

        $video_info = json_decode($video_info_json, true);

        if (!$video_info || !isset($video_info['title'])) {
            header("HTTP/1.1 500 Internal Server Error");
            echo "<main class='container mx-auto mt-8 p-4'><div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative' role='alert'>Error: Could not parse video information for download.</div></main>";
            include("includes/footer.php");
            exit;
        }
        $video_title = $video_info['title'];
        $original_extension = 'mp4'; // Default

        // Determine extension
        foreach ($video_info['formats'] as $format) {
            if ($format['format_id'] === $format_id) {
                $original_extension = $format['ext'];
                break;
            }
        }
        
        // Special handling for audio download request
        $download_command = "";
        $final_extension = $original_extension;

        if ($format_id === 'mp3') {
            $download_command = "yt-dlp -f bestaudio -x --audio-format mp3 -o - " . escapeshellarg($validated_url);
            $final_extension = 'mp3';
        } else {
            $download_command = "yt-dlp -f " . escapeshellarg($format_id) . " -o - " . escapeshellarg($validated_url);
        }

        // Clean up title for filename
        $filename_title = preg_replace('/[^A-Za-z0-9_\-]/', '_', $video_title);
        $filename = $filename_title . '.' . $final_extension;

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        // Ensure no output before passthru
        ob_end_flush(); // End output buffering if any was started by header.php

        passthru($download_command);
        exit;

    } else {
        // PHP part that lists formats (HTML fallback)
        echo '<div id="php-format-list">';
        // Fetch and display format options
        $info_command = "yt-dlp -j --skip-download " . escapeshellarg($validated_url);
        $video_info_json = shell_exec($info_command);

        if (!$video_info_json) {
            echo "<main class='container mx-auto mt-8 p-4'><div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative' role='alert'>Could not retrieve video information. The video may be private, deleted, copyrighted, or the URL is incorrect.</div></main>";
            include("includes/footer.php");
            exit;
        }

        $video_info = json_decode($video_info_json, true);

        if (!$video_info || !isset($video_info['title'])) {
            echo "<main class='container mx-auto mt-8 p-4'><div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative' role='alert'>Failed to parse video information. The video data might be malformed.</div></main>";
            include("includes/footer.php");
            exit;
        }

        $video_title = htmlspecialchars($video_info['title']);
        ?>
        <main class="container mx-auto mt-8 p-4">
            <div class="bg-white shadow-lg rounded-lg p-6">
                <h1 class="text-2xl font-bold text-gray-800 mb-2">Download Video: <?php echo $video_title; ?></h1>
                <img src="<?php echo htmlspecialchars($video_info['thumbnail'] ?? ''); ?>" alt="Video Thumbnail" class="my-4 rounded-lg" style="max-width:320px; margin-left:auto; margin-right:auto;">
                <h2 class="text-xl font-semibold text-gray-700 mb-4">Available Formats:</h2>
                <ul class="space-y-3">
                    <?php
                    $found_mp4 = false;
                    $found_audio = false;

                    // Offer MP3 download option
                    echo '<li class="p-3 bg-gray-50 rounded-md shadow-sm hover:bg-gray-100 transition duration-150">';
                    echo '<a href="download.php?url=' . urlencode($validated_url) . '&format_id=mp3" class="block text-blue-600 hover:text-blue-800">';
                    echo '<strong>Audio:</strong> MP3 (Best Available Audio)';
                    echo '<span class="text-sm text-gray-500 block">Download as MP3</span>';
                    echo '</a></li>';
                    $found_audio = true;


                    foreach ($video_info['formats'] as $format) {
                        $format_note = isset($format['format_note']) ? htmlspecialchars($format['format_note']) : 'N/A';
                        $resolution = isset($format['resolution']) ? htmlspecialchars($format['resolution']) : 'Audio';
                        $ext = htmlspecialchars($format['ext']);
                        $format_id = htmlspecialchars($format['format_id']);
                        $filesize_approx = isset($format['filesize_approx']) ? round($format['filesize_approx'] / (1024*1024), 2) . " MB" : (isset($format['filesize']) ? round($format['filesize'] / (1024*1024), 2) . " MB" : "N/A");


                        // Prioritize MP4 videos and common audio like M4A
                        if (strpos($ext, 'mp4') !== false && strpos($resolution, 'x') !== false) { // Video format
                             if (in_array($format_note, ['360p', '480p', '720p', '1080p'])) {
                                echo '<li class="p-3 bg-gray-50 rounded-md shadow-sm hover:bg-gray-100 transition duration-150">';
                                echo '<a href="download.php?url=' . urlencode($validated_url) . '&format_id=' . urlencode($format_id) . '" class="block text-blue-600 hover:text-blue-800">';
                                echo "<strong>Video:</strong> {$resolution} ({$format_note}) - {$ext} ({$filesize_approx})";
                                echo "<span class='text-sm text-gray-500 block'>ID: {$format_id}</span>";
                                echo '</a></li>';
                                $found_mp4 = true;
                             }
                        }
                    }

                    if (!$found_mp4 && !$found_audio) {
                        echo "<li class='text-gray-500'>No suitable MP4 video or M4A/MP3 audio formats found. You can check all available formats below.</li>";
                    }

                    // Fallback: List all formats if no specific ones were found or for debugging
                    if (empty($video_info['formats'])) {
                         echo "<li class='text-gray-500'>No download formats listed by yt-dlp.</li>";
                    } else if (!$found_mp4 && !$found_audio){ // Only show all if specific ones not found
                        echo "<h3 class='text-lg font-semibold text-gray-700 mt-6 mb-3'>All Available Formats (for advanced users):</h3>";
                        foreach ($video_info['formats'] as $format) {
                            $description = isset($format['format']) ? htmlspecialchars($format['format']) : $format['format_id'];
                            $ext = htmlspecialchars($format['ext']);
                            $format_id = htmlspecialchars($format['format_id']);
                            $filesize_approx = isset($format['filesize_approx']) ? round($format['filesize_approx'] / (1024*1024), 2) . " MB" : (isset($format['filesize']) ? round($format['filesize'] / (1024*1024), 2) . " MB" : "N/A");

                            echo '<li class="p-3 bg-gray-50 rounded-md shadow-sm hover:bg-gray-100 transition duration-150">';
                            echo '<a href="download.php?url=' . urlencode($validated_url) . '&format_id=' . urlencode($format_id) . '" class="block text-blue-600 hover:text-blue-800">';
                            echo "{$description} - {$ext} ({$filesize_approx})";
                            echo '</a></li>';
                        }
                    }
                    ?>
                </ul>
            </div>
        </main>
        <?php
        echo '</div>'; // End of #php-format-list
    } // End of else (format_id not set)

    // Footer should be included only if not a JSON request and not a direct file download
    if (!(isset($_GET['json']) && $_GET['json'] == '1') && !(isset($_GET['ajax']) && $_GET['ajax'] == '1') && !isset($_GET['format_id'])) {
        include("includes/footer.php");
    } elseif (isset($_GET['format_id'])) {
        // If it's a download request, the script exits earlier, but as a fallback:
        // No footer output during direct download.
    }
?>
