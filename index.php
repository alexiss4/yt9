<?php
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    ?>
<?php include("includes/header.php"); ?>

<main>
  <!-- Hero Section -->
  <section class="bg-gradient-to-b from-blue-100 to-blue-50 py-16">
    <div class="container mx-auto px-6 text-center">
      <div class="bg-white p-8 md:p-12 rounded-xl shadow-xl max-w-3xl mx-auto">
        <h1 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4">YouTube Video Downloader</h1>
        <p class="text-gray-600 mb-8">Download YouTube videos to mp3 and mp4 online for free.</p>
        <form id="video-url-form" method="GET" action="download.php" class="flex flex-col sm:flex-row items-center justify-center space-y-4 sm:space-y-0 sm:space-x-2">
          <input name="url" class="flex-grow w-full sm:w-auto p-4 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none" placeholder="Paste YouTube link here" type="text" required/>
          <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-4 px-6 rounded-lg flex items-center justify-center w-full sm:w-auto">
            <span class="material-icons mr-2">search</span>
            Download
          </button>
        </form>
        <div id="video-info-container" class="mt-8"></div> <!-- New container for video info -->
        <p class="text-xs text-gray-500 mt-4 flex items-center justify-center">
          Copyrighted content is not available for download.
          <span class="material-icons text-sm ml-1">info_outline</span>
        </p>
      </div>
    </div>
  </section>

  <!-- Features Section -->
  <section class="py-16">
    <div class="container mx-auto px-6">
      <div class="grid md:grid-cols-3 gap-8 text-center">
        <!-- يمكنك هنا إعادة الكروت التي أدرجتها في السكريبت -->
        <!-- سأضع مثالاً واحداً فقط: -->
        <div class="bg-white p-6 rounded-lg shadow-lg">
          <span class="material-icons text-5xl text-blue-500 mb-4">transform</span>
          <h3 class="text-xl font-semibold text-gray-800 mb-2">Versatility in Format</h3>
          <p class="text-gray-600">Download YouTube videos in various formats like MP4 and MP3, choosing the quality that suits your needs.</p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-lg">
          <span class="material-icons text-5xl text-blue-500 mb-4">speed</span>
          <h3 class="text-xl font-semibold text-gray-800 mb-2">High-Speed Downloads</h3>
          <p class="text-gray-600">Utilizes efficient technology to quickly process and download your favorite videos without long waits.</p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-lg">
          <span class="material-icons text-5xl text-blue-500 mb-4">person_off</span>
          <h3 class="text-xl font-semibold text-gray-800 mb-2">Easy & Anonymous</h3>
          <p class="text-gray-600">Download videos freely without any registration or personal data collection. Simple and direct.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- How-To Section -->
  <section class="py-16 bg-white">
    <div class="container mx-auto px-6">
      <h2 class="text-3xl font-bold text-gray-800 text-center mb-4">Your Simple YouTube Video Downloader</h2>
      <p class="text-gray-700 text-center max-w-3xl mx-auto mb-12">YTiD makes it easy to save YouTube videos for offline viewing. Just paste a link, choose your format, and download. Our service is fast, free, and requires no software installation.</p>
      <h3 class="text-2xl font-semibold text-gray-800 text-center mb-8">How to download Youtube videos online via YTiD</h3>
      <ol class="list-decimal list-inside space-y-4 max-w-xl mx-auto text-gray-700">
        <li><strong>Find Your Video:</strong> Navigate to YouTube and copy the full URL of the video you wish to download from your browser's address bar.</li>
        <li><strong>Paste the Link:</strong> Paste the copied YouTube URL into the input field on our homepage.</li>
        <li><strong>Initiate Search:</strong> Click the "Download" button to allow our tool to fetch video information and available formats.</li>
        <li><strong>Select Format & Download:</strong> Once the options appear, choose your desired video quality (e.g., MP4 720p) or audio format (MP3). Click the corresponding download button, and your file will begin downloading.</li>
      </ol>
    </div>
  </section>
</main>

<?php include("includes/footer.php"); ?>
