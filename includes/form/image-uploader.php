<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Image uploader class
 * Handles secure file uploads with validation and optimization
 */
class PCF_Image_Uploader {
    // Track uploaded files for potential cleanup
    private $uploaded_files = array();
    
    // Configuration
    private $max_size_bytes;
    private $allowed_types = array('image/jpeg', 'image/png', 'image/gif', 'image/webp');
    private $allowed_extensions = array('jpg', 'jpeg', 'png', 'gif', 'webp');
    
    /**
     * Constructor - set up configuration
     */
    public function __construct() {
        // Get max upload size from settings or default to 2MB
        $max_size_mb = intval(get_option('preowned_clothing_max_image_size', 2));
        $this->max_size_bytes = $max_size_mb * 1024 * 1024;
        
        // Check PHP upload limits
        $php_max_upload = min(
            wp_convert_hr_to_bytes(ini_get('upload_max_filesize')),
            wp_convert_hr_to_bytes(ini_get('post_max_size')),
            wp_max_upload_size()
        );
        
        // Use the smaller of the two limits
        $this->max_size_bytes = min($this->max_size_bytes, $php_max_upload);
    }
    
    /**
     * Process all images for a submission
     * 
     * @param int $submission_id The submission ID
     * @param array $items The items data
     * @return array Result with status and image URLs
     */
    public function process_submission_images($submission_id, $items) {
        $image_urls = array();
        $has_errors = false;
        
        // Create submission directory
        $upload_dir = $this->create_submission_directory($submission_id);
        if (!$upload_dir) {
            return array(
                'status' => false,
                'error' => 'Failed to create upload directory'
            );
        }
        
        // Process each item's images
        foreach ($items as $item_id => $item_data) {
            $image_urls[$item_id] = array(
                'front' => '',
                'back' => '',
                'brand_tag' => '',
                'material_tag' => '',
                'detail' => ''
            );
            
            // Skip if no files uploaded for this item
            if (!isset($_FILES['items']['name'][$item_id]['images'])) {
                continue;
            }
            
            // Process each image type
            foreach ($_FILES['items']['name'][$item_id]['images'] as $image_type => $filename) {
                if (empty($filename)) {
                    continue;
                }
                
                // Create file array for this specific image
                $file = array(
                    'name' => $_FILES['items']['name'][$item_id]['images'][$image_type],
                    'type' => $_FILES['items']['type'][$item_id]['images'][$image_type],
                    'tmp_name' => $_FILES['items']['tmp_name'][$item_id]['images'][$image_type],
                    'error' => $_FILES['items']['error'][$item_id]['images'][$image_type],
                    'size' => $_FILES['items']['size'][$item_id]['images'][$image_type]
                );
                
                // Upload the image
                $upload_result = $this->upload_image($file, $upload_dir, $image_type);
                
                if ($upload_result['status']) {
                    $image_urls[$item_id][$image_type] = $upload_result['url'];
                } else {
                    $has_errors = true;
                }
            }
        }
        
        return array(
            'status' => !$has_errors,
            'image_urls' => $image_urls,
            'uploaded_files' => $this->uploaded_files
        );
    }
    
    /**
     * Create a secure directory for uploads
     * 
     * @param int $submission_id The submission ID
     * @return string|bool Directory path or false on failure
     */
    private function create_submission_directory($submission_id) {
        $upload_dir = wp_upload_dir();
        $relative_path = '/preowned-clothing/' . $submission_id . '/';
        $target_dir = $upload_dir['basedir'] . $relative_path;
        
        // Create directory if it doesn't exist
        if (!file_exists($target_dir)) {
            if (!wp_mkdir_p($target_dir)) {
                error_log("Failed to create upload directory: $target_dir");
                return false;
            }
            
            // Add security files to prevent directory browsing
            file_put_contents($target_dir . 'index.php', '<?php // Silence is golden');
            file_put_contents($target_dir . '.htaccess', "Options -Indexes\nDeny from all");
            
            // Set directory permissions
            chmod($target_dir, 0755);
        }
        
        return $target_dir;
    }
    
