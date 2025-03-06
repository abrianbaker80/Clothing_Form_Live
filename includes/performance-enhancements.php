<?php

/**
 * Performance Enhancements
 *
 * Optimizes the plugin for better performance.
 * 
 * @package Preowned_Clothing_Form
 * @since 2.8.1.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Optimize images during upload
 * 
 * @param string $file_path Path to the uploaded image
 * @return bool True if optimized, false otherwise
 */
function preowned_clothing_optimize_image($file_path)
{
    // Skip optimization if file doesn't exist
    if (!file_exists($file_path)) {
        return false;
    }

    // Check if optimization is enabled in settings
    if (get_option('preowned_clothing_optimize_images', '1') !== '1') {
        return false;
    }

    // Get image information
    $image_size = filesize($file_path);
    $image_info = getimagesize($file_path);

    // Get optimization settings from admin options
    $max_width = intval(get_option('preowned_clothing_image_max_width', 1500));
    $quality = intval(get_option('preowned_clothing_image_quality', 85));

    // Skip if not a supported image type or already small enough
    if (!$image_info || $image_info[0] <= $max_width) {
        return false;
    }

    // Create image resource based on file type
    $source_image = null;
    switch ($image_info[2]) {
        case IMAGETYPE_JPEG:
            $source_image = imagecreatefromjpeg($file_path);
            break;
        case IMAGETYPE_PNG:
            $source_image = imagecreatefrompng($file_path);
            break;
        case IMAGETYPE_GIF:
            $source_image = imagecreatefromgif($file_path);
            break;
        default:
            return false; // Unsupported image type
    }

    if (!$source_image) {
        return false;
    }

    // Calculate new dimensions
    $width = $image_info[0];
    $height = $image_info[1];
    $new_width = min($max_width, $width); // Don't upscale small images
    $new_height = $height * ($new_width / $width);

    // Create resized image
    $new_image = imagecreatetruecolor($new_width, $new_height);

    // Handle transparency for PNG
    if ($image_info[2] === IMAGETYPE_PNG) {
        imagealphablending($new_image, false);
        imagesavealpha($new_image, true);
        $transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
        imagefilledrectangle($new_image, 0, 0, $new_width, $new_height, $transparent);
    }

    // Resize image with better quality settings
    imagecopyresampled($new_image, $source_image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

    // Track original file size for logging
    $original_size = filesize($file_path);

    // Save optimized image
    $result = false;
    switch ($image_info[2]) {
        case IMAGETYPE_JPEG:
            $result = imagejpeg($new_image, $file_path, $quality);
            break;
        case IMAGETYPE_PNG:
            // PNG quality is 0-9 (0:no compression, 9:max compression)
            $png_quality = round(9 - (($quality / 100) * 9));
            $result = imagepng($new_image, $file_path, $png_quality);
            break;
        case IMAGETYPE_GIF:
            $result = imagegif($new_image, $file_path);
            break;
    }

    // Free memory
    imagedestroy($source_image);
    imagedestroy($new_image);

    // Log optimization results if debugging is enabled
    if ($result && defined('WP_DEBUG') && WP_DEBUG) {
        $new_size = filesize($file_path);
        $savings = $original_size - $new_size;
        $savings_percent = round(($savings / $original_size) * 100);

        error_log(sprintf(
            'Preowned Clothing Form - Image optimized: %s. Original size: %s. New size: %s. Savings: %s (%d%%)',
            basename($file_path),
            preowned_clothing_format_bytes($original_size),
            preowned_clothing_format_bytes($new_size),
            preowned_clothing_format_bytes($savings),
            $savings_percent
        ));
    }

    return $result;
}

/**
 * Helper to format bytes in a human-readable format
 * 
 * @param int $bytes Number of bytes
 * @return string Formatted string
 */
function preowned_clothing_format_bytes($bytes)
{
    $units = array('B', 'KB', 'MB', 'GB', 'TB');

    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);

    $bytes /= pow(1024, $pow);

    return round($bytes, 2) . ' ' . $units[$pow];
}

/**
 * Implement lazy loading for image previews
 */
