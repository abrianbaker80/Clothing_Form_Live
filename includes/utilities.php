<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Utility functions for the plugin
 */

/**
 * Check if a file exists in the plugin directory
 *
 * @param string $relative_path The relative path from the plugin directory
 * @return bool Whether the file exists
 */
function pcf_file_exists($relative_path) {
    $file_path = plugin_dir_path(dirname(__FILE__)) . $relative_path;
    return file_exists($file_path);
}

/**
 * Format a file size in bytes to a human-readable format
 * 
 * @param int $bytes The size in bytes
 * @param int $precision The number of decimal places
 * @return string The formatted size
 */
function preowned_clothing_format_bytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= (1 << (10 * $pow));
    
    return round($bytes, $precision) . ' ' . $units[$pow];
}

/**
 * Optimize an image file
 * 
 * @param string $file_path Path to the image file
 * @return bool Whether optimization was successful
 */
function preowned_clothing_optimize_image($file_path) {
    // Use WordPress image editor if available
    if (function_exists('wp_get_image_editor')) {
        $editor = wp_get_image_editor($file_path);
        
        if (!is_wp_error($editor)) {
            // Resize if larger than max width
            $max_width = intval(get_option('preowned_clothing_image_max_width', 1500));
            $current_size = $editor->get_size();
            
            if ($current_size['width'] > $max_width) {
                $editor->resize($max_width, null, false);
            }
            
            // Set quality
            $quality = intval(get_option('preowned_clothing_image_quality', 85));
            $editor->set_quality($quality);
            
            // Save the optimized image
            $result = $editor->save($file_path);
            
            return !is_wp_error($result);
        }
    }
    
    return false;
}
