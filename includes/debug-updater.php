<?php
/**
 * GitHub Updater Debug Tool
 * 
 * Special debugging tool to help diagnose folder name issues during GitHub updates
 * 
 * @package PreownedClothingForm
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Debug WordPress plugin paths and updater system
 */
class PCF_Path_Debug {
    /**
     * Current plugin info
     */
    private $plugin_file;
    private $plugin_basename;
    private $plugin_dir;
    private $debug_mode;
    
    /**
     * Initialize the debug tool
     * 
     * @param string $plugin_file The main plugin file
     */
    public function __construct($plugin_file) {
        $this->plugin_file = $plugin_file;
        $this->plugin_basename = plugin_basename($plugin_file);
        $this->plugin_dir = dirname($plugin_file);
        $this->debug_mode = defined('WP_DEBUG') && WP_DEBUG;
        
        // Register hooks
        add_action('admin_menu', array($this, 'add_debug_menu'));
        add_action('admin_init', array($this, 'handle_path_corrections'));
        add_filter('upgrader_source_selection', array($this, 'fix_directory_name'), 10, 4);
        
        // Log startup when debugging
        if ($this->debug_mode) {
            error_log('PCF_Path_Debug: Initialized for ' . $this->plugin_basename);
        }
    }
    
    /**
     * Add a debug menu under tools
     */
    public function add_debug_menu() {
        add_management_page(
            'Plugin Path Debugger',
            'Path Debugger',
            'manage_options',
            'pcf-path-debugger',
            array($this, 'render_debug_page')
        );
    }
    
