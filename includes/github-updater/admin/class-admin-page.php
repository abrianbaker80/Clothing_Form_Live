<?php
/**
 * GitHub Updater Admin Page
 *
 * Handles the admin interface for the GitHub updater
 * 
 * @package PreownedClothingForm
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * GitHub Updater Admin Class
 */
class Preowned_Clothing_GitHub_Admin {
    /**
     * Instance of GitHub updater
     *
     * @var object
     */
    private $updater;
    
    /**
     * Plugin data
     *
     * @var array
     */
    private $plugin_data;
    
    /**
     * API instance
     * 
     * @var Preowned_Clothing_GitHub_API
     */
    private $api;
    
    /**
     * Whether debug mode is enabled
     *
     * @var boolean
     */
    private $debug_mode;
    
    /**
     * Settings fields
     * 
     * @var array
     */
    private $settings_fields;
    
    /**
     * Constructor
     *
     * @param object $updater GitHub updater instance
     */
    public function __construct($updater) {
        $this->updater = $updater;
        $this->debug_mode = defined('WP_DEBUG') && WP_DEBUG;

        // Get plugin file from updater
        $plugin_file = $this->updater->file;
        
        // Get plugin data
        if (!function_exists('get_plugin_data')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $this->plugin_data = get_plugin_data($plugin_file);
        
        // Define settings fields
        $this->settings_fields = array(
            'username' => array(
                'label' => 'GitHub Username',
                'type' => 'text',
                'default' => 'abrianbaker80',
                'description' => 'The GitHub username that owns the repository (e.g., <code>abrianbaker80</code>)'
            ),
            'repository' => array(
                'label' => 'Repository Name',
                'type' => 'text',
                'default' => 'Clothing_Form',
                'description' => 'The exact name of your GitHub repository (e.g., <code>Clothing_Form</code>). This is case-sensitive.'
            ),
            'token' => array(
                'label' => 'Personal Access Token',
                'type' => 'password',
                'default' => '',
                'description' => 'Required for private repositories. <a href="https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/creating-a-personal-access-token" target="_blank">Learn how to create a token</a>.'
            ),
            'branch' => array(
                'label' => 'Branch',
                'type' => 'text',
                'default' => 'main',
                'description' => 'The branch to fetch updates from (e.g., main, master)'
            )
        );
        
        // Initialize the API
        $this->init_api();
    }
    
    /**
     * Initialize the API instance
     */
    private function init_api() {
        if (!class_exists('Preowned_Clothing_GitHub_API')) {
            require_once dirname(dirname(__FILE__)) . '/class-api.php';
        }
        
        $username = $this->get_setting('username');
        $repository = $this->get_setting('repository');
        $token = $this->get_setting('token');
        
        $this->api = new Preowned_Clothing_GitHub_API(
            $username,
            $repository,
            $token,
            $this->debug_mode
        );
    }
    
    /**
     * Initialize admin hooks
     */
    public function initialize() {
        // Register admin menu
        add_action('admin_menu', array($this, 'register_admin_menu'));
        
        // Register settings
        add_action('admin_init', array($this, 'register_settings'));
        
        // Add settings link to plugins page
        $plugin_basename = plugin_basename($this->updater->file);
        add_filter('plugin_action_links_' . $plugin_basename, array($this, 'add_settings_link'));
        
        // Add a check for errors or required setup
        add_action('admin_notices', array($this, 'admin_notices'));
        
        // Register AJAX handlers
        add_action('wp_ajax_pcf_github_test_connection', array($this, 'ajax_test_connection'));
        add_action('wp_ajax_pcf_github_force_update_check', array($this, 'ajax_force_update_check'));
        add_action('wp_ajax_pcf_github_clear_cache', array($this, 'ajax_clear_cache'));
    }
    
    /**
     * Register admin menu
     */
    public function register_admin_menu() {
        add_options_page(
            'GitHub Updater',
            'GitHub Updater',
            'manage_options',
            'github-updater',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting(
            'github_updater_settings',
            'preowned_clothing_github_settings',
            array($this, 'validate_settings')
        );
        
        add_settings_section(
            'github_updater_main_section',
            'GitHub Repository Settings',
            array($this, 'section_callback'),
            'github-updater'
        );
        
        // Register each setting field
        foreach ($this->settings_fields as $id => $field) {
            add_settings_field(
                'preowned_clothing_github_' . $id,
                $field['label'],
                array($this, 'field_callback'),
                'github-updater',
                'github_updater_main_section',
                array(
                    'id' => $id,
                    'label_for' => 'preowned_clothing_github_' . $id,
                    'description' => $field['description'],
                    'type' => $field['type'],
                    'default' => $field['default']
                )
            );
        }
    }
    
    /**
     * Section callback - uses a template instead of inline HTML
     */
    public function section_callback() {
        include dirname(__FILE__) . '/views/section-intro.php';
    }
    
    /**
     * Field callback
     * 
     * @param array $args Arguments from add_settings_field()
     */
    public function field_callback($args) {
        $id = $args['id'];
        $settings = get_option('preowned_clothing_github_settings', array());
        $value = isset($settings[$id]) ? $settings[$id] : $args['default'];
        $type = $args['type'];
        
        echo '<input name="preowned_clothing_github_settings[' . esc_attr($id) . ']" ';
        echo 'id="preowned_clothing_github_' . esc_attr($id) . '" ';
        echo 'type="' . esc_attr($type) . '" ';
        echo 'class="regular-text" value="' . esc_attr($value) . '" />';
        
        if (isset($args['description'])) {
            echo '<p class="description">' . $args['description'] . '</p>';
        }
    }
    
    /**
     * Validate settings
     * 
     * @param array $input Input values
     * @return array Sanitized settings
     */
    public function validate_settings($input) {
        $output = array();
        
        // Sanitize each setting
        foreach ($this->settings_fields as $id => $field) {
            if (isset($input[$id])) {
                if ($field['type'] === 'text' || $field['type'] === 'password') {
                    $output[$id] = sanitize_text_field($input[$id]);
                } else {
                    $output[$id] = sanitize_text_field($input[$id]);
                }
            } else {
                $output[$id] = $field['default'];
            }
        }
        
        return $output;
    }
    
    /**
     * Add settings link to plugins page
     * 
     * @param array $links Existing plugin action links
     * @return array Modified links
     */
    public function add_settings_link($links) {
        $settings_link = '<a href="' . admin_url('options-general.php?page=github-updater') . '">' . __('GitHub Settings', 'preowned-clothing-form') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
    
    /**
     * Get a specific setting value
     * 
     * @param string $key Setting key
     * @return mixed Setting value or default
     */
    public function get_setting($key) {
        $settings = get_option('preowned_clothing_github_settings', array());
        
        if (isset($settings[$key])) {
            return $settings[$key];
        }
        
        // Return default if set
        if (isset($this->settings_fields[$key])) {
            return $this->settings_fields[$key]['default'];
        }
        
        return '';
    }
    
    /**
     * Display admin notices for setup requirements
     */
    public function admin_notices() {
        // Only show to admins
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $username = $this->get_setting('username');
        $repository = $this->get_setting('repository');
        
        if (empty($username) || empty($repository)) {
            // Check if we're on the settings page
            $screen = get_current_screen();
            if ($screen && $screen->id === 'settings_page_github-updater') {
                return; // Don't show on the settings page
            }
            
            ?>
            <div class="notice notice-warning is-dismissible">
                <p>
                    <strong>GitHub Updater:</strong> 
                    Please <a href="<?php echo admin_url('options-general.php?page=github-updater'); ?>">configure your GitHub repository settings</a> to enable automatic updates.
                </p>
            </div>
            <?php
        }
    }
    
    /**
     * AJAX handler for testing the GitHub connection
     */
    public function ajax_test_connection() {
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }
        
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'github_updater_test_connection')) {
            wp_send_json_error('Security check failed');
        }
        
        $username = isset($_POST['username']) ? sanitize_text_field($_POST['username']) : $this->get_setting('username');
        $repository = isset($_POST['repository']) ? sanitize_text_field($_POST['repository']) : $this->get_setting('repository');
        $token = isset($_POST['token']) ? sanitize_text_field($_POST['token']) : $this->get_setting('token');
        
        // Create a test API instance
        $test_api = new Preowned_Clothing_GitHub_API($username, $repository, $token, true);
        
        // Check if repository exists
        $repo_check = $test_api->check_repository();
        
        if (is_wp_error($repo_check)) {
            $error = $repo_check->get_error_message();
            wp_send_json_error(array(
                'message' => 'Repository check failed: ' . $error,
                'details' => $repo_check->get_error_data()
            ));
            return;
        }
        
        // Check for releases
        $release = $test_api->get_latest_release(true);
        
        if (is_wp_error($release)) {
            $error = $release->get_error_message();
            wp_send_json_error(array(
                'message' => 'Release check failed: ' . $error,
                'repository_exists' => true,
                'details' => $release->get_error_data()
            ));
            return;
        }
        
        // Success! Get release details
        $details = $test_api->extract_release_details($release);
        
        wp_send_json_success(array(
            'message' => 'Connection successful!',
            'version' => $details['version'],
            'repository' => $repo_check['full_name'],
            'published_at' => $details['published_at'],
            'release_url' => isset($release['html_url']) ? $release['html_url'] : '',
            'body' => $details['body']
        ));
    }
    
