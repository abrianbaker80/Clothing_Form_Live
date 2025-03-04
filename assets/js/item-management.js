/**
 * Item Management for Clothing Form
 * Handles adding and removing items
 */
jQuery(document).ready(function($) {
    console.log('Item Management script loaded');
    
    // Store the max number of items allowed
    const MAX_ITEMS = parseInt($('#add-item-btn').data('max-items')) || 10;
    
    // Debug check if the button exists
    if ($('#add-item-btn').length === 0) {
        console.error('Add item button not found! Check if the button ID is correct.');
    } else {
        console.log('Add item button found with max-items:', MAX_ITEMS);
    }
    
    // Add another item button click handler - use document delegation for reliability
    $(document).on('click', '#add-item-btn', function(e) {
        e.preventDefault();
        console.log('Add item button clicked');
        
        const currentItemCount = $('.clothing-item-container').length;
        console.log('Current item count:', currentItemCount);
        
        // Check if we've reached the maximum number of items
        if (currentItemCount >= MAX_ITEMS) {
            alert('You cannot add more than ' + MAX_ITEMS + ' items.');
            return;
        }
        
        try {
            // Get the new item index
            const newItemIndex = currentItemCount + 1;
            
            // Clone the first item container and update IDs
            const $firstItem = $('.clothing-item-container').first();
            
            if ($firstItem.length === 0) {
                console.error('No item container found to clone!');
                return;
            }
            
            const $newItem = $firstItem.clone(false); // Changed to false to not clone event handlers
            
            // Update item index and other attributes
            $newItem.attr('data-item-id', newItemIndex);
            
            // Update the item number and ordinal
            $newItem.find('.item-number-badge').text(newItemIndex);
            $newItem.find('.item-ordinal').text(getOrdinal(newItemIndex) + ' Item');
            
            // Show the remove button for this item
            $newItem.find('.remove-item-btn').show();
            
            // Update all input names and IDs
            $newItem.find('input, select, textarea').each(function() {
                const $input = $(this);
                const name = $input.attr('name');
                const id = $input.attr('id');
                
                if (name) {
                    $input.attr('name', name.replace(/items\[1\]/g, 'items[' + newItemIndex + ']'));
                }
                
                if (id) {
                    const newId = id.replace(/-1-|-1$/g, function(match) {
                        return match.replace('1', newItemIndex);
                    });
                    $input.attr('id', newId);
                    
                    // Also update any labels pointing to this input
                    $newItem.find('label[for="' + id + '"]').attr('for', newId);
                }
                
                // Clear values completely
                if ($input.is('select')) {
                    $input.val('');
                } else if ($input.attr('type') === 'file') {
                    // For file inputs, replace with a new empty one to clear
                    const $newFileInput = $('<input>').attr({
                        'type': 'file',
                        'name': $input.attr('name'),
                        'id': $input.attr('id'),
                        'class': $input.attr('class'),
                        'accept': $input.attr('accept')
                    });
                    $input.replaceWith($newFileInput);
                } else {
                    $input.val('');
                }
            });
            
            // Update container IDs
            $newItem.find('[id]').each(function() {
                const $el = $(this);
                const id = $el.attr('id');
                if (id && id.includes('-1')) {
                    $el.attr('id', id.replace(/-1/g, '-' + newItemIndex));
                }
            });
            
            // Remove any image previews and reset upload boxes
            $newItem.find('.image-preview').remove();
            $newItem.find('.remove-image, .remove-preview-btn').remove();
            $newItem.find('.upload-error').remove();
            $newItem.find('.image-upload-box').removeClass('has-image');
            $newItem.find('.upload-placeholder').show();
            
            // Append the new item to the items container
            $('#items-container').append($newItem);
            console.log('New item appended with index:', newItemIndex);
            
            // Initialize event handlers for the new item
            initializeItemHandlers($newItem);
            
            // Update form storage
            if (typeof saveFormData === 'function') {
                saveFormData();
            }
            
            // Check if we've reached the max items
            if (newItemIndex >= MAX_ITEMS) {
                $('#add-item-btn').hide();
            }
            
            // Scroll to the new item
            $('html, body').animate({
                scrollTop: $newItem.offset().top - 50
            }, 500);
            
            // Trigger custom event for other scripts to respond to the new item
            $(document).trigger('itemAdded', [newItemIndex]);
        } catch (error) {
            console.error('Error adding new item:', error);
            alert('There was an error adding a new item. Please check the console for details.');
        }
    });
    
    // Remove item button click handler
    $(document).on('click', '.remove-item-btn', function() {
        const $itemContainer = $(this).closest('.clothing-item-container');
        const confirmed = confirm('Are you sure you want to remove this item?');
        
        if (confirmed) {
            $itemContainer.slideUp(300, function() {
                // Remove the item
                $itemContainer.remove();
                
                // Renumber the remaining items
                renumberItems();
                
                // Show the add item button if previously hidden
                $('#add-item-btn').show();
                
                // Update form storage
                if (typeof saveFormData === 'function') {
                    saveFormData();
                }
            });
        }
    });
    
    // Function to renumber items after removal
    function renumberItems() {
        $('.clothing-item-container').each(function(index) {
            const itemNumber = index + 1;
            const $item = $(this);
            
            // Update item ID attribute
            $item.attr('data-item-id', itemNumber);
            
            // Update item number badge and ordinal
            $item.find('.item-number-badge').text(itemNumber);
            $item.find('.item-ordinal').text(getOrdinal(itemNumber) + ' Item');
            
            // Update input names and IDs
            $item.find('input, select, textarea').each(function() {
                const $input = $(this);
                const name = $input.attr('name');
                const id = $input.attr('id');
                
                if (name && name.match(/items\[\d+\]/)) {
                    $input.attr('name', name.replace(/items\[\d+\]/g, 'items[' + itemNumber + ']'));
                }
                
                if (id) {
                    // Extract the original base ID before the number
                    const baseParts = id.split(/-\d+/);
                    if (baseParts.length > 1) {
                        // Rebuild the ID with the new index
                        let newId = baseParts[0] + '-' + itemNumber;
                        if (baseParts.length > 2) {
                            newId += baseParts.slice(1).join('');
                        }
                        $input.attr('id', newId);
                        
                        // Also update any labels pointing to this input
                        $item.find('label[for="' + id + '"]').attr('for', newId);
                    }
                }
            });
            
            // Update container IDs
            $item.find('[id]').each(function() {
                const $el = $(this);
                const id = $el.attr('id');
                if (id && id.match(/-\d+/)) {
                    const newId = id.replace(/-\d+/, '-' + itemNumber);
                    $el.attr('id', newId);
                }
            });
            
            // Hide remove button for first item
            if (itemNumber === 1) {
                $item.find('.remove-item-btn').hide();
            } else {
                $item.find('.remove-item-btn').show();
            }
        });
    }
    
    // Function to initialize event handlers for a new item
    function initializeItemHandlers($item) {
        const itemId = $item.data('item-id');
        console.log('Initializing handlers for item:', itemId);
        
        // Initialize category selects for the new item
        if (typeof initializeGenderBasedCategories === 'function') {
            initializeGenderBasedCategories();
        }
        
        // Initialize image uploads for this item
        if (window.pcfImageUpload && typeof window.pcfImageUpload.initializeImageUploads === 'function') {
            setTimeout(function() {
                window.pcfImageUpload.initializeImageUploads(itemId);
            }, 100);
        }
        
        // Initialize validation for description field
        const $descriptionField = $item.find('textarea[id^="description-"]');
        if ($descriptionField.length) {
            // Remove any previous event handlers to prevent duplicates
            $descriptionField.off();
            
            // Add event handlers for validation and character counting
            const fieldId = $descriptionField.attr('id');
            if (typeof setupFieldValidation === 'function') {
                setupFieldValidation('#' + fieldId, validateDescription);
            }
            
            if (typeof addCharacterCounter === 'function') {
                addCharacterCounter('#' + fieldId);
            }
        }
        
        // Make sure gender selector has no initial selection
        const $genderSelect = $item.find('select[id^="gender-"]');
        if ($genderSelect.length) {
            $genderSelect.val('').trigger('change');
        }
        
        // Trigger a custom event that other scripts can listen for
        $(document).trigger('itemInitialized', [itemId, $item]);
    }
    
    // Helper function to get ordinal suffix (1st, 2nd, 3rd, etc.)
    function getOrdinal(n) {
        const suffixes = ['th', 'st', 'nd', 'rd'];
        const v = n % 100;
        return n + (suffixes[(v - 20) % 10] || suffixes[v] || suffixes[0]);
    }
});
