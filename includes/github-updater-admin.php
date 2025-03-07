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
function preowned_clothing_github_updater_menu() {
    // Change capability to 'manage_options' which is standard for admins
    add_submenu_page(
        'options-general.php',
        'GitHub Updater Settings',
        'GitHub Updater',
        'manage_options', // Changed capability
        'preowned-clothing-github-updater',
        'preowned_clothing_github_updater_page'
    );
}
add_action('admin_menu', 'preowned_clothing_github_updater_menu');

// Duplicate registration removed to avoid duplicate symbol declaration.

/**
 * Display GitHub updater settings page
 */
function preowned_clothing_github_updater_page() {
    // Check user capability
    if (!current_user_can('manage_options')) {
        wp_die(__('Sorry, you do not have sufficient permissions to access this page.'));
    }
    
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
    $username = get_option('preowned_clothing_github_username', 'abrianbaker80');
    $repository = get_option('preowned_clothing_github_repository', 'Clothing_Form');
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
        
        // First test if the repository exists
        $repo_exists = preowned_clothing_test_repo_exists(
            $test_username,
            $test_repository,
            $test_token
        );
        
        // Then test for releases if repo exists
        if ($repo_exists['success']) {
            $test_result = preowned_clothing_test_github_connection(
                $test_username, 
                $test_repository, 
                $test_token
            );
        } else {
            $test_result = $repo_exists;
        }
    }
    
    // Handle verify repository
    if (isset($_POST['verify_repository'])) {
        check_admin_referer('preowned_clothing_github_updater');
        
        $test_username = sanitize_text_field($_POST['preowned_clothing_github_username']);
        $test_repository = sanitize_text_field($_POST['preowned_clothing_github_repository']);
        $test_token = sanitize_text_field($_POST['preowned_clothing_github_token']);
        
        $repo_exists = preowned_clothing_test_repo_exists(
            $test_username,
            $test_repository,
            $test_token
        );
        
        $test_result = $repo_exists;
        $test_result['phase'] = 'repository_check';
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
                <tr>
                    <th>Repository URL:</th>
                    <td>
                        <?php if (!empty($username) && !empty($repository)): ?>
                            <a href="https://github.com/<?php echo esc_attr($username); ?>/<?php echo esc_attr($repository); ?>" target="_blank">
                                https://github.com/<?php echo esc_attr($username); ?>/<?php echo esc_attr($repository); ?>
                            </a>
                        <?php else: ?>
                            <em>Not configured</em>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th>Releases URL:</th>
                    <td>
                        <?php if (!empty($username) && !empty($repository)): ?>
                            <a href="https://github.com/<?php echo esc_attr($username); ?>/<?php echo esc_attr($repository); ?>/releases" target="_blank">
                                https://github.com/<?php echo esc_attr($username); ?>/<?php echo esc_attr($repository); ?>/releases
                            </a>
                        <?php else: ?>
                            <em>Not configured</em>
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
                        <p class="description">
                            The GitHub username that owns the repository (e.g., <code>abrianbaker80</code>).
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="preowned_clothing_github_repository">GitHub Repository Name</label></th>
                    <td>
                        <input name="preowned_clothing_github_repository" type="text" id="preowned_clothing_github_repository" 
                            value="<?php echo esc_attr($repository); ?>" class="regular-text">
                        <p class="description">
                            The exact name of your GitHub repository (e.g., <code>Clothing_Form</code>). 
                            This is case-sensitive and should not include the username or slashes.
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="preowned_clothing_github_token">Personal Access Token</label></th>
                    <td>
                        <input name="preowned_clothing_github_token" type="password" id="preowned_clothing_github_token" 
                            value="<?php echo esc_attr($token); ?>" class="regular-text">
                        <p class="description">
                            Required for private repositories. <a href="https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/creating-a-personal-access-token" target="_blank">Learn how to create a token</a>.
                            Your token needs at least the <code>repo</code> scope for private repositories.
                        </p>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" name="save_github_settings" class="button-primary" value="Save Settings">
                <input type="submit" name="verify_repository" class="button" value="Verify Repository">
                <input type="submit" name="test_github_connection" class="button" value="Test Releases">
                <input type="submit" name="force_update_check" class="button" value="Force Update Check">
            </p>
        </form>
        
        <?php if (isset($test_result)): ?>
            <div class="github-test-result">
                <h2><?php echo isset($test_result['phase']) && $test_result['phase'] == 'repository_check' ? 'Repository Verification Result' : 'GitHub Releases Test Result'; ?></h2>
                <?php if ($test_result['success']): ?>
                    <div class="notice notice-success">
                        <p>Connection successful!</p>
                        <?php if (isset($test_result['version'])): ?>
                            <p>Latest version on GitHub: <?php echo esc_html($test_result['version']); ?></p>
                        <?php endif; ?>
                        <?php if (isset($test_result['message'])): ?>
                            <p><?php echo esc_html($test_result['message']); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($test_result['details'])): ?>
                            <pre><?php echo esc_html($test_result['details']); ?></pre>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="notice notice-error">
                        <p>Connection failed: <?php echo esc_html($test_result['error']); ?></p>
                        <?php if (isset($test_result['api_url'])): ?>
                            <p>API URL tried: <code><?php echo esc_html($test_result['api_url']); ?></code></p>
                        <?php endif; ?>
                        <?php if (!empty($test_result['details'])): ?>
                            <pre><?php echo esc_html($test_result['details']); ?></pre>
                        <?php endif; ?>
                        
                        <?php if (strpos($test_result['error'], '404') !== false): ?>
                            <div class="error-help">
                                <h3>How to Fix 404 Errors</h3>
                                <ol>
                                    <li>Verify that the repository exists: <a href="https://github.com/<?php echo esc_attr($test_username); ?>/<?php echo esc_attr($test_repository); ?>" target="_blank">https://github.com/<?php echo esc_attr($test_username); ?>/<?php echo esc_attr($test_repository); ?></a></li>
                                    <li>Check that the username and repository name are spelled correctly (they're case-sensitive)</li>
                                    <li>If it's a private repository, ensure your token has the correct permissions</li>
                                    <li>Confirm that the repository has at least one release: <a href="https://github.com/<?php echo esc_attr($test_username); ?>/<?php echo esc_attr($test_repository); ?>/releases" target="_blank">View Releases</a></li>
                                </ol>
                            </div>
                        <?php elseif (strpos($test_result['error'], '401') !== false): ?>
                            <div class="error-help">
                                <h3>How to Fix 401 Unauthorized Errors</h3>
                                <ol>
                                    <li>Make sure you've created a valid GitHub Personal Access Token</li>
                                    <li>Ensure the token has at least the 'repo' scope for private repositories</li>
                                    <li>Check that the token has not expired</li>
                                </ol>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="github-updater-troubleshooting">
            <h2>Troubleshooting</h2>
            
            <h3>Common Issues</h3>
            <ul>
                <li><strong>404 Not Found</strong>: The repository or releases page was not found. Check the username and repository name.</li>
                <li><strong>401 Unauthorized</strong>: Your token doesn't have the right permissions.</li>
                <li><strong>No releases found</strong>: You need to create <a href="https://docs.github.com/en/repositories/releasing-projects-on-github/managing-releases-in-a-repository" target="_blank">at least one release</a> on GitHub.</li>
            </ul>
            
            <h3>Release Requirements</h3>
            <p>For the updater to work properly:</p>
            <ul>
                <li>Your GitHub repository must have at least one published release</li>
                <li>Release tags should follow semantic versioning (e.g., "1.2.3" or "v1.2.3")</li>
                <li>The repository should contain a valid WordPress plugin with the same version number in its main file</li>
            </ul>
        </div>
        
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
        
        <style>
            .github-test-result pre {
                background: #f5f5f5;
                padding: 10px;
                overflow: auto;
                max-height: 200px;
                border: 1px solid #ddd;
                margin-top: 10px;
            }
            .error-help {
                background: #f9f9f9;
                border-left: 4px solid #0073aa;
                padding: 10px 15px;
                margin-top: 15px;
            }
            .error-help h3 {
                margin-top: 0;
            }
            #debug-log-container {
                background: #f5f5f5;
                padding: 10px;
                margin-top: 10px;
                border: 1px solid #ddd;
            }
            #debug-log {
                max-height: 300px;
                overflow: auto;
                margin: 0;
            }
            .github-updater-troubleshooting {
                background: #fff;
                padding: 15px;
                margin-top: 20px;
                border: 1px solid #ddd;
                border-left: 4px solid #46b450;
            }
            .github-updater-troubleshooting h3 {
                margin-top: 15px;
                margin-bottom: 5px;
            }
        </style>
        
        <?php preowned_clothing_maybe_show_advanced_debug(); ?>
        <?php preowned_clothing_add_help_links_to_main_page(); ?>
    </div>
    <?php
}