function preowned_clothing_enqueue_lazy_load_script()
{
    // Only load on pages with the clothing form
    global $post;
    if (!is_a($post, 'WP_Post') || !has_shortcode($post->post_content, 'preowned_clothing_form')) {
        return;
    }

    // Get admin setting
    if (get_option('preowned_clothing_use_lazy_loading', '1') === '1') {
        wp_enqueue_script(
            'preowned-clothing-lazy-load',
            plugin_dir_url(dirname(__FILE__)) . 'assets/js/lazy-load.js',
            array('jquery'),
            PCF_VERSION,
            true
        );

        // Add lazy-loading CSS
        wp_add_inline_style('preowned-clothing-style', '
            .lazy-load {
                opacity: 0;
                transition: opacity 0.5s ease;
            }
            .lazy-load.loaded {
                opacity: 1;
            }
        ');
    }
}
add_action('wp_enqueue_scripts', 'preowned_clothing_enqueue_lazy_load_script', 30);

/**
 * Add asset versioning for better caching
 */
function preowned_clothing_versioned_assets($url, $path)
{
    if (strpos($url, 'clothing-form') === false) {
        return $url; // Only process our plugin assets
    }

    $file_path = ABSPATH . str_replace(site_url(), '', $url);

    if (file_exists($file_path)) {
        $version = filemtime($file_path);
        return add_query_arg('ver', $version, $url);
    }

    return $url;
}
add_filter('script_loader_src', 'preowned_clothing_versioned_assets', 10, 2);
add_filter('style_loader_src', 'preowned_clothing_versioned_assets', 10, 2);

/**
 * Add browser caching headers for plugin assets
 */
function preowned_clothing_add_caching_headers()
{
    // Only run if the current request is for our plugin assets
    if (!isset($_SERVER['REQUEST_URI']) || strpos($_SERVER['REQUEST_URI'], 'clothing-form') === false) {
        return;
    }

    $file_extension = pathinfo($_SERVER['REQUEST_URI'], PATHINFO_EXTENSION);
    $cacheable_extensions = array('css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'woff', 'woff2');

    if (in_array($file_extension, $cacheable_extensions)) {
        // Cache for 1 week
        $cache_time = 60 * 60 * 24 * 7;
        header('Cache-Control: public, max-age=' . $cache_time);
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $cache_time) . ' GMT');
    }
}
add_action('init', 'preowned_clothing_add_caching_headers', 1);

/**
 * Add image optimization settings to the admin settings page
 */
function preowned_clothing_add_image_settings($settings)
{
    $image_settings = array(
        'optimize_images' => array(
            'title' => 'Optimize Images',
            'description' => 'Automatically resize and optimize images during upload to improve performance.',
            'type' => 'checkbox',
            'default' => '1',
            'section' => 'performance',
        ),

        'image_max_width' => array(
            'title' => 'Maximum Image Width',
            'description' => 'Images larger than this width will be resized. Set to 0 to disable resizing.',
            'type' => 'number',
            'default' => '1500',
            'min' => '0',
            'max' => '3000',
            'section' => 'performance',
        ),

        'image_quality' => array(
            'title' => 'Image Quality',
            'description' => 'Quality setting for JPEG images (1-100). Higher values mean better quality but larger file sizes.',
            'type' => 'number',
            'default' => '85',
            'min' => '30',
            'max' => '100',
            'section' => 'performance',
        ),

        'use_lazy_loading' => array(
            'title' => 'Use Lazy Loading',
            'description' => 'Load images only when they scroll into view to improve page load speed.',
            'type' => 'checkbox',
            'default' => '1',
            'section' => 'performance',
        ),
    );

    return array_merge($settings, $image_settings);
}
add_filter('preowned_clothing_settings_fields', 'preowned_clothing_add_image_settings');

/**
 * Create lazy loading JavaScript file if it doesn't exist
 */
