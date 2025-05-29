<?php 
    // These ini_set and error_reporting lines are good for debugging, keep them.
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    // includes/header.php also calls session_start() via language.php
    include("includes/header.php"); 
?>
<main>
    <section class="bg-gradient-to-b from-blue-100 to-blue-50 py-16">
        <div class="container mx-auto px-6 text-center">
            <div class="bg-white p-8 md:p-12 rounded-xl shadow-xl max-w-3xl mx-auto">
                <h1 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4"><?php echo _t('yt_to_mp3_title', 'YouTube To Mp3 Converter'); ?></h1>
                <p class="text-gray-600 mb-8"><?php echo _t('yt_to_mp3_description', 'Convert YouTube videos to MP3 format quickly and easily.'); ?></p>
                <form id="video-url-form-mp3" class="flex flex-col sm:flex-row items-center justify-center space-y-4 sm:space-y-0 sm:space-x-2">
                    <input name="url" class="flex-grow w-full sm:w-auto p-4 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none" placeholder="<?php echo _t('enter_youtube_url_placeholder', 'Paste YouTube video link here'); ?>" type="text" required/>
                    <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-4 px-6 rounded-lg flex items-center justify-center w-full sm:w-auto">
                        <span class="material-icons mr-2">audiotrack</span> <?php /* Changed icon */ ?>
                        <?php echo _t('convert_to_mp3_button', 'Convert to MP3'); ?>
                    </button>
                </form>
                <p class="text-xs text-gray-500 mt-4 flex items-center justify-center">
                    <?php echo _t('copyrighted_content_warning', 'Copyrighted content is not available for download with this tool.'); ?>
                    <span class="material-icons text-sm ml-1">info_outline</span>
                </p>
            </div>
        </div>
    </section>

    <div id="conversion-results-container" class="mt-8">
        <?php // Placeholder for MP3 conversion results, download links, etc. ?>
    </div>

    <section class="py-16 bg-white">
        <div class="container mx-auto px-6">
            <h2 class="text-3xl font-bold text-gray-800 text-center mb-4"><?php echo _t('yt_to_mp3_features_title', 'Features of Our YouTube to MP3 Converter'); ?></h2>
            <div class="grid md:grid-cols-2 gap-8 text-left">
                <div class="bg-gray-50 p-6 rounded-lg">
                    <h3 class="text-xl font-semibold text-gray-800 mb-2"><?php echo _t('feature_high_quality_audio_title', 'High Quality Audio'); ?></h3>
                    <p class="text-gray-600"><?php echo _t('feature_high_quality_audio_desc', 'Extract MP3 audio from YouTube videos in the best available quality.'); ?></p>
                </div>
                <div class="bg-gray-50 p-6 rounded-lg">
                    <h3 class="text-xl font-semibold text-gray-800 mb-2"><?php echo _t('feature_fast_conversion_title', 'Fast Conversion'); ?></h3>
                    <p class="text-gray-600"><?php echo _t('feature_fast_conversion_desc', 'Our service quickly processes your video and starts the MP3 conversion.'); ?></p>
                </div>
                <div class="bg-gray-50 p-6 rounded-lg">
                    <h3 class="text-xl font-semibold text-gray-800 mb-2"><?php echo _t('feature_easy_to_use_title', 'Easy to Use'); ?></h3>
                    <p class="text-gray-600"><?php echo _t('feature_easy_to_use_desc', 'Just paste the YouTube URL, click convert, and download your MP3.'); ?></p>
                </div>
                <div class="bg-gray-50 p-6 rounded-lg">
                    <h3 class="text-xl font-semibold text-gray-800 mb-2"><?php echo _t('feature_no_registration_mp3_title', 'No Registration Needed'); ?></h3>
                    <p class="text-gray-600"><?php echo _t('feature_no_registration_mp3_desc', 'Convert videos to MP3 without any signup or registration required.'); ?></p>
                </div>
            </div>
        </div>
    </section>
</main>
<?php include("includes/footer.php"); ?>
