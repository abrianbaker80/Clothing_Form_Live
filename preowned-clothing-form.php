<?php
/**
 * Plugin Name: Preowned Clothing Form
 * Plugin URI:  https://github.com/abrianbaker80/Clothing_Form
 * Description: A plugin to create a form for submitting pre-owned clothing items.
 * Version:     2.5.9.9
 * Author:      Allen Baker
 * Author URI:  Your Website/Author URL
 * License:     GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * GitHub Plugin URI: abrianbaker80/Clothing_Form
 * 
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Check if plugin_dir_url is defined, if not, make sure we have WordPress core functions
if (!function_exists('plugin_dir_url')) {
    // This ensures WordPress core functions are available
    require_once(ABSPATH . 'wp-includes/plugin.php');
}
// Define plugin constants
define('PCF_VERSION', '2.5.9'); // Updated version
define('PCF_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PCF_PLUGIN_URL', plugin_dir_url(__FILE__));

// Make sure the debug tool is loaded
require_once PCF_PLUGIN_DIR . 'debug-form.php';

// Load core classes in correct order
require_once PCF_PLUGIN_DIR . 'includes/utilities.php';  // Add utilities first
require_once PCF_PLUGIN_DIR . 'includes/form/session-manager.php';
require_once PCF_PLUGIN_DIR . 'includes/form/form-renderer.php'; // Make sure this is loaded early
require_once PCF_PLUGIN_DIR . 'includes/form/validation.php';
require_once PCF_PLUGIN_DIR . 'includes/form/image-uploader.php';
require_once PCF_PLUGIN_DIR . 'includes/form/database.php';

// Make sure we load all required WordPress plugin functions
if (!function_exists('get_plugin_data') && function_exists('is_admin') && is_admin()) {
    include_once ABSPATH . 'wp-admin/includes/plugin.php';
}

// Safely include GitHub updater - wrapped in a try/catch to prevent fatal errors
try {
    if (file_exists(plugin_dir_path(__FILE__) . 'includes/github-updater.php')) {
        require_once plugin_dir_path(__FILE__) . 'includes/github-updater.php';
        
        // Include the admin interface for GitHub updater
        if (is_admin() && file_exists(plugin_dir_path(__FILE__) . 'includes/github-updater-admin.php')) {
            require_once plugin_dir_path(__FILE__) . 'includes/github-updater-admin.php';
        }
        
        // Only initialize if we're in admin and have the right functions
        if (is_admin() && function_exists('preowned_clothing_can_run_updater') && preowned_clothing_can_run_updater()) {
            // Setup the updater safely
            add_action('admin_init', function() {
                $updater = new Preowned_Clothing_GitHub_Updater(__FILE__);
                
                // Use options from settings
                $username = get_option('preowned_clothing_github_username', 'abrianbaker80');
                $repository = get_option('preowned_clothing_github_repository', 'Clothing_Form');
                $token = get_option('preowned_clothing_github_token', '');
                
                $updater->set_username($username);
                $updater->set_repository($repository);
                
                if (!empty($token)) {
                    $updater->authorize($token);
                }
                
                $updater->initialize();
            }, 15);
        }
    }
} catch (Exception $e) {
    // Log error but don't crash
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('Preowned Clothing Form - GitHub updater error: ' . $e->getMessage());
    }
}

// Include admin settings - also in a try/catch for safety
try {
    if (file_exists(plugin_dir_path(__FILE__) . 'includes/class-preowned-clothing-admin-settings.php')) {
        require_once plugin_dir_path(__FILE__) . 'includes/class-preowned-clothing-admin-settings.php';
        // Class is auto-initialized through the get_instance call
    }
} catch (Exception $e) {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('Preowned Clothing Form - Admin settings error: ' . $e->getMessage());
    }
}

/**
 * Initialize plugin sessions
 */
function preowned_clothing_init() {
    // Use session manager to initialize
    PCF_Session_Manager::initialize();
}
add_action('init', 'preowned_clothing_init', 1);

/**
 * Enqueue scripts and styles
 */
