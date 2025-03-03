<?php
/**
 * WordPress Integration for GitHub Updater
 *
 * Handles integration with the WordPress plugin update system
 *
 * @package PreownedClothingForm
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * WordPress Integration Class
 */
class Preowned_Clothing_GitHub_WP_Integration {
    /**
     * Plugin file path
     *
     * @var string
     */
    private $plugin_file;

    /**
     * Plugin basename
     * 
     * @var string
     */
    private $basename;

    /**
     * Normalized basename for consistency
     *
     * @var string
     */
    private $normalized_basename;

    /**
     * Whether plugin is active
     *
     * @var boolean
     */
    private $active;

    /**
     * Plugin data from WordPress
     *
     * @var array
     */
    private $plugin_data;

    /**
     * The GitHub API handler
     *
     * @var Preowned_Clothing_GitHub_API
     */
    private $api;

    /**
     * Debug mode flag
     *
     * @var boolean
     */
    private $debug_mode;

    /**
     * Constructor
     *
     * @param string                      $plugin_file Main plugin file path
     * @param Preowned_Clothing_GitHub_API $api         GitHub API handler instance
     * @param boolean                     $debug_mode  Debug mode flag
     */
    public function __construct($plugin_file, $api, $debug_mode = false) {
        $this->plugin_file = $plugin_file;
        $this->basename = plugin_basename($plugin_file);
        $this->active = is_plugin_active($this->basename);
        $this->api = $api;
        $this->debug_mode = $debug_mode;

        // Load plugin data
        if (!function_exists('get_plugin_data')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $this->plugin_data = get_plugin_data($this->plugin_file);

        // Create a normalized basename for consistent update checking
        $slug = sanitize_title($this->plugin_data['Name']);
        $this->normalized_basename = $slug . '/' . basename($this->plugin_file);

        if ($this->debug_mode) {
            error_log('WP Integration: Plugin basename: ' . $this->basename);
            error_log('WP Integration: Normalized basename: ' . $this->normalized_basename);
        }
    }

    /**
     * Initialize WordPress hooks
     */
    public function initialize() {
        // Main update filter - called during WordPress update checks
        add_filter('pre_set_site_transient_update_plugins', array($this, 'check_for_update'), 10, 1);
        
        // Plugin information popup
        add_filter('plugins_api', array($this, 'plugin_popup'), 10, 3);
        
        // After install hook - ensures proper file location
        add_filter('upgrader_post_install', array($this, 'after_install'), 10, 3);
        
        // Pre-download filter - adds authentication if needed
        add_filter('upgrader_pre_download', array($this, 'pre_download'), 10, 4);
        
        // Add debug info to the plugin row
        if ($this->debug_mode) {
            add_action('after_plugin_row_' . $this->basename, array($this, 'debug_info_row'), 10, 2);
        }
        
        // Force plugin to be included in update checks
        add_action('admin_init', array($this, 'force_update_check'), 5);
        
        // Daily background check for updates
        if (!wp_next_scheduled('preowned_clothing_daily_update_check')) {
            wp_schedule_event(time(), 'daily', 'preowned_clothing_daily_update_check');
        }
        add_action('preowned_clothing_daily_update_check', array($this, 'daily_check_for_update'));
    }

    /**
     * Force plugin to be included in WordPress update checks
     */
    public function force_update_check() {
        // Only run this once per page load and only in admin
        static $already_run = false;
        if ($already_run || !is_admin()) {
            return;
        }
        $already_run = true;

        // Get the update transient
        $current = get_site_transient('update_plugins');
        if (!is_object($current)) {
            $current = new stdClass();
        }

        if (!isset($current->checked) || !is_array($current->checked)) {
            $current->checked = array();
        }

        // Make sure both real and normalized basenames are present
        $current_version = $this->plugin_data['Version'];
        $current->checked[$this->basename] = $current_version;
        $current->checked[$this->normalized_basename] = $current_version;
        
        set_site_transient('update_plugins', $current);
        
        if ($this->debug_mode) {
            error_log('WP Integration: Added plugin to update check list with version ' . $current_version);
        }
    }

    /**
     * Check if update is available during transient update
     *
     * @param object $transient Update transient object
     * @return object Modified transient with update info if available
     */
    public function check_for_update($transient) {
        if (empty($transient) || !is_object($transient)) {
            if ($this->debug_mode) {
                error_log('WP Integration: Received empty transient');
            }
            return $transient;
        }

        // Check if we're running a WordPress update check
        if (!isset($transient->checked) || !is_array($transient->checked)) {
            if ($this->debug_mode) {
                error_log('WP Integration: No plugins in checked list');
            }
            return $transient;
        }

        // Get the current version from transient if available
        $current_version = $this->plugin_data['Version'];
        if (isset($transient->checked[$this->basename])) {
            $current_version = $transient->checked[$this->basename];
        } elseif (isset($transient->checked[$this->normalized_basename])) {
            $current_version = $transient->checked[$this->normalized_basename];
        }

        if ($this->debug_mode) {
            error_log('WP Integration: Checking for updates to version ' . $current_version);
        }

        try {
            // Get latest release from GitHub
            $release = $this->api->get_latest_release();
            
            // Handle errors
            if (is_wp_error($release)) {
                if ($this->debug_mode) {
                    error_log('WP Integration: Error getting release: ' . $release->get_error_message());
                }
                return $transient;
            }
            
            // Get release details
            $release_details = $this->api->extract_release_details($release);
            
            if (empty($release_details) || empty($release_details['version'])) {
                if ($this->debug_mode) {
                    error_log('WP Integration: Invalid release details or missing version');
                }
                return $transient;
            }
            
            $github_version = $release_details['version'];
            
            if ($this->debug_mode) {
                error_log("WP Integration: Comparing versions - Current: $current_version | GitHub: $github_version");
            }
            
            // Check if update is available
            if (version_compare($github_version, $current_version, '>')) {
                if ($this->debug_mode) {
                    error_log('WP Integration: Update available! Creating update object.');
                }
                
                // Create update object
                $obj = $this->create_update_object($release_details);
                
                // Add to transient using both basenames to ensure it's found
                $transient->response[$this->basename] = $obj;
                
                // Also add with normalized basename
                $norm_obj = clone $obj;
                $norm_obj->plugin = $this->normalized_basename;
                $transient->response[$this->normalized_basename] = $norm_obj;
                
                if ($this->debug_mode) {
                    error_log('WP Integration: Added update to transient for both basenames');
                }
            } else {
                if ($this->debug_mode) {
                    error_log('WP Integration: No update needed');
                }
            }
        } catch (Exception $e) {
            if ($this->debug_mode) {
                error_log('WP Integration: Exception during update check: ' . $e->getMessage());
            }
        }

        return $transient;
    }

    /**
     * Create update object for WordPress
     *
     * @param array $release_details Release details from GitHub
     * @return object Update object
     */
    private function create_update_object($release_details) {
        $obj = new stdClass();
        $obj->slug = dirname($this->basename);
        $obj->plugin = $this->basename;
        $obj->new_version = $release_details['version'];
        $obj->url = $this->plugin_data['PluginURI'];
        $obj->package = $release_details['zipball_url'];
        
        // Set compatibility info if available
        if (isset($release_details['tested'])) {
            $obj->tested = $release_details['tested'];
        }
        if (isset($release_details['requires_php'])) {
            $obj->requires_php = $release_details['requires_php'];
        }
        if (isset($release_details['requires'])) {
            $obj->requires = $release_details['requires'];
        }
        
        return $obj;
    }

    /**
     * Plugin information popup
     *
     * @param boolean $result Default result (always false)
     * @param string  $action The API action being performed
     * @param object  $args   Plugin arguments 
     * @return object|boolean Plugin information or false
     */
    public function plugin_popup($result, $action, $args) {
        // Verify this is for our plugin
        if ($action !== 'plugin_information' || 
            !isset($args->slug) || 
            $args->slug !== dirname($this->basename)) {
            return $result;
        }
        
        try {
            // Get latest release info
            $release = $this->api->get_latest_release();
            
            if (is_wp_error($release)) {
                if ($this->debug_mode) {
                    error_log('WP Integration: Error getting release for popup: ' . $release->get_error_message());
                }
                return $result;
            }
            
            // Create response object
            $plugin_info = new stdClass();
            $plugin_info->slug = dirname($this->basename);
            $plugin_info->plugin = $this->basename;
            $plugin_info->name = $this->plugin_data['Name'];
            $plugin_info->plugin_name = $this->plugin_data['Name'];
            $plugin_info->version = $this->api->format_version($release['tag_name']);
            $plugin_info->author = $this->plugin_data['AuthorName'];
            $plugin_info->homepage = $this->plugin_data['PluginURI'];
            $plugin_info->requires = isset($release['requires']) ? $release['requires'] : null;
            $plugin_info->requires_php = isset($release['requires_php']) ? $release['requires_php'] : null;
            $plugin_info->tested = isset($release['tested']) ? $release['tested'] : null;
            $plugin_info->downloaded = 0;
            $plugin_info->last_updated = isset($release['published_at']) ? $release['published_at'] : null;
            $plugin_info->sections = array();
            
            // Create sections from release body
            if (isset($release['body']) && !empty($release['body'])) {
                $this->parse_release_body($release['body'], $plugin_info);
            } else {
                // Fallback to plugin description
                $plugin_info->sections['description'] = $this->plugin_data['Description'];
            }
            
            // Add download link
            $plugin_info->download_link = $release['zipball_url'];
            
            return $plugin_info;
        } catch (Exception $e) {
            if ($this->debug_mode) {
                error_log('WP Integration: Exception during plugin popup: ' . $e->getMessage());
            }
            return $result;
        }
    }
    
    /**
     * Parse release body into sections
     *
     * @param string $body        Release body content
     * @param object $plugin_info Plugin information object to update
     */
    private function parse_release_body($body, $plugin_info) {
        // Default sections
        $plugin_info->sections['description'] = $this->plugin_data['Description'];
        $plugin_info->sections['changelog'] = $this->markdown_to_html($body);
        
        // Look for section headers in the release notes
        if (strpos($body, '## Description') !== false) {
            $parts = explode('## Description', $body, 2);
            if (count($parts) > 1) {
                $desc_changelog = $parts[1];
                
                // Check for changelog section
                if (strpos($desc_changelog, '## Changelog') !== false) {
                    $desc_parts = explode('## Changelog', $desc_changelog, 2);
                    $plugin_info->sections['description'] = $this->markdown_to_html($desc_parts[0]);
                    $plugin_info->sections['changelog'] = $this->markdown_to_html($desc_parts[1]);
                } else {
                    // Just description, no explicit changelog
                    $plugin_info->sections['description'] = $this->markdown_to_html($desc_changelog);
                }
            }
        }
        
        // Look for Installation section
        if (strpos($body, '## Installation') !== false) {
            $parts = explode('## Installation', $body, 2);
            if (count($parts) > 1) {
                $install_parts = explode('##', $parts[1], 2);
                $plugin_info->sections['installation'] = $this->markdown_to_html($install_parts[0]);
            }
        }
        
        // Look for FAQ section
        if (strpos($body, '## FAQ') !== false) {
            $parts = explode('## FAQ', $body, 2);
            if (count($parts) > 1) {
                $faq_parts = explode('##', $parts[1], 2);
                $plugin_info->sections['faq'] = $this->markdown_to_html($faq_parts[0]);
            }
        }
    }
    
    /**
     * Simple markdown to HTML conversion
     *
     * @param string $markdown Markdown content
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
        
        // Wrap lists in ul tags
        $html = preg_replace('/((?:<li>.*?<\/li>\n)+)/s', '<ul>$1</ul>', $html);
        
        // Convert links
        $html = preg_replace('/\[(.*?)\]\((.*?)\)/', '<a href="$2" target="_blank">$1</a>', $html);
        
        // Convert bold and italic
        $html = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $html);
        $html = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $html);
        
        // Convert code blocks
        $html = preg_replace('/`(.*?)`/', '<code>$1</code>', $html);
        
        // Convert paragraphs (simplified)
        $html = '<p>' . str_replace("\n\n", '</p><p>', $html) . '</p>';
        
        return $html;
    }
    
    /**
     * After installation hook
     *
     * @param boolean $response Installation response
     * @param array   $extras   Extra data
     * @param array   $result   Installation result data
     * @return array Modified result
     */
    public function after_install($response, $extras, $result) {
        global $wp_filesystem;
        
        // Only process for our plugin
        if (!isset($extras['plugin']) || $extras['plugin'] !== $this->basename) {
            return $result;
        }
        
        // Get install directory
        $plugin_dir = dirname($this->plugin_file);
        $wp_filesystem->move($result['destination'], $plugin_dir);
        $result['destination'] = $plugin_dir;
        
        // Reactivate plugin if it was active
        if ($this->active) {
            activate_plugin($this->basename);
        }
        
        return $result;
    }
    
    /**
     * Pre-download hook to add authentication
     *
     * @param boolean|WP_Error $response Response
     * @param string           $package  Download URL
     * @param WP_Upgrader      $upgrader Upgrader instance
     * @return boolean|WP_Error|string Modified response or download path
     */
    public function pre_download($response, $package, $upgrader) {
        if (!is_a($upgrader, 'Plugin_Upgrader') || empty($package)) {
            return $response;
        }
        
        // Get plugin info - we might be updating this plugin
        if (!isset($upgrader->skin->plugin_info)) {
            return $response;
        }
        
        $plugin_info = $upgrader->skin->plugin_info;
        
        // Only process our plugin
        if (!isset($plugin_info['slug']) || $plugin_info['slug'] !== dirname($this->basename)) {
            return $response;
        }
        
        // Get GitHub API instance and log method call
        if ($this->debug_mode) {
            error_log('WP Integration: Pre-download hook called for package: ' . $package);
        }
        
        // Download the package
        try {
            $download_file = $this->api->download_release($package);
            
            if (is_wp_error($download_file)) {
                if ($this->debug_mode) {
                    error_log('WP Integration: Download error: ' . $download_file->get_error_message());
                }
                return $download_file;
            }
            
            if ($this->debug_mode) {
                error_log('WP Integration: Download successful to: ' . $download_file);
            }
            
            return $download_file;
        } catch (Exception $e) {
            if ($this->debug_mode) {
                error_log('WP Integration: Exception during download: ' . $e->getMessage());
            }
            
            return new WP_Error(
                'download_failed', 
                'Error downloading update package: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Display debug information in plugin row
     *
     * @param string $file        Plugin file
     * @param array  $plugin_data Plugin data
     */
    public function debug_info_row($file, $plugin_data) {
        try {
            // Get latest release from GitHub
            $release = $this->api->get_latest_release();
            
            // Setup default values
            $current_version = $plugin_data['Version'];
            $github_version = 'Unknown';
            $update_available = 'No';
            
            // Check if we got a valid response
            if (!is_wp_error($release) && isset($release['tag_name'])) {
                $github_version = $this->api->format_version($release['tag_name']);
                
                // Check if update is available
                if (version_compare($github_version, $current_version, '>')) {
                    $update_available = '<span style="color:green;font-weight:bold;">Yes</span>';
                }
            } else if (is_wp_error($release)) {
                $github_version = 'Error: ' . $release->get_error_message();
            }
            
            // Get last check time
            $last_check = get_option('preowned_clothing_last_update_check');
            $last_check_str = $last_check ? date('Y-m-d H:i:s', $last_check) : 'Never';
            
            // Output debug row
            echo '<tr class="plugin-update-tr"><td colspan="3" class="plugin-update">';
            echo '<div class="update-message notice inline notice-warning notice-alt">';
            echo '<p><strong>Debug Info:</strong> ';
            echo 'Current: ' . esc_html($current_version) . ' | ';
            echo 'GitHub: ' . esc_html($github_version) . ' | ';
            echo 'Update: ' . $update_available . ' | ';
            echo 'Last check: ' . esc_html($last_check_str);
            echo ' <a href="#" class="pcf-toggle-debug">Show More</a>';
            echo '</p>';
            
            echo '<div class="pcf-extended-debug" style="display:none;">';
            echo '<p><strong>Technical Info:</strong></p>';
            echo '<ul>';
            echo '<li>Plugin File: ' . esc_html($this->plugin_file) . '</li>';
            echo '<li>Basename: ' . esc_html($this->basename) . '</li>';
            echo '<li>Normalized Basename: ' . esc_html($this->normalized_basename) . '</li>';
            
            // Check if plugin is in update transient
            $update_transient = get_site_transient('update_plugins');
            if (is_object($update_transient)) {
                $in_checked = isset($update_transient->checked[$this->basename]) || 
                              isset($update_transient->checked[$this->normalized_basename]);
                              
                $in_response = isset($update_transient->response[$this->basename]) || 
                               isset($update_transient->response[$this->normalized_basename]);
                               
                echo '<li>In update checked: ' . ($in_checked ? 'Yes' : 'No') . '</li>';
                echo '<li>In update response: ' . ($in_response ? 'Yes' : 'No') . '</li>';
            }
            
            echo '</ul>';
            echo '</div>';
            
            echo '</div>';
            echo '</td></tr>';
            
            // Add JavaScript to toggle debug info
            echo '<script>
                jQuery(document).ready(function($) {
                    $(".pcf-toggle-debug").click(function(e) {
                        e.preventDefault();
                        var $debug = $(this).closest(".update-message").find(".pcf-extended-debug");
                        $debug.toggle();
                        $(this).text($debug.is(":visible") ? "Hide" : "Show More");
                    });
                });
            </script>';
            
        } catch (Exception $e) {
            if ($this->debug_mode) {
                error_log('WP Integration: Exception in debug row: ' . $e->getMessage());
            }
        }
    }
    
    /**
     * Daily background update check
     */
    public function daily_check_for_update() {
        if ($this->debug_mode) {
            error_log('WP Integration: Running daily update check');
        }
        
        try {
            // Force refresh of GitHub data
            $release = $this->api->get_latest_release(true);
            
            // Log result
            if (is_wp_error($release)) {
                if ($this->debug_mode) {
                    error_log('WP Integration: Daily check failed: ' . $release->get_error_message());
                }
                return;
            }
            
            update_option('preowned_clothing_last_update_check', time());
            
            // Compare versions
            $current_version = $this->plugin_data['Version'];
            $github_version = $this->api->format_version($release['tag_name']);
            
            if ($this->debug_mode) {
                error_log("WP Integration: Daily check result - Current: $current_version, GitHub: $github_version");
                
                if (version_compare($github_version, $current_version, '>')) {
                    error_log('WP Integration: Update available!');
                } else {
                    error_log('WP Integration: No update needed.');
                }
            }
            
            // Force WordPress to recheck for updates
            delete_site_transient('update_plugins');
            
        } catch (Exception $e) {
            if ($this->debug_mode) {
                error_log('WP Integration: Exception during daily check: ' . $e->getMessage());
            }
        }
    }
}
