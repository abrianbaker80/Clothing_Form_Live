<?php
/**
 * Size Management Interface
 */

// Security check
if (!defined('ABSPATH')) {
    exit;
}

function preowned_clothing_size_manager_page() {
    // Check if form was submitted
    if (isset($_POST['pcf_save_sizes']) && check_admin_referer('pcf_save_sizes')) {
        preowned_clothing_handle_size_save();
    }
    
    // Get existing size groups
    $sizes = get_option('preowned_clothing_sizes', array());
    if (empty($sizes)) {
        // Load default sizes from file as fallback
        $sizes_file = PCF_PLUGIN_DIR . 'includes/clothing-sizes.php';
        if (file_exists($sizes_file)) {
            $sizes = include($sizes_file);
            update_option('preowned_clothing_sizes', $sizes);
        }
    }
    
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__('Size Manager', 'preowned-clothing-form'); ?></h1>
        
        <div class="card">
            <h2><?php echo esc_html__('Manage Clothing Sizes', 'preowned-clothing-form'); ?></h2>
            <p><?php echo esc_html__('Add, edit, or remove size options for different clothing categories.', 'preowned-clothing-form'); ?></p>
            
            <form method="post" action="">
                <?php wp_nonce_field('pcf_save_sizes'); ?>
                
                <div id="pcf-size-manager">
                    <div class="size-groups">
                        <?php if (!empty($sizes)): ?>
                            <?php foreach ($sizes as $group_id => $group): ?>
                                <div class="size-group" data-id="<?php echo esc_attr($group_id); ?>">
                                    <div class="group-header">
                                        <input type="text" name="sizes[<?php echo esc_attr($group_id); ?>][name]" 
                                            value="<?php echo esc_attr($group['name']); ?>" class="group-name">
                                        <div class="group-actions">
                                            <button type="button" class="add-size-option">Add Size</button>
                                            <button type="button" class="remove-size-group">Remove Group</button>
                                        </div>
                                    </div>
                                    
                                    <div class="size-options">
                                        <?php if (!empty($group['sizes'])): ?>
                                            <?php foreach ($group['sizes'] as $size_id => $size): ?>
                                                <div class="size-option">
                                                    <input type="text" name="sizes[<?php echo esc_attr($group_id); ?>][sizes][<?php echo esc_attr($size_id); ?>]" 
                                                        value="<?php echo esc_attr($size); ?>" placeholder="Size value">
                                                    <button type="button" class="remove-size">Remove</button>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <button type="button" class="button add-size-group">Add New Size Group</button>
                </div>
                
                <p class="submit">
                    <input type="submit" name="pcf_save_sizes" class="button button-primary" value="<?php echo esc_attr__('Save Sizes', 'preowned-clothing-form'); ?>">
                </p>
            </form>
        </div>
    </div>
    
    <script>
        jQuery(document).ready(function($) {
            // Add new size group
            $('.add-size-group').on('click', function() {
                const newId = 'group_' + Math.random().toString(36).substr(2, 9);
                
                const newGroup = `
                    <div class="size-group" data-id="${newId}">
                        <div class="group-header">
                            <input type="text" name="sizes[${newId}][name]" class="group-name" placeholder="Size Group Name (e.g., Women's Tops)">
                            <div class="group-actions">
                                <button type="button" class="add-size-option">Add Size</button>
                                <button type="button" class="remove-size-group">Remove Group</button>
                            </div>
                        </div>
                        
                        <div class="size-options">
                            <div class="size-option">
                                <input type="text" name="sizes[${newId}][sizes][0]" placeholder="Size value (e.g., S, M, L)">
                                <button type="button" class="remove-size">Remove</button>
                            </div>
                        </div>
                    </div>
                `;
                
                $('.size-groups').append(newGroup);
            });
            
            // Add new size option
            $(document).on('click', '.add-size-option', function() {
                const $group = $(this).closest('.size-group');
                const groupId = $group.data('id');
                const $sizeOptions = $group.find('.size-options');
                const newSizeId = $sizeOptions.children().length;
                
                const newSize = `
                    <div class="size-option">
                        <input type="text" name="sizes[${groupId}][sizes][${newSizeId}]" placeholder="Size value">
                        <button type="button" class="remove-size">Remove</button>
                    </div>
                `;
                
                $sizeOptions.append(newSize);
            });
            
            // Remove size option
            $(document).on('click', '.remove-size', function() {
                $(this).closest('.size-option').remove();
            });
            
            // Remove size group
            $(document).on('click', '.remove-size-group', function() {
                if (confirm('Are you sure you want to remove this size group?')) {
                    $(this).closest('.size-group').remove();
                }
            });
        });
    </script>
    
    <style>
        .size-groups {
            margin: 20px 0;
        }
        .size-group {
            margin-bottom: 20px;
            background: #fff;
            padding: 15px;
            border: 1px solid #ddd;
        }
        .group-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .group-name {
            font-weight: bold;
            width: 60%;
        }
        .size-options {
            margin-top: 10px;
        }
        .size-option {
            display: flex;
            align-items: center;
            margin: 5px 0;
        }
        .size-option input {
            margin-right: 10px;
            flex-grow: 1;
        }
    </style>
    <?php
}

function preowned_clothing_handle_size_save() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized access');
    }
    
    // Sanitize and save the size groups
    $size_groups = array();
    
    if (isset($_POST['sizes']) && is_array($_POST['sizes'])) {
        foreach ($_POST['sizes'] as $group_id => $group) {
            $group_name = sanitize_text_field($group['name']);
            if (empty($group_name)) continue;
            
            $size_groups[$group_id] = array(
                'name' => $group_name,
                'sizes' => array()
            );
            
            if (isset($group['sizes']) && is_array($group['sizes'])) {
                foreach ($group['sizes'] as $size_id => $size) {
                    $size_value = sanitize_text_field($size);
                    if (!empty($size_value)) {
                        $size_groups[$group_id]['sizes'][$size_id] = $size_value;
                    }
                }
            }
        }
    }
    
    // Save to database
    update_option('preowned_clothing_sizes', $size_groups);
    
    // Show success message
    add_settings_error(
        'preowned_clothing_sizes',
        'sizes_updated',
        __('Size options have been updated successfully.', 'preowned-clothing-form'),
        'updated'
    );
}
