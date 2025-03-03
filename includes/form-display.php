<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Load the clothing categories data
global $clothing_categories_hierarchical;
$clothing_categories_file = dirname(__FILE__) . '/clothing-categories.php';

// More detailed error checking and debugging for categories file
if (file_exists($clothing_categories_file)) {
    $clothing_categories_hierarchical = include($clothing_categories_file);
    // Debug log if categories file loaded
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('Clothing categories file found at: ' . $clothing_categories_file);
        if (empty($clothing_categories_hierarchical)) {
            error_log('WARNING: Clothing categories file loaded but returned empty array');
        } else {
            error_log('Categories loaded successfully with ' . count($clothing_categories_hierarchical) . ' main categories');
        }
    }
} else {
    // Debug log if categories file not found
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('ERROR: Clothing categories file not found at: ' . $clothing_categories_file);
        // Try to help diagnose the issue by checking directory permissions
        if (is_dir(dirname(__FILE__))) {
            error_log('Parent directory exists. Checking contents:');
            $files = scandir(dirname(__FILE__));
            error_log('Directory contents: ' . implode(', ', $files));
        }
    }
    
    // Create a fallback categories array to prevent errors
    $clothing_categories_hierarchical = [
        'womens' => [
            'name' => 'Women\'s',
            'subcategories' => [
                'tops' => ['name' => 'Tops'],
                'bottoms' => ['name' => 'Bottoms'],
                'dresses' => ['name' => 'Dresses']
            ]
        ],
        'mens' => [
            'name' => 'Men\'s',
            'subcategories' => [
                'tops' => ['name' => 'Tops'],
                'bottoms' => ['name' => 'Bottoms']
            ]
        ]
    ];
}

// Check for form submission status with enhanced debug info
if (isset($_SESSION['submission_status'])) {
    $submission_status = $_SESSION['submission_status'];
    $submission_message = $_SESSION['submission_message'];
    $submission_debug_info = isset($_SESSION['submission_debug_info']) ? $_SESSION['submission_debug_info'] : '';
    unset($_SESSION['submission_status']);
    unset($_SESSION['submission_message']);
    unset($_SESSION['submission_debug_info']);
} else {
    // Also check URL parameters for direct access using the parameter names
    if (!function_exists('sanitize_text_field')) {
        // Check if ABSPATH is defined, or find WordPress load
        if (!defined('ABSPATH')) {
            // Try to find WordPress load by going up directories
            $wp_load_file = dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';
            if (file_exists($wp_load_file)) {
                require_once($wp_load_file);
            }
        } else if (defined('ABSPATH')) {
            $abspath = constant('ABSPATH'); // Use constant() function to safely get the constant value
            if (file_exists($abspath . 'wp-includes/formatting.php')) {
                require_once($abspath . 'wp-includes/formatting.php');
            }
        }
    }
    // Check if sanitize_text_field exists, otherwise create a simple fallback
    if (!function_exists('sanitize_text_field')) {
        function sanitize_text_field($str) {
            return htmlspecialchars(strip_tags($str), ENT_QUOTES, 'UTF-8');
        }
    }
    
    $submission_status = isset($_GET['pcf_submission_status']) ? sanitize_text_field($_GET['pcf_submission_status']) : null;
    $submission_message = $submission_status === 'success' ? 
        'Your clothing item submission was successful! Thank you. Someone from our team will be reaching out to you within 24-48 hours.' : 
        (isset($_GET['submission_message']) ? sanitize_text_field($_GET['submission_message']) : null);
    $submission_debug_info = '';
}

/**
 * Display the clothing submission form with proper feedback handling
 */
