<?php
/**
 * Reusable UI component for generating a video URL input form.
 *
 * This component creates a form with an input field for a URL and a submit button.
 * It can be customized by defining specific variables before including this file.
 *
 * Expected variables from the including page:
 * @var string $form_id                  The ID attribute for the form element. Defaults to 'video-url-form'.
 * @var string $input_placeholder        Placeholder text for the URL input field. Defaults to a generic placeholder.
 * @var string $button_text              Text for the submit button. Defaults to 'Download'.
 * @var string $button_icon              Material icon name for the button. Defaults to 'search'.
 * @var string $form_action_url          The URL where the form will be submitted. Defaults to '#', implying AJAX/JS handling.
 *                                       If set to a specific URL, the form method will be 'POST'.
 * @var bool   $show_copyright_warning   Whether to display a copyright warning below the form. Defaults to true.
 * @var string $input_value              The pre-filled value for the URL input field. Defaults to URL from $_REQUEST or empty.
 */

// includes/ui_components/video_form.php

$form_id = $form_id ?? 'video-url-form';
$input_placeholder = $input_placeholder ?? _t('enter_youtube_url_or_search_placeholder', 'Search keywords or paste video link here');
$button_text = $button_text ?? _t('download_button', 'Download');
$button_icon = $button_icon ?? 'search';
$form_action_url = $form_action_url ?? '#'; // '#' or empty implies AJAX/JS handling
$show_copyright_warning = $show_copyright_warning ?? true;
// Use $_REQUEST to get 'url' value, works for POST (thumbnail page) and GET (if URL is passed in query for some reason)
$input_value = isset($_REQUEST['url']) ? htmlspecialchars($_REQUEST['url']) : ''; 
?>
<form id="<?php echo htmlspecialchars($form_id); ?>" 
      class="flex flex-col sm:flex-row items-center justify-center space-y-4 sm:space-y-0 sm:space-x-2"
      <?php if ($form_action_url !== '#') echo 'method="POST" action="'.htmlspecialchars($form_action_url).'"'; ?>
      >
    <input name="url" class="flex-grow w-full sm:w-auto p-4 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent outline-none"
           placeholder="<?php echo htmlspecialchars($input_placeholder); ?>" type="text" required 
           value="<?php echo $input_value; ?>"
    />
    <button type="submit" class="bg-sky-500 hover:bg-sky-600 text-white font-semibold py-4 px-6 rounded-lg flex items-center justify-center w-full sm:w-auto">
        <span class="material-icons mr-2"><?php echo htmlspecialchars($button_icon); ?></span>
        <?php echo htmlspecialchars($button_text); ?>
    </button>
</form>
<?php if ($show_copyright_warning): ?>
<p class="text-xs text-slate-500 mt-4 flex items-center justify-center">
    <?php echo _t('copyrighted_content_warning', 'Copyrighted content is not available for download with this tool.'); ?>
    <span class="material-icons text-sm ml-1">info_outline</span>
</p>
<?php endif; ?>
