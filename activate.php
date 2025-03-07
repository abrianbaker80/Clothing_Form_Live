<?php
/**
 * Activation handler for Preowned Clothing Form plugin
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define constants if not already defined
if (!defined('PCF_VERSION')) {
    define('PCF_VERSION', '3.0.6.2');
}

// Only define the function if it doesn't already exist
if (!function_exists('preowned_clothing_create_submission_table')) {
    function preowned_clothing_create_submission_table()
    {
        // During activation, log that we were called
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Activation function called from activate.php: preowned_clothing_create_submission_table');
        }

        // Call the database function if it exists
        if (function_exists('_preowned_clothing_create_tables')) {
            _preowned_clothing_create_tables(false);
        }

        // Return true to avoid activation errors
        return true;
    }
}

// Optional - Run initial setup tasks during activation
function preowned_clothing_run_activation_tasks()
{
    // Create option to track version
    update_option('preowned_clothing_version', PCF_VERSION);

    // Log successful activation
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('Preowned Clothing Form: Plugin activated successfully - Version ' . PCF_VERSION);
    }
}

// Run activation tasks on plugins_loaded with high priority
add_action('plugins_loaded', 'preowned_clothing_run_activation_tasks', 0);
