<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Submission processor class
 * Handles database operations and transaction management
 */
class PCF_Submission_Processor {
    // Database tables
    private $submissions_table;
    private $items_table;
    
    // For cleanup
    private $uploader;
    
    /**
     * Constructor - set up database tables
     */
    public function __construct() {
        global $wpdb;
        $this->submissions_table = $wpdb->prefix . 'preowned_clothing_submissions';
        $this->items_table = $wpdb->prefix . 'preowned_clothing_items';
    }
    
    /**
     * Process a submission
     * 
     * @param array $data The validated form data
     * @return array Result with status and message
     */
    public function process($data) {
        global $wpdb;
        
        // Create an image uploader
        $this->uploader = new PCF_Image_Uploader();
        
        // Result defaults
        $result = array(
            'status' => 'error',
            'message' => 'An error occurred processing your submission.',
            'debug_info' => ''
        );
        
        try {
            // Verify database tables exist
            $this->ensure_tables_exist();
            
            // Start transaction
            $wpdb->query('START TRANSACTION');
            
            // Insert submission record
            $submission_id = $this->insert_submission($data);
            if (!$submission_id) {
                throw new Exception('Failed to create submission record: ' . $wpdb->last_error);
            }
            
            // Process images
            $image_result = $this->uploader->process_submission_images($submission_id, $data['items']);
            if (!$image_result['status']) {
                throw new Exception('Error uploading one or more images');
            }
            
            // Insert items
            $this->insert_items($submission_id, $data['items'], $image_result['image_urls']);
            
            // Commit the transaction
            $wpdb->query('COMMIT');
            
            // Send notification emails
            $this->send_notifications($submission_id, $data);
            
            // Set success result
            $result = array(
                'status' => 'success',
                'message' => 'Your clothing items have been successfully submitted! Thank you. Someone from our team will be reaching out to you within 24-48 hours.',
                'redirect' => add_query_arg(array('success' => '1', 'pcf_t' => time()), remove_query_arg('error'))
            );
            
        } catch (Exception $e) {
            // Rollback transaction
            $wpdb->query('ROLLBACK');
            
            // Clean up any uploaded files
            if (isset($image_result) && isset($image_result['uploaded_files'])) {
                $this->uploader->cleanup_files();
            }
            
            // Log the error
            error_log('Preowned Clothing Form submission error: ' . $e->getMessage());
            
            // Set error result
            $result = array(
                'status' => 'error',
                'message' => 'There was a problem processing your submission. Please try again.',
                'debug_info' => 'Exception: ' . $e->getMessage()
            );
        }
        
        return $result;
    }
    
    /**
     * Ensure database tables exist before insertion
     * 
     * @throws Exception If tables don't exist and can't be created
     */
    private function ensure_tables_exist() {
        global $wpdb;
        
        // Check if tables exist
        $submissions_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $this->submissions_table)) === $this->submissions_table;
        $items_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $this->items_table)) === $this->items_table;
        
        // If either table is missing, try to create them
        if (!$submissions_exists || !$items_exists) {
            if (function_exists('preowned_clothing_create_submission_table')) {
                $created = preowned_clothing_create_submission_table();
                if (!$created) {
                    throw new Exception('Database tables missing and could not be created: ' . $wpdb->last_error);
                }
            } else {
                throw new Exception('Database tables missing and creation function not available');
            }
        }
    }
    
    /**
     * Insert submission record
     * 
     * @param array $data The form data
     * @return int|false The submission ID or false on failure
     */
    private function insert_submission($data) {
        global $wpdb;
        
        $submission_data = array(
            'name' => $data['name'],
            'email' => $data['email'],
            'submission_date' => current_time('mysql'),
            'status' => 'pending'
        );
        
        $result = $wpdb->insert($this->submissions_table, $submission_data);
        
        return $result ? $wpdb->insert_id : false;
    }
    
    /**
     * Insert item records
     * 
     * @param int $submission_id The submission ID
     * @param array $items The items data
     * @param array $image_urls The processed image URLs
     * @throws Exception If any item insertion fails
     */
    private function insert_items($submission_id, $items, $image_urls) {
        global $wpdb;
        
        foreach ($items as $item_id => $item_data) {
            // Skip if description is empty
            if (empty($item_data['description'])) {
                continue;
            }
            
            // Prepare item data
            $insert_data = array(
                'submission_id' => $submission_id,
                'description' => $item_data['description'],
                'size' => isset($item_data['size']) ? $item_data['size'] : ''
            );
            
            // Add category data
            foreach ($item_data as $field => $value) {
                if (strpos($field, 'category_level_') === 0) {
                    $insert_data[$field] = $value;
                }
            }
            
            // Add image URLs
            if (isset($image_urls[$item_id])) {
                foreach ($image_urls[$item_id] as $type => $url) {
                    if (!empty($url)) {
                        $insert_data['image_' . $type] = $url;
                    }
                }
            }
            
            // Insert the item
            $result = $wpdb->insert($this->items_table, $insert_data);
            
            if ($result === false) {
                throw new Exception('Failed to insert item record: ' . $wpdb->last_error);
            }
        }
    }
    
    /**
     * Send notification emails
     * 
     * @param int $submission_id The submission ID
     * @param array $data The submission data
     */
    private function send_notifications($submission_id, $data) {
        // Trigger email notifications if function exists
        if (!function_exists('preowned_clothing_send_notification_emails')) {
            function preowned_clothing_send_notification_emails($submission_id, $data) {
                // Define the function logic here
                // For example, send an email using wp_mail()
                $to = $data['email'];
                $subject = 'Submission Received';
                $message = 'Thank you for your submission. Your submission ID is ' . $submission_id;
                wp_mail($to, $subject, $message);
            }
        }
        preowned_clothing_send_notification_emails($submission_id, $data);
        
        // Also fire an action for custom notification handling
        do_action('preowned_clothing_after_submission', $submission_id, $data);
    }
}
