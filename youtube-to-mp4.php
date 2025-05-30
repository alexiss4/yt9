<?php 
    require_once __DIR__ . '/config.php';
    require_once __DIR__ . '/includes/header.php'; 
?>
<main>
    <section class="bg-gradient-to-b from-blue-100 to-blue-50 py-16">
        <div class="container mx-auto px-6 text-center">
            <div class="bg-white p-8 md:p-12 rounded-xl shadow-xl max-w-3xl mx-auto">
                <h1 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4"><?php echo _t('yt_to_mp4_title', 'YouTube To Mp4 Converter'); ?></h1>
                <p class="text-gray-600 mb-8"><?php echo _t('yt_to_mp4_description', 'Download and convert YouTube videos to MP4 format in various resolutions.'); ?></p>
                <?php
                $form_id = 'video-url-form-mp4'; 
                $input_placeholder = _t('enter_youtube_url_placeholder', 'Paste YouTube video link here');
                $button_text = _t('convert_to_mp4_button', 'Convert to MP4');
                $button_icon = 'smart_display';
                require __DIR__ . '/includes/ui_components/video_form.php'; 
                ?>
            </div>
        </div>
    </section>

    <div id="conversion-results-container-mp4" class="mt-8">
        <?php // Placeholder for MP4 conversion results, download links, quality options etc. ?>
    </div>

    <section class="py-16 bg-white">
        <div class="container mx-auto px-6">
            <h2 class="text-3xl font-bold text-gray-800 text-center mb-4"><?php echo _t('yt_to_mp4_features_title', 'Features of Our YouTube to MP4 Converter'); ?></h2>
            <?php
            $features_list = [
                ['icon' => 'personal_video', 'title' => _t('feature_high_resolution_title', 'High Resolution Video'), 'description' => _t('feature_high_resolution_desc', 'Download MP4 videos in resolutions up to 4K and 8K where available.')],
                ['icon' => 'video_library', 'title' => _t('feature_multiple_formats_mp4_title', 'Multiple Format Options'), 'description' => _t('feature_multiple_formats_mp4_desc', 'Choose from various MP4 quality levels and other video formats if needed.')],
                ['icon' => 'fast_forward', 'title' => _t('feature_fast_download_mp4_title', 'Fast Downloads'), 'description' => _t('feature_fast_download_mp4_desc', 'Our service quickly processes your video for fast MP4 downloads.')],
                ['icon' => 'no_encryption', 'title' => _t('feature_no_registration_mp4_title', 'No Registration Needed'), 'description' => _t('feature_no_registration_mp4_desc', 'Download and convert videos to MP4 without any signup or registration.')]
            ];
            $grid_cols_class = 'md:grid-cols-2';
            require __DIR__ . '/includes/ui_components/features_section.php';
            ?>
        </div>
    </section>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