    /**
     * Upload and process a single image
     * 
     * @param array $file The file data array
     * @param string $target_dir The target directory
     * @param string $image_type The type of image (front, back, etc.)
     * @return array Result with status and URL or error
     */
    public function upload_image($file, $target_dir, $image_type = '') {
        // Basic validation
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return array(
                'status' => false,
                'error' => $this->get_upload_error_message($file['error'])
            );
        }
        
        // Verify this is really an uploaded file
        if (!is_uploaded_file($file['tmp_name'])) {
            return array(
                'status' => false,
                'error' => 'Security check failed: Not a valid uploaded file'
            );
        }
        
        // Validate file size
        if ($file['size'] > $this->max_size_bytes) {
            return array(
                'status' => false,
                'error' => 'File too large. Maximum size is ' . size_format($this->max_size_bytes)
            );
        }
        
        // Validate mime type using fileinfo
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mime_type, $this->allowed_types)) {
                return array(
                    'status' => false,
                    'error' => 'Invalid file type: ' . $mime_type
                );
            }
        }
        
        // Validate file extension
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($file_extension, $this->allowed_extensions)) {
            return array(
                'status' => false,
                'error' => 'Invalid file extension: ' . $file_extension
            );
        }
        
        // Generate a cryptographically secure filename
        $prefix = empty($image_type) ? '' : $image_type . '_';
        $secure_filename = $prefix . bin2hex(random_bytes(8)) . '_' . time() . '.' . $file_extension;
        $target_path = rtrim($target_dir, '/') . '/' . $secure_filename;
        
        // Move the uploaded file
        if (!move_uploaded_file($file['tmp_name'], $target_path)) {
            return array(
                'status' => false,
                'error' => 'Failed to move uploaded file'
            );
        }
        
        // Keep track of uploaded file
        $this->uploaded_files[] = $target_path;
        
        // Set secure file permissions
        chmod($target_path, 0644);
        
        // Optimize the image if possible
        $this->optimize_image($target_path);
        
        // Generate URL
        $upload_dir = wp_upload_dir();
        $relative_path = str_replace($upload_dir['basedir'], '', $target_dir);
        $file_url = $upload_dir['baseurl'] . $relative_path . $secure_filename;
        
        return array(
            'status' => true,
            'path' => $target_path,
            'url' => $file_url
        );
    }
    
    /**
     * Optimize an uploaded image
     * 
     * @param string $file_path The path to the image file
     */
    private function optimize_image($file_path) {
        // Use WordPress image editor if available
        if (function_exists('wp_get_image_editor')) {
            $editor = wp_get_image_editor($file_path);
            
            if (!is_wp_error($editor)) {
                // Resize if larger than 2000px on any dimension
                $current_size = $editor->get_size();
                $max_dimension = 2000;
                
                if ($current_size['width'] > $max_dimension || $current_size['height'] > $max_dimension) {
                    $editor->resize($max_dimension, $max_dimension, false);
                }
                
                // Set quality
                $editor->set_quality(90);
                
                // Save optimized image
                $editor->save($file_path);
            }
        }
        
        // Additional optimization if available
        if (function_exists('preowned_clothing_optimize_image')) {
            preowned_clothing_optimize_image($file_path);
        }
    }
    
    /**
     * Clean up uploaded files in case of failure
     */
    public function cleanup_files() {
        foreach ($this->uploaded_files as $file_path) {
            if (file_exists($file_path)) {
                @unlink($file_path);
            }
        }
    }
    
    /**
     * Get human-readable upload error message
     * 
     * @param int $error_code The PHP upload error code
     * @return string Human-readable error message
     */
    private function get_upload_error_message($error_code) {
        switch ($error_code) {
            case UPLOAD_ERR_INI_SIZE:
                return 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
            case UPLOAD_ERR_FORM_SIZE:
                return 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form';
            case UPLOAD_ERR_PARTIAL:
                return 'The uploaded file was only partially uploaded';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing a temporary folder';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk';
            case UPLOAD_ERR_EXTENSION:
                return 'A PHP extension stopped the file upload';
            default:
                return 'Unknown upload error';
        }
    }
}
