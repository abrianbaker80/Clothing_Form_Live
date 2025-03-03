<?php
/**
 * Enhanced GitHub Updater for Preowned Clothing Form Plugin
 * 
 * Checks for updates to the plugin on GitHub and handles the update process
 * 
 * @package PreownedClothingForm
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Check if we can run the updater (avoid conflicts with other plugins)
 */
function preowned_clothing_can_run_updater() {
    return (!class_exists('Preowned_Clothing_GitHub_Updater') && 
            !class_exists('GitHub_Updater') && 
            !function_exists('github_plugin_updater_init'));
}

/**
 * Fix WordPress update checks for this plugin specifically
 */
function preowned_clothing_force_update_check() {
    // Only run this once per page load and only in admin
    static $already_ran = false;
    if ($already_ran || !is_admin()) {
        return;
    }
    $already_ran = true;
    
    // Find the main plugin file regardless of directory name
    $plugin_file = __FILE__;
    
    // Navigate up from the includes directory to the main plugin file
    $plugin_dir = dirname(dirname($plugin_file));
    $main_plugin_file = $plugin_dir . '/preowned-clothing-form.php';
    
    if (!file_exists($main_plugin_file)) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("GitHub Updater: Cannot find main plugin file at: $main_plugin_file");
        }
        return;
    }
    
    $plugin_data = get_plugin_data($main_plugin_file);
    $plugin_basename = plugin_basename($main_plugin_file);
    
    // Get the standard plugin directory name without version
    $normalized_basename = 'Clothing_Form/preowned-clothing-form.php';
    
    // Update the update_plugins transient with our plugin data
    $current = get_site_transient('update_plugins');
    if (!is_object($current)) {
        $current = new stdClass;
    }
    
    if (!isset($current->checked) || !is_array($current->checked)) {
        $current->checked = array();
    }
    
    // Add our plugin to the list that WordPress checks - both with the actual and normalized basename
    $current->checked[$plugin_basename] = $plugin_data['Version'];
    $current->checked[$normalized_basename] = $plugin_data['Version'];
    
    set_site_transient('update_plugins', $current);
    
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('Preowned Clothing Form: Force-added plugin to update check list: ' . $plugin_basename . ' @ ' . $plugin_data['Version']);
        error_log('Preowned Clothing Form: Also added normalized basename: ' . $normalized_basename);
    }
}
add_action('admin_init', 'preowned_clothing_force_update_check', 5);

/**
 * Enhanced GitHub Updater Class
 * 
 * Handles checking for and performing updates from GitHub with improved error handling
 */
class Preowned_Clothing_GitHub_Updater {
    private $file;
    private $plugin;
    private $basename;
    private $active;
    private $username;
    private $repository;
    private $authorize_token;
    private $github_response;
    private $plugin_data;
    private $last_check;
    private $debug_mode;
    private $normalized_basename;

    /**
     * Class constructor
     * 
     * @param string $file The main plugin file path
     */
    public function __construct($file) {
        $this->file = $file;
        $this->plugin = plugin_basename($file);
        $this->basename = plugin_basename($file);
        $this->active = is_plugin_active($this->basename);
        
        // Use standard basename for update checks
        $this->normalized_basename = 'Clothing_Form/preowned-clothing-form.php';
        
        $this->plugin_data = get_plugin_data($file);
        $this->debug_mode = defined('WP_DEBUG') && WP_DEBUG;
        
        // Store the last check time
        $this->last_check = get_option('preowned_clothing_last_update_check');
        
        // Log instantiation in debug mode
        if ($this->debug_mode) {
            error_log('Preowned Clothing GitHub Updater: Initializing for ' . $this->plugin_data['Name']);
            error_log('Preowned Clothing GitHub Updater: Actual basename: ' . $this->basename);
            error_log('Preowned Clothing GitHub Updater: Normalized basename: ' . $this->normalized_basename);
            error_log('Preowned Clothing GitHub Updater: Current plugin version: ' . $this->plugin_data['Version']);
        }
    }

    /**
     * Set the GitHub username
     * 
     * @param string $username GitHub username
     */
    public function set_username($username) {
        $this->username = $username;
    }

    /**
     * Set the GitHub repository
     * 
     * @param string $repository GitHub repository name
     */
    public function set_repository($repository) {
        $this->repository = $repository;
    }

