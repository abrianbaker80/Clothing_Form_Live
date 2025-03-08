<?php
/**
 * Uninstall handler for Preowned Clothing Form
 *
 * This file runs when the plugin is deleted from the WordPress Plugins admin page.
 * It cleans up all database tables and options created by the plugin.
 */

// If not called by WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Option to keep data (can be set in plugin settings)
$keep_data = get_option('preowned_clothing_preserve_data', false);
if ($keep_data) {
    return;
}

global $wpdb;

// Drop database tables
$tables = array(
    $wpdb->prefix . 'preowned_clothing_submissions',
    $wpdb->prefix . 'preowned_clothing_items'
);

foreach ($tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS $table");
}

// Remove all options with the plugin prefix
$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'preowned_clothing_%'");

// Clear any transients
$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_preowned_clothing_%'");
$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_timeout_preowned_clothing_%'");

// Log that uninstallation was successful
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('Preowned Clothing Form: Plugin uninstalled and data cleared successfully.');
}