function preowned_clothing_display_form($atts = []) {
    // Initialize the HTML output buffer
    ob_start();
    
    // Debug information for admins
    if (current_user_can('manage_options') && defined('WP_DEBUG') && WP_DEBUG) {
        echo '<div class="debug-info" style="background: #f8f8f8; padding: 10px; border: 1px solid #ddd; margin-bottom: 20px;">';
        echo '<h4>Debug Information (Only visible to admins)</h4>';
        
        global $clothing_categories_hierarchical;
        echo '<p>Categories loaded: ' . (is_array($clothing_categories_hierarchical) ? count($clothing_categories_hierarchical) : 'No') . '</p>';
        
        if (!is_array($clothing_categories_hierarchical) || empty($clothing_categories_hierarchical)) {
            echo '<p style="color: red;">Warning: No categories found!</p>';
            
            // Check if the file exists
            $categories_file = plugin_dir_path(__FILE__) . 'clothing-categories.php';
            echo '<p>Looking for categories file at: ' . esc_html($categories_file) . '</p>';
            echo '<p>File exists: ' . (file_exists($categories_file) ? 'Yes' : 'No') . '</p>';
        }
        
        echo '<button type="button" onclick="window.testCategoriesData()">Test Categories Data</button>';
        echo '</div>';
    }
    
    // Include the form-renderer class
    require_once plugin_dir_path(__FILE__) . 'form/form-renderer.php';
    
    // Get form customization settings
    $options = [
        'form_title' => get_option('preowned_clothing_form_title', 'Submit Your Pre-owned Clothing'),
        'form_intro' => get_option('preowned_clothing_form_intro', 'You can submit multiple clothing items in a single form. Please provide clear photos and detailed descriptions for each item.'),
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
        echo '<div class="submission-error">';
        echo '<p>' . esc_html($feedback['message']) . '</p>';
        echo '</div>';
        
        // Clear error after displaying
        PCF_Session_Manager::clear_feedback();
    }
    
    // Create and render the form
    global $clothing_categories_hierarchical;
    $options['categories'] = $clothing_categories_hierarchical; // Add categories to options
    
    // Debug - print categories data if admin
    if (current_user_can('manage_options') && defined('WP_DEBUG') && WP_DEBUG) {
        echo '<script>console.log("Categories data being passed to renderer:", ' . json_encode($clothing_categories_hierarchical) . ');</script>';
    }
    
    $renderer = new PCF_Form_Renderer($options);
    echo $renderer->render();
    
    return ob_get_clean();
}

/**
 * Add required scripts and styles for the form
 */
function preowned_clothing_enqueue_form_assets() {
    // Enqueue scripts
    wp_enqueue_script('jquery');
    
    // Enqueue form-specific CSS
    wp_enqueue_style('preowned-clothing-form-style', 
        plugin_dir_url(dirname(__FILE__)) . 'assets/css/style.css', 
        [], '1.1.0');
    
    // Enqueue wizard interface
    wp_enqueue_style('preowned-clothing-wizard', 
        plugin_dir_url(dirname(__FILE__)) . 'assets/css/wizard-interface.css',
        [], '1.1.0');
    
    // Enqueue card layout
    wp_enqueue_style('preowned-clothing-card-layout',
        plugin_dir_url(dirname(__FILE__)) . 'assets/css/card-layout.css',
        [], '1.0.0');
    
    // Enqueue category selection styles
    wp_enqueue_style('preowned-clothing-category-selection',
        plugin_dir_url(dirname(__FILE__)) . 'assets/css/category-selection.css',
        [], '1.0.0');
    
    // Enqueue scripts
    wp_enqueue_script('preowned-clothing-wizard',
        plugin_dir_url(dirname(__FILE__)) . 'assets/js/wizard-interface.js',
        ['jquery'], '1.0.1', true);
        
    wp_enqueue_script('preowned-clothing-image-upload',
        plugin_dir_url(dirname(__FILE__)) . 'assets/js/image-upload.js',
        ['jquery'], '1.0.0', true);
    
    wp_enqueue_script('preowned-clothing-form-validation',
        plugin_dir_url(dirname(__FILE__)) . 'assets/js/form-validation.js',
        ['jquery'], '1.0.0', true);
    
    // Ensure category handler is loaded with current timestamp to avoid caching
    wp_enqueue_script('preowned-clothing-category-handler',
        plugin_dir_url(dirname(__FILE__)) . 'assets/js/category-handler.js',
        ['jquery'], time(), true);
    
    // Get the clothing categories for JavaScript
    global $clothing_categories_hierarchical;
    
    // Localize script with form options and ajax URL
    wp_localize_script('preowned-clothing-category-handler', 'pcfFormOptions', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('preowned_clothing_ajax_nonce'),
        'plugin_url' => plugin_dir_url(dirname(__FILE__)),
        'debug' => true,
        'categories' => $clothing_categories_hierarchical // Add categories data for JS
    ]);
}
add_action('wp_enqueue_scripts', 'preowned_clothing_enqueue_form_assets');

// Add a diagnostic AJAX action to help debug category issues
function pcf_debug_categories_ajax() {
    check_ajax_referer('preowned_clothing_ajax_nonce', 'nonce');
    
    global $clothing_categories_hierarchical;
    
    $response = array(
        'success' => true,
        'categories_loaded' => !empty($clothing_categories_hierarchical),
        'categories_count' => is_array($clothing_categories_hierarchical) ? count($clothing_categories_hierarchical) : 0,
        'categories' => $clothing_categories_hierarchical,
        'categories_file_path' => plugin_dir_path(__FILE__) . 'clothing-categories.php',
        'file_exists' => file_exists(plugin_dir_path(__FILE__) . 'clothing-categories.php'),
    );
    
    wp_send_json($response);
}
add_action('wp_ajax_pcf_debug_categories', 'pcf_debug_categories_ajax');
add_action('wp_ajax_nopriv_pcf_debug_categories', 'pcf_debug_categories_ajax');