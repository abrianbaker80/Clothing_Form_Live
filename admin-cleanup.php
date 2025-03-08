<?php
/**
 * Admin Database Cleanup Tool
 * 
 * IMPORTANT: DELETE THIS FILE IN PRODUCTION!
 * This file provides a simple way to clean up plugin data during development.
 */

// Direct access check
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(dirname(dirname(dirname(__FILE__)))) . '/');
    require_once(ABSPATH . 'wp-config.php');
    require_once(ABSPATH . 'wp-includes/pluggable.php');
}

// Security check
if (!current_user_can('manage_options')) {
    wp_die('Unauthorized access');
}

// Verify nonce if provided
$nonce_valid = isset($_GET['nonce']) ? wp_verify_nonce($_GET['nonce'], 'pcf_admin_cleanup') : false;
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Header
echo '<!DOCTYPE html>
<html>
<head>
    <title>Preowned Clothing Form - Database Cleanup</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; padding: 10px; margin: 10px 0; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; margin: 10px 0; }
        .warning { background: #fff3cd; border: 1px solid #ffeeba; padding: 10px; margin: 10px 0; }
        button, .button { background: #0073aa; color: white; border: none; padding: 8px 16px; cursor: pointer; }
        .button-danger { background: #dc3545; }
    </style>
</head>
<body>
    <h1>Preowned Clothing Form - Database Cleanup Tool</h1>
    <div class="warning">
        <strong>Warning:</strong> This tool is for development use only. It permanently deletes data.
    </div>';

// Process cleanup
if ($action === 'cleanup' && $nonce_valid) {
    global $wpdb;
    $results = [];
    $errors = [];

    // Drop tables
    $tables = [
        $wpdb->prefix . 'preowned_clothing_submissions',
        $wpdb->prefix . 'preowned_clothing_items'
    ];

    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS {$table}");
        if ($wpdb->last_error) {
            $errors[] = "Error dropping {$table}: {$wpdb->last_error}";
        } else {
            $results[] = "Table {$table} dropped or did not exist.";
        }
    }

    // Delete options
    $count = $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'preowned_clothing_%'");
    $results[] = "Deleted {$count} plugin options.";

    // Delete transients
    $count = $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_preowned_clothing_%'");
    $count += $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_preowned_clothing_%'");
    $results[] = "Deleted {$count} plugin transients.";

    // Display results
    if (!empty($errors)) {
        echo '<div class="error">';
        foreach ($errors as $error) {
            echo '<p>' . esc_html($error) . '</p>';
        }
        echo '</div>';
    }

    echo '<div class="success">';
    foreach ($results as $result) {
        echo '<p>' . esc_html($result) . '</p>';
    }
    echo '</div>';

    echo '<p>Cleanup completed. You should now reinstall the plugin.</p>';
}

// Create nonce for security
$nonce = wp_create_nonce('pcf_admin_cleanup');

// Display cleanup button
echo '
    <h2>Clean Plugin Data</h2>
    <p>This will remove all plugin data including:</p>
    <ul>
        <li>Database tables (<code>' . $wpdb->prefix . 'preowned_clothing_submissions</code>, <code>' . $wpdb->prefix . 'preowned_clothing_items</code>)</li>
        <li>Options in wp_options table</li>
        <li>Transient data</li>
    </ul>
    
    <a href="?action=cleanup&nonce=' . $nonce . '" class="button button-danger" onclick="return confirm(\'Are you sure you want to delete all plugin data? This cannot be undone.\');">Clean Database</a>
    
    <h2>SQL Commands</h2>
    <p>If you prefer to run SQL commands directly, use these:</p>
    <pre>
-- Drop tables
DROP TABLE IF EXISTS ' . $wpdb->prefix . 'preowned_clothing_submissions;
DROP TABLE IF EXISTS ' . $wpdb->prefix . 'preowned_clothing_items;

-- Remove options
DELETE FROM ' . $wpdb->prefix . 'options WHERE option_name LIKE \'preowned_clothing_%\';

-- Clear transients
DELETE FROM ' . $wpdb->prefix . 'options WHERE option_name LIKE \'_transient_preowned_clothing_%\';
DELETE FROM ' . $wpdb->prefix . 'options WHERE option_name LIKE \'_transient_timeout_preowned_clothing_%\';
    </pre>
</body>
</html>';
