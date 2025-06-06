#!/bin/bash

# --- 1. Modify includes/header.php ---

# Remove the inline onclick attribute
# Using a temporary file for sed operations for safety
sed 's/ onclick="document.getElementById('\''language-dropdown'\'').classList.toggle('\''hidden'\'');"//' "includes/header.php" > "includes/header.php.tmp" && mv "includes/header.php.tmp" "includes/header.php"

# Remove the script block for language switcher
# This is a bit more complex with sed due to multiline. Using awk for better control.
awk '
  BEGIN { printing = 1 }
  /<script>/ {
    # Check if this script block contains langSwitcherButton
    script_content = ""
    inside_script = 1
  }
  inside_script {
    script_content = script_content $0 "\n"
    if (/langSwitcherButton/) {
      is_target_script = 1
    }
  }
  /<\/script>/ {
    if (inside_script && is_target_script) {
      # This was the target script block, skip printing it
      printing = 1
      inside_script = 0
      is_target_script = 0
      next # Skip the closing </script> tag of the target block
    }
    # If not the target script, reset and continue printing
    inside_script = 0
    is_target_script = 0
  }
  printing && !inside_script { print }
  inside_script && !is_target_script { print } # Print lines of non-target scripts
' "includes/header.php" > "includes/header.php.tmp" && mv "includes/header.php.tmp" "includes/header.php"

echo "--- Modified includes/header.php: ---"
# Get rid of the previous file content from the output to avoid confusion
# by printing only the diff.
# diff -u "includes/header.php.original" "includes/header.php" || true # Show diff if original was saved
# For now, just confirm it's modified by showing a snippet or relying on awk's correctness
head -n 50 "includes/header.php" # Show first 50 lines
echo "..."
tail -n 30 "includes/header.php" # Show last 30 lines
echo "-------------------------------------"


# --- 2. Modify assets/js/script.js ---
# Add the language switcher logic into assets/js/script.js
# The new logic will be placed inside the DOMContentLoaded listener

# Create a temporary script file with the new JS logic
cat > /tmp/new_lang_switcher_logic.js << 'EOL'

    // --- Language Switcher Logic ---
    const langSwitcher = document.getElementById('language-switcher');
    if (langSwitcher) {
        const langSwitcherButton = langSwitcher.querySelector('button');
        const langDropdown = document.getElementById('language-dropdown');

        if (langSwitcherButton && langDropdown) {
            langSwitcherButton.addEventListener('click', (event) => {
                event.stopPropagation(); // Prevent click from immediately closing dropdown via window listener
                langDropdown.classList.toggle('hidden');
            });

            window.addEventListener('click', (e) => {
                // Check if the click is outside the button AND outside the dropdown
                if (!langSwitcherButton.contains(e.target) && !langDropdown.contains(e.target)) {
                    langDropdown.classList.add('hidden');
                }
            });
        }
    }
EOL

# Now, insert this logic into assets/js/script.js
# The target is to insert it *inside* the DOMContentLoaded listener, for example,
# right before the line: // --- Logic for download.php (Dynamic Format Loading) ---
# or at the end of the DOMContentLoaded listener. Let's add it near the top for clarity.

# Using awk to insert the content of /tmp/new_lang_switcher_logic.js
# after the line "document.addEventListener('DOMContentLoaded', () => {"
# and before the line "    const mainElement = document.querySelector('main');"

awk '
  1; # Print every line
  /document.addEventListener\("DOMContentLoaded", \(\) => \{/ {
    # After the DOMContentLoaded line, print the content of the new logic file
    # We need to make sure it is printed only once.
    if (!inserted) {
      system("cat /tmp/new_lang_switcher_logic.js");
      inserted = 1;
    }
  }
' "assets/js/script.js" > "assets/js/script.js.tmp" && mv "assets/js/script.js.tmp" "assets/js/script.js"


echo "--- Modified assets/js/script.js (snippet): ---"
# Print a section of the modified script.js to show the new code
# For example, the section around DOMContentLoaded and the new logic
grep -C 15 "Language Switcher Logic" "assets/js/script.js"
echo "---------------------------------------------"

# Clean up temporary file
rm /tmp/new_lang_switcher_logic.js

echo "Subtask finished: Language switcher JS should be refactored."
