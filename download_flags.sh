#!/bin/bash
# This subtask will download the required country flag icons and save them
# to the assets/images/country_logos/ directory.

# Create the directory if it doesn't exist (it should, but just in case)
mkdir -p assets/images/country_logos/

# Base URL for flag icons. Using a reliable source.
# Using flagcdn.com which provides PNGs in various sizes. w20 seems appropriate.
# The country codes are mostly ISO 3166-1 alpha-2 codes.

# Define an associative array for filename to country code mapping for flagcdn
declare -A flag_map
flag_map=(
    ["gb.png"]="gb" # UK for English
    ["es.png"]="es" # Spain
    ["fr.png"]="fr" # France
    ["de.png"]="de" # Germany
    ["it.png"]="it" # Italy
    ["pt.png"]="pt" # Portugal
    ["ru.png"]="ru" # Russia
    ["jp.png"]="jp" # Japan
    ["kr.png"]="kr" # South Korea
    ["cn.png"]="cn" # China
    ["sa.png"]="sa" # Saudi Arabia for Arabic
    ["in.png"]="in" # India
    ["nl.png"]="nl" # Netherlands
    ["pl.png"]="pl" # Poland
    ["tr.png"]="tr" # Turkey
    ["se.png"]="se" # Sweden
    ["dk.png"]="dk" # Denmark
    ["fi.png"]="fi" # Finland
    ["no.png"]="no" # Norway
    ["gr.png"]="gr" # Greece
    ["ke.png"]="ke" # Kenya for Swahili
    ["bd.png"]="bd" # Bangladesh for Bengali
)

FLAG_URL_BASE="https://flagcdn.com/w20" # w20 gives 20px width PNGs

# Loop through the map and download each flag
for filename in "${!flag_map[@]}"; do
    country_code="${flag_map[$filename]}"
    download_url="${FLAG_URL_BASE}/${country_code}.png"
    output_path="assets/images/country_logos/${filename}"

    echo "Downloading ${filename} (code: ${country_code}) from ${download_url} to ${output_path}"
    curl -s -L -o "${output_path}" "${download_url}"

    # Check if download was successful (curl doesn't always return non-zero for 404s with -s -L -o)
    if [ ! -s "${output_path}" ]; then
        # File is empty or does not exist, meaning download likely failed (e.g. 404)
        # Try a slightly different size (w40) just in case w20 was the issue for a specific flag
        echo "Warning: Download of ${filename} (w20) might have failed or resulted in an empty file. Trying w40..."
        download_url_w40="https://flagcdn.com/w40/${country_code}.png" # w40 for fallback
        curl -s -L -o "${output_path}" "${download_url_w40}"
        if [ ! -s "${output_path}" ]; then
            echo "Error: Download of ${filename} (w40 fallback) also failed or resulted in an empty file. The logo will be missing."
            # Create an empty file so it's clear it was attempted but failed, rather than being missed.
            # Or, alternatively, remove it: rm -f "${output_path}"
            # For now, let's leave it empty.
        else
             echo "Success: ${filename} downloaded using w40 fallback."
        fi
    else
        echo "Success: ${filename} downloaded."
    fi
done

echo "Flag download process complete. Listing downloaded files:"
ls -l assets/images/country_logos/
