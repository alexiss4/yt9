#!/bin/bash

echo "--- Starting Color Scheme Consistency Fixes ---"

# --- 1. Fixes for includes/ui_components/video_form.php ---
echo "Updating includes/ui_components/video_form.php..."
# Input focus ring
sed -i "s/focus:ring-blue-500/focus:ring-sky-500/g" "includes/ui_components/video_form.php"
# Copyright text
sed -i "s/text-gray-500/text-slate-500/g" "includes/ui_components/video_form.php"
echo "Done with includes/ui_components/video_form.php."

# --- 2. Fixes for assets/js/script.js ---
echo "Updating assets/js/script.js..."

# createLoadingMessage text color (ensure it's the more muted slate-500)
# The subtask report said it was fixed to text-slate-500 py-4, but file showed text-slate-600 py-4.
# This ensures it is text-slate-500 py-4.
sed -i "s/text-slate-600 py-4/text-slate-500 py-4/g" "assets/js/script.js"

# Search result "Duration" text
# It's within a template literal: <p class="text-sm text-gray-500">Duration: ${video.duration_string || 'N/A'}</p>
sed -i "s/text-sm text-gray-500\">Duration:/text-sm text-slate-500\">Duration:/g" "assets/js/script.js"

# displayVideoDownloadOptions function:
# Video titles (h3): text-gray-800 to text-slate-800
# Example: titleElement.className = isDownloadPage ? 'text-2xl font-bold text-gray-800 mb-2' : 'text-xl font-semibold mb-3 text-gray-800 text-center';
sed -i "s/font-bold text-gray-800 mb-2/font-bold text-slate-800 mb-2/g" "assets/js/script.js"
sed -i "s/font-semibold mb-3 text-gray-800 text-center/font-semibold mb-3 text-slate-800 text-center/g" "assets/js/script.js"

# Formats title (h4): text-gray-700 to text-slate-700
# Example: formatsTitle.className = isDownloadPage ? 'text-xl font-semibold text-gray-700 mb-4 mt-5' : 'text-lg font-medium text-gray-700 mb-3 mt-5 text-center';
sed -i "s/font-semibold text-gray-700 mb-4 mt-5/font-semibold text-slate-700 mb-4 mt-5/g" "assets/js/script.js"
sed -i "s/font-medium text-gray-700 mb-3 mt-5 text-center/font-medium text-slate-700 mb-3 mt-5 text-center/g" "assets/js/script.js"

# Format list item links (download.php context):
# linkElement.className = 'block text-blue-600 hover:text-blue-800 font-medium';
sed -i "s/block text-blue-600 hover:text-blue-800 font-medium/block text-sky-600 hover:text-sky-700 font-medium/g" "assets/js/script.js"

echo "Done with assets/js/script.js."

echo "--- Color Scheme Consistency Fixes Subtask Finished ---"
