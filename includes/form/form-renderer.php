<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Form Renderer Class
 * Handles the rendering of the clothing submission form
 */
class PCF_Form_Renderer {
    // Store form options
    private $options = [];
    
    /**
     * Constructor
     * 
     * @param array $options Form display options
     */
    public function __construct($options = []) {
        // Set default options
        $this->options = wp_parse_args($options, [
            'form_title' => 'Submit Your Pre-owned Clothing',
            'form_intro' => 'You can submit multiple clothing items in a single form.',
            'max_items' => 10,
            'primary_color' => '#0073aa',
            'secondary_color' => '#005177',
            'max_image_size' => 2,
            'required_images' => ['front', 'back', 'brand_tag'],
        ]);
    }
    
    /**
     * Render the complete form
     * 
     * @return string The rendered form HTML
     */
    public function render() {
        ob_start();
        
        // Start the form container with custom styles
        $this->render_form_styles();
        
        echo '<div class="clothing-submission-form">';
        
        // Form title and introduction
        echo '<h2>' . esc_html($this->options['form_title']) . '</h2>';
        echo '<div class="form-guidance">';
        echo '<p>' . wp_kses_post($this->options['form_intro']) . '</p>';
        echo '</div>';
        
        // Start the form
        echo '<form action="' . esc_url($_SERVER['REQUEST_URI']) . '" method="post" enctype="multipart/form-data" id="clothing-form">';
        
        // Let other functions hook into the form start
        do_action('preowned_clothing_form_start');
        
        // Render the form steps
        $this->render_wizard_container();
        
        // End the form
        echo '</form>';
        
        // End the form container
        echo '</div>';
        
        return ob_get_clean();
    }
    
    /**
     * Render custom form styles
     */
    private function render_form_styles() {
        $primary_color = esc_attr($this->options['primary_color']);
        $secondary_color = esc_attr($this->options['secondary_color']);
        
        echo '<style>
            .clothing-submission-form .submit-button,
            .clothing-submission-form .item-number-badge,
            .wizard-navigation .wizard-btn {
                background-color: ' . $primary_color . ';
            }
            .clothing-submission-form .submit-button:hover,
            .wizard-navigation .wizard-btn:hover {
                background-color: ' . $secondary_color . ';
            }
            /* Additional custom styles would go here */
        </style>';
    }
    
    /**
     * Render the wizard container and steps
     */
    private function render_wizard_container() {
        // Progress bar and steps
        echo '<div class="progress-container">';
        echo '<div class="progress-bar"><div class="progress-bar-fill"></div></div>';
        echo '<div class="step-indicators">';
        echo '<div class="step-indicator active">1</div><div class="step-label">Contact Info</div>';
        echo '<div class="step-indicator">2</div><div class="step-label">Item Details</div>';
        echo '<div class="step-indicator">3</div><div class="step-label">Review</div>';
        echo '</div></div>';
        
        // Wizard container
        echo '<div class="wizard-container">';
        
        // Contact info step - Added address and phone fields
        $this->render_contact_step();
        
        // Item details step - Combined with photo upload
        $this->render_items_step();
        
        // Review step
        $this->render_review_step();
        
        echo '</div>';
        
        // Navigation buttons
        echo '<div class="wizard-navigation">';
        echo '<button type="button" class="wizard-btn wizard-prev"><i class="fas fa-arrow-left"></i> Previous</button>';
        echo '<button type="button" class="wizard-btn wizard-next">Next <i class="fas fa-arrow-right"></i></button>';
        echo '<button type="submit" class="wizard-btn wizard-submit" name="submit_clothing"><i class="fas fa-paper-plane"></i> Submit Items</button>';
        echo '</div>';
    }
    
