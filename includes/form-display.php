<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Load the clothing categories data
global $clothing_categories_hierarchical;
$clothing_categories_hierarchical = include(dirname(__FILE__) . '/clothing-categories.php');

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