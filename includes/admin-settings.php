<?php
/**
 * Admin Settings
 *
 * Handles plugin settings and configuration.
 *
 * @package PreownedClothingForm
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin Settings Class
 */
class Preowned_Clothing_Admin_Settings {
    
    /**
     * Instance of this class
     *
     * @var Preowned_Clothing_Admin_Settings
     */
    private static $instance = null;
    
    /**
     * Get singleton instance
     *
     * @return Preowned_Clothing_Admin_Settings
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Use proper WordPress hook for admin initialization
        add_action('admin_menu', array($this, 'add_admin_menu'), 9); // Lower priority to ensure it runs early
        add_action('admin_init', array($this, 'register_settings'), 10);
    }
    
    /**
     * Register admin menu items
     */
    public function add_admin_menu() {
        // Debug to help identify if the function is running
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Preowned Clothing Form - Adding admin menu');
        }
        
        // Add main menu page
        add_menu_page(
            'Preowned Clothing Form',
            'Clothing Form',
            'manage_options',
            'preowned-clothing-settings',
            array($this, 'settings_page'),
            'dashicons-admin-appearance',
            80
        );
        
        // Add settings subpage
        add_submenu_page(
            'preowned-clothing-settings', // Parent slug
            'Form Settings',
            'Settings',
            'manage_options',
            'preowned-clothing-settings', // Same as parent to make it the default page
            array($this, 'settings_page')
        );
        