    /**
     * AJAX handler for forcing an update check
     */
    public function ajax_force_update_check() {
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }
        
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'github_updater_force_update_check')) {
            wp_send_json_error('Security check failed');
        }
        
        // Clear transients and cached data
        delete_site_transient('update_plugins');
        delete_option('preowned_clothing_github_response');
        
        // Force check
        wp_clean_plugins_cache(true);
        wp_update_plugins();
        
        // Check if an update is available
        $current = get_site_transient('update_plugins');
        $plugin_basename = plugin_basename($this->updater->file);
        $update_available = isset($current->response[$plugin_basename]);
        
        if ($update_available) {
            $update_version = $current->response[$plugin_basename]->new_version;
            wp_send_json_success(array(
                'message' => 'Update check complete. An update is available!',
                'version' => $update_version,
                'update_url' => admin_url('update-core.php')
            ));
        } else {
            wp_send_json_success(array(
                'message' => 'Update check complete. No updates available.',
                'current_version' => $this->plugin_data['Version']
            ));
        }
    }
    
    /**
     * AJAX handler for clearing the cache
     */
    public function ajax_clear_cache() {
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }
        
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'github_updater_clear_cache')) {
            wp_send_json_error('Security check failed');
        }
        
        // Clear all GitHub updater related options and transients
        delete_option('preowned_clothing_github_response');
        delete_option('preowned_clothing_last_update_check');
        delete_site_transient('update_plugins');
        delete_transient('preowned_clothing_github_release_' . $this->get_setting('username') . '_' . $this->get_setting('repository'));
        
        wp_send_json_success(array(
            'message' => 'Cache cleared successfully'
        ));
    }
    
    /**
     * Render the settings page
     */
    public function render_settings_page() {
        // Get GitHub version
        $current_version = $this->plugin_data['Version'];
        
        // Try to get GitHub version for display
        $api = $this->api;
        $github_version = 'Unknown';
        $update_available = false;
        $release_info = null;
        
        try {
            $release = $api->get_latest_release();
            if (!is_wp_error($release)) {
                $details = $api->extract_release_details($release);
                $github_version = $details['version'];
                $update_available = version_compare($github_version, $current_version, '>');
                $release_info = $release;
            } else if ($this->debug_mode) {
                error_log('GitHub Admin: Error getting release for display: ' . $release->get_error_message());
            }
        } catch (Exception $e) {
            if ($this->debug_mode) {
                error_log('GitHub Admin: Exception getting release: ' . $e->getMessage());
            }
        }
        
        // Pass admin instance and variables explicitly to the template
        $admin = $this; // Explicitly pass this reference
        
        // Include the main settings view with explicit variable scope
        include dirname(__FILE__) . '/views/settings-page.php';
        
        // Include additional view components based on query parameters
        if (isset($_GET['advanced_debug']) && $_GET['advanced_debug'] === '1') {
            $this->display_advanced_debug();
        } else {
            echo '<p style="margin-top: 20px;"><a href="' . esc_url(add_query_arg('advanced_debug', '1')) . '" class="button">Show Advanced Debug Info</a></p>';
        }
        
        // Always include these sections
        $this->display_troubleshooting();
        $this->display_help_section();
        $this->display_debug_panel();
    }
    
    /**
     * Display help section
     */
    public function display_help_section() {
        // Pass data to the template
        $username = $this->get_setting('username');
        $repository = $this->get_setting('repository');
        
        // Include the template
        include dirname(__FILE__) . '/views/help-section.php';
    }
    
    /**
     * Display troubleshooting section
     */
    public function display_troubleshooting() {
        include dirname(__FILE__) . '/views/troubleshooting.php';
    }
    
    /**
     * Display debug panel
     */
    public function display_debug_panel() {
        // Get debug log content
        $debug_log = preowned_clothing_get_debug_log();
        
        // Include the template
        include dirname(__FILE__) . '/views/debug-panel.php';
    }
    
    /**
     * Display advanced debugging information
     */
    public function display_advanced_debug() {
        // Pass data to the template
        $plugin_file = $this->updater->file;
        
        // Include the template
        include dirname(__FILE__) . '/views/advanced-debug.php';
    }
}
