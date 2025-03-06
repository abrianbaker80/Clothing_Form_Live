<?php
/**
 * Admin Settings Fallback
 *
 * A simplified version of the admin settings page to avoid conflicts
 *
 * @package PreownedClothingForm
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Only create the function if it doesn't exist
if (!function_exists('preowned_clothing_admin_settings_page')) {
    /**
     * Register admin settings page
     */
    function preowned_clothing_admin_settings_page() {
        add_options_page(
            'Clothing Form Settings',
            'Clothing Form',
            'manage_options',
            'preowned-clothing-settings',
            'preowned_clothing_display_settings'
        );
        
        // Add submenu pages
        if (file_exists(plugin_dir_path(dirname(__FILE__)) . 'includes/admin/category-manager.php')) {
            require_once plugin_dir_path(dirname(__FILE__)) . 'includes/admin/category-manager.php';
            
            add_submenu_page(
                'preowned-clothing-settings',
                'Category Manager',
                'Category Manager',
                'manage_options',
                'preowned-clothing-categories',
                'preowned_clothing_category_manager_page'
            );
        }
        
        if (file_exists(plugin_dir_path(dirname(__FILE__)) . 'includes/admin/size-manager.php')) {
            require_once plugin_dir_path(dirname(__FILE__)) . 'includes/admin/size-manager.php';
            
            add_submenu_page(
                'preowned-clothing-settings',
                'Size Manager',
                'Size Manager',
                'manage_options',
                'preowned-clothing-sizes',
                'preowned_clothing_size_manager_page'
            );
        }
        
        if (file_exists(plugin_dir_path(dirname(__FILE__)) . 'includes/admin/form-field-manager.php')) {
            require_once plugin_dir_path(dirname(__FILE__)) . 'includes/admin/form-field-manager.php';
            
            add_submenu_page(
                'preowned-clothing-settings',
                'Form Field Manager',
                'Form Fields',
                'manage_options',
                'preowned-clothing-form-fields',
                'preowned_clothing_form_field_manager_page'
            );
        }
    }

    /**
     * Register admin settings
     */
    function preowned_clothing_register_settings() {
        // Basic settings
        register_setting('preowned_clothing_general', 'preowned_clothing_max_items');
        register_setting('preowned_clothing_general', 'preowned_clothing_max_image_size');
        register_setting('preowned_clothing_appearance', 'preowned_clothing_form_title');
        register_setting('preowned_clothing_appearance', 'preowned_clothing_form_intro');
        register_setting('preowned_clothing_appearance', 'preowned_clothing_primary_color');
        register_setting('preowned_clothing_appearance', 'preowned_clothing_secondary_color');
        register_setting('preowned_clothing_advanced', 'preowned_clothing_github_token');
    }

    /**
     * Display settings page
     */
    function preowned_clothing_display_settings() {
        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_die('You do not have sufficient permissions to access this page.');
        }
        
        // Get active tab
        $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'general';
        
        // Process form submission
        if (isset($_POST['submit']) && isset($_POST['_wpnonce'])) {
            // Check nonce
            check_admin_referer('preowned_clothing_settings');
            
            // Save General tab settings
            if ($active_tab == 'general') {
                if (isset($_POST['max_items'])) {
                    update_option('preowned_clothing_max_items', intval($_POST['max_items']));
                }
                
                if (isset($_POST['max_image_size'])) {
                    update_option('preowned_clothing_max_image_size', intval($_POST['max_image_size']));
                }
            }
            
            // Save Appearance tab settings
            if ($active_tab == 'appearance') {
                if (isset($_POST['form_title'])) {
                    update_option('preowned_clothing_form_title', sanitize_text_field($_POST['form_title']));
                }
                
                if (isset($_POST['form_intro'])) {
                    update_option('preowned_clothing_form_intro', wp_kses_post($_POST['form_intro']));
                }
                
                if (isset($_POST['primary_color'])) {
                    update_option('preowned_clothing_primary_color', sanitize_hex_color($_POST['primary_color']));
                }
                
                if (isset($_POST['secondary_color'])) {
                    update_option('preowned_clothing_secondary_color', sanitize_hex_color($_POST['secondary_color']));
                }
            }
            
            // Save Advanced tab settings
            if ($active_tab == 'advanced') {
                if (isset($_POST['github_token'])) {
                    update_option('preowned_clothing_github_token', sanitize_text_field($_POST['github_token']));
                    
                    // Also regenerate the GitHub updater to use the new token
                    do_action('preowned_clothing_refresh_updater');
                }
            }
            
            echo '<div class="notice notice-success is-dismissible"><p>Settings saved successfully!</p></div>';
        }
        
        ?>
        <div class="wrap">
            <h1>Clothing Form Settings</h1>
            
            <h2 class="nav-tab-wrapper">
                <a href="?page=preowned-clothing-settings&tab=general" class="nav-tab <?php echo $active_tab == 'general' ? 'nav-tab-active' : ''; ?>">
                    General Settings
                </a>
                <a href="?page=preowned-clothing-settings&tab=appearance" class="nav-tab <?php echo $active_tab == 'appearance' ? 'nav-tab-active' : ''; ?>">
                    Appearance
                </a>
                <a href="?page=preowned-clothing-settings&tab=advanced" class="nav-tab <?php echo $active_tab == 'advanced' ? 'nav-tab-active' : ''; ?>">
                    Advanced
                </a>
            </h2>
            
            <form method="post">
                <?php 
                wp_nonce_field('preowned_clothing_settings'); 
                
                // Display tab content
                if ($active_tab == 'general') {
                    ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row">Maximum Items</th>
                            <td>
                                <input type="number" name="max_items" min="1" max="50" value="<?php echo esc_attr(get_option('preowned_clothing_max_items', 10)); ?>">
                                <p class="description">Maximum number of clothing items allowed per submission.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Maximum Image Size (MB)</th>
                            <td>
                                <input type="number" name="max_image_size" min="1" max="10" value="<?php echo esc_attr(get_option('preowned_clothing_max_image_size', 2)); ?>">
                                <p class="description">Maximum file size allowed for uploaded images in megabytes.</p>
                            </td>
                        </tr>
                    </table>
                    <?php
                }
                elseif ($active_tab == 'appearance') {
                    ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row">Form Title</th>
                            <td>
                                <input type="text" name="form_title" class="regular-text" value="<?php echo esc_attr(get_option('preowned_clothing_form_title', 'Submit Your Pre-owned Clothing')); ?>">
                                <p class="description">Main heading displayed at the top of the form.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Form Introduction</th>
                            <td>
                                <textarea name="form_intro" class="large-text" rows="3"><?php echo esc_textarea(get_option('preowned_clothing_form_intro', 'You can submit multiple clothing items in a single form.')); ?></textarea>
                                <p class="description">Text shown above the form explaining its purpose.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Primary Color</th>
                            <td>
                                <input type="color" name="primary_color" value="<?php echo esc_attr(get_option('preowned_clothing_primary_color', '#0073aa')); ?>">
                                <p class="description">Main color used for buttons and highlights.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Secondary Color</th>
                            <td>
                                <input type="color" name="secondary_color" value="<?php echo esc_attr(get_option('preowned_clothing_secondary_color', '#005177')); ?>">
                                <p class="description">Secondary color used for accents and hover states.</p>
                            </td>
                        </tr>
                    </table>
                    <?php
                }
                elseif ($active_tab == 'advanced') {
                    ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row">GitHub Access Token</th>
                            <td>
                                <input type="password" name="github_token" class="regular-text" value="<?php echo esc_attr(get_option('preowned_clothing_github_token', '')); ?>">
                                <p class="description">Enter your GitHub personal access token to enable automatic updates.</p>
                            </td>
                        </tr>
                    </table>
                    <?php
                }
                ?>
                
                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes">
                </p>
            </form>
        </div>
        <?php
    }

    // Hook into admin menu and settings registration
    add_action('admin_menu', 'preowned_clothing_admin_settings_page');
    add_action('admin_init', 'preowned_clothing_register_settings');
}

/**
 * Add an action to refresh the GitHub updater
 */
function preowned_clothing_refresh_updater_callback() {
    // Reset any updater flags
    global $preowned_clothing_gh_updater_running;
    $preowned_clothing_gh_updater_running = false;
    
    if (defined('PREOWNED_CLOTHING_UPDATER_INITIALIZED')) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Attempting to reinitialize GitHub updater after settings change');
        }
    }
    
    // Reinitialize the updater
    do_action('plugins_loaded');
}
add_action('preowned_clothing_refresh_updater', 'preowned_clothing_refresh_updater_callback');
