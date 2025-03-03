<?php
/**
 * Wizard Step 2 Template: Item Details
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

$max_items = $this->get_option('max_items', 10);
?>
<!-- Step 2: Item Details -->
<div class="wizard-step">
    <h3><i class="fas fa-tshirt"></i> Item Details</h3>
    <p class="step-instruction">Select the appropriate category and provide details for each item.</p>
    
    <!-- Debug Panel - remove in production -->
    <div class="debug-panel" style="background: #f8f9fa; border: 1px solid #ddd; padding: 10px; margin-bottom: 20px; display: none;">
        <h4>Debug Info</h4>
        <button type="button" id="check-scripts" class="button">Check Scripts</button>
        <button type="button" id="test-ajax" class="button">Test Ajax</button>
        <div id="debug-output" style="margin-top: 10px; padding: 10px; background: #fff; border: 1px solid #eee; max-height: 150px; overflow: auto;"></div>
    </div>
    <script>
    jQuery(document).ready(function($) {
        // Show debug panel if needed
        if (typeof pcfFormOptions !== 'undefined' && pcfFormOptions.debug) {
            $('.debug-panel').show();
        }
        
        // Check scripts button
        $('#check-scripts').on('click', function() {
            var output = '';
            if (typeof pcfCategoryHandler !== 'undefined') {
                output += "✓ Category Handler script loaded\n";
                output += "Category Data: " + (pcfCategoryHandler.getCategoryData() ? "Loaded" : "Not Loaded") + "\n";
            } else {
                output += "✗ Category Handler script NOT loaded\n";
            }
            
            if (typeof pcfFormOptions !== 'undefined') {
                output += "✓ Form options available\n";
                output += "Ajax URL: " + pcfFormOptions.ajax_url + "\n";
            } else {
                output += "✗ Form options NOT available\n";
            }
            
            $('#debug-output').html('<pre>' + output + '</pre>');
        });
        
        // Test Ajax button
        $('#test-ajax').on('click', function() {
            $('#debug-output').html('<pre>Testing Ajax call...</pre>');
            
            $.ajax({
                url: pcfFormOptions.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_clothing_categories',
                    nonce: pcfFormOptions.nonce
                },
                success: function(response) {
                    $('#debug-output').html('<pre>Ajax Success:\n' + JSON.stringify(response, null, 2) + '</pre>');
                },
                error: function(xhr, status, error) {
                    $('#debug-output').html('<pre>Ajax Error:\n' + error + '\n\nStatus: ' + status + '</pre>');
                }
            });
        });
    });
    </script>
    
    <div id="items-container">
        <div class="clothing-item-container" data-item-id="1">
            <div class="clothing-item-header">
                <div class="clothing-item-title">
                    <span class="item-number-badge">1</span>
                    <span class="item-ordinal">First Item</span>
                </div>
                <button type="button" class="remove-item-btn" style="display: none;">×</button>
            </div>
            
            <!-- Gender selection -->
            <div class="form-group">
                <label for="gender-1">Gender <span class="required-indicator">*</span></label>
                <select id="gender-1" name="items[1][gender]" class="gender-select" required>
                    <option value="">Select Gender</option>
                    <option value="womens">Women's</option>
                    <option value="mens">Men's</option>
                </select>
            </div>
            
            <!-- Category selection -->
            <div class="form-group category-group">
                <label for="category-1">Clothing Category <span class="required-indicator">*</span></label>
                <div class="category-select-container" id="category-select-container-1">
                    <!-- Categories will be populated dynamically based on gender -->
                    <select id="category-level-0-1" name="items[1][category_level_0]" class="category-select category-level-0" style="display: none;" required>
                        <option value="">Select Category</option>
                    </select>
                </div>
                <div class="smart-search-hint">First select gender, then choose the appropriate clothing category</div>
            </div>
            
            <!-- Size selection -->
            <div class="form-group">
                <label for="size-1">Size (if applicable):</label>
                <select id="size-1" name="items[1][size]">
                    <option value="">Not Applicable/Select Size</option>
                    <!-- Size options will be populated based on category -->
                </select>
            </div>
            
            <!-- Item description -->
            <div class="form-group">
                <label for="description-1">Description of Item <span class="required-indicator">*</span></label>
                <textarea id="description-1" name="items[1][description]" rows="4" required 
                          placeholder="Please include details about the condition, color, material, and any flaws or special features."
                          data-min-length="25"></textarea>
                <div class="description-quality-meter">
                    <div class="quality-meter">
                        <div class="quality-fill"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add another item button -->
    <div class="add-item-btn-container">
        <button type="button" id="add-item-btn" class="add-item-btn">
            <i class="fas fa-plus-circle"></i> Add Another Item
        </button>
    </div>
</div>

<!-- Force category handler initialization - remove in production -->
<script>
jQuery(document).ready(function($) {
    setTimeout(function() {
        if (window.pcfCategoryHandler && typeof window.pcfCategoryHandler.initGenderSelection === 'function') {
            console.log("Force initializing gender selection for Item 1");
            window.pcfCategoryHandler.initGenderSelection(1);
        } else {
            console.error("Category handler not available - cannot initialize selections");
        }
    }, 1000);
});
</script>