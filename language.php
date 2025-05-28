<?php
session_start();

$available_languages = [
    'en' => 'English',
    'es' => 'Español',
    'fr' => 'Français',
    'de' => 'Deutsch',
    'it' => 'Italiano',
    'pt' => 'Português',
    'ru' => 'Русский',
    'ja' => '日本語',
    'ko' => '한국어',
    'zh' => '中文',
    'ar' => 'العربية',
    'hi' => 'हिन्दी',
    'nl' => 'Nederlands',
    'pl' => 'Polski',
    'tr' => 'Türkçe',
    'sv' => 'Svenska',
    'da' => 'Dansk',
    'fi' => 'Suomi',
    'no' => 'Norsk',
    'el' => 'Ελληνικά',
    'sw' => 'Kiswahili', // New
    'bn' => 'বাংলা'     // New
];

$language_flags = [
    'en' => 'gb', // United Kingdom for English
    'es' => 'es', // Spain
    'fr' => 'fr', // France
    'de' => 'de', // Germany
    'it' => 'it', // Italy
    'pt' => 'pt', // Portugal
    'ru' => 'ru', // Russia
    'ja' => 'jp', // Japan
    'ko' => 'kr', // South Korea
    'zh' => 'cn', // China
    'ar' => 'sa', // Saudi Arabia for Arabic
    'hi' => 'in', // India
    'nl' => 'nl', // Netherlands
    'pl' => 'pl', // Poland
    'tr' => 'tr', // Turkey
    'sv' => 'se', // Sweden
    'da' => 'dk', // Denmark
    'fi' => 'fi', // Finland
    'no' => 'no', // Norway
    'el' => 'gr', // Greece
    'sw' => 'ke', // Kenya for Swahili (Example, can be tz, ug etc.)
    'bn' => 'bd', // Bangladesh for Bengali
];

$default_language = 'en';

if (isset($_GET['lang']) && array_key_exists($_GET['lang'], $available_languages)) {
    $_SESSION['lang'] = $_GET['lang'];
} elseif (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = $default_language;
}

$current_language = $_SESSION['lang'];

$lang_file_path = __DIR__ . "/../languages/{$current_language}.php";
$default_lang_file_path = __DIR__ . "/../languages/{$default_language}.php";

$lang_loaded_successfully = false;
if (file_exists($lang_file_path)) {
    // Attempt to include the current language file.
    // The included file should define the $lang array.
    include $lang_file_path;
    if (isset($lang) && is_array($lang) && !empty($lang)) {
        $lang_loaded_successfully = true;
    }
}

// If the current language file failed to load or didn't define $lang properly,
// load the default language file as a fallback.
if (!$lang_loaded_successfully) {
    if (file_exists($default_lang_file_path)) {
        include $default_lang_file_path; // This should define $lang from en.php
        // If even the default language fails, $lang might still be unset,
        // and _t() will rely on its default parameter.
        // To be absolutely sure $lang is an array for _t(), initialize it.
        if (!isset($lang) || !is_array($lang)) {
            $lang = []; // Initialize $lang as an empty array if default also failed.
        }
        // Optionally, set current_language to default_language if fallback occurred
        // $_SESSION['lang'] = $default_language; // This might be too aggressive if user explicitly chose a language that's just missing content
        // $current_language = $default_language;
    } else {
        // This is a critical error: default language file is missing.
        // Initialize $lang as an empty array so _t() doesn't error on global $lang.
        $lang = [];
    }
}

function _t($key, $default = '') {
    global $lang; // $lang should now be populated either by current or default language file
    return isset($lang[$key]) ? $lang[$key] : $default;
}