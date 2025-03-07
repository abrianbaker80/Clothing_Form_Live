<?php
/**
 * Activation handler for Preowned Clothing Form plugin
 */

// Make sure this function exists during activation
if (!function_exists('preowned_clothing_create_submission_table')) {
    function preowned_clothing_create_submission_table()
    {
        // During activation, just log that we were called
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Activation function called: preowned_clothing_create_submission_table');
        }

        // Don't actually create tables here - we'll do that during normal plugin operation
        // This is just to satisfy WordPress's activation hook mechanism
        return true;
    }
}
