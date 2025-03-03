<?php
/**
 * GitHub Updater Advanced Debug Template
 * 
 * @package PreownedClothingForm
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get plugin file
$plugin_file = isset($plugin_file) ? $plugin_file : dirname(dirname(dirname(dirname(__FILE__)))) . '/preowned-clothing-form.php';
?>
<div class="github-advanced-debug">
    <h2>Advanced Debugging</h2>
    
    <div class="debug-section">
        <h3>Plugin Data</h3>
        <?php
        if (file_exists($plugin_file)) {
            $plugin_data = get_plugin_data($plugin_file);
            echo '<table class="widefat striped">';
            foreach ($plugin_data as $key => $value) {
                if (is_string($value)) {
                    echo '<tr><th>' . esc_html($key) . '</th><td>' . esc_html($value) . '</td></tr>';
                }
            }
            echo '</table>';
        } else {
            echo '<p>Plugin file not found at: ' . esc_html($plugin_file) . '</p>';
        }
        ?>
    </div>
    
    <div class="debug-section">
        <h3>WordPress Update System</h3>
        <?php
        echo '<p><strong>WordPress Version:</strong> ' . get_bloginfo('version') . '</p>';
        
        // Check if update_plugins transient exists
        $update_transient = get_site_transient('update_plugins');
        echo '<p><strong>Update Transient:</strong> ' . (is_object($update_transient) ? 'Exists' : 'Not Found') . '</p>';
        
        // Check if our plugin is in the transient
        $plugin_basename = plugin_basename($plugin_file);
        $normalized_basename = 'Clothing_Form/preowned-clothing-form.php';
        echo '<p><strong>Plugin Basename:</strong> ' . esc_html($plugin_basename) . '</p>';
        echo '<p><strong>Normalized Basename:</strong> ' . esc_html($normalized_basename) . '</p>';
        
        if (is_object($update_transient)) {
            // Check if plugin is in checked array
            $in_checked = isset($update_transient->checked[$plugin_basename]) || 
                         isset($update_transient->checked[$normalized_basename]);
            echo '<p><strong>In Checked Array:</strong> ' . ($in_checked ? 'Yes' : 'No') . '</p>';
            
            // Check if plugin is in response array (has update)
            $in_response = isset($update_transient->response[$plugin_basename]) || 
                          isset($update_transient->response[$normalized_basename]);
            echo '<p><strong>In Response Array (Has Update):</strong> ' . ($in_response ? 'Yes' : 'No') . '</p>';
            
            if ($in_response) {
                // Show update details
                $update_obj = isset($update_transient->response[$plugin_basename]) ? 
                             $update_transient->response[$plugin_basename] : 
                             $update_transient->response[$normalized_basename];
                echo '<h4>Update Info:</h4>';
                echo '<pre>' . esc_html(print_r($update_obj, true)) . '</pre>';
            }
        }
        ?>
    </div>
    
    <div class="debug-section">
        <h3>System Information</h3>
        <?php
        echo '<p><strong>PHP Version:</strong> ' . phpversion() . '</p>';
        echo '<p><strong>WordPress Memory Limit:</strong> ' . WP_MEMORY_LIMIT . '</p>';
        echo '<p><strong>SSL Available:</strong> ' . (extension_loaded('openssl') ? 'Yes' : 'No') . '</p>';
        
        // Check if WordPress can make outbound HTTPS requests
        $test_url = 'https://api.github.com/';
        $test_args = array(
            'timeout' => 5,
            'redirection' => 5,
            'sslverify' => true
        );
        $test_response = wp_remote_get($test_url, $test_args);
        $can_connect = !is_wp_error($test_response);
        echo '<p><strong>Can Connect to GitHub API:</strong> ' . ($can_connect ? 'Yes' : 'No') . '</p>';
        
        if (!$can_connect && is_wp_error($test_response)) {
            echo '<p><strong>Connection Error:</strong> ' . esc_html($test_response->get_error_message()) . '</p>';
        }
        ?>
        
        <h4>Server Variables</h4>
        <table class="widefat striped">
            <?php
            $server_vars = array(
                'SERVER_SOFTWARE',
                'HTTP_USER_AGENT',
                'HTTPS',
                'PHP_SELF',
            );
            foreach ($server_vars as $var) {
                if (isset($_SERVER[$var])) {
                    echo '<tr><th>' . esc_html($var) . '</th><td>' . esc_html($_SERVER[$var]) . '</td></tr>';
                }
            }
            ?>
        </table>
    </div>
</div>

<style>
    .github-advanced-debug {
        background: #fff;
        padding: 15px;
        margin-top: 20px;
        border: 1px solid #ddd;
    }
    .debug-section {
        margin-bottom: 20px;
    }
    .debug-section h3 {
        margin-top: 15px;
        margin-bottom: 10px;
        color: #23282d;
    }
    .debug-section table {
        margin-top: 10px;
    }
</style>
