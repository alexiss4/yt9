/**
 * Main script for handling frontend interactions for the YouTube Downloader.
 * This includes AJAX form submissions for video URL/search, dynamic loading of
 * video formats on download pages, and displaying results/errors.
 */
document.addEventListener('DOMContentLoaded', () => {
    const mainElement = document.querySelector('main');
    const isDownloadPage = window.location.pathname.includes('download.php');
    const urlParamsDL = new URLSearchParams(window.location.search);
    const videoUrlDL = urlParamsDL.get('url');

    // --- Helper Functions ---

    /**
     * Creates and displays an error message within a specified container.
     * @param {string} message - The error message to display.
     * @param {HTMLElement} container - The HTML element to display the error message in.
     */
    function createErrorMessage(message, container) {
        container.innerHTML = `<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative text-center" role="alert">
                                <strong class="font-bold">Error:</strong>
                                <span class="block sm:inline">${message}</span>
                            </div>`;
    }

    /**
     * Creates and displays a loading message with a spinner within a specified container.
     * @param {string} message - The loading message to display.
     * @param {HTMLElement} container - The HTML element to display the loading message in.
     */
    function createLoadingMessage(message, container) {
        container.innerHTML = `<div class="flex items-center justify-center text-slate-500 py-4">
                                <svg class="animate-spin h-5 w-5 mr-3 text-sky-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                ${message}
                            </div>`;
    }

    // --- Logic for download.php (Dynamic Format Loading) ---
    /**
     * Handles dynamic loading of video formats on the download.php page.
     * If on download.php and a URL is provided, it fetches format info via AJAX.
     */
    if (isDownloadPage && videoUrlDL && mainElement) {
        const phpFormatList = document.getElementById('php-format-list');
        if (phpFormatList) phpFormatList.style.display = 'none'; // Hide static list if JS active

        let dlContainer = document.getElementById('download-options-container');
        if (!dlContainer) { // Create container if not present in static HTML (fallback)
            dlContainer = document.createElement('div');
            dlContainer.id = 'download-options-container';
            dlContainer.className = 'bg-white shadow-lg rounded-lg p-6'; // Apply some default styling
            // Try to insert it before the footer or append to main
            const footer = document.querySelector('footer');
            if (footer) {
                mainElement.insertBefore(dlContainer, footer);
            } else {
                mainElement.appendChild(dlContainer);
            }
        }
        createLoadingMessage('Loading video formats...', dlContainer);

        fetch(`download.php?url=${encodeURIComponent(videoUrlDL)}&json=1`)
            .then(response => {
                if (!response.ok) {
                    return response.json().then(errData => { throw new Error(errData.error || `Server error: ${response.status}. Please try again.`); })
                                     .catch(() => { throw new Error(`Server error: ${response.status}. Could not retrieve format details.`); });
                }
                return response.json();
            })
            .then(data => {
                dlContainer.innerHTML = ''; 
                if (data.error) throw new Error(data.error);
                displayVideoDownloadOptions(data, videoUrlDL, dlContainer, true); // isDownloadPageContext = true
            })
            .catch(error => {
                console.error('Error fetching video info for download.php:', error);
                createErrorMessage(error.message || 'Could not load video information. Please try again.', dlContainer);
            });
    }

    // --- Logic for index.php and other pages with video URL form (AJAX Form Submission: URL or Search) ---
    const videoUrlForm = document.getElementById('video-url-form') || 
                         document.getElementById('video-url-form-mp3') || 
                         document.getElementById('video-url-form-mp4');
                         // Thumbnail page form ('video-url-form-thumbnail') is not handled by AJAX here, it uses standard POST.

    if (videoUrlForm) {
        // Determine container based on form ID or a generic one
        let videoInfoContainer;
        if (videoUrlForm.id === 'video-url-form-mp3') {
            videoInfoContainer = document.getElementById('conversion-results-container');
        } else if (videoUrlForm.id === 'video-url-form-mp4') {
            videoInfoContainer = document.getElementById('conversion-results-container-mp4');
        } else { // Default for index.php
            videoInfoContainer = document.getElementById('video-info-container');
        }

        const urlInput = videoUrlForm.querySelector('input[name="url"]');
        const submitButton = videoUrlForm.querySelector('button[type="submit"]');

        /**
         * Handles the submission of the main video URL form (index.php, mp3, mp4 pages).
         * It determines if the input is a URL or a search query and calls the appropriate function.
         * @param {Event} event - The form submission event.
         */
        videoUrlForm.addEventListener('submit', (event) => {
            event.preventDefault();
            if (!videoInfoContainer) { // Ensure container exists
                console.error("Video info container not found for form:", videoUrlForm.id);
                return;
            }
            const inputValue = urlInput.value.trim();
            const originalButtonText = submitButton.innerHTML;

            createLoadingMessage('Processing your request...', videoInfoContainer);
            submitButton.disabled = true;
            submitButton.innerHTML = `<svg class="animate-spin h-5 w-5 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Processing...`;

            if (!inputValue) {
                createErrorMessage('Please enter a YouTube URL or search query.', videoInfoContainer);
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonText;
                return;
            }

            // Regex to identify YouTube video URLs
            const youtubeRegex = /^(https?:\/\/)?(www\.)?(youtube\.com\/(watch\?v=|shorts\/|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/;
            const isUrl = youtubeRegex.test(inputValue);

            if (isUrl) {
                fetchVideoInfo(inputValue, videoInfoContainer, submitButton, originalButtonText);
            } else {
                // Only perform search if on index.php (video-url-form)
                if (videoUrlForm.id === 'video-url-form') {
                    searchVideos(inputValue, videoInfoContainer, submitButton, originalButtonText);
                } else {
                    createErrorMessage('Invalid YouTube URL. Please enter a valid video link for conversion.', videoInfoContainer);
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalButtonText;
                }
            }
        });
    }

    /**
     * Fetches video information (title, thumbnail, formats) from the API for a given YouTube URL.
     * @param {string} videoUrl - The YouTube video URL.
     * @param {HTMLElement} container - The HTML element to display results or errors.
     * @param {HTMLElement|null} button - The submit button element (optional, for disabling/reenabling).
     * @param {string|null} originalButtonText - The original text of the submit button (optional).
     */
    function fetchVideoInfo(videoUrl, container, button, originalButtonText) {
        createLoadingMessage('Fetching video information...', container);
        fetch(`api.php?action=getVideoInfo&url=${encodeURIComponent(videoUrl)}`)
            .then(response => {
                if (button) { 
                    button.disabled = false;
                    button.innerHTML = originalButtonText;
                }
                if (!response.ok) {
                    return response.json().then(errData => { throw new Error(errData.error || `Server error (${response.status}).`); })
                                     .catch(() => { throw new Error(`Server error (${response.status}). Could not retrieve video details.`); });
                }
                return response.json();
            })
            .then(data => {
                container.innerHTML = ''; // Clear loading message
                if (data.error) throw new Error(data.error);
                displayVideoDownloadOptions(data, videoUrl, container, false); // isDownloadPageContext = false
            })
            .catch(error => {
                console.error('Error fetching video info via API:', error);
                createErrorMessage(error.message || 'Could not load video information. Please try again.', container);
                if (button) { // Ensure button is re-enabled on error too
                    button.disabled = false;
                    button.innerHTML = originalButtonText;
                }
            });
    }

    /**
     * Performs a video search using the API based on a query.
     * Displays search results or an error message.
     * @param {string} query - The search query.
     * @param {HTMLElement} container - The HTML element to display search results or errors.
     * @param {HTMLElement} button - The submit button element.
     * @param {string} originalButtonText - The original text of the submit button.
     */
    function searchVideos(query, container, button, originalButtonText) {
        createLoadingMessage(`Searching for "${query}"...`, container);
        fetch(`api.php?action=searchVideos&query=${encodeURIComponent(query)}`)
            .then(response => {
                if (button) { // Re-enable button once response starts
                     button.disabled = false;
                     button.innerHTML = originalButtonText;
                }
                if (!response.ok) {
                    return response.json().then(errData => { throw new Error(errData.error || `Search error (${response.status}).`); })
                                     .catch(() => { throw new Error(`Search error (${response.status}). Could not retrieve search results.`); });
                }
                return response.json();
            })
            .then(data => {
                container.innerHTML = ''; // Clear loading/previous results
                if (data.error) throw new Error(data.error); // API returned an error object

                if (data.results && data.results.length > 0) {
                    const searchResultsTitle = document.createElement('h3');
                    searchResultsTitle.className = 'text-2xl font-semibold mb-4 text-gray-800 text-center';
                    searchResultsTitle.textContent = 'Search Results:';
                    container.appendChild(searchResultsTitle);

                    data.results.forEach(video => {
                        const videoElement = document.createElement('div');
                        // Styling for each search result item
                        videoElement.className = 'p-4 mb-4 border rounded-lg flex flex-col sm:flex-row items-start sm:space-x-4 shadow-md hover:shadow-lg transition-shadow';
                        
                        const thumbnail = `<img src="${video.thumbnail_url}" alt="Thumbnail for ${video.title}" class="w-full sm:w-40 sm:h-24 object-cover rounded mb-3 sm:mb-0">`;
                        
                        const detailsDiv = document.createElement('div');
                        detailsDiv.className = 'flex-grow';
                        detailsDiv.innerHTML = `
                            <h4 class="text-lg font-semibold text-sky-600">${video.title}</h4>
                            <p class="text-sm text-slate-600">By: ${video.uploader || 'N/A'}</p>
                            <p class="text-sm text-slate-500">Duration: ${video.duration_string || 'N/A'}</p>
                        `;

                        // "Get Download Links" button for each search result
                        const getFormatsBtn = document.createElement('button');
                        getFormatsBtn.className = 'get-formats-btn mt-3 py-2 px-4 bg-emerald-500 text-white rounded hover:bg-emerald-600 transition duration-150 text-sm';
                        getFormatsBtn.textContent = 'Get Download Links';
                        getFormatsBtn.dataset.videoUrl = video.url; 
                        // Store other details if needed for displayVideoDownloadOptions context, though not strictly used by fetchVideoInfo
                        getFormatsBtn.dataset.videoTitle = video.title; 
                        getFormatsBtn.dataset.videoThumbnail = video.thumbnail_url;

                        detailsDiv.appendChild(getFormatsBtn);
                        videoElement.innerHTML = thumbnail; 
                        videoElement.appendChild(detailsDiv); 
                        container.appendChild(videoElement);
                    });

                    addFormatButtonListeners(); // Add listeners to the newly created buttons
                } else {
                    // Handle no results found
                    container.innerHTML = '<p class="text-slate-600 text-center py-4">No videos found for your query. Please try different keywords.</p>';
                }
            })
            .catch(error => { // Catch network errors or errors thrown from .then()
                console.error('Error fetching search results:', error);
                createErrorMessage(error.message || 'Could not perform search. Please try again.', container);
                 if (button) { // Ensure button is re-enabled on error
                    button.disabled = false;
                    button.innerHTML = originalButtonText;
                }
            });
    }

    /**
     * Adds event listeners to "Get Download Links" buttons generated after a search.
     * When clicked, these buttons will fetch and display download options for the respective video.
     */
    function addFormatButtonListeners() {
        document.querySelectorAll('.get-formats-btn').forEach(button => {
            button.addEventListener('click', (event) => {
                const videoUrl = event.target.dataset.videoUrl;
                // Re-use the main video info container from index.php to display formats.
                // This implies search results will be cleared.
                const videoInfoContainer = document.getElementById('video-info-container'); 
                if (videoUrl && videoInfoContainer) {
                    // Call fetchVideoInfo without button context as the main form's button isn't involved here.
                    fetchVideoInfo(videoUrl, videoInfoContainer, null, null); 
                } else {
                    console.error("Could not find video URL or container for format fetching from search result.");
                    if(videoInfoContainer) createErrorMessage("Could not retrieve video URL to fetch formats.", videoInfoContainer);
                }
            });
        });
    }
    
    /**
     * Displays video title, thumbnail, and download format options in a specified container.
     * @param {object} data - The video information object from the API (should include title, thumbnail_url, formats).
     * @param {string} videoUrl - The original YouTube video URL (for generating download links).
     * @param {HTMLElement} container - The HTML element to display the information in.
     * @param {boolean} isDownloadPageContext - True if called from download.php, false if from index.php (search/direct URL). Affects styling.
     */
    function displayVideoDownloadOptions(data, videoUrl, container, isDownloadPageContext = false) {
        // Clear previous content in the specific container
        container.innerHTML = ''; 

        if (data.title) {
            const titleElement = document.createElement('h3');
            // Use slightly different styling based on context (index vs download page)
            titleElement.className = isDownloadPage ? 'text-2xl font-bold text-slate-800 mb-2' : 'text-xl font-semibold mb-3 text-slate-800 text-center';
            titleElement.textContent = isDownloadPage ? `Download Video: ${data.title}` : data.title;
            container.appendChild(titleElement);
        }

        const thumbUrl = data.thumbnail_url || data.thumbnail; // API uses thumbnail_url, download.php JSON uses thumbnail
        if (thumbUrl) {
            const thumbnailElement = document.createElement('img');
            thumbnailElement.src = thumbUrl;
            thumbnailElement.alt = 'Video Thumbnail';
            thumbnailElement.className = isDownloadPage ? 'my-4 rounded-lg shadow-md' : 'my-4 rounded shadow-lg w-full max-w-sm mx-auto';
            if(isDownloadPage) thumbnailElement.style.maxWidth = '320px';
            if(isDownloadPage) thumbnailElement.style.marginLeft = 'auto';
            if(isDownloadPage) thumbnailElement.style.marginRight = 'auto';
            container.appendChild(thumbnailElement);
        }

        if (data.formats && data.formats.length > 0) {
            const formatsTitle = document.createElement('h4');
            formatsTitle.className = isDownloadPage ? 'text-xl font-semibold text-slate-700 mb-4 mt-5' : 'text-lg font-medium text-slate-700 mb-3 mt-5 text-center';
            formatsTitle.textContent = 'Available Download Options:';
            container.appendChild(formatsTitle);

            const listElement = document.createElement(isDownloadPage ? 'ul' : 'div');
            listElement.className = isDownloadPage ? 'space-y-3' : 'flex flex-wrap justify-center gap-3';
            
            data.formats.forEach(format => {
                const linkElement = document.createElement('a');
                linkElement.href = `download.php?url=${encodeURIComponent(videoUrl)}&format_id=${encodeURIComponent(format.id || format.format_id)}`; // format.id from API, format.format_id from download.php JSON
                
                if (isDownloadPage) {
                    const listItem = document.createElement('li');
                    listItem.className = 'p-3 bg-gray-50 rounded-md shadow-sm hover:bg-gray-100 transition duration-150';
                    linkElement.className = 'block text-sky-600 hover:text-sky-700 font-medium';
                    let desc = format.description || `${format.ext} - ${format.resolution || format.format_note}`;
                    linkElement.innerHTML = `<span class="material-icons text-sm mr-2 align-middle">${(format.id || format.format_id) === 'mp3' || (format.resolution === 'Audio' || (format.label && format.label.toLowerCase().includes('audio')) ) ? 'audiotrack' : 'videocam'}</span> ${desc}`;
                    listItem.appendChild(linkElement);
                    listElement.appendChild(listItem);
                } else {
                    linkElement.className = 'py-2 px-4 bg-emerald-500 text-white rounded hover:bg-emerald-600 my-1 inline-block text-sm shadow transition duration-150 ease-in-out transform hover:-translate-y-1';
                    linkElement.textContent = format.label || `${format.ext.toUpperCase()} ${format.url_quality || (format.id || format.format_id)}`;
                    const icon = document.createElement('span');
                    icon.className = 'material-icons text-sm mr-1 align-middle';
                    icon.textContent = (format.type === 'mp3' || (format.label && format.label.toLowerCase().includes('audio'))) ? 'audiotrack' : 'videocam';
                    linkElement.prepend(icon);
                    listElement.appendChild(linkElement);
                }
            });
            container.appendChild(listElement);
        } else {
            container.innerHTML += '<p class="text-slate-600 text-center py-4">No suitable download formats found for this video. It might be a livestream or have unusual encoding.</p>';
        }
    }
});
