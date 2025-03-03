<?php
/**
 * Admin Image Optimization Test
 *
 * Test script for the image optimization features
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Add image optimization test page
 */
function preowned_clothing_add_optimization_test_page() {
    add_submenu_page(
        'tools.php',
        'Image Optimization Test',
        'Image Optimization Test',
        'manage_options',
        'preowned-clothing-optimization-test',
        'preowned_clothing_optimization_test_page'
    );
}
add_action('admin_menu', 'preowned_clothing_add_optimization_test_page');

/**
 * Display the image optimization test page
 */
function preowned_clothing_optimization_test_page() {
    // Process form submission
    $optimization_result = null;
    $file_path = '';
    $original_size = 0;
    $new_size = 0;
    
    if (isset($_POST['test_optimization']) && check_admin_referer('preowned_clothing_test_optimization')) {
        if (!empty($_FILES['test_image']['name'])) {
            // Save uploaded file to temp directory
            $upload_dir = wp_upload_dir();
            $temp_dir = $upload_dir['basedir'] . '/clothing-form-temp';
            
            // Create directory if it doesn't exist
            if (!file_exists($temp_dir)) {
                wp_mkdir_p($temp_dir);
            }
            
            $file_path = $temp_dir . '/' . $_FILES['test_image']['name'];
            
            // Move uploaded file
            if (move_uploaded_file($_FILES['test_image']['tmp_name'], $file_path)) {
                // Get original file size
                $original_size = filesize($file_path);
                
                // Optimize the image
                $optimization_result = preowned_clothing_optimize_image($file_path);
                
                // Get new file size
                $new_size = filesize($file_path);
            }
        }
    }
    
    ?>
    <div class="wrap">
        <h1>Image Optimization Test</h1>
        
        <div class="card">
            <h2>Test Image Optimization Settings</h2>
            <p>Upload an image to test the current optimization settings.</p>
            
            <form method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('preowned_clothing_test_optimization'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Upload Image</th>
                        <td><input type="file" name="test_image" accept="image/*" required></td>
                    </tr>
                    <tr>
                        <th scope="row">Current Settings</th>
                        <td>
                            <p>
                                <strong>Optimization enabled:</strong> <?php echo get_option('preowned_clothing_optimize_images', '1') === '1' ? 'Yes' : 'No'; ?><br>
                                <strong>Maximum width:</strong> <?php echo esc_html(get_option('preowned_clothing_image_max_width', '1500')); ?> pixels<br>
                                <strong>Quality level:</strong> <?php echo esc_html(get_option('preowned_clothing_image_quality', '85')); ?>%
                            </p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="test_optimization" class="button button-primary" value="Test Optimization">
                </p>
            </form>
        </div>
        
        <?php if (!is_null($optimization_result)): ?>
            <div class="card">
                <h2>Test Results</h2>
                
                <?php if ($optimization_result): ?>
                    <div class="notice notice-success inline">
                        <p>Image optimized successfully!</p>
                    </div>
                    
                    <?php
                    $savings = $original_size - $new_size;
                    $savings_percent = $original_size > 0 ? round(($savings / $original_size) * 100) : 0;
                    ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">Original Size</th>
                            <td><?php echo esc_html(preowned_clothing_format_bytes($original_size)); ?></td>
                        </tr>
                        <tr>
                            <th scope="row">New Size</th>
                            <td><?php echo esc_html(preowned_clothing_format_bytes($new_size)); ?></td>
                        </tr>
                        <tr>
                            <th scope="row">Size Reduction</th>
                            <td>
                                <?php echo esc_html(preowned_clothing_format_bytes($savings)); ?> 
                                (<?php echo esc_html($savings_percent); ?>%)
                            </td>
                        </tr>
                    </table>
                    
                    <?php if (file_exists($file_path)): ?>
                        <div style="max-width: 600px; margin-top: 20px; border: 1px solid #ddd; padding: 10px;">
                            <h3>Optimized Image Preview</h3>
                            <img src="<?php echo esc_url(str_replace(ABSPATH, site_url('/'), $file_path) . '?t=' . time()); ?>" style="max-width: 100%; height: auto;">
                        </div>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <div class="notice notice-error inline">
                        <p>Image optimization failed. The image may already be optimized or the optimization feature may be disabled.</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <h2>Documentation</h2>
            <p>The image optimization feature automatically resizes and compresses images to improve page load speed.</p>
            
            <h3>How It Works</h3>
            <ol>
                <li>When a user uploads an image, the system checks if it's larger than the maximum width setting.</li>
                <li>If it is, the image is resized to maintain its aspect ratio while reducing its dimensions.</li>
                <li>The image is then compressed according to the quality setting.</li>
                <li>The result is a smaller file that loads faster while maintaining acceptable quality.</li>
            </ol>
            
            <h3>Recommendations</h3>
            <ul>
                <li>For clothing images, a maximum width of 1200-1500 pixels is usually sufficient.</li>
                <li>A quality setting of 80-90% provides a good balance between size and image clarity.</li>
                <li>Enable lazy loading to further improve page load speed.</li>
            </ul>
        </div>
    </div>
    <?php
}
