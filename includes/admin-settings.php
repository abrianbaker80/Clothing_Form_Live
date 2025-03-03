<?php
/**
 * Admin Settings
 *
 * Handles plugin settings and configuration.
 *
 * @package PreownedClothingForm
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Register admin settings page
 */
function preowned_clothing_add_settings_page() {
    add_options_page(
        'Preowned Clothing Form Settings',
        'Clothing Form Settings',
        'manage_options',
        'preowned-clothing-settings',
        'preowned_clothing_settings_page'
    );
}
add_action('admin_menu', 'preowned_clothing_add_settings_page');

/**
 * Register settings
 */
function preowned_clothing_register_settings() {
    // All settings are registered through the GitHub updater class
}
add_action('admin_init', 'preowned_clothing_register_settings');

/**
 * Get Settings Fields
 *
 * @return array Settings fields
 */
function preowned_clothing_get_settings_fields() {
    $fields = array(
        // Notification Settings
        'notification_email' => array(
            'title' => 'Notification Email',
            'description' => 'Email address where submission notifications will be sent.',
            'type' => 'email',
            'default' => get_option('admin_email'),
            'section' => 'notifications',
        ),
        
        'enable_notifications' => array(
            'title' => 'Enable Email Notifications',
            'description' => 'Send email notifications when new submissions arrive.',
            'type' => 'checkbox',
            'default' => '1',
            'section' => 'notifications',
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
            'section' => 'functionality',
        ),
        
        'required_images' => array(
            'title' => 'Required Images',
            'description' => 'Select which images should be required for submission.',
            'type' => 'multicheck',
            'options' => array(
                'front' => 'Front View', 
                'back' => 'Back View', 
                'brand_tag' => 'Brand Tag', 
                'material_tag' => 'Material Tag',
                'detail' => 'Detail View'
            ),
            'default' => array('front', 'back', 'brand_tag'),
            'section' => 'functionality',
        ),
        
        'max_image_size' => array(
            'title' => 'Maximum Image Size (MB)',
            'description' => 'Maximum file size allowed for uploaded images in megabytes.',
            'type' => 'number',
            'default' => '2',
            'min' => '1',
            'max' => '10',
            'section' => 'functionality',
        ),
        
        // Message Settings
        'submission_success_message' => array(
            'title' => 'Success Message',
            'description' => 'Message shown to users after successful submission.',
            'type' => 'textarea',
            'default' => 'Thank you for your submission! We will review your clothing items and contact you within 24-48 hours.',
            'section' => 'messages',
        ),
        
        'submission_error_message' => array(
            'title' => 'Error Message',
            'description' => 'Message shown when submission fails.',
            'type' => 'textarea',
            'default' => 'There was a problem with your submission. Please try again or contact us for assistance.',
            'section' => 'messages',
        ),
        
        'image_size_error' => array(
            'title' => 'Image Size Error',
            'description' => 'Message shown when an image exceeds the maximum file size.',
            'type' => 'text',
            'default' => 'Image is too large. Please select an image smaller than {size}MB.',
            'section' => 'messages',
        ),
        
        // GitHub Updater Token
        'github_token' => array(
            'title' => 'GitHub Access Token',
            'description' => 'Enter your GitHub personal access token to enable automatic updates. <a href="https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/creating-a-personal-access-token" target="_blank">Learn how to create a token</a>.',
            'type' => 'password',
            'default' => '',
            'section' => 'advanced',
        ),
    );
    
    // Allow extensions or other parts of the plugin to add settings
    return apply_filters('preowned_clothing_settings_fields', $fields);
}

/**
 * Render the settings page with tabs navigation
 */