        // Add submenu pages
        $this->register_submenu_pages();
    }
    
    /**
     * Register submenu pages
     */
    private function register_submenu_pages() {
        // Category manager
        if (file_exists(plugin_dir_path(dirname(__FILE__)) . 'includes/admin/category-manager.php')) {
            require_once plugin_dir_path(dirname(__FILE__)) . 'includes/admin/category-manager.php';
            
            add_submenu_page(
                'preowned-clothing-settings', // Parent slug
                'Category Manager',
                'Category Manager',
                'manage_options',
                'preowned-clothing-categories',
                'preowned_clothing_category_manager_page'
            );
        }
        
        // Size manager
        if (file_exists(plugin_dir_path(dirname(__FILE__)) . 'includes/admin/size-manager.php')) {
            require_once plugin_dir_path(dirname(__FILE__)) . 'includes/admin/size-manager.php';
            
            add_submenu_page(
                'preowned-clothing-settings', // Parent slug
                'Size Manager',
                'Size Manager',
                'manage_options',
                'preowned-clothing-sizes',
                'preowned_clothing_size_manager_page'
            );
        }
        
        // Form field manager
        if (file_exists(plugin_dir_path(dirname(__FILE__)) . 'includes/admin/form-field-manager.php')) {
            require_once plugin_dir_path(dirname(__FILE__)) . 'includes/admin/form-field-manager.php';
            
            add_submenu_page(
                'preowned-clothing-settings', // Parent slug
                'Form Field Manager',
                'Form Fields',
                'manage_options',
                'preowned-clothing-form-fields',
                'preowned_clothing_form_field_manager_page'
            );
        }
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        // Get all settings fields
        $fields = $this->get_settings_fields();
        
        // Register each setting
        foreach ($fields as $field_id => $field) {
            $option_name = 'preowned_clothing_' . $field_id;
            
            // Register the setting
            register_setting(
                'preowned_clothing_settings',
                $option_name,
                array(
                    'type' => $field['type'] === 'multicheck' ? 'array' : 'string',
                    'description' => isset($field['description']) ? $field['description'] : '',
                    'sanitize_callback' => function($value) use ($field) {
                        if ($field['type'] === 'checkbox') {
                            return $value ? '1' : '0';
                        } elseif ($field['type'] === 'multicheck') {
                            return is_array($value) ? array_map('sanitize_text_field', $value) : array();
                        } elseif ($field['type'] === 'textarea') {
                            return wp_kses_post($value);
                        } elseif ($field['type'] === 'email') {
                            return sanitize_email($value);
                        } elseif ($field['type'] === 'number') {
                            return intval($value);
                        } elseif ($field['type'] === 'color') {
                            return sanitize_hex_color($value);
                        } else {
                            return sanitize_text_field($value);
                        }
                    }
                )
            );
            
            // Add default value if not already set
            if (isset($field['default']) && get_option($option_name) === false) {
                add_option($option_name, $field['default']);
            }
        }
        
        // Register custom option groups for each tab
        register_setting('preowned_clothing_general', 'preowned_clothing_max_items');
        register_setting('preowned_clothing_general', 'preowned_clothing_max_image_size');
        
        register_setting('preowned_clothing_email', 'preowned_clothing_notification_email');
        register_setting('preowned_clothing_email', 'preowned_clothing_enable_notifications');
        register_setting('preowned_clothing_email', 'preowned_clothing_email_subject');
        register_setting('preowned_clothing_email', 'preowned_clothing_email_format');
        
        register_setting('preowned_clothing_appearance', 'preowned_clothing_form_title');
        register_setting('preowned_clothing_appearance', 'preowned_clothing_form_intro');
        register_setting('preowned_clothing_appearance', 'preowned_clothing_primary_color');
        register_setting('preowned_clothing_appearance', 'preowned_clothing_secondary_color');
        register_setting('preowned_clothing_appearance', 'preowned_clothing_enable_modern_theme');
        
        register_setting('preowned_clothing_advanced', 'preowned_clothing_github_token');
        register_setting('preowned_clothing_advanced', 'preowned_clothing_enable_debug');
        register_setting('preowned_clothing_advanced', 'preowned_clothing_clear_data');
    }

    /**
     * Get Settings Fields
     *
     * @return array Settings fields
     */
    public function get_settings_fields() {
        $fields = array(
            // Notification Settings
            'notification_email' => array(
                'title' => 'Notification Email',
                'description' => 'Email address where submission notifications will be sent.',
                'type' => 'email',
                'default' => get_option('admin_email'),
                'section' => 'email',
            ),
            
            'enable_notifications' => array(
                'title' => 'Enable Email Notifications',
                'description' => 'Send email notifications when new submissions arrive.',
                'type' => 'checkbox',
                'default' => '1',
                'section' => 'email',
            ),
            
            // Form Settings
            'form_title' => array(
                'title' => 'Form Title',
                'description' => 'Main heading displayed at the top of the form.',
                'type' => 'text',
                'default' => 'Submit Your Pre-owned Clothing',
                'section' => 'appearance',
            ),
            
            'form_intro' => array(
                'title' => 'Form Introduction',
                'description' => 'Text shown above the form explaining its purpose.',
                'type' => 'textarea',
                'default' => 'You can submit multiple clothing items in a single form. Please provide clear photos and detailed descriptions for each item.',
                'section' => 'appearance',
            ),
            
            'primary_color' => array(
                'title' => 'Primary Color',
                'description' => 'Main color used for buttons and highlights (hexadecimal color code).',
                'type' => 'color',
                'default' => '#0073aa',
                'section' => 'appearance',
            ),
            
            'secondary_color' => array(
                'title' => 'Secondary Color',
                'description' => 'Secondary color used for accents and hover states.',
                'type' => 'color',
                'default' => '#005177',
                'section' => 'appearance',
            ),
            
            'max_items' => array(
                'title' => 'Maximum Items',
                'description' => 'Maximum number of clothing items allowed per submission.',
                'type' => 'number',
                'default' => '10',
                'min' => '1',
                'max' => '50',
                'section' => 'general',
            ),
            
            'max_image_size' => array(
                'title' => 'Maximum Image Size (MB)',
                'description' => 'Maximum file size allowed for uploaded images in megabytes.',
                'type' => 'number',
                'default' => '2',
                'min' => '1',
                'max' => '10',
                'section' => 'general',
            ),
            
            // Message Settings
            'submission_success_message' => array(
                'title' => 'Success Message',
                'description' => 'Message shown to users after successful submission.',
                'type' => 'textarea',
                'default' => 'Thank you for your submission! We will review your clothing items and contact you within 24-48 hours.',
                'section' => 'email',
            ),
            
            'submission_error_message' => array(
                'title' => 'Error Message',
                'description' => 'Message shown when submission fails.',
                'type' => 'textarea',
                'default' => 'There was a problem with your submission. Please try again or contact us for assistance.',
                'section' => 'email',
            ),
            
            'image_size_error' => array(
                'title' => 'Image Size Error',
                'description' => 'Message shown when an image exceeds the maximum file size.',
                'type' => 'text',
                'default' => 'Image is too large. Please select an image smaller than {size}MB.',
                'section' => 'email',
            ),
            
            // GitHub Updater Token
            'github_token' => array(
                'title' => 'GitHub Access Token',
                'description' => 'Enter your GitHub personal access token to enable automatic updates.',
                'type' => 'password',
                'default' => '',
                'section' => 'advanced',
            ),
        );
        
        return apply_filters('preowned_clothing_settings_fields', $fields);
    }

    /**
     * Display the admin settings page
     */
    public function settings_page() {
        // Double-check user permissions to prevent unauthorized access
        if (!current_user_can('manage_options')) {
            wp_die(__('Sorry, you do not have sufficient permissions to access this page.', 'preowned-clothing-form'));
        }
        
        // Get active tab
        $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'general';
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <h2 class="nav-tab-wrapper">
                <a href="?page=preowned-clothing-settings&tab=general" class="nav-tab <?php echo $active_tab == 'general' ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-admin-settings"></span> <?php echo esc_html__('General Settings', 'preowned-clothing-form'); ?>
                </a>
                <a href="?page=preowned-clothing-settings&tab=email" class="nav-tab <?php echo $active_tab == 'email' ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-email"></span> <?php echo esc_html__('Email Settings', 'preowned-clothing-form'); ?>
                </a>
                <a href="?page=preowned-clothing-settings&tab=appearance" class="nav-tab <?php echo $active_tab == 'appearance' ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-admin-appearance"></span> <?php echo esc_html__('Appearance', 'preowned-clothing-form'); ?>
                </a>
                <a href="?page=preowned-clothing-settings&tab=advanced" class="nav-tab <?php echo $active_tab == 'advanced' ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-admin-tools"></span> <?php echo esc_html__('Advanced', 'preowned-clothing-form'); ?>
                </a>
            </h2>
            
            <div class="clothing-admin-wrapper">
                <div class="settings-tabs-content">
                    <?php
                    // Only show customization dashboard on the general tab
                    if ($active_tab == 'general') {
                        $this->display_customization_dashboard();
                    }
                    
                    // Display tab content based on active tab
                    if ($active_tab == 'general') {
                        $this->display_general_settings();
                    } elseif ($active_tab == 'email') {
                        $this->display_email_settings();
                    } elseif ($active_tab == 'appearance') {
                        $this->display_appearance_settings();
                    } elseif ($active_tab == 'advanced') {
                        $this->display_advanced_settings();
                    }
                    ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Display the customization dashboard
     */
    public function display_customization_dashboard() {
        ?>
        <!-- Customization Links -->
        <div class="customization-dashboard card">
            <div class="card-header">
                <h2><?php echo esc_html__('Form Customization', 'preowned-clothing-form'); ?></h2>
            </div>
            <div class="card-body">
                <div class="customization-links">
                    <a href="<?php echo admin_url('admin.php?page=preowned-clothing-form-fields'); ?>" class="customization-link">
                        <span class="dashicons dashicons-forms"></span>
                        <h3><?php echo esc_html__('Form Fields', 'preowned-clothing-form'); ?></h3>
                        <p><?php echo esc_html__('Manage form fields, make them required, and add custom fields', 'preowned-clothing-form'); ?></p>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=preowned-clothing-categories'); ?>" class="customization-link">
                        <span class="dashicons dashicons-category"></span>
                        <h3><?php echo esc_html__('Categories', 'preowned-clothing-form'); ?></h3>
                        <p><?php echo esc_html__('Customize clothing categories for each gender', 'preowned-clothing-form'); ?></p>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=preowned-clothing-sizes'); ?>" class="customization-link">
                        <span class="dashicons dashicons-editor-textcolor"></span>
                        <h3><?php echo esc_html__('Size Options', 'preowned-clothing-form'); ?></h3>
                        <p><?php echo esc_html__('Manage clothing size options for different categories', 'preowned-clothing-form'); ?></p>
                    </a>
                </div>
            </div>
        </div>
        
        <style>
            .customization-dashboard {
                margin-bottom: 25px;
            }
            
            .customization-links {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 20px;
                padding: 10px;
            }
            
            .customization-link {
                display: block;
                padding: 20px;
                background: #fff;
                border: 1px solid #ddd;
                border-radius: 5px;
                text-decoration: none;
                color: #333;
                transition: all 0.2s ease;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
            
            .customization-link:hover {
                transform: translateY(-3px);
                box-shadow: 0 4px 8px rgba(0,0,0,0.15);
            }
            
            .customization-link .dashicons {
                font-size: 36px;
                height: 36px;
                width: 36px;
                color: #0073aa;
            }
            
            .customization-link h3 {
                margin: 10px 0;
                color: #0073aa;
                font-size: 18px;
            }
            
            .customization-link p {
                margin: 0;
                color: #666;
            }
        </style>
        <?php
    }

    /**
     * Display the general settings tab content
     */
    public function display_general_settings() {
        // Process form submission
        if (isset($_POST['submit']) && isset($_POST['preowned_clothing_settings_nonce']) && 
            wp_verify_nonce($_POST['preowned_clothing_settings_nonce'], 'preowned_clothing_save_settings')) {
            
            // Save max_items setting
            if (isset($_POST['max_items'])) {
                update_option('preowned_clothing_max_items', intval($_POST['max_items']));
            }
            
            // Save max_image_size setting
            if (isset($_POST['max_image_size'])) {
                update_option('preowned_clothing_max_image_size', intval($_POST['max_image_size']));
            }
            
            // Show success message
            echo '<div class="notice notice-success is-dismissible"><p>Settings saved successfully.</p></div>';
        }
        
        ?>
        <div class="card">
            <h2><?php echo esc_html__('General Settings', 'preowned-clothing-form'); ?></h2>
            
            <form method="post">
                <?php wp_nonce_field('preowned_clothing_save_settings', 'preowned_clothing_settings_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="max_items"><?php echo esc_html__('Maximum Items', 'preowned-clothing-form'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="max_items" name="max_items" min="1" max="50" 
                                value="<?php echo esc_attr(get_option('preowned_clothing_max_items', 10)); ?>">
                            <p class="description"><?php echo esc_html__('Maximum number of clothing items allowed per submission.', 'preowned-clothing-form'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="max_image_size"><?php echo esc_html__('Maximum Image Size (MB)', 'preowned-clothing-form'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="max_image_size" name="max_image_size" min="1" max="10"
                                value="<?php echo esc_attr(get_option('preowned_clothing_max_image_size', 2)); ?>">
                            <p class="description"><?php echo esc_html__('Maximum file size allowed for uploaded images in megabytes.', 'preowned-clothing-form'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php echo esc_attr__('Save Changes', 'preowned-clothing-form'); ?>">
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * Display the email settings tab content
     */
    public function display_email_settings() {
        // Process form submission
        if (isset($_POST['submit']) && isset($_POST['preowned_clothing_settings_nonce']) && 
            wp_verify_nonce($_POST['preowned_clothing_settings_nonce'], 'preowned_clothing_save_settings')) {
            
            // Save email settings
            if (isset($_POST['notification_email'])) {
                update_option('preowned_clothing_notification_email', sanitize_email($_POST['notification_email']));
            }
            
            if (isset($_POST['enable_notifications'])) {
                update_option('preowned_clothing_enable_notifications', '1');
            } else {
                update_option('preowned_clothing_enable_notifications', '0');
            }
            
            if (isset($_POST['email_subject'])) {
                update_option('preowned_clothing_email_subject', sanitize_text_field($_POST['email_subject']));
            }
            
            if (isset($_POST['email_format'])) {
                update_option('preowned_clothing_email_format', sanitize_text_field($_POST['email_format']));
            }
            
            if (isset($_POST['submission_success_message'])) {
                update_option('preowned_clothing_submission_success_message', wp_kses_post($_POST['submission_success_message']));
            }
            
            if (isset($_POST['submission_error_message'])) {
                update_option('preowned_clothing_submission_error_message', wp_kses_post($_POST['submission_error_message']));
            }
            
            if (isset($_POST['image_size_error'])) {
                update_option('preowned_clothing_image_size_error', sanitize_text_field($_POST['image_size_error']));
            }
            
            // Show success message
            echo '<div class="notice notice-success is-dismissible"><p>Email settings saved successfully.</p></div>';
        }
        
        ?>
        <div class="card">
            <h2><?php echo esc_html__('Email Settings', 'preowned-clothing-form'); ?></h2>
            
            <form method="post">
                <?php wp_nonce_field('preowned_clothing_save_settings', 'preowned_clothing_settings_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="notification_email"><?php echo esc_html__('Notification Email', 'preowned-clothing-form'); ?></label>
                        </th>
                        <td>
                            <input type="email" id="notification_email" name="notification_email" class="regular-text"
                                value="<?php echo esc_attr(get_option('preowned_clothing_notification_email', get_option('admin_email'))); ?>">
                            <p class="description"><?php echo esc_html__('Email address where submission notifications will be sent.', 'preowned-clothing-form'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="enable_notifications"><?php echo esc_html__('Enable Email Notifications', 'preowned-clothing-form'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" id="enable_notifications" name="enable_notifications" value="1" 
                                <?php checked('1', get_option('preowned_clothing_enable_notifications', '1')); ?>>
                            <label><?php echo esc_html__('Send email notifications when new submissions arrive.', 'preowned-clothing-form'); ?></label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="email_subject"><?php echo esc_html__('Email Subject', 'preowned-clothing-form'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="email_subject" name="email_subject" class="regular-text"
                                value="<?php echo esc_attr(get_option('preowned_clothing_email_subject', 'New Clothing Submission')); ?>">
                            <p class="description"><?php echo esc_html__('Subject line for notification emails.', 'preowned-clothing-form'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="email_format"><?php echo esc_html__('Email Format', 'preowned-clothing-form'); ?></label>
                        </th>
                        <td>
                            <select id="email_format" name="email_format">
                                <option value="html" <?php selected('html', get_option('preowned_clothing_email_format', 'html')); ?>><?php echo esc_html__('HTML', 'preowned-clothing-form'); ?></option>
                                <option value="plain" <?php selected('plain', get_option('preowned_clothing_email_format', 'html')); ?>><?php echo esc_html__('Plain Text', 'preowned-clothing-form'); ?></option>
                            </select>
                            <p class="description"><?php echo esc_html__('Format for notification emails.', 'preowned-clothing-form'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="submission_success_message"><?php echo esc_html__('Success Message', 'preowned-clothing-form'); ?></label>
                        </th>
                        <td>
                            <textarea id="submission_success_message" name="submission_success_message" class="large-text" rows="3"><?php echo esc_textarea(get_option('preowned_clothing_submission_success_message', 'Thank you for your submission! We will review your clothing items and contact you within 24-48 hours.')); ?></textarea>
                            <p class="description"><?php echo esc_html__('Message shown to users after successful submission.', 'preowned-clothing-form'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="submission_error_message"><?php echo esc_html__('Error Message', 'preowned-clothing-form'); ?></label>
                        </th>
                        <td>
                            <textarea id="submission_error_message" name="submission_error_message" class="large-text" rows="3"><?php echo esc_textarea(get_option('preowned_clothing_submission_error_message', 'There was a problem with your submission. Please try again or contact us for assistance.')); ?></textarea>
                            <p class="description"><?php echo esc_html__('Message shown when submission fails.', 'preowned-clothing-form'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="image_size_error"><?php echo esc_html__('Image Size Error', 'preowned-clothing-form'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="image_size_error" name="image_size_error" class="regular-text"
                                value="<?php echo esc_attr(get_option('preowned_clothing_image_size_error', 'Image is too large. Please select an image smaller than {size}MB.')); ?>">
                            <p class="description"><?php echo esc_html__('Message shown when an image exceeds the maximum file size.', 'preowned-clothing-form'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php echo esc_attr__('Save Changes', 'preowned-clothing-form'); ?>">
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * Display the appearance settings tab content
     */
    public function display_appearance_settings() {
        // Process form submission
        if (isset($_POST['submit']) && isset($_POST['preowned_clothing_settings_nonce']) && 
            wp_verify_nonce($_POST['preowned_clothing_settings_nonce'], 'preowned_clothing_save_settings')) {
            
            // Save appearance settings
            if (isset($_POST['form_title'])) {
                update_option('preowned_clothing_form_title', sanitize_text_field($_POST['form_title']));
            }
            
            if (isset($_POST['form_intro'])) {
                update_option('preowned_clothing_form_intro', wp_kses_post($_POST['form_intro']));
            }
            
            if (isset($_POST['primary_color'])) {
                update_option('preowned_clothing_primary_color', sanitize_hex_color($_POST['primary_color']));
            }
            
            if (isset($_POST['secondary_color'])) {
                update_option('preowned_clothing_secondary_color', sanitize_hex_color($_POST['secondary_color']));
            }
            
            if (isset($_POST['enable_modern_theme'])) {
                update_option('preowned_clothing_enable_modern_theme', '1');
            } else {
                update_option('preowned_clothing_enable_modern_theme', '0');
            }
            
            // Show success message
            echo '<div class="notice notice-success is-dismissible"><p>Appearance settings saved successfully.</p></div>';
        }
        
        ?>
        <div class="card">
            <h2><?php echo esc_html__('Appearance Settings', 'preowned-clothing-form'); ?></h2>
            
            <form method="post">
                <?php wp_nonce_field('preowned_clothing_save_settings', 'preowned_clothing_settings_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="form_title"><?php echo esc_html__('Form Title', 'preowned-clothing-form'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="form_title" name="form_title" class="regular-text"
                                value="<?php echo esc_attr(get_option('preowned_clothing_form_title', 'Submit Your Pre-owned Clothing')); ?>">
                            <p class="description"><?php echo esc_html__('Main heading displayed at the top of the form.', 'preowned-clothing-form'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="form_intro"><?php echo esc_html__('Form Introduction', 'preowned-clothing-form'); ?></label>
                        </th>
                        <td>
                            <textarea id="form_intro" name="form_intro" class="large-text" rows="3"><?php echo esc_textarea(get_option('preowned_clothing_form_intro', 'You can submit multiple clothing items in a single form. Please provide clear photos and detailed descriptions for each item.')); ?></textarea>
                            <p class="description"><?php echo esc_html__('Text shown above the form explaining its purpose.', 'preowned-clothing-form'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="primary_color"><?php echo esc_html__('Primary Color', 'preowned-clothing-form'); ?></label>
                        </th>
                        <td>
                            <input type="color" id="primary_color" name="primary_color"
                                value="<?php echo esc_attr(get_option('preowned_clothing_primary_color', '#0073aa')); ?>">
                            <p class="description"><?php echo esc_html__('Main color used for buttons and highlights.', 'preowned-clothing-form'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="secondary_color"><?php echo esc_html__('Secondary Color', 'preowned-clothing-form'); ?></label>
                        </th>
                        <td>
                            <input type="color" id="secondary_color" name="secondary_color"
                                value="<?php echo esc_attr(get_option('preowned_clothing_secondary_color', '#005177')); ?>">
                            <p class="description"><?php echo esc_html__('Secondary color used for accents and hover states.', 'preowned-clothing-form'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="enable_modern_theme"><?php echo esc_html__('Enable Modern Theme', 'preowned-clothing-form'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" id="enable_modern_theme" name="enable_modern_theme" value="1" 
                                <?php checked('1', get_option('preowned_clothing_enable_modern_theme', '1')); ?>>
                            <label for="enable_modern_theme"><?php echo esc_html__('Use enhanced modern styling for the form.', 'preowned-clothing-form'); ?></label>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php echo esc_attr__('Save Changes', 'preowned-clothing-form'); ?>">
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * Display the advanced settings tab content
     */
    public function display_advanced_settings() {
        // Process form submission
        if (isset($_POST['submit']) && isset($_POST['preowned_clothing_settings_nonce']) && 
            wp_verify_nonce($_POST['preowned_clothing_settings_nonce'], 'preowned_clothing_save_settings')) {
            
            // Save advanced settings
            if (isset($_POST['github_token'])) {
                update_option('preowned_clothing_github_token', sanitize_text_field($_POST['github_token']));
            }
            
            if (isset($_POST['enable_debug'])) {
                update_option('preowned_clothing_enable_debug', '1');
            } else {
                update_option('preowned_clothing_enable_debug', '0');
            }
            
            // Handle clear data option
            if (isset($_POST['clear_data']) && $_POST['clear_data'] == '1') {
                global $wpdb;
                $options = $wpdb->get_results("SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE 'preowned_clothing_%'");
                
                foreach ($options as $option) {
                    delete_option($option->option_name);
                }
                
                echo '<div class="notice notice-warning is-dismissible"><p>All plugin data has been cleared. Default settings will be restored on next page load.</p></div>';
            } else {
                echo '<div class="notice notice-success is-dismissible"><p>Advanced settings saved successfully.</p></div>';
            }
        }
        
        // Process reset settings action
        if (isset($_GET['action']) && $_GET['action'] === 'reset_settings') {
            $default_fields = $this->get_settings_fields();
            foreach ($default_fields as $field_id => $field) {
                update_option('preowned_clothing_' . $field_id, $field['default']);
            }
            
            echo '<div class="notice notice-success is-dismissible"><p>Settings have been reset to defaults.</p></div>';
        }
        
        ?>
        <div class="card">
            <h2><?php echo esc_html__('Advanced Settings', 'preowned-clothing-form'); ?></h2>
            
            <form method="post">
                <?php wp_nonce_field('preowned_clothing_save_settings', 'preowned_clothing_settings_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="github_token"><?php echo esc_html__('GitHub Access Token', 'preowned-clothing-form'); ?></label>
                        </th>
                        <td>
                            <input type="password" id="github_token" name="github_token" class="regular-text"
                                value="<?php echo esc_attr(get_option('preowned_clothing_github_token', '')); ?>">
                            <p class="description"><?php echo wp_kses_post(__('Enter your GitHub personal access token to enable automatic updates. <a href="https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/creating-a-personal-access-token" target="_blank">Learn how to create a token</a>.', 'preowned-clothing-form')); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="clear_data"><?php echo esc_html__('Clear Plugin Data', 'preowned-clothing-form'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" id="clear_data" name="clear_data" value="1">
                            <label><?php echo esc_html__('Check this box and save to remove all plugin data from the database. Warning: This action cannot be undone!', 'preowned-clothing-form'); ?></label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="enable_debug"><?php echo esc_html__('Debug Mode', 'preowned-clothing-form'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" id="enable_debug" name="enable_debug" value="1" 
                                <?php checked('1', get_option('preowned_clothing_enable_debug', '0')); ?>>
                            <label><?php echo esc_html__('Enable debug logging for form submissions.', 'preowned-clothing-form'); ?></label>
                            <p class="description"><?php echo esc_html__('This will log detailed information about form submissions to the error log.', 'preowned-clothing-form'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php echo esc_attr__('Save Changes', 'preowned-clothing-form'); ?>">
                </p>
            </form>
        </div>
        
        <div class="card" style="margin-top: 20px;">
            <h2><?php echo esc_html__('Troubleshooting Tools', 'preowned-clothing-form'); ?></h2>
            <div class="inside">
                <p><?php echo esc_html__('Use these tools to troubleshoot issues with the clothing form.', 'preowned-clothing-form'); ?></p>
                <p>
                    <a href="<?php echo esc_url(admin_url('options-general.php?page=preowned-clothing-test-upload')); ?>" class="button">
                        <?php echo esc_html__('Test Image Upload', 'preowned-clothing-form'); ?>
                    </a>
                    <a href="<?php echo esc_url(admin_url('options-general.php?page=preowned-clothing-settings&tab=advanced&action=reset_settings')); ?>" class="button" 
                       onclick="return confirm('<?php echo esc_js(__('Are you sure you want to reset all settings to defaults?', 'preowned-clothing-form')); ?>');">
                        <?php echo esc_html__('Reset Settings', 'preowned-clothing-form'); ?>
                    </a>
                </p>
            </div>
        </div>
        <?php
    }

    /**
     * Render a settings field
     */
    public function render_field($field_id, $field, $stored_value) {
        switch ($field['type']) {
            case 'text':
            case 'email':
            case 'url':
            case 'number':
                echo '<input type="' . esc_attr($field['type']) . '" id="' . esc_attr($field_id) . '" name="' . esc_attr($field_id) . '" value="' . esc_attr($stored_value) . '" class="regular-text"';
                if (isset($field['min'])) {
                    echo ' min="' . esc_attr($field['min']) . '"';
                }
                if (isset($field['max'])) {
                    echo ' max="' . esc_attr($field['max']) . '"';
                }
                echo '>';
                break;
                
            case 'color':
                echo '<input type="color" id="' . esc_attr($field_id) . '" name="' . esc_attr($field_id) . '" value="' . esc_attr($stored_value) . '">';
                break;
                
            case 'password':
                echo '<input type="password" id="' . esc_attr($field_id) . '" name="' . esc_attr($field_id) . '" value="' . esc_attr($stored_value) . '" class="regular-text">';
                break;
                
            case 'textarea':
                echo '<textarea id="' . esc_attr($field_id) . '" name="' . esc_attr($field_id) . '" rows="5" class="large-text">' . esc_textarea($stored_value) . '</textarea>';
                break;
                
            case 'select':
                echo '<select id="' . esc_attr($field_id) . '" name="' . esc_attr($field_id) . '">';
                foreach ($field['options'] as $key => $label) {
                    $selected = selected($stored_value, $key, false);
                    echo '<option value="' . esc_attr($key) . '" ' . $selected . '>' . esc_html($label) . '</option>';
                }
                echo '</select>';
                break;
                
            case 'checkbox':
                echo '<input type="checkbox" id="' . esc_attr($field_id) . '" name="' . esc_attr($field_id) . '" value="1" ' . checked('1', $stored_value, false) . '>';
                break;
                
            case 'multicheck':
                if (!is_array($stored_value)) {
                    $stored_value = array();
                }
                
                echo '<div class="checkbox-group">';
                foreach ($field['options'] as $key => $label) {
                    $checked = in_array($key, $stored_value) ? 'checked' : '';
                    echo '<label style="display:block; margin-bottom:8px;">';
                    echo '<input type="checkbox" name="' . esc_attr($field_id) . '[]" value="' . esc_attr($key) . '" ' . $checked . '> ';
                    echo esc_html($label);
                    echo '</label>';
                }
                echo '</div>';
                break;
        }
    }
}

/**
 * Get the settings instance - direct access function for debugging
 */
function preowned_clothing_get_settings() {
    return Preowned_Clothing_Admin_Settings::get_instance();
}

/**
 * Initialize the settings class - completely rewritten for reliability
 */
function initialize_preowned_clothing_admin_settings() {
    // Skip on AJAX requests to avoid duplicate loads
    if (defined('DOING_AJAX') && DOING_AJAX) {
        return;
    }
    
    // Get instance to initialize it
    $instance = Preowned_Clothing_Admin_Settings::get_instance();
    
    // Debug to help identify if this function is running
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('Preowned Clothing Form - Admin settings initialized');
    }
}

// Remove all existing hooks for the initializer to avoid conflicts
remove_action('plugins_loaded', 'initialize_preowned_clothing_admin_settings');
remove_action('admin_init', 'initialize_preowned_clothing_admin_settings');
remove_action('admin_menu', 'initialize_preowned_clothing_admin_settings');
remove_action('init', 'initialize_preowned_clothing_admin_settings');

// Attach to multiple hooks to ensure it runs
add_action('admin_menu', 'initialize_preowned_clothing_admin_settings', 5); // Run at priority 5 (earlier)
add_action('plugins_loaded', 'initialize_preowned_clothing_admin_settings', 5); // Also initialize on plugins_loaded for safety
