<?php
/**
 * GitHub Updater Core Class
 *
 * @package PreownedClothingForm
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Main GitHub Updater Class
 */
class Preowned_Clothing_GitHub_Updater {
    /**
     * Plugin main file path
     * 
     * @var string
     */
    public $file;

    /**
     * Repository attributes
     *
     * @var array
     */
    private $repo = [
        'username'  => '',
        'repository' => '',
        'token' => '',
        'branch' => 'main'
    ];
    
    /**
     * Plugin information
     * 
     * @var array
     */
    private $plugin_info = [];

    /**
     * Debug mode flag
     *
     * @var boolean
     */
    private $debug_mode = false;

    /**
     * Constructor
     *
     * @param string $file Full path to main plugin file
     */
    public function __construct($file) {
        $this->file = $file;
        $this->debug_mode = defined('WP_DEBUG') && WP_DEBUG;
        
        // Load plugin info
        $this->load_plugin_info();

        // Log initialization in debug mode
        if ($this->debug_mode) {
            error_log('GitHub Updater: Initialized for ' . basename($file));
            error_log('GitHub Updater: Plugin directory: ' . dirname(plugin_basename($file)));
        }
    }

    /**
     * Load plugin information from the main file
     */
    private function load_plugin_info() {
        if (!function_exists('get_plugin_data')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        $this->plugin_info = get_plugin_data($this->file);
        
        // Handle paths
        $this->plugin_info['basename'] = plugin_basename($this->file);
        
        // Store normalized basename - key for reliable updates
        $slug = sanitize_title($this->plugin_info['Name']);
        $this->plugin_info['normalized_basename'] = $slug . '/' . basename($this->file);
        
        // Get the directory structure insight
        $this->plugin_info['directory'] = dirname($this->plugin_info['basename']);
        
        // Store actual folder name for checking after install
        $this->plugin_info['folder_name'] = basename(dirname($this->file));
        
        // Log info if debugging
        if ($this->debug_mode) {
            error_log('GitHub Updater: Plugin name: ' . $this->plugin_info['Name']);
            error_log('GitHub Updater: Plugin version: ' . $this->plugin_info['Version']);
            error_log('GitHub Updater: Basename: ' . $this->plugin_info['basename']);
            error_log('GitHub Updater: Normalized basename: ' . $this->plugin_info['normalized_basename']);
            error_log('GitHub Updater: Directory: ' . $this->plugin_info['directory']);
            error_log('GitHub Updater: Folder name: ' . $this->plugin_info['folder_name']);
        }
    }

    /**
     * Set GitHub username
     *
     * @param string $username GitHub username
     * @return $this For method chaining
     */
    public function set_username($username) {
        $this->repo['username'] = trim($username);
        return $this;
    }

    /**
     * Set GitHub repository name
     *
     * @param string $repository Repository name
     * @return $this For method chaining
     */
    public function set_repository($repository) {
        $this->repo['repository'] = trim($repository);
        return $this;
    }

    /**
     * Set GitHub access token
     *
     * @param string $token Access token
     * @return $this For method chaining
     */
    public function set_token($token) {
        $this->repo['token'] = trim($token);
        return $this;
    }

    /**
     * Set whether to use debug mode
     *
     * @param boolean $debug Debug mode flag
     * @return $this For method chaining
     */
    public function set_debug($debug) {
        $this->debug_mode = (bool)$debug;
        return $this;
    }

    /**
     * Set repository branch to use
     * 
     * @param string $branch Branch name
     * @return $this For method chaining
     */
    public function set_branch($branch) {
        $this->repo['branch'] = $branch;
        return $this;
    }
    
    /**
     * Alias for set_token
     *
     * @param string $token Access token
     * @return $this For method chaining
     */
    public function authorize($token) {
        return $this->set_token($token);
    }
    
    /**
     * Get plugin file
     *
     * @return string Plugin file path
     */
    public function get_plugin_file() {
        return $this->file;
    }

    /**
     * Initialize the updater
     */
    public function initialize() {
        // Make sure we have the required data
        if (empty($this->repo['username']) || empty($this->repo['repository'])) {
            if ($this->debug_mode) {
                error_log('GitHub Updater: Missing repository or username, not initializing.');
            }
            return;
        }

        // Add filters for WordPress update system
        add_filter('pre_set_site_transient_update_plugins', [$this, 'check_for_update']);
        add_filter('plugins_api', [$this, 'plugin_popup'], 10, 3);
        add_filter('upgrader_post_install', [$this, 'after_install'], 10, 3);
        add_filter('upgrader_pre_download', [$this, 'pre_download'], 10, 4);
        
        // Add the folder name normalization filter to fix update path issues
        add_filter('upgrader_source_selection', [$this, 'fix_directory_name'], 10, 4);
        
        // Register the daily update check
        if (!wp_next_scheduled('preowned_clothing_github_updater_check')) {
            wp_schedule_event(time(), 'daily', 'preowned_clothing_github_updater_check');
            if ($this->debug_mode) {
                error_log('GitHub Updater: Scheduled daily update check.');
            }
        }
        
        // Register the function for the scheduled event
        add_action('preowned_clothing_github_updater_check', [$this, 'scheduled_check']);
        
        // Force the plugin to be included in update checks
        add_action('admin_init', [$this, 'force_check_inclusion'], 5);
        
        if ($this->debug_mode) {
            error_log('GitHub Updater: Successfully initialized.');
        }
    }

    /**
     * Force inclusion in WordPress update checks
     */
    public function force_check_inclusion() {
        // Only run this once per page load and only in admin
        static $already_ran = false;
        if ($already_ran || !is_admin()) {
            return;
        }
        $already_ran = true;
        
        // Get the current transient
        $current = get_site_transient('update_plugins');
        if (!is_object($current)) {
            $current = new stdClass();
        }
        
        if (!isset($current->checked) || !is_array($current->checked)) {
            $current->checked = [];
        }
        
        // Add both real and normalized basename to the checked list
        $current_version = $this->plugin_info['Version'];
        $current->checked[$this->plugin_info['basename']] = $current_version;
        $current->checked[$this->plugin_info['normalized_basename']] = $current_version;
        
        // Apply the changes
        set_site_transient('update_plugins', $current);
        
        if ($this->debug_mode) {
            error_log('GitHub Updater: Added plugin to update check list with version ' . $current_version);
        }
    }

    /**
     * Check for updates during WordPress update process
     *
     * @param object $transient WordPress update transient
     * @return object Modified transient
     */
    public function check_for_update($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }
        
        // Try to get GitHub API instance
        $api = $this->get_api_instance();
        if (!$api) {
            return $transient;
        }
        
        // Get current version - use transient value if available
        $current_version = $this->plugin_info['Version'];
        if (isset($transient->checked[$this->plugin_info['basename']])) {
            $current_version = $transient->checked[$this->plugin_info['basename']];
        } elseif (isset($transient->checked[$this->plugin_info['normalized_basename']])) {
            $current_version = $transient->checked[$this->plugin_info['normalized_basename']];
        }
        
        try {
            // Check for an update using the API
            $update_info = $api->check_for_update($current_version);
            
            if (!$update_info) {
                return $transient;
            }
            
            // We have an update! Build the update object for WordPress
            $obj = new stdClass();
            $obj->slug = dirname($this->plugin_info['basename']);
            $obj->plugin = $this->plugin_info['basename'];
            $obj->new_version = $update_info['version'];
            $obj->url = $this->plugin_info['PluginURI'];
            $obj->package = $update_info['zipball_url'];
            $obj->icons = (isset($this->plugin_info['Icons'])) ? $this->plugin_info['Icons'] : [];
            
            // Set compatibility info if available
            if (!empty($update_info['tested'])) {
                $obj->tested = $update_info['tested'];
            }
            if (!empty($update_info['requires_php'])) {
                $obj->requires_php = $update_info['requires_php'];
            }
            if (!empty($update_info['requires'])) {
                $obj->requires = $update_info['requires'];
            }
            
            // Add to transient with both basenames to ensure it works
            $transient->response[$this->plugin_info['basename']] = $obj;
            
            // Also add with normalized basename
            $norm_obj = clone $obj;
            $norm_obj->plugin = $this->plugin_info['normalized_basename'];
            $transient->response[$this->plugin_info['normalized_basename']] = $norm_obj;
            
            if ($this->debug_mode) {
                error_log('GitHub Updater: Update available! Adding to transient.');
                error_log('GitHub Updater: Current version: ' . $current_version . ', GitHub version: ' . $update_info['version']);
            }
        } catch (Exception $e) {
            if ($this->debug_mode) {
                error_log('GitHub Updater: Error checking for update: ' . $e->getMessage());
            }
        }
        
        return $transient;
    }

