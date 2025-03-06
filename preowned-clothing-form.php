<?php

/**
 * Plugin Name: Preowned Clothing Form
 * Plugin URI: https://github.com/abrianbaker80/Clothing_Form_Live.git
 * Description: A customizable form for submitting preowned clothing items.
 * Version: 2.8.2.3
 * Author: Allen Baker
 * Author URI: https://www.thereclaimedhanger.com
 * Text Domain: preowned-clothing-form
 * Domain Path: /languages
 *
 * Changelog:
 * 2.8.1.9 - Fixed real_time_feedback_path variable definition sequence (3/7/2025)
 * 2.8.1.8 - Fixed real_time_feedback_path variable assignment (3/7/2025)
 * 2.8.1.7 - Fixed unassigned variable error for $real_time_feedback_path (3/7/2025)
 * 2.8.1.6 - Fixed additional function redeclaration issue with preowned_clothing_format_bytes() (3/6/2025)
 * 2.8.1.5 - Fixed duplicate function declaration in performance-enhancements.php (3/6/2025)
 * 2.8.1.4 - Updated performance optimizations and fixed compatibility issues (3/6/2025)
 * 2.8.1.1 - Fixed the "add another item" button functionality (3/5/2025)
 * 2.8.1.0 - Enhanced GitHub updater to properly detect and install new plugin versions (3/5/2025)
 * 2.8.0.0 - Added comprehensive security module with anti-bot protection, malware scanning, and rate limiting
 * 2.7.5.1 - Updated plugin structure and included security enhancements
 * 2.7.5.0 - Restored multi-step wizard functionality with proper step navigation and styling
 * 2.7.4.0 - Fixed fatal error in form renderer, added missing methods, improved image preview functionality
 * 2.7.3.0 - Fixed image upload display and preview functionality, added proper form renderer hook integration
 * 2.7.2.0 - Enhanced image upload system: fixed SVG placeholders, restored image optimizer, improved display styles
 * 2.7.1.0 - Fixed image upload section with proper SVG placeholder icons
 * 2.7.0.0 - Enhanced Size Manager with improved category mapping and visual size display
 * 2.6.0.9 - Size Manager improvements, admin menu fixes
 * 1.1.0 - Added Form Field Manager, Category Manager
 * 1.0.0 - Initial release
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Check if plugin_dir_url is defined, if not, make sure we have WordPress core functions
if (!function_exists('plugin_dir_url')) {
    // This ensures WordPress core functions are available
    require_once(ABSPATH . 'wp-includes/plugin.php');
}

// Define plugin constants
define('PCF_VERSION', '2.8.2.3');
define('PCF_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PCF_PLUGIN_URL', plugin_dir_url(__FILE__));

// Initialize global variables to prevent conflicts
global $preowned_clothing_gh_updater_running;
$preowned_clothing_gh_updater_running = false;

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

// Include additional security module (must load early)
require_once plugin_dir_path(__FILE__) . 'includes/advanced-security.php';

// Include performance enhancements
require_once plugin_dir_path(__FILE__) . 'includes/performance-enhancements.php';

/**
 * Initialize GitHub updater if the files exist
 * This function chooses one updater system to avoid conflicts
 */