    /**
     * Authorize with GitHub
     * 
     * @param string $token GitHub personal access token
     */
    public function authorize($token) {
        $this->authorize_token = $token;
    }

    /**
     * Initialize the updater
     */
    public function initialize() {
        // Add filters to modify update check behavior
        add_filter('pre_set_site_transient_update_plugins', array($this, 'modify_transient'), 10, 1);
        add_filter('plugins_api', array($this, 'plugin_popup'), 10, 3);
        add_filter('upgrader_post_install', array($this, 'after_install'), 10, 3);
        add_filter('upgrader_pre_download', array($this, 'upgrader_pre_download'), 10, 4);
        
        // Clear update cache when viewing plugins page or force-checking updates
        if ((isset($_GET['page']) && $_GET['page'] === 'preowned-clothing-settings') || 
            isset($_GET['force-check']) || 
            (isset($_GET['plugin_status']) && $_GET['plugin_status'] === 'all')) {
            delete_site_transient('update_plugins');
            delete_option('preowned_clothing_github_response');
            delete_option('preowned_clothing_last_update_check');
        }
        
        // Add debug info to plugin row
        if ($this->debug_mode) {
            add_action('after_plugin_row_' . $this->basename, array($this, 'debug_info_row'), 10, 2);
        }
        
        // Manually check for updates on plugin page
        if (is_admin() && !wp_doing_ajax() && isset($_GET['page']) && $_GET['page'] === 'preowned-clothing-settings') {
            // Clear cache and fetch updates when on settings page
            $this->get_repository_info();
            if ($this->debug_mode) {
                error_log('Preowned Clothing GitHub Updater: Manually checking for updates from settings page');
            }
        }
        
        // Run a daily check for updates in the background
        if (!wp_next_scheduled('preowned_clothing_daily_update_check')) {
            wp_schedule_event(time(), 'daily', 'preowned_clothing_daily_update_check');
        }
        add_action('preowned_clothing_daily_update_check', array($this, 'daily_check_for_update'));
    }
    
    /**
     * Daily check for updates
     */
    public function daily_check_for_update() {
        if ($this->debug_mode) {
            error_log('Preowned Clothing GitHub Updater: Running daily update check');
        }
        
        $this->get_repository_info(true); // Force refresh
        update_option('preowned_clothing_last_update_check', time());
        
        if ($this->debug_mode && $this->github_response) {
            // Compare current version with GitHub version
            $current_version = $this->plugin_data['Version'];
            $github_version = ltrim($this->github_response['tag_name'], 'v');
            
            error_log("Preowned Clothing GitHub Updater: Daily check results - Current: $current_version, GitHub: $github_version");
            if (version_compare($github_version, $current_version, '>')) {
                error_log("Preowned Clothing GitHub Updater: Update available!");
            } else {
                error_log("Preowned Clothing GitHub Updater: No update needed");
            }
        }
        
        // Force WordPress to check for updates
        delete_site_transient('update_plugins');
    }
    
    /**
     * Display debug information in the plugin row
     */
    public function debug_info_row($file, $plugin_data) {
        $current_version = $plugin_data['Version'];
        $github_version = isset($this->github_response) && isset($this->github_response['tag_name']) ? 
                         ltrim($this->github_response['tag_name'], 'v') : 'Unknown';
                         
        $last_check = $this->last_check ? date('Y-m-d H:i:s', $this->last_check) : 'Never';
        $update_available = 'No';
        
        if ($github_version !== 'Unknown' && version_compare($github_version, $current_version, '>')) {
            $update_available = '<strong style="color: green;">Yes</strong>';
        }
                
        $debug_info = "<tr class='plugin-update-tr'><td colspan='3' class='plugin-update'>";
        $debug_info .= "<div class='update-message notice inline notice-info notice-alt'>";
        $debug_info .= "<p><strong>Debug Info:</strong> Current: $current_version | ";
        $debug_info .= "Latest on GitHub: $github_version | ";
        $debug_info .= "Update available: $update_available | ";
        $debug_info .= "Last checked: $last_check | ";
        $debug_info .= "<a href='" . admin_url('options-general.php?page=preowned-clothing-settings&force-check=1') . "'>Force Check Now</a>";
        $debug_info .= "</p></div></td></tr>";
        
        echo $debug_info;
    }

