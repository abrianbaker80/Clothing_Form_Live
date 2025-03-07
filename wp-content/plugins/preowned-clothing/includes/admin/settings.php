<?php
// Make sure this file isn't accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register the settings page
 */
function preowned_clothing_register_settings_page() {
    add_options_page(
        'Pre-owned Clothing Settings',
        'Pre-owned Clothing',
        'manage_options',  // Ensure this is 'manage_options' for admin access
        'preowned-clothing-settings',
        'preowned_clothing_settings_page'
    );
}
add_action('admin_menu', 'preowned_clothing_register_settings_page');

/**
 * Display the settings page
 */
function preowned_clothing_settings_page() {
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }
    
    // Settings page content
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form action="options.php" method="post">
            <?php
            // Output security fields
            settings_fields('preowned_clothing_settings');
            
            // Output setting sections and their fields
            do_settings_sections('preowned-clothing-settings');
            
            // Output save settings button
            submit_button('Save Settings');
            ?>
        </form>
    </div>
    <?php
}

/**
 * Register plugin settings
 */
function preowned_clothing_register_settings() {
    register_setting(
        'preowned_clothing_settings',
        'preowned_clothing_options'
    );
    
    // Add your settings sections and fields here
    add_settings_section(
        'preowned_clothing_general',
        'General Settings',
        'preowned_clothing_general_section_callback',
        'preowned-clothing-settings'
    );
    
    // Add fields as needed
}
add_action('admin_init', 'preowned_clothing_register_settings');

/**
 * Section callback
 */
function preowned_clothing_general_section_callback() {
    echo '<p>Configure the general settings for the Pre-owned Clothing plugin.</p>';
}
