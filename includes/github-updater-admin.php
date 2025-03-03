<?php
/**
 * GitHub Updater Admin Interface
 * Provides an admin interface for configuring and testing the GitHub updater
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Register the GitHub updater settings page
 */
function preowned_clothing_register_github_updater_page() {
    add_submenu_page(
        'options-general.php',
        'GitHub Updater',
        'GitHub Updater',
        'manage_options',
        'preowned-clothing-github-updater',
        'preowned_clothing_github_updater_page'
    );
}
add_action('admin_menu', 'preowned_clothing_register_github_updater_page');

/**
 * Register GitHub updater settings
 */
function preowned_clothing_register_github_updater_settings() {
    register_setting('preowned_clothing_github_updater', 'preowned_clothing_github_token');
    register_setting('preowned_clothing_github_updater', 'preowned_clothing_github_username');
    register_setting('preowned_clothing_github_updater', 'preowned_clothing_github_repository');
}
add_action('admin_init', 'preowned_clothing_register_github_updater_settings');

/**
 * Display GitHub updater settings page
 */
function preowned_clothing_github_updater_page() {
    // Check for current version information
    $plugin_file = WP_PLUGIN_DIR . '/Clothing_Form_Live/preowned-clothing-form.php';
    if (!file_exists($plugin_file)) {
        $plugin_file = dirname(dirname(__FILE__)) . '/preowned-clothing-form.php';
    }
    
    if (file_exists($plugin_file)) {
        $plugin_data = get_plugin_data($plugin_file);
        $current_version = isset($plugin_data['Version']) ? $plugin_data['Version'] : 'Unknown';
    } else {
        $current_version = 'Unknown (Plugin file not found)';
    }
    
    // Check GitHub version if we have settings
    $github_version = 'Unknown';
    $username = get_option('preowned_clothing_github_username', '');
    $repository = get_option('preowned_clothing_github_repository', '');
    $token = get_option('preowned_clothing_github_token', '');
    
    if (!empty($username) && !empty($repository)) {
        $github_version = preowned_clothing_get_github_version($username, $repository, $token);
    }
    
    // Check if update is needed
    $update_available = $github_version !== 'Unknown' && $current_version !== 'Unknown' && 
                       version_compare($github_version, $current_version, '>');
    
    // Handle form submission
    if (isset($_POST['test_github_connection'])) {
        check_admin_referer('preowned_clothing_github_updater');
        
        $test_username = sanitize_text_field($_POST['preowned_clothing_github_username']);
        $test_repository = sanitize_text_field($_POST['preowned_clothing_github_repository']);
        $test_token = sanitize_text_field($_POST['preowned_clothing_github_token']);
        
        $test_result = preowned_clothing_test_github_connection(
            $test_username, 
            $test_repository, 
            $test_token
        );
    }
    
    // Handle force update check
    if (isset($_POST['force_update_check'])) {
        check_admin_referer('preowned_clothing_github_updater');
        
        // Clear transients and cached data
        delete_site_transient('update_plugins');
        delete_option('preowned_clothing_github_response');
        delete_option('preowned_clothing_last_update_check');
        
        // Force check
        wp_clean_plugins_cache(true);
        wp_update_plugins();
        
        // Redirect to updates page
        wp_redirect(admin_url('update-core.php'));
        exit;
    }
    
    // Handle saving settings
    if (isset($_POST['save_github_settings'])) {
        check_admin_referer('preowned_clothing_github_updater');
        
        update_option('preowned_clothing_github_username', sanitize_text_field($_POST['preowned_clothing_github_username']));
        update_option('preowned_clothing_github_repository', sanitize_text_field($_POST['preowned_clothing_github_repository']));
        update_option('preowned_clothing_github_token', sanitize_text_field($_POST['preowned_clothing_github_token']));
        
        echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
    }
    
    ?>
    <div class="wrap">
        <h1>GitHub Updater Settings</h1>
        
        <div class="github-updater-status">
            <h2>Update Status</h2>
            <table class="form-table">
                <tr>
                    <th>Current Plugin Version:</th>
                    <td><?php echo esc_html($current_version); ?></td>
                </tr>
                <tr>
                    <th>Latest GitHub Version:</th>
                    <td><?php echo esc_html($github_version); ?></td>
                </tr>
                <tr>
                    <th>Update Available:</th>
                    <td>
                        <?php if ($update_available): ?>
                            <span style="color: green; font-weight: bold;">Yes</span> - 
                            <a href="<?php echo admin_url('update-core.php'); ?>">Go to WordPress Updates</a>
                        <?php else: ?>
                            <span>No</span>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </div>
        
        <form method="post" action="">
            <?php wp_nonce_field('preowned_clothing_github_updater'); ?>
            
            <h2>GitHub Repository Settings</h2>
            <p>Configure these settings to enable automatic updates from your GitHub repository.</p>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="preowned_clothing_github_username">GitHub Username</label></th>
                    <td>
                        <input name="preowned_clothing_github_username" type="text" id="preowned_clothing_github_username" 
                            value="<?php echo esc_attr($username); ?>" class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="preowned_clothing_github_repository">GitHub Repository Name</label></th>
                    <td>
                        <input name="preowned_clothing_github_repository" type="text" id="preowned_clothing_github_repository" 
                            value="<?php echo esc_attr($repository); ?>" class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="preowned_clothing_github_token">Personal Access Token</label></th>
                    <td>
                        <input name="preowned_clothing_github_token" type="password" id="preowned_clothing_github_token" 
                            value="<?php echo esc_attr($token); ?>" class="regular-text">
                        <p class="description">
                            Required for private repositories. <a href="https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/creating-a-personal-access-token" target="_blank">Learn how to create a token</a>.
                        </p>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" name="save_github_settings" class="button-primary" value="Save Settings">
                <input type="submit" name="test_github_connection" class="button" value="Test Connection">
                <input type="submit" name="force_update_check" class="button" value="Force Update Check">
            </p>
        </form>
        
        <?php if (isset($test_result)): ?>
            <div class="github-test-result">
                <h2>Connection Test Result</h2>
                <?php if ($test_result['success']): ?>
                    <div class="notice notice-success">
                        <p>Connection successful!</p>
                        <p>Latest version on GitHub: <?php echo esc_html($test_result['version']); ?></p>
                        <?php if (!empty($test_result['details'])): ?>
                            <pre><?php echo esc_html($test_result['details']); ?></pre>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="notice notice-error">
                        <p>Connection failed: <?php echo esc_html($test_result['error']); ?></p>
                        <?php if (!empty($test_result['details'])): ?>
                            <pre><?php echo esc_html($test_result['details']); ?></pre>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="github-updater-log">
            <h2>Debug Log</h2>
            <p>
                <button id="toggle-debug-log" class="button">Show Debug Log</button>
                <button id="refresh-debug-log" class="button">Refresh</button>
                <button id="clear-debug-log" class="button">Clear Log</button>
            </p>
            <div id="debug-log-container" style="display: none;">
                <pre id="debug-log"><?php echo esc_html(preowned_clothing_get_debug_log()); ?></pre>
            </div>
        </div>
        
        <script>
            jQuery(document).ready(function($) {
                $('#toggle-debug-log').on('click', function() {
                    $('#debug-log-container').toggle();
                    $(this).text($(this).text() === 'Show Debug Log' ? 'Hide Debug Log' : 'Show Debug Log');
                });
                
                $('#refresh-debug-log').on('click', function() {
                    $.post(ajaxurl, {
                        action: 'preowned_clothing_refresh_debug_log',
                        nonce: '<?php echo wp_create_nonce('preowned_clothing_refresh_debug_log'); ?>'
                    }, function(response) {
                        if (response.success) {
                            $('#debug-log').text(response.data);
                        }
                    });
                });
                
                $('#clear-debug-log').on('click', function() {
                    if (confirm('Are you sure you want to clear the debug log?')) {
                        $.post(ajaxurl, {
                            action: 'preowned_clothing_clear_debug_log',
                            nonce: '<?php echo wp_create_nonce('preowned_clothing_clear_debug_log'); ?>'
                        }, function(response) {
                            if (response.success) {
                                $('#debug-log').text('');
                            }
                        });
                    }
                });
            });
        </script>
    </div>
    <?php
}