    /**
     * Modify the transient for update checking
     * 
     * @param object $transient Transient data
     * @return object Modified transient data
     */
    public function modify_transient($transient) {
        // Always check for updates during a forced update check
        $doing_cron = defined('DOING_CRON') && DOING_CRON;
        $force_check = $doing_cron || isset($_GET['force-check']) || (isset($_GET['action']) && $_GET['action'] === 'do-plugin-upgrade');
        
        // Log to the error log for debugging
        if ($this->debug_mode) {
            error_log('Preowned Clothing GitHub Updater: modify_transient called');
            error_log('Preowned Clothing GitHub Updater: Current plugin version: ' . $this->plugin_data['Version']);
        }
        
        // Fetch update info from GitHub if not already loaded or force checking
        if ($force_check || !isset($this->github_response) || empty($this->github_response)) {
            $this->github_response = get_option('preowned_clothing_github_response');
            
            if ($force_check || empty($this->github_response)) {
                $this->get_repository_info($force_check);
                update_option('preowned_clothing_last_update_check', time());
                update_option('preowned_clothing_github_response', $this->github_response);
                
                // Debug log the GitHub response
                if ($this->debug_mode && $this->github_response) {
                    error_log('Preowned Clothing GitHub Updater: GitHub API found version: ' . $this->github_response['tag_name']);
                }
            }
        }
        
        // Make sure we have cached GitHub info
        if (!$this->github_response || empty($this->github_response['tag_name'])) {
            if ($this->debug_mode) {
                error_log('Preowned Clothing GitHub Updater: No GitHub data available');
            }
            return $transient;
        }
        
        // Get current version from either transient or plugin data
        $current_version = $this->plugin_data['Version'];
        if (!empty($transient->checked)) {
            // Try to get the version from the checked array with actual or normalized basename
            if (isset($transient->checked[$this->basename])) {
                $current_version = $transient->checked[$this->basename];
            } elseif (isset($transient->checked[$this->normalized_basename])) {
                $current_version = $transient->checked[$this->normalized_basename];
            }
        }
        
        // Get tag without 'v' prefix for version comparison
        $github_version = ltrim($this->github_response['tag_name'], 'v');
        
        // Debug comparison logic
        if ($this->debug_mode) {
            error_log("Preowned Clothing GitHub Updater: Comparing versions - Current: $current_version | GitHub: $github_version");
            error_log("Preowned Clothing GitHub Updater: Comparison result: " . version_compare($github_version, $current_version, '>'));
        }
        
        // Compare versions with version_compare() - handles semantic versioning properly
        if (version_compare($github_version, $current_version, '>')) {
            if ($this->debug_mode) {
                error_log("Preowned Clothing GitHub Updater: Update available! Current: $current_version < GitHub: $github_version");
            }
            
            // Format the download URL
            $download_url = $this->github_response['zipball_url'];
            
            // Add token for private repos
            if ($this->authorize_token) {
                $download_url = add_query_arg('access_token', $this->authorize_token, $download_url);
            }
            
            // Set transient data for update - for both actual and normalized basename
            $obj = new stdClass();
            $obj->slug = dirname($this->basename); // Use directory name as slug
            $obj->plugin = $this->basename;  // Use actual basename for plugin field
            $obj->new_version = $github_version;
            $obj->url = $this->plugin_data['PluginURI'];
            $obj->package = $download_url;
            $obj->tested = isset($this->github_response['tested']) ? $this->github_response['tested'] : '';
            $obj->requires_php = isset($this->github_response['requires_php']) ? $this->github_response['requires_php'] : '';
            
            // Add to transient using both basenames to ensure it's found
            $transient->response[$this->basename] = $obj;
            
            // Also add with normalized basename to make sure it works
            $norm_obj = clone $obj;
            $norm_obj->plugin = $this->normalized_basename;
            $transient->response[$this->normalized_basename] = $norm_obj;
            
            // Force refresh of plugin update information
            wp_clean_plugins_cache();
            
            if ($this->debug_mode) {
                error_log("Preowned Clothing GitHub Updater: Added update to transient with basenames:");
                error_log("  - " . $this->basename);
                error_log("  - " . $this->normalized_basename);
            }
        } else if ($this->debug_mode) {
            error_log("Preowned Clothing GitHub Updater: No update needed. Current: $current_version >= GitHub: $github_version");
        }
        
        return $transient;
    }
    
