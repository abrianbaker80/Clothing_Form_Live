<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Session Manager for Preowned Clothing Form
 * Handles all session operations in a secure and WordPress-friendly way
 */

class PCF_Session_Manager {
    /**
     * Initialize the session handling
     */
    public static function initialize() {
        // Only start session if not already started
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
        
        // Initialize feedback storage if not exists
        if (!isset($_SESSION['pcf_feedback'])) {
            $_SESSION['pcf_feedback'] = [
                'status' => '',
                'message' => ''
            ];
        }
    }
    
    /**
     * Set feedback message in session
     * 
     * @param string $status Status of the feedback (success, error, etc.)
     * @param string $message Feedback message to display
     */
    public static function set_feedback($status, $message) {
        // Start session if not started
        self::initialize();
        
        $_SESSION['pcf_feedback'] = [
            'status' => sanitize_text_field($status),
            'message' => sanitize_text_field($message)
        ];
    }
    
    /**
     * Get feedback from session
     * 
     * @return array Feedback array with status and message
     */
    public static function get_feedback() {
        // Start session if not started
        self::initialize();
        
        // Return empty array with default values if not set
        if (!isset($_SESSION['pcf_feedback'])) {
            return [
                'status' => '',
                'message' => ''
            ];
        }
        
        return $_SESSION['pcf_feedback'];
    }
    
    /**
     * Clear feedback from session
     */
    public static function clear_feedback() {
        // Start session if not started
        self::initialize();
        
        $_SESSION['pcf_feedback'] = [
            'status' => '',
            'message' => ''
        ];
    }
    
    /**
     * Store form data in session
     * 
     * @param array $data Form data to store
     */
    public static function store_form_data($data) {
        // Start session if not started
        self::initialize();
        
        // Sanitize all input data
        $sanitized_data = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitized_data[$key] = array_map('sanitize_text_field', $value);
            } else {
                $sanitized_data[$key] = sanitize_text_field($value);
            }
        }
        
        $_SESSION['pcf_form_data'] = $sanitized_data;
    }
    
    /**
     * Get stored form data
     * 
     * @return array Form data from session
     */
    public static function get_form_data() {
        // Start session if not started
        self::initialize();
        
        return isset($_SESSION['pcf_form_data']) ? $_SESSION['pcf_form_data'] : [];
    }
    
    /**
     * Clear form data from session
     */
    public static function clear_form_data() {
        // Start session if not started
        self::initialize();
        
        // Unset the form data
        if (isset($_SESSION['pcf_form_data'])) {
            unset($_SESSION['pcf_form_data']);
        }
    }
}
