<?php
/**
 * Update Debugging Helper
 *
 * Functions to help debug the GitHub updater
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Add admin page for update debugging
 */
function preowned_clothing_add_update_debug_page() {
    add_submenu_page(
        'options-general.php',
        'Update Debug',
        'Update Debug',
        'manage_options',
        'preowned-clothing-update-debug',
        'preowned_clothing_update_debug_page'
    );
}
add_action('admin_menu', 'preowned_clothing_add_update_debug_page');

/**
 * Update debug page callback
 */
function preowned_clothing_update_debug_page() {
    ?>
    <div class="wrap">
        <h1>Update Debug Information</h1>
        
        <div class="card">
            <h2>Plugin Information</h2>
            <?php
            if (!function_exists('get_plugin_data')) {
                require_once(ABSPATH . 'wp-admin/includes/plugin.php');
            }
            
            // Find the plugin file regardless of the folder name
            $plugin_file = __DIR__ . '/../preowned-clothing-form.php';
            
            if (!file_exists($plugin_file)) {
                // Fallback method to find the plugin file
                $all_plugins = get_plugins();
                $plugin_file = '';
                
                foreach ($all_plugins as $file => $plugin) {
                    if ($plugin['Name'] === 'Preowned Clothing Form') {
                        $plugin_file = WP_PLUGIN_DIR . '/' . $file;
                        break;
                    }
                }
                
                if (empty($plugin_file)) {
                    echo '<div class="notice notice-error"><p>Could not locate plugin file.</p></div>';
                    return;
                }
            }
            
            $plugin_data = get_plugin_data($plugin_file);
            ?>
            
            <table class="form-table">
                <tr>
                    <th>Plugin Name</th>
                    <td><?php echo esc_html($plugin_data['Name']); ?></td>
                </tr>
                <tr>
                    <th>Current Version</th>
                    <td><?php echo esc_html($plugin_data['Version']); ?></td>
                </tr>
                <tr>
                    <th>Plugin URI</th>
                    <td><?php echo esc_html($plugin_data['PluginURI']); ?></td>
                </tr>
                <tr>
                    <th>GitHub URI</th>
                    <td><?php echo esc_html($plugin_data['GitHub Plugin URI'] ?? 'Not set'); ?></td>
                </tr>
                <tr>
                    <th>Plugin Directory</th>
                    <td><?php echo esc_html(dirname($plugin_file)); ?></td>
                </tr>
                <tr>
                    <th>Plugin Basename</th>
                    <td><?php echo esc_html(plugin_basename($plugin_file)); ?></td>
                </tr>
            </table>
        </div>

        <div class="card">
            <h2>GitHub API Test</h2>
            <?php
            // Get username and repository from settings or plugin data
            $username = 'abrianbaker80';
            $repository = 'Clothing_Form';
            $token = get_option('preowned_clothing_github_token', '');
            
            // Make test request to GitHub API
            $url = "https://api.github.com/repos/{$username}/{$repository}/releases/latest";
            $args = array(
                'headers' => array(
                    'Accept' => 'application/vnd.github.v3+json',
                    'User-Agent' => 'WordPress/' . get_bloginfo('version') . '; ' . get_bloginfo('url')
                )
            );
            
            if (!empty($token)) {
                $args['headers']['Authorization'] = "token {$token}";
            }
            
            $response = wp_remote_get($url, $args);
            
            if (is_wp_error($response)) {
                echo '<div class="notice notice-error"><p>Error: ' . esc_html($response->get_error_message()) . '</p></div>';
            } else {
                $body = wp_remote_retrieve_body($response);
                $data = json_decode($body, true);
                $response_code = wp_remote_retrieve_response_code($response);
                
                if ($response_code !== 200) {
                    echo '<div class="notice notice-error"><p>API Error: Status code ' . esc_html($response_code) . '</p>';
                    echo '<p>Response: ' . esc_html($body) . '</p></div>';
                } else {
                    echo '<div class="notice notice-success"><p>API Request Successful!</p></div>';
                    
                    echo '<h3>Latest Release Information</h3>';
                    echo '<table class="widefat striped">';
                    echo '<tr><th>Tag Name</th><td>' . esc_html($data['tag_name']) . '</td></tr>';
                    echo '<tr><th>Published</th><td>' . esc_html($data['published_at']) . '</td></tr>';
                    echo '<tr><th>Zip URL</th><td>' . esc_html($data['zipball_url']) . '</td></tr>';
                    echo '</table>';
                    
                    // Version comparison
                    $github_version = ltrim($data['tag_name'], 'v');
                    $current_version = $plugin_data['Version'];
                    
                    echo '<h3>Version Comparison</h3>';
                    echo '<table class="widefat striped">';
                    echo '<tr><th>WordPress Version</th><td>' . esc_html($current_version) . '</td></tr>';
                    echo '<tr><th>GitHub Version</th><td>' . esc_html($github_version) . '</td></tr>';
                    
                    // Compare versions using various methods
                    echo '<tr><th>version_compare()</th><td>';
                    if (version_compare($github_version, $current_version, '>')) {
                        echo '<span style="color:green">GitHub version is newer</span>';
                    } elseif (version_compare($github_version, $current_version, '=')) {
                        echo '<span style="color:orange">Versions are equal</span>';
                    } else {
                        echo '<span style="color:red">GitHub version is older</span>';
                    }
                    echo '</td></tr>';
                    
                    // Check WordPress update cache
                    $update_cache = get_site_transient('update_plugins');
                    $has_update = isset($update_cache->response) && isset($update_cache->response[plugin_basename($plugin_file)]);
                    
                    echo '<tr><th>WordPress Update Cache</th><td>';
                    if ($has_update) {
                        echo '<span style="color:green">Update available in cache</span>';
                    } else {
                        echo '<span style="color:red">No update in cache</span>';
                    }
                    echo '</td></tr>';
                    
                    echo '</table>';
                    
                    echo '<h3>Actions</h3>';
                    echo '<form method="post">';
                    wp_nonce_field('update_debug_action', 'update_debug_nonce');
                    echo '<p><button type="submit" name="force_check" class="button button-primary">Force Update Check</button></p>';
                    echo '</form>';
                }
                
                // Display rate limit info
                echo '<h3>GitHub API Rate Limit Info</h3>';
                echo '<table class="widefat striped">';
                echo '<tr><th>Rate Limit</th><td>' . esc_html($response['headers']['x-ratelimit-limit'] ?? 'Unknown') . '</td></tr>';
                echo '<tr><th>Remaining</th><td>' . esc_html($response['headers']['x-ratelimit-remaining'] ?? 'Unknown') . '</td></tr>';
                echo '<tr><th>Reset Time</th><td>';
                if (isset($response['headers']['x-ratelimit-reset'])) {
                    echo date('Y-m-d H:i:s', $response['headers']['x-ratelimit-reset']);
                } else {
                    echo 'Unknown';
                }
                echo '</td></tr>';
                echo '</table>';
            }
            ?>
        </div>
        
        <div class="card">
            <h2>Update Transients</h2>
            <?php 
            // Display contents of update_plugins transient
            $update_transient = get_site_transient('update_plugins');
            
            if (!$update_transient) {
                echo '<p>No update_plugins transient found.</p>';
            } else {
                echo '<h3>Current Update Transient</h3>';
                
                // Check for our plugin
                $plugin_basename = plugin_basename($plugin_file);
                
                if (isset($update_transient->response) && isset($update_transient->response[$plugin_basename])) {
                    echo '<div class="notice notice-success"><p>Plugin found in update response!</p></div>';
                    
                    $plugin_update_data = $update_transient->response[$plugin_basename];
                    echo '<h4>Plugin Update Data</h4>';
                    echo '<pre>' . esc_html(print_r($plugin_update_data, true)) . '</pre>';
                } else {
                    echo '<div class="notice notice-warning"><p>Plugin not found in update response.</p></div>';
                }
                
                if (isset($update_transient->checked) && isset($update_transient->checked[$plugin_basename])) {
                    echo '<h4>Checked Version</h4>';
                    echo '<p>WordPress thinks the current version is: <strong>' . esc_html($update_transient->checked[$plugin_basename]) . '</strong></p>';
                } else {
                    echo '<p>Plugin not found in checked list.</p>';
                }
            }
            
            // Handle form submissions
            if (isset($_POST['force_check']) && check_admin_referer('update_debug_action', 'update_debug_nonce')) {
                delete_site_transient('update_plugins');
                delete_option('preowned_clothing_github_response');
                delete_option('preowned_clothing_last_update_check');
                wp_update_plugins();
                
                echo '<div class="notice notice-success"><p>Update check forced! Transients cleared and update check triggered.</p></div>';
                echo '<p><a href="' . esc_url($_SERVER['REQUEST_URI']) . '">Refresh this page</a> to see the latest data.</p>';
            }
            ?>
        </div>
    </div>
    <?php
}

/**
 * Add updater debug link to plugin action links
 */
function preowned_clothing_add_debug_action_link($links) {
    $debug_link = '<a href="' . admin_url('options-general.php?page=preowned-clothing-update-debug') . '">Update Debug</a>';
    $links[] = $debug_link;
    return $links;
}

// Use the plugin file from this plugin's directory
$plugin_file_path = dirname(__DIR__) . '/preowned-clothing-form.php';
$plugin_basename = plugin_basename($plugin_file_path);
add_filter('plugin_action_links_' . $plugin_basename, 'preowned_clothing_add_debug_action_link');
