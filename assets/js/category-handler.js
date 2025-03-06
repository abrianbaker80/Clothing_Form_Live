/**
 * Category Handler for Clothing Form
 */
jQuery(document).ready(function($) {
    // Debug info - more detailed
    console.log('PCF Category Handler loaded');
    
    // Function to initialize categories
    function initializeCategories() {
        // First check if we have categories in pcfFormOptions
        if (typeof pcfFormOptions === 'undefined') {
            console.error('Error: pcfFormOptions is not defined');
            tryFetchingCategoriesViaAjax();
            return;
        }
        
        console.log('PCF Form Options:', pcfFormOptions);
        
        if (!pcfFormOptions.categories || $.isEmptyObject(pcfFormOptions.categories)) {
            console.error('Error: No categories found in pcfFormOptions');
            console.log('pcfFormOptions structure:', pcfFormOptions);
            tryFetchingCategoriesViaAjax();
            return;
        }
        
        // Log available categories for debugging
        console.log('Available categories:');
        $.each(pcfFormOptions.categories, function(key, category) {
            console.log(' - ' + key + ': ' + category.name);
        });
        
        // Check if we have any .main-category selects
        const $mainCategorySelects = $('.main-category');
        const $genderSelects = $('.gender-select');
        
        console.log('Found ' + $mainCategorySelects.length + ' main category selects');
        console.log('Found ' + $genderSelects.length + ' gender selects');
        
        if ($mainCategorySelects.length === 0 && $genderSelects.length === 0) {
            console.error('No category select elements found in the DOM');
            
            // Add diagnostic info to the page for admins
            if ($('.debug-info').length) {
                $('.debug-info').append(
                    '<div class="error-message" style="color: red;">' +
                    'ERROR: Category selects not found in the DOM. Form structure may be incorrect.' +
                    '</div>'
                );
            }
            return;
        }
        
        // Populate main category selects if they exist
        if ($mainCategorySelects.length > 0) {
            $mainCategorySelects.each(function() {
                const $select = $(this);
                // Only populate if empty
                if ($select.find('option').length <= 1) {
                    populateMainCategorySelect($select);
                }
                
                // Log populated options for debugging
                console.log('Populated select #' + $select.attr('id') + ' with options:', 
                    Array.from($select.find('option')).map(opt => opt.value + ': ' + opt.text).join(', ')
                );
            });
        }
        
        // Or initialize gender-based categories
        if ($genderSelects.length > 0) {
            console.log('Initializing gender-based categories');
            initializeGenderBasedCategories();
        }
    }
    
    // Function to try getting categories via AJAX
    function tryFetchingCategoriesViaAjax() {
        console.log('Trying to fetch categories via AJAX...');
        
        // Check if we have ajax_url
        if (typeof pcfFormOptions === 'undefined' || !pcfFormOptions.ajax_url) {
            console.error('Cannot fetch categories: ajax_url not defined');
            return;
        }
        
        $.ajax({
            url: pcfFormOptions.ajax_url,
            type: 'POST',
            data: {
                action: 'get_clothing_categories',
                nonce: pcfFormOptions.nonce || ''
            },
            success: function(response) {
                if (response.success && response.data) {
                    console.log('Successfully fetched categories via AJAX');
                    
                    // Store in pcfFormOptions
                    if (typeof pcfFormOptions === 'undefined') {
                        window.pcfFormOptions = {};
                    }
                    pcfFormOptions.categories = response.data;
                    
                    // Initialize categories
                    initializeCategories();
                } else {
                    console.error('Failed to fetch categories: Invalid response');
                }
            },
            error: function(xhr, status, error) {
                console.error('Failed to fetch categories:', error);
            }
        });
    }
    
    // Function to populate a main category select
    function populateMainCategorySelect($select) {
        // Keep the first option and append new ones
        const $firstOption = $select.find('option').first();
        $select.empty().append($firstOption);
        
        // Add options
        $.each(pcfFormOptions.categories, function(key, category) {
            $select.append('<option value="' + key + '">' + category.name + '</option>');
        });
    }
    
    // Function to initialize gender-based categories
    function initializeGenderBasedCategories() {
        $('.gender-select').on('change', function() {
            const $select = $(this);
            const itemId = $select.attr('id').replace('gender-', '');
            const gender = $select.val();
            
            if (!gender) return;
            
            // Reset the size selector when gender changes
            const $sizeSelect = $('#size-' + itemId);
            resetSizeSelector($sizeSelect, gender);
            
            // Get the category container
            const $categoryContainer = $('#category-select-container-' + itemId);
            $categoryContainer.empty();
            
            // Create category select for this gender
            const $categorySelect = $('<select>', {
                id: 'item-category-' + itemId,
                name: 'items[' + itemId + '][category]',
                'class': 'form-control item-category',
                required: true
            });
            
            $categorySelect.append('<option value="">Select Category</option>');
            
            // Get categories for this gender
            const genderCategory = pcfFormOptions.categories[gender];
            if (genderCategory && genderCategory.subcategories) {
                $.each(genderCategory.subcategories, function(key, category) {
                    $categorySelect.append('<option value="' + key + '">' + category.name + '</option>');
                });
                
                // Add to container
                $categoryContainer.append($('<div class="form-group"></div>').append($categorySelect));
                
                // Setup change handler for category
                $categorySelect.on('change', function() {
                    const categoryKey = $(this).val();
                    if (!categoryKey) return;
                    
                    handleCategorySelection(gender, categoryKey, itemId);
                    updateSizeOptions(gender, categoryKey, null, itemId);
                });
            } else {
                $categoryContainer.html('<p class="error">No categories found for ' + gender + '</p>');
            }
        });
    }
    
    // Function to handle category selection in gender-based approach
    function handleCategorySelection(gender, categoryKey, itemId) {
        // Get the subcategories
        const genderCategory = pcfFormOptions.categories[gender];
        const category = genderCategory.subcategories[categoryKey];
        
        if (!category || !category.subcategories) return;
        
        // Get the category container and clear any existing subcategory selects
        const $categoryContainer = $('#category-select-container-' + itemId);
        $categoryContainer.find('.subcategory-select').remove();
        
        // Create subcategory select
        const $subcategorySelect = $('<select>', {
            id: 'item-subcategory-' + itemId,
            name: 'items[' + itemId + '][subcategory]',
            'class': 'form-control item-subcategory subcategory-select',
            required: true
        });
        
        $subcategorySelect.append('<option value="">Select Type</option>');
        
        $.each(category.subcategories, function(key, subcategory) {
            $subcategorySelect.append('<option value="' + key + '">' + subcategory.name + '</option>');
        });
        
        // Add to container
        $categoryContainer.append($('<div class="form-group subcategory-select"></div>').append($subcategorySelect));
        
        // Set up event handler for subcategory changes
        $subcategorySelect.on('change', function() {
            const subcategoryKey = $(this).val();
            if (!subcategoryKey) return;
            
            updateSizeOptions(gender, categoryKey, subcategoryKey, itemId);
        });
    }
    
    // Handle category selection - Original approach
    $(document).on('change', '.main-category', function() {
        const $select = $(this);
        const itemIndex = $select.closest('.clothing-item').data('item-index');
        const categoryKey = $select.val();
        const $subcategoryContainer = $('#subcategory-container-' + itemIndex);
        
        console.log('Category selected:', categoryKey, 'for item', itemIndex);
        
        // Clear existing subcategories
        $subcategoryContainer.empty();
        
        if (!categoryKey) {
            console.log('No category selected, exiting');
            return; // Exit if no category selected
        }
        
        // Get subcategories for this main category
        const category = pcfFormOptions.categories[categoryKey];
        
        console.log('Category data:', category);
        
        if (!category || !category.subcategories) {
            console.error('No subcategories found for', categoryKey);
            $subcategoryContainer.html('<p class="error">Error: No subcategories found for this category</p>');
            return;
        }
        
        // Create subcategory dropdown
        const $subcategoryGroup = $('<div class="form-group"></div>');
        $subcategoryGroup.append('<label for="item-subcategory-' + itemIndex + '">Sub Category</label>');
        
        const $subcategorySelect = $('<select class="form-control subcategory" id="item-subcategory-' + itemIndex + '" name="items[' + itemIndex + '][subcategory]" required></select>');
        $subcategorySelect.append('<option value="">Select a Sub-Category</option>');
        
        // Add subcategory options
        let subcategoryCount = 0;
        $.each(category.subcategories, function(subKey, subcategory) {
            $subcategorySelect.append('<option value="' + subKey + '">' + subcategory.name + '</option>');
            subcategoryCount++;
        });
        
        console.log('Added', subcategoryCount, 'subcategories');
        
        $subcategoryGroup.append($subcategorySelect);
        $subcategoryContainer.append($subcategoryGroup);
        
        // Set up event handler for subcategory changes
        $subcategorySelect.on('change', function() {
            handleSubcategorySelection($(this), itemIndex);
            
            // Update size options when subcategory changes
            const gender = categoryKey; // In this approach, categoryKey is the gender
            const category = $subcategorySelect.val(); // Subcategory is actually the category
            updateSizeOptions(gender, category, null, itemIndex);
        });
        
        // Update size options when main category changes (gender level)
        updateSizeOptions(categoryKey, null, null, itemIndex);
    });
    
    // Handle subcategory selection
    function handleSubcategorySelection($select, itemIndex) {
        const mainCategoryKey = $('#item-category-' + itemIndex).val();
        const subcategoryKey = $select.val();
        const $container = $select.closest('.subcategory-container');
        
        // Remove any subsequent dropdowns
        $select.closest('.form-group').nextAll().remove();
        
        if (!subcategoryKey) return;
        
        // Get the subcategory data
        const mainCategory = pcfFormOptions.categories[mainCategoryKey];
        const subcategory = mainCategory.subcategories[subcategoryKey];
        
        // Check if we have deeper subcategories
        if (subcategory && subcategory.subcategories && Object.keys(subcategory.subcategories).length > 0) {
            // Create the next level dropdown
            const $nextGroup = $('<div class="form-group"></div>');
            $nextGroup.append('<label for="item-subcategory-' + itemIndex + '-level2">Specific Type</label>');
            
            const $nextSelect = $('<select class="form-control subcategory-level2" id="item-subcategory-' + itemIndex + '-level2" name="items[' + itemIndex + '][subcategory_level2]" required></select>');
            $nextSelect.append('<option value="">Select Option</option>');
            
            // Add options
            $.each(subcategory.subcategories, function(key, value) {
                $nextSelect.append('<option value="' + key + '">' + value.name + '</option>');
            });
            
            $nextGroup.append($nextSelect);
            $container.append($nextGroup);
            
            // Set up event handler for deeper levels if needed
            $nextSelect.on('change', function() {
                // You can extend this for deeper levels if needed
            });
        }
    }
    
    // Size selector functions
    
    // Reset the size selector to defaults
    function resetSizeSelector($sizeSelect, gender) {
        if (!$sizeSelect.length) return;
        
        $sizeSelect.empty();
        $sizeSelect.append('<option value="">Select Size</option>');
        
        // Add default sizes for the gender
        if (gender && pcfFormOptions.sizes && pcfFormOptions.sizes[gender] && pcfFormOptions.sizes[gender].default) {
            const sizes = pcfFormOptions.sizes[gender].default;
            $.each(sizes, function(i, size) {
                $sizeSelect.append('<option value="' + size + '">' + size + '</option>');
            });
        } else if (pcfFormOptions.sizes && pcfFormOptions.sizes.default) {
            // Use general defaults if no gender-specific defaults
            const sizes = pcfFormOptions.sizes.default;
            $.each(sizes, function(i, size) {
                $sizeSelect.append('<option value="' + size + '">' + size + '</option>');
            });
        }
    }
    
    // Update size options based on selected gender/category/subcategory
    function updateSizeOptions(gender, category, subcategory, itemId) {
        console.log('Updating sizes for:', gender, category, subcategory);
        
        // Get the size select element
        const $sizeSelect = $('#size-' + itemId);
        if (!$sizeSelect.length) {
            console.log('Size select not found for item', itemId);
            return;
        }
        
        // Reset the size selector
        $sizeSelect.empty();
        $sizeSelect.append('<option value="">Select Size</option>');
        
        // Determine which size array to use based on selections
        let sizes = [];
        
        if (pcfFormOptions.sizes) {
            // Try to find the most specific size array
            if (gender && category && subcategory && 
                pcfFormOptions.sizes[gender] && 
                pcfFormOptions.sizes[gender][category] && 
                pcfFormOptions.sizes[gender][category][subcategory]) {
                sizes = pcfFormOptions.sizes[gender][category][subcategory];
            }
            // Try category level
            else if (gender && category && 
                    pcfFormOptions.sizes[gender] && 
                    pcfFormOptions.sizes[gender][category]) {
                sizes = pcfFormOptions.sizes[gender][category];
            }
            // Try gender level defaults
            else if (gender && pcfFormOptions.sizes[gender] && pcfFormOptions.sizes[gender].default) {
                sizes = pcfFormOptions.sizes[gender].default;
            }
            // Use general defaults
            else {
                sizes = pcfFormOptions.sizes.default;
            }
        } else {
            // If no size data in pcfFormOptions, use hardcoded defaults
            sizes = ['One Size', 'XS', 'S', 'M', 'L', 'XL', 'XXL'];
        }
        
        // Add size options
        $.each(sizes, function(i, size) {
            $sizeSelect.append('<option value="' + size + '">' + size + '</option>');
        });
        
        console.log('Size options updated with', sizes.length, 'options');
    }
    
    // Restore previous selections if form has saved data
    function restoreCategorySelections() {
        if (typeof(Storage) !== "undefined" && localStorage.getItem("clothingFormData")) {
            const savedData = JSON.parse(localStorage.getItem("clothingFormData"));
            
            if (savedData.items) {
                $.each(savedData.items, function(index, item) {
                    if (item.category) {
                        const $categorySelect = $('#item-category-' + index);
                        if ($categorySelect.length) {
                            $categorySelect.val(item.category).trigger('change');
                            
                            // Short timeout to allow the subcategory dropdown to be created
                            setTimeout(function() {
                                if (item.subcategory) {
                                    const $subcategorySelect = $('#item-subcategory-' + index);
                                    if ($subcategorySelect.length) {
                                        $subcategorySelect.val(item.subcategory).trigger('change');
                                    }
                                }
                            }, 100);
                        }
                    }
                });
            }
        }
    }
    
    // Initialize categories once the DOM is ready
    setTimeout(function() {
        console.log('Triggering category initialization');
        initializeCategories();
        
        // Also try to restore previous selections if they exist
        if (typeof restoreCategorySelections === 'function') {
            setTimeout(restoreCategorySelections, 300);
        }
    }, 500);
    
    // Initialize all size selectors with default sizes
    $('.gender-select').each(function() {
        const $select = $(this);
        const itemId = $select.attr('id').replace('gender-', '');
        const $sizeSelect = $('#size-' + itemId);
        
        resetSizeSelector($sizeSelect, null);
    });
    
    // Add a global test function for admin debugging
    window.testCategoriesData = function() {
        console.log('==== TESTING CATEGORIES DATA ====');
        console.log('pcfFormOptions available:', typeof pcfFormOptions !== 'undefined');
        if (typeof pcfFormOptions !== 'undefined') {
            console.log('categories property exists:', typeof pcfFormOptions.categories !== 'undefined');
            console.log('categories count:', Object.keys(pcfFormOptions.categories || {}).length);
            console.log('categories data:', pcfFormOptions.categories);
        }
        console.log('================================');
        
        // Also output to page for admin users
        if ($('.debug-info').length) {
            let output = '<div style="background:#f0f0f0;padding:10px;margin:10px 0;font-family:monospace;">';
            output += '<h4>Categories Data Test</h4>';
            output += '<p>pcfFormOptions available: ' + (typeof pcfFormOptions !== 'undefined') + '</p>';
            
            if (typeof pcfFormOptions !== 'undefined') {
                output += '<p>categories property exists: ' + (typeof pcfFormOptions.categories !== 'undefined') + '</p>';
                output += '<p>categories count: ' + Object.keys(pcfFormOptions.categories || {}).length + '</p>';
                
                // Add information about DOM elements
                output += '<p>Main category selects found: ' + $('.main-category').length + '</p>';
                output += '<p>Gender selects found: ' + $('.gender-select').length + '</p>';
                output += '<p>Subcategory containers found: ' + $('.subcategory-container').length + '</p>';
            }
            
            output += '</div>';
            $('.debug-info').append(output);
        }
        
        // Also test sizes data
        console.log('Sizes data available:', typeof pcfFormOptions !== 'undefined' && typeof pcfFormOptions.sizes !== 'undefined');
        if (typeof pcfFormOptions !== 'undefined' && typeof pcfFormOptions.sizes !== 'undefined') {
            console.log('Sizes data:', pcfFormOptions.sizes);
        }
        
        // Also output to page for admin users
        if ($('.debug-info').length) {
            let output = '<div style="background:#f0f0f0;padding:10px;margin:10px 0;font-family:monospace;">';
            // ...existing code...
            
            if (typeof pcfFormOptions !== 'undefined' && typeof pcfFormOptions.sizes !== 'undefined') {
                output += '<p>Sizes data available: Yes</p>';
                
                // Check size selectors
                output += '<p>Size selects found: ' + $('select[id^="size-"]').length + '</p>';
                output += '<p>Size selects with options: ' + $('select[id^="size-"] option').length + '</p>';
            } else {
                output += '<p style="color:red">Sizes data missing!</p>';
            }
            
            output += '</div>';
            $('..debug-info').append(output);
        }
        
        return typeof pcfFormOptions !== 'undefined' && 
               typeof pcfFormOptions.categories !== 'undefined' &&
               typeof pcfFormOptions.sizes !== 'undefined';
    };
    
    // Run the test automatically
    setTimeout(function() {
        console.log('Running categories data test');
        window.testCategoriesData();
    }, 1000);
});