    /**
     * Fix directory name after unzip by removing version number
     * 
     * @param string $source        Source directory
     * @param string $remote_source Remote source directory
     * @param object $upgrader      Upgrader instance
     * @param array  $args          Extra arguments
     * @return string Fixed source directory
     */
    public function fix_directory_name($source, $remote_source, $upgrader, $args = []) {
        global $wp_filesystem;
        
        // Only process our plugin
        if (!is_object($upgrader->skin) || !isset($upgrader->skin->plugin) || 
            $upgrader->skin->plugin !== $this->plugin_info['basename']) {
            return $source;
        }
        
        // Get the expectation and reality
        $desired_folder = dirname(plugin_dir_path($this->file));
        $source_name = basename($source);
        
        // Check if source folder contains version number (like "plugin-name-1.2.3")
        if (preg_match('/-[0-9]+\.[0-9]+/', $source_name)) {
            // If the folder name includes the version, normalize it
            $corrected_name = $this->plugin_info['folder_name'];
            $corrected_source = str_replace($source_name, $corrected_name, $source);
            
            if ($this->debug_mode) {
                error_log("GitHub Updater: Renaming folder from '$source_name' to '$corrected_name'");
                error_log("GitHub Updater: Source: $source");
                error_log("GitHub Updater: Corrected: $corrected_source");
            }
            
            // Check if destination already exists
            if ($wp_filesystem->exists($corrected_source)) {
                // Delete the destination folder
                $wp_filesystem->delete($corrected_source, true);
                if ($this->debug_mode) {
                    error_log("GitHub Updater: Deleted existing folder: $corrected_source");
                }
            }
            
            // Rename to the corrected directory
            $renamed = $wp_filesystem->move($source, $corrected_source);
            if ($renamed) {
                return $corrected_source;
            } else {
                if ($this->debug_mode) {
                    error_log("GitHub Updater: Failed to rename directory to: $corrected_source");
                }
            }
        }
        
        return $source;
    }