function preowned_clothing_init_github_updater()
{
    // Prevent duplicate initialization
    static $already_initialized = false;
    if ($already_initialized) {
        return;
    }
    $already_initialized = true;

    // Set a flag for other parts of the code
    global $preowned_clothing_gh_updater_running;

    // Choose ONE updater system - prioritize the newer modular one
    $loader_file = PCF_PLUGIN_DIR . 'includes/github-updater/loader.php';

    if (file_exists($loader_file)) {
        require_once $loader_file;

        // Only initialize if the function exists
        if (function_exists('preowned_clothing_register_updater_hooks')) {
            // This will register the updater with proper hooks
            preowned_clothing_register_updater_hooks(__FILE__, [
                'username' => get_option('preowned_clothing_github_username', 'abrianbaker80'),
                'repository' => get_option('preowned_clothing_github_repository', 'Clothing_Form'),
                'token' => get_option('preowned_clothing_github_token', ''),
                'debug' => defined('WP_DEBUG') && WP_DEBUG,
            ]);

            // Flag that the updater is now running
            $preowned_clothing_gh_updater_running = true;

            // Flag that we're using the new updater
            if (!defined('PCF_USING_NEW_UPDATER')) {
                define('PCF_USING_NEW_UPDATER', true);
            }

            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Preowned Clothing Form - Using modular GitHub updater system');
            }

            return;
        }
    }

    // Fallback to legacy updater ONLY if new one isn't loaded
    $legacy_file = PCF_PLUGIN_DIR . 'includes/github-updater.php';
    if (!defined('PCF_USING_NEW_UPDATER') && file_exists($legacy_file)) {
        try {
            require_once $legacy_file;

            // Include the admin interface
            $admin_file = PCF_PLUGIN_DIR . 'includes/github-updater-admin.php';
            if (is_admin() && file_exists($admin_file)) {
                require_once $admin_file;
            }

            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Preowned Clothing Form - Using legacy GitHub updater system');
            }

            // Setup the updater using admin_init with lower priority
            if (is_admin()) {
                add_action('admin_init', function () {
                    if (!function_exists('preowned_clothing_can_run_updater') || !preowned_clothing_can_run_updater()) {
                        if (defined('WP_DEBUG') && WP_DEBUG) {
                            error_log('Legacy GitHub Updater: Cannot run updater - conflict detected');
                        }
                        return;
                    }

                    global $preowned_clothing_gh_updater_running;
                    $preowned_clothing_gh_updater_running = true;

                    $updater = new Preowned_Clothing_GitHub_Updater(__FILE__);

                    $username = get_option('preowned_clothing_github_username', 'abrianbaker80');
                    $repository = get_option('preowned_clothing_github_repository', 'Clothing_Form');
                    $token = get_option('preowned_clothing_github_token', '');

                    $updater->set_username($username);
                    $updater->set_repository($repository);

                    if (!empty($token)) {
                        $updater->authorize($token);
                    }

                    $updater->initialize();
                }, 5); // Lower priority to run earlier
            }
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Preowned Clothing Form - GitHub updater error: ' . $e->getMessage());
            }
        }
    }
}

// Initialize GitHub updater on plugins_loaded
add_action('plugins_loaded', 'preowned_clothing_init_github_updater', 5);

// Include admin settings
require_once plugin_dir_path(__FILE__) . 'includes/admin-settings.php';

/**
 * Initialize plugin sessions
 */
function preowned_clothing_init()
{
    // Use session manager to initialize
    PCF_Session_Manager::initialize();
}
add_action('init', 'preowned_clothing_init', 1);

/**
 * Enqueue scripts and styles
 */