    /**
     * Get plugin information for the update popup
     * 
     * @param boolean $false Always false
     * @param string $action The API action being performed
     * @param object $response Plugin info
     * @return object|boolean Modified response or false
     */
    public function plugin_popup($false, $action, $response) {
        // Verify this is for our plugin
        if (!isset($response->slug) || $response->slug !== dirname($this->basename)) {
            return $false;
        }
        
        // Get plugin & GitHub response
        $this->plugin_data = get_plugin_data($this->file);
        if (empty($this->github_response)) {
            $this->github_response = get_option('preowned_clothing_github_response');
            if (empty($this->github_response)) {
                $this->get_repository_info();
            }
        }
        
        // Return false if no GitHub response
        if (!$this->github_response) {
            return $false;
        }
        
        // Parse GitHub releases info
        $response->slug = dirname($this->basename);
        $response->plugin = $this->basename;
        $response->name = $this->plugin_data['Name'];
        $response->plugin_name = $this->plugin_data['Name'];
        $response->version = ltrim($this->github_response['tag_name'], 'v');
        $response->author = $this->plugin_data['AuthorName'];
        $response->homepage = $this->plugin_data['PluginURI'];
        
        // Extract description and changelog
        if (isset($this->github_response['body']) && !empty($this->github_response['body'])) {
            $changelog = $this->github_response['body'];
            
            // Check if the GitHub release body contains a section marker
            if (strpos($changelog, '## Description') !== false) {
                $parts = explode('## Description', $changelog, 2);
                if (count($parts) > 1) {
                    $desc_changelog = $parts[1];
                    
                    // Further split into description and changelog if available
                    if (strpos($desc_changelog, '## Changelog') !== false) {
                        $desc_parts = explode('## Changelog', $desc_changelog, 2);
                        $response->sections['description'] = $this->markdown_to_html($desc_parts[0]);
                        $response->sections['changelog'] = $this->markdown_to_html($desc_parts[1]);
                    } else {
                        $response->sections['description'] = $this->markdown_to_html($desc_changelog);
                    }
                } else {
                    $response->sections['description'] = $this->markdown_to_html($changelog);
                }
            } else {
                // No structured sections found, use the entire body as changelog
                $response->sections['changelog'] = $this->markdown_to_html($changelog);
                $response->sections['description'] = $this->plugin_data['Description'];
            }
        } else {
            // Fallback to plugin description
            $response->sections['description'] = $this->plugin_data['Description'];
        }
        
        // Fetch additional details that might be in GitHub API
        $response->last_updated = $this->github_response['published_at'];
        $response->download_link = $this->github_response['zipball_url'];
        
        // Set compatibility info
        $response->tested = isset($this->github_response['tested']) ? $this->github_response['tested'] : '';
        $response->requires = isset($this->github_response['requires']) ? $this->github_response['requires'] : '';
        $response->requires_php = isset($this->github_response['requires_php']) ? $this->github_response['requires_php'] : '';
        
        // Add banner if available
        if (isset($this->github_response['assets']) && is_array($this->github_response['assets'])) {
            foreach ($this->github_response['assets'] as $asset) {
                if (strpos($asset['name'], 'banner') !== false) {
                    $response->banners['high'] = $asset['browser_download_url'];
                    $response->banners['low'] = $asset['browser_download_url'];
                    break;
                }
            }
        }
        
        // Set download link
        $download_link = $this->github_response['zipball_url'];
        if ($this->authorize_token) {
            $download_link = add_query_arg('access_token', $this->authorize_token, $download_link);
        }
        $response->download_link = $download_link;
        
        return $response;
    }

    /**
     * After installation, restore the plugin structure
     * 
     * @param boolean $response Installation response
     * @param array $hook_extra Extra information about the plugin
     * @param array $result Installation result data
     * @return array Modified result
     */
    public function after_install($response, $hook_extra, $result) {
        global $wp_filesystem;

        // Get the install directory and ensure proper file structure
        $install_directory = plugin_dir_path($this->file);
        $wp_filesystem->move($result['destination'], $install_directory);
        $result['destination'] = $install_directory;
        
        // Re-activate plugin
        if ($this->active) {
            activate_plugin($this->basename);
        }
        
        return $result;
    }
    
