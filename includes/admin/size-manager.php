<?php
/**
 * Size Management Interface
 */

// Security check
if (!defined('ABSPATH')) {
    exit;
}

function preowned_clothing_size_manager_page() {
    // Check user permissions
    if (!current_user_can('manage_options')) {
        wp_die(__('Sorry, you do not have sufficient permissions to access this page.', 'preowned-clothing-form'));
    }
    
    // Check if form was submitted
    if (isset($_POST['pcf_save_sizes']) && check_admin_referer('pcf_save_sizes')) {
        preowned_clothing_handle_size_save();
    }
    
    // Get categories to associate with size groups
    $categories = get_option('preowned_clothing_categories', array());
    
    // Get existing size groups
    $sizes = get_option('preowned_clothing_sizes', array());
    
    // Debug information
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('Size Manager - Categories: ' . print_r($categories, true));
        error_log('Size Manager - Sizes: ' . print_r($sizes, true));
    }
    
    if (empty($sizes)) {
        // Try to load default sizes from file as fallback
        $sizes_file = PCF_PLUGIN_DIR . 'includes/clothing-sizes.php';
        if (file_exists($sizes_file)) {
            $sizes = include($sizes_file);
            update_option('preowned_clothing_sizes', $sizes);
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Preowned Clothing Form - Loaded sizes from file');
            }
        }
        
        // If still empty, provide default sizes
        if (empty($sizes)) {
            $sizes = preowned_clothing_get_default_sizes();
            update_option('preowned_clothing_sizes', $sizes);
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Preowned Clothing Form - Using hardcoded default sizes');
            }
        }
    }
    
    // If still empty after all attempts, show an error
    if (empty($sizes)) {
        add_settings_error(
            'preowned_clothing_sizes',
            'sizes_empty',
            __('Could not load size options. Using empty configuration.', 'preowned-clothing-form'),
            'error'
        );
    }
    
    // Create a mapping of categories for easier reference
    $category_map = array();
    foreach ($categories as $gender => $gender_cats) {
        foreach ($gender_cats as $cat_id => $category) {
            $key = $gender . '|' . $cat_id;
            $category_map[$key] = array(
                'gender' => $gender,
                'category_id' => $cat_id,
                'name' => $category['name']
            );
        }
    }
    
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__('Size Manager', 'preowned-clothing-form'); ?></h1>
        
        <?php settings_errors('preowned_clothing_sizes'); ?>
        
        <div class="card">
            <h2><?php echo esc_html__('Manage Clothing Sizes', 'preowned-clothing-form'); ?></h2>
            <p><?php echo esc_html__('Add, edit, or remove size options for different clothing categories.', 'preowned-clothing-form'); ?></p>
            
            <form method="post" action="">
                <?php wp_nonce_field('pcf_save_sizes'); ?>
                
                <div id="pcf-size-manager">
                    <!-- Reset button for sizes -->
                    <div class="actions-row">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=preowned-clothing-sizes&action=reset_sizes')); ?>" 
                           class="button" onclick="return confirm('<?php echo esc_js(__('Are you sure you want to reset all size groups to defaults?', 'preowned-clothing-form')); ?>');">
                            <?php echo esc_html__('Reset to Default Sizes', 'preowned-clothing-form'); ?>
                        </a>
                    </div>
                    
                    <!-- Summary of Current Size Groups -->
                    <div class="size-summary card">
                        <h3><?php echo esc_html__('Current Size Groups', 'preowned-clothing-form'); ?></h3>
                        <table class="widefat">
                            <thead>
                                <tr>
                                    <th><?php echo esc_html__('Group Name', 'preowned-clothing-form'); ?></th>
                                    <th><?php echo esc_html__('Category', 'preowned-clothing-form'); ?></th>
                                    <th><?php echo esc_html__('Available Sizes', 'preowned-clothing-form'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($sizes)): ?>
                                    <?php foreach ($sizes as $group_id => $group): ?>
                                        <tr>
                                            <td><strong><?php echo esc_html($group['name']); ?></strong></td>
                                            <td>
                                                <?php 
                                                if (isset($group['category']) && isset($category_map[$group['category']])) {
                                                    echo esc_html(ucfirst($category_map[$group['category']]['gender']) . ' - ' . $category_map[$group['category']]['name']);
                                                } else {
                                                    echo '<em>' . esc_html__('No category assigned', 'preowned-clothing-form') . '</em>';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php 
                                                if (!empty($group['sizes']) && is_array($group['sizes'])) {
                                                    echo '<div class="size-chips">';
                                                    foreach ($group['sizes'] as $size) {
                                                        echo '<span class="size-chip">' . esc_html($size) . '</span>';
                                                    }
                                                    echo '</div>';
                                                } else {
                                                    echo '<em>' . esc_html__('No sizes defined', 'preowned-clothing-form') . '</em>';
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3"><?php echo esc_html__('No size groups defined yet.', 'preowned-clothing-form'); ?></td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <h3><?php echo esc_html__('Edit Size Groups', 'preowned-clothing-form'); ?></h3>
                    
                    <div class="size-groups">
                        <?php if (!empty($sizes)): ?>
                            <?php foreach ($sizes as $group_id => $group): ?>
                                <div class="size-group" data-id="<?php echo esc_attr($group_id); ?>">
                                    <div class="group-header">
                                        <div class="group-info">
                                            <input type="text" name="sizes[<?php echo esc_attr($group_id); ?>][name]" 
                                                value="<?php echo esc_attr($group['name']); ?>" class="group-name" required>
                                            
                                            <select name="sizes[<?php echo esc_attr($group_id); ?>][category]" class="size-category">
                                                <option value=""><?php echo esc_html__('Select Category', 'preowned-clothing-form'); ?></option>
                                                <?php foreach ($categories as $gender => $gender_cats): ?>
                                                    <optgroup label="<?php echo esc_attr(ucfirst($gender)); ?>">
                                                        <?php foreach ($gender_cats as $cat_id => $category): ?>
                                                            <?php 
                                                            $cat_key = $gender . '|' . $cat_id;
                                                            $selected = (isset($group['category']) && $group['category'] === $cat_key);
                                                            ?>
                                                            <option value="<?php echo esc_attr($cat_key); ?>" <?php selected($selected); ?>>
                                                                <?php echo esc_html($category['name']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </optgroup>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="group-actions">
                                            <button type="button" class="add-size-option button-secondary"><?php echo esc_html__('Add Size', 'preowned-clothing-form'); ?></button>
                                            <button type="button" class="remove-size-group button-link delete"><?php echo esc_html__('Remove Group', 'preowned-clothing-form'); ?></button>
                                        </div>
                                    </div>
                                    
                                    <div class="size-options">
                                        <?php if (!empty($group['sizes'])): ?>
                                            <?php foreach ($group['sizes'] as $size_id => $size): ?>
                                                <div class="size-option">
                                                    <input type="text" name="sizes[<?php echo esc_attr($group_id); ?>][sizes][<?php echo esc_attr($size_id); ?>]" 
                                                        value="<?php echo esc_attr($size); ?>" placeholder="<?php echo esc_attr__('Size value', 'preowned-clothing-form'); ?>" required>
                                                    <button type="button" class="remove-size button-link delete"><?php echo esc_html__('Remove', 'preowned-clothing-form'); ?></button>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="size-option">
                                                <input type="text" name="sizes[<?php echo esc_attr($group_id); ?>][sizes][0]" 
                                                    placeholder="<?php echo esc_attr__('Size value (e.g., S, M, L)', 'preowned-clothing-form'); ?>" required>
                                                <button type="button" class="remove-size button-link delete"><?php echo esc_html__('Remove', 'preowned-clothing-form'); ?></button>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="no-sizes"><?php echo esc_html__('No size groups defined. Add a new size group to get started.', 'preowned-clothing-form'); ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <button type="button" class="button add-size-group">
                        <span class="dashicons dashicons-plus-alt2"></span> 
                        <?php echo esc_html__('Add New Size Group', 'preowned-clothing-form'); ?>
                    </button>
                </div>
                
                <p class="submit">
                    <input type="submit" name="pcf_save_sizes" class="button button-primary" value="<?php echo esc_attr__('Save Sizes', 'preowned-clothing-form'); ?>">
                </p>
            </form>
        </div>
        
        <div class="card" style="margin-top: 20px;">
            <h3><?php echo esc_html__('About Size Groups', 'preowned-clothing-form'); ?></h3>
            <p>
                <?php echo esc_html__('Size groups let you organize different sets of sizes for specific clothing categories.', 'preowned-clothing-form'); ?>
                <?php echo esc_html__('For example, you might have one group for "Women\'s Tops" with sizes XS, S, M, L, XL and another for "Men\'s Pants" with sizes 30, 32, 34, 36, etc.', 'preowned-clothing-form'); ?>
            </p>
            <p>
                <?php echo esc_html__('Each size group should be associated with a category to ensure the correct size options appear when users select that category in the submission form.', 'preowned-clothing-form'); ?>
            </p>
        </div>
    </div>
    
    <script>
        jQuery(document).ready(function($) {
            // Add new size group
            $('.add-size-group').on('click', function() {
                const newId = 'group_' + Math.random().toString(36).substr(2, 9);
                const categoryOptions = [];
                
                <?php foreach ($categories as $gender => $gender_cats): ?>
                    categoryOptions.push('<optgroup label="<?php echo esc_js(ucfirst($gender)); ?>">');
                    <?php foreach ($gender_cats as $cat_id => $category): ?>
                        categoryOptions.push('<option value="<?php echo esc_js($gender . '|' . $cat_id); ?>"><?php echo esc_js($category['name']); ?></option>');
                    <?php endforeach; ?>
                    categoryOptions.push('</optgroup>');
                <?php endforeach; ?>
                
                const newGroup = `
                    <div class="size-group" data-id="${newId}">
                        <div class="group-header">
                            <div class="group-info">
                                <input type="text" name="sizes[${newId}][name]" class="group-name" 
                                    placeholder="<?php echo esc_attr__('Size Group Name (e.g., Women\'s Tops)', 'preowned-clothing-form'); ?>" required>
                                    
                                <select name="sizes[${newId}][category]" class="size-category">
                                    <option value=""><?php echo esc_js(__('Select Category', 'preowned-clothing-form')); ?></option>
                                    ${categoryOptions.join('')}
                                </select>
                            </div>
                            
                            <div class="group-actions">
                                <button type="button" class="add-size-option button-secondary"><?php echo esc_js(__('Add Size', 'preowned-clothing-form')); ?></button>
                                <button type="button" class="remove-size-group button-link delete"><?php echo esc_js(__('Remove Group', 'preowned-clothing-form')); ?></button>
                            </div>
                        </div>
                        
                        <div class="size-options">
                            <div class="size-option">
                                <input type="text" name="sizes[${newId}][sizes][0]" 
                                    placeholder="<?php echo esc_attr__('Size value (e.g., S, M, L)', 'preowned-clothing-form'); ?>" required>
                                <button type="button" class="remove-size button-link delete"><?php echo esc_js(__('Remove', 'preowned-clothing-form')); ?></button>
                            </div>
                        </div>
                    </div>
                `;
                
                $('.size-groups').append(newGroup);
                $('.no-sizes').hide();
            });
            
            // Add new size option
            $(document).on('click', '.add-size-option', function() {
                const $group = $(this).closest('.size-group');
                const groupId = $group.data('id');
                const $sizeOptions = $group.find('.size-options');
                const newSizeId = $sizeOptions.children().length;
                
                const newSize = `
                    <div class="size-option">
                        <input type="text" name="sizes[${groupId}][sizes][${newSizeId}]" 
                            placeholder="<?php echo esc_attr__('Size value', 'preowned-clothing-form'); ?>" required>
                        <button type="button" class="remove-size button-link delete"><?php echo esc_js(__('Remove', 'preowned-clothing-form')); ?></button>
                    </div>
                `;
                
                $sizeOptions.append(newSize);
            });
            
            // Remove size option
            $(document).on('click', '.remove-size', function() {
                const $sizeOption = $(this).closest('.size-option');
                const $sizeOptions = $sizeOption.parent();
                
                // Don't remove the last size option
                if ($sizeOptions.children().length > 1) {
                    $sizeOption.remove();
                } else {
                    alert('<?php echo esc_js(__('A size group must have at least one size option.', 'preowned-clothing-form')); ?>');
                }
            });
            
            // Remove size group
            $(document).on('click', '.remove-size-group', function() {
                if (confirm('<?php echo esc_js(__('Are you sure you want to remove this size group?', 'preowned-clothing-form')); ?>')) {
                    $(this).closest('.size-group').remove();
                    
                    // Show "no sizes" message if all groups are removed
                    if ($('.size-group').length === 0) {
                        $('.size-groups').append('<p class="no-sizes"><?php echo esc_js(__('No size groups defined. Add a new size group to get started.', 'preowned-clothing-form')); ?></p>');
                    }
                }
            });
            
            // Form validation
            $('form').on('submit', function(e) {
                let valid = true;
                
                // Check if categories are selected
                $('.size-category').each(function() {
                    if (!$(this).val()) {
                        alert('<?php echo esc_js(__('Please select a category for each size group.', 'preowned-clothing-form')); ?>');
                        $(this).focus();
                        valid = false;
                        return false;
                    }
                });
                
                if (!valid) {
                    e.preventDefault();
                }
            });
        });
    </script>
    
    <style>
        .actions-row {
            margin-bottom: 15px;
            text-align: right;
        }
        
        .size-summary {
            margin-bottom: 30px;
            background: #f9f9f9;
            padding: 15px;
        }
        
        .size-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }
        
        .size-chip {
            display: inline-block;
            background: #e0f0fa;
            border: 1px solid #c3e0f7;
            border-radius: 3px;
            padding: 2px 8px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .size-groups {
            margin: 20px 0;
        }
        
        .no-sizes {
            background: #f8f8f8;
            padding: 15px;
            text-align: center;
            border: 1px dashed #ccc;
        }
        
        .size-group {
            margin-bottom: 20px;
            background: #fff;
            padding: 15px;
            border: 1px solid #ddd;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .group-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .group-info {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            width: 70%;
            gap: 10px;
        }
        
        .group-name {
            font-weight: bold;
            min-width: 200px;
        }
        
        .size-category {
            min-width: 220px;
        }
        
        .group-actions {
            display: flex;
            gap: 10px;
        }
        
        .size-options {
            margin-top: 10px;
            padding: 10px;
            background: #f9f9f9;
            border-radius: 4px;
        }
        
        .size-option {
            display: flex;
            align-items: center;
            margin: 5px 0;
        }
        
        .size-option input {
            margin-right: 10px;
            flex-grow: 1;
            max-width: 200px;
        }
        
        .button-link.delete {
            color: #a00;
        }
        
        .button-link.delete:hover {
            color: #dc3232;
        }
    </style>
    <?php
    
    // Handle reset action
    if (isset($_GET['action']) && $_GET['action'] === 'reset_sizes') {
        $default_sizes = preowned_clothing_get_default_sizes();
        update_option('preowned_clothing_sizes', $default_sizes);
        
        // Redirect to remove the action from URL
        wp_safe_redirect(admin_url('admin.php?page=preowned-clothing-sizes&reset=true'));
        exit;
    }
    
    // Show success message after reset
    if (isset($_GET['reset']) && $_GET['reset'] === 'true') {
        add_settings_error(
            'preowned_clothing_sizes',
            'sizes_reset',
            __('Size groups have been reset to default values.', 'preowned-clothing-form'),
            'updated'
        );
    }
}

/**
 * Handle saving size settings
 */
function preowned_clothing_handle_size_save() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized access');
    }
    
    // Sanitize and save the size groups
    $size_groups = array();
    
    if (isset($_POST['sizes']) && is_array($_POST['sizes'])) {
        foreach ($_POST['sizes'] as $group_id => $group) {
            $group_name = sanitize_text_field($group['name']);
            $category = isset($group['category']) ? sanitize_text_field($group['category']) : '';
            
            if (empty($group_name)) continue;
            
            $size_groups[$group_id] = array(
                'name' => $group_name,
                'category' => $category,
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

/**
 * Get default size groups if none are defined
 */
function preowned_clothing_get_default_sizes() {
    return array(
        'womens_tops' => array(
            'name' => "Women's Tops",
            'category' => 'women|tops',
            'sizes' => array(
                'XS', 'S', 'M', 'L', 'XL', 'XXL'
            )
        ),
        'womens_bottoms' => array(
            'name' => "Women's Bottoms",
            'category' => 'women|bottoms',
            'sizes' => array(
                '0', '2', '4', '6', '8', '10', '12', '14', '16', '18', '20'
            )
        ),
        'mens_tops' => array(
            'name' => "Men's Tops",
            'category' => 'men|tops',
            'sizes' => array(
                'XS', 'S', 'M', 'L', 'XL', 'XXL'
            )
        ),
        'mens_bottoms' => array(
            'name' => "Men's Pants",
            'category' => 'men|bottoms',
            'sizes' => array(
                '28', '30', '32', '34', '36', '38', '40', '42', '44'
            )
        ),
        'kids_clothes' => array(
            'name' => "Kids Clothing",
            'category' => 'kids|tops',
            'sizes' => array(
                '2T', '3T', '4T', '5', '6', '7', '8', '10', '12', '14', '16'
            )
        )
    );
}