function preowned_clothing_enqueue_scripts()
{
    // Enqueue Font Awesome for modern icons - with fallback
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css', array(), '5.15.4');

    // Check if modern-theme.css exists before enqueuing
    $modern_theme_path = plugin_dir_path(__FILE__) . 'assets/css/modern-theme.css';
    if (file_exists($modern_theme_path)) {
        wp_enqueue_style('preowned-clothing-modern-theme', plugin_dir_url(__FILE__) . 'assets/css/modern-theme.css', array(), '1.0.0');
    }

    // Check if we are on a page with our shortcode
    global $post;
    $has_our_shortcode = is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'preowned_clothing_form');

    // Only enqueue form-specific scripts if we're on a page with our shortcode
    if ($has_our_shortcode) {
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
            wp_enqueue_style(
                'preowned-clothing-drag-drop',
                plugin_dir_url(__FILE__) . 'assets/css/drag-drop-upload.css',
                array('preowned-clothing-style'),
                PCF_VERSION
            ); // Use version constant for cache busting
        }

        // Enqueue category selection styles
        wp_enqueue_style(
            'preowned-clothing-category-selection',
            plugin_dir_url(__FILE__) . 'assets/css/category-selection.css',
            array('preowned-clothing-style'),
            '1.0.0'
        );

        // Define real-time feedback path before checking existence
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
            // Deregister first to avoid duplicates
            wp_deregister_script('preowned-clothing-wizard');

            wp_enqueue_script(
                'preowned-clothing-wizard',
                plugin_dir_url(__FILE__) . 'assets/js/wizard-interface.js',
                array('jquery'),
                filemtime($wizard_interface_js_path), // Use file modification time for version
                true
            ); // In footer
        }

        // Load wizard styling with the right priority
        wp_enqueue_style(
            'preowned-clothing-wizard-interface',
            plugin_dir_url(__FILE__) . 'assets/css/wizard-interface.css',
            array(),
            filemtime(plugin_dir_path(__FILE__) . 'assets/css/wizard-interface.css')
        );

        // Ensure image upload scripts are loaded with proper version for cache busting
        wp_enqueue_script(
            'preowned-clothing-image-upload',
            plugin_dir_url(__FILE__) . 'assets/js/image-upload.js',
            ['jquery'],
            PCF_VERSION,
            true
        );

        // Ensure drag-drop upload script is loaded if it exists
        $drag_drop_js_path = plugin_dir_path(__FILE__) . 'assets/js/drag-drop-upload.js';
        if (file_exists($drag_drop_js_path)) {
            wp_enqueue_script(
                'preowned-clothing-drag-drop-upload',
                plugin_dir_url(__FILE__) . 'assets/js/drag-drop-upload.js',
                ['jquery', 'preowned-clothing-image-upload'],
                PCF_VERSION,
                true
            );
        }

        // Enqueue form validation script
        wp_enqueue_script(
            'preowned-clothing-form-validation',
            plugin_dir_url(__FILE__) . 'assets/js/form-validation.js',
            ['jquery'],
            '1.0.0',
            true
        );

        // Enqueue form storage script
        wp_enqueue_script(
            'preowned-clothing-form-storage',
            plugin_dir_url(__FILE__) . 'assets/js/form-storage.js',
            ['jquery'],
            '1.0.0',
            true
        );

        // Enqueue item management script
        wp_enqueue_script(
            'preowned-clothing-item-management',
            plugin_dir_url(__FILE__) . 'assets/js/item-management.js',
            ['jquery', 'preowned-clothing-form-storage'],
            '1.0.0',
            true
        );

        // Ensure jQuery is loaded first
        wp_enqueue_script('jquery');

        // DON'T enqueue category-handler here - it will be handled by form-display.php
        // This avoids duplication and issues with different data being passed

        // Enqueue form autosave script
        wp_enqueue_script(
            'preowned-clothing-form-autosave',
            plugin_dir_url(__FILE__) . 'assets/js/form-autosave.js',
            ['jquery'],
            '1.0.1',
            true
        );

        // Enqueue keyboard accessibility enhancements
        wp_enqueue_script(
            'preowned-clothing-keyboard-accessibility',
            plugin_dir_url(__FILE__) . 'assets/js/keyboard-accessibility.js',
            ['jquery'],
            '1.0.0',
            true
        );

        // Set ajax variables that can be used by other scripts
        wp_localize_script('preowned-clothing-form', 'pcf_ajax_object', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('preowned_clothing_ajax_nonce'),
            'plugin_url' => plugin_dir_url(__FILE__),
            'plugin_version' => PCF_VERSION,
        ));
    }

    // Admin-specific scripts and styles
    if (is_admin()) {
        wp_enqueue_style('preowned-clothing-admin-style', plugin_dir_url(__FILE__) . 'assets/css/admin-style.css', array(), '1.0.0');
        wp_enqueue_script('preowned-clothing-admin-script', plugin_dir_url(__FILE__) . 'assets/js/admin-script.js', array('jquery'), '1.0.0', true);
    }
}
add_action('wp_enqueue_scripts', 'preowned_clothing_enqueue_scripts', 10); // Use default priority, lower than form-display.php

/**
 * Add viewport meta tag for proper mobile rendering
 */
function preowned_clothing_add_viewport_meta()
{
    // Only add on pages with our shortcode
    global $post;
    if (!is_a($post, 'WP_Post') || !has_shortcode($post->post_content, 'preowned_clothing_form')) {
        return;
    }

    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">';
}
add_action('wp_head', 'preowned_clothing_add_viewport_meta');

// Include additional security module (must load early)
require_once plugin_dir_path(__FILE__) . 'includes/advanced-security.php';

// Make sure database files are loaded first
require_once plugin_dir_path(__FILE__) . 'includes/database-setup.php';