    /**
     * Add authentication to the download URL for private repositories
     */
    public function upgrader_pre_download($reply, $package, $upgrader) {
        // Only apply to our plugin and if token is set
        if (strpos($package, 'github.com/' . $this->username . '/' . $this->repository) !== false && $this->authorize_token) {
            if ($this->debug_mode) {
                error_log('Preowned Clothing GitHub Updater: Adding authorization token to download package');
            }
            
            // Add token to URL
            $package = add_query_arg('access_token', $this->authorize_token, $package);
            // Tell WordPress to use the modified URL
            $upgrader->strings['downloading_package'] = 'Downloading package from GitHub...';
            $upgrader->skin->feedback('downloading_package');
            
            // Use the WordPress HTTP API to download the package
            $download_file = download_url($package);
            
            if (is_wp_error($download_file)) {
                // Log the error
                if ($this->debug_mode) {
                    error_log('Preowned Clothing GitHub Updater: Download failed: ' . $download_file->get_error_message());
                }
                return new WP_Error(
                    'download_failed',
                    'Error downloading package from GitHub: ' . $download_file->get_error_message(),
                    $download_file->get_error_data()
                );
            }
            
            return $download_file;
        }
        return $reply;
    }

    /**
     * Get the repository info from the GitHub API
     * 
     * @param boolean $force_refresh Force refresh of data from GitHub
     */
    private function get_repository_info($force_refresh = false) {
        if (!empty($this->github_response) && !$force_refresh) {
            return; // Already have the data and not forcing refresh
        }
        
        // Clear existing response if forcing refresh
        if ($force_refresh) {
            $this->github_response = null;
        }
        
        if (!isset($this->username) || !isset($this->repository)) {
            if ($this->debug_mode) {
                error_log('Preowned Clothing GitHub Updater: Username or repository not set');
            }
            return;
        }
        
        // GitHub API URL for the latest release
        $url = "https://api.github.com/repos/{$this->username}/{$this->repository}/releases/latest";
        
        // Set up the API request
        $args = array(
            'headers' => array(
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'WordPress/' . get_bloginfo('version') . '; ' . get_bloginfo('url')
            ),
            'timeout' => 15, // Increase timeout to prevent failures
        );
        
        // Add authorization if token exists
        if ($this->authorize_token) {
            $args['headers']['Authorization'] = "token {$this->authorize_token}";
        }
        
        if ($this->debug_mode) {
            error_log("Preowned Clothing GitHub Updater: Making API request to $url");
        }
        
        // Make the request
        $response = wp_remote_get($url, $args);
        
        // Log errors in debug mode
        if (is_wp_error($response)) {
            if ($this->debug_mode) {
                error_log('Preowned Clothing GitHub Updater: API Error - ' . $response->get_error_message());
            }
            return;
        }
        
        // Process the response
        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        // Log rate limit info in debug mode
        if ($this->debug_mode) {
            $rate_limit = wp_remote_retrieve_header($response, 'x-ratelimit-limit');
            $rate_remaining = wp_remote_retrieve_header($response, 'x-ratelimit-remaining');
            $rate_reset = wp_remote_retrieve_header($response, 'x-ratelimit-reset');
            
            if ($rate_reset) {
                $rate_reset_time = date('Y-m-d H:i:s', $rate_reset);
                error_log("Preowned Clothing GitHub Updater: API Status Code: $response_code, Rate Limit: $rate_limit, Remaining: $rate_remaining, Reset: $rate_reset_time");
            } else {
                error_log("Preowned Clothing GitHub Updater: API Status Code: $response_code");
            }
        }
        
        // Handle error responses
        if ($response_code !== 200) {
            if ($this->debug_mode) {
                // Log the specific error
                if ($response_code === 404) {
                    error_log("Preowned Clothing GitHub Updater: API Error - Repository or release not found (404)");
                } elseif ($response_code === 403) {
                    error_log("Preowned Clothing GitHub Updater: API Error - Rate limited or authentication required (403)");
                } elseif ($response_code === 401) {
                    error_log("Preowned Clothing GitHub Updater: API Error - Unauthorized, check your token (401)");
                } else {
                    error_log("Preowned Clothing GitHub Updater: API Error - Status $response_code, Response: $body");
                }
            }
            return;
        }
        
        // Decode response body and fix possible JSON issues
        $body = trim($body);
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            if ($this->debug_mode) {
                error_log('Preowned Clothing GitHub Updater: JSON decode error: ' . json_last_error_msg());
                error_log('Preowned Clothing GitHub Updater: Response body: ' . substr($body, 0, 100) . '...');
            }
            return;
        }
        
