<?php
/**
 * Form Field Customization Interface
 */

// Security check
if (!defined('ABSPATH')) {
    exit;
}

function preowned_clothing_form_field_manager_page() {
    // Check if form was submitted
    if (isset($_POST['pcf_save_form_fields']) && check_admin_referer('pcf_save_form_fields')) {
        preowned_clothing_handle_form_fields_save();
    }
    
    // Get current field settings
    $form_fields = get_option('preowned_clothing_form_fields', array(
        'contact_fields' => array(
            'name' => array('required' => true, 'enabled' => true, 'label' => 'Full Name'),
            'email' => array('required' => true, 'enabled' => true, 'label' => 'Email Address'),
            'phone' => array('required' => false, 'enabled' => true, 'label' => 'Phone Number'),
            'address' => array('required' => false, 'enabled' => true, 'label' => 'Street Address'),
            'city' => array('required' => false, 'enabled' => true, 'label' => 'City'),
            'state' => array('required' => false, 'enabled' => true, 'label' => 'State/Province'),
            'zip' => array('required' => false, 'enabled' => true, 'label' => 'ZIP/Postal Code')
        ),
        'item_fields' => array(
            'gender' => array('required' => true, 'enabled' => true, 'label' => 'Gender'),
            'category' => array('required' => true, 'enabled' => true, 'label' => 'Category'),
            'size' => array('required' => true, 'enabled' => true, 'label' => 'Size'),
            'description' => array('required' => true, 'enabled' => true, 'label' => 'Description'),
        ),
        'image_fields' => array(
            'front' => array('required' => true, 'enabled' => true, 'label' => 'Front View'),
            'back' => array('required' => true, 'enabled' => true, 'label' => 'Back View'),
            'brand_tag' => array('required' => true, 'enabled' => true, 'label' => 'Brand Tag'),
            'material_tag' => array('required' => false, 'enabled' => true, 'label' => 'Material/Care Tag'),
            'detail' => array('required' => false, 'enabled' => true, 'label' => 'Detail/Damage View')
        ),
        'custom_fields' => array()
    ));
    
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__('Form Field Manager', 'preowned-clothing-form'); ?></h1>
        
        <div class="card">
            <h2><?php echo esc_html__('Customize Form Fields', 'preowned-clothing-form'); ?></h2>
            <p><?php echo esc_html__('Enable/disable fields, make them required, and customize their labels.', 'preowned-clothing-form'); ?></p>
            
            <form method="post" action="">
                <?php wp_nonce_field('pcf_save_form_fields'); ?>
                
                <!-- Contact Fields Section -->
                <div class="field-section">
                    <h3><?php echo esc_html__('Contact Information Fields', 'preowned-clothing-form'); ?></h3>
                    
                    <table class="form-table">
                        <thead>
                            <tr>
                                <th><?php echo esc_html__('Field', 'preowned-clothing-form'); ?></th>
                                <th><?php echo esc_html__('Enabled', 'preowned-clothing-form'); ?></th>
                                <th><?php echo esc_html__('Required', 'preowned-clothing-form'); ?></th>
                                <th><?php echo esc_html__('Label', 'preowned-clothing-form'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($form_fields['contact_fields'] as $field_id => $field): ?>
                                <tr>
                                    <td><?php echo esc_html(ucfirst(str_replace('_', ' ', $field_id))); ?></td>
                                    <td>
                                        <input type="checkbox" name="form_fields[contact_fields][<?php echo esc_attr($field_id); ?>][enabled]" 
                                            <?php checked($field['enabled'], true); ?>>
                                    </td>
                                    <td>
                                        <input type="checkbox" name="form_fields[contact_fields][<?php echo esc_attr($field_id); ?>][required]" 
                                            <?php checked($field['required'], true); ?>>
                                    </td>
                                    <td>
                                        <input type="text" name="form_fields[contact_fields][<?php echo esc_attr($field_id); ?>][label]" 
                                            value="<?php echo esc_attr($field['label']); ?>">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Item Fields Section -->
                <div class="field-section">
                    <h3><?php echo esc_html__('Item Detail Fields', 'preowned-clothing-form'); ?></h3>
                    
                    <table class="form-table">
                        <thead>
                            <tr>
                                <th><?php echo esc_html__('Field', 'preowned-clothing-form'); ?></th>
                                <th><?php echo esc_html__('Enabled', 'preowned-clothing-form'); ?></th>
                                <th><?php echo esc_html__('Required', 'preowned-clothing-form'); ?></th>
                                <th><?php echo esc_html__('Label', 'preowned-clothing-form'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($form_fields['item_fields'] as $field_id => $field): ?>
                                <tr>
                                    <td><?php echo esc_html(ucfirst($field_id)); ?></td>
                                    <td>
                                        <input type="checkbox" name="form_fields[item_fields][<?php echo esc_attr($field_id); ?>][enabled]" 
                                            <?php checked($field['enabled'], true); ?> <?php echo ($field_id == 'gender' || $field_id == 'category') ? 'disabled' : ''; ?>>
                                    </td>
                                    <td>
                                        <input type="checkbox" name="form_fields[item_fields][<?php echo esc_attr($field_id); ?>][required]" 
                                            <?php checked($field['required'], true); ?>>
                                    </td>
                                    <td>
                                        <input type="text" name="form_fields[item_fields][<?php echo esc_attr($field_id); ?>][label]" 
                                            value="<?php echo esc_attr($field['label']); ?>">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Image Fields Section -->
                <div class="field-section">
                    <h3><?php echo esc_html__('Image Upload Fields', 'preowned-clothing-form'); ?></h3>
                    
                    <table class="form-table">
                        <thead>
                            <tr>
                                <th><?php echo esc_html__('Field', 'preowned-clothing-form'); ?></th>
                                <th><?php echo esc_html__('Enabled', 'preowned-clothing-form'); ?></th>
                                <th><?php echo esc_html__('Required', 'preowned-clothing-form'); ?></th>
                                <th><?php echo esc_html__('Label', 'preowned-clothing-form'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($form_fields['image_fields'] as $field_id => $field): ?>
                                <tr>
                                    <td><?php echo esc_html(ucfirst(str_replace('_', ' ', $field_id))); ?></td>
                                    <td>
                                        <input type="checkbox" name="form_fields[image_fields][<?php echo esc_attr($field_id); ?>][enabled]" 
                                            <?php checked($field['enabled'], true); ?>>
                                    </td>
                                    <td>
                                        <input type="checkbox" name="form_fields[image_fields][<?php echo esc_attr($field_id); ?>][required]" 
                                            <?php checked($field['required'], true); ?>>
                                    </td>
                                    <td>
                                        <input type="text" name="form_fields[image_fields][<?php echo esc_attr($field_id); ?>][label]" 
                                            value="<?php echo esc_attr($field['label']); ?>">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Custom Fields Section -->
                <div class="field-section">
                    <h3><?php echo esc_html__('Custom Fields', 'preowned-clothing-form'); ?></h3>
                    <p><?php echo esc_html__('Add custom fields to collect additional information from users.', 'preowned-clothing-form'); ?></p>
                    
                    <table id="custom-fields-table" class="form-table">
                        <thead>
                            <tr>
                                <th><?php echo esc_html__('Label', 'preowned-clothing-form'); ?></th>
                                <th><?php echo esc_html__('Field Type', 'preowned-clothing-form'); ?></th>
                                <th><?php echo esc_html__('Required', 'preowned-clothing-form'); ?></th>
                                <th><?php echo esc_html__('Options (for dropdown/checkbox)', 'preowned-clothing-form'); ?></th>
                                <th><?php echo esc_html__('Actions', 'preowned-clothing-form'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($form_fields['custom_fields'])): ?>
                                <?php foreach ($form_fields['custom_fields'] as $field_id => $field): ?>
                                    <tr class="custom-field-row">
                                        <td>
                                            <input type="text" name="form_fields[custom_fields][<?php echo esc_attr($field_id); ?>][label]" 
                                                value="<?php echo esc_attr($field['label']); ?>" class="regular-text" required>
                                        </td>
                                        <td>
                                            <select name="form_fields[custom_fields][<?php echo esc_attr($field_id); ?>][type]" class="field-type-select">
                                                <option value="text" <?php selected($field['type'], 'text'); ?>><?php echo esc_html__('Text', 'preowned-clothing-form'); ?></option>
                                                <option value="textarea" <?php selected($field['type'], 'textarea'); ?>><?php echo esc_html__('Textarea', 'preowned-clothing-form'); ?></option>
                                                <option value="select" <?php selected($field['type'], 'select'); ?>><?php echo esc_html__('Dropdown', 'preowned-clothing-form'); ?></option>
                                                <option value="checkbox" <?php selected($field['type'], 'checkbox'); ?>><?php echo esc_html__('Checkbox', 'preowned-clothing-form'); ?></option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="checkbox" name="form_fields[custom_fields][<?php echo esc_attr($field_id); ?>][required]" 
                                                <?php checked($field['required'], true); ?>>
                                        </td>
                                        <td>
                                            <input type="text" name="form_fields[custom_fields][<?php echo esc_attr($field_id); ?>][options]" 
                                                value="<?php echo isset($field['options']) ? esc_attr($field['options']) : ''; ?>" 
                                                class="regular-text options-field" <?php echo ($field['type'] === 'select' || $field['type'] === 'checkbox') ? '' : 'style="display:none;"'; ?> 
                                                placeholder="<?php echo esc_attr__('Comma-separated options', 'preowned-clothing-form'); ?>">
                                        </td>
                                        <td>
                                            <button type="button" class="button remove-custom-field"><?php echo esc_html__('Remove', 'preowned-clothing-form'); ?></button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    
                    <button type="button" id="add-custom-field" class="button"><?php echo esc_html__('Add Custom Field', 'preowned-clothing-form'); ?></button>
                </div>
                
                <p class="submit">
                    <input type="submit" name="pcf_save_form_fields" class="button button-primary" value="<?php echo esc_attr__('Save Form Fields', 'preowned-clothing-form'); ?>">
                </p>
            </form>
        </div>
    </div>
    
    <script>
        jQuery(document).ready(function($) {
            // Handle adding new custom fields
            $('#add-custom-field').on('click', function() {
                const newFieldId = 'custom_' + Date.now();
                const newRow = `
                    <tr class="custom-field-row">
                        <td>
                            <input type="text" name="form_fields[custom_fields][${newFieldId}][label]" 
                                class="regular-text" required placeholder="<?php echo esc_attr__('Field Label', 'preowned-clothing-form'); ?>">
                        </td>
                        <td>
                            <select name="form_fields[custom_fields][${newFieldId}][type]" class="field-type-select">
                                <option value="text"><?php echo esc_html__('Text', 'preowned-clothing-form'); ?></option>
                                <option value="textarea"><?php echo esc_html__('Textarea', 'preowned-clothing-form'); ?></option>
                                <option value="select"><?php echo esc_html__('Dropdown', 'preowned-clothing-form'); ?></option>
                                <option value="checkbox"><?php echo esc_html__('Checkbox', 'preowned-clothing-form'); ?></option>
                            </select>
                        </td>
                        <td>
                            <input type="checkbox" name="form_fields[custom_fields][${newFieldId}][required]">
                        </td>
                        <td>
                            <input type="text" name="form_fields[custom_fields][${newFieldId}][options]" 
                                class="regular-text options-field" style="display:none;" 
                                placeholder="<?php echo esc_attr__('Comma-separated options', 'preowned-clothing-form'); ?>">
                        </td>
                        <td>
                            <button type="button" class="button remove-custom-field"><?php echo esc_html__('Remove', 'preowned-clothing-form'); ?></button>
                        </td>
                    </tr>
                `;
                
                $('#custom-fields-table tbody').append(newRow);
            });
            
            // Handle removing custom fields
            $(document).on('click', '.remove-custom-field', function() {
                $(this).closest('tr').remove();
            });
            
            // Show/hide options field based on field type
            $(document).on('change', '.field-type-select', function() {
                const type = $(this).val();
                const optionsField = $(this).closest('tr').find('.options-field');
                
                if (type === 'select' || type === 'checkbox') {
                    optionsField.show();
                } else {
                    optionsField.hide();
                }
            });
        });
    </script>
    
    <style>
        .field-section {
            background: #fff;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid #e5e5e5;
        }
        
        .field-section h3 {
            margin-top: 0;
            padding-bottom: 10px;
            border-bottom: 1px solid #e5e5e5;
        }
        
        .form-table {
            margin-bottom: 20px;
        }
        
        .form-table th {
            padding: 10px 0;
        }
        
        #custom-fields-table {
            border-collapse: collapse;
            width: 100%;
        }
        
        #custom-fields-table th, 
        #custom-fields-table td {
            padding: 10px;
            border: 1px solid #e5e5e5;
        }
        
        .custom-field-row {
            background-color: #f9f9f9;
        }
        
        #add-custom-field {
            margin-bottom: 20px;
        }
    </style>
    <?php
}

