# PHP YouTube Downloader & Tools

This project provides a web interface for downloading YouTube videos in various formats (MP4, MP3) and also includes tools for fetching video thumbnails. It is built with PHP and uses `yt-dlp` (a fork of youtube-dl) as the core engine for interacting with YouTube.

## Features

*   **Video Downloading**: Download YouTube videos in different resolutions and formats.
*   **Audio Extraction**: Convert YouTube videos to MP3 audio.
*   **Thumbnail Downloader**: Fetch and download thumbnails for YouTube videos in various qualities.
*   **Search Functionality**: Search for YouTube videos directly from the interface.
*   **AJAX-based Interface**: Modern, dynamic user experience for fetching video info and search results.
*   **Localization Support**: Interface text can be translated into multiple languages (currently setup for English).
*   **Configurable**: Key settings like `yt-dlp` path and error reporting can be configured.

## Requirements

*   **PHP**: Version 7.4 or higher recommended.
    *   Required PHP extensions: `json`, `mbstring`.
*   **yt-dlp**: The `yt-dlp` executable (or a compatible fork like `yt-dlp_x86`) must be installed on the server and accessible via the system's PATH, or its full path must be specified in `config.php`.
*   **Web Server**: Apache, Nginx, or any other web server capable of running PHP scripts.
    *   Ensure the web server has write permissions for the log file path if error logging to a file is enabled in `config.php`.
*   **Composer**: Not strictly required for the current setup, but good practice if PHP dependencies were to be managed.

## Setup

1.  **Clone the Repository**:
    ```bash
    git clone <repository_url>
    cd <repository_directory>
    ```

2.  **Install `yt-dlp`**:
    *   Follow the official installation instructions for `yt-dlp` for your server's operating system: [yt-dlp GitHub](https://github.com/yt-dlp/yt-dlp#installation)
    *   Ensure the `yt-dlp` binary is executable and preferably in the system's PATH.

3.  **Configure `config.php`**:
    *   Copy `config.example.php` to `config.php` if a template is provided (otherwise, create `config.php` directly if it doesn't exist from previous steps).
    *   **`IS_PRODUCTION`**: Set to `true` for production environments (disables detailed PHP error display, enables logging to file if configured). Set to `false` for development (shows all PHP errors).
        ```php
        define('IS_PRODUCTION', false); // Default to false for development
        ```
    *   **`YT_DLP_PATH`**: Specify the path to your `yt-dlp` executable. If `yt-dlp` is in your system's PATH, you can usually leave this as `'yt-dlp'`.
        ```php
        define('YT_DLP_PATH', 'yt-dlp');
        // Example for a specific path:
        // define('YT_DLP_PATH', '/usr/local/bin/yt-dlp');
        ```
    *   **`LOG_FILE_PATH`**: (Optional) Define a path to a log file where errors should be written if `IS_PRODUCTION` is `true`. Ensure the web server has write permissions to this file/directory.
        ```php
        // Example: define('LOG_FILE_PATH', __DIR__ . '/logs/app_errors.log');
        define('LOG_FILE_PATH', null); // Default: no file logging
        ```
        If you set a path, make sure the `logs` directory (or your chosen directory) exists and is writable.

4.  **Web Server Configuration**:
    *   Ensure your web server is configured to serve PHP files from the project directory.
    *   For Apache, you might need an `.htaccess` file if using friendly URLs (though this project currently uses direct script access).
    *   For Nginx, ensure your `location` block for PHP files is correctly set up.
    *   URL Rewriting: Not strictly required by the current file structure, but if you intend to use cleaner URLs, configure your web server accordingly (e.g. `mod_rewrite` for Apache).

5.  **Permissions**:
    *   Ensure the web server has execute permissions on the `yt-dlp` binary if its path is directly specified.
    *   If file logging is enabled, ensure the log directory/file is writable by the web server user (e.g., `www-data`).

## Usage

*   **Main Downloader (Video/Search)**: Access `index.php` in your browser. You can paste a YouTube video URL to get download options or type search terms to find videos.
*   **YouTube to MP3**: Access `youtube-to-mp3.php`. Paste a YouTube video URL to get MP3 conversion options.
*   **YouTube to MP4**: Access `youtube-to-mp4.php`. Paste a YouTube video URL to get MP4 download options.
*   **Thumbnail Downloader**: Access `youtube-thumbnail-downloader.php`. Paste a YouTube video URL to see and download available thumbnail images.

## Localization

*   Language strings are managed in files within the `languages/` directory (e.g., `languages/en.php`, `languages/es.php`).
*   The active language is determined by the `?lang=` query parameter (e.g., `index.php?lang=es`). If not set, it defaults to English (`en`).
*   To add a new language:
    1.  Copy `languages/en.php` to a new file, e.g., `languages/fr.php` for French.
    2.  Translate the strings in the new file.
    3.  Update `includes/language.php` to include the new language in the `$available_languages` array and provide a corresponding country flag image if desired.

## Troubleshooting

*   **`yt-dlp` command failed**:
    *   Verify `YT_DLP_PATH` in `config.php` is correct and `yt-dlp` is executable by the web server user.
    *   Test `yt-dlp` from the command line on your server with a sample URL to ensure it's working.
    *   Check for errors in your web server's error logs and the PHP error log (if `LOG_FILE_PATH` is set in `config.php`).
    *   Ensure the version of `yt-dlp` is up-to-date, as YouTube often makes changes that can break older versions.
*   **PHP Errors / Blank Page**:
    *   Set `IS_PRODUCTION` to `false` in `config.php` during debugging to see detailed error messages.
    *   Check PHP error logs.
*   **File Download Issues**:
    *   Ensure no output is sent before `header()` calls in `download.php` (PHP's output buffering can affect this). The `YtDlpWrapper::streamMedia` method attempts to handle this.
*   **Permission Denied**:
    *   If `yt-dlp` execution fails with permission errors, check file permissions of the `yt-dlp` executable.
    *   If logging to file fails, check write permissions for the log directory/file.

This README provides a basic guide. Depending on your server setup, further configuration might be necessary.