    /**
     * Render contact step - Updated with address and phone
     */
    private function render_contact_step() {
        echo '<div class="wizard-step active">';
        echo '<h3><i class="fas fa-user-circle"></i> Your Contact Information</h3>';
        
        // Name field
        echo '<div class="form-group">';
        echo '<label for="name">Your Name <span class="required-indicator">*</span></label>';
        echo '<input type="text" id="name" name="name" required>';
        echo '</div>';
        
        // Email field
        echo '<div class="form-group">';
        echo '<label for="email">Your Email <span class="required-indicator">*</span></label>';
        echo '<input type="email" id="email" name="email" required>';
        echo '</div>';
        
        // Phone field (added)
        echo '<div class="form-group">';
        echo '<label for="phone">Your Phone Number <span class="required-indicator">*</span></label>';
        echo '<input type="tel" id="phone" name="phone" required>';
        echo '</div>';
        
        // Address fields (added)
        echo '<div class="form-group address-group">';
        echo '<label for="address">Your Address <span class="required-indicator">*</span></label>';
        echo '<input type="text" id="address" name="address" placeholder="Street Address" required>';
        echo '</div>';
        
        echo '<div class="form-row">';
        echo '<div class="form-group address-city">';
        echo '<label for="city">City <span class="required-indicator">*</span></label>';
        echo '<input type="text" id="city" name="city" required>';
        echo '</div>';
        
        echo '<div class="form-group address-state">';
        echo '<label for="state">State <span class="required-indicator">*</span></label>';
        echo '<input type="text" id="state" name="state" required>';
        echo '</div>';
        
        echo '<div class="form-group address-zip">';
        echo '<label for="zip">ZIP Code <span class="required-indicator">*</span></label>';
        echo '<input type="text" id="zip" name="zip" required>';
        echo '</div>';
        echo '</div>';
        
        echo '</div>'; // End step
    }
    
