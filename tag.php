<?php

/**
 * Version Tagging Utility with Auto-Increment
 * Usage: php tag.php [--dry-run] [--branch=branch_name]
 */

// Check for command line arguments
$dry_run = in_array('--dry-run', $argv);
$branch = 'main'; // Default branch name
foreach ($argv as $arg) {
    if (strpos($arg, '--branch=') === 0) {
        $branch = substr($arg, 9);
    }
}

if ($dry_run) {
    echo "*** DRY RUN MODE - No changes will be committed ***\n";
}

// Get current version from main plugin file
$plugin_file = __DIR__ . '/preowned-clothing-form.php';
if (!file_exists($plugin_file)) {
    echo "Error: Plugin file not found at $plugin_file\n";
    exit(1);
}

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

// Show summary and confirm
echo "\nSummary of changes:";
echo "\n- Current version: $current_version";
echo "\n- New version: $new_version";
echo "\n- Change type: $type";
echo "\n- Message: $message";
echo "\n- Branch: $branch";
echo "\n- Mode: " . ($dry_run ? "DRY RUN (no changes)" : "LIVE (changes will be committed)");
echo "\n\nProceed? (Y/n): ";
$confirm = trim(fgets(STDIN));
if (strtolower($confirm) === 'n') {
    echo "Operation cancelled by user.\n";
    exit(0);
}

// Update main plugin file
$updated_plugin_data = preg_replace(
    '/(Version:\s*)[\d\.]+/',
    '${1}' . $new_version,
    $plugin_data
);

$updated_plugin_data = preg_replace(
    "/define\('PCF_VERSION',\s*'[\d\.]+'\);/",
    "define('PCF_VERSION', '" . $new_version . "');",
    $updated_plugin_data
);

// Write changes to plugin file if not a dry run
if (!$dry_run) {
    if (file_put_contents($plugin_file, $updated_plugin_data) === false) {
        echo "Error: Failed to write to plugin file!\n";
        exit(1);
    }
    echo "Updated version in plugin file.\n";
} else {
    echo "DRY RUN: Would update plugin file.\n";
}

// Update changelog
$date = date('Y-m-d');
$changelog_file = __DIR__ . '/CHANGELOG.md';

// Check if changelog exists, create if not
if (!file_exists($changelog_file)) {
    echo "CHANGELOG.md not found. Creating it...\n";
    if (!$dry_run) {
        file_put_contents($changelog_file, "# Changelog\n\n");
    } else {
        echo "DRY RUN: Would create CHANGELOG.md\n";
    }
}

if (file_exists($changelog_file) || $dry_run) {
    $changelog_content = file_exists($changelog_file) ? file_get_contents($changelog_file) : "# Changelog\n";
    $new_entry = "\n## [$new_version] - $date\n### $type\n- $message\n";

    // Insert after the first line that starts with #
    $updated_changelog = preg_replace(
        '/(# Changelog\n)/',
        "$1\n$new_entry",
        $changelog_content
    );

    // Write changes to changelog file if not a dry run
    if (!$dry_run) {
        if (file_put_contents($changelog_file, $updated_changelog) === false) {
            echo "Error: Failed to write to changelog file!\n";
            exit(1);
        }
        echo "Updated changelog.\n";
    } else {
        echo "DRY RUN: Would update changelog.\n";
    }
}

// Git commands in correct sequence
$commands = [
    // Stage ALL changes first to ensure clean state
    'git add .',

    // Commit all changes
    'git commit -m ' . escapeshellarg("Version $new_version - $message"),

    // Pull latest changes
    'git pull origin ' . escapeshellarg($branch),

    // Push changes
    'git push origin ' . escapeshellarg($branch),

    // Create and push tag
    'git tag -a v' . $new_version . ' -m ' . escapeshellarg("Version $new_version - $message"),
    'git push origin v' . $new_version
];

// Execute git commands if not a dry run
if (!$dry_run) {
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
} else {
    echo "\nDRY RUN: Git commands that would be executed:\n";
    foreach ($commands as $command) {
        echo "- $command\n";
    }
    echo "\nDRY RUN complete. No changes were made.\n";
}
