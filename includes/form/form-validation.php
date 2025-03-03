<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Form Validation Class
 * Handles server-side validation of form submissions
 */
class PCF_Validation {
    /**
     * Validate a form submission
     *
     * @return array Validation result
     */
    public function validate_submission() {
        // Check nonce
        if (!isset($_POST['clothing_form_nonce']) || 
            !wp_verify_nonce($_POST['clothing_form_nonce'], 'clothing_form_submission')) {
            return array(
                'is_valid' => false,
                'message' => 'Security verification failed. Please try again.',
                'debug_info' => 'Nonce verification failed.'
            );
        }
        
        // Validate required contact fields
        $required_fields = array('name', 'email', 'phone', 'address', 'city', 'state', 'zip');
        foreach ($required_fields as $field) {
            if (!isset($_POST[$field]) || empty($_POST[$field])) {
                return array(
                    'is_valid' => false,
                    'message' => 'Please fill in all required contact fields.',
                    'debug_info' => "Missing required field: $field"
                );
            }
        }
        
        // Validate email format
        if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            return array(
                'is_valid' => false,
                'message' => 'Please enter a valid email address.',
                'debug_info' => 'Invalid email format.'
            );
        }
        
        // Validate items - make sure at least one exists and has required fields
        if (!isset($_POST['items']) || !is_array($_POST['items']) || empty($_POST['items'])) {
            return array(
                'is_valid' => false,
                'message' => 'Please add at least one clothing item.',
                'debug_info' => 'No items submitted.'
            );
        }
        
        // Validate each item
        $valid_items = array();
        foreach ($_POST['items'] as $item_id => $item) {
            // Check required item fields
            if (!isset($item['gender']) || empty($item['gender']) ||
                !isset($item['category_level_0']) || empty($item['category_level_0']) ||
                !isset($item['size']) || empty($item['size']) ||
                !isset($item['description']) || empty($item['description'])) {
                
                return array(
                    'is_valid' => false,
                    'message' => 'Please complete all required fields for each item.',
                    'debug_info' => "Item $item_id has missing required fields."
                );
            }
            
            // Validate images - at least one image is required
            if (!isset($_FILES['items']['name'][$item_id]['images']['front']) || 
                empty($_FILES['items']['name'][$item_id]['images']['front'])) {
                
                return array(
                    'is_valid' => false,
                    'message' => 'Please upload at least a front view image for each item.',
                    'debug_info' => "Item $item_id is missing required image."
                );
            }
            
            // Validation passed for this item
            $valid_items[$item_id] = $item;
        }
        
        // All validation passed
        return array(
            'is_valid' => true,
            'data' => array(
                'contact' => array(
                    'name' => sanitize_text_field($_POST['name']),
                    'email' => sanitize_email($_POST['email']),
                    'phone' => sanitize_text_field($_POST['phone']),
                    'address' => sanitize_text_field($_POST['address']),
                    'city' => sanitize_text_field($_POST['city']),
                    'state' => sanitize_text_field($_POST['state']),
                    'zip' => sanitize_text_field($_POST['zip']),
                ),
                'items' => $valid_items
            ),
            'debug_info' => 'Validation successful.'
        );
    }
}
