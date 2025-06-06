<?php
// download.php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/language.php'; // For _t() function
require_once __DIR__ . '/includes/functions.php';  // For YtDlpWrapper and sanitize_input

$ytDlp = new YtDlpWrapper();

// SECTION: Initial URL validation and sanitization
if (!isset($_GET['url']) || !is_string($_GET['url'])) { // Ensure URL is a string
    ob_start();
    if (!headers_sent() && file_exists(__DIR__ . "/includes/header.php")) { require_once __DIR__ . '/includes/header.php'; }
    echo "<main class='container mx-auto mt-8 p-4'><div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative' role='alert'>"._t('error_no_url_provided', 'No URL provided or URL is invalid.')."</div></main>";
    if (!headers_sent() && file_exists(__DIR__ . "/includes/footer.php")) { require_once __DIR__ . "/includes/footer.php"; }
    ob_end_flush();
    exit;
}

$url = sanitize_input($_GET['url']); // Sanitize early for validation checks

if (!filter_var($url, FILTER_VALIDATE_URL)) {
    ob_start();
    if (!headers_sent() && file_exists(__DIR__ . "/includes/header.php")) { require_once __DIR__ . '/includes/header.php'; }
    echo "<main class='container mx-auto mt-8 p-4'><div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative' role='alert'>"._t('error_invalid_url_format', 'Invalid URL format.')."</div></main>";
    if (!headers_sent() && file_exists(__DIR__ . "/includes/footer.php")) { require_once __DIR__ . "/includes/footer.php"; }
    ob_end_flush();
    exit;
}

// Basic YouTube URL pattern check
if (!preg_match('/(youtube\.com\/watch\?v=|youtu\.be\/)/', $url)) {
    ob_start();
    if (!headers_sent() && file_exists(__DIR__ . "/includes/header.php")) { require_once __DIR__ . '/includes/header.php'; }
    echo "<main class='container mx-auto mt-8 p-4'><div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative' role='alert'>"._t('error_invalid_youtube_url', 'Invalid YouTube URL provided. Please use a valid YouTube video link.')."</div></main>";
    if (!headers_sent() && file_exists(__DIR__ . "/includes/footer.php")) { require_once __DIR__ . "/includes/footer.php"; }
    ob_end_flush();
    exit;
}

$validated_url = $url; // URL is now considered validated for basic structure and sanitized
// END SECTION: Initial URL validation

// SECTION: Handle JSON API request for video info
if (isset($_GET['json']) && $_GET['json'] == '1' || isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    // Note: $validated_url is already sanitized. YtDlpWrapper methods handle further escaping for shell.
    $videoDetails = $ytDlp->getFormattableVideoInfo($validated_url); 

    if (isset($videoDetails['error'])) {
        http_response_code(500); // Or more specific based on error if possible
        header('Content-Type: application/json');
        echo json_encode(['error' => $videoDetails['error']]); // Error message is already translated by wrapper
        exit;
    }
    header('Content-Type: application/json');
    echo json_encode($videoDetails);
    exit;
}
// END SECTION: JSON API request


// SECTION: Handle direct media download (format_id is present)
if (isset($_GET['format_id'])) {
    $format_id = sanitize_input($_GET['format_id']);
    
    // getVideoInfo is used here as we need raw format details to find the extension
    $video_data = $ytDlp->getVideoInfo($validated_url); 

    // If fetching video info for title/extension fails, display error page
    if (isset($video_data['error']) || !isset($video_data['title'])) {
        ob_start(); 
        // Only include header if it exists and output hasn't started
        if (!headers_sent() && file_exists(__DIR__ . "/includes/header.php")) {
             require_once __DIR__ . "/includes/header.php";
        }
        $error_message_display = isset($video_data['error']) ? $video_data['error'] : _t('error_fetch_video_info_download', 'Error: Could not re-fetch video information for download.');
        echo "<main class='container mx-auto mt-8 p-4'><div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative' role='alert'>".htmlspecialchars($error_message_display)."</div></main>";
        if (!headers_sent() && file_exists(__DIR__ . "/includes/footer.php")) {
             require_once __DIR__ . "/includes/footer.php";
        }
        ob_end_flush(); // Send buffered output
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
    
    // Stream the media - this function will set headers and exit.
    $ytDlp->streamMedia($validated_url, $format_id, $video_title, $original_extension);
    exit; 
// END SECTION: Direct media download

} else {
    // SECTION: HTML Fallback Mode (display available formats on page)
    // This part executes if no 'json' and no 'format_id' param is set.
    // It means we should display a full HTML page with format options.
    ob_start(); // Start output buffering for the full page

    // Header should be included first for a full HTML page.
    if (!headers_sent() && file_exists(__DIR__ . "/includes/header.php")) {
        require_once __DIR__ . "/includes/header.php";
    }
    
    // Note: $validated_url is already sanitized. YtDlpWrapper methods handle further escaping for shell.
    $videoDetailsForHtml = $ytDlp->getFormattableVideoInfo($validated_url);

    echo '<div id="php-format-list">'; // Keep this for potential JS hooks if any

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
            <h1 class="text-2xl font-bold text-slate-800 mb-2"><?php echo _t('download_video_title_page', 'Download Video: {title}', ['title' => $video_title_html]); ?></h1>
            <?php if ($thumbnail_html): ?>
            <img src="<?php echo $thumbnail_html; ?>" alt="<?php echo htmlspecialchars(_t('video_thumbnail_alt', 'Video Thumbnail')); ?>" class="my-4 rounded-lg shadow-md" style="max-width:320px; margin-left:auto; margin-right:auto;">
            <?php endif; ?>
            <h2 class="text-xl font-semibold text-slate-700 mb-4"><?php echo htmlspecialchars(_t('available_formats_title', 'Available Formats:')); ?></h2>
            <ul class="space-y-3">
                <?php
                if (!empty($videoDetailsForHtml['formats'])) {
                    foreach ($videoDetailsForHtml['formats'] as $format) {
                        $format_label_html = htmlspecialchars($format['label']);
                        $format_id_html = htmlspecialchars($format['id']);
                        $download_link = 'download.php?url=' . urlencode($validated_url) . '&format_id=' . urlencode($format_id_html);
                        
                        $file_details = htmlspecialchars($format['ext'] . (isset($format['resolution']) && $format['resolution'] !== 'Audio' ? ' - ' . $format['resolution'] : '') . (isset($format['filesize_approx_str']) ? ' - ' . $format['filesize_approx_str'] : ''));

                        echo '<li class="p-3 bg-gray-50 rounded-md shadow-sm hover:bg-gray-100 transition duration-150">';
                        echo '<a href="' . htmlspecialchars($download_link) . '" class="block text-sky-600 hover:text-sky-700">';
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

    // Footer for the full HTML page.
    if (!headers_sent() && file_exists(__DIR__ . "/includes/footer.php")) {
        require_once __DIR__ . "/includes/footer.php";
    }
    ob_end_flush(); // Send the complete page
    exit; // Ensure script exits after sending the page
}
// END SECTION: HTML Fallback Mode
?>
