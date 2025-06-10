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
<nav id="main-navbar" class="container mx-auto px-6 py-3 flex justify-between items-center border-b border-gray-200 shadow-sm relative"> <?php // Added id and relative class ?>
<a class="text-2xl font-bold text-blue-600" href="index.php">ytid.com</a><div id="mobile-header-controls" class="md:hidden flex items-center relative"> <div id="mobile-language-button-wrapper" class="relative"><button id="mobile-language-switcher-button" class="p-2 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500 flex items-center mr-2" aria-expanded="false" aria-controls="language-dropdown">
    <img src="<?php echo htmlspecialchars($lang['lang_flag_image'] ?? 'assets/images/flags/default.png'); ?>" alt="Current Language Flag" class="h-5 w-auto align-middle">
    <span class="ml-1 text-xs font-medium"><?php echo strtoupper(htmlspecialchars($current_language)); ?></span>
</button></div><button id="mobile-menu-toggle" class="p-2 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500" aria-expanded="false" aria-controls="mobile-menu"> <span class="sr-only">Open main menu</span> <span class="material-icons">menu</span> </button> </div> <div class="hidden md:flex items-center space-x-4">
<a class="text-gray-600 hover:text-blue-600 font-semibold px-4" href="index.php"><?php echo _t('nav_home', 'YouTube Downloader'); ?></a>
<a class="text-gray-600 hover:text-blue-600 font-semibold px-4" href="youtube-to-mp3.php"><?php echo _t('nav_yt_to_mp3', 'YouTube To Mp3'); ?></a>
<a class="text-gray-600 hover:text-blue-600 font-semibold px-4" href="youtube-to-mp4.php"><?php echo _t('nav_yt_to_mp4', 'YouTube To Mp4'); ?></a>
<a class="text-gray-600 hover:text-blue-600 font-semibold px-4" href="youtube-thumbnail-downloader.php"><?php echo _t('nav_thumb_downloader', 'Thumbnail Downloader'); ?></a>
</div>
<div class="relative hidden md:block" id="language-switcher"> <?php // Moved switcher here and added hidden md:block ?>
        <button class="text-gray-600 hover:text-blue-600 flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm">
            <img src="<?php echo htmlspecialchars($lang['lang_flag_image'] ?? 'assets/images/flags/default.png'); ?>" alt="Flag" class="h-5 w-auto mr-2 align-middle">
            <?php echo htmlspecialchars($available_languages[$current_language]); ?>
            <span class="material-icons text-sm ml-1">expand_more</span>
        </button>
        <?php // Dropdown is no longer here ?>
    </div>
<?php // Script block removed ?>
<div id="language-dropdown" class="absolute z-20 hidden left-4 right-4 bg-white rounded-md shadow-md border border-gray-200 py-2"> <?php // Removed max-h and overflow again ?>
            <div class="grid grid-cols-2 md:grid-cols-1 gap-x-1 gap-y-0.5"> <?php // Changed gap-y-1 to gap-y-0.5 ?>
            <?php foreach ($available_languages as $lang_code => $lang_name): ?>
                <?php if ($lang_code !== $current_language): ?>
                    <?php
                        $flag_image_path = 'assets/images/flags/' . htmlspecialchars($lang_code) . '.png';
                        $default_flag_path = 'assets/images/flags/default.png';
                    ?>
                    <a href="?lang=<?php echo htmlspecialchars($lang_code); ?>" class="block w-full px-2 py-1 text-xs sm:text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:bg-gray-100 focus:text-gray-900 flex items-center"> <?php // Changed text-sm to text-xs sm:text-sm ?>
                        <img src="<?php echo htmlspecialchars($flag_image_path); ?>"
                             alt="Flag for <?php echo htmlspecialchars($lang_name); ?>"
                             class="h-5 w-auto mr-3 align-middle"
                             onerror="this.onerror=null; this.src='<?php echo $default_flag_path; ?>';">
                        <?php echo htmlspecialchars($lang_name); ?>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
            </div>
        </div>
</nav>
<div id="mobile-menu" class="hidden md:hidden bg-white shadow-lg border-t border-gray-200">
    <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
        <a href="index.php" class="block py-3 px-4 text-base text-gray-700 hover:bg-gray-50 rounded-md"><?php echo _t('nav_home', 'YouTube Downloader'); ?></a>
        <a href="youtube-to-mp3.php" class="block py-3 px-4 text-base text-gray-700 hover:bg-gray-50 rounded-md"><?php echo _t('nav_yt_to_mp3', 'YouTube To Mp3'); ?></a>
        <a href="youtube-to-mp4.php" class="block py-3 px-4 text-base text-gray-700 hover:bg-gray-50 rounded-md"><?php echo _t('nav_yt_to_mp4', 'YouTube To Mp4'); ?></a>
        <a href="youtube-thumbnail-downloader.php" class="block py-3 px-4 text-base text-gray-700 hover:bg-gray-50 rounded-md"><?php echo _t('nav_thumb_downloader', 'Thumbnail Downloader'); ?></a>
    </div>
</div>
</header>
