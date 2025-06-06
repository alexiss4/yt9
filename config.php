<?php
// config.php

// Environment Setting
// Set to false for development/debugging, true for production
define('IS_PRODUCTION', true);

// yt-dlp Path (leave as 'yt-dlp' if it's in system PATH)
define('YT_DLP_PATH', __DIR__ . '/yt-dlp');

// Log File Path (ensure this path is writable by the web server)
// Example: define('LOG_FILE_PATH', __DIR__ . '/logs/app_errors.log');
define('LOG_FILE_PATH', __DIR__ . '/phperrors.log');

// Function to set error reporting based on IS_PRODUCTION
function setup_error_handling() {
    if (IS_PRODUCTION) {
        ini_set('display_errors', 0);
        ini_set('display_startup_errors', 0);
        // error_reporting(0); // This might be too restrictive for logging some things, let log_errors handle it.
        error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT); // Log most things, but not dev clutter
        if (LOG_FILE_PATH) { // Check if LOG_FILE_PATH is not null or empty
            ini_set('log_errors', 1);
            ini_set('error_log', LOG_FILE_PATH);
        } else {
            // Fallback if LOG_FILE_PATH is somehow not set, though it should be.
            // This might log to the default web server error log.
            ini_set('log_errors', 1);
        }
    } else {
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);
    }
}

// Call the function to set up error handling
setup_error_handling();