/**
 * Test if repository exists
 */
function preowned_clothing_test_repo_exists($username, $repository, $token = '') {
    // GitHub API URL for the repository (not releases)
    $url = "https://api.github.com/repos/{$username}/{$repository}";
    
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
    
    // Prepare result
    $result = array(
        'success' => false,
        'error' => '',
        'message' => '',
        'details' => '',
        'api_url' => $url
    );
    
    // Make the request
    $response = wp_remote_get($url, $args);
    
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
    
    // Decode response
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if (empty($data) || !isset($data['full_name'])) {
        $result['error'] = 'Invalid API response';
        $result['details'] = substr($body, 0, 500) . '...';
        return $result;
    }
    
    // Success!
    $result['success'] = true;
    $result['message'] = "Repository found: {$data['full_name']}";
    $result['details'] = "Repository ID: {$data['id']}\n";
    $result['details'] .= "Owner: {$data['owner']['login']}\n";
    $result['details'] .= "Private: " . ($data['private'] ? 'Yes' : 'No') . "\n";
    $result['details'] .= "Description: {$data['description']}";
    
    return $result;
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
 * Test GitHub connection specifically for releases
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
        'details' => '',
        'api_url' => $url
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
        
        // Special handling for 404 on releases
        if ($response_code === 404) {
            // Check if repo exists but has no releases
            $repo_exists = preowned_clothing_test_repo_exists($username, $repository, $token);
            if ($repo_exists['success']) {
                $result['error'] = "Repository exists but no releases were found";
                $result['details'] = "Your repository was found, but it needs at least one published release.\n\n"
                    . "Please create a release on GitHub and try again.\n\n"
                    . "Learn more: https://docs.github.com/en/repositories/releasing-projects-on-github/managing-releases-in-a-repository";
            }
        }
        
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
    $result['details'] .= "Release Name: {$data['name']}\n";
    $result['details'] .= "Published: " . date('Y-m-d H:i:s', strtotime($data['published_at'])) . "\n";
    $result['details'] .= "Download URL: {$data['zipball_url']}";
    
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

/**
 * Add help section to admin page
 */
function preowned_clothing_github_help_section() {
    ?>
    <div class="github-updater-help">
        <h2>GitHub Release Guide</h2>
        
        <div class="help-section">
            <h3>Creating a New Release on GitHub</h3>
            <ol>
                <li>Go to your GitHub repository</li>
                <li>Click on "Releases" in the right sidebar</li>
                <li>Click the "Create a new release" button</li>
                <li>Enter a version tag (e.g., "v2.5.9")</li>
                <li>Enter a title for the release (e.g., "Version 2.5.9")</li>
                <li>Add release notes in the description field</li>
                <li>Click "Publish release"</li>
            </ol>
            
            <p><strong>Note:</strong> The version tag (without the "v" prefix) should match the version number in your plugin's main PHP file.</p>
        </div>
        
        <div class="help-section">
            <h3>Version Naming</h3>
            <p>WordPress uses semantic versioning for plugins:</p>
            <ul>
                <li><strong>Major.Minor.Patch</strong> (e.g., 2.5.9)</li>
                <li><strong>Major:</strong> Big changes that might break compatibility</li>
                <li><strong>Minor:</strong> New features that are backward compatible</li>
                <li><strong>Patch:</strong> Bug fixes and small improvements</li>
            </ul>
        </div>
        
        <div class="help-section">
            <h3>Quick Links</h3>
            <?php 
            $username = get_option('preowned_clothing_github_username', '');
            $repository = get_option('preowned_clothing_github_repository', '');
            if (!empty($username) && !empty($repository)) : 
            ?>
            <ul>
                <li><a href="https://github.com/<?php echo esc_attr($username); ?>/<?php echo esc_attr($repository); ?>/releases/new" target="_blank">Create a New Release</a></li>
                <li><a href="https://github.com/<?php echo esc_attr($username); ?>/<?php echo esc_attr($repository); ?>/releases" target="_blank">View All Releases</a></li>
                <li><a href="https://github.com/<?php echo esc_attr($username); ?>/<?php echo esc_attr($repository); ?>/tags" target="_blank">View Tags</a></li>
            </ul>
            <?php else : ?>
            <p><em>Configure your repository settings first to see quick links.</em></p>
            <?php endif; ?>
        </div>
    </div>
    <style>
        .github-updater-help {
            background: #fff;
            padding: 15px;
            margin-top: 20px;
            border: 1px solid #ddd;
            border-left: 4px solid #00a0d2;
        }
        .help-section {
            margin-bottom: 20px;
        }
        .help-section h3 {
            margin-top: 15px;
            margin-bottom: 5px;
            color: #0073aa;
        }
    </style>
    <?php
}

/**
 * Register the admin page under GitHub menu
 */
function preowned_clothing_register_github_menu() {
    // Create a dedicated GitHub menu
    add_menu_page(
        'GitHub Updater',
        'GitHub',
        'manage_options',
        'github-updater',
        'preowned_clothing_github_updater_page',
        'dashicons-update',
        100
    );
    
    // Add submenu items
    add_submenu_page(
        'github-updater',
        'GitHub Updater Settings',
        'Settings',
        'manage_options',
        'github-updater',
        'preowned_clothing_github_updater_page'
    );
    
    // Add help submenu
    add_submenu_page(
        'github-updater',
        'GitHub Release Guide',
        'Release Guide',
        'manage_options',
        'github-release-guide',
        'preowned_clothing_github_release_guide_page'
    );
}
// Only register if not already registered in options-general.php
if (get_option('preowned_clothing_use_github_menu', false)) {
    add_action('admin_menu', 'preowned_clothing_register_github_menu');
}

/**
 * Display the GitHub release guide page
 */
function preowned_clothing_github_release_guide_page() {
    ?>
    <div class="wrap">
        <h1>GitHub Release Guide</h1>
        <?php preowned_clothing_github_help_section(); ?>
        
        <div class="github-examples">
            <h2>Example Release Notes Template</h2>
            <textarea rows="10" style="width:100%;" onclick="this.select()">## Version <?php echo esc_html(get_option('preowned_clothing_next_version', '2.6.0')); ?>

### Added
- New feature: [Description]
- Another addition: [Description]

### Changed
- Updated [something] to improve [benefit]
- Modified [feature] for better [reason]

### Fixed
- Fixed issue where [description of bug]
- Resolved problem with [description]

### Removed
- Deprecated [feature] in favor of [new approach]
</textarea>
        </div>
    </div>
    <?php
}

/**
 * Add advanced debugging section - call this from the main page when needed
 */
function preowned_clothing_add_advanced_debugging() {
    ?>
    <div class="github-advanced-debug">
        <h2>Advanced Debugging</h2>
        
        <div class="debug-section">
            <h3>Plugin Data</h3>
            <?php
            $plugin_file = dirname(dirname(__FILE__)) . '/preowned-clothing-form.php';
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
    <?php
}

/**
 * Add this to the main updater admin page to include the advanced debugging
 */
function preowned_clothing_maybe_show_advanced_debug() {
    if (isset($_GET['advanced_debug']) && $_GET['advanced_debug'] === '1') {
        preowned_clothing_add_advanced_debugging();
    } else {
        echo '<p style="margin-top: 20px;"><a href="' . esc_url(add_query_arg('advanced_debug', '1')) . '" class="button">Show Advanced Debug Info</a></p>';
    }
}

/**
 * Add this code to the main page right after the troubleshooting section
 */
function preowned_clothing_add_help_links_to_main_page() {
    ?>
    <div style="margin: 15px 0;">
        <h3>Helpful Resources</h3>
        <p>
            <a href="https://docs.github.com/en/repositories/releasing-projects-on-github/managing-releases-in-a-repository" target="_blank" class="button">GitHub Releases Documentation</a>
            <a href="<?php echo admin_url('admin.php?page=github-release-guide'); ?>" class="button">Release Guide</a>
            <a href="https://github.com/settings/tokens" target="_blank" class="button">Manage GitHub Tokens</a>
        </p>
    </div>
    <?php
}

/**
 * Add notice for new GitHub Updater release
 */
function preowned_clothing_updater_release_notice() {
    // Only show to admins and only once
    if (!current_user_can('manage_options') || get_option('preowned_clothing_release_notice_dismissed', false)) {
        return;
    }
    
    // Check if settings are incomplete
    $username = get_option('preowned_clothing_github_username', '');
    $repository = get_option('preowned_clothing_github_repository', '');
    
    if (empty($username) || empty($repository)) {
        ?>
        <div class="notice notice-info is-dismissible" id="preowned-clothing-release-notice">
            <h3>GitHub Updater Ready to Use!</h3>
            <p>The new GitHub Updater feature can automatically update your plugin from your GitHub repository.</p>
            <p>Simply <a href="<?php echo admin_url('options-general.php?page=preowned-clothing-github-updater'); ?>">configure your repository details</a> to get started.</p>
            <button type="button" class="notice-dismiss">
                <span class="screen-reader-text">Dismiss this notice.</span>
            </button>
        </div>
        <script>
            jQuery(document).ready(function($) {
                $(document).on('click', '#preowned-clothing-release-notice .notice-dismiss', function() {
                    $.ajax({
                        url: ajaxurl,
                        data: {
                            action: 'dismiss_updater_notice',
                            security: '<?php echo wp_create_nonce('dismiss_updater_notice'); ?>'
                        }
                    });
                });
            });
        </script>
        <?php
    }
}
add_action('admin_notices', 'preowned_clothing_updater_release_notice');

/**
 * AJAX handler to dismiss the release notice
 */
function preowned_clothing_dismiss_updater_notice() {
    check_ajax_referer('dismiss_updater_notice', 'security');
    
    if (current_user_can('manage_options')) {
        update_option('preowned_clothing_release_notice_dismissed', true);
    }
    
    wp_die();
}
add_action('wp_ajax_dismiss_updater_notice', 'preowned_clothing_dismiss_updater_notice');

/**
 * GitHub Updater Admin Functions
 *
 * Handles the GitHub updater admin interface
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Settings section callback
 */
function preowned_clothing_github_updater_section_callback() {
    echo '<p>Configure the GitHub repository for automatic updates. This allows the plugin to check for new versions and update directly from GitHub.</p>';
}

/**
 * Username field callback
 */
function preowned_clothing_github_username_callback() {
    $username = get_option('preowned_clothing_github_username', 'abrianbaker80');
    echo '<input type="text" id="preowned_clothing_github_username" name="preowned_clothing_github_username" value="' . esc_attr($username) . '" class="regular-text">';
    echo '<p class="description">Enter the GitHub username that owns the repository.</p>';
}

/**
 * Repository field callback
 */
function preowned_clothing_github_repository_callback() {
    $repository = get_option('preowned_clothing_github_repository', 'Clothing_Form');
    echo '<input type="text" id="preowned_clothing_github_repository" name="preowned_clothing_github_repository" value="' . esc_attr($repository) . '" class="regular-text">';
    echo '<p class="description">Enter the repository name without the username.</p>';
}

/**
 * Token field callback
 */
function preowned_clothing_github_token_callback() {
    $token = get_option('preowned_clothing_github_token', '');
    echo '<input type="password" id="preowned_clothing_github_token" name="preowned_clothing_github_token" value="' . esc_attr($token) . '" class="regular-text">';
    echo '<p class="description">Optional: Enter a GitHub access token for private repositories or to avoid API rate limits.</p>';
}

/**
 * Render the settings page
 */
function preowned_clothing_github_updater_settings_page() {
    // Check permissions
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Show debug information if needed
    global $preowned_clothing_gh_updater_running;
    $updater_status = $preowned_clothing_gh_updater_running ? 'Active' : 'Not active';
    
    // Try to get updater info
    $updater_version = defined('PCF_USING_NEW_UPDATER') && PCF_USING_NEW_UPDATER ? 'Modular (new)' : 'Legacy';
    
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <?php if (isset($_GET['settings-updated'])) : ?>
            <div class="notice notice-success is-dismissible">
                <p><?php _e('Settings saved successfully.', 'preowned-clothing-form'); ?></p>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <h2>GitHub Updater Status</h2>
            <p><strong>Updater Status:</strong> <?php echo $updater_status; ?></p>
            <p><strong>Updater Type:</strong> <?php echo $updater_version; ?></p>
            <p><strong>Current Settings:</strong></p>
            <ul>
                <li>Username: <?php echo esc_html(get_option('preowned_clothing_github_username', 'abrianbaker80')); ?></li>
                <li>Repository: <?php echo esc_html(get_option('preowned_clothing_github_repository', 'Clothing_Form')); ?></li>
                <li>Token: <?php echo get_option('preowned_clothing_github_token') ? '••••••••' : 'Not set'; ?></li>
            </ul>
        </div>
        
        <form action="options.php" method="post">
            <?php
            settings_fields('preowned_clothing_github_updater');
            do_settings_sections('preowned-clothing-github-updater');
            submit_button('Save Settings');
            ?>
        </form>
        
        <div class="card" style="margin-top: 20px;">
            <h2>Test GitHub Connection</h2>
            <p>Click the button below to test the GitHub connection with your current settings.</p>
            <button id="test-github-connection" class="button button-secondary">Test Connection</button>
            <div id="test-result" style="margin-top: 10px; padding: 10px; display: none;"></div>
        </div>
    </div>
    
    <script>
        jQuery(document).ready(function($) {
            $('#test-github-connection').on('click', function(e) {
                e.preventDefault();
                
                const $resultDiv = $('#test-result');
                $resultDiv.html('Testing connection...').show().css('background', '#f7f7f7');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'preowned_clothing_test_github_connection',
                        username: $('#preowned_clothing_github_username').val(),
                        repository: $('#preowned_clothing_github_repository').val(),
                        token: $('#preowned_clothing_github_token').val(),
                        nonce: '<?php echo wp_create_nonce('preowned_clothing_github_test'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $resultDiv.html('Connection successful! Repository exists and is accessible.').css('background', '#ecf7ed');
                        } else {
                            $resultDiv.html('Connection failed: ' + response.data).css('background', '#fbeaea');
                        }
                    },
                    error: function() {
                        $resultDiv.html('Request failed. Please check your internet connection.').css('background', '#fbeaea');
                    }
                });
            });
        });
    </script>
    <?php
}

