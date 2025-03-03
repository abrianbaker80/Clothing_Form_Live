/**
 * Enhanced Category Handler for Preowned Clothing Form
 * Handles hierarchical selection of gender → category → subcategory → size
 */
jQuery(document).ready(function($) {
    console.log('Category handler initialized');
    
    // Cache DOM elements
    const $genderField = $('#clothing_gender');
    const $categoryField = $('#clothing_category');
    const $subcategoryField = $('#clothing_subcategory');
    const $sizeField = $('#clothing_size');
    
    // Cache container elements
    const $categoryContainer = $('#category_container');
    const $subcategoryContainer = $('#subcategory_container');
    const $sizeContainer = $('#size_container');
    
    // Track our current categories data
    let categoriesData = null;
    let currentGender = null;
    let currentCategory = null;
    let currentSubcategory = null;
    
    // Function to fetch categories data via AJAX
    function fetchCategoriesData() {
        console.log('Fetching category data...');
        
        // Initially hide the category, subcategory and size fields
        $categoryContainer.hide();
        $subcategoryContainer.hide();
        $sizeContainer.hide();
        
        // Make AJAX request to get categories
        $.ajax({
            url: pcfFormOptions.ajax_url,
            type: 'POST',
            data: {
                action: 'get_clothing_categories',
                nonce: pcfFormOptions.nonce
            },
            success: function(response) {
                console.log('Categories data received');
                if (response.success && response.data) {
                    categoriesData = response.data;
                    initializeGenderDropdown();
                } else {
                    console.error('Error fetching categories data:', response);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error);
            }
        });
    }
    
    // Initialize the gender dropdown
    function initializeGenderDropdown() {
        // Clear current options
        $genderField.empty();
        
        // Add default option
        $genderField.append($('<option>', {
            value: '',
            text: 'Select Gender',
            disabled: true,
            selected: true
        }));
        
        // Add gender options
        if (categoriesData && categoriesData.gender) {
            $.each(categoriesData.gender, function(key, data) {
                $genderField.append($('<option>', {
                    value: key,
                    text: data.label
                }));
            });
            
            // Show gender field
            $genderField.closest('.form-group').show();
        }
    }
    
    // Handle gender selection
    function handleGenderChange() {
        const gender = $genderField.val();
        currentGender = gender;
        
        // Clear subsequent fields
        $categoryField.empty();
        $subcategoryField.empty();
        $sizeField.empty();
        
        // Hide subsequent containers
        $subcategoryContainer.hide();
        $sizeContainer.hide();
        
        if (gender && categoriesData.gender[gender]) {
            populateCategoryDropdown(gender);
            $categoryContainer.show();
        } else {
            $categoryContainer.hide();
        }
    }
    
    // Populate category dropdown based on selected gender
    function populateCategoryDropdown(gender) {
        // Clear current options
        $categoryField.empty();
        
        // Add default option
        $categoryField.append($('<option>', {
            value: '',
            text: 'Select Category',
            disabled: true,
            selected: true
        }));
        
        // Add category options for the selected gender
        const categories = categoriesData.gender[gender].categories;
        
        $.each(categories, function(key, data) {
            $categoryField.append($('<option>', {
                value: key,
                text: data.label
            }));
        });
    }
    
    // Handle category selection
    function handleCategoryChange() {
        const category = $categoryField.val();
        currentCategory = category;
        
        // Clear subsequent fields
        $subcategoryField.empty();
        $sizeField.empty();
        
        // Hide size container
        $sizeContainer.hide();
        
        if (category && 
            categoriesData.gender[currentGender].categories[category].subcategories) {
            populateSubcategoryDropdown(currentGender, category);
            $subcategoryContainer.show();
        } else {
            $subcategoryContainer.hide();
        }
    }
    
    // Populate subcategory dropdown based on selected gender and category
    function populateSubcategoryDropdown(gender, category) {
        // Clear current options
        $subcategoryField.empty();
        
        // Add default option
        $subcategoryField.append($('<option>', {
            value: '',
            text: 'Select Subcategory',
            disabled: true,
            selected: true
        }));
        
        // Add subcategory options
        const subcategories = categoriesData.gender[gender].categories[category].subcategories;
        
        $.each(subcategories, function(key, data) {
            $subcategoryField.append($('<option>', {
                value: key,
                text: data.label
            }));
        });
    }
    
    // Handle subcategory selection
    function handleSubcategoryChange() {
        const subcategory = $subcategoryField.val();
        currentSubcategory = subcategory;
        
        // Clear size field
        $sizeField.empty();
        
        if (subcategory && 
            categoriesData.gender[currentGender].categories[currentCategory].subcategories[subcategory].sizes) {
            populateSizeDropdown(currentGender, currentCategory, subcategory);
            $sizeContainer.show();
        } else {
            $sizeContainer.hide();
        }
    }
    
    // Populate size dropdown based on selected gender, category, and subcategory
    function populateSizeDropdown(gender, category, subcategory) {
        // Clear current options
        $sizeField.empty();
        
        // Add default option
        $sizeField.append($('<option>', {
            value: '',
            text: 'Select Size',
            disabled: true,
            selected: true
        }));
        
        // Add size options
        const sizes = categoriesData.gender[gender].categories[category].subcategories[subcategory].sizes;
        
        $.each(sizes, function(index, size) {
            $sizeField.append($('<option>', {
                value: size,
                text: size
            }));
        });
    }
    
    // Set up event listeners
    $genderField.on('change', handleGenderChange);
    $categoryField.on('change', handleCategoryChange);
    $subcategoryField.on('change', handleSubcategoryChange);
    
    // Initialize the form
    fetchCategoriesData();
});
