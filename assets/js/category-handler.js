/**
 * Category Handler for Clothing Form
 */
jQuery(document).ready(function($) {
    // Debug info
    if (pcfFormOptions.debug) {
        console.log('PCF Category Handler loaded');
        console.log('Categories:', pcfFormOptions.categories);
    }
    
    // Handle category selection
    $(document).on('change', '.main-category', function() {
        const $select = $(this);
        const itemIndex = $select.closest('.clothing-item').data('item-index');
        const categoryKey = $select.val();
        const $subcategoryContainer = $('#subcategory-container-' + itemIndex);
        
        // Clear existing subcategories
        $subcategoryContainer.empty();
        
        if (!categoryKey) return; // Exit if no category selected
        
        // Get subcategories for this main category
        const category = pcfFormOptions.categories[categoryKey];
        if (!category || !category.subcategories) {
            console.error('No subcategories found for', categoryKey);
            return;
        }
        
        // Create subcategory dropdown
        const $subcategoryGroup = $('<div class="form-group"></div>');
        $subcategoryGroup.append('<label for="item-subcategory-' + itemIndex + '">Sub Category</label>');
        
        const $subcategorySelect = $('<select class="form-control subcategory" id="item-subcategory-' + itemIndex + '" name="items[' + itemIndex + '][subcategory]" required></select>');
        $subcategorySelect.append('<option value="">Select a Sub-Category</option>');
        
        // Add subcategory options
        $.each(category.subcategories, function(subKey, subcategory) {
            $subcategorySelect.append('<option value="' + subKey + '">' + subcategory.name + '</option>');
        });
        
        $subcategoryGroup.append($subcategorySelect);
        $subcategoryContainer.append($subcategoryGroup);
        
        // Set up event handler for subcategory changes (for deeper hierarchies)
        $subcategorySelect.on('change', function() {
            handleSubcategorySelection($(this), itemIndex);
        });
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
    
    // Call restore function when document is ready
    restoreCategorySelections();
});
