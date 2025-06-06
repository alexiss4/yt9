#!/bin/bash

echo "--- Starting Color Scheme Update ---"

# --- 1. Update includes/header.php ---
echo "Updating includes/header.php..."
# Body class is in header.php, change bg-blue-50 to bg-slate-50
sed -i "s/bg-blue-50/bg-slate-50/g" "includes/header.php"
# Header nav title text-blue-600 to text-sky-600
sed -i "s/text-blue-600/text-sky-600/g" "includes/header.php"
# Header nav links text-gray-600 to text-slate-600
sed -i "s/text-gray-600/text-slate-600/g" "includes/header.php"
# Header nav links hover:text-blue-600 to hover:text-sky-600
sed -i "s/hover:text-blue-600/hover:text-sky-600/g" "includes/header.php"
# Language dropdown hover:bg-blue-500 to hover:bg-sky-600
sed -i "s/hover:bg-blue-500/hover:bg-sky-600/g" "includes/header.php"
# Language dropdown text-gray-700 to text-slate-700 (for items)
sed -i "s/text-gray-700/text-slate-700/g" "includes/header.php" # Be careful, this is broad for header

echo "Done with includes/header.php."

# --- 2. Update index.php ---
echo "Updating index.php..."
# Hero gradient from-blue-100 to from-sky-100
sed -i "s/from-blue-100/from-sky-100/g" "index.php"
# Hero gradient to-blue-50 to to-slate-50
sed -i "s/to-blue-50/to-slate-50/g" "index.php" # This was body bg, ensure it's correct for gradient context
# Text colors:
sed -i "s/text-gray-800/text-slate-800/g" "index.php"
sed -i "s/text-gray-700/text-slate-700/g" "index.php"
sed -i "s/text-gray-600/text-slate-600/g" "index.php"
# Placeholder for button if any hardcoded, most are from video_form.php or script.js
echo "Done with index.php."

# --- 3. Update includes/ui_components/video_form.php ---
# This likely contains the main call-to-action button
echo "Updating includes/ui_components/video_form.php..."
# Assuming button uses Tailwind classes like 'bg-green-500'
# This will change the "Download" / "Convert" / "Search" button
sed -i "s/bg-blue-500/bg-sky-500/g" "includes/ui_components/video_form.php" # Main button color
sed -i "s/hover:bg-blue-600/hover:bg-sky-600/g" "includes/ui_components/video_form.php" # Main button hover

# The form button in video_form.php uses a variable for icon, so color is likely text based or specific classes
# The original plan was to change green buttons. The video_form.php uses blue for its button.
# Let's assume the original primary button was blue and change it to the new primary sky blue.
# If it was green, this won't affect it. We'll handle green buttons in script.js
# The actual button in video_form.php seems to be:
# <button type="submit" class="bg-blue-500 hover:bg-blue-600 ...">
# So the above sed should work for this.

echo "Done with includes/ui_components/video_form.php."

# --- 4. Update assets/js/script.js for dynamic elements ---
echo "Updating assets/js/script.js..."
# Error messages: bg-red-100, border-red-400, text-red-700 - these are standard, probably keep for errors.
# Loading messages: text-gray-600 to text-slate-500 (more muted)
sed -i "s/text-gray-600/text-slate-600/g" "assets/js/script.js" # General text, be careful
# Specifically target loading message:
sed -i "s/text-gray-600 py-4/text-slate-500 py-4/g" "assets/js/script.js" # More specific for loading
# Loading spinner: text-blue-500 to text-sky-500
sed -i "s/text-blue-500/text-sky-500/g" "assets/js/script.js" # Spinner color

# Download links/buttons created dynamically:
# Current: bg-green-500 text-white rounded hover:bg-green-600
# New:     bg-emerald-500 text-white rounded hover:bg-emerald-600
sed -i "s/bg-green-500/bg-emerald-500/g" "assets/js/script.js"
sed -i "s/hover:bg-green-600/hover:bg-emerald-600/g" "assets/js/script.js"

# Search results:
# Title: text-blue-600 to text-sky-600
# This was already changed by a broader rule above, but ensure it's covered.
# No, the broader rule was for text-gray-600.
# sed -i "s/text-blue-600/text-sky-600/g" "assets/js/script.js" # For search result titles
# This one is tricky as text-blue-600 is also used in header.
# Only apply to script.js if specific enough.
# The title in search results is: `<h4 class="text-lg font-semibold text-blue-600">`
sed -i "s/text-lg font-semibold text-blue-600/text-lg font-semibold text-sky-600/g" "assets/js/script.js"


echo "Done with assets/js/script.js."

# --- 5. Update other PHP page files for text consistency ---
# (youtube-to-mp3.php, youtube-to-mp4.php, youtube-thumbnail-downloader.php, download.php)
PAGES_TO_UPDATE=(
    "youtube-to-mp3.php"
    "youtube-to-mp4.php"
    "youtube-thumbnail-downloader.php"
    "download.php"
    "includes/ui_components/features_section.php"
)

for page in "${PAGES_TO_UPDATE[@]}"; do
    echo "Updating text colors in ${page}..."
    if [ -f "$page" ]; then
        sed -i "s/text-gray-800/text-slate-800/g" "$page"
        sed -i "s/text-gray-700/text-slate-700/g" "$page"
        sed -i "s/text-gray-600/text-slate-600/g" "$page"
        # Specific for download.php and format lists if they use blue text for links
        sed -i "s/text-blue-600/text-sky-600/g" "$page"
        sed -i "s/hover:text-blue-800/hover:text-sky-700/g" "$page" # For links that have a darker hover
    else
        echo "Warning: File ${page} not found."
    fi
done
echo "Done updating other PHP pages."


# --- 6. Specific fix for text-slate-600 in header.php's language switcher button ---
# The language switcher button text (current language name) was text-gray-600, became text-slate-600.
# Its hover is hover:text-sky-600. This is fine.
# The dropdown items were text-gray-700, became text-slate-700.
# Their hover is hover:bg-sky-600 hover:text-white. This is also fine.

# --- 7. Check button in youtube-to-mp3.php and youtube-to-mp4.php ---
# These pages use video_form.php, which was updated.
# The $button_icon color might need adjustment if it's a text color.
# The video_form.php uses: <span class="material-icons mr-2">${button_icon}</span>
# This will inherit text color. The button text is white, so material icons should also be white.
# If any icons are not white, this might be an issue. The current setup seems okay.

echo "--- Color Scheme Update Subtask Finished ---"
echo "Please review the changes carefully. Testing visually is highly recommended."
