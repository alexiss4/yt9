<?php 
    require_once __DIR__ . '/../config.php';
    require_once __DIR__ . '/language.php'; 
?>
<!DOCTYPE html>
<html lang="en"><head>
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
<body class="bg-slate-50">
<header class="bg-white shadow-md">
<nav class="container mx-auto px-6 py-3 flex justify-between items-center">
<a class="text-2xl font-bold text-sky-600" href="index.php">ytid.com</a> <div class="flex items-center space-x-4">
<a class="text-slate-600 hover:text-sky-600" href="index.php"><?php echo _t('nav_home', 'YouTube Downloader'); ?></a>
<a class="text-slate-600 hover:text-sky-600" href="youtube-to-mp3.php"><?php echo _t('nav_yt_to_mp3', 'YouTube To Mp3'); ?></a>
<a class="text-slate-600 hover:text-sky-600" href="youtube-to-mp4.php"><?php echo _t('nav_yt_to_mp4', 'YouTube To Mp4'); ?></a>
<a class="text-slate-600 hover:text-sky-600" href="youtube-thumbnail-downloader.php"><?php echo _t('nav_thumb_downloader', 'Thumbnail Downloader'); ?></a>
<div class="relative" id="language-switcher">
        <button class="text-slate-600 hover:text-sky-600 flex items-center">
            <img src="assets/images/country_logos/<?php echo htmlspecialchars($language_logos[$current_language]); ?>" alt="<?php echo htmlspecialchars($available_languages[$current_language]); ?>" class="w-5 h-auto mr-2">
            <?php echo htmlspecialchars($available_languages[$current_language]); ?>
            <span class="material-icons text-sm ml-1">expand_more</span>
        </button>
        <div id="language-dropdown" class="absolute right-0 mt-2 py-2 w-48 bg-white rounded-md shadow-xl z-20 hidden">
            <?php foreach ($available_languages as $lang_code => $lang_name): ?>
                <?php if ($lang_code !== $current_language): ?>
                    <a href="?lang=<?php echo htmlspecialchars($lang_code); ?>" class="block px-4 py-2 text-sm text-slate-700 hover:bg-sky-600 hover:text-white">
                        <img src="assets/images/country_logos/<?php echo htmlspecialchars($language_logos[$lang_code]); ?>" alt="<?php echo htmlspecialchars($lang_name); ?>" class="w-5 h-auto mr-2 inline-block">
                        <?php echo htmlspecialchars($lang_name); ?>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
</div>
</nav>
</header>
