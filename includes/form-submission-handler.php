<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Include modular components
require_once(plugin_dir_path(__FILE__) . 'form/validation.php');
require_once(plugin_dir_path(__FILE__) . 'form/image-uploader.php');
require_once(plugin_dir_path(__FILE__) . 'form/database.php');
require_once(plugin_dir_path(__FILE__) . 'form/session-manager.php');

/**
 * Main form submission handler
 * Acts as the coordinator between validation, file uploads, and database operations
 */
function preowned_clothing_handle_form_submission() {
    // Only process if this is a form submission
    if (!isset($_POST['submit_clothing'])) {
        return;
    }
    
    // 1. Validate the submission - stop early if invalid
    $validation = new PCF_Validation();
    $validation_result = $validation->validate_submission();
    
    if (!$validation_result['is_valid']) {
        PCF_Session_Manager::set_feedback(
            'error',
            $validation_result['message'],
            $validation_result['debug_info']
        );
        return;
    }
    
    // 2. Process the submission
    $processor = new PCF_Submission_Processor();
    $result = $processor->process($validation_result['data']);
    
    // 3. Set appropriate feedback
    PCF_Session_Manager::set_feedback(
        $result['status'],
        $result['message'],
        isset($result['debug_info']) ? $result['debug_info'] : ''
    );
    
    // 4. Redirect if needed
    if (!empty($result['redirect'])) {
        wp_safe_redirect($result['redirect']);
        exit;
    }
}

// Hook handler to WordPress initialization
add_action('init', 'preowned_clothing_handle_form_submission');

/**
 * Initialize session if needed
 */
add_action('init', array('PCF_Session_Manager', 'initialize'), 1);

/**
 * Add CSRF nonce field to the form
 */
function preowned_clothing_add_nonce_field() {
    wp_nonce_field('clothing_form_submission', 'clothing_form_nonce');
}
add_action('preowned_clothing_form_start', 'preowned_clothing_add_nonce_field');

// Remove the duplicate implementation - we're already handling form submission above