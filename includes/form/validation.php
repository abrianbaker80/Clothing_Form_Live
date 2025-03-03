<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Form validation class
 * Handles all validation logic separate from processing logic
 */
class PCF_Validation {
    // Store validation error information
    private $errors = array();
    private $debug_info = '';
    
    /**
     * Validate the entire submission
     * 
     * @return array Validation result with status and messages
     */
    public function validate_submission() {
        // Verify nonce first - security check
        if (!$this->validate_nonce()) {
            return array(
                'is_valid' => false,
                'message' => 'Security check failed. Please refresh the page and try again.',
                'debug_info' => $this->debug_info
            );
        }
        
        // Check for honeypot fields - bot protection
        if ($this->is_bot_submission()) {
            return array(
                'is_valid' => false,
                'message' => 'Your submission was flagged as potential spam.',
                'debug_info' => 'Honeypot field was filled'
            );
        }
        
        // Validate required fields
        $data = $this->validate_and_sanitize_fields();
        if (!empty($this->errors)) {
            return array(
                'is_valid' => false,
                'message' => 'Please fill in all required fields correctly.',
                'debug_info' => $this->debug_info
            );
        }
        
        // Additional custom validations
        $custom_result = apply_filters('preowned_clothing_validate_submission', array('valid' => true, 'error' => ''));
        if (!$custom_result['valid']) {
            return array(
                'is_valid' => false,
                'message' => 'Validation failed: ' . $custom_result['error'],
                'debug_info' => 'Custom validation error: ' . $custom_result['error']
            );
        }
        
        // If we got this far, validation passed
        return array(
            'is_valid' => true,
            'data' => $data,
            'debug_info' => $this->debug_info
        );
    }
    
    /**
     * Validate the security nonce
     * 
     * @return bool True if nonce is valid
     */
    private function validate_nonce() {
        if (!isset($_POST['clothing_form_nonce']) || 
            !wp_verify_nonce($_POST['clothing_form_nonce'], 'clothing_form_submission')) {
            
            $this->debug_info .= "Nonce verification failed:\n";
            $this->debug_info .= "Timestamp: " . current_time('mysql') . "\n";
            $this->debug_info .= "IP Address: " . sanitize_text_field($_SERVER['REMOTE_ADDR']) . "\n";
            $this->debug_info .= "User Agent: " . sanitize_text_field($_SERVER['HTTP_USER_AGENT']) . "\n";
            $this->debug_info .= "Referrer: " . (isset($_SERVER['HTTP_REFERER']) ? sanitize_text_field($_SERVER['HTTP_REFERER']) : 'Not provided') . "\n";
            
            // Log security issue for monitoring
            error_log('[Security Alert] Preowned Clothing Form: Nonce verification failed. Possible CSRF attempt.');
            
            return false;
        }
        
        return true;
    }
    
    /**
     * Check if submission is from a bot
     * 
     * @return bool True if submission appears to be from a bot
     */
    private function is_bot_submission() {
        // Check honeypot field
        if (isset($_POST['website']) && !empty($_POST['website'])) {
            return true;
        }
        
        // Add other bot detection methods here
        
        return false;
    }
    
    /**
     * Validate and sanitize all fields
     * 
     * @return array Sanitized data
     */
    private function validate_and_sanitize_fields() {
        $sanitized = array();
        
        // Contact details
        $sanitized['name'] = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $sanitized['email'] = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        
        // Validate required fields
        if (empty($sanitized['name'])) {
            $this->errors[] = 'name';
            $this->debug_info .= "Missing required field: name\n";
        }
        
        if (empty($sanitized['email'])) {
            $this->errors[] = 'email';
            $this->debug_info .= "Missing required field: email\n";
        } elseif (!is_email($sanitized['email'])) {
            $this->errors[] = 'email_invalid';
            $this->debug_info .= "Invalid email format: " . $sanitized['email'] . "\n";
        }
        
        // Process clothing items
        $sanitized['items'] = array();
        if (isset($_POST['items']) && is_array($_POST['items'])) {
            foreach ($_POST['items'] as $item_id => $item_data) {
                // Skip empty items
                if (empty($item_data['description'])) {
                    continue;
                }
                
                $sanitized_item = array(
                    'description' => sanitize_textarea_field($item_data['description']),
                    'size' => isset($item_data['size']) ? sanitize_text_field($item_data['size']) : '',
                );
                
                // Process category fields
                foreach ($item_data as $field => $value) {
                    if (strpos($field, 'category_level_') === 0) {
                        $sanitized_item[$field] = sanitize_text_field($value);
                    }
                }
                
                // Add to items array
                $sanitized['items'][$item_id] = $sanitized_item;
            }
        }
        
        // Check that at least one item was submitted
        if (empty($sanitized['items'])) {
            $this->errors[] = 'no_items';
            $this->debug_info .= "No clothing items were submitted\n";
        }
        
        return $sanitized;
    }
    
