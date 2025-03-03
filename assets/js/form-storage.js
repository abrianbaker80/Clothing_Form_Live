/**
 * Form Storage Handler
 * Manages saving and restoring form data between steps
 */
jQuery(document).ready(function($) {
    console.log('Form Storage script loaded');
    
    const STORAGE_KEY = 'clothingFormData';
    let formDataDirty = false; // Track if data has changed
    
    // Event delegation for form input changes
    $('#clothing-form').on('change', 'input, select, textarea', function() {
        // Mark data as dirty so we know to save it
        formDataDirty = true;
        
        // Debounce the save operation
        clearTimeout(window.saveFormDataTimer);
        window.saveFormDataTimer = setTimeout(saveFormData, 500);
    });
    
    // Populate form data when the page loads
    loadFormData();
    
    // Save form data before leaving the page
    window.addEventListener('beforeunload', function() {
        if (formDataDirty) {
            saveFormData();
        }
    });
    
    // Save form data before moving between wizard steps
    $(document).on('click', '.wizard-next, .wizard-prev', function() {
        // Only save if data is dirty to prevent unnecessary operations
        if (formDataDirty) {
            saveFormData();
        }
    });
    
    // Save form data function - also make it globally accessible
    window.saveFormData = function() {
        // Create object to store all form data
        const formData = {
            contact: {
                name: $('#name').val(),
                email: $('#email').val(),
                phone: $('#phone').val(),
                address: $('#address').val(),
                city: $('#city').val(),
                state: $('#state').val(),
                zip: $('#zip').val()
            },
            items: []
        };
        
        // Collect data for each item
        $('.clothing-item-container').each(function() {
            const $item = $(this);
            const itemId = $item.data('item-id');
            
            // Create an object for this item's data
            const itemData = {
                gender: $('#gender-' + itemId).val(),
                category: $('#item-category-' + itemId).val(),
                subcategory: $('#item-subcategory-' + itemId).val(),
                size: $('#size-' + itemId).val(),
                description: $('#description-' + itemId).val(),
                
                // Note: we don't store image data in localStorage as it would be too large
                // and browsers have localStorage size limits
                
                // Store other item fields if present
                fields: {}
            };
            
            // Store any custom fields (can be extended)
            $item.find('input[type="text"], input[type="number"]').each(function() {
                const $field = $(this);
                const name = $field.attr('name');
                if (name && !name.includes('images')) {
                    // Extract field name from items[1][fieldname] format
                    const matches = name.match(/items\[\d+\]\[([^\]]+)\]/);
                    if (matches && matches[1]) {
                        const fieldName = matches[1];
                        // Don't duplicate fields we already captured explicitly
                        if (!['gender', 'category', 'subcategory', 'size', 'description'].includes(fieldName)) {
                            itemData.fields[fieldName] = $field.val();
                        }
                    }
                }
            });
            
            // Add this item to the items array
            formData.items.push(itemData);
        });
        
        // Save to localStorage
        if (typeof(Storage) !== "undefined") {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(formData));
            console.log('Form data saved:', formData);
            formDataDirty = false;
            
            // Update the review step with this data
            updateReviewStep(formData);
        } else {
            console.error('localStorage not available, form data cannot be saved');
        }
        
        return formData;
    };
    
    // Load saved form data if available
    function loadFormData() {
        if (typeof(Storage) !== "undefined" && localStorage.getItem(STORAGE_KEY)) {
            try {
                const formData = JSON.parse(localStorage.getItem(STORAGE_KEY));
                console.log('Loading saved form data:', formData);
                
                // Populate contact fields
                if (formData.contact) {
                    $('#name').val(formData.contact.name);
                    $('#email').val(formData.contact.email);
                    $('#phone').val(formData.contact.phone);
                    $('#address').val(formData.contact.address);
                    $('#city').val(formData.contact.city);
                    $('#state').val(formData.contact.state);
                    $('#zip').val(formData.contact.zip);
                }
                
                // Populate first item
                if (formData.items && formData.items.length > 0) {
                    populateItem(1, formData.items[0]);
                    
                    // Add and populate additional items
                    for (let i = 1; i < formData.items.length; i++) {
                        // Click the add button to create new item form
                        $('#add-item-btn').trigger('click');
                        
                        // Get the new item index (it may not be i+1 if there were deletions)
                        const newItemIndex = $('.clothing-item-container').length;
                        
                        // Populate the new item
                        populateItem(newItemIndex, formData.items[i]);
                    }
                }
                
                // Update the review step
                updateReviewStep(formData);
                
            } catch (e) {
                console.error('Error loading form data:', e);
            }
        }
    }
    
    // Function to populate a single item with data
    function populateItem(itemIndex, itemData) {
        if (!itemData) return;
        
        // Set gender and trigger change to load categories
        if (itemData.gender) {
            $('#gender-' + itemIndex).val(itemData.gender).trigger('change');
            
            // Wait for category dropdown to be populated
            setTimeout(function() {
                // Set category and trigger change to load subcategories
                if (itemData.category) {
                    $('#item-category-' + itemIndex).val(itemData.category).trigger('change');
                    
                    // Wait for subcategory dropdown to be populated
                    setTimeout(function() {
                        // Set subcategory
                        if (itemData.subcategory) {
                            $('#item-subcategory-' + itemIndex).val(itemData.subcategory).trigger('change');
                        }
                        
                        // Set size
                        if (itemData.size) {
                            $('#size-' + itemIndex).val(itemData.size);
                        }
                    }, 100);
                }
            }, 100);
        }
        
        // Set description
        if (itemData.description) {
            $('#description-' + itemIndex).val(itemData.description);
        }
        
        // Set any custom fields
        if (itemData.fields) {
            for (const [fieldName, fieldValue] of Object.entries(itemData.fields)) {
                $('[name="items[' + itemIndex + '][' + fieldName + ']"]').val(fieldValue);
            }
        }
    }
    
    // Function to update the review step with current form data
    function updateReviewStep(formData) {
        // Update contact information
        $('#review-name').text(formData.contact.name || '');
        $('#review-email').text(formData.contact.email || '');
        $('#review-phone').text(formData.contact.phone || '');
        
        const address = [
            formData.contact.address,
            formData.contact.city,
            formData.contact.state,
            formData.contact.zip
        ].filter(Boolean).join(', ');
        
        $('#review-address').text(address);
        
        // Update items
        const $reviewItemsContainer = $('#review-items-container');
        $reviewItemsContainer.empty();
        
        formData.items.forEach((item, index) => {
            const genderText = item.gender ? $(`#gender-${index+1} option[value="${item.gender}"]`).text() : '';
            const categoryText = item.category ? $(`#item-category-${index+1} option[value="${item.category}"]`).text() : '';
            const subcategoryText = item.subcategory ? $(`#item-subcategory-${index+1} option[value="${item.subcategory}"]`).text() : '';
            
            const $itemDiv = $(`
                <div class="review-item-card">
                    <h4>Item #${index+1}</h4>
                    <div class="review-item-details">
                        <div><strong>Gender:</strong> ${genderText}</div>
                        <div><strong>Category:</strong> ${categoryText}</div>
                        <div><strong>Type:</strong> ${subcategoryText}</div>
                        <div><strong>Size:</strong> ${item.size || ''}</div>
                        <div><strong>Description:</strong> ${item.description || ''}</div>
                    </div>
                </div>
            `);
            
            $reviewItemsContainer.append($itemDiv);
        });
    }
    
    // Make the loadFormData function available globally
    window.loadFormData = loadFormData;
});