// Include other plugin files
require_once plugin_dir_path(__FILE__) . 'includes/clothing-categories.php';
require_once plugin_dir_path(__FILE__) . 'includes/clothing-sizes.php';
require_once plugin_dir_path(__FILE__) . 'includes/form-display.php'; // This is the correct file to include
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
function preowned_clothing_form_shortcode($atts = [])
{
    ob_start();

    // Display form feedback messages
    preowned_clothing_display_messages();

    // Get form customization settings
    $options = [
        'title' => get_option('preowned_clothing_form_title', 'Submit Your Pre-owned Clothing'),
        'intro' => get_option('preowned_clothing_form_intro', 'You can submit multiple clothing items in a single form.'),
        'max_items' => intval(get_option('preowned_clothing_max_items', 10)),
        'primary_color' => get_option('preowned_clothing_primary_color', '#0073aa'),
        'secondary_color' => get_option('preowned_clothing_secondary_color', '#005177'),
        'max_image_size' => intval(get_option('preowned_clothing_max_image_size', 2)),
        'required_images' => get_option('preowned_clothing_required_images', ['front', 'back', 'brand_tag']),
    ];

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
function preowned_clothing_action_links($links)
{
    $settings_link = '<a href="' . admin_url('options-general.php?page=preowned-clothing-settings') . '">' . __('Settings', 'preowned-clothing-form') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'preowned_clothing_action_links');

/**
 * Clean up when plugin is uninstalled
 */
function preowned_clothing_uninstall()
{
    // Optional: You could add code here to remove the database table when the plugin is uninstalled
    // global $wpdb;
    // $table_name = $wpdb->prefix . 'preowned_clothing_submissions';
    // $wpdb->query("DROP TABLE IF EXISTS $table_name");
}
register_uninstall_hook(__FILE__, 'preowned_clothing_uninstall');

/**
 * AJAX Handler for getting clothing categories
 */
function preowned_clothing_get_categories()
{
    // Verify nonce and exit if invalid
    if (!isset($_REQUEST['nonce']) || !wp_verify_nonce($_REQUEST['nonce'], 'preowned_clothing_ajax_nonce')) {
        wp_send_json_error('Security check failed');
        exit;
    }

    // For debugging - log the request only in debug mode
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('Category data request received');
    }

    // Get the categories from the categories file
    $categories_file = plugin_dir_path(__FILE__) . 'includes/clothing-categories.php';

    if (!file_exists($categories_file)) {
        wp_send_json_error('Categories file not found');
        exit;
    }

    $categories = include($categories_file);

    // Validate the categories array
    if (!is_array($categories)) {
        wp_send_json_error('Invalid categories data');
        exit;
    }

    // Send the response
    wp_send_json_success($categories);
}

// Register the AJAX handlers
add_action('wp_ajax_get_clothing_categories', 'preowned_clothing_get_categories');
add_action('wp_ajax_nopriv_get_clothing_categories', 'preowned_clothing_get_categories');

/**
 * Display form feedback messages from session
 */
function preowned_clothing_display_messages()
{
    // Replace direct $_SESSION access with the Session Manager
    $feedback = PCF_Session_Manager::get_feedback();

    // Success message
    if ((isset($_GET['success']) && $_GET['success'] == '1') ||
        ($feedback['status'] === 'success')
    ) {
        // Clear the session flag
        PCF_Session_Manager::clear_feedback();

        // Get customized message from settings
        $message = get_option(
            'preowned_clothing_success_message',
            'Thank you for your submission! We will review your items and contact you soon.'
        );

        echo '<div class="submission-feedback success" data-submission-success="true">';
        echo '<strong>Success!</strong> ' . esc_html($message) . '</div>';

        // Add script to clear localStorage data with error handling
        echo '<script>
            (function() {
                try {
                    if(typeof(Storage) !== "undefined") {
                        localStorage.removeItem("clothingFormData");
                        console.log("Form submitted successfully - cleared saved data");
                    }
                } catch(e) {
                    console.error("Error clearing form data:", e);
                }
            })();
        </script>';
    }

    // Error message
    if ($feedback['status'] === 'error' && !empty($feedback['message'])) {
        echo '<div class="submission-feedback error">' . esc_html($feedback['message']) . '</div>';
        PCF_Session_Manager::clear_feedback();
    }
}
add_action('wp_footer', 'preowned_clothing_display_messages');