    /**
     * After installation hook
     * 
     * @param bool  $response   Installation response
     * @param array $hook_extra Extra data
     * @param array $result     Installation result data
     * @return array Modified result
     */
    public function after_install($response, $hook_extra, $result) {
        global $wp_filesystem;
        
        // Only process our plugin
        if (!isset($hook_extra['plugin']) || $hook_extra['plugin'] !== $this->plugin_info['basename']) {
            return $result;
        }
        
        // Ensure that the destination is the plugin directory
        $plugin_dir = dirname($this->file);
        $destination = $result['destination'];
        
        if ($wp_filesystem->exists($destination) && $destination !== $plugin_dir) {
            // Move to the correct plugin directory if needed
            $wp_filesystem->move($destination, $plugin_dir);
            $result['destination'] = $plugin_dir;
            
            if ($this->debug_mode) {
                error_log('GitHub Updater: Moved installation from ' . $destination . ' to ' . $plugin_dir);
            }
        }
        
        // Reactivate the plugin if it was active
        if (is_plugin_active($this->plugin_info['basename'])) {
            activate_plugin($this->plugin_info['basename']);
        }
        
        return $result;
    }

    /**
     * Pre-download filter to handle authentication for private repositories
     * 
     * @param bool|WP_Error $response  Response
     * @param string        $package   Package URL
     * @param WP_Upgrader   $upgrader  Upgrader instance
     * @return bool|WP_Error|string Modified response or download path
     */
    public function pre_download($response, $package, $upgrader) {
        // Bail if not our plugin
        if (!is_object($upgrader) || !property_exists($upgrader, 'skin') || 
            !is_object($upgrader->skin) || !isset($upgrader->skin->plugin) ||
            $upgrader->skin->plugin !== $this->plugin_info['basename']) {
            return $response;
        }
        
        // Only process GitHub URLs and if we have a token
        if (empty($this->repo['token']) || strpos($package, 'github.com') === false) {
            return $response;
        }
        
        if ($this->debug_mode) {
            error_log('GitHub Updater: Pre-download hook processing package: ' . $package);
        }
        
        // Add token to URL
        $authenticated_package = add_query_arg('access_token', $this->repo['token'], $package);
        
        // Download file
        $download_file = download_url($authenticated_package);
        
        if (is_wp_error($download_file)) {
            if ($this->debug_mode) {
                error_log('GitHub Updater: Download failed: ' . $download_file->get_error_message());
            }
            return $download_file;
        }
        
        return $download_file;
    }