    /**
     * Handle path correction actions
     */
    public function handle_path_corrections() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        if (isset($_POST['pcf_normalize_plugin_path']) && 
            isset($_POST['pcf_path_nonce']) && 
            wp_verify_nonce($_POST['pcf_path_nonce'], 'pcf_path_correction')) {
            
            $this->normalize_plugin_path();
            
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible">';
                echo '<p>Plugin path normalization attempted. Please verify plugin functionality.</p>';
                echo '</div>';
            });
        }
    }
    
    /**
     * Normalize plugin path by correcting the directory name
     */
    private function normalize_plugin_path() {
        global $wp_filesystem;
        
        // Initialize the WordPress filesystem
        if (!function_exists('WP_Filesystem')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        WP_Filesystem();
        
        // Get the current directory name and the desired directory name
        $current_dir = basename(dirname($this->plugin_file));
        $desired_dir = 'Clothing_Form'; // Normalize to the standard name
        
        // Get the parent directory
        $parent_dir = dirname(dirname($this->plugin_file));
        
        // Target directories
        $source_dir = $parent_dir . '/' . $current_dir;
        $target_dir = $parent_dir . '/' . $desired_dir;
        
        // Check if source exists and target doesn't
        if ($wp_filesystem->exists($source_dir) && !$wp_filesystem->exists($target_dir)) {
            // Back up existing plugin
            $backup_dir = $parent_dir . '/' . $current_dir . '_backup_' . time();
            $wp_filesystem->copy($source_dir, $backup_dir, true); // Recursive copy
            
            // Copy files to the normalized directory 
            $wp_filesystem->mkdir($target_dir);
            $this->recursive_copy($source_dir, $target_dir);
            
            // Log operation
            if ($this->debug_mode) {
                error_log('PCF_Path_Debug: Copied plugin from ' . $source_dir . ' to ' . $target_dir);
                error_log('PCF_Path_Debug: Backup created at ' . $backup_dir);
            }
        } else {
            if ($this->debug_mode) {
                error_log('PCF_Path_Debug: Cannot normalize - source or target issue');
                error_log('PCF_Path_Debug: Source exists: ' . ($wp_filesystem->exists($source_dir) ? 'Yes' : 'No'));
                error_log('PCF_Path_Debug: Target exists: ' . ($wp_filesystem->exists($target_dir) ? 'Yes' : 'No'));
            }
        }
    }
    
    /**
     * Recursive copy function
     * 
     * @param string $src Source directory
     * @param string $dst Destination directory
     */
    private function recursive_copy($src, $dst) {
        global $wp_filesystem;
        
        $dir = opendir($src);
        @mkdir($dst);
        
        while (($file = readdir($dir)) !== false) {
            if (($file != '.') && ($file != '..')) {
                if (is_dir($src . '/' . $file)) {
                    $this->recursive_copy($src . '/' . $file, $dst . '/' . $file);
                } else {
                    $wp_filesystem->copy($src . '/' . $file, $dst . '/' . $file);
                }
            }
        }
        closedir($dir);
    }
    
    /**
     * Render the debug page
     */
    public function render_debug_page() {
        global $wp_filesystem;
        
        // Initialize WordPress filesystem
        if (!function_exists('WP_Filesystem')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        WP_Filesystem();
        
        // Get update transient
        $update_transient = get_site_transient('update_plugins');
        $standard_basename = 'Clothing_Form/preowned-clothing-form.php';
        
        // Get plugin data
        $plugin_data = get_plugin_data($this->plugin_file);
        ?>
        <div class="wrap">
            <h1>Plugin Path Debugger</h1>
            
            <div class="card">
                <h2>Plugin Path Information</h2>
                <table class="widefat striped">
                    <tr>
                        <th>Plugin File</th>
                        <td><?php echo esc_html($this->plugin_file); ?></td>
                    </tr>
                    <tr>
                        <th>Plugin Basename</th>
                        <td><?php echo esc_html($this->plugin_basename); ?></td>
                    </tr>
                    <tr>
                        <th>Directory Name</th>
                        <td><?php echo esc_html(basename(dirname($this->plugin_file))); ?></td>
                    </tr>
                    <tr>
                        <th>Expected Dir Name</th>
                        <td>Clothing_Form</td>
                    </tr>
                    <tr>
                        <th>Plugin Version</th>
                        <td><?php echo esc_html($plugin_data['Version']); ?></td>
                    </tr>
                    <tr>
                        <th>Path Mismatch</th>
                        <td><?php echo $this->plugin_basename !== $standard_basename ? '<strong style="color:red">Yes</strong>' : '<strong style="color:green">No</strong>'; ?></td>
                    </tr>
                </table>
            </div>
            
            <div class="card" style="margin-top: 20px;">
                <h2>WordPress Update System</h2>
                <table class="widefat striped">
                    <tr>
                        <th>Update Transient</th>
                        <td><?php echo is_object($update_transient) ? 'Exists' : 'Not Found'; ?></td>
                    </tr>
                    <tr>
                        <th>Actual in Checked Array</th>
                        <td><?php echo isset($update_transient->checked[$this->plugin_basename]) ? 'Yes' : 'No'; ?></td>
                    </tr>
                    <tr>
                        <th>Normalized in Checked Array</th>
                        <td><?php echo isset($update_transient->checked[$standard_basename]) ? 'Yes' : 'No'; ?></td>
                    </tr>
                    <tr>
                        <th>Actual in Response Array</th>
                        <td><?php echo isset($update_transient->response[$this->plugin_basename]) ? 'Yes' : 'No'; ?></td>
                    </tr>
                    <tr>
                        <th>Normalized in Response Array</th>
                        <td><?php echo isset($update_transient->response[$standard_basename]) ? 'Yes' : 'No'; ?></td>
                    </tr>
                </table>
            </div>
            
            <div class="card" style="margin-top: 20px;">
                <h2>Directory Structure</h2>
                <table class="widefat striped">
                    <tr>
                        <th>Actual Directory</th>
                        <td><?php echo $wp_filesystem->exists(dirname($this->plugin_file)) ? 'Exists' : 'Missing'; ?></td>
                    </tr>
                    <tr>
                        <th>Normalized Directory</th>
                        <td><?php 
                            $normalized_dir = WP_PLUGIN_DIR . '/Clothing_Form';
                            echo $wp_filesystem->exists($normalized_dir) ? 'Exists' : 'Missing'; 
                        ?></td>
                    </tr>
                    <tr>
                        <th>Main Plugin File in Actual</th>
                        <td><?php echo $wp_filesystem->exists($this->plugin_file) ? 'Exists' : 'Missing'; ?></td>
                    </tr>
                    <tr>
                        <th>Main Plugin File in Normalized</th>
                        <td><?php 
                            $normalized_file = WP_PLUGIN_DIR . '/Clothing_Form/preowned-clothing-form.php';
                            echo $wp_filesystem->exists($normalized_file) ? 'Exists' : 'Missing'; 
                        ?></td>
                    </tr>
                </table>
            </div>
            
            <?php if ($this->plugin_basename !== $standard_basename): ?>
            <div class="card" style="margin-top: 20px; background-color: #fef8ee; border-left: 4px solid #f0b849;">
                <h2>Path Correction</h2>
                <p><strong>Warning:</strong> Your plugin is installed in a non-standard directory which may cause update issues.</p>
                <p>Current path: <code><?php echo esc_html($this->plugin_basename); ?></code></p>
                <p>Expected path: <code><?php echo esc_html($standard_basename); ?></code></p>
                
                <div style="margin-top: 20px;">
                    <form method="post" action="">
                        <?php wp_nonce_field('pcf_path_correction', 'pcf_path_nonce'); ?>
                        <button type="submit" name="pcf_normalize_plugin_path" class="button button-primary" 
                                onclick="return confirm('This will attempt to normalize your plugin directory structure. Do you want to proceed?');">
                            Attempt Path Normalization
                        </button>
                    </form>
                </div>
                
                <div style="margin-top: 15px;">
                    <p><strong>Note:</strong> This operation will:</p>
                    <ol>
                        <li>Create a backup of your current plugin installation</li>
                        <li>Create a new directory with the normalized name</li>
                        <li>Copy your plugin files to the new directory</li>
                    </ol>
                    <p><em>The old directory will not be deleted automatically for safety reasons.</em></p>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="card" style="margin-top: 20px;">
                <h2>Updater Hooks</h2>
                <p>The following hooks ensure smooth updates regardless of directory structure:</p>
                <pre style="background: #f5f5f5; padding: 10px; overflow: auto;">
/**
 * Fix directory name after unzip by removing version number
 * 
 * @param string $source Source directory
 * @return string Fixed source directory
 */
public function fix_directory_name($source, $remote_source, $upgrader, $args = []) {
    global $wp_filesystem;
    
    // Only process our plugin
    if (!is_object($upgrader->skin) || !isset($upgrader->skin->plugin) || 
        $upgrader->skin->plugin !== $this->plugin_basename) {
        return $source;
    }
    
    $source_name = basename($source);
    
    // Check if source folder contains version number (like "plugin-name-1.2.3")
    if (preg_match('/-[0-9]+\.[0-9]+/', $source_name)) {
        // If the folder name includes the version, normalize it
        $corrected_name = 'Clothing_Form';
        $corrected_source = str_replace($source_name, $corrected_name, $source);
        
        // Delete the destination folder if it exists
        if ($wp_filesystem->exists($corrected_source)) {
            $wp_filesystem->delete($corrected_source, true);
        }
        
        // Rename to the corrected directory
        $renamed = $wp_filesystem->move($source, $corrected_source);
        if ($renamed) {
            return $corrected_source;
        }
    }
    
    return $source;
}</pre>
            </div>
            
            <div class="card" style="margin-top: 20px;">
                <h2>Manual Fix Steps</h2>
                <p>If the automatic fix doesn't work, you can manually fix the plugin directory structure:</p>
                <ol>
                    <li>Deactivate the plugin</li>
                    <li>Rename the plugin folder from <code><?php echo esc_html(basename(dirname($this->plugin_file))); ?></code> to <code>Clothing_Form</code></li>
                    <li>Reactivate the plugin</li>
                    <li>Update WordPress permalink settings</li>
                </ol>
                <p>This ensures that future updates will work correctly.</p>
            </div>
        </div>
        
        <style>
            .card {
                background: #fff;
                border: 1px solid #ccd0d4;
                box-shadow: 0 1px 1px rgba(0,0,0,.04);
                padding: 15px;
                margin-bottom: 20px;
            }
            .card h2 {
                margin-top: 0;
            }
        </style>
        <?php
    }
    
    /**
     * Fix directory name during updates
     */
    public function fix_directory_name($source, $remote_source, $upgrader, $args = []) {
        global $wp_filesystem;
        
        // Check if this is a plugin installation
        if (!is_object($upgrader->skin) || !is_a($upgrader->skin, 'Plugin_Upgrader_Skin')) {
            return $source;
        }
        
        // Try to detect our plugin update
        $is_our_plugin = false;
        
        // Check for our plugin in various ways
        if (isset($upgrader->skin->plugin) && $upgrader->skin->plugin === $this->plugin_basename) {
            $is_our_plugin = true;
        } elseif (isset($args['plugin']) && $args['plugin'] === $this->plugin_basename) {
            $is_our_plugin = true;
        } elseif (strpos(basename($source), 'Clothing_Form') !== false) {
            $is_our_plugin = true;
        } elseif (strpos(basename($source), 'clothing-form') !== false) {
            $is_our_plugin = true;
        }
        
        // Exit if not our plugin
        if (!$is_our_plugin) {
            return $source;
        }
        
        // Get the source directory name
        $source_name = basename($source);
        
        // Define the desired name
        $corrected_name = 'Clothing_Form';
        
        // Log what we're doing
        if ($this->debug_mode) {
            error_log('PCF_Path_Debug: Upgrader source name: ' . $source_name);
            error_log('PCF_Path_Debug: Will normalize to: ' . $corrected_name);
        }
        
        // Normalize the directory name
        if ($source_name !== $corrected_name) {
            $corrected_source = str_replace($source_name, $corrected_name, $source);
            
            if ($this->debug_mode) {
                error_log('PCF_Path_Debug: Source: ' . $source);
                error_log('PCF_Path_Debug: Target: ' . $corrected_source);
            }
            
            // Remove the target dir if it exists
            if ($wp_filesystem->exists($corrected_source)) {
                $wp_filesystem->delete($corrected_source, true);
                if ($this->debug_mode) {
                    error_log('PCF_Path_Debug: Deleted existing: ' . $corrected_source);
                }
            }
            
            // Rename the directory
            $renamed = $wp_filesystem->move($source, $corrected_source);
            if ($renamed) {
                if ($this->debug_mode) {
                    error_log('PCF_Path_Debug: Successfully renamed to: ' . $corrected_source);
                }
                return $corrected_source;
            } else {
                if ($this->debug_mode) {
                    error_log('PCF_Path_Debug: Failed to rename to: ' . $corrected_source);
                }
            }
        }
        
        return $source;
    }
    
    /**
     * Update the plugin's checked version in the transient
     * This ensures the plugin is correctly tracked even with folder name issues
     */
    public static function update_transient_checked() {
        $update_transient = get_site_transient('update_plugins');
        if (!is_object($update_transient)) {
            return;
        }
        
        // Get all instances where our plugin might be registered
        $actual_basename = false;
        $standard_basename = 'Clothing_Form/preowned-clothing-form.php';
        $version_number = false;
        
        // Find the actual plugin install
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $all_plugins = get_plugins();
        
        foreach ($all_plugins as $path => $data) {
            // Check for our plugin by name
            if (strpos($path, 'preowned-clothing-form.php') !== false) {
                $actual_basename = $path;
                $version_number = $data['Version'];
                break;
            }
        }
        
        if ($actual_basename && $version_number) {
            // Update both the actual and standard paths with the same version
            if (!isset($update_transient->checked)) {
                $update_transient->checked = array();
            }
            
            $update_transient->checked[$actual_basename] = $version_number;
            $update_transient->checked[$standard_basename] = $version_number;
            
            // Save the updated transient
            set_site_transient('update_plugins', $update_transient);
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('PCF_Path_Debug: Updated transient with version: ' . $version_number);
                error_log('PCF_Path_Debug: Actual path: ' . $actual_basename);
                error_log('PCF_Path_Debug: Standard path: ' . $standard_basename);
            }
        }
    }
}

