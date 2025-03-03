<?php
/**
 * Security Enhancements
 *
 * Improves the security of the plugin.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Enhanced file type validation
 * 
 * @param array $file File data from $_FILES
 * @return bool|string True if valid, error message otherwise
 */
function preowned_clothing_validate_image($file) {
    // Basic file type validation
    $allowed_types = array('image/jpeg', 'image/png', 'image/gif');
    $allowed_extensions = array('jpg', 'jpeg', 'png', 'gif');
    
    // Get file info
    $file_name = $file['name'];
    $file_type = $file['type'];
    $file_tmp = $file['tmp_name'];
    
    // Check file extension
    $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    if (!in_array($file_extension, $allowed_extensions)) {
        return 'Invalid file extension. Allowed types: ' . implode(', ', $allowed_extensions);
    }
    
    // Check MIME type from the file itself (more reliable)
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detected_type = finfo_file($finfo, $file_tmp);
        finfo_close($finfo);
        
        if (!in_array($detected_type, $allowed_types)) {
            return 'Invalid file type detected. Allowed types: JPG, PNG, GIF.';
        }
    }
    
    // Check if the file is actually an image
    if (!getimagesize($file_tmp)) {
        return 'The uploaded file is not a valid image.';
    }
    
    // Check for malicious code in the image
    $image_contents = file_get_contents($file_tmp);
    $suspicious_patterns = array(
        '<?php', '<?=', '<script', 'eval(', 'base64_decode(', 'system(', 'exec('
    );
    
    foreach ($suspicious_patterns as $pattern) {
        if (stripos($image_contents, $pattern) !== false) {
            return 'Potentially malicious content detected in the file.';
        }
    }
    
    return true;
}

/**
 * Add CSRF protection to AJAX requests
 */
function preowned_clothing_ajax_check_nonce() {
    check_ajax_referer('preowned_clothing_ajax_nonce', 'security');
}
add_action('wp_ajax_preowned_clothing_ajax_action', 'preowned_clothing_ajax_check_nonce', 1);
add_action('wp_ajax_nopriv_preowned_clothing_ajax_action', 'preowned_clothing_ajax_check_nonce', 1);

/**
 * Validate and sanitize all form inputs
 * 
 * @param array $data Form data array
 * @return array Sanitized data
 */
function preowned_clothing_sanitize_form_data($data) {
    $sanitized = array();
    
    // Contact Information
    $sanitized['name'] = isset($data['name']) ? sanitize_text_field($data['name']) : '';
    $sanitized['email'] = isset($data['email']) ? sanitize_email($data['email']) : '';
    
    // Items
    if (!empty($data['items']) && is_array($data['items'])) {
        foreach ($data['items'] as $item_id => $item) {
            $sanitized['items'][$item_id] = array(
                'category_level_0' => isset($item['category_level_0']) ? sanitize_text_field($item['category_level_0']) : '',
                'category_level_1' => isset($item['category_level_1']) ? sanitize_text_field($item['category_level_1']) : '',
                'category_level_2' => isset($item['category_level_2']) ? sanitize_text_field($item['category_level_2']) : '',
                'category_level_3' => isset($item['category_level_3']) ? sanitize_text_field($item['category_level_3']) : '',
                'size' => isset($item['size']) ? sanitize_text_field($item['size']) : '',
                'description' => isset($item['description']) ? sanitize_textarea_field($item['description']) : '',
            );
        }
    }
    
    return $sanitized;
}

/**
 * Log failed submission attempts for security monitoring
 */
function preowned_clothing_log_failed_submission($reason, $data = array()) {
    $log_dir = WP_CONTENT_DIR . '/clothing-form-logs';
    
    // Create logs directory if it doesn't exist
    if (!file_exists($log_dir)) {
        wp_mkdir_p($log_dir);
        
        // Add an index.php file to prevent directory listing
        file_put_contents($log_dir . '/index.php', '<?php // Silence is golden');
        
        // Add .htaccess to prevent direct access
        file_put_contents($log_dir . '/.htaccess', 'Deny from all');
    }
    
    $log_file = $log_dir . '/security.log';
    $log_message = '[' . date('Y-m-d H:i:s') . '] ' . $reason;
    $log_message .= ' - IP: ' . sanitize_text_field($_SERVER['REMOTE_ADDR']);
    $log_message .= !empty($data) ? ' - Data: ' . json_encode($data) : '';
    $log_message .= PHP_EOL;
    
    file_put_contents($log_file, $log_message, FILE_APPEND);
}
