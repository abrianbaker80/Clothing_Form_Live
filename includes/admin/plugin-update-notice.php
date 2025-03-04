<?php
/**
 * Plugin Update Notice
 */

// Security check
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Show update notices after plugin updates
 */
function preowned_clothing_form_update_notice() {
    // Current plugin version
    $current_version = '2.7.0.0';
    
    // Get the previously installed version
    $previous_version = get_option('preowned_clothing_form_version', '0.0.0');
    
    // Check if this is a new installation or update
    if (version_compare($previous_version, $current_version, '<')) {
        // Only show notices for significant updates (not minor versions)
        $is_major_update = version_compare($previous_version, '1.0.0', '>=') && substr($previous_version, 0, 1) !== substr($current_version, 0, 1);
        $is_feature_update = version_compare($previous_version, '1.0.0', '>=') && substr($previous_version, 0, 3) !== substr($current_version, 0, 3);
        
        if ($is_major_update || $is_feature_update || $previous_version === '0.0.0') {
            // Set a transient to display the notice
            set_transient('preowned_clothing_form_updated', true, 60 * 60 * 24 * 3); // Display for 3 days
        }
        
        // Update the stored version number
        update_option('preowned_clothing_form_version', $current_version);
    }
}
add_action('admin_init', 'preowned_clothing_form_update_notice');

/**
 * Display the update notice
 */
function preowned_clothing_form_display_update_notice() {
    // Check if we should show the notice
    if (get_transient('preowned_clothing_form_updated')) {
        ?>
        <div class="notice notice-success is-dismissible">
            <p>
                <strong><?php echo esc_html__('Thank you for updating Preowned Clothing Form!', 'preowned-clothing-form'); ?></strong>
                <?php echo sprintf(
                    esc_html__('This update includes new features and improvements. %sView the changelog%s to see what\'s new.', 'preowned-clothing-form'),
                    '<a href="' . esc_url(admin_url('admin.php?page=preowned-clothing-settings&tab=changelog')) . '">', '</a>'
                ); ?>
            </p>
        </div>
        <?php
        
        // Delete the transient when the notice is displayed
        delete_transient('preowned_clothing_form_updated');
    }
}
add_action('admin_notices', 'preowned_clothing_form_display_update_notice');
