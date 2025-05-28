<!DOCTYPE html>
<html lang="en"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>YouTube Video Downloader</title>
<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&amp;display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="assets/css/style.css"/>
<style>
        body {
            font-family: 'Roboto', sans-serif;
        }
    </style>
</head>
<body class="bg-blue-50">
<?php 
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    include("includes/header.php"); 
?>
<main>
<section class="bg-gradient-to-b from-blue-100 to-blue-50 py-16">
<div class="container mx-auto px-6 text-center">
<div class="bg-white p-8 md:p-12 rounded-xl shadow-xl max-w-3xl mx-auto">
<h1 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4">YouTube Video Downloader</h1>
<p class="text-gray-600 mb-8">Download YouTube videos to mp3 and mp4 online for free.</p>
<form id="video-url-form" class="flex flex-col sm:flex-row items-center justify-center space-y-4 sm:space-y-0 sm:space-x-2">
<input name="url" class="flex-grow w-full sm:w-auto p-4 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none" placeholder="Search keywords or paste video link here" type="text" required/>
<button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-4 px-6 rounded-lg flex items-center justify-center w-full sm:w-auto">
<span class="material-icons mr-2">search</span>
                            Download
                        </button>
</form>
<p class="text-xs text-gray-500 mt-4 flex items-center justify-center">
                        Copyrighted content is not available for download with this tool.
                        <span class="material-icons text-sm ml-1">info_outline</span>
</p>
</div>
</div>
</section>
<div id="video-info-container" class="mt-8"></div>
<section class="py-16">
<div class="container mx-auto px-6">
<p class="text-gray-700 text-center max-w-3xl mx-auto mb-12">
                    YouTube is the biggest YouTube video sharing platform in the world, and provides an excellent experience for users to upload, watch, and share videos. You can easily find the videos you want. With ytid.com, you can download YouTube videos to your device for offline viewing.
                    <br/><br/>
                    With our YouTube video downloader, you can search for and download videos, Shorts, and music tracks directly from YouTube. You can also convert YouTube videos to MP3, allowing you to easily listen to audio tracks. With just a single click, ensure seamless saving and sharing. Ready to try? Paste your video link and start downloading instantly!
                </p>
<div class="grid md:grid-cols-3 gap-8 text-center">
<div class="bg-white p-6 rounded-lg shadow-lg">
<span class="material-icons text-5xl text-blue-500 mb-4">transform</span>
<h3 class="text-xl font-semibold text-gray-800 mb-2">Versatility in Format</h3>
<p class="text-gray-600">Download YouTube videos to MP4/MP3 format. Convert YouTube video files, catering to diverse needs such as offline playback, video editing, and professional editing.</p>
</div>
<div class="bg-white p-6 rounded-lg shadow-lg">
<span class="material-icons text-5xl text-blue-500 mb-4">high_quality</span>
<h3 class="text-xl font-semibold text-gray-800 mb-2">High-Quality Options</h3>
<p class="text-gray-600">Download YouTube videos, Shorts, and music to MP3, MP4 formats in original full HD, 1080p, 4k and even 8k.</p>
</div>
<div class="bg-white p-6 rounded-lg shadow-lg">
<span class="material-icons text-5xl text-blue-500 mb-4">price_check</span>
<h3 class="text-xl font-semibold text-gray-800 mb-2">Absolutely Free</h3>
<p class="text-gray-600">Enjoy unlimited YouTube video and music downloads with our ytid.com without spending a dime. ytid is committed to providing a completely free service for all users.</p>
</div>
<div class="bg-white p-6 rounded-lg shadow-lg">
<span class="material-icons text-5xl text-blue-500 mb-4">bolt</span>
<h3 class="text-xl font-semibold text-gray-800 mb-2">Fastly Downloads</h3>
<p class="text-gray-600">ytid offers a fast YouTube video downloader. All downloads can be completed with incredible, providing you with high-speed downloads.</p>
</div>
<div class="bg-white p-6 rounded-lg shadow-lg">
<span class="material-icons text-5xl text-blue-500 mb-4">no_encryption</span>
<h3 class="text-xl font-semibold text-gray-800 mb-2">No Sign-up Required</h3>
<p class="text-gray-600">Our safe YouTube downloader ensures that your online data and user privacy are our top priorities. No login is required, and we do not store your user private information.</p>
</div>
<div class="bg-white p-6 rounded-lg shadow-lg">
<span class="material-icons text-5xl text-blue-500 mb-4">devices</span>
<h3 class="text-xl font-semibold text-gray-800 mb-2">Cross-Platform Compatibility</h3>
<p class="text-gray-600">Download YouTube videos instantly across various systems and electronic devices through any web browser (Chrome/Safari) without client installation required.</p>
</div>
</div>
</div>
</section>
<section class="py-16 bg-white">
<div class="container mx-auto px-6">
<h2 class="text-3xl font-bold text-gray-800 text-center mb-4">The Best Free Online Youtube Downloader</h2>
<p class="text-gray-700 text-center max-w-3xl mx-auto mb-12">
                    Our YouTube downloader is easy to use! Easily download YouTube videos, Shorts, and music. Just visit our website – ytid.com on your device and start enjoying free content now!
                </p>
<h3 class="text-2xl font-semibold text-gray-800 text-center mb-8">How to download Youtube videos online via YTiD</h3>
<ol class="list-decimal list-inside space-y-4 max-w-xl mx-auto text-gray-700">
<li>Copy the youtube link of the video and paste it into the input line.</li>
<li>Click "Download" and wait for the video to be ready.</li>
<li>Select the desired download options and click "Download".</li>
</ol>
</div>
</section>
<section class="py-16">
<div class="container mx-auto px-6">
<h3 class="text-2xl font-semibold text-gray-800 text-center mb-8">How to Use YTiD downloader Short Domain?</h3>
<div class="flex justify-center mb-8">
<img alt="Example of YouTube link modification for download" class="rounded-lg shadow-lg" src="https://lh3.googleusercontent.com/aida-public/AB6AXuCkmQLjx50v6PbwySgbuGFXWCFLoiM3e6tpIejutITzm6RxLZPDuK1QbKyeMmNNhRygbz7e1LW-zmAoCyBv9WY6x5e5XJNnZ5y6HAIXsfwMX3-4a3CqYFF7i_4BZJ7dio8PTSivYcuY4K0houUzjrrzrsfAHdhDnF8IFkKvyRl26efTAU_lp8-VPyS4WhVgOOJb9nfofGINBT7Qdx3nnceBqcU4O7N3Fff9y3epaokyMbAMSzquqIu-G3XtgfJmaBPl6G-PEzL-iqUx"/>
</div>
<div class="grid md:grid-cols-3 gap-8 text-left">
<div class="bg-white p-6 rounded-lg shadow-lg">
<h4 class="font-semibold text-gray-800 mb-2">Open Youtube URL</h4>
<p class="text-gray-600">Open the target video in YouTube that you want to download.</p>
</div>
<div class="bg-white p-6 rounded-lg shadow-lg">
<h4 class="font-semibold text-gray-800 mb-2">Add "yd" before the video URL</h4>
<p class="text-gray-600">Add "yd" before the video URL to start the downloading process.</p>
</div>
<div class="bg-white p-6 rounded-lg shadow-lg">
<h4 class="font-semibold text-gray-800 mb-2">YT to MP4 will launch quickly</h4>
<p class="text-gray-600">After pressing the "Enter" button, you will be redirected to the page with several downloading options.</p>
</div>
</div>
</div>
</section>
</main>
<?php include("includes/footer.php"); ?>
</body></html>
