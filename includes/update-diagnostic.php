<?php
/**
 * Update Diagnostic Tool
 *
 * A standalone tool to help diagnose and test GitHub updater
 */

if (isset($_GET['run-update-test'])) {
    // Make sure we can access WordPress functions
    define('WP_DEBUG', true);
    require_once dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/wp-load.php';

    // Check if we were successful
    if (!function_exists('get_plugin_data')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    echo "<h1>Update Diagnostic Results</h1>";
    echo "<pre>";

    // 1. Find the current plugin path
    $plugin_file = __FILE__;
    echo "Diagnostic file location: $plugin_file\n\n";
    
    // Navigate up from the includes directory to the main plugin file
    $plugin_dir = dirname(dirname($plugin_file));
    $main_plugin_file = $plugin_dir . '/preowned-clothing-form.php';
    echo "Looking for main plugin file at: $main_plugin_file\n";
    
    if (file_exists($main_plugin_file)) {
        echo "FOUND!\n\n";
    } else {
        echo "NOT FOUND - This is a critical error\n\n";
    }
    
    // 2. Get plugin data
    if (file_exists($main_plugin_file)) {
        $plugin_data = get_plugin_data($main_plugin_file);
        echo "Plugin Name: {$plugin_data['Name']}\n";
        echo "Plugin Version: {$plugin_data['Version']}\n";
        echo "Plugin URI: {$plugin_data['PluginURI']}\n";
        echo "GitHub URI: " . (isset($plugin_data['GitHub Plugin URI']) ? $plugin_data['GitHub Plugin URI'] : 'Not set') . "\n\n";
    }
    
    // 3. Check basename
    $actual_basename = plugin_basename($main_plugin_file);
    $normalized_basename = 'Clothing_Form/preowned-clothing-form.php';
    echo "Actual Plugin Basename: $actual_basename\n";
    echo "Expected Normalized Basename: $normalized_basename\n\n";
    
    // 4. Check update transient
    $update_transient = get_site_transient('update_plugins');
    echo "Current Update Transient:\n";
    if (!$update_transient) {
        echo "No update_plugins transient found.\n";
    } else {
        echo "Transient exists.\n";
        
        // Check for our plugin
        if (isset($update_transient->checked) && isset($update_transient->checked[$actual_basename])) {
            echo "Plugin found in checked list with actual basename\n";
            echo "Checked version: {$update_transient->checked[$actual_basename]}\n";
        } else {
            echo "Plugin NOT found in checked list with actual basename\n";
        }
        
        if (isset($update_transient->checked) && isset($update_transient->checked[$normalized_basename])) {
            echo "Plugin found in checked list with normalized basename\n";
            echo "Checked version: {$update_transient->checked[$normalized_basename]}\n";
        } else {
            echo "Plugin NOT found in checked list with normalized basename\n";
        }
        
        if (isset($update_transient->response) && isset($update_transient->response[$actual_basename])) {
            echo "Plugin found in update response with actual basename\n";
            $plugin_update_data = $update_transient->response[$actual_basename];
            echo "Update version: {$plugin_update_data->new_version}\n";
        } else {
            echo "Plugin NOT found in update response with actual basename\n";
        }
        
        if (isset($update_transient->response) && isset($update_transient->response[$normalized_basename])) {
            echo "Plugin found in update response with normalized basename\n";
            $plugin_update_data = $update_transient->response[$normalized_basename];
            echo "Update version: {$plugin_update_data->new_version}\n";
        } else {
            echo "Plugin NOT found in update response with normalized basename\n";
        }
    }
    
    // 5. Check GitHub API
    echo "\nTesting GitHub API Connection:\n";
    $username = 'abrianbaker80';
    $repository = 'Clothing_Form';
    $token = get_option('preowned_clothing_github_token', '');
    
    $url = "https://api.github.com/repos/{$username}/{$repository}/releases/latest";
    $args = array(
        'headers' => array(
            'Accept' => 'application/vnd.github.v3+json',
            'User-Agent' => 'WordPress Test Script'
        ),
        'timeout' => 15
    );
    
    if (!empty($token)) {
        echo "Using authentication token\n";
        $args['headers']['Authorization'] = "token {$token}";
    } else {
        echo "No GitHub token found - using unauthenticated request\n";
    }
    
    $response = wp_remote_get($url, $args);
    
    if (is_wp_error($response)) {
        echo "API Error: " . $response->get_error_message() . "\n";
    } else {
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        $response_code = wp_remote_retrieve_response_code($response);
        
        echo "API Status Code: $response_code\n";
        if ($response_code === 200) {
            echo "GitHub Latest Version: " . $data['tag_name'] . "\n";
            echo "Published Date: " . $data['published_at'] . "\n";
            
            $github_version = ltrim($data['tag_name'], 'v');
            if (file_exists($main_plugin_file)) {
                echo "\nVersion Comparison Result:\n";
                if (version_compare($github_version, $plugin_data['Version'], '>')) {
                    echo "Update is available! ($github_version > {$plugin_data['Version']})\n";
                } elseif (version_compare($github_version, $plugin_data['Version'], '=')) {
                    echo "Versions are equal ($github_version = {$plugin_data['Version']})\n";
                } else {
                    echo "GitHub version is older! ($github_version < {$plugin_data['Version']})\n";
                }
            }
        }
    }
    
    // 6. Provide potential fix actions
    echo "\n\nRecommended Actions:\n";
    echo "1. Force clear update cache by running: wp_delete_site_transient('update_plugins');\n";
    echo "2. Make sure the plugin directory is named 'Clothing_Form' without version numbers\n";
    echo "3. Check if GitHub token is valid\n";
    echo "4. Ensure plugin version in PHP header is formatted correctly (e.g. 2.3.1.4)\n";
    echo "5. Test with a newer version tag on GitHub\n";
    
    // 7. Apply fixes automatically if requested
    if (isset($_GET['apply-fixes'])) {
        echo "\n\nApplying automatic fixes:\n";
        
        // Clear update cache
        delete_site_transient('update_plugins');
        echo "✓ Cleared update_plugins transient\n";
        
        // Perform a manual transient update
        $current = new stdClass;
        $current->checked = array();
        $current->response = array();
        
        // Add our plugin to the checked list with both basenames
        if (file_exists($main_plugin_file)) {
            $current->checked[$actual_basename] = $plugin_data['Version'];
            $current->checked[$normalized_basename] = $plugin_data['Version'];
            echo "✓ Added plugin to checked list with both basenames\n";
            
            // If GitHub version is newer, add to response
            if (isset($data['tag_name']) && version_compare($github_version, $plugin_data['Version'], '>')) {
                $obj = new stdClass();
                $obj->slug = 'Clothing_Form';
                $obj->plugin = $actual_basename;
                $obj->new_version = $github_version;
                $obj->url = $plugin_data['PluginURI'];
                $obj->package = $data['zipball_url'];
                
                // Add both basenames
                $current->response[$actual_basename] = $obj;
                
                $norm_obj = clone $obj;
                $norm_obj->plugin = $normalized_basename;
                $current->response[$normalized_basename] = $norm_obj;
                
                echo "✓ Manually added update data to transient\n";
                set_site_transient('update_plugins', $current);
            }
        }
    }
    
    echo "</pre>";
    
    if (!isset($_GET['apply-fixes'])) {
        echo "<p><a href='?run-update-test&apply-fixes=1' style='padding:10px; background:#0073aa; color:white; text-decoration:none; display:inline-block;'>Apply Automatic Fixes</a></p>";
    } else {
        echo "<p><a href='../wp-admin/plugins.php' style='padding:10px; background:#0073aa; color:white; text-decoration:none; display:inline-block;'>Return to Plugins Page</a></p>";
    }
    
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Update Diagnostic Tool</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif; margin: 20px; line-height: 1.4; }
        h1 { color: #23282d; }
        .card { background: white; padding: 20px; border: 1px solid #ddd; margin: 20px 0; max-width: 800px; box-shadow: 0 1px 1px rgba(0,0,0,0.04); }
        .button { background: #0073aa; color: white; padding: 10px 15px; text-decoration: none; display: inline-block; border: none; cursor: pointer; }
        .button:hover { background: #006799; }
    </style>
</head>
<body>
    <div class="card">
        <h1>GitHub Updater Diagnostic Tool</h1>
        <p>This tool helps diagnose issues with the GitHub Updater functionality.</p>
        <p>When you click the button below, the script will:</p>
        <ol>
            <li>Check for the main plugin file</li>
            <li>Compare actual and expected plugin paths</li>
            <li>Inspect the update transient data</li>
            <li>Test connection to the GitHub API</li>
            <li>Recommend potential fixes</li>
        </ol>
        <p><a href="?run-update-test=1" class="button">Run Diagnostic Tests</a></p>
    </div>
</body>
</html>
