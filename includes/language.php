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

$language_logos = [
    'en' => 'gb.png', // United Kingdom for English
    'es' => 'es.png', // Spain
    'fr' => 'fr.png', // France
    'de' => 'de.png', // Germany
    'it' => 'it.png', // Italy
    'pt' => 'pt.png', // Portugal
    'ru' => 'ru.png', // Russia
    'ja' => 'jp.png', // Japan
    'ko' => 'kr.png', // South Korea
    'zh' => 'cn.png', // China
    'ar' => 'sa.png', // Saudi Arabia for Arabic
    'hi' => 'in.png', // India
    'nl' => 'nl.png', // Netherlands
    'pl' => 'pl.png', // Poland
    'tr' => 'tr.png', // Turkey
    'sv' => 'se.png', // Sweden
    'da' => 'dk.png', // Denmark
    'fi' => 'fi.png', // Finland
    'no' => 'no.png', // Norway
    'el' => 'gr.png', // Greece
    'sw' => 'ke.png', // Kenya for Swahili (Example, can be tz, ug etc.)
    'bn' => 'bd.png', // Bangladesh for Bengali
];

$default_language = 'en';

// Determine the current language
if (isset($_GET['lang']) && array_key_exists($_GET['lang'], $available_languages)) {
    $current_language = $_GET['lang']; // Prioritize GET parameter for current request
    $_SESSION['lang'] = $current_language; // Update session
} elseif (isset($_SESSION['lang']) && array_key_exists($_SESSION['lang'], $available_languages)) {
    $current_language = $_SESSION['lang']; // Fallback to session if GET is not set or invalid
} else {
    $current_language = $default_language; // Default if no valid GET or session language
    $_SESSION['lang'] = $current_language; // Ensure session is set to default
}

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