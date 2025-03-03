<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Display admin notices from session data
 */
function preowned_clothing_admin_notices() {
    // Replace direct $_SESSION access with the Session Manager
    $feedback = PCF_Session_Manager::get_feedback();
    
    if ($feedback['status'] === 'success') {
        ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html($feedback['message']); ?></p>
        </div>
        <?php
        PCF_Session_Manager::clear_feedback();
    }
    
    if ($feedback['status'] === 'error') {
        ?>
        <div class="notice notice-error is-dismissible">
            <p><?php echo esc_html($feedback['message']); ?></p>
            <?php if (!empty($feedback['debug_info']) && current_user_can('manage_options')): ?>
                <p><strong>Debug info:</strong> <code><?php echo esc_html($feedback['debug_info']); ?></code></p>
            <?php endif; ?>
        </div>
        <?php
        PCF_Session_Manager::clear_feedback();
    }
}
add_action('admin_notices', 'preowned_clothing_admin_notices');

// Rest of admin submissions list handling
// ...existing code...
