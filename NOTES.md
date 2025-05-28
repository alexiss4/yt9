# Project Notes

## "yd" Short Domain Feature (e.g., ydyoutube.com)

The `index.php` page includes a section describing a feature where users can prepend "yd" to a YouTube domain (e.g., `ydyoutube.com/watch?v=VIDEO_ID`) to quickly access the downloader for that video.

Implementing this feature fully requires server-level configurations:

1.  **DNS Configuration:**
    *   A short domain (e.g., `ydyoutube.com`, `ydyt.com`, or similar) would need to be registered.
    *   The DNS records for this short domain would need to be pointed to the same server hosting the main `ytid.com` website.

2.  **Web Server Configuration (Apache or Nginx):**
    *   **Apache (`.htaccess` or virtual host config):**
        Rewrite rules would be needed to capture requests to the short domain and internally redirect or proxy them to the main application, passing the original YouTube path/query.
        Example (conceptual):
        ```apache
        RewriteEngine On
        RewriteCond %{HTTP_HOST} ^(www\.)?ydyoutube\.com$ [NC]
        RewriteRule ^(.*)$ https://ytid.com/index.php?url=https://youtube.com/$1 [P,L,QSA]
        # Or, if you want to pass it to a specific handler:
        # RewriteRule ^(.*)$ https://ytid.com/shortdomain_handler.php?path=$1 [P,L,QSA]
        ```
        *(Note: The exact rule would depend on how `yt-dlp` expects the URL and how the main script (`index.php` or `download.php`) is set up to receive it. The `[P]` flag implies mod_proxy is enabled if proxying).*

    *   **Nginx (server block config):**
        A server block for the short domain would capture requests and rewrite/proxy them.
        Example (conceptual):
        ```nginx
        server {
            listen 80;
            server_name ydyoutube.com www.ydyoutube.com;

            location / {
                rewrite ^/(.*)$ https://ytid.com/index.php?url=https://youtube.com/$1 permanent;
                # Or using proxy_pass:
                # proxy_pass https://ytid.com/index.php?url=https://youtube.com/$1;
                # proxy_set_header Host $host;
                # proxy_set_header X-Real-IP $remote_addr;
                # proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
                # proxy_set_header X-Forwarded-Proto $scheme;
            }
        }
        ```

3.  **Application Handling (PHP):**
    *   The PHP script (`index.php` or a dedicated handler) would then receive the YouTube URL (e.g., `https://youtube.com/watch?v=VIDEO_ID`) in a GET parameter.
    *   The script would then proceed to fetch video info and display download options as it currently does for URLs pasted directly.
    *   The `input[name="url"]` on `index.php` could be pre-filled with this URL if the user lands on `index.php`.

This setup allows users to type a shorter, memorable domain prefix to quickly use the downloader without visiting `ytid.com` first. The current codebase is prepared to handle valid YouTube URLs passed via the `url` GET parameter to `index.php` (which then uses `api.php`) or `download.php`.