/**
 * Handle the AJAX test connection request
 */
function preowned_clothing_test_github_connection_ajax() {
    // Check nonce for security
    check_ajax_referer('preowned_clothing_github_test', 'nonce');
    
    // Check if user has permission
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permission denied');
        return;
    }
    
    $username = isset($_POST['username']) ? sanitize_text_field($_POST['username']) : '';
    $repository = isset($_POST['repository']) ? sanitize_text_field($_POST['repository']) : '';
    $token = isset($_POST['token']) ? sanitize_text_field($_POST['token']) : '';
    
    if (empty($username) || empty($repository)) {
        wp_send_json_error('Username and repository are required');
        return;
    }
    
    // Build API URL
    $api_url = "https://api.github.com/repos/{$username}/{$repository}";
    
    // Set up request arguments
    $args = array(
        'headers' => array(
            'Accept' => 'application/vnd.github.v3+json',
            'User-Agent' => 'WordPress/' . get_bloginfo('version') . '; ' . get_bloginfo('url')
        )
    );
    
    // Add token to request if provided
    if (!empty($token)) {
        $args['headers']['Authorization'] = 'token ' . $token;
    }
    
    // Make the request
    $response = wp_remote_get($api_url, $args);
    
    // Check for errors
    if (is_wp_error($response)) {
        wp_send_json_error('Connection error: ' . $response->get_error_message());
        return;
    }
    
    // Check response code
    $response_code = wp_remote_retrieve_response_code($response);
    if ($response_code !== 200) {
        $body = wp_remote_retrieve_body($response);
        $error_data = json_decode($body, true);
        $error_message = isset($error_data['message']) ? $error_data['message'] : 'Unknown error';
        
        wp_send_json_error("GitHub API error ({$response_code}): {$error_message}");
        return;
    }
    
    // Success - repository exists and is accessible
    wp_send_json_success();
}
add_action('wp_ajax_preowned_clothing_test_github_connection', 'preowned_clothing_test_github_connection_ajax');