function preowned_clothing_settings_page() {
    // Get settings fields
    $fields = preowned_clothing_get_settings_fields();
    
    // Define sections/tabs
    $sections = array(
        'appearance' => array('title' => 'Appearance', 'icon' => 'dashicons-admin-appearance'),
        'functionality' => array('title' => 'Functionality', 'icon' => 'dashicons-admin-generic'),
        'messages' => array('title' => 'Messages', 'icon' => 'dashicons-format-chat'),
        'notifications' => array('title' => 'Notifications', 'icon' => 'dashicons-email-alt'),
        'performance' => array('title' => 'Performance', 'icon' => 'dashicons-performance'), // New performance tab
        'advanced' => array('title' => 'Advanced', 'icon' => 'dashicons-admin-tools'),
    );
    
    // Set active tab
    $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'appearance';
    
    // Save settings if form is submitted
    if (isset($_POST['preowned_clothing_settings_nonce']) && 
        wp_verify_nonce($_POST['preowned_clothing_settings_nonce'], 'preowned_clothing_save_settings')) {
        
        $current_section = $active_tab;
        
        // Save settings from current section
        foreach ($fields as $field_id => $field) {
            if ($field['section'] === $current_section) {
                $option_name = 'preowned_clothing_' . $field_id;
                
                if ($field['type'] === 'checkbox') {
                    $value = isset($_POST[$field_id]) ? '1' : '0';
                } elseif ($field['type'] === 'multicheck') {
                    $value = isset($_POST[$field_id]) ? array_map('sanitize_text_field', $_POST[$field_id]) : array();
                } else {
                    $value = isset($_POST[$field_id]) ? sanitize_text_field($_POST[$field_id]) : '';
                }
                
                update_option($option_name, $value);
            }
        }
        
        // Show success message
        echo '<div class="notice notice-success is-dismissible"><p>Settings saved successfully.</p></div>';
    }
    
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <h2 class="nav-tab-wrapper">
            <?php foreach ($sections as $section_id => $section): ?>
                <a href="?page=preowned-clothing-settings&tab=<?php echo esc_attr($section_id); ?>" 
                   class="nav-tab <?php echo $active_tab === $section_id ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons <?php echo esc_attr($section['icon']); ?>"></span>
                    <?php echo esc_html($section['title']); ?>
                </a>
            <?php endforeach; ?>
        </h2>
        
        <form method="post" action="?page=preowned-clothing-settings&tab=<?php echo esc_attr($active_tab); ?>">
            <?php wp_nonce_field('preowned_clothing_save_settings', 'preowned_clothing_settings_nonce'); ?>
            
            <table class="form-table">
                <?php
                // Display fields for the active tab
                foreach ($fields as $field_id => $field) {
                    if ($field['section'] === $active_tab) {
                        $stored_value = get_option('preowned_clothing_' . $field_id, $field['default']);
                        ?>
                        <tr>
                            <th scope="row"><label for="<?php echo esc_attr($field_id); ?>"><?php echo esc_html($field['title']); ?></label></th>
                            <td>
                                <?php preowned_clothing_render_field($field_id, $field, $stored_value); ?>
                                <?php if (!empty($field['description'])): ?>
                                    <p class="description"><?php echo wp_kses_post($field['description']); ?></p>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php
                    }
                }
                ?>
            </table>
            
            <p class="submit">
                <input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes">
            </p>
        </form>
        
        <?php if ($active_tab === 'advanced'): ?>
        <div class="settings-info-card" style="background: #f9f9f9; padding: 15px; border-left: 4px solid #0073aa; margin-top: 20px;">
            <h3>Plugin Information</h3>
            <?php 
            $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/Clothing_Form/preowned-clothing-form.php');
            ?>
            <p><strong>Version:</strong> <?php echo esc_html($plugin_data['Version']); ?></p>
            <p><strong>GitHub Repository:</strong> <a href="<?php echo esc_url($plugin_data['PluginURI']); ?>" target="_blank"><?php echo esc_html($plugin_data['PluginURI']); ?></a></p>
            <p>For issues or feature requests, please visit the GitHub repository.</p>
        </div>
        <?php endif; ?>
    </div>
    
    <style>
    .nav-tab .dashicons {
        margin-right: 5px;
    }
    .settings-info-card h3 {
        margin-top: 0;
    }
    </style>
    <?php
}

/**
 * Render a settings field
 * 
 * @param string $field_id The field ID
 * @param array $field The field data
 * @param mixed $stored_value The stored value
 */
function preowned_clothing_render_field($field_id, $field, $stored_value) {
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
