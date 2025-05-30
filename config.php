<?php
// config.php

// Environment Setting
// Set to false for development/debugging, true for production
define('IS_PRODUCTION', true);

// yt-dlp Path (leave as 'yt-dlp' if it's in system PATH)
define('YT_DLP_PATH', 'yt-dlp');

// Log File Path (ensure this path is writable by the web server)
// Example: define('LOG_FILE_PATH', __DIR__ . '/logs/app_errors.log');
// For now, leave it null or handle logging setup later.
define('LOG_FILE_PATH', null);

// Function to set error reporting based on IS_PRODUCTION
function setup_error_handling() {
    if (IS_PRODUCTION) {
        ini_set('display_errors', 0);
        ini_set('display_startup_errors', 0);
        error_reporting(0); // Report no errors to the screen
        if (LOG_FILE_PATH) {
            ini_set('log_errors', 1);
            ini_set('error_log', LOG_FILE_PATH);
        }
    } else {
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);
    }
}

// Call the function to set up error handling
setup_error_handling();

?>
