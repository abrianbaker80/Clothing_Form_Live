<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Image Uploader Class
 * Handles processing of uploaded images for clothing form
 */
class PCF_Image_Uploader {
    
    /**
     * Process uploaded images for a submission
     */
    public function process_submission_images($submission_id, $item_data) {
        // Create result array
        $result = array(
            'status' => true,
            'message' => '',
            'image_urls' => array(),
            'uploaded_files' => array()
        );
        
        // Check if there are any images
        if (empty($_FILES['item_images'])) {
            // No images were uploaded - check if required
            $required_images = get_option('preowned_clothing_required_images', ['front', 'back']);
            if (!empty($required_images)) {
                $result['status'] = false;
                $result['message'] = 'Required images are missing. Please upload at least front and back photos.';
                return $result;
            }
            return $result;
        }
        
        // Get WordPress upload directory
        $upload_dir = wp_upload_dir();
        if (!empty($upload_dir['error'])) {
            $result['status'] = false;
            $result['message'] = 'WordPress upload directory error: ' . $upload_dir['error'];
            return $result;
        }
        
        $base_dir = $upload_dir['basedir'] . '/preowned-clothing/' . $submission_id;
        $base_url = $upload_dir['baseurl'] . '/preowned-clothing/' . $submission_id;
        
        // Create directory if it doesn't exist
        if (!file_exists($base_dir)) {
            if (!wp_mkdir_p($base_dir)) {
                $result['status'] = false;
                $result['message'] = 'Failed to create upload directory. Please check permissions.';
                return $result;
            }
        }
        
        // Process each uploaded image
        $files = $_FILES['item_images'];
        $count = count($files['name']);
        $max_size = intval(get_option('preowned_clothing_max_image_size', 2)) * 1024 * 1024; // Convert MB to bytes
        $allowed_types = array('image/jpeg', 'image/png', 'image/gif', 'image/webp');
        
        for ($i = 0; $i < $count; $i++) {
            // Skip empty uploads
            if (empty($files['name'][$i])) {
                continue;
            }
            
            // Check for upload errors
            if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                $error_message = $this->get_upload_error_message($files['error'][$i]);
                $result['status'] = false;
                $result['message'] = 'Upload error: ' . $error_message;
                $this->cleanup_files($result['uploaded_files']);
                return $result;
            }
            
            // Get file information
            $temp_file = $files['tmp_name'][$i];
            $file_name = sanitize_file_name($files['name'][$i]);
            $file_type = $files['type'][$i];
            $file_size = $files['size'][$i];
            
            // Validate file size
            if ($file_size > $max_size) {
                $max_size_mb = $max_size / (1024 * 1024);
                $result['status'] = false;
                $result['message'] = "File size exceeds maximum allowed size of {$max_size_mb}MB.";
                $this->cleanup_files($result['uploaded_files']);
                return $result;
            }
            
            // Validate file type
            if (!in_array($file_type, $allowed_types)) {
                $result['status'] = false;
                $result['message'] = 'Invalid file type. Only JPEG, PNG, GIF and WebP images are allowed.';
                $this->cleanup_files($result['uploaded_files']);
                return $result;
            }
            
            // Additional security check for file type using exif_imagetype if available
            if (function_exists('exif_imagetype')) {
                $image_type = @exif_imagetype($temp_file);
                if (!$image_type || !in_array($image_type, array(IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP))) {
                    $result['status'] = false;
                    $result['message'] = 'Invalid image file. Only JPEG, PNG, GIF and WebP images are allowed.';
                    $this->cleanup_files($result['uploaded_files']);
                    return $result;
                }
            }
            
            // Create unique filename
            $unique_filename = wp_unique_filename($base_dir, $file_name);
            $destination = $base_dir . '/' . $unique_filename;
            
            // Optimize image before saving (RESTORED FUNCTIONALITY)
            $optimized_file = $this->optimize_image($temp_file, $file_type);
            if ($optimized_file) {
                $temp_file = $optimized_file;
            }
            
            // Move the uploaded file
            if (!move_uploaded_file($temp_file, $destination)) {
                $result['status'] = false;
                $result['message'] = 'Failed to move uploaded file. Please check permissions.';
                $this->cleanup_files($result['uploaded_files']);
                
                // Clean up optimized temp file if it exists
                if ($optimized_file && $optimized_file != $files['tmp_name'][$i]) {
                    @unlink($optimized_file);
                }
                
                return $result;
            }
            
            // Clean up optimized temp file if it exists and is different from original
            if ($optimized_file && $optimized_file != $files['tmp_name'][$i]) {
                @unlink($optimized_file);
            }
            
            // Add to uploaded files list
            $result['uploaded_files'][] = $destination;
            
            // Add URL to result
            $image_url = $base_url . '/' . $unique_filename;
            $result['image_urls'][] = $image_url;
        }
        