        if (empty($data)) {
            if ($this->debug_mode) {
                error_log('Preowned Clothing GitHub Updater: Empty API response');
            }
            return;
        }
        
        // Store the response
        $this->github_response = $data;
        
        if ($this->debug_mode) {
            error_log('Preowned Clothing GitHub Updater: API request successful, found version: ' . $data['tag_name']);
            
            // Also log the current plugin version for comparison
            $current_version = $this->plugin_data['Version'];
            $github_version = ltrim($data['tag_name'], 'v');
            
            error_log("Preowned Clothing GitHub Updater: Current plugin version: $current_version");
            error_log("Preowned Clothing GitHub Updater: GitHub version: $github_version");
            error_log("Preowned Clothing GitHub Updater: Update needed: " . (version_compare($github_version, $current_version, '>') ? 'Yes' : 'No'));
        }
    }
    
    /**
     * Simple Markdown to HTML converter
     * 
     * @param string $markdown Markdown text
     * @return string HTML content
     */
    private function markdown_to_html($markdown) {
        // Convert headers
        $html = preg_replace('/^###\s+(.*?)$/m', '<h3>$1</h3>', $markdown);
        $html = preg_replace('/^##\s+(.*?)$/m', '<h2>$1</h2>', $html);
        $html = preg_replace('/^#\s+(.*?)$/m', '<h1>$1</h1>', $html);
        
        // Convert lists
        $html = preg_replace('/^\*\s+(.*?)$/m', '<li>$1</li>', $html);
        $html = preg_replace('/^-\s+(.*?)$/m', '<li>$1</li>', $html);
        $html = preg_replace('/^(\d+\.)\s+(.*?)$/m', '<li>$2</li>', $html);
        
        // Wrap lists in ul/ol tags (simplified)
        $html = preg_replace('/((?:<li>.*?<\/li>\n)+)/s', '<ul>$1</ul>', $html);
        
        // Convert links
        $html = preg_replace('/\[(.*?)\]\((.*?)\)/', '<a href="$2" target="_blank">$1</a>', $html);
        
        // Convert bold and italic
        $html = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $html);
        $html = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $html);
        
        // Convert code blocks
        $html = preg_replace('/`(.*?)`/', '<code>$1</code>', $html);
        
        // Convert paragraphs
        $html = '<p>' . preg_replace('/\n\n/', '</p><p>', $html) . '</p>';
        
        return $html;
    }
}

/**
 * Display a notice if the GitHub token is not set
 */
function preowned_clothing_updater_notice() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    $token = get_option('preowned_clothing_github_token', '');
    if (empty($token)) {
        ?>
        <div class="notice notice-warning">
            <p><strong>Preowned Clothing Form:</strong> For automatic GitHub updates to work properly, please add a GitHub access token in the <a href="<?php echo admin_url('options-general.php?page=preowned-clothing-settings'); ?>">plugin settings</a>.</p>
        </div>
        <?php
    }
}
add_action('admin_notices', 'preowned_clothing_updater_notice');

/**
 * Add GitHub token setting to the admin settings page
 */
function preowned_clothing_add_github_token_setting($settings) {
    // Only add this if we don't already have it
    if (!isset($settings['github_token'])) {
        $settings['github_token'] = array(
            'title' => 'GitHub Access Token',
            'description' => 'Enter your GitHub personal access token to enable automatic updates. <a href="https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/creating-a-personal-access-token" target="_blank">Learn how to create a token</a>.',
            'type' => 'password',
            'default' => '',
        );
    }
    
    return $settings;
}
add_filter('preowned_clothing_settings_fields', 'preowned_clothing_add_github_token_setting');