    /**
     * Static method to validate required fields
     * 
     * @param array $data Form data to validate
     * @param array $required_fields List of required field names
     * @return array Validation result ['valid' => bool, 'errors' => array]
     */
    public static function validate_required_fields($data, $required_fields) {
        $result = [
            'valid' => true,
            'errors' => []
        ];
        
        foreach ($required_fields as $field) {
            if (!isset($data[$field]) || trim($data[$field]) === '') {
                $result['valid'] = false;
                $result['errors'][$field] = sprintf(__('The %s field is required.', 'preowned-clothing-form'), $field);
            }
        }
        
        return $result;
    }
    
    /**
     * Validate email address
     * 
     * @param string $email Email address to validate
     * @return bool Whether the email is valid
     */
    public static function validate_email($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Validate phone number (basic format check)
     * 
     * @param string $phone Phone number to validate
     * @return bool Whether the phone number is valid
     */
    public static function validate_phone($phone) {
        // Remove all non-numeric characters
        $phone_clean = preg_replace('/[^0-9]/', '', $phone);
        
        // Check if it's a valid length (adjust as needed for your country)
        return strlen($phone_clean) >= 10;
    }
    
    /**
     * Validate image file
     * 
     * @param array $file $_FILES element
     * @return array Validation result ['valid' => bool, 'error' => string]
     */
    public static function validate_image($file) {
        $result = [
            'valid' => true,
            'error' => ''
        ];
        
        // Check if file was uploaded properly
        if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
            $result['valid'] = false;
            $result['error'] = __('No file was uploaded.', 'preowned-clothing-form');
            return $result;
        }
        
        // Check file size (limit to 10MB)
        $max_size = 10 * 1024 * 1024; // 10MB in bytes
        if ($file['size'] > $max_size) {
            $result['valid'] = false;
            $result['error'] = __('File is too large. Maximum size is 10MB.', 'preowned-clothing-form');
            return $result;
        }
        
        // Check file type
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($file['type'], $allowed_types)) {
            $result['valid'] = false;
            $result['error'] = __('Invalid file type. Only JPG, PNG, and GIF are allowed.', 'preowned-clothing-form');
            return $result;
        }
        
        // Verify it's actually an image
        $image_info = getimagesize($file['tmp_name']);
        if ($image_info === false) {
            $result['valid'] = false;
            $result['error'] = __('Uploaded file is not a valid image.', 'preowned-clothing-form');
            return $result;
        }
        
        return $result;
    }
    
    /**
     * Validate complete form submission
     * 
     * @param array $data Form data
     * @return array Validation result ['valid' => bool, 'errors' => array]
     */
    public static function validate_form_submission($data) {
        $result = [
            'valid' => true,
            'errors' => []
        ];
        
        // Required fields
        $required_fields = [
            'name',
            'email',
            'phone',
            'category',
            'description'
        ];
        
        $required_check = self::validate_required_fields($data, $required_fields);
        if (!$required_check['valid']) {
            $result['valid'] = false;
            $result['errors'] = array_merge($result['errors'], $required_check['errors']);
        }
        
        // Email validation
        if (isset($data['email']) && !empty($data['email']) && !self::validate_email($data['email'])) {
            $result['valid'] = false;
            $result['errors']['email'] = __('Please enter a valid email address.', 'preowned-clothing-form');
        }
        
        // Phone validation
        if (isset($data['phone']) && !empty($data['phone']) && !self::validate_phone($data['phone'])) {
            $result['valid'] = false;
            $result['errors']['phone'] = __('Please enter a valid phone number.', 'preowned-clothing-form');
        }
        
        return $result;
    }
}
