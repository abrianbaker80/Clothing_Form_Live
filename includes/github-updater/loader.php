<?php
/**
 * GitHub Updater Loader
 *
 * Main entry point for the GitHub updater component.
 * Conditionally loads other components as needed.
 *
 * @package PreownedClothingForm
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Define updater constants
define('PCF_UPDATER_VERSION', '1.0.3');
define('PCF_UPDATER_DIR', plugin_dir_path(__FILE__));
define('PCF_UPDATER_URL', plugin_dir_url(__FILE__));

/**
 * Check if we can run the updater (avoid conflicts with other plugins)
 * 
 * @return boolean True if we can run the updater
 */
if (!function_exists('preowned_clothing_can_run_updater')) {
    function preowned_clothing_can_run_updater() {
        global $preowned_clothing_gh_updater_running;
        
        // Always allow our own updater to run after we've set the flag
        if ($preowned_clothing_gh_updater_running === true) {
            return true;
        }
        
        // Check for common GitHub updater classes from other plugins or themes
        $conflicting_classes = [
            'GitHub_Updater', 
            'WP_GitHub_Updater'
        ];
        
        foreach ($conflicting_classes as $class) {
            if (class_exists($class, false)) { // false = don't autoload
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('GitHub Updater: External updater class detected: ' . $class);
                }
                return false;
            }
        }
        
        // No conflicts detected
        return true;
    }
}

/**
 * Initialize the GitHub updater system
 *
 * @param string $plugin_file Main plugin file path
 * @param array $config Optional configuration parameters
 */
function preowned_clothing_init_updater($plugin_file, $config = array()) {
    global $preowned_clothing_gh_updater_running;
    
    // Only allow initialization once per page load
    static $initialized = false;
    if ($initialized) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('GitHub Updater: Already initialized this page load, skipping.');
        }
        return;
    }
    
    // Check if we can safely run the updater
    if (!preowned_clothing_can_run_updater()) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('GitHub Updater: Another updater is already active. Not loading our updater.');
        }
        return;
    }
    
    // Set a flag to indicate initialization has occurred
    $initialized = true;
    $preowned_clothing_gh_updater_running = true;
    
    // Only load in admin or during AJAX/cron
    if (!is_admin() && !wp_doing_ajax() && !defined('DOING_CRON')) {
        return;
    }

    // Validate plugin file
    if (!file_exists($plugin_file)) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('GitHub Updater: Invalid plugin file path: ' . $plugin_file);
        }
        return;
    }

    // Ensure required WordPress functions are available
    if (!function_exists('get_plugin_data')) {
        if (function_exists('is_admin') && is_admin()) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        } else {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('GitHub Updater: Could not load plugin.php in non-admin context');
            }
            return;
        }
    }

    // Set default configuration
    $default_config = array(
        'username' => get_option('preowned_clothing_github_username', 'abrianbaker80'),
        'repository' => get_option('preowned_clothing_github_repository', 'Clothing_Form'),
        'token' => get_option('preowned_clothing_github_token', ''),
        'debug' => defined('WP_DEBUG') && WP_DEBUG,
    );
    
    // Merge with user config
    $config = wp_parse_args($config, $default_config);
    
    try {
        // Check if core updater file exists
        $updater_file = PCF_UPDATER_DIR . 'class-updater.php';
        if (!file_exists($updater_file)) {
            throw new Exception('Core updater file not found: ' . $updater_file);
        }
        
        // Load core updater class
        require_once $updater_file;
        
        if (!class_exists('Preowned_Clothing_GitHub_Updater')) {
            throw new Exception('Updater class not found after including the file');
        }
        
        // Initialize updater
        $updater = new Preowned_Clothing_GitHub_Updater($plugin_file);
        $updater->set_username($config['username']);
        $updater->set_repository($config['repository']);
        
        if (!empty($config['token'])) {
            $updater->authorize($config['token']);
        }
        
        $updater->initialize();
        
        // Store updater instance for potential future reference
        global $preowned_clothing_updater;
        $preowned_clothing_updater = $updater;
        
        // Register the admin interface only when in admin
        if (is_admin() && !wp_doing_ajax()) {
            $admin_file = PCF_UPDATER_DIR . 'admin/class-admin-page.php';
            if (file_exists($admin_file)) {
                require_once $admin_file;
                if (class_exists('Preowned_Clothing_GitHub_Admin')) {
                    $admin = new Preowned_Clothing_GitHub_Admin($updater);
                    $admin->initialize();
                } else {
                    throw new Exception('Admin class not found after including the file');
                }
            } else {
                if ($config['debug']) {
                    error_log('GitHub Updater: Admin page file not found: ' . $admin_file);
                }
            }
        }
        
        // Log initialization if debugging is enabled
        if ($config['debug']) {
            error_log('GitHub Updater: Successfully initialized for ' . $config['username'] . '/' . $config['repository']);
        }
        
    } catch (Exception $e) {
        // Log error but don't crash
        if ($config['debug']) {
            error_log('GitHub Updater: Error: ' . $e->getMessage());
        }
    }
}

/**
 * Register hooks to ensure the updater is initialized at the right time
 *
 * @param string $plugin_file Path to the main plugin file
 * @param array $config Optional configuration parameters
 */