        // Check if we have the required number of images
        $required_images_count = count(get_option('preowned_clothing_required_images', ['front', 'back']));
        if (count($result['image_urls']) < $required_images_count) {
            $result['status'] = false;
            $result['message'] = "Please upload at least {$required_images_count} images including front and back views.";
            $this->cleanup_files($result['uploaded_files']);
            return $result;
        }
        
        return $result;
    }
    
    /**
     * Optimize image to reduce file size
     * 
     * @param string $source_path Path to the source image file
     * @param string $mime_type MIME type of the image
     * @return string|false Path to optimized image or false on failure
     */
    private function optimize_image($source_path, $mime_type) {
        // Skip optimization if GD library is not available
        if (!extension_loaded('gd') || !function_exists('imagecreatefrompng')) {
            return false;
        }
        
        // Get optimization settings
        $max_width = intval(get_option('preowned_clothing_max_image_width', 1200));
        $max_height = intval(get_option('preowned_clothing_max_image_height', 1200));
        $quality = intval(get_option('preowned_clothing_image_quality', 80));
        
        // Ensure quality is within valid range (0-100)
        $quality = max(0, min(100, $quality));
        
        // Create image resource based on mime type
        $image = false;
        
        switch ($mime_type) {
            case 'image/jpeg':
                $image = @imagecreatefromjpeg($source_path);
                break;
            case 'image/png':
                $image = @imagecreatefrompng($source_path);
                break;
            case 'image/gif':
                $image = @imagecreatefromgif($source_path);
                break;
            case 'image/webp':
                if (function_exists('imagecreatefromwebp')) {
                    $image = @imagecreatefromwebp($source_path);
                }
                break;
        }
        
        // If image creation failed, return false
        if (!$image) {
            return false;
        }
        
        // Get original image dimensions
        $orig_width = imagesx($image);
        $orig_height = imagesy($image);
        
        // Check if resizing is needed
        if ($orig_width <= $max_width && $orig_height <= $max_height) {
            // No resizing needed, but we might still compress JPEGs for quality
            if ($mime_type == 'image/jpeg') {
                $temp_file = tempnam(sys_get_temp_dir(), 'pcf_img_');
                if (imagejpeg($image, $temp_file, $quality)) {
                    imagedestroy($image);
                    return $temp_file;
                }
            }
            
            // For other formats, or if JPEG compression failed, return original
            imagedestroy($image);
            return false;
        }
        
        // Calculate new dimensions while maintaining aspect ratio
        $ratio = min($max_width / $orig_width, $max_height / $orig_height);
        $new_width = round($orig_width * $ratio);
        $new_height = round($orig_height * $ratio);
        
        // Create a new true color image
        $new_image = imagecreatetruecolor($new_width, $new_height);
        
        // Handle transparency for PNG and GIF
        if ($mime_type == 'image/png' || $mime_type == 'image/gif') {
            // Enable alpha blending
            imagealphablending($new_image, false);
            imagesavealpha($new_image, true);
            // Fill with transparent background
            $transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
            imagefilledrectangle($new_image, 0, 0, $new_width, $new_height, $transparent);
        }
        
        // Resample the original image to the new dimensions
        imagecopyresampled(
            $new_image,
            $image,
            0, 0, 0, 0,
            $new_width, $new_height,
            $orig_width, $orig_height
        );
        
        // Create temp file
        $temp_file = tempnam(sys_get_temp_dir(), 'pcf_img_');
        
        // Save optimized image based on mime type
        $success = false;
        
        switch ($mime_type) {
            case 'image/jpeg':
                $success = imagejpeg($new_image, $temp_file, $quality);
                break;
            case 'image/png':
                // PNG quality is 0-9, not 0-100
                $png_quality = 9 - round(($quality / 100) * 9);
                $success = imagepng($new_image, $temp_file, $png_quality);
                break;
            case 'image/gif':
                $success = imagegif($new_image, $temp_file);
                break;
            case 'image/webp':
                if (function_exists('imagewebp')) {
                    $success = imagewebp($new_image, $temp_file, $quality);
                }
                break;
        }
        
        // Free up memory
        imagedestroy($image);
        imagedestroy($new_image);
        
        // Return optimized image path or false on failure
        if ($success) {
            return $temp_file;
        } else {
            @unlink($temp_file); // Delete the temp file on failure
            return false;
        }
    }
    
    /**
     * Get upload error message
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
    
    /**
     * Clean up uploaded files
     * Used in case of errors to remove any uploaded files
     */
    public function cleanup_files($file_paths) {
        foreach ($file_paths as $path) {
            if (file_exists($path)) {
                @unlink($path);
            }
        }
    }
}