/**
 * Get latest version from GitHub
 */
function preowned_clothing_get_github_version($username, $repository, $token = '') {
    // GitHub API URL for the latest release
    $url = "https://api.github.com/repos/{$username}/{$repository}/releases/latest";
    
    // Set up the API request
    $args = array(
        'headers' => array(
            'Accept' => 'application/vnd.github.v3+json',
            'User-Agent' => 'WordPress/' . get_bloginfo('version') . '; ' . get_bloginfo('url')
        ),
        'timeout' => 15,
    );
    
    // Add authorization if token exists
    if (!empty($token)) {
        $args['headers']['Authorization'] = "token {$token}";
    }
    
    // Make the request
    $response = wp_remote_get($url, $args);
    
    // Check for errors
    if (is_wp_error($response)) {
        return 'Error: ' . $response->get_error_message();
    }
    
    // Check response code
    $response_code = wp_remote_retrieve_response_code($response);
    if ($response_code !== 200) {
        return 'API Error: ' . $response_code;
    }
    
    // Decode response
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if (empty($data) || !isset($data['tag_name'])) {
        return 'Invalid response';
    }
    
    // Return version (removing 'v' prefix if present)
    return ltrim($data['tag_name'], 'v');
}

/**
 * Test GitHub connection
 */
function preowned_clothing_test_github_connection($username, $repository, $token = '') {
    // GitHub API URL for the latest release
    $url = "https://api.github.com/repos/{$username}/{$repository}/releases/latest";
    
    // Set up the API request
    $args = array(
        'headers' => array(
            'Accept' => 'application/vnd.github.v3+json',
            'User-Agent' => 'WordPress/' . get_bloginfo('version') . '; ' . get_bloginfo('url')
        ),
        'timeout' => 15,
    );
    
    // Add authorization if token exists
    if (!empty($token)) {
        $args['headers']['Authorization'] = "token {$token}";
    }
    
    // Make the request
    $response = wp_remote_get($url, $args);
    
    // Prepare result
    $result = array(
        'success' => false,
        'error' => '',
        'version' => '',
        'details' => ''
    );
    
    // Check for errors
    if (is_wp_error($response)) {
        $result['error'] = $response->get_error_message();
        return $result;
    }
    
    // Check response code
    $response_code = wp_remote_retrieve_response_code($response);
    if ($response_code !== 200) {
        $result['error'] = "API Error: HTTP {$response_code}";
        $result['details'] = wp_remote_retrieve_body($response);
        return $result;
    }
    
    // Get response headers for rate limit info
    $headers = wp_remote_retrieve_headers($response);
    $rate_limit = isset($headers['x-ratelimit-limit']) ? $headers['x-ratelimit-limit'] : 'Unknown';
    $rate_remaining = isset($headers['x-ratelimit-remaining']) ? $headers['x-ratelimit-remaining'] : 'Unknown';
    $rate_reset = isset($headers['x-ratelimit-reset']) ? date('Y-m-d H:i:s', $headers['x-ratelimit-reset']) : 'Unknown';
    
    // Decode response
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if (empty($data) || !isset($data['tag_name'])) {
        $result['error'] = 'Invalid API response';
        $result['details'] = substr($body, 0, 500) . '...';
        return $result;
    }
    
    // Success!
    $result['success'] = true;
    $result['version'] = ltrim($data['tag_name'], 'v');
    $result['details'] = "Rate Limit: {$rate_limit}\nRemaining: {$rate_remaining}\nReset: {$rate_reset}\n";
    
    return $result;
}

