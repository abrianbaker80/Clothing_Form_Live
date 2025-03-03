<?php
/**
 * Wizard Step 3 Template: Photos
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Get settings from the class if available, otherwise use defaults
$max_image_size = 2;
$required_images = ['front', 'back', 'brand_tag'];

// Check if we're in an object context by examining backtrace
$in_object_context = false;
if (function_exists('debug_backtrace')) {
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2);
    $in_object_context = isset($backtrace[1]['object']) && is_object($backtrace[1]['object']) && method_exists($backtrace[1]['object'], 'get_option');
    if ($in_object_context) {
        $object = $backtrace[1]['object'];
        $max_image_size = $object->get_option('max_image_size', 2);
        $required_images = $object->get_option('required_images', ['front', 'back', 'brand_tag']);
    }
}

// Define image types with guidance text and placeholder icons
$image_types = array(
    'front' => array(
        'label' => 'Front',
        'hint' => 'Show the front of the garment laid flat or on a hanger',
        'placeholder' => 'shirt-front.svg',
        'required' => in_array('front', $required_images)
    ),
    'back' => array(
        'label' => 'Back',
        'hint' => 'Show the back of the garment laid flat or on a hanger',
        'placeholder' => 'shirt-back.svg',
        'required' => in_array('back', $required_images)
    ),
    'brand_tag' => array(
        'label' => 'Brand Tag',
        'hint' => 'Close-up of the brand/size tag',
        'placeholder' => 'brand-tag.svg',
        'required' => in_array('brand_tag', $required_images)
    ),
    'material_tag' => array(
        'label' => 'Material Tag',
        'hint' => 'Close-up of the fabric/care label',
        'placeholder' => 'material-tag.svg',
        'required' => in_array('material_tag', $required_images)
    ),
    'detail' => array(
        'label' => 'Detail',
        'hint' => 'Any special details, damage, or distinctive features',
        'placeholder' => 'detail-view.svg',
        'required' => in_array('detail', $required_images)
    )
);
?>

<!-- Step 3: Photos -->
<div class="wizard-step">
    <h3><i class="fas fa-images"></i> Upload Photos</h3>
    <p class="step-instruction">Please upload clear photos of your item from different angles.</p>
    
    <div class="form-group">
        <label>Item 1 Photos <span class="required-indicator">*</span></label>
        <div class="image-upload-container" data-max-size="<?php echo esc_attr($max_image_size); ?>">
            <?php foreach ($image_types as $type => $image_info) : ?>
                <div class="image-upload-box <?php echo $image_info['required'] ? 'required' : ''; ?>" data-type="<?php echo esc_attr($type); ?>">
                    <input type="file" name="items[1][images][<?php echo esc_attr($type); ?>]" id="images-1-<?php echo esc_attr($type); ?>" accept="image/*" <?php echo $image_info['required'] ? 'required' : ''; ?>>
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
        
        <!-- Help tip -->
        <div class="upload-help-tip">
            <p><i class="fas fa-info-circle"></i> Maximum file size: <?php echo esc_html($max_image_size); ?>MB. Accepted formats: JPEG, PNG</p>
            <p>You can also drag and drop images directly onto the upload boxes.</p>
        </div>
    </div>
    
    <!-- Additional item photo sections will be added here by JavaScript -->
    <div id="additional-photos-container"></div>
</div>

<!-- Image processing script -->
<script>
jQuery(document).ready(function($) {
    // Initialize image uploads for first item
    if (window.pcfImageUpload && typeof window.pcfImageUpload.initializeImageUploads === 'function') {
        setTimeout(function() {
            window.pcfImageUpload.initializeImageUploads(1);
        }, 500);
    } else {
        console.error('Image upload handler not available');
    }
});
</script>
