<?php 
    require_once __DIR__ . '/config.php';
    // includes/header.php also calls session_start() via language.php
    require_once __DIR__ . '/includes/header.php';
?>
<main>
    <section class="bg-gradient-to-b from-blue-100 to-blue-50 py-16">
        <div class="container mx-auto px-6 text-center">
            <div class="bg-white p-8 md:p-12 rounded-xl shadow-xl max-w-3xl mx-auto">
                <h1 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4"><?php echo _t('yt_to_mp3_title', 'YouTube To Mp3 Converter'); ?></h1>
                <p class="text-gray-600 mb-8"><?php echo _t('yt_to_mp3_description', 'Convert YouTube videos to MP3 format quickly and easily.'); ?></p>
                <?php
                $form_id = 'video-url-form-mp3';
                $input_placeholder = _t('enter_youtube_url_placeholder', 'Paste YouTube video link here');
                $button_text = _t('convert_to_mp3_button', 'Convert to MP3');
                $button_icon = 'audiotrack';
                require __DIR__ . '/includes/ui_components/video_form.php';
                ?>
                <!-- Moved results container inside and added 'hidden' class -->
                <div id="conversion-results-container" class="mt-8 hidden">
                    <?php // Placeholder for MP3 conversion results, download links, etc. ?>
                </div>
            </div>
        </div>
    </section>

    <section class="py-16 bg-white">
        <div class="container mx-auto px-6">
            <h2 class="text-3xl font-bold text-gray-800 text-center mb-4"><?php echo _t('yt_to_mp3_features_title', 'Features of Our YouTube to MP3 Converter'); ?></h2>
            <?php
            $features_list = [
                ['icon' => 'high_quality', 'title' => _t('feature_high_quality_audio_title', 'High Quality Audio'), 'description' => _t('feature_high_quality_audio_desc', 'Extract MP3 audio from YouTube videos in the best available quality.')],
                ['icon' => 'bolt', 'title' => _t('feature_fast_conversion_title', 'Fast Conversion'), 'description' => _t('feature_fast_conversion_desc', 'Our service quickly processes your video and starts the MP3 conversion.')],
                ['icon' => 'phonelink_setup', 'title' => _t('feature_easy_to_use_title', 'Easy to Use'), 'description' => _t('feature_easy_to_use_desc', 'Just paste the YouTube URL, click convert, and download your MP3.')],
                ['icon' => 'no_encryption', 'title' => _t('feature_no_registration_mp3_title', 'No Registration Needed'), 'description' => _t('feature_no_registration_mp3_desc', 'Convert videos to MP3 without any signup or registration required.')]
            ];
            $grid_cols_class = 'md:grid-cols-2';
            require __DIR__ . '/includes/ui_components/features_section.php';
            ?>
        </div>
    </section>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
