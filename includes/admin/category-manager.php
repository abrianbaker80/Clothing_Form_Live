<?php
/**
 * Category Management Interface
 */

// Security check
if (!defined('ABSPATH')) {
    exit;
}

function preowned_clothing_category_manager_page() {
    // Check if form was submitted
    if (isset($_POST['pcf_save_categories']) && check_admin_referer('pcf_save_categories')) {
        preowned_clothing_handle_category_save();
    }
    
    // Get existing categories
    $categories = get_option('preowned_clothing_categories', array());
    if (empty($categories)) {
        // Load default categories from file as fallback
        $categories_file = PCF_PLUGIN_DIR . 'includes/clothing-categories.php';
        if (file_exists($categories_file)) {
            $categories = include($categories_file);
            update_option('preowned_clothing_categories', $categories);
        }
    }
    
    // Display the category manager interface
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__('Category Manager', 'preowned-clothing-form'); ?></h1>
        
        <div class="card">
            <h2><?php echo esc_html__('Manage Clothing Categories', 'preowned-clothing-form'); ?></h2>
            <p><?php echo esc_html__('Add, edit, or remove clothing categories for your submission form.', 'preowned-clothing-form'); ?></p>
            
            <form method="post" action="">
                <?php wp_nonce_field('pcf_save_categories'); ?>
                
                <div id="pcf-category-builder">
                    <!-- Category Builder UI will be loaded here via JavaScript -->
                    <div class="pcf-category-tree">
                        <?php foreach ($categories as $gender => $gender_cats): ?>
                            <div class="gender-section">
                                <h3><?php echo esc_html(ucfirst($gender)); ?></h3>
                                <button type="button" class="add-top-category" data-gender="<?php echo esc_attr($gender); ?>">
                                    Add Category
                                </button>
                                
                                <div class="top-categories">
                                    <?php foreach ($gender_cats as $cat_id => $category): ?>
                                        <div class="category-item" data-id="<?php echo esc_attr($cat_id); ?>">
                                            <div class="category-header">
                                                <input type="text" name="categories[<?php echo esc_attr($gender); ?>][<?php echo esc_attr($cat_id); ?>][name]" 
                                                    value="<?php echo esc_attr($category['name']); ?>">
                                                <div class="category-actions">
                                                    <button type="button" class="add-subcategory">Add Subcategory</button>
                                                    <button type="button" class="remove-category">Remove</button>
                                                </div>
                                            </div>
                                            
                                            <?php if (!empty($category['subcategories'])): ?>
                                                <div class="subcategories">
                                                    <?php foreach ($category['subcategories'] as $subcat_id => $subcategory): ?>
                                                        <div class="subcategory-item" data-id="<?php echo esc_attr($subcat_id); ?>">
                                                            <input type="text" name="categories[<?php echo esc_attr($gender); ?>][<?php echo esc_attr($cat_id); ?>][subcategories][<?php echo esc_attr($subcat_id); ?>][name]" 
                                                                value="<?php echo esc_attr($subcategory['name']); ?>">
                                                            <button type="button" class="remove-subcategory">Remove</button>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="subcategories"></div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <p class="submit">
                    <input type="submit" name="pcf_save_categories" class="button button-primary" value="<?php echo esc_attr__('Save Categories', 'preowned-clothing-form'); ?>">
                </p>
            </form>
        </div>
    </div>
    
    <script>
        // Simple JavaScript to handle dynamic category management
        jQuery(document).ready(function($) {
            // Add top-level category
            $('.add-top-category').on('click', function() {
                const gender = $(this).data('gender');
                const newId = 'new_' + Math.random().toString(36).substr(2, 9);
                
                const newCategory = `
                    <div class="category-item" data-id="${newId}">
                        <div class="category-header">
                            <input type="text" name="categories[${gender}][${newId}][name]" placeholder="Category Name">
                            <div class="category-actions">
                                <button type="button" class="add-subcategory">Add Subcategory</button>
                                <button type="button" class="remove-category">Remove</button>
                            </div>
                        </div>
                        <div class="subcategories"></div>
                    </div>
                `;
                
                $(this).closest('.gender-section').find('.top-categories').append(newCategory);
            });
            
            // Add subcategory (using event delegation for dynamically created elements)
            $(document).on('click', '.add-subcategory', function() {
                const parentCategory = $(this).closest('.category-item');
                const gender = $(this).closest('.gender-section').find('h3').text().toLowerCase();
                const categoryId = parentCategory.data('id');
                const newId = 'new_' + Math.random().toString(36).substr(2, 9);
                
                const newSubcategory = `
                    <div class="subcategory-item" data-id="${newId}">
                        <input type="text" name="categories[${gender}][${categoryId}][subcategories][${newId}][name]" placeholder="Subcategory Name">
                        <button type="button" class="remove-subcategory">Remove</button>
                    </div>
                `;
                
                parentCategory.find('.subcategories').append(newSubcategory);
            });
            
            // Remove category
            $(document).on('click', '.remove-category', function() {
                if (confirm('Are you sure you want to remove this category and all its subcategories?')) {
                    $(this).closest('.category-item').remove();
                }
            });
            
            // Remove subcategory
            $(document).on('click', '.remove-subcategory', function() {
                if (confirm('Are you sure you want to remove this subcategory?')) {
                    $(this).closest('.subcategory-item').remove();
                }
            });
        });
    </script>
    
    <style>
        .pcf-category-tree {
            margin: 20px 0;
        }
        .gender-section {
            margin-bottom: 30px;
            background: #fff;
            padding: 15px;
            border: 1px solid #ddd;
        }
        .category-item {
            margin: 10px 0;
            padding: 10px;
            background: #f9f9f9;
            border: 1px solid #e5e5e5;
        }
        .category-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .subcategories {
            margin-left: 20px;
            padding: 10px;
            border-left: 3px solid #e5e5e5;
        }
        .subcategory-item {
            display: flex;
            align-items: center;
            margin: 5px 0;
        }
        .subcategory-item input {
            margin-right: 10px;
        }
    </style>
    <?php
}

function preowned_clothing_handle_category_save() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized access');
    }
    
    // Sanitize and save the categories
    $categories = array();
    
    if (isset($_POST['categories']) && is_array($_POST['categories'])) {
        foreach ($_POST['categories'] as $gender => $gender_cats) {
            $categories[$gender] = array();
            
            foreach ($gender_cats as $cat_id => $category) {
                $cat_name = sanitize_text_field($category['name']);
                if (empty($cat_name)) continue;
                
                $categories[$gender][$cat_id] = array(
                    'name' => $cat_name,
                    'subcategories' => array()
                );
                
                // Process subcategories if they exist
                if (isset($category['subcategories']) && is_array($category['subcategories'])) {
                    foreach ($category['subcategories'] as $subcat_id => $subcategory) {
                        $subcat_name = sanitize_text_field($subcategory['name']);
                        if (empty($subcat_name)) continue;
                        
                        $categories[$gender][$cat_id]['subcategories'][$subcat_id] = array(
                            'name' => $subcat_name
                        );
                    }
                }
            }
        }
    }
    
    // Save to database
    update_option('preowned_clothing_categories', $categories);
    
    // Show success message
    add_settings_error(
        'preowned_clothing_categories',
        'categories_updated',
        __('Categories have been updated successfully.', 'preowned-clothing-form'),
        'updated'
    );
}