    /**
     * Plugin information popup
     * 
     * @param false|object $result Default value for the request
     * @param string       $action Action requested
     * @param object       $args   Plugin info
     * @return false|object Plugin info
     */
    public function plugin_popup($result, $action, $args) {
        // Check if this is a request for our plugin
        if ($action !== 'plugin_information' || !isset($args->slug) || 
            $args->slug !== dirname($this->plugin_info['basename'])) {
            return $result;
        }
        
        // Get GitHub API instance
        $api = $this->get_api_instance();
        if (!$api) {
            return $result;
        }
        
        try {
            // Get release info
            $release = $api->get_latest_release();
            if (is_wp_error($release)) {
                return $result;
            }
            
            // Get release details
            $details = $api->extract_release_details($release);
            
            // Create plugin information object
            $plugin_info = new stdClass();
            $plugin_info->slug = dirname($this->plugin_info['basename']);
            $plugin_info->plugin_name = $this->plugin_info['Name'];
            $plugin_info->name = $this->plugin_info['Name'];
            $plugin_info->version = $details['version'];
            $plugin_info->author = $this->plugin_info['AuthorName'];
            $plugin_info->homepage = $this->plugin_info['PluginURI'];
            
            // Add description and sections
            $plugin_info->sections = [];
            $plugin_info->sections['description'] = $this->plugin_info['Description'];
            
            if (!empty($details['body'])) {
                $plugin_info->sections['changelog'] = $details['body'];
            }
            
            // Set compatibility
            if (!empty($details['tested'])) {
                $plugin_info->tested = $details['tested'];
            }
            
            if (!empty($details['requires_php'])) {
                $plugin_info->requires_php = $details['requires_php'];
            }
            
            if (!empty($details['requires'])) {
                $plugin_info->requires = $details['requires'];
            }
            
            // Add download link
            $plugin_info->download_link = $details['zipball_url'];
            
            return $plugin_info;
        } catch (Exception $e) {
            if ($this->debug_mode) {
                error_log('GitHub Updater: Exception in plugin popup: ' . $e->getMessage());
            }
            return $result;
        }
    }

    /**
     * Get API instance
     * 
     * @return Preowned_Clothing_GitHub_API|null API instance or null
     */
    private function get_api_instance() {
        // Check if we have the required data
        if (empty($this->repo['username']) || empty($this->repo['repository'])) {
            if ($this->debug_mode) {
                error_log('GitHub Updater: Missing repository or username, cannot create API instance.');
            }
            return null;
        }
        
        // Load API class if needed
        if (!class_exists('Preowned_Clothing_GitHub_API')) {
            $api_file = dirname(__FILE__) . '/class-api.php';
            if (!file_exists($api_file)) {
                if ($this->debug_mode) {
                    error_log('GitHub Updater: API class file not found at: ' . $api_file);
                }
                return null;
            }
            require_once $api_file;
        }
        
        // Create and return API instance
        return new Preowned_Clothing_GitHub_API(
            $this->repo['username'],
            $this->repo['repository'],
            $this->repo['token'],
            $this->debug_mode
        );
    }
    
    /**
     * Scheduled update check
     */
    public function scheduled_check() {
        if ($this->debug_mode) {
            error_log('GitHub Updater: Running scheduled update check');
        }
        
        // Get latest release
        $api = $this->get_api_instance();
        if (!$api) {
            return;
        }
        
        try {
            // Force fresh data
            $release = $api->get_latest_release(true);
            if (is_wp_error($release)) {
                if ($this->debug_mode) {
                    error_log('GitHub Updater: Scheduled check error: ' . $release->get_error_message());
                }
                return;
            }
            
            $details = $api->extract_release_details($release);
            $github_version = $details['version'];
            $current_version = $this->plugin_info['Version'];
            
            if ($this->debug_mode) {
                error_log("GitHub Updater: Scheduled check - GitHub: $github_version, Current: $current_version");
                
                if (version_compare($github_version, $current_version, '>')) {
                    error_log('GitHub Updater: Update is available from scheduled check');
                } else {
                    error_log('GitHub Updater: No update needed from scheduled check');
                }
            }
            
            // Store last check time
            update_option('preowned_clothing_last_update_check', time());
            
            // Clear the transient to force a fresh check
            delete_site_transient('update_plugins');
            
        } catch (Exception $e) {
            if ($this->debug_mode) {
                error_log('GitHub Updater: Exception in scheduled check: ' . $e->getMessage());
            }
        }
    }
}