function preowned_clothing_create_lazy_load_script()
{
    $file_path = plugin_dir_path(dirname(__FILE__)) . 'assets/js/lazy-load.js';

    // Only create if the file doesn't exist
    if (!file_exists($file_path)) {
        $js_content = "/**
 * Lazy Loading Script
 * 
 * Loads images only when they scroll into view
 */
(function($) {
    'use strict';
    
    // Initialize once DOM is ready
    $(function() {
        // Add lazy load class to all image elements
        $('.image-preview').addClass('lazy-load');
        
        // Initialize lazy loading
        initLazyLoading();
    });
    
    function initLazyLoading() {
        // Load visible images immediately
        loadVisibleImages();
        
        // Listen for scroll, resize and orientationchange events
        $(window).on('scroll.lazyload resize.lazyload orientationchange.lazyload', throttle(loadVisibleImages, 200));
    }
    
    function loadVisibleImages() {
        $('.lazy-load').each(function() {
            var \$img = $(this);
            
            // Skip already loaded images
            if (\$img.hasClass('loaded')) {
                return;
            }
            
            if (isElementInViewport(\$img[0])) {
                // Load image by setting the src attribute
                if (\$img.attr('data-src')) {
                    \$img.attr('src', \$img.attr('data-src')).removeAttr('data-src');
                }
                
                // Mark as loaded when image is loaded
                \$img.on('load', function() {
                    \$img.addClass('loaded');
                });
            }
        });
    }
    
    function isElementInViewport(el) {
        var rect = el.getBoundingClientRect();
        
        return (
            rect.top <= (window.innerHeight || document.documentElement.clientHeight) + 100 && // 100px offset for pre-loading
            rect.bottom >= 0 &&
            rect.left <= (window.innerWidth || document.documentElement.clientWidth) + 100 &&
            rect.right >= 0
        );
    }
    
    // Throttle function to limit function calls
    function throttle(func, limit) {
        var inThrottle;
        return function() {
            var args = arguments;
            var context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(function() {
                    inThrottle = false;
                }, limit);
            }
        };
    }
    
})(jQuery);";

        // Create directory if it doesn't exist
        $js_dir = dirname($file_path);
        if (!file_exists($js_dir)) {
            wp_mkdir_p($js_dir);
        }

        // Write the file
        file_put_contents($file_path, $js_content);
    }
}
add_action('admin_init', 'preowned_clothing_create_lazy_load_script');

/**
 * Hook image optimization into the upload process
 */
function preowned_clothing_process_uploaded_image($file_info)
{
    // Only process if it's an image
    if (isset($file_info['file']) && isset($file_info['type']) && strpos($file_info['type'], 'image/') === 0) {
        preowned_clothing_optimize_image($file_info['file']);
    }

    return $file_info;
}
add_filter('wp_handle_upload', 'preowned_clothing_process_uploaded_image');

/**
 * Initialize performance optimization hooks
 */
function preowned_clothing_init_performance()
{
    // Apply image optimization to custom form uploads
    add_filter('preowned_clothing_uploaded_file', function ($file_path, $file_type) {
        if (strpos($file_type, 'image/') === 0) {
            preowned_clothing_optimize_image($file_path);
        }
        return $file_path;
    }, 10, 2);
}
add_action('init', 'preowned_clothing_init_performance', 5);

/**
 * Performance Enhancement Functions
 *
 * @package PreownedClothingForm
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Performance optimizations for the plugin
 */

// Check if the function already exists before declaring it
if (!function_exists('preowned_clothing_optimize_image')) {
    /**
     * Optimize images for better performance
     * 
     * @param string $image_path Path to the image file
     * @param int    $quality    Quality level (1-100)
     * @return bool Whether optimization was successful
     */
    function preowned_clothing_optimize_image($image_path, $quality = 85)
    {
        // Function implementation moved to utilities.php
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Performance Enhancement: Image optimization requested but function exists in utilities.php');
        }
        return false;
    }
}

/**
 * Cache form data for better performance
 * 
 * @param string $key   Cache key
 * @param mixed  $data  Data to cache
 * @param int    $expiry Expiration time in seconds
 */
function preowned_clothing_cache_form_data($key, $data, $expiry = 3600)
{
    set_transient('pcf_cache_' . $key, $data, $expiry);
}

/**
 * Get cached form data
 * 
 * @param string $key Cache key
 * @return mixed|false The cached data or false if not found
 */
function preowned_clothing_get_cached_form_data($key)
{
    return get_transient('pcf_cache_' . $key);
}

/**
 * Minify HTML output
 * 
 * @param string $html HTML content
 * @return string Minified HTML
 */
function preowned_clothing_minify_html($html)
{
    // Simple minification - remove unnecessary whitespace
    $search = array(
        '/\>[^\S ]+/s',  // Remove whitespace after tags
        '/[^\S ]+\</s',  // Remove whitespace before tags
        '/(\s)+/s',      // Reduce multiple whitespace to single space
    );

    $replace = array(
        '>',
        '<',
        '\\1',
    );

    return preg_replace($search, $replace, $html);
}
