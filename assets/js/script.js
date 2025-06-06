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
        container.classList.remove('hidden'); // Ensure container is visible for errors
        // Clear animation styles if they were added
        container.style.maxHeight = '';
        container.style.overflow = '';
    }

    /**
     * Creates and displays a loading message with a spinner within a specified container.
     * @param {string} message - The loading message to display.
     * @param {HTMLElement} container - The HTML element to display the loading message in.
     */
    function createLoadingMessage(message, container) {
        container.innerHTML = `<div class="flex items-center justify-center text-gray-600 py-4">
                                <svg class="animate-spin h-5 w-5 mr-3 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                ${message}
                            </div>`;
        container.classList.remove('hidden'); // Ensure container is visible for loading
        // Clear animation styles if they were added
        container.style.maxHeight = '';
        container.style.overflow = '';
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
        if (!dlContainer) {
            dlContainer = document.createElement('div');
            dlContainer.id = 'download-options-container';
            // Match styling of other main content boxes if this one is dynamically created
            dlContainer.className = 'bg-white p-8 md:p-12 rounded-xl shadow-xl max-w-3xl mx-auto mt-8';
            const firstSection = mainElement.querySelector('section'); // Try to insert before first section or append
            if (firstSection) {
                 mainElement.insertBefore(dlContainer, firstSection);
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
                if (data.error) throw new Error(data.error);
                displayVideoDownloadOptions(data, videoUrlDL, dlContainer);
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

    if (videoUrlForm) {
        let videoInfoContainer;
        if (videoUrlForm.id === 'video-url-form-mp3') {
            videoInfoContainer = document.getElementById('conversion-results-container');
        } else if (videoUrlForm.id === 'video-url-form-mp4') {
            videoInfoContainer = document.getElementById('conversion-results-container-mp4');
        } else {
            videoInfoContainer = document.getElementById('video-info-container');
        }

        const urlInput = videoUrlForm.querySelector('input[name="url"]');
        const submitButton = videoUrlForm.querySelector('button[type="submit"]');

        /**
         * Handles the submission of the main video URL form.
         * @param {Event} event - The form submission event.
         */
        videoUrlForm.addEventListener('submit', (event) => {
            event.preventDefault();
            if (!videoInfoContainer) {
                console.error("Video info container not found for form:", videoUrlForm.id);
                return;
            }
            const inputValue = urlInput.value.trim();
            const originalButtonHTML = submitButton.innerHTML; // Store full HTML

            createLoadingMessage('Processing your request...', videoInfoContainer);
            submitButton.disabled = true;
            submitButton.innerHTML = `<svg class="animate-spin h-5 w-5 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Processing...`;

            if (!inputValue) {
                createErrorMessage('Please enter a YouTube URL or search query.', videoInfoContainer);
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonHTML;
                return;
            }

            const youtubeRegex = /^(https?:\/\/)?(www\.)?(youtube\.com\/(watch\?v=|shorts\/|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/;
            const isUrl = youtubeRegex.test(inputValue);

            if (isUrl) {
                fetchVideoInfo(inputValue, videoInfoContainer, submitButton, originalButtonHTML);
            } else {
                if (videoUrlForm.id === 'video-url-form') { // Only index.php form supports search
                    searchVideos(inputValue, videoInfoContainer, submitButton, originalButtonHTML);
                } else {
                    createErrorMessage('Invalid YouTube URL. Please enter a valid video link for conversion.', videoInfoContainer);
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalButtonHTML;
                }
            }
        });
    }

    /**
     * Fetches video information from the API.
     * @param {string} videoUrl - The YouTube video URL.
     * @param {HTMLElement} container - The HTML element to display results/errors.
     * @param {HTMLElement|null} button - The submit button element (optional).
     * @param {string|null} originalButtonHTML - The original HTML of the submit button (optional).
     */
    function fetchVideoInfo(videoUrl, container, button, originalButtonHTML) {
        createLoadingMessage('Fetching video information...', container);
        fetch(`api.php?action=getVideoInfo&url=${encodeURIComponent(videoUrl)}`)
            .then(response => {
                if (button) {
                    button.disabled = false;
                    button.innerHTML = originalButtonHTML;
                }
                if (!response.ok) {
                    return response.json().then(errData => { throw new Error(errData.error || `Server error (${response.status}).`); })
                                     .catch(() => { throw new Error(`Server error (${response.status}). Could not retrieve video details.`); });
                }
                return response.json();
            })
            .then(data => {
                if (data.error) throw new Error(data.error);
                displayVideoDownloadOptions(data, videoUrl, container);
            })
            .catch(error => {
                console.error('Error fetching video info via API:', error);
                createErrorMessage(error.message || 'Could not load video information. Please try again.', container);
                if (button) {
                    button.disabled = false;
                    button.innerHTML = originalButtonHTML;
                }
            });
    }

    /**
     * Performs a video search using the API.
     * @param {string} query - The search query.
     * @param {HTMLElement} container - The HTML element to display search results/errors.
     * @param {HTMLElement} button - The submit button element.
     * @param {string} originalButtonHTML - The original HTML of the submit button.
     */
    function searchVideos(query, container, button, originalButtonHTML) {
        createLoadingMessage(`Searching for "${query}"...`, container);
        fetch(`api.php?action=searchVideos&query=${encodeURIComponent(query)}`)
            .then(response => {
                if (button) {
                     button.disabled = false;
                     button.innerHTML = originalButtonHTML;
                }
                if (!response.ok) {
                    return response.json().then(errData => { throw new Error(errData.error || `Search error (${response.status}).`); })
                                     .catch(() => { throw new Error(`Search error (${response.status}). Could not retrieve search results.`); });
                }
                return response.json();
            })
            .then(data => {
                container.innerHTML = '';
                if (data.error && data.results === undefined) { // Check if it's an error from searchVideos itself
                    throw new Error(data.error);
                }
                if (data.results && data.results.length > 0) {
                    const searchResultsTitle = document.createElement('h3');
                    searchResultsTitle.className = 'text-2xl font-semibold mb-4 text-gray-800 text-center';
                    searchResultsTitle.textContent = 'Search Results:';
                    container.appendChild(searchResultsTitle);

                    data.results.forEach(video => {
                        const videoElement = document.createElement('div');
                        videoElement.className = 'p-4 mb-4 border rounded-lg flex flex-col sm:flex-row items-start sm:space-x-4 shadow-md hover:shadow-lg transition-shadow';
                        
                        const thumbnailHTML = video.thumbnail_url ? `<img src="${video.thumbnail_url}" alt="Thumbnail for ${video.title}" class="w-full sm:w-40 sm:h-24 object-cover rounded mb-3 sm:mb-0">` : '<div class="w-full sm:w-40 sm:h-24 bg-gray-200 flex items-center justify-center rounded mb-3 sm:mb-0"><span class="material-icons text-gray-400" style="font-size: 48px;">photo</span></div>';
                        
                        const detailsDiv = document.createElement('div');
                        detailsDiv.className = 'flex-grow';
                        detailsDiv.innerHTML = `
                            <h4 class="text-lg font-semibold text-blue-600">${video.title}</h4>
                            <p class="text-sm text-gray-600">By: ${video.uploader || 'N/A'}</p>
                            <p class="text-sm text-gray-500">Duration: ${video.duration_string || 'N/A'}</p>
                        `;

                        const getFormatsBtn = document.createElement('button');
                        getFormatsBtn.className = 'get-formats-btn mt-3 py-2 px-4 bg-green-500 text-white rounded hover:bg-green-600 transition duration-150 text-sm';
                        getFormatsBtn.textContent = 'Get Download Links';
                        getFormatsBtn.dataset.videoUrl = video.url;
                        getFormatsBtn.dataset.videoTitle = video.title;
                        getFormatsBtn.dataset.videoThumbnail = video.thumbnail_url || '';

                        detailsDiv.appendChild(getFormatsBtn);
                        videoElement.innerHTML = thumbnailHTML;
                        videoElement.appendChild(detailsDiv);
                        container.appendChild(videoElement);
                    });

                    addFormatButtonListeners();
                } else {
                    container.innerHTML = '<p class="text-gray-600 text-center py-4">No videos found for your query. Please try different keywords.</p>';
                }
            })
            .catch(error => {
                console.error('Error fetching search results:', error);
                createErrorMessage(error.message || 'Could not perform search. Please try again.', container);
                 if (button) {
                    button.disabled = false;
                    button.innerHTML = originalButtonHTML;
                }
            });
    }

    /**
     * Adds event listeners to "Get Download Links" buttons.
     */
    function addFormatButtonListeners() {
        document.querySelectorAll('.get-formats-btn').forEach(button => {
            button.addEventListener('click', (event) => {
                const videoUrl = event.target.dataset.videoUrl;
                const videoInfoContainer = document.getElementById('video-info-container'); 
                if (videoUrl && videoInfoContainer) {
                    fetchVideoInfo(videoUrl, videoInfoContainer, null, null); 
                } else {
                    console.error("Could not find video URL or container for format fetching from search result.");
                    if(videoInfoContainer) createErrorMessage("Could not retrieve video URL to fetch formats.", videoInfoContainer);
                }
            });
        });
    }
    
    /**
     * Displays video title, thumbnail, duration, and download format options.
     * @param {object} data - Video information object from API.
     * @param {string} videoUrl - Original YouTube video URL.
     * @param {HTMLElement} container - HTML element to display in.
     */
    function displayVideoDownloadOptions(data, videoUrl, container) {
        container.innerHTML = ''; 
        container.classList.remove('hidden');

        const contentWrapper = document.createElement('div');
        contentWrapper.style.maxHeight = '0px';
        contentWrapper.style.overflow = 'hidden';
        contentWrapper.style.transition = 'max-height 0.6s ease-out';

        // --- Video Info Section ---
        const videoInfoDiv = document.createElement('div');
        videoInfoDiv.className = 'mb-4 text-center';

        if (data.title) {
            const titleElement = document.createElement('h3');
            titleElement.className = 'text-xl sm:text-2xl font-semibold text-gray-800 mb-2';
            titleElement.textContent = data.title;
            videoInfoDiv.appendChild(titleElement);
        } else {
            const titleElement = document.createElement('h3');
            titleElement.className = 'text-xl sm:text-2xl font-semibold text-gray-800 mb-2';
            titleElement.textContent = (window.siteTranslations && window.siteTranslations['video_title_unavailable_js']) || 'Video Title Unavailable';
            videoInfoDiv.appendChild(titleElement);
        }

        if (data.thumbnail_url) {
            const thumbnailElement = document.createElement('img');
            thumbnailElement.src = data.thumbnail_url;
            thumbnailElement.alt = (window.siteTranslations && window.siteTranslations['video_thumbnail_alt_js']) || 'Video Thumbnail';
            thumbnailElement.className = 'my-3 rounded-lg shadow-md w-full max-w-sm mx-auto';
            videoInfoDiv.appendChild(thumbnailElement);
        }

        if (data.duration_string) {
            const durationElement = document.createElement('p');
            durationElement.className = 'text-sm text-gray-600 mb-3';
            durationElement.innerHTML = `<span class="font-medium">${(window.siteTranslations && window.siteTranslations['duration_label_js']) || 'Duration'}:</span> ${data.duration_string}`;
            videoInfoDiv.appendChild(durationElement);
        }
        contentWrapper.appendChild(videoInfoDiv);

        // --- Formats Tables ---
        if (data.formats && data.formats.length > 0) {
            const audioFormats = data.formats.filter(f => f.category === 'audio');
            const videoFormats = data.formats.filter(f => f.category === 'video');
            let videoFormatsToShowInitially = 3;

            const createTable = (formats, titleKey, defaultTitle, videoUrlForTable, isVideoTable = false) => {
                if (formats.length === 0) return null;

                const sectionDiv = document.createElement('div');
                sectionDiv.className = 'mb-4';

                const sectionTitle = document.createElement('h4');
                sectionTitle.className = 'text-md font-semibold text-gray-700 mb-2 border-b pb-1';
                sectionTitle.textContent = (window.siteTranslations && window.siteTranslations[titleKey]) || defaultTitle;
                sectionDiv.appendChild(sectionTitle);

                const table = document.createElement('table');
                table.className = 'w-full text-sm border-collapse';

                const tbody = document.createElement('tbody');
                formats.forEach((format, index) => {
                    const tr = document.createElement('tr');
                    tr.className = index % 2 === 0 ? 'bg-gray-50' : '';
                    if (isVideoTable && index >= videoFormatsToShowInitially) {
                        tr.classList.add('hidden', 'extra-video-format');
                    }

                    const tdQuality = document.createElement('td');
                    tdQuality.className = 'p-2 border border-gray-300 text-left font-medium';
                    let qualityHtml = format.label || format.resolution_or_bitrate || format.id;
                    if (format.category === 'video') {
                        const audioIconText = format.has_audio ?
                            ((window.siteTranslations && window.siteTranslations['has_audio_title_js']) || 'Includes audio') :
                            ((window.siteTranslations && window.siteTranslations['video_only_title_js']) || 'Video only');
                        const audioIconClass = format.has_audio ? 'icon' : 'icon muted-icon';
                        const audioIconName = format.has_audio ? 'volume_up' : 'volume_off';
                        qualityHtml = `${format.resolution_or_bitrate || format.label} <span class="material-icons ${audioIconClass}" title="${audioIconText}">${audioIconName}</span>`;
                    }
                    tdQuality.innerHTML = qualityHtml;
                    tr.appendChild(tdQuality);

                    const tdFilesize = document.createElement('td');
                    tdFilesize.className = 'p-2 border border-gray-300 text-center text-gray-600';
                    tdFilesize.textContent = format.filesize_str || 'N/A';
                    tr.appendChild(tdFilesize);

                    const tdButton = document.createElement('td');
                    tdButton.className = 'p-2 border border-gray-300 text-right';
                    const downloadLink = document.createElement('a');
                    downloadLink.href = `download.php?url=${encodeURIComponent(videoUrlForTable)}&format_id=${encodeURIComponent(format.id)}`;
                    downloadLink.className = 'download-button py-1 px-2 bg-blue-500 hover:bg-blue-600 text-white rounded text-xs sm:text-sm';
                    downloadLink.innerHTML = `<span class="material-icons icon text-sm mr-1">file_download</span> ${((window.siteTranslations && window.siteTranslations['download_button_js']) || 'DOWNLOAD')}`;
                    downloadLink.setAttribute('role', 'button');
                    tdButton.appendChild(downloadLink);
                    tr.appendChild(tdButton);

                    tbody.appendChild(tr);
                });
                table.appendChild(tbody);
                sectionDiv.appendChild(table);
                
                if (isVideoTable && formats.length > videoFormatsToShowInitially) {
                    const showMoreButton = document.createElement('button');
                    showMoreButton.className = 'mt-2 py-1 px-3 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded text-xs w-full';
                    showMoreButton.textContent = (window.siteTranslations && window.siteTranslations['show_more_button_js']) || 'SHOW MORE';
                    let allShown = false;

                    showMoreButton.addEventListener('click', () => {
                        allShown = !allShown;
                        for(let i = videoFormatsToShowInitially; i < tbody.rows.length; i++) {
                            tbody.rows[i].classList.toggle('hidden', !allShown);
                        }
                        showMoreButton.textContent = allShown ?
                            ((window.siteTranslations && window.siteTranslations['show_less_button_js']) || 'SHOW LESS') :
                            ((window.siteTranslations && window.siteTranslations['show_more_button_js']) || 'SHOW MORE');
                    });
                    sectionDiv.appendChild(showMoreButton);
                }
                return sectionDiv;
            };

            const audioTableContainer = createTable(audioFormats, 'audio_section_title_js', 'Audio Formats', videoUrl, false);
            if (audioTableContainer) contentWrapper.appendChild(audioTableContainer);

            const videoTableContainer = createTable(videoFormats, 'video_section_title_js', 'Video Formats', videoUrl, true);
            if (videoTableContainer) contentWrapper.appendChild(videoTableContainer);

        } else {
            const noFormatsMessage = document.createElement('p');
            noFormatsMessage.className = 'text-gray-600 text-center py-4';
            noFormatsMessage.textContent = (window.siteTranslations && window.siteTranslations['error_no_formats_found_js']) || 'No downloadable formats were found for this video.';
            contentWrapper.appendChild(noFormatsMessage);
        }

        container.appendChild(contentWrapper);

        // Trigger the animation
        requestAnimationFrame(() => {
            contentWrapper.getBoundingClientRect(); // Force reflow
            contentWrapper.style.maxHeight = contentWrapper.scrollHeight + 'px';
        });

    } // End of displayVideoDownloadOptions
});
