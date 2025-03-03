/**
 * Item Management for Clothing Form
 * Handles adding and removing items
 */
jQuery(document).ready(function($) {
    console.log('Item Management script loaded');
    
    // Store the max number of items allowed
    const MAX_ITEMS = parseInt($('#add-item-btn').data('max-items')) || 10;
    
    // Add another item button click handler
    $('#add-item-btn').on('click', function() {
        const currentItemCount = $('.clothing-item-container').length;
        
        // Check if we've reached the maximum number of items
        if (currentItemCount >= MAX_ITEMS) {
            alert('You cannot add more than ' + MAX_ITEMS + ' items.');
            return;
        }
        
        // Get the new item index
        const newItemIndex = currentItemCount + 1;
        
        // Clone the first item container and update IDs
        const $newItem = $('.clothing-item-container').first().clone(true);
        
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
        });
        
        // Update container IDs
        $newItem.find('[id]').each(function() {
            const $el = $(this);
            const id = $el.attr('id');
            if (id && id.includes('-1')) {
                $el.attr('id', id.replace(/-1/g, '-' + newItemIndex));
            }
        });
        
        // Clear all inputs
        $newItem.find('input[type="text"], input[type="number"], textarea').val('');
        $newItem.find('select').val('');
        $newItem.find('.image-upload-box').removeClass('has-image');
        $newItem.find('.upload-placeholder').show();
        
        // Reset image upload preview areas
        $newItem.find('.image-preview').empty();
        
        // Append the new item to the items container
        $('#items-container').append($newItem);
        
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
        // Initialize any special event handlers for the new item
        // For example, category handling, image uploads, etc.
        
        // Initialize gender selection
        const itemId = $item.data('item-id');
        const $genderSelect = $item.find('#gender-' + itemId);
        
        if ($genderSelect.length) {
            // If we have a resetSizeSelector function available (from category-handler.js)
            if (typeof resetSizeSelector === 'function') {
                const $sizeSelect = $('#size-' + itemId);
                resetSizeSelector($sizeSelect, null);
            }
            
            // Manually trigger the category-handler logic
            if (typeof initializeGenderBasedCategories === 'function') {
                initializeGenderBasedCategories(); // Re-initialize for the new selects
            }
        }
    }
    
    // Helper function to get ordinal suffix (1st, 2nd, 3rd, etc.)
    function getOrdinal(n) {
        const suffixes = ['th', 'st', 'nd', 'rd'];
        const v = n % 100;
        return n + (suffixes[(v - 20) % 10] || suffixes[v] || suffixes[0]);
    }
});
