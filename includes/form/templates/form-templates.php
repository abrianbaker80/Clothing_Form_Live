<?php
/**
 * HTML templates for JavaScript
 *
 * These templates are used by JavaScript to create new elements
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}
?>
<!-- Item template for JavaScript to clone when adding new items -->
<template id="item-template">
    <div class="clothing-item-container" data-item-id="[ITEM_ID]">
        <div class="clothing-item-header">
            <div class="clothing-item-title">
                <span class="item-number-badge">[ITEM_ID]</span>
                <span class="item-ordinal">[ORDINAL] Item</span>
            </div>
            <button type="button" class="remove-item-btn">×</button>
        </div>
        
        <!-- Gender selection -->
        <div class="form-group">
            <label for="gender-[ITEM_ID]">Gender <span class="required-indicator">*</span></label>
            <select id="gender-[ITEM_ID]" name="items[[ITEM_ID]][gender]" class="gender-select" required>
                <option value="">Select Gender</option>
                <option value="womens">Women's</option>
                <option value="mens">Men's</option>
            </select>
        </div>
        
        <!-- Category selection -->
        <div class="form-group category-group">
            <label for="category-[ITEM_ID]">Clothing Category <span class="required-indicator">*</span></label>
            <div class="category-select-container" id="category-select-container-[ITEM_ID]">
                <!-- Categories will be populated dynamically based on gender -->
                <select id="category-level-0-[ITEM_ID]" name="items[[ITEM_ID]][category_level_0]" class="category-select category-level-0" style="display: none;" required>
                    <option value="">Select Category</option>
                </select>
            </div>
            <div class="smart-search-hint">First select gender, then choose the appropriate clothing category</div>
        </div>
        
        <div class="form-group">
            <label for="size-[ITEM_ID]">Size (if applicable):</label>
            <select id="size-[ITEM_ID]" name="items[[ITEM_ID]][size]">
                <option value="">Not Applicable/Select Size</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="description-[ITEM_ID]">Description of Item <span class="required-indicator">*</span></label>
            <textarea id="description-[ITEM_ID]" name="items[[ITEM_ID]][description]" rows="4" required 
                      placeholder="Please include details about the condition, color, material, and any flaws or special features."
                      data-min-length="25"></textarea>
            <div class="description-quality-meter">
                <div class="quality-meter">
                    <div class="quality-fill"></div>
                </div>
            </div>
        </div>
    </div>
</template>

<!-- Photo Upload Template for Additional Items -->
<template id="photos-template">
    <div class="form-group additional-photos">
        <label>Item [ITEM_ID] Photos <span class="required-indicator">*</span></label>
        <div class="image-upload-container" data-max-size="<?php echo esc_attr($this->get_option('max_image_size', 2)); ?>">
            <?php 
            $image_types = array(
                'front' => array(
                    'label' => 'Front',
                    'hint' => 'Show the front of the garment laid flat or on a hanger',
                    'placeholder' => 'shirt-front.svg',
                    'required' => true
                ),
                'back' => array(
                    'label' => 'Back',
                    'hint' => 'Show the back of the garment laid flat or on a hanger',
                    'placeholder' => 'shirt-back.svg',
                    'required' => true
                ),
                'brand_tag' => array(
                    'label' => 'Brand Tag',
                    'hint' => 'Close-up of the brand/size tag',
                    'placeholder' => 'brand-tag.svg',
                    'required' => true
                ),
                'material_tag' => array(
                    'label' => 'Material Tag',
                    'hint' => 'Close-up of the fabric/care label',
                    'placeholder' => 'material-tag.svg',
                    'required' => false
                ),
                'detail' => array(
                    'label' => 'Detail',
                    'hint' => 'Any special details, damage, or distinctive features',
                    'placeholder' => 'detail-view.svg',
                    'required' => false
                )
            );

            foreach ($image_types as $type => $image_info) : ?>
                <div class="image-upload-box <?php echo $image_info['required'] ? 'required' : ''; ?>" data-type="<?php echo esc_attr($type); ?>">
                    <input type="file" name="items[[ITEM_ID]][images][<?php echo esc_attr($type); ?>]" id="images-[ITEM_ID]-<?php echo esc_attr($type); ?>" accept="image/*">
                    <div class="upload-placeholder">
                        <div class="upload-icon">
                            <?php if (!empty($image_info['placeholder'])) : ?>
                                <img src="<?php echo esc_url(plugin_dir_url(dirname(dirname(dirname(__FILE__)))) . 'assets/images/placeholders/' . $image_info['placeholder']); ?>" alt="<?php echo esc_attr($image_info['label']); ?>" class="placeholder-icon">
                            <?php else: ?>
                                <span class="dashicons dashicons-plus"></span>
                            <?php endif; ?>
                        </div>
                        <div class="upload-label"><?php echo esc_html($image_info['label']); ?></div>
                        <div class="upload-hint"><?php echo esc_html($image_info['hint']); ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</template>

<!-- Review Item Template -->
<template id="review-item-template">
    <div class="review-item-block">
        <h5>Item [ITEM_ID]: <span class="review-item-category">[CATEGORY]</span></h5>
        <div class="review-item"><strong>Size:</strong> <span class="review-item-size">[SIZE]</span></div>
        <div class="review-item"><strong>Description:</strong> <span class="review-item-description">[DESCRIPTION]</span></div>
        <div class="review-item">
            <strong>Photos:</strong> <span class="review-item-photos">[PHOTO_COUNT] photos selected</span>
        </div>
    </div>
</template>
