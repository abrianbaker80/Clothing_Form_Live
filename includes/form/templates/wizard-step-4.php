<?php
/**
 * Wizard Step 4 Template: Review
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}
?>

<!-- Step 4: Review -->
<div class="wizard-step">
    <h3><i class="fas fa-clipboard-check"></i> Review Your Submission</h3>
    <p class="step-instruction">Please review your information before submitting.</p>
    
    <div class="review-section">
        <h4>Contact Information</h4>
        <div class="review-item">
            <strong>Name:</strong> <span id="review-name"></span>
        </div>
        <div class="review-item">
            <strong>Email:</strong> <span id="review-email"></span>
        </div>
    </div>
    
    <div class="review-section">
        <h4>Item Details</h4>
        <div id="review-items-container">
            <!-- Will be populated dynamically by JavaScript -->
        </div>
    </div>
    
    <div class="review-section">
        <h4>Terms & Conditions</h4>
        <div class="form-group">
            <label for="terms-checkbox" class="checkbox-label">
                <input type="checkbox" id="terms-checkbox" name="terms_agreed" required>
                I agree to the <a href="#" data-toggle="modal" data-target="#termsModal">terms and conditions</a> and consent to the processing of my personal information.
                <span class="required-indicator">*</span>
            </label>
        </div>
    </div>
    
    <div class="review-section">
        <div class="form-notice">
            <p><i class="fas fa-info-circle"></i> By clicking submit, you confirm that all information provided is accurate to the best of your knowledge.</p>
            <p>Our team will review your submission and contact you within 24-48 hours.</p>
        </div>
    </div>
</div>
