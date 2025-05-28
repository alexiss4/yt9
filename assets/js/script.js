document.addEventListener('DOMContentLoaded', () => {
    const mainElement = document.querySelector('main');
    const isDownloadPage = window.location.pathname.includes('download.php');
    const urlParamsDL = new URLSearchParams(window.location.search);
    const videoUrlDL = urlParamsDL.get('url');

    // --- Helper Functions ---
    function createErrorMessage(message, container) {
        container.innerHTML = `<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative text-center" role="alert">
                                <strong class="font-bold">Error:</strong>
                                <span class="block sm:inline">${message}</span>
                            </div>`;
    }

    function createLoadingMessage(message, container) {
        container.innerHTML = `<div class="flex items-center justify-center text-gray-600 py-4">
                                <svg class="animate-spin h-5 w-5 mr-3 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                ${message}
                            </div>`;
    }

    // --- Logic for download.php (Dynamic Format Loading) ---
    if (isDownloadPage && videoUrlDL && mainElement) {
        const phpFormatList = document.getElementById('php-format-list');
        if (phpFormatList) phpFormatList.style.display = 'none';

        let dlContainer = document.getElementById('download-options-container');
        if (!dlContainer) {
            dlContainer = document.createElement('div');
            dlContainer.id = 'download-options-container';
            dlContainer.className = 'bg-white shadow-lg rounded-lg p-6';
            mainElement.appendChild(dlContainer);
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
                displayVideoDownloadOptions(data, videoUrlDL, dlContainer, false); // isSearchContext = false
            })
            .catch(error => {
                console.error('Error fetching video info for download.php:', error);
                createErrorMessage(error.message || 'Could not load video information. Please try again.', dlContainer);
            });
    }

    // --- Logic for index.php (AJAX Form Submission: URL or Search) ---
    const videoUrlForm = document.getElementById('video-url-form');
    if (videoUrlForm) {
        const videoInfoContainer = document.getElementById('video-info-container');
        const urlInput = videoUrlForm.querySelector('input[name="url"]');
        const submitButton = videoUrlForm.querySelector('button[type="submit"]');

        videoUrlForm.addEventListener('submit', (event) => {
            event.preventDefault();
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

            const youtubeRegex = /^(https?:\/\/)?(www\.)?(youtube\.com\/(watch\?v=|shorts\/|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/;
            const isUrl = youtubeRegex.test(inputValue);

            if (isUrl) {
                fetchVideoInfo(inputValue, videoInfoContainer, submitButton, originalButtonText);
            } else {
                searchVideos(inputValue, videoInfoContainer, submitButton, originalButtonText);
            }
        });
    }

    function fetchVideoInfo(videoUrl, container, button, originalButtonText) {
        createLoadingMessage('Fetching video information...', container);
        fetch(`api.php?action=getVideoInfo&url=${encodeURIComponent(videoUrl)}`)
            .then(response => {
                if (button) { // Button might not exist if called from search result click
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
                container.innerHTML = '';
                if (data.error) throw new Error(data.error);
                displayVideoDownloadOptions(data, videoUrl, container, false); // isSearchContext = false
            })
            .catch(error => {
                console.error('Error fetching video info via API:', error);
                createErrorMessage(error.message || 'Could not load video information. Please try again.', container);
                if (button) {
                    button.disabled = false;
                    button.innerHTML = originalButtonText;
                }
            });
    }

    function searchVideos(query, container, button, originalButtonText) {
        createLoadingMessage(`Searching for "${query}"...`, container);
        fetch(`api.php?action=searchVideos&query=${encodeURIComponent(query)}`)
            .then(response => {
                if (button) {
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
                container.innerHTML = '';
                if (data.error) throw new Error(data.error);

                if (data.results && data.results.length > 0) {
                    const searchResultsTitle = document.createElement('h3');
                    searchResultsTitle.className = 'text-2xl font-semibold mb-4 text-gray-800 text-center';
                    searchResultsTitle.textContent = 'Search Results:';
                    container.appendChild(searchResultsTitle);

                    data.results.forEach(video => {
                        const videoElement = document.createElement('div');
                        videoElement.className = 'p-4 mb-4 border rounded-lg flex flex-col sm:flex-row items-start sm:space-x-4 shadow-md hover:shadow-lg transition-shadow';
                        
                        const thumbnail = `<img src="${video.thumbnail}" alt="Thumbnail for ${video.title}" class="w-full sm:w-40 sm:h-24 object-cover rounded mb-3 sm:mb-0">`;
                        
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
                        getFormatsBtn.dataset.videoUrl = video.url; // video.url is already a full URL
                        getFormatsBtn.dataset.videoTitle = video.title; // Pass title for context
                        getFormatsBtn.dataset.videoThumbnail = video.thumbnail; // Pass thumbnail

                        detailsDiv.appendChild(getFormatsBtn);
                        videoElement.innerHTML = thumbnail; // Add thumbnail first
                        videoElement.appendChild(detailsDiv); // Then add details
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
                    button.innerHTML = originalButtonText;
                }
            });
    }

    function addFormatButtonListeners() {
        document.querySelectorAll('.get-formats-btn').forEach(button => {
            button.addEventListener('click', (event) => {
                const videoUrl = event.target.dataset.videoUrl;
                // For now, just re-use videoInfoContainer from index.php. 
                // This assumes search results are always cleared before showing formats.
                const videoInfoContainer = document.getElementById('video-info-container'); 
                if (videoUrl && videoInfoContainer) {
                    // No button/originalButtonText for these calls as the primary form button isn't involved
                    fetchVideoInfo(videoUrl, videoInfoContainer, null, null); 
                } else {
                    console.error("Could not find video URL or container for format fetching.");
                    if(videoInfoContainer) createErrorMessage("Could not retrieve video URL to fetch formats.", videoInfoContainer);
                }
            });
        });
    }
    
    // Reusable function to display video title, thumbnail, and download format options
    // isSearchContext helps decide if we should show a "Back to search results" or similar if needed (not implemented yet)
    function displayVideoDownloadOptions(data, videoUrl, container, isSearchContext = false) {
        // Clear previous content in the specific container
        container.innerHTML = ''; 

        if (data.title) {
            const titleElement = document.createElement('h3');
            // Use slightly different styling based on context (index vs download page)
            titleElement.className = isDownloadPage ? 'text-2xl font-bold text-gray-800 mb-2' : 'text-xl font-semibold mb-3 text-gray-800 text-center';
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
            formatsTitle.className = isDownloadPage ? 'text-xl font-semibold text-gray-700 mb-4 mt-5' : 'text-lg font-medium text-gray-700 mb-3 mt-5 text-center';
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
                    linkElement.className = 'block text-blue-600 hover:text-blue-800 font-medium';
                    let desc = format.description || `${format.ext} - ${format.resolution || format.format_note}`;
                    linkElement.innerHTML = `<span class="material-icons text-sm mr-2 align-middle">${(format.id || format.format_id) === 'mp3' || (format.resolution === 'Audio' || (format.label && format.label.toLowerCase().includes('audio')) ) ? 'audiotrack' : 'videocam'}</span> ${desc}`;
                    listItem.appendChild(linkElement);
                    listElement.appendChild(listItem);
                } else {
                    linkElement.className = 'py-2 px-4 bg-green-500 text-white rounded hover:bg-green-600 my-1 inline-block text-sm shadow transition duration-150 ease-in-out transform hover:-translate-y-1';
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
            container.innerHTML += '<p class="text-gray-600 text-center py-4">No suitable download formats found for this video. It might be a livestream or have unusual encoding.</p>';
        }
    }
});