function preowned_clothing_enqueue_scripts() {
    // Enqueue Font Awesome for modern icons - with fallback
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css', array(), '5.15.4');
    
    // Check if modern-theme.css exists before enqueuing
    $modern_theme_path = plugin_dir_path(__FILE__) . 'assets/css/modern-theme.css';
    if (file_exists($modern_theme_path)) {
        wp_enqueue_style('preowned-clothing-modern-theme', plugin_dir_url(__FILE__) . 'assets/css/modern-theme.css', array(), '1.0.0');
    }
    
    // Enqueue main stylesheet
    wp_enqueue_style('preowned-clothing-style', plugin_dir_url(__FILE__) . 'assets/css/style.css', array(), '1.1.0');
    
    // Enqueue card-based layout enhancements
    wp_enqueue_style('preowned-clothing-card-layout', plugin_dir_url(__FILE__) . 'assets/css/card-layout.css', array('preowned-clothing-style'), '1.0.0');

    // Enqueue form controls stylesheet
    $form_controls_path = plugin_dir_path(__FILE__) . 'assets/css/form-controls.css';
    if (file_exists($form_controls_path)) {
        wp_enqueue_style('preowned-clothing-form-controls', plugin_dir_url(__FILE__) . 'assets/css/form-controls.css', array('preowned-clothing-style'), '1.0.0');
    }

    // Enqueue wizard interface styles
    $wizard_interface_path = plugin_dir_path(__FILE__) . 'assets/css/wizard-interface.css';
    if (file_exists($wizard_interface_path)) {
        wp_enqueue_style('preowned-clothing-wizard', plugin_dir_url(__FILE__) . 'assets/css/wizard-interface.css', array('preowned-clothing-style'), '1.0.0');
    }
    
    // Enqueue wizard review styles
    $wizard_review_path = plugin_dir_path(__FILE__) . 'assets/css/wizard-review.css';
    if (file_exists($wizard_review_path)) {
        wp_enqueue_style('preowned-clothing-wizard-review', plugin_dir_url(__FILE__) . 'assets/css/wizard-review.css', array('preowned-clothing-wizard'), '1.0.0');
    }
    
    // Enqueue drag-and-drop upload styles
    $drag_drop_upload_path = plugin_dir_path(__FILE__) . 'assets/css/drag-drop-upload.css';
    if (file_exists($drag_drop_upload_path)) {
        wp_enqueue_style('preowned-clothing-drag-drop', plugin_dir_url(__FILE__) . 'assets/css/drag-drop-upload.css', array('preowned-clothing-style'), '1.0.0');
    }
    
    // Enqueue category selection styles
    wp_enqueue_style('preowned-clothing-category-selection', 
        plugin_dir_url(__FILE__) . 'assets/css/category-selection.css',
        array('preowned-clothing-style'), '1.0.0');
    
    // Enqueue real-time feedback styles
    $real_time_feedback_path = plugin_dir_path(__FILE__) . 'assets/css/real-time-feedback.css';
    if (file_exists($real_time_feedback_path)) {
        wp_enqueue_style('preowned-clothing-real-time-feedback', plugin_dir_url(__FILE__) . 'assets/css/real-time-feedback.css', array('preowned-clothing-style'), '1.0.0');
    }
    
    // Enqueue jQuery UI from CDN when local file doesn't exist
    $jquery_ui_path = plugin_dir_path(__FILE__) . 'assets/js/vendor/jquery-ui.min.js';
    if (!file_exists($jquery_ui_path)) {
        wp_enqueue_style('jquery-ui-css', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');
        wp_enqueue_script('jquery-ui-cdn', 'https://code.jquery.com/ui/1.12.1/jquery-ui.min.js', array('jquery'), '1.12.1', true);
    }
    
    // Main script
    wp_enqueue_script('preowned-clothing-form', plugin_dir_url(__FILE__) . 'assets/js/script.js', array('jquery'), '1.1.0', true);
    
    // Enqueue wizard interface script
    $wizard_interface_js_path = plugin_dir_path(__FILE__) . 'assets/js/wizard-interface.js';
    if (file_exists($wizard_interface_js_path)) {
        wp_enqueue_script('preowned-clothing-wizard', plugin_dir_url(__FILE__) . 'assets/js/wizard-interface.js', array('jquery'), '1.0.1', true);
    }
    
    // Enqueue image upload script
    wp_enqueue_script('preowned-clothing-image-upload',
        plugin_dir_url(__FILE__) . 'assets/js/image-upload.js',
        ['jquery'], '1.0.0', true);
    
    // Enqueue form validation script
    wp_enqueue_script('preowned-clothing-form-validation',
        plugin_dir_url(__FILE__) . 'assets/js/form-validation.js',
        ['jquery'], '1.0.0', true);
    
    // Enqueue form storage script
    wp_enqueue_script('preowned-clothing-form-storage',
        plugin_dir_url(__FILE__) . 'assets/js/form-storage.js',
        ['jquery'], '1.0.0', true);
    
    // Enqueue item management script
    wp_enqueue_script('preowned-clothing-item-management',
        plugin_dir_url(__FILE__) . 'assets/js/item-management.js',
        ['jquery', 'preowned-clothing-form-storage'], '1.0.0', true);
        
    // Remove any previous category-handler scripts
    wp_deregister_script('preowned-clothing-category-handler');
    
    // Ensure jQuery is loaded first
    wp_enqueue_script('jquery');
    
    // Enqueue category handler script with proper dependencies and version (adding timestamp for cache busting)
    wp_enqueue_script('preowned-clothing-category-handler',
        plugin_dir_url(__FILE__) . 'assets/js/category-handler.js',
        ['jquery'], time(), true);
    
    // Enqueue diagnostic script in debug mode
    if (defined('WP_DEBUG') && WP_DEBUG) {
        wp_enqueue_script('preowned-clothing-diagnostic', 
            plugin_dir_url(__FILE__) . 'assets/js/diagnostic.js',
            [], '1.0.0', true);
    }
    
    // Enqueue form autosave script
    wp_enqueue_script('preowned-clothing-form-autosave',
        plugin_dir_url(__FILE__) . 'assets/js/form-autosave.js',
        ['jquery'], '1.0.1', true);
    
    // Enqueue keyboard accessibility enhancements
    wp_enqueue_script('preowned-clothing-keyboard-accessibility',
        plugin_dir_url(__FILE__) . 'assets/js/keyboard-accessibility.js',
        ['jquery'], '1.0.0', true);
    
    // Only add to admin or pages with our shortcode
    global $post;
    if (is_admin() || (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'preowned_clothing_form'))) {
        wp_localize_script('preowned-clothing-form', 'pcf_ajax_object', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('preowned_clothing_ajax_nonce'),
            'plugin_url' => plugin_dir_url(__FILE__)
         ));
        wp_localize_script('preowned-clothing-category-handler', 'pcfFormOptions', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('preowned_clothing_ajax_nonce'),
            'plugin_url' => plugin_dir_url(__FILE__),
            'debug' => true
        ));
    }
}
add_action('wp_enqueue_scripts', 'preowned_clothing_enqueue_scripts');

