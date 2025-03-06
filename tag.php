<?php

/**
 * Version Tagging Utility with Auto-Increment
 * Usage: php tag.php
 */

// Get current version from main plugin file
$plugin_file = __DIR__ . '/preowned-clothing-form.php';
$plugin_data = file_get_contents($plugin_file);
preg_match('/Version:\s*([0-9\.]+)/', $plugin_data, $matches);
$current_version = $matches[1] ?? '0.0.0.0';

// Ask for release type
echo "\nCurrent version: $current_version\n";
echo "Select release type:\n";
echo "1) Major (Breaking changes)\n";
echo "2) Minor (New features)\n";
echo "3) Patch (Bug fixes)\n";
echo "4) Build (Minor tweaks)\n";
echo "Choose (1-4): ";
$choice = trim(fgets(STDIN));

// Get version parts
$parts = array_pad(explode('.', $current_version), 4, 0);

// Increment version based on choice
switch ($choice) {
    case '1': // Major
        $parts[0]++;
        $parts[1] = 0;
        $parts[2] = 0;
        $parts[3] = 0;
        $type = 'BREAKING';
        break;
    case '2': // Minor
        $parts[1]++;
        $parts[2] = 0;
        $parts[3] = 0;
        $type = 'Added';
        break;
    case '3': // Patch
        $parts[2]++;
        $parts[3] = 0;
        $type = 'Fixed';
        break;
    case '4': // Build
        $parts[3]++;
        $type = 'Changed';
        break;
    default:
        echo "Invalid choice. Exiting.\n";
        exit(1);
}

$new_version = implode('.', $parts);

// Get commit message
echo "\nEnter changelog message: ";
$message = trim(fgets(STDIN));

// Update main plugin file
$plugin_data = preg_replace(
    '/(Version:\s*)[\d\.]+/',
    '${1}' . $new_version,
    $plugin_data
);

$plugin_data = preg_replace(
    "/define\('PCF_VERSION',\s*'[\d\.]+'\);/",
    "define('PCF_VERSION', '" . $new_version . "');",
    $plugin_data
);

// Write changes to plugin file
file_put_contents($plugin_file, $plugin_data);

// Update changelog
$date = date('Y-m-d');
$changelog_file = __DIR__ . '/CHANGELOG.md';
$changelog_content = file_get_contents($changelog_file);
$new_entry = "\n## [$new_version] - $date\n### $type\n- $message\n";

// Insert after the first line that starts with #
$changelog_content = preg_replace(
    '/(# Changelog\n)/',
    "$1\n$new_entry",
    $changelog_content
);

file_put_contents($changelog_file, $changelog_content);

// Git commands in correct sequence
$commands = [
    // Stage ALL changes first to ensure clean state
    'git add .',

    // Commit all changes
    'git commit -m ' . escapeshellarg("Version $new_version - $message"),

    // Pull latest changes
    'git pull origin main',

    // Push changes
    'git push origin main',

    // Create and push tag
    'git tag -a v' . $new_version . ' -m ' . escapeshellarg("Version $new_version - $message"),
    'git push origin v' . $new_version
];

// Execute commands with better error handling
foreach ($commands as $command) {
    echo "\nExecuting: $command\n";

    // Execute command and capture output
    exec($command, $output, $result_code);

    // Display command output
    if (!empty($output)) {
        echo implode("\n", $output) . "\n";
    }

    // Check for errors
    if ($result_code !== 0) {
        echo "Error executing command. Exit code: $result_code\n";
        echo "\nTo recover:\n";
        echo "1. Use 'git status' to check current state\n";
        echo "2. Use 'git reset --hard HEAD^' to undo the last commit if needed\n";
        echo "3. Use 'git tag -d v$new_version' to delete the tag if created\n";
        exit(1);
    }
}

echo "\nSuccessfully updated to version $new_version\n";
