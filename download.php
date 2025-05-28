<?php
if (isset($_GET['url'])) {
    $url = $_GET['url'];
    echo "<h2>Processing download for: <span style='color:blue'>$url</span></h2>";
    // لاحقًا يمكن استخدام yt-dlp أو youtube-dl هنا
} else {
    echo "<p>No URL provided.</p>";
}
?>
