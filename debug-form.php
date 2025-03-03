<?php
/**
 * Debug utility for the Clothing Form plugin
 * Add this shortcode to a page to see debugging information: [pcf_debug]
 */

// Register debug shortcode
add_shortcode('pcf_debug', 'pcf_debug_information');

/**
 * Display debugging information about the form plugin
 */
function pcf_debug_information() {
    ob_start();
    
    echo '<div style="background:#f5f5f5; padding:20px; border:1px solid #ddd; margin:20px 0;">';
    echo '<h2>Clothing Form Debug Information</h2>';

    // Check if key files exist
    echo '<h3>File Existence Check:</h3>';
    $plugin_dir = plugin_dir_path(__FILE__);
    
    $files_to_check = [
        'includes/form/form-renderer.php',
        'includes/form/validation.php',
        'includes/form/image-uploader.php',
        'includes/form/database.php',
        'includes/form/session-manager.php',
        'includes/form-display.php',
        'includes/form-submission-handler.php',
        'assets/js/wizard-interface.js',
        'assets/js/category-handler.js',
        'assets/js/image-upload.js',
        'assets/js/form-validation.js',
        'assets/css/wizard-interface.css',
        'assets/css/style.css',
        'assets/css/category-selection.css',
        'assets/css/card-layout.css'
    ];
    
    echo '<table style="width:100%; border-collapse:collapse;">';
    echo '<tr><th style="text-align:left; border:1px solid #ddd; padding:8px;">File</th><th style="text-align:left; border:1px solid #ddd; padding:8px;">Status</th></tr>';
    
    foreach ($files_to_check as $file) {
        $file_path = $plugin_dir . $file;
        $exists = file_exists($file_path);
        $status = $exists ? 
            '<span style="color:green;">EXISTS</span>' : 
            '<span style="color:red;">MISSING</span>';
        
        echo "<tr><td style=\"border:1px solid #ddd; padding:8px;\">$file</td><td style=\"border:1px solid #ddd; padding:8px;\">$status</td></tr>";
    }
    
    echo '</table>';
    
    // Check if the form renderer class exists
    echo '<h3>Class Check:</h3>';
    
    // Try to include the form renderer
    if (file_exists($plugin_dir . 'includes/form/form-renderer.php')) {
        include_once($plugin_dir . 'includes/form/form-renderer.php');
        
        if (class_exists('PCF_Form_Renderer')) {
            echo '<p style="color:green;">PCF_Form_Renderer class exists and is accessible.</p>';
            
            // Try to instantiate the class
            try {
                $renderer = new PCF_Form_Renderer();
                echo '<p style="color:green;">Successfully created PCF_Form_Renderer instance.</p>';
            } catch (Exception $e) {
                echo '<p style="color:red;">Error creating PCF_Form_Renderer instance: ' . esc_html($e->getMessage()) . '</p>';
            }
        } else {
            echo '<p style="color:red;">PCF_Form_Renderer class does not exist despite the file being included.</p>';
            echo '<p>Contents of form-renderer.php:</p>';
            echo '<pre style="background:#fff; padding:10px; max-height:200px; overflow:auto;">';
            echo esc_html(file_get_contents($plugin_dir . 'includes/form/form-renderer.php'));
            echo '</pre>';
        }
    } else {
        echo '<p style="color:red;">form-renderer.php file does not exist.</p>';
    }
    
    // Check if Session Manager is working
    echo '<h3>Session Manager Check:</h3>';
    if (file_exists($plugin_dir . 'includes/form/session-manager.php')) {
        include_once($plugin_dir . 'includes/form/session-manager.php');
        
        if (class_exists('PCF_Session_Manager')) {
            echo '<p style="color:green;">PCF_Session_Manager class exists and is accessible.</p>';
            
            // Test session functions
            try {
                PCF_Session_Manager::initialize();
                PCF_Session_Manager::set_feedback('test', 'This is a test message', 'Debug test');
                $feedback = PCF_Session_Manager::get_feedback();
                
                echo '<p>Session test: ';
                if ($feedback['status'] === 'test' && $feedback['message'] === 'This is a test message') {
                    echo '<span style="color:green;">SUCCESS</span>';
                } else {
                    echo '<span style="color:red;">FAILED</span>';
                }
                echo '</p>';
                
                PCF_Session_Manager::clear_feedback();
            } catch (Exception $e) {
                echo '<p style="color:red;">Error testing PCF_Session_Manager: ' . esc_html($e->getMessage()) . '</p>';
            }
        } else {
            echo '<p style="color:red;">PCF_Session_Manager class does not exist despite the file being included.</p>';
        }
    } else {
        echo '<p style="color:red;">session-manager.php file does not exist.</p>';
    }
    
    // Check shortcode registration
    echo '<h3>Shortcode Check:</h3>';
    global $shortcode_tags;
    if (isset($shortcode_tags['preowned_clothing_form'])) {
        echo '<p style="color:green;">preowned_clothing_form shortcode is registered.</p>';
    } else {
        echo '<p style="color:red;">preowned_clothing_form shortcode is NOT registered.</p>';
    }
    
    // Check for JavaScript errors
    echo '<h3>JavaScript Check:</h3>';
    echo '<p>Open your browser console (F12) to check for JavaScript errors when loading the form.</p>';
    echo '<script>
    console.log("PCF Debug: Checking for clothing form scripts...");
    if (typeof jQuery !== "undefined") {
        console.log("PCF Debug: jQuery is loaded");
    } else {
        console.error("PCF Debug: jQuery is NOT loaded");
    }
    
    // Check if our scripts are loaded
    setTimeout(function() {
        if (typeof pcfFormOptions !== "undefined") {
            console.log("PCF Debug: Form options are loaded", pcfFormOptions);
        } else {
            console.error("PCF Debug: Form options are NOT loaded");
        }
    }, 500);
    </script>';
    
    // End debug info
    echo '</div>';
    
    return ob_get_clean();
}

// Direct test - will output HTML if accessed directly
if (basename($_SERVER['SCRIPT_FILENAME']) == basename(__FILE__)) {
    // Basic WP functions for direct access
    if (!function_exists('plugin_dir_path')) {
        function plugin_dir_path($file) {
            return dirname($file) . '/';
        }
    }
    
    if (!function_exists('esc_html')) {
        function esc_html($text) {
            return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        }
    }
    
    echo '<html><head><title>PCF Debug</title></head><body>';
    echo pcf_debug_information();
    echo '</body></html>';
}