/**
 * Add viewport meta tag for proper mobile rendering
 */
function preowned_clothing_add_viewport_meta() {
    // Only add on pages with our shortcode
    global $post;
    if (!is_a($post, 'WP_Post') || !has_shortcode($post->post_content, 'preowned_clothing_form')) {
        return;
    }
    
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">';
}
add_action('wp_head', 'preowned_clothing_add_viewport_meta');

// Make sure database files are loaded first
require_once plugin_dir_path(__FILE__) . 'includes/database-setup.php';

// Include other plugin files
require_once plugin_dir_path(__FILE__) . 'includes/clothing-categories.php';
require_once plugin_dir_path(__FILE__) . 'includes/clothing-sizes.php'; // Add clothing sizes
require_once plugin_dir_path(__FILE__) . 'includes/form-display.php';
require_once plugin_dir_path(__FILE__) . 'includes/form-submission-handler.php';
require_once plugin_dir_path(__FILE__) . 'includes/email-notifications.php';

// Include additional admin files
if (is_admin()) {
    require_once plugin_dir_path(__FILE__) . 'includes/admin-submissions.php';
    require_once plugin_dir_path(__FILE__) . 'includes/admin-image-test.php';
    require_once plugin_dir_path(__FILE__) . 'includes/dashboard-widget.php';
    
    // Include new admin customization modules
    $admin_dir = plugin_dir_path(__FILE__) . 'includes/admin/';
    if (is_dir($admin_dir)) {
        // Create directory if it doesn't exist
        if (!file_exists($admin_dir)) {
            mkdir($admin_dir, 0755, true);
        }
        
        // Check for individual files
        $admin_files = [
            'category-manager.php',
            'size-manager.php',
            'form-field-manager.php'
        ];
        
        foreach ($admin_files as $file) {
            $file_path = $admin_dir . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            }
        }
    }
}

/**
 * Main shortcode function
 */