/**
 * Get debug log contents (last 100 lines)
 */
function preowned_clothing_get_debug_log() {
    $log_file = WP_CONTENT_DIR . '/debug.log';
    
    if (!file_exists($log_file)) {
        return 'Debug log file not found.';
    }
    
    // Get only the last portion of the file (100KB)
    $max_size = 100 * 1024; // 100KB
    $file_size = filesize($log_file);
    
    if ($file_size > $max_size) {
        $handle = fopen($log_file, 'r');
        fseek($handle, -$max_size, SEEK_END);
        $log = fread($handle, $max_size);
        fclose($handle);
        
        // Find the first complete line
        $pos = strpos($log, "\n");
        if ($pos !== false) {
            $log = substr($log, $pos + 1);
        }
    } else {
        $log = file_get_contents($log_file);
    }
    
    // Filter for GitHub updater entries
    $lines = explode("\n", $log);
    $github_lines = array();
    
    foreach ($lines as $line) {
        if (strpos($line, 'GitHub') !== false || strpos($line, 'github') !== false) {
            $github_lines[] = $line;
        }
    }
    
    return !empty($github_lines) ? implode("\n", $github_lines) : 'No GitHub updater entries found in the log.';
}

/**
 * AJAX handler for refreshing debug log
 */
function preowned_clothing_refresh_debug_log_ajax() {
    check_ajax_referer('preowned_clothing_refresh_debug_log', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permission denied');
    }
    
    $log = preowned_clothing_get_debug_log();
    wp_send_json_success($log);
}
add_action('wp_ajax_preowned_clothing_refresh_debug_log', 'preowned_clothing_refresh_debug_log_ajax');

/**
 * AJAX handler for clearing debug log
 */
function preowned_clothing_clear_debug_log_ajax() {
    check_ajax_referer('preowned_clothing_clear_debug_log', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permission denied');
    }
    
    $log_file = WP_CONTENT_DIR . '/debug.log';
    
    if (file_exists($log_file)) {
        $success = file_put_contents($log_file, '');
        wp_send_json_success($success !== false);
    } else {
        wp_send_json_error('Log file not found');
    }
}
add_action('wp_ajax_preowned_clothing_clear_debug_log', 'preowned_clothing_clear_debug_log_ajax');

/**
 * Add settings link to plugins page
 */
function preowned_clothing_add_github_settings_link($links) {
    $settings_link = '<a href="' . admin_url('options-general.php?page=preowned-clothing-github-updater') . '">GitHub Settings</a>';
    array_push($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(dirname(dirname(__FILE__)) . '/preowned-clothing-form.php'), 'preowned_clothing_add_github_settings_link');