// Initialize the debug tool if we're in admin
if (is_admin()) {
    // Find our plugin file
    $plugin_file = null;
    
    // Plugin may be in different folders, try to locate it
    $possible_folders = array(
        'Clothing_Form_Live-2.5.9.4',
        'Clothing_Form_Live',
        'Clothing_Form',
    );
    
    foreach ($possible_folders as $folder) {
        $test_file = WP_PLUGIN_DIR . '/' . $folder . '/preowned-clothing-form.php';
        if (file_exists($test_file)) {
            $plugin_file = $test_file;
            break;
        }
    }
    
    // If found, initialize the debug tool
    if ($plugin_file) {
        $path_debug = new PCF_Path_Debug($plugin_file);
        
        // Update the transient to fix plugin tracking
        add_action('admin_init', array('PCF_Path_Debug', 'update_transient_checked'), 20);
    }
}

// Also create a standalone function for easier access
if (!function_exists('pcf_debug_path')) {
    function pcf_debug_path() {
        $url = admin_url('tools.php?page=pcf-path-debugger');
        echo '<div class="notice notice-info">';
        echo '<p>Having issues with plugin updates? Visit the <a href="' . esc_url($url) . '">Path Debugger</a></p>';
        echo '</div>';
    }
    add_action('admin_notices', 'pcf_debug_path');
}
