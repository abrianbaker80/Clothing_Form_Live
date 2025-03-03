/**
 * Category Handler for Clothing Form
 * Manages dynamic category selection in the clothing submission form
 */
(function($) {
    'use strict';
    
    // Global category data cache
    let categoriesData = null;
    let isLoadingCategories = false;
    
    /**
     * Initialize category handlers for all items
     */
    function initCategoryHandlers() {
        console.log('Initializing category handlers');
        
        // Load categories if not already cached
        loadCategoriesData().then(function(categories) {
            // Find all category containers and initialize them
            $('.category-select-container').each(function() {
                const containerId = $(this).attr('id');
                if (containerId) {
                    const itemId = containerId.split('-').pop();
                    initializeItemCategories(itemId, categories);
                }
            });
        });
        
        // Handle gender selection changes
        $(document).on('change', '.gender-select', function() {
            const $select = $(this);
            const itemId = $select.attr('id').split('-').pop();
            const selectedGender = $select.val();
            
            // Reset category container
            $(`#category-select-container-${itemId}`).empty();
            
            // Initialize categories based on selected gender
            if (selectedGender) {
                loadCategoriesData().then(function(categories) {
                    initializeItemCategoriesWithGender(itemId, categories, selectedGender);
                });
            }
        });
        
        // Smart search functionality
        $(document).on('input', '.category-smart-search', function() {
            handleSmartSearch($(this));
        });
        
        // Handle category selection
        $(document).on('change', '.category-select', function() {
            const $select = $(this);
            const level = parseInt($select.data('level'), 10);
            const itemId = $select.data('item');
            const selectedValue = $select.val();
            
            // Remove any subsequent level selects
            removeSubsequentSelects($select, level);
            
            // If a value is selected and it has children, add the next level
            if (selectedValue !== '' && categoriesData) {
                addNextLevelSelect($select, level, itemId, selectedValue);
            }
            
            // Update size options based on selected category
            updateSizeOptions(itemId);
            
            // Update review section if available
            if (typeof updateReviewSection === 'function') {
                updateReviewSection();
            }
        });
    }
    
    /**
     * Initialize categories for a specific item based on gender
     */
    function initializeItemCategoriesWithGender(itemId, categories, gender) {
        const container = $(`#category-select-container-${itemId}`);
        if (!container.length) return;
        
        container.empty();
        
        // Add smart search first
        const searchBox = $('<input>')
            .attr('type', 'text')
            .attr('placeholder', 'Type to search categories (e.g., "Dress" or "Jeans")')
            .addClass('category-smart-search')
            .data('item', itemId);
            
        container.append(searchBox);
        
        // Add initial level 0 select based on gender
        if (gender === 'womens' && categories.womens) {
            addCategorySelect(container, 0, itemId, categories.womens, 'Select Women\'s Category');
        } else if (gender === 'mens' && categories.mens) {
            addCategorySelect(container, 0, itemId, categories.mens, 'Select Men\'s Category');
        }
    }
    
    /**
     * Initialize categories for a specific item
     */
    function initializeItemCategories(itemId, categories) {
        const container = $(`#category-select-container-${itemId}`);
        if (!container.length) return;
        
        // Check if gender is selected
        const genderSelect = $(`#gender-${itemId}`);
        if (genderSelect.length) {
            const selectedGender = genderSelect.val();
            if (selectedGender) {
                initializeItemCategoriesWithGender(itemId, categories, selectedGender);
                return;
            }
        }
        
        // If no gender selected or gender select doesn't exist, 
        // don't initialize categories yet
        container.empty();
        container.append('<p class="category-notice">Please select gender first</p>');
    }
    
    /**
     * Load categories data from the server
     */
    function loadCategoriesData() {
        return new Promise(function(resolve, reject) {
            if (categoriesData !== null) {
                // Return cached data if available
                resolve(categoriesData);
                return;
            }
            
            if (isLoadingCategories) {
                // Check every 100ms if data is loaded
                const checkInterval = setInterval(function() {
                    if (categoriesData !== null) {
                        clearInterval(checkInterval);
                        resolve(categoriesData);
                    }
                }, 100);
                return;
            }
            
            isLoadingCategories = true;
            
            // AJAX request to get categories
            $.ajax({
                url: pcfFormOptions.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_clothing_categories',
                    nonce: pcfFormOptions.nonce
                },
                success: function(response) {
                    if (response.success && response.data) {
                        categoriesData = response.data;
                        resolve(categoriesData);
                    } else {
                        console.error('Failed to load category data');
                        reject('Invalid data received');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', error);
                    reject(error);
                },
                complete: function() {
                    isLoadingCategories = false;
                }
            });
        });
    }
    
    /**
     * Add a category select dropdown
     */
    function addCategorySelect(container, level, itemId, options, placeholder) {
        const selectId = `clothing_category_level_${level}_${itemId}`;
        const selectName = `items[${itemId}][category_level_${level}]`;
        
        const $select = $('<select>')
            .attr('id', selectId)
            .attr('name', selectName)
            .addClass('category-select')
            .data('level', level)
            .data('item', itemId);
            
        // Add placeholder option
        $select.append($('<option>').val('').text(placeholder || 'Select Option'));
        
        // Add category options
        if (options && typeof options === 'object') {
            // Check if it's a categories object or direct size_types object
            if (options.categories) {
                // It's a hierarchical category with subcategories
                $.each(options.categories, function(key, value) {
                    $select.append($('<option>').val(key).text(value.label || key));
                });
            } else {
                // Loop through each option
                $.each(options, function(key, value) {
                    // Only add if it has a label (to filter out size_types and other metadata)
                    if (value.label) {
                        $select.append($('<option>').val(key).text(value.label));
                    }
                });
            }
        }
        
        // Add to container
        const $wrapper = $('<div>').addClass(`category-level category-level-${level}`);
        $wrapper.append($select);
        container.append($wrapper);
        
        // If first level, make it required
        if (level === 0) {
            $select.attr('required', 'required');
        }
        
        return $select;
    }
    
    /**
     * Handle smart search functionality
     */
    function handleSmartSearch($input) {
        const query = $input.val().toLowerCase();
        const itemId = $input.data('item');
        
        // Don't process short queries
        if (query.length < 2) {
            return;
        }
        
        // Access global categories data
        if (!categoriesData) {
            loadCategoriesData();
            return;
        }
        
        // Simple search through the category data
        // This is a simplified approach - a more complete solution would search deeper
        const results = [];
        
        // Search function for recursive category traversal
        function searchCategories(categories, path = []) {
            if (!categories) return;
            
            // If it has a categories property, search within those
            if (categories.categories) {
                $.each(categories.categories, function(key, value) {
                    const newPath = [...path, value.label || key];
                    
                    // If the current category matches, add it
                    if ((value.label && value.label.toLowerCase().includes(query)) || key.toLowerCase().includes(query)) {
                        results.push({
                            path: newPath,
                            key: key,
                            category: value
                        });
                    }
                    
                    // Search within this category too
                    searchCategories(value, newPath);
                });
            }
        }
        
        // Start search at top level (women's and men's)
        searchCategories(categoriesData.womens, ['Womens']);
        searchCategories(categoriesData.mens, ['Mens']);
        
        // Display results
        displaySearchResults($input, itemId, results);
    }
    
    /**
     * Display search results as quick selection options
     */
    function displaySearchResults($input, itemId, results) {
        // Remove any existing results
        $('.search-results-dropdown').remove();
        
        // If no results, don't add dropdown
        if (results.length === 0) {
            return;
        }
        
        // Create results dropdown
        const $dropdown = $('<div>').addClass('search-results-dropdown');
        
        // Add results (limit to first 10)
        const maxResults = Math.min(results.length, 10);
        for (let i = 0; i < maxResults; i++) {
            const result = results[i];
            
            // Create result item
            const $result = $('<div>')
                .addClass('search-result-item')
                .text(result.path.join(' > '))
                .data('path', result.path)
                .data('key', result.key)
                .data('item', itemId);
                
            // Add click handler
            $result.on('click', function() {
                selectCategoryFromSearch($(this).data('path'), $(this).data('key'), $(this).data('item'));
                $('.search-results-dropdown').remove();
            });
            
            $dropdown.append($result);
        }
        
        // Add dropdown after input
        $input.after($dropdown);
    }
    
    /**
     * Select a category from the search results
     */
    function selectCategoryFromSearch(path, key, itemId) {
        // Remove existing selects
        $(`#category-select-container-${itemId} .category-level`).remove();
        
        // Now rebuild selects based on the path
        // This is a simplified version - a more complete solution would need to
        // navigate the full category hierarchy
        
        // Prefill search with the selected path
        $(`#category-select-container-${itemId} .category-smart-search`).val(path.join(' > '));
        
        // TODO: Build out select fields based on the path
        // This would require following the path through the category hierarchy
        
        // For now, reload the category selects
        initializeItemCategories(itemId, categoriesData);
    }
    
    /**
     * Remove all select elements after the current level
     */
    function removeSubsequentSelects($select, level) {
        const container = $select.closest('.category-select-container');
        container.find(`.category-level`).each(function() {
            const selectLevel = parseInt($(this).find('select').data('level'), 10);
            if (selectLevel > level) {
                $(this).remove();
            }
        });
    }
    
    /**
     * Add the next level select based on selected value
     */
    function addNextLevelSelect($select, level, itemId, selectedValue) {
        const container = $select.closest('.category-select-container');
        
        // Navigate to the current subcategory based on all previous selections
        let currentCategory = level === 0 ? categoriesData : null;
        
        // If we're not at the top level, we need to navigate to the correct subcategory
        if (level > 0) {
            currentCategory = findCurrentCategory(container, level);
        }
        
        // If we couldn't find the current category or it doesn't have subcategories, exit
        if (!currentCategory || !currentCategory.categories || !currentCategory.categories[selectedValue]) {
            return;
        }
        
        // Get the selected subcategory
        const selectedCategory = currentCategory.categories[selectedValue];
        
        // If it has its own categories, add a select for them
        if (selectedCategory && selectedCategory.categories) {
            addCategorySelect(container, level + 1, itemId, selectedCategory, `Select ${selectedCategory.label} Type`);
        }
        
        // Update size options based on the selection
        updateSizeOptions(itemId, selectedCategory);
    }
    
    /**
     * Find the current category based on previous selections
     */
    function findCurrentCategory(container, currentLevel) {
        let category = categoriesData;
        
        // Follow the path down from the top level
        for (let i = 0; i <= currentLevel; i++) {
            const select = container.find(`select[data-level="${i}"]`);
            const selectedValue = select.val();
            
            if (!selectedValue || !category || !category.categories || !category.categories[selectedValue]) {
                return null;
            }
            
            category = category.categories[selectedValue];
        }
        
        return category;
    }
    
    /**
     * Update size options based on selected category
     */
    function updateSizeOptions(itemId, category) {
        // Get the size dropdown for this item
        const $sizeSelect = $(`#size-${itemId}`);
        if (!$sizeSelect.length) {
            return;
        }
        
        // Clear existing options except the first one
        $sizeSelect.find('option:not(:first)').remove();
        
        // If no category with size_types, exit
        if (!category || !category.size_types) {
            return;
        }
        
        // Add new size options
        $.each(category.size_types, function(sizeType, sizes) {
            // Add size type as group
            const $group = $('<optgroup>').attr('label', sizeType);
            
            // Add individual sizes
            $.each(sizes, function(i, size) {
                $group.append($('<option>').val(size).text(size));
            });
            
            $sizeSelect.append($group);
        });
    }
    
    // Initialize on document ready
    $(document).ready(function() {
        initCategoryHandlers();
        
        // Re-init when items are added
        $(document).on('item_added', function(e, itemId) {
            loadCategoriesData().then(function(categories) {
                initializeItemCategories(itemId, categories);
            });
        });
    });
    
})(jQuery);
