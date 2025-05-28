document.addEventListener('DOMContentLoaded', () => {
    const mainElement = document.querySelector('main');
    const isDownloadPage = window.location.pathname.includes('download.php');
    const urlParamsDL = new URLSearchParams(window.location.search);
    const videoUrlDL = urlParamsDL.get('url');

    // Helper function for creating error messages
    function createErrorMessage(message) {
        return `<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative text-center" role="alert">
                    <strong class="font-bold">Error:</strong>
                    <span class="block sm:inline">${message}</span>
                </div>`;
    }

    // Helper function for creating loading messages with a spinner
    function createLoadingMessage(message) {
        return `<div class="flex items-center justify-center text-gray-600 py-4">
                    <svg class="animate-spin h-5 w-5 mr-3 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    ${message}
                </div>`;
    }

    if (isDownloadPage && videoUrlDL && mainElement) {
        const phpFormatList = document.getElementById('php-format-list');
        if (phpFormatList) {
            phpFormatList.style.display = 'none';
        }

        let dlContainer = document.getElementById('download-options-container');
        if (!dlContainer) {
            dlContainer = document.createElement('div');
            dlContainer.id = 'download-options-container';
            dlContainer.className = 'bg-white shadow-lg rounded-lg p-6';
            mainElement.appendChild(dlContainer);
        }
        dlContainer.innerHTML = createLoadingMessage('Loading video formats...');

        fetch(`download.php?url=${encodeURIComponent(videoUrlDL)}&json=1`)
            .then(response => {
                if (!response.ok) {
                     return response.json().then(errData => {
                        throw new Error(errData.error || `Server error: ${response.status}. Please try again.`);
                    }).catch(() => {
                        throw new Error(`Server error: ${response.status}. Could not retrieve format details.`);
                    });
                }
                return response.json();
            })
            .then(data => {
                dlContainer.innerHTML = ''; 
                if (data.error) throw new Error(data.error);

                if (data.title) {
                    const titleEl = document.createElement('h1');
                    titleEl.className = 'text-2xl font-bold text-gray-800 mb-2';
                    titleEl.textContent = `Download Video: ${data.title}`;
                    dlContainer.appendChild(titleEl);
                }
                if (data.thumbnail) {
                    const thumbEl = document.createElement('img');
                    thumbEl.src = data.thumbnail;
                    thumbEl.alt = 'Video Thumbnail';
                    thumbEl.className = 'my-4 rounded-lg shadow-md';
                    thumbEl.style.maxWidth = '320px';
                    thumbEl.style.marginLeft = 'auto';
                    thumbEl.style.marginRight = 'auto';
                    dlContainer.appendChild(thumbEl);
                }
                if (data.formats && data.formats.length > 0) {
                    const formatsTitle = document.createElement('h2');
                    formatsTitle.className = 'text-xl font-semibold text-gray-700 mb-4 mt-5';
                    formatsTitle.textContent = 'Available Formats:'; // Simplified title
                    dlContainer.appendChild(formatsTitle);

                    const listEl = document.createElement('ul');
                    listEl.className = 'space-y-3';
                    data.formats.forEach(format => {
                        const listItem = document.createElement('li');
                        listItem.className = 'p-3 bg-gray-50 rounded-md shadow-sm hover:bg-gray-100 transition duration-150';
                        const link = document.createElement('a');
                        link.href = `download.php?url=${encodeURIComponent(videoUrlDL)}&format_id=${encodeURIComponent(format.format_id)}`;
                        link.className = 'block text-blue-600 hover:text-blue-800 font-medium'; // Added font-medium
                        let desc = format.description || `${format.ext} - ${format.resolution || format.format_note}`;
                        link.innerHTML = `<span class="material-icons text-sm mr-2 align-middle">${format.format_id === 'mp3' || format.resolution === 'Audio' ? 'audiotrack' : 'videocam'}</span> ${desc}`;
                        
                        // No need for the extra ID and resolution span, it's in the description from API
                        listItem.appendChild(link);
                        listEl.appendChild(listItem);
                    });
                    dlContainer.appendChild(listEl);
                } else {
                    const noFormatsMsg = document.createElement('p');
                    noFormatsMsg.className = 'text-gray-600';
                    noFormatsMsg.textContent = 'No specific download formats found for this video. It might be a livestream or have unusual encoding.';
                    dlContainer.appendChild(noFormatsMsg);
                }
            })
            .catch(error => {
                console.error('Error fetching video info for download.php:', error);
                dlContainer.innerHTML = createErrorMessage(error.message || 'Could not load video information. Please try again.');
            });
    }

    const videoUrlForm = document.getElementById('video-url-form');
    if (videoUrlForm) {
        const videoInfoContainer = document.getElementById('video-info-container');
        const urlInput = videoUrlForm.querySelector('input[name="url"]');
        const submitButton = videoUrlForm.querySelector('button[type="submit"]');

        videoUrlForm.addEventListener('submit', (event) => {
            event.preventDefault();
            const youtubeUrl = urlInput.value.trim();
            const originalButtonText = submitButton.innerHTML;

            videoInfoContainer.innerHTML = createLoadingMessage('Fetching video information...');
            submitButton.disabled = true;
            submitButton.innerHTML = `<svg class="animate-spin h-5 w-5 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Processing...`;


            if (!youtubeUrl) {
                videoInfoContainer.innerHTML = createErrorMessage('Please enter a YouTube URL.');
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonText;
                return;
            }
            // Regex for YouTube: includes watch, shorts, embed, youtu.be, and optional www.
            const youtubeRegex = /^(https?:\/\/)?(www\.)?(youtube\.com\/(watch\?v=|shorts\/|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/;
            if (!youtubeRegex.test(youtubeUrl)) {
                videoInfoContainer.innerHTML = createErrorMessage('Invalid YouTube URL. Please use a full video link (e.g., youtube.com/watch?v=...).');
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonText;
                return;
            }

            fetch(`api.php?action=getVideoInfo&url=${encodeURIComponent(youtubeUrl)}`)
                .then(response => {
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalButtonText;
                    if (!response.ok) {
                        return response.json().then(errData => {
                             throw new Error(errData.error || `Server error (${response.status}). Please check the URL or try again later.`);
                        }).catch(() => { 
                            throw new Error(`Server error (${response.status}). Could not retrieve video details. The video might be private or unavailable.`);
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    videoInfoContainer.innerHTML = ''; 

                    if (data.error) {
                        throw new Error(data.error);
                    }

                    if (data.title) {
                        const titleElement = document.createElement('h3');
                        titleElement.className = 'text-xl font-semibold mb-3 text-gray-800 text-center';
                        titleElement.textContent = data.title;
                        videoInfoContainer.appendChild(titleElement);
                    }

                    if (data.thumbnail_url) {
                        const thumbnailElement = document.createElement('img');
                        thumbnailElement.src = data.thumbnail_url;
                        thumbnailElement.alt = 'Video Thumbnail';
                        thumbnailElement.className = 'my-4 rounded shadow-lg w-full max-w-sm mx-auto';
                        videoInfoContainer.appendChild(thumbnailElement);
                    }

                    if (data.formats && data.formats.length > 0) {
                        const formatsTitle = document.createElement('h4');
                        formatsTitle.className = 'text-lg font-medium text-gray-700 mb-3 mt-5 text-center';
                        formatsTitle.textContent = 'Available Download Options:';
                        videoInfoContainer.appendChild(formatsTitle);

                        const listElement = document.createElement('div');
                        listElement.className = 'flex flex-wrap justify-center gap-3'; 
                        data.formats.forEach(format => {
                            const link = document.createElement('a');
                            link.href = `download.php?url=${encodeURIComponent(youtubeUrl)}&format_id=${encodeURIComponent(format.id)}`;
                            link.className = 'py-2 px-4 bg-green-500 text-white rounded hover:bg-green-600 my-1 inline-block text-sm shadow transition duration-150 ease-in-out transform hover:-translate-y-1'; // Added hover effect
                            link.textContent = format.label || `${format.ext.toUpperCase()} ${format.url_quality || format.id}`;
                            // Add a small icon based on type
                            const icon = document.createElement('span');
                            icon.className = 'material-icons text-sm mr-1 align-middle';
                            icon.textContent = (format.type === 'mp3' || format.label.toLowerCase().includes('audio')) ? 'audiotrack' : 'videocam';
                            link.prepend(icon);
                            listElement.appendChild(link);
                        });
                        videoInfoContainer.appendChild(listElement);
                    } else {
                        videoInfoContainer.innerHTML += '<p class="text-gray-600 text-center py-4">No suitable download formats found for this video. It might be a livestream or have unusual encoding.</p>';
                    }
                })
                .catch(error => {
                    console.error('Error fetching video info via API:', error);
                    videoInfoContainer.innerHTML = createErrorMessage(error.message || 'Could not load video information. Please try again.');
                    submitButton.disabled = false; // Ensure button is re-enabled on error
                    submitButton.innerHTML = originalButtonText;
                });
        });
    }
});
