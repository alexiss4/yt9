<?php
    require_once __DIR__ . '/config.php';
    require_once __DIR__ . '/includes/header.php';

    $video_id = null;
    $thumbnail_urls = [];
    $error_message = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['url'])) {
        $url = sanitize_input(trim($_POST['url']));
        if (!empty($url)) {
            // Regex to extract YouTube video ID from various URL formats
            $regex = '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/';
            if (preg_match($regex, $url, $matches)) {
                $video_id = $matches[1];
                $thumbnail_qualities = [
                    'maxresdefault' => _t('thumb_max_res', 'Max Resolution'),
                    'sddefault'     => _t('thumb_sd_res', 'Standard Definition'),
                    'hqdefault'     => _t('thumb_hq_res', 'High Quality'),
                    'mqdefault'     => _t('thumb_mq_res', 'Medium Quality'),
                    'default'       => _t('thumb_default_res', 'Default Quality')
                ];
                foreach ($thumbnail_qualities as $quality => $label) {
                    $thumbnail_urls[] = [
                        'url' => "https://img.youtube.com/vi/{$video_id}/{$quality}.jpg",
                        'label' => $label,
                        'filename' => "{$video_id}_{$quality}.jpg"
                    ];
                }
            } else {
                $error_message = _t('error_invalid_youtube_url', 'Invalid YouTube URL. Please enter a valid video link.');
            }
        } else {
            $error_message = _t('error_empty_youtube_url', 'Please enter a YouTube URL.');
        }
    }
?>
<main>
    <section class="bg-gradient-to-b from-teal-100 to-teal-50 py-16"> <?php /* Changed color theme slightly */ ?>
        <div class="container mx-auto px-6 text-center">
            <div class="bg-white p-8 md:p-12 rounded-xl shadow-xl max-w-3xl mx-auto">
                <h1 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4"><?php echo _t('yt_thumb_title', 'YouTube Thumbnail Downloader'); ?></h1>
                <p class="text-gray-600 mb-8"><?php echo _t('yt_thumb_description', 'Download thumbnails from YouTube videos in various resolutions.'); ?></p>
                <?php
                $form_id = 'video-url-form-thumbnail';
                $input_placeholder = _t('enter_youtube_url_placeholder', 'Paste YouTube video link here');
                $button_text = _t('get_thumbnails_button', 'Get Thumbnails');
                $button_icon = 'image';
                $form_action_url = 'youtube-thumbnail-downloader.php'; 
                $show_copyright_warning = false; 
                require __DIR__ . '/includes/ui_components/video_form.php'; 
                ?>

                <?php if (!empty($error_message)): ?>
                    <p class="text-red-500 mt-4"><?php echo htmlspecialchars($error_message); // Keep error message display specific to this page ?></p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php if (!empty($thumbnail_urls)): ?>
    <section id="thumbnail-results" class="py-12">
        <div class="container mx-auto px-6">
            <h2 class="text-2xl font-bold text-gray-800 text-center mb-8"><?php echo _t('available_thumbnails_title', 'Available Thumbnails'); ?> (ID: <?php echo htmlspecialchars($video_id); ?>)</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                <?php foreach ($thumbnail_urls as $index => $thumb): ?>
                    <div class="bg-white p-4 rounded-lg shadow-lg text-center">
                        <h3 class="text-lg font-semibold text-gray-700 mb-2"><?php echo htmlspecialchars($thumb['label']); ?></h3>
                        <img src="<?php echo htmlspecialchars($thumb['url']); ?>" alt="<?php echo htmlspecialchars($thumb['label']); ?>" class="w-full h-auto rounded-md border mb-3" onerror="this.alt='<?php echo htmlspecialchars(_t('thumb_not_available', 'Thumbnail not available at this resolution.')); ?>'; this.src='data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=='; /* Transparent GIF */ ">
                        <a href="<?php echo htmlspecialchars($thumb['url']); ?>" download="<?php echo htmlspecialchars($thumb['filename']); ?>" target="_blank" class="mt-2 inline-block bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded-lg text-sm">
                            <span class="material-icons mr-1" style="font-size:1.2em; vertical-align:middle;">file_download</span>
                            <?php echo _t('download_thumb_button', 'Download'); ?>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