function preowned_clothing_register_updater_hooks($plugin_file, $config = array()) {
    // We use admin_init with priority 5 to ensure it runs before 
    // other GitHub updater implementations, helping avoid conflicts
    add_action('admin_init', function() use ($plugin_file, $config) {
        preowned_clothing_init_updater($plugin_file, $config);
    }, 5);
    
    // Also initialize during cron runs
    add_action('wp_loaded', function() use ($plugin_file, $config) {
        if (defined('DOING_CRON') && DOING_CRON) {
            preowned_clothing_init_updater($plugin_file, $config);
        }
    }, 5);
}

/**
 * Get plugin data with caching
 * 
 * @param string $plugin_file Path to plugin file
 * @return array Plugin data
 */
function preowned_clothing_get_plugin_data($plugin_file) {
    static $plugin_data = array();
    
    if (!isset($plugin_data[$plugin_file])) {
        if (!function_exists('get_plugin_data')) {
            if (function_exists('is_admin') && is_admin()) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            } else {
                // Return empty array if we can't load plugin data
                return array('Name' => '', 'Version' => '');
            }
        }
        
        if (file_exists($plugin_file)) {
            $plugin_data[$plugin_file] = get_plugin_data($plugin_file);
        } else {
            // Return empty array for invalid files
            $plugin_data[$plugin_file] = array('Name' => '', 'Version' => '');
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('GitHub Updater: Invalid plugin file in get_plugin_data: ' . $plugin_file);
            }
        }
    }
    
    return $plugin_data[$plugin_file];
}

/**
 * Helper to get normalized plugin basename
 * 
 * @param string $plugin_file Plugin file path
 * @return string Normalized basename for consistent update checking
 */
function preowned_clothing_get_normalized_basename($plugin_file) {
    $plugin_data = preowned_clothing_get_plugin_data($plugin_file);
    $slug = !empty($plugin_data['Name']) ? sanitize_title($plugin_data['Name']) : '';
    $filename = basename($plugin_file);
    return $slug . '/' . $filename;
}

/**
 * Force WordPress to recognize our plugin in update checks
 * Renamed to avoid conflict with function in github-updater.php
 */
function preowned_clothing_loader_force_update_check() {
    // Only run this once per page load and only in admin
    static $already_ran = false;
    if ($already_ran || !is_admin()) {
        return;
    }
    $already_ran = true;
    
    // Get plugin file
    global $preowned_clothing_updater;
    if (empty($preowned_clothing_updater) || !method_exists($preowned_clothing_updater, 'get_plugin_file')) {
        return;
    }
    
    $plugin_file = $preowned_clothing_updater->get_plugin_file();
    if (!file_exists($plugin_file)) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('GitHub Updater: Plugin file not found: ' . $plugin_file);
        }
        return;
    }
    
    $plugin_data = preowned_clothing_get_plugin_data($plugin_file);
    if (empty($plugin_data['Version'])) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('GitHub Updater: Could not determine plugin version');
        }
        return;
    }
    
    $plugin_basename = plugin_basename($plugin_file);
    $normalized_basename = preowned_clothing_get_normalized_basename($plugin_file);
    
    // Update the update_plugins transient
    $current = get_site_transient('update_plugins');
    if (!is_object($current)) {
        $current = new stdClass;
    }
    
    if (!isset($current->checked) || !is_array($current->checked)) {
        $current->checked = array();
    }
    
    // Add our plugin with both normal and normalized basenames
    $current->checked[$plugin_basename] = $plugin_data['Version'];
    $current->checked[$normalized_basename] = $plugin_data['Version'];
    
    set_site_transient('update_plugins', $current);
    
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('GitHub Updater: Added plugin to update check list: ' . $plugin_basename);
        error_log('GitHub Updater: Also added normalized basename: ' . $normalized_basename);
    }
}
add_action('admin_init', 'preowned_clothing_loader_force_update_check', 5);

/**
 * Force clear all GitHub updater caches to ensure fresh update checks
 */
function preowned_clothing_clear_update_caches() {
    global $preowned_clothing_updater;
    
    // Clear WordPress transient
    delete_site_transient('update_plugins');
    
    // Clear GitHub-specific options and transients
    $username = get_option('preowned_clothing_github_username', 'abrianbaker80');
    $repository = get_option('preowned_clothing_github_repository', 'Clothing_Form');
    delete_transient('preowned_clothing_github_release_' . $username . '_' . $repository);
    delete_option('preowned_clothing_github_response');
    delete_option('preowned_clothing_last_update_check');
    
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('GitHub Updater: Manually cleared all caches');
    }
    
    // Force WordPress to check for updates
    wp_clean_plugins_cache(true);
    wp_update_plugins();
}

// Register the cache clearing function
add_action('init', function() {
    // Check for admin request to clear caches
    if (is_admin() && isset($_GET['preowned_clothing_clear_caches']) && current_user_can('update_plugins')) {
        preowned_clothing_clear_update_caches();
        
        // Redirect back
        $redirect_url = remove_query_arg('preowned_clothing_clear_caches');
        wp_redirect(add_query_arg('cache_cleared', '1', $redirect_url));
        exit;
    }
});

// Add notice when cache is cleared
add_action('admin_notices', function() {
    if (isset($_GET['cache_cleared'])) {
        echo '<div class="notice notice-success is-dismissible"><p>GitHub updater caches cleared. Update checks will now use fresh data.</p></div>';
    }
});
