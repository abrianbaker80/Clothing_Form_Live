<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Include the original content from includes/form-display.php
// This is a minimal version - the full file should be moved here

/**
 * Display the clothing submission form with proper feedback handling
 */
function preowned_clothing_display_form($atts = []) {
    // Initialize the HTML output buffer
    ob_start();
    
    // Start form display logic
    // Load required form classes
    require_once(dirname(__FILE__) . '/form-renderer.php');
    
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
        return render_success_message($feedback['message']);
    }
    
    // Show error messages if present
    if ($feedback['status'] === 'error') {
        echo '<div class="submission-error">';
        echo '<p>' . esc_html($feedback['message']) . '</p>';
        echo '</div>';
        
        // Clear error after displaying
        PCF_Session_Manager::clear_feedback();
    }
    
    // If not success, continue with form
    $renderer = new PCF_Form_Renderer($options);
    
    // Enqueue necessary scripts and styles
    preowned_clothing_enqueue_form_assets();
    
    // Return the rendered form
    return $renderer->render();
    
    return ob_get_clean();
}

// Define alias function for compatibility with main plugin file
if (!function_exists('preowned_clothing_form_shortcode')) {
    function preowned_clothing_form_shortcode($atts = []) {
        return preowned_clothing_display_form($atts);
    }
}

/**
 * Enqueue all required assets for the form
 */
function preowned_clothing_enqueue_form_assets() {
    // Enqueue CSS
    wp_enqueue_style('preowned-clothing-wizard', 
        plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/css/wizard-interface.css',
        [], '1.1.0');
    
    wp_enqueue_style('preowned-clothing-card-layout',
        plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/css/card-layout.css',
        [], '1.0.0');
    
    // Add category selection CSS
    wp_enqueue_style('preowned-clothing-category-selection',
        plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/css/category-selection.css',
        [], '1.0.0');
    
    // Enqueue JS files
    wp_enqueue_script('preowned-clothing-wizard',
        plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/js/wizard-interface.js',
        ['jquery'], '1.0.1', true);
        
    wp_enqueue_script('preowned-clothing-image-upload',
        plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/js/image-upload.js',
        ['jquery'], '1.0.0', true);
    
    wp_enqueue_script('preowned-clothing-form-validation',
        plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/js/form-validation.js',
        ['jquery'], '1.0.0', true);
    
    // Make sure category handler is loaded (force version to avoid cache)
    wp_enqueue_script('preowned-clothing-category-handler',
        plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/js/category-handler.js',
        ['jquery'], time(), true);
    
    // Localize script with form options
    wp_localize_script('preowned-clothing-category-handler', 'pcfFormOptions', [
        'max_items' => get_option('preowned_clothing_max_items', 10),
        'max_image_size' => get_option('preowned_clothing_max_image_size', 2),
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('preowned_clothing_form')
    ]);
}

/**
 * Render success message
 */
function render_success_message($message = '') {
    if (empty($message)) {
        $message = 'Thank you for your submission! We will review your items and contact you soon.';
    }
    
    $output = '<div class="submission-success">';
    $output .= '<h3>Submission Received!</h3>';
    $output .= '<p>' . esc_html($message) . '</p>';
    $output .= '</div>';
    
    // Add script to clear localStorage data
    $output .= '<script>
        if(typeof(Storage) !== "undefined") {
            localStorage.removeItem("clothingFormData");
            console.log("Form submitted successfully - cleared saved data");
        }
        
        if (typeof(ga) === "function") { 
            ga("send", "event", "Clothing Form", "Submit", "Success"); 
        }
    </script>';
    
    return $output;
}