/**
 * Handle saving form field settings
 */
function preowned_clothing_handle_form_fields_save() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized access');
    }
    
    $form_fields = array(
        'contact_fields' => array(),
        'item_fields' => array(),
        'image_fields' => array(),
        'custom_fields' => array()
    );
    
    // Process contact fields
    if (isset($_POST['form_fields']['contact_fields'])) {
        foreach ($_POST['form_fields']['contact_fields'] as $field_id => $field) {
            $form_fields['contact_fields'][$field_id] = array(
                'label' => sanitize_text_field($field['label']),
                'enabled' => isset($field['enabled']) ? true : false,
                'required' => isset($field['required']) ? true : false
            );
        }
    }
    
    // Process item fields
    if (isset($_POST['form_fields']['item_fields'])) {
        foreach ($_POST['form_fields']['item_fields'] as $field_id => $field) {
            // Always enable gender and category fields
            $enabled = ($field_id === 'gender' || $field_id === 'category') ? true : isset($field['enabled']);
            
            $form_fields['item_fields'][$field_id] = array(
                'label' => sanitize_text_field($field['label']),
                'enabled' => $enabled,
                'required' => isset($field['required']) ? true : false
            );
        }
    }
    
    // Process image fields
    if (isset($_POST['form_fields']['image_fields'])) {
        foreach ($_POST['form_fields']['image_fields'] as $field_id => $field) {
            $form_fields['image_fields'][$field_id] = array(
                'label' => sanitize_text_field($field['label']),
                'enabled' => isset($field['enabled']) ? true : false,
                'required' => isset($field['required']) ? true : false
            );
        }
    }
    
    // Process custom fields
    if (isset($_POST['form_fields']['custom_fields'])) {
        foreach ($_POST['form_fields']['custom_fields'] as $field_id => $field) {
            if (empty($field['label'])) continue;
            
            $field_data = array(
                'label' => sanitize_text_field($field['label']),
                'type' => sanitize_text_field($field['type']),
                'required' => isset($field['required']) ? true : false
            );
            
            // Add options for select and checkbox fields
            if (($field['type'] === 'select' || $field['type'] === 'checkbox') && isset($field['options'])) {
                $field_data['options'] = sanitize_text_field($field['options']);
            }
            
            $form_fields['custom_fields'][$field_id] = $field_data;
        }
    }
    
    // Save form fields to options
    update_option('preowned_clothing_form_fields', $form_fields);
    
    // Show success message
    add_settings_error(
        'preowned_clothing_form_fields',
        'form_fields_updated',
        __('Form fields updated successfully.', 'preowned-clothing-form'),
        'updated'
    );
}