function preowned_clothing_form_shortcode($atts = []) {
    // Include required files if not already loaded
    if (!class_exists('PCF_Form_Renderer')) {
        require_once PCF_PLUGIN_DIR . 'includes/form/form-renderer.php';
    }
    
    // Output buffer to capture content
    ob_start();
    
    // Get form customization settings
    $options = [
        'form_title' => get_option('preowned_clothing_form_title', 'Submit Your Pre-owned Clothing'),
        'form_intro' => get_option('preowned_clothing_form_intro', 'You can submit multiple clothing items in a single form.'),
        'max_items' => intval(get_option('preowned_clothing_max_items', 10)),
        'primary_color' => get_option('preowned_clothing_primary_color', '#0073aa'),
        'secondary_color' => get_option('preowned_clothing_secondary_color', '#005177'),
        'max_image_size' => intval(get_option('preowned_clothing_max_image_size', 2)),
        'required_images' => get_option('preowned_clothing_required_images', ['front', 'back', 'brand_tag']),
    ];
    
    // Display success/error messages
    $feedback = PCF_Session_Manager::get_feedback();
    if ($feedback['status'] === 'success') {
        // Show success message and return
        PCF_Session_Manager::clear_feedback();
        
        echo '<div class="submission-feedback success">';
        echo '<strong>Success!</strong> ' . esc_html($feedback['message'] ?: 'Thank you for your submission! We will review your items and contact you soon.') . '</div>';
        
        // Add script to clear localStorage data
        echo '<script>
            if(typeof(Storage) !== "undefined") {
                localStorage.removeItem("clothingFormData");
                console.log("Form submitted successfully - cleared saved data");
            }
        </script>';
        
        return ob_get_clean();
    }
    
    // Show error messages if present
    if ($feedback['status'] === 'error') {
        echo '<div class="submission-feedback error">';
        echo '<strong>Error:</strong> ' . esc_html($feedback['message']) . '</div>';
        
        // Clear error after displaying
        PCF_Session_Manager::clear_feedback();
    }
    
    try {
        // Create form renderer
        $renderer = new PCF_Form_Renderer($options);
        
        // Render the form
        echo $renderer->render();
    } catch (Exception $e) {
        echo '<div class="submission-feedback error">';
        echo '<strong>System Error:</strong> Error loading the clothing form. Please try again later.</div>';
        
        if (current_user_can('manage_options')) {
            echo '<div class="admin-error-notice">';
            echo '<p>Admin notice: ' . esc_html($e->getMessage()) . '</p>';
            echo '</div>';
        }
    }
    
    return ob_get_clean();
}
// Register Shortcode
add_shortcode('preowned_clothing_form', 'preowned_clothing_form_shortcode');

// Register activation hook for database creation
register_activation_hook(__FILE__, 'preowned_clothing_create_submission_table');

/**
 * Add plugin action links
 */
function preowned_clothing_action_links($links) {
    $settings_link = '<a href="' . admin_url('options-general.php?page=preowned-clothing-settings') . '">' . __('Settings', 'preowned-clothing-form') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'preowned_clothing_action_links');

/**
 * Clean up when plugin is uninstalled
 */
function preowned_clothing_uninstall() {
    // Optional: You could add code here to remove the database table when the plugin is uninstalled
    // global $wpdb;
    // $table_name = $wpdb->prefix . 'preowned_clothing_submissions';
    // $wpdb->query("DROP TABLE IF EXISTS $table_name");
}
register_uninstall_hook(__FILE__, 'preowned_clothing_uninstall');

/**
 * AJAX Handler for getting clothing categories
 */
function preowned_clothing_get_categories() {
    // Check nonce
    check_ajax_referer('preowned_clothing_ajax_nonce', 'nonce');
    
    // For debugging - log the request
    error_log('Category data request received');
    
    // Get the categories from the categories file
    $categories_file = plugin_dir_path(__FILE__) . 'includes/clothing-categories.php';
    $categories = include($categories_file);
    
    // Send the response
    wp_send_json_success($categories);
}

// Register the AJAX handlers
add_action('wp_ajax_get_clothing_categories', 'preowned_clothing_get_categories');
add_action('wp_ajax_nopriv_get_clothing_categories', 'preowned_clothing_get_categories');

/**
 * Display form feedback messages from session
 */
function preowned_clothing_display_messages() {
    // Replace direct $_SESSION access with the Session Manager
    $feedback = PCF_Session_Manager::get_feedback();
    
    // Success message
    if (isset($_GET['success']) && $_GET['success'] == '1' || 
        ($feedback['status'] === 'success')) {
        
        // Clear the session flag
        PCF_Session_Manager::clear_feedback();
        
        // Get customized message from settings
        $message = get_option('preowned_clothing_success_message', 
            'Thank you for your submission! We will review your items and contact you soon.');
        
        echo '<div class="submission-feedback success" data-submission-success="true">';
        echo '<strong>Success!</strong> ' . esc_html($message) . '</div>';
        
        // Add script to clear localStorage data
        echo '<script>
            if(typeof(Storage) !== "undefined") {
                localStorage.removeItem("clothingFormData");
                console.log("Form submitted successfully - cleared saved data");
            }
        </script>';
    }
    
    // Error message
    if ($feedback['status'] === 'error') {
        echo '<div class="submission-feedback error">' . esc_html($feedback['message']) . '</div>';
        PCF_Session_Manager::clear_feedback();
    }
}
add_action('wp_footer', 'preowned_clothing_display_messages');
