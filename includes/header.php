<?php
    require_once __DIR__ . '/../config.php';
    require_once __DIR__ . '/language.php';
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($current_language); ?>"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title><?php echo _t('site_title', 'YouTube Video Downloader'); ?></title>
<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&amp;display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="assets/css/style.css"/> 
<script src="assets/js/script.js" defer></script> 
<style>
        body {
            font-family: 'Roboto', sans-serif;
        }
    </style>
</head>
<body class="bg-blue-50">
<header class="bg-white shadow-md">
<nav class="container mx-auto px-6 py-3 flex justify-between items-center border-b border-gray-200 shadow-sm">
<a class="text-2xl font-bold text-blue-600" href="index.php">ytid.com</a> <div class="flex items-center space-x-4">
<a class="text-gray-600 hover:text-blue-600 font-semibold px-4" href="index.php"><?php echo _t('nav_home', 'YouTube Downloader'); ?></a>
<a class="text-gray-600 hover:text-blue-600 font-semibold px-4" href="youtube-to-mp3.php"><?php echo _t('nav_yt_to_mp3', 'YouTube To Mp3'); ?></a>
<a class="text-gray-600 hover:text-blue-600 font-semibold px-4" href="youtube-to-mp4.php"><?php echo _t('nav_yt_to_mp4', 'YouTube To Mp4'); ?></a>
<a class="text-gray-600 hover:text-blue-600 font-semibold px-4" href="youtube-thumbnail-downloader.php"><?php echo _t('nav_thumb_downloader', 'Thumbnail Downloader'); ?></a>
<div class="relative" id="language-switcher">
        <button class="text-gray-600 hover:text-blue-600 flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm" onclick="document.getElementById('language-dropdown').classList.toggle('hidden');">
            <img src="<?php echo htmlspecialchars($lang['lang_flag_image'] ?? 'assets/images/flags/default.png'); ?>" alt="Flag" class="h-5 w-auto mr-2 align-middle">
            <?php echo htmlspecialchars($available_languages[$current_language]); ?>
            <span class="material-icons text-sm ml-1">expand_more</span>
        </button>
        <div id="language-dropdown" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg border border-gray-300 z-20 hidden py-1">
            <?php foreach ($available_languages as $lang_code => $lang_name): ?>
                <?php if ($lang_code !== $current_language): ?>
                    <?php
                        // Construct the path to the flag image for the language in the loop.
                        // This assumes that the language files (e.g., es.php, fr.php) are not loaded in this scope to get $lang['lang_flag_image'] for each.
                        // Instead, we directly construct the path using the $lang_code.
                        $flag_image_path = 'assets/images/flags/' . htmlspecialchars($lang_code) . '.png';
                        // A true fallback for missing images would involve checking file_exists,
                        // but for this task, we'll output the path and rely on a potential default.png or broken image icon.
                        // The prompt included 'assets/images/flags/default.png' as a fallback in the $lang array access,
                        // but here we don't have individual $lang arrays for each item in the loop easily.
                        // We can use a general default path if we imagine one.
                        $default_flag_path = 'assets/images/flags/default.png'; // Illustrative default
                    ?>
                    <a href="?lang=<?php echo htmlspecialchars($lang_code); ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-500 hover:text-white flex items-center">
                        <img src="<?php echo htmlspecialchars($flag_image_path); ?>"
                             alt="Flag for <?php echo htmlspecialchars($lang_name); ?>"
                             class="h-5 w-auto mr-3 align-middle"
                             onerror="this.onerror=null; this.src='<?php echo $default_flag_path; ?>';"> <?php // Fallback to default.png if specific flag is missing ?>
                        <?php echo htmlspecialchars($lang_name); ?>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<script>
        const langSwitcherButton = document.querySelector('#language-switcher button');
        const langDropdown = document.getElementById('language-dropdown');

        // Close dropdown if clicked outside
        // The onclick attribute on the button handles the toggle.
        window.addEventListener('click', function(e) {
            if (!langSwitcherButton.contains(e.target) && !langDropdown.contains(e.target)) {
                langDropdown.classList.add('hidden');
            }
        });
    </script>
</nav>
</header>
