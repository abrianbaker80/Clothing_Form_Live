<?php
/**
 * Wizard Step 1 Template: Contact Information
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}
?>
<!-- Step 1: Contact Information -->
<div class="wizard-step active">
    <h3><i class="fas fa-user-circle"></i> Your Contact Information</h3>
    <div class="form-group">
        <label for="name">Your Name <span class="required-indicator">*</span></label>
        <input type="text" id="name" name="name" required>
    </div>
    <div class="form-group">
        <label for="email">Your Email <span class="required-indicator">*</span></label>
        <input type="email" id="email" name="email" required>
    </div>
</div>
