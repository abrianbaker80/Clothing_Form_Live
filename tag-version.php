<?php

/**
 * Version Tagging Utility
 * Usage: php tag-version.php [commit message]
 */

// Get current version from main plugin file
$plugin_file = __DIR__ . '/preowned-clothing-form.php';
$plugin_data = file_get_contents($plugin_file);
preg_match('/Version:\s*([0-9\.]+)/', $plugin_data, $matches);
$version = $matches[1] ?? '0.0.0';

// Get commit message from command line or use default
$commit_message = $argv[1] ?? "Version {$version} release";

// Commands to execute in order
$commands = [
    // Check for changes
    'git status --porcelain',

    // Add and commit changes
    'git add .',
    'git commit -m "' . $commit_message . '"',

    // Create and push tag
    'git tag -a ' . $version . ' -m "Version ' . $version . '"',
    'git push origin ' . $version,

    // Sync with remote
    'git pull origin main --rebase',
    'git push origin main'
];

echo "Preparing version " . $version . "\n";

foreach ($commands as $index => $command) {
    echo "\nExecuting: " . $command . "\n";

    // Execute command and capture output
    exec($command, $output, $result_code);

    // Handle special cases
    if ($index === 0 && empty($output)) {
        echo "No changes to commit.\n";
        // Continue to tagging if no changes
        continue;
    }

    // Display command output
    if (!empty($output)) {
        echo implode("\n", $output) . "\n";
    }

    if ($result_code !== 0) {
        echo "Error executing command. Exit code: " . $result_code . "\n";
        exit(1);
    }
}

echo "\nSuccessfully committed, tagged, and synced version " . $version . "\n";