    /**
     * Render items step - Combined with photos
     */
    private function render_items_step() {
        echo '<div class="wizard-step">';
        echo '<h3><i class="fas fa-tshirt"></i> Item Details</h3>';
        echo '<p class="step-instruction">Fill in the details and upload photos for each clothing item.</p>';
        
        echo '<div id="items-container">';
        // First item container
        echo '<div class="clothing-item-container" data-item-id="1">';
        echo '<div class="clothing-item-header">';
        echo '<div class="clothing-item-title">';
        echo '<span class="item-number-badge">1</span>';
        echo '<span class="item-ordinal">First Item</span>';
        echo '</div>';
        echo '<button type="button" class="remove-item-btn" style="display: none;">×</button>';
        echo '</div>';
        
        // Gender selection (added)
        echo '<div class="form-group">';
        echo '<label for="gender-1">Gender <span class="required-indicator">*</span></label>';
        echo '<select id="gender-1" name="items[1][gender]" class="gender-select" required>';
        echo '<option value="">Select Gender</option>';
        echo '<option value="womens">Women\'s</option>';
        echo '<option value="mens">Men\'s</option>';
        echo '</select>';
        echo '</div>';
        
        // Category selection
        echo '<div class="form-group">';
        echo '<label for="clothing_category_level_0_1">Clothing Category <span class="required-indicator">*</span></label>';
        echo '<div class="category-select-container" id="category-select-container-1"></div>';
        echo '<div class="smart-search-hint">Try typing your item type (e.g., "dress" or "jeans")</div>';
        echo '</div>';
        
        // Size selection
        echo '<div class="form-group">';
        echo '<label for="size-1">Size <span class="required-indicator">*</span></label>';
        echo '<select id="size-1" name="items[1][size]" required>';
        echo '<option value="">Select Size</option>';
        echo '</select>';
        echo '</div>';
        
        // Item description
        echo '<div class="form-group">';
        echo '<label for="description-1">Description of Item <span class="required-indicator">*</span></label>';
        echo '<textarea id="description-1" name="items[1][description]" rows="4" required 
                    placeholder="Please include details about the condition, color, material, and any flaws (holes, stains, etc.) or special features."
                    data-min-length="25"></textarea>';
        echo '<div class="description-quality-meter">';
        echo '<div class="quality-meter"><div class="quality-fill"></div></div>';
        echo '</div>';
        echo '</div>';
        
        // Photo upload section (moved from step 3)
        echo '<div class="form-group">';
        echo '<label>Photos <span class="required-indicator">*</span></label>';
        echo '<p class="photo-instruction">Please provide clear photos of your item from different angles.</p>';
        
        echo '<div class="image-upload-container" data-max-size="' . esc_attr($this->options['max_image_size']) . '">';
        
        // Front view
        echo '<div class="image-upload-box required" data-type="front">';
        echo '<input type="file" name="items[1][images][front]" id="images-1-front" accept="image/*" required>';
        echo '<div class="upload-placeholder">';
        echo '<div class="upload-icon"><img src="' . esc_url(plugin_dir_url(dirname(dirname(__FILE__)))) . 'assets/images/placeholders/shirt-front.svg" alt="Front" class="placeholder-icon"></div>';
        echo '<div class="upload-label">Front</div>';
        echo '<div class="upload-hint">Show the front of the garment</div>';
        echo '</div>';
        echo '</div>';
        
        // Back view
        echo '<div class="image-upload-box required" data-type="back">';
        echo '<input type="file" name="items[1][images][back]" id="images-1-back" accept="image/*" required>';
        echo '<div class="upload-placeholder">';
        echo '<div class="upload-icon"><img src="' . esc_url(plugin_dir_url(dirname(dirname(__FILE__)))) . 'assets/images/placeholders/shirt-back.svg" alt="Back" class="placeholder-icon"></div>';
        echo '<div class="upload-label">Back</div>';
        echo '<div class="upload-hint">Show the back of the garment</div>';
        echo '</div>';
        echo '</div>';
        
        // Brand tag
        echo '<div class="image-upload-box required" data-type="brand_tag">';
        echo '<input type="file" name="items[1][images][brand_tag]" id="images-1-brand_tag" accept="image/*" required>';
        echo '<div class="upload-placeholder">';
        echo '<div class="upload-icon"><img src="' . esc_url(plugin_dir_url(dirname(dirname(__FILE__)))) . 'assets/images/placeholders/brand-tag.svg" alt="Brand Tag" class="placeholder-icon"></div>';
        echo '<div class="upload-label">Brand Tag</div>';
        echo '<div class="upload-hint">Close-up of the brand/size tag</div>';
        echo '</div>';
        echo '</div>';
        
        // Material tag
        echo '<div class="image-upload-box" data-type="material_tag">';
        echo '<input type="file" name="items[1][images][material_tag]" id="images-1-material_tag" accept="image/*">';
        echo '<div class="upload-placeholder">';
        echo '<div class="upload-icon"><img src="' . esc_url(plugin_dir_url(dirname(dirname(__FILE__)))) . 'assets/images/placeholders/material-tag.svg" alt="Material Tag" class="placeholder-icon"></div>';
        echo '<div class="upload-label">Material Tag</div>';
        echo '<div class="upload-hint">Close-up of the fabric/care label</div>';
        echo '</div>';
        echo '</div>';
        
        // Detail
        echo '<div class="image-upload-box" data-type="detail">';
        echo '<input type="file" name="items[1][images][detail]" id="images-1-detail" accept="image/*">';
        echo '<div class="upload-placeholder">';
        echo '<div class="upload-icon"><img src="' . esc_url(plugin_dir_url(dirname(dirname(__FILE__)))) . 'assets/images/placeholders/detail-view.svg" alt="Detail" class="placeholder-icon"></div>';
        echo '<div class="upload-label">Detail</div>';
        echo '<div class="upload-hint">Any special details, damage, or distinctive features</div>';
        echo '</div>';
        echo '</div>';
        
        echo '</div>'; // End image-upload-container
        echo '</div>'; // End form-group
        
        echo '</div>'; // End first clothing-item-container
        echo '</div>'; // End items-container
        
        // Add another item button
        echo '<div class="add-item-btn-container">';
        echo '<button type="button" id="add-item-btn" class="add-item-btn">';
        echo '<i class="fas fa-plus-circle"></i> Add Another Item';
        echo '</button>';
        echo '</div>';
        
        echo '</div>'; // End step
    }
    
    /**
     * Render review step
     */
    private function render_review_step() {
        echo '<div class="wizard-step">';
        echo '<h3><i class="fas fa-clipboard-check"></i> Review Your Submission</h3>';
        echo '<p class="step-instruction">Please review your information before submitting.</p>';
        
        echo '<div class="review-section">';
        echo '<h4>Contact Information</h4>';
        echo '<div class="review-item"><strong>Name:</strong> <span id="review-name"></span></div>';
        echo '<div class="review-item"><strong>Email:</strong> <span id="review-email"></span></div>';
        echo '<div class="review-item"><strong>Phone:</strong> <span id="review-phone"></span></div>';
        echo '<div class="review-item"><strong>Address:</strong> <span id="review-address"></span></div>';
        echo '</div>';
        
        echo '<div class="review-section">';
        echo '<h4>Item Details</h4>';
        echo '<div id="review-items-container"><!-- Will be populated by JavaScript --></div>';
        echo '</div>';
        
        echo '</div>'; // End step
    }
}
