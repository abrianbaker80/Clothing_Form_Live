<?php


/**
 * Wizard Step 2 Template: Item Details
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Get max_items from passed parameters or use default
$max_items = isset($max_items) ? $max_items : 10;
?>
<!-- Step 2: Item Details -->
<div class="wizard-step">
    <h3><i class="fas fa-tshirt"></i> Item Details</h3>
    <p class="step-instruction">Select the appropriate category and provide details for each item.</p>

    <!-- Debug Panel - show for admins in debug mode -->
    <div class="debug-panel"
        style="background: #f8f9fa; border: 1px solid #ddd; padding: 10px; margin-bottom: 20px; <?php echo (current_user_can('manage_options') && defined('WP_DEBUG') && WP_DEBUG) ? '' : 'display: none;'; ?>">
        <h4>Debug Info</h4>
        <button type="button" id="check-scripts" class="button">Check Scripts</button>
        <button type="button" id="test-ajax" class="button">Test Ajax</button>
        <button type="button" id="check-gender-selects" class="button">Check Gender Selects</button>
        <button type="button" id="force-initialize" class="button">Force Initialize</button>
        <div id="debug-output"
            style="margin-top: 10px; padding: 10px; background: #fff; border: 1px solid #eee; max-height: 150px; overflow: auto;">
        </div>
    </div>
    <script>
        jQuery(document).ready(function ($) {
            // Show debug panel if needed
            if (typeof pcfFormOptions !== 'undefined' && pcfFormOptions.debug) {
                $('.debug-panel').show();
            }

            // Check scripts button
            $('#check-scripts').on('click', function () {
                var output = '';
                output += "pcfFormOptions available: " + (typeof pcfFormOptions !== 'undefined') + "\n";

                if (typeof pcfFormOptions !== 'undefined') {
                    output += "categories property exists: " + (typeof pcfFormOptions.categories !== 'undefined') + "\n";
                    output += "categories count: " + Object.keys(pcfFormOptions.categories || {}).length + "\n";

                    // Check initialization functions
                    output += "initializeCategories function available: " + (typeof window.initializeCategories === "function") + "\n";
                    output += "initializeGenderBasedCategories function available: " + (typeof window.initializeGenderBasedCategories === "function") + "\n";
                }

                // Check DOM elements
                output += "Gender select elements: " + $('.gender-select').length + "\n";
                output += "Category containers: " + $('[id^="category-select-container-"]').length + "\n";

                $('#debug-output').html('<pre>' + output + '</pre>');
            });

            // Test Ajax button
            $('#test-ajax').on('click', function () {
                $('#debug-output').html('<pre>Testing Ajax call...</pre>');

                $.ajax({
                    url: pcfFormOptions.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'pcf_debug_categories',
                        nonce: pcfFormOptions.nonce
                    },
                    success: function (response) {
                        $('#debug-output').html('<pre>Ajax Success:\n' + JSON.stringify(response, null, 2) + '</pre>');
                    },
                    error: function (xhr, status, error) {
                        $('#debug-output').html('<pre>Ajax Error:\n' + error + '\n\nStatus: ' + status + '</pre>');
                    }
                });
            });

            // Check gender selects
            $('#check-gender-selects').on('click', function () {
                const $genderSelects = $('.gender-select');
                let output = "Found " + $genderSelects.length + " gender selects\n";

                $genderSelects.each(function (index) {
                    const $select = $(this);
                    const id = $select.attr('id');
                    const val = $select.val();
                    const itemId = id.replace('gender-', '');
                    const $container = $('#category-select-container-' + itemId);

                    output += "\nSelect #" + index + ":\n";
                    output += "  id: " + id + "\n";
                    output += "  value: " + val + "\n";
                    output += "  container exists: " + ($container.length > 0) + "\n";
                    output += "  container contents: " + $container.html().substring(0, 50) + "...\n";
                });

                $('#debug-output').html('<pre>' + output + '</pre>');
            });

            // Force initialize button
            $('#force-initialize').on('click', function () {
                if (typeof window.initializeGenderBasedCategories === "function") {
                    $('#debug-output').html('<pre>Forcing initialization...</pre>');
                    window.initializeGenderBasedCategories();

                    setTimeout(function () {
                        const $containers = $('[id^="category-select-container-"]');
                        let output = "Initialization complete.\n";
                        output += "Category containers: " + $containers.length + "\n";
                        $containers.each(function (index) {
                            output += "Container #" + index + " contents: " + $(this).html().substring(0, 50) + "...\n";
                        });
                        $('#debug-output').html('<pre>' + output + '</pre>');
                    }, 500);
                } else {
                    $('#debug-output').html('<pre>Error: initialization function not available</pre>');
                }
            });
        });
    </script>


    <div id="items-container">
        <div class="clothing-item-container" data-item-id="1">
            <!-- Gender selection -->
            <div class="form-group">
                <label for="gender-1">Gender <span class="required-indicator">*</span></label>
                <select id="gender-1" name="items[1][gender]" class="gender-select" required>
                    <option value="">Select Gender</option>
                    <option value="womens">Women's</option>
                    <option value="mens">Men's</option>
                </select>
            </div>

            <!-- Category selection - FIXED: Make sure the container ID matches what JS expects -->
            <div class="form-group category-group">
                <label for="category-select-container-1">Clothing Category <span
                        class="required-indicator">*</span></label>
                <div id="category-select-container-1" class="category-select-container">
                    <!-- Categories will be populated dynamically based on gender selection -->
                    <div class="category-notice">Please select a gender first</div>
                </div>
                <div class="smart-search-hint">First select gender, then choose the appropriate category</div>
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

    <button type="button" id="add-item-btn" class="add-item-btn" data-max-items="<?php echo esc_attr($max_items); ?>">
        <i class="fas fa-plus-circle"></i> Add Another Item
    </button>
</div>

<script>
    jQuery(document).ready(function ($) {
        if (typeof window.pcfCategoryHandler !== 'undefined' &&
            typeof window.pcfCategoryHandler.initGenderSelection === 'function') {
            jQuery(document).ready(function ($) {
                if (typeof window.pcfCategoryHandler !== 'undefined' &&
                    typeof window.pcfCategoryHandler.initGenderSelection === 'function') {
                    window.pcfCategoryHandler.initGenderSelection(1);
                } else {
                    console.error('Category handler not properly initialized');
                    console.error('Category handler not properly initialized');
                }
            });
        });
</script>