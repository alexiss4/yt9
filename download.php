<?php
    require_once __DIR__ . '/config.php';
    require_once __DIR__ . '/includes/functions.php';
    
    $ytDlp = new YtDlpWrapper(); // Instantiate the wrapper

    // Header is included conditionally later or not at all if direct download.
    // require_once __DIR__ . '/includes/header.php'; 

    // SECTION: Initial URL validation and sanitization
    if (!isset($_GET['url'])) {
        // Conditional header/footer for error page display
        if (!headers_sent()) { require_once __DIR__ . '/includes/header.php'; }
        echo "<main class='container mx-auto mt-8 p-4'><div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative' role='alert'>No URL provided.</div></main>";
        if (file_exists(__DIR__ . "/includes/footer.php")) { require_once __DIR__ . "/includes/footer.php"; }
        exit;
    }

    $url = sanitize_input($_GET['url']);

    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        if (!headers_sent()) { require_once __DIR__ . '/includes/header.php'; }
        echo "<main class='container mx-auto mt-8 p-4'><div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative' role='alert'>Invalid URL format.</div></main>";
        if (file_exists(__DIR__ . "/includes/footer.php")) { require_once __DIR__ . "/includes/footer.php"; }
        exit;
    }

    // Basic YouTube URL pattern check
    if (!preg_match('/(youtube\.com\/watch\?v=|youtu\.be\/)/', $url)) {
        if (!headers_sent()) { require_once __DIR__ . '/includes/header.php'; }
        echo "<main class='container mx-auto mt-8 p-4'><div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative' role='alert'>Invalid YouTube URL provided. Please use a valid YouTube video link (e.g., youtube.com/watch?v=...).</div></main>";
        if (file_exists(__DIR__ . "/includes/footer.php")) { require_once __DIR__ . "/includes/footer.php"; }
        exit;
    }

    $validated_url = $url;
    // END SECTION: Initial URL validation

    // SECTION: Handle JSON API request for video info
    if (isset($_GET['json']) && $_GET['json'] == '1' || isset($_GET['ajax']) && $_GET['ajax'] == '1') {
        $videoDetails = $ytDlp->getFormattableVideoInfo($validated_url); 

        if (isset($videoDetails['error'])) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['error' => $videoDetails['error']]);
            exit;
        }
        header('Content-Type: application/json');
        echo json_encode($videoDetails);
        exit;
    }
    // END SECTION: JSON API request

    // SECTION: Prepare for HTML page display or direct download
    // Header is included here for non-JSON requests if not a direct download yet.
    // If it's a direct download (format_id is set), header output is suppressed until/unless an error occurs.
    if (!isset($_GET['format_id'])) { 
        require_once __DIR__ . '/includes/header.php';
    }

    // SECTION: Handle direct media download
    if (isset($_GET['format_id'])) {
        $format_id = sanitize_input($_GET['format_id']);
        
        $video_data = $ytDlp->getVideoInfo($validated_url);

        // If fetching video info for title/extension fails, display error page
        if (isset($video_data['error']) || !isset($video_data['title'])) {
            ob_start(); 
            if (!headers_sent()) { // Ensure header is loaded for error messages if not already sent
                 require_once __DIR__ . "/includes/header.php";
            }
            $error_message_display = isset($video_data['error']) ? $video_data['error'] : _t('error_fetch_video_info_download', 'Error: Could not re-fetch video information for download.');
            echo "<main class='container mx-auto mt-8 p-4'><div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative' role='alert'>".htmlspecialchars($error_message_display)."</div></main>";
            if (file_exists(__DIR__ . "/includes/footer.php")) {
                require_once __DIR__ . "/includes/footer.php";
            }
            if(ob_get_level() > 0) ob_end_flush(); // Flush buffered output
            exit;
        }
        
        $video_title = $video_data['title'];
        $original_extension = 'mp4'; // Default extension

        // Determine the correct extension from format data if not MP3
        if ($format_id !== 'mp3' && isset($video_data['formats']) && is_array($video_data['formats'])) {
            foreach ($video_data['formats'] as $format_entry) {
                if (isset($format_entry['format_id']) && $format_entry['format_id'] === $format_id && isset($format_entry['ext'])) {
                    $original_extension = $format_entry['ext'];
                    break;
                }
            }
        }
        
        // Stream the media
        $ytDlp->streamMedia($validated_url, $format_id, $video_title, $original_extension);
        exit; 
    // END SECTION: Direct media download
    
    } else {
        // SECTION: HTML Fallback Mode (display available formats on page)
        $videoDetailsForHtml = $ytDlp->getFormattableVideoInfo($validated_url);

        echo '<div id="php-format-list">'; // Container for JS to potentially interact with

        if (isset($videoDetailsForHtml['error']) || !isset($videoDetailsForHtml['title'])) {
            // Display error if video info fetching failed
            echo "<main class='container mx-auto mt-8 p-4'><div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative' role='alert'>" . htmlspecialchars(isset($videoDetailsForHtml['error']) ? $videoDetailsForHtml['error'] : _t('error_retrieve_video_info', 'Could not retrieve video information.')) . "</div></main>";
        } else {
            // Display video details and format list
            $video_title_html = htmlspecialchars($videoDetailsForHtml['title']);
            $thumbnail_html = isset($videoDetailsForHtml['thumbnail_url']) ? htmlspecialchars($videoDetailsForHtml['thumbnail_url']) : '';
        ?>
        <main class="container mx-auto mt-8 p-4">
            <div class="bg-white shadow-lg rounded-lg p-6">
                <h1 class="text-2xl font-bold text-gray-800 mb-2"><?php echo _t('download_video_title_page', 'Download Video: {title}', ['title' => $video_title_html]); ?></h1>
                <?php if ($thumbnail_html): ?>
                <img src="<?php echo $thumbnail_html; ?>" alt="<?php echo htmlspecialchars(_t('video_thumbnail_alt', 'Video Thumbnail')); ?>" class="my-4 rounded-lg shadow-md" style="max-width:320px; margin-left:auto; margin-right:auto;">
                <?php endif; ?>
                <h2 class="text-xl font-semibold text-gray-700 mb-4"><?php echo htmlspecialchars(_t('available_formats_title', 'Available Formats:')); ?></h2>
                <ul class="space-y-3">
                    <?php
                    if (!empty($videoDetailsForHtml['formats'])) {
                        foreach ($videoDetailsForHtml['formats'] as $format) {
                            $format_label_html = htmlspecialchars($format['label']);
                            $format_id_html = htmlspecialchars($format['id']);
                            $download_link = 'download.php?url=' . urlencode($validated_url) . '&format_id=' . urlencode($format_id_html);
                            
                            $file_details = htmlspecialchars($format['ext'] . (isset($format['resolution']) && $format['resolution'] !== 'Audio' ? ' - ' . $format['resolution'] : '') . (isset($format['filesize_approx_str']) ? ' - ' . $format['filesize_approx_str'] : ''));

                            echo '<li class="p-3 bg-gray-50 rounded-md shadow-sm hover:bg-gray-100 transition duration-150">';
                            echo '<a href="' . htmlspecialchars($download_link) . '" class="block text-blue-600 hover:text-blue-800">';
                            echo '<strong>' . $format_label_html . '</strong>';
                            echo '<span class="text-sm text-gray-500 block">' . $file_details . '</span>';
                            echo '</a></li>';
                        }
                    } else {
                        echo "<li class='text-gray-500'>".htmlspecialchars(_t('no_suitable_formats_html', 'No suitable download formats found for this video.'))."</li>";
                    }
                    ?>
                </ul>
            </div>
        </main>
        <?php
        } // Closing else for $videoDetailsForHtml check
        echo '</div>'; // End of #php-format-list
    } // END SECTION: HTML Fallback Mode

    // SECTION: Footer inclusion for HTML pages
    // Footer should be included only if not a JSON request and not a direct file download (where script exits earlier)
    if (!(isset($_GET['json']) && $_GET['json'] == '1') && !(isset($_GET['ajax']) && $_GET['ajax'] == '1') && !isset($_GET['format_id'])) {
        if (file_exists(__DIR__ . "/includes/footer.php")) { require_once __DIR__ . "/includes/footer.php"; }
    } elseif (isset($_GET['format_id'])) {
        // If it's a download request, the script exits earlier, but as a fallback:
        // No footer output during direct download.
    }
?>
