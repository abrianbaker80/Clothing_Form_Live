<?php
/**
 * Main form template
 *
 * This is the main template for the clothing submission form
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

global $clothing_categories_hierarchical;

// Get form options from renderer
$form_title = $renderer->get_option('form_title', 'Submit Your Pre-owned Clothing');
$form_intro = $renderer->get_option('form_intro', 'You can submit multiple clothing items...');
$max_items = intval($renderer->get_option('max_items', 10));
$primary_color = $renderer->get_option('primary_color', '#0073aa');
$max_image_size = intval($renderer->get_option('max_image_size', 2));
?>

<div class="clothing-submission-form">
    <h2><?php echo esc_html($form_title); ?></h2>
    
    <div class="form-guidance">
        <h3><i class="fas fa-info-circle"></i> <span>Submission Guidelines</span></h3>
        <p><?php echo wp_kses_post($form_intro); ?></p>
        <ul>
            <li><strong>Select categories</strong> - Choose the appropriate type for each item</li>
            <li><strong>Provide details</strong> - Include condition, colors, and any flaws</li>
            <li><strong>Upload photos</strong> - Clear images showing the item from multiple angles</li>
        </ul>
    </div>
    
    <form action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>" method="post" enctype="multipart/form-data" id="clothing-form">
        <?php wp_nonce_field('clothing_form_submission', 'clothing_form_nonce'); ?>
        
        <!-- Progress bar and step indicators -->
        <div class="progress-container">
            <div class="progress-bar">
                <div class="progress-bar-fill"></div>
            </div>
            <div class="step-indicators">
                <div class="step-indicator active">1</div>
                <div class="step-label">Contact Info</div>
                
                <div class="step-indicator">2</div>
                <div class="step-label">Item Details</div>
                
                <div class="step-indicator">3</div>
                <div class="step-label">Photos</div>
                
                <div class="step-indicator">4</div>
                <div class="step-label">Review</div>
            </div>
        </div>
        
        <!-- Wizard Container -->
        <div class="wizard-container">
            <?php 
            // Render each step
            for ($i = 1; $i <= 4; $i++) {
                $renderer->render_step($i);
            }
            ?>
        </div>
        
        <!-- Wizard Navigation -->
        <div class="wizard-navigation">
            <button type="button" class="wizard-btn wizard-prev">
                <i class="fas fa-arrow-left"></i> Previous
            </button>
            <button type="button" class="wizard-btn wizard-next">
                Next <i class="fas fa-arrow-right"></i>
            </button>
            <button type="submit" class="wizard-btn wizard-submit" name="submit_clothing">
                <i class="fas fa-paper-plane"></i> Submit Items
            </button>
        </div>
        
        <!-- Templates for JavaScript -->
        <?php include(dirname(__FILE__) . '/form-templates.php'); ?>
    </form>
</div>
