/**
 * Item Management Module
 * 
 * @module ItemManagement
 * @description Handles adding, removing and managing form items
 */

// Tell TypeScript that jQuery exists as a global variable
/** @type {any} */

// Declare global window properties using JSDoc
/**
 * @typedef {Window & typeof globalThis & {
 *   saveFormData?: function(): void,
 *   handleGenderChange?: function(JQuery<HTMLElement>): void,
 *   initializeCategorySelects?: function(jQuery, number): void,
 *   initializeGenderBasedCategories?: function(): void
 * }} ExtendedWindow
 */

/** @type {ExtendedWindow} */
const windowWithExtensions = window;

jQuery(document).ready(function ($) {
    console.log('Item Management script loaded');

    // Store the max number of items allowed
    const MAX_ITEMS = parseInt($('#add-item-btn').data('max-items')) || 10;

    // Debug check if the button exists
    if ($('#add-item-btn').length === 0) {
        console.error('Add item button not found! Check if the button ID is correct.');
    } else {
        console.log('Add item button found with max-items:', MAX_ITEMS);
    }

    /**
     * Add Item Button Click Handler
     * Uses direct binding instead of delegation to avoid multiple handler attachment
     */
    $('#add-item-btn').on('click', function (e) {
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

            // Clone the first item container without events
            const $firstItem = $('.clothing-item-container').first();

            if ($firstItem.length === 0) {
                console.error('No item container found to clone!');
                return;
            }

            const $newItem = $firstItem.clone(false); // Clone without events

            // Update item index and other attributes
            $newItem.attr('data-item-id', newItemIndex);

            // Update the item number and ordinal
            $newItem.find('.item-number-badge').text(newItemIndex);
            $newItem.find('.item-ordinal').text(getOrdinalWithSuffix(newItemIndex) + ' Item');

            // Show the remove button for this item
            $newItem.find('.remove-item-btn').show();

            // Update all input names and IDs
            $newItem.find('input, select, textarea').each(function () {
                const $input = $(this);
                const name = $input.attr('name');
                const id = $input.attr('id');

                if (name) {
                    $input.attr('name', name.replace(/items\[\d+\]/g, 'items[' + newItemIndex + ']'));
                }

                if (id) {
                    const newId = id.replace(/-\d+-|-\d+$/g, function (match) {
                        return match.replace(/\d+/, String(newItemIndex));
                    });
                    $input.attr('id', newId);
                }
            });

            // Update labels separately
            $newItem.find('label[for]').each(function () {
                const $label = $(this);
                const forAttr = $label.attr('for');
                if (forAttr) {
                    const newFor = forAttr.replace(/-\d+-|-\d+$/g, function (match) {
                        return match.replace(/\d+/, String(newItemIndex));
                    });
                    $label.attr('for', newFor);
                }
            });

            // Update container IDs
            $newItem.find('[id]').each(function () {
                const $el = $(this);
                const id = $el.attr('id');
                if (id && id.match(/-\d+/)) {
                    const newId = id.replace(/-\d+/g, '-' + newItemIndex);
                    $el.attr('id', newId);
                }
            });

            // Reset file inputs completely (cloning doesn't work well with them)
            $newItem.find('input[type="file"]').each(function () {
                const $oldInput = $(this);
                const name = $oldInput.attr('name');
                const id = $oldInput.attr('id');
                const required = $oldInput.prop('required');
                const accept = $oldInput.attr('accept');

                // Create a new input element to replace the old one
                const $newInput = $('<input>').attr({
                    'type': 'file',
                    'name': name,
                    'id': id,
                    'accept': accept
                });

                if (required) {
                    $newInput.prop('required', true);
                }

                $oldInput.replaceWith($newInput);
            });

            // Clear all other inputs
            $newItem.find('input[type="text"], input[type="number"], textarea').val('');
            $newItem.find('select').val('');
            $newItem.find('.image-upload-box').removeClass('has-image');
            $newItem.find('.upload-placeholder').show();

            // Reset image preview areas
            $newItem.find('.image-preview').empty();

            // Append the new item to the items container
            $('#items-container').append($newItem);
            console.log('New item appended with index:', newItemIndex);

            // Initialize event handlers for the new item
            initializeItemHandlers($newItem);
            // Update form storage
            if (typeof windowWithExtensions.saveFormData === 'function') {
                windowWithExtensions.saveFormData();
            } else {
                console.log('Form data auto-save not available');
            }

            // Check if we've reached the max items
            if (newItemIndex >= MAX_ITEMS) {
                $('#add-item-btn').hide();
            }

            // Scroll to the new item
            $('html, body').animate({
                scrollTop: $newItem.offset().top - 50
            }, 500);
        } catch (error) {
            console.error('Error adding new item:', error);
            alert('There was an error adding a new item. Please check the console for details.');
        }
    });

    // Remove item button click handler - Use delegation for dynamic elements
    $(document).on('click', '.remove-item-btn', function () {
        const $itemContainer = $(this).closest('.clothing-item-container');
        const confirmed = confirm('Are you sure you want to remove this item?');

        if (confirmed) {
            $itemContainer.slideUp(300, function () {
                // Remove the item
                $itemContainer.remove();

                // Renumber the remaining items
                renumberItems();

                // Show the add item button if previously hidden
                $('#add-item-btn').show();

                // Update form storage
                // Update form storage
                if (typeof windowWithExtensions.saveFormData === 'function') {
                    windowWithExtensions.saveFormData();
                }
            }); // Close slideUp callback
        }
    });

    // Function to renumber items after removal
    function renumberItems() {
        $('.clothing-item-container').each(function (index) {
            const itemNumber = index + 1;
            const $item = $(this);

            // Update item ID attribute
            $item.attr('data-item-id', itemNumber);

            // Update item number badge and ordinal
            $item.find('.item-number-badge').text(itemNumber);
            $item.find('.item-ordinal').text(getOrdinalWithSuffix(itemNumber) + ' Item');

            // Update input names and IDs
            $item.find('input, select, textarea').each(function () {
                const $input = $(this);
                const name = $input.attr('name');
                const id = $input.attr('id');

                if (name && name.match(/items\[\d+\]/)) {
                    $input.attr('name', name.replace(/items\[\d+\]/g, 'items[' + itemNumber + ']'));
                }

                if (id) {
                    // Use a more robust regex to handle complex IDs
                    const newId = id.replace(/(-|_)\d+(-|_|\b)/g, '$1' + itemNumber + '$2');
                    $input.attr('id', newId);
                }
            });

            // Update labels separately
            $item.find('label[for]').each(function () {
                const $label = $(this);
                const forAttr = $label.attr('for');
                if (forAttr) {
                    const newFor = forAttr.replace(/(-|_)\d+(-|_|\b)/g, '$1' + itemNumber + '$2');
                    $label.attr('for', newFor);
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
        // Initialize gender selection
        const itemId = $item.data('item-id');
        const $genderSelect = $item.find('#gender-' + itemId);

        // Attach change event handlers to gender select
        $genderSelect.on('change', function () {
            // If we have category handling functions available from other scripts
            if (windowWithExtensions.handleGenderChange) {
                windowWithExtensions.handleGenderChange($(this));
            }
        });

        // Initialize category container for this item
        initializeCategoryContainer($item);

        // Initialize file upload handlers
        initializeFileUploadHandlers($item);
    }

    // Function to initialize category container
    function initializeCategoryContainer($item) {
        const itemId = $item.data('item-id');
        const $container = $item.find('.category-select-container');

        // Check for existing category-handler.js functions
        if (typeof windowWithExtensions.initializeCategorySelects === 'function') {
            windowWithExtensions.initializeCategorySelects($container, itemId);
        }

        if (typeof windowWithExtensions.initializeGenderBasedCategories === 'function') {
            windowWithExtensions.initializeGenderBasedCategories();
        }
    }

    // Function to initialize file upload handlers
    function initializeFileUploadHandlers($item) {
        $item.find('input[type="file"]').each(function () {
            const $input = $(this);

            $input.on('change', function (e) {
                // Display preview of selected file
                const file = this.files[0];
                if (!file) return;

                const $box = $(this).closest('.image-upload-box');
                const reader = new FileReader();

                reader.onload = function (e) {
                    $box.addClass('has-image');

                    // Find or create preview element
                    let $preview = $box.find('.image-preview');
                    if ($preview.length === 0) {
                        $preview = $('<div class="image-preview"></div>');
                        $box.append($preview);
                    }

                    // Clear and set the preview image
                    // TypeScript fix: Cast the result to string since we know it's a data URL
                    const imageUrl = String(e.target.result);
                    $preview.empty().append($('<img>').attr('src', imageUrl));
                    $box.find('.upload-placeholder').hide();
                };

                reader.readAsDataURL(file);
            });
        });
    }

    /**
     * Fixed ordinal suffix function
     */
    function getOrdinalWithSuffix(n) {
        const j = n % 10;
        const k = n % 100;

        if (j === 1 && k !== 11) {
            return n + "st";
        }
        if (j === 2 && k !== 12) {
            return n + "nd";
        }
        if (j === 3 && k !== 13) {
            return n + "rd";
        }
        return n + "th";
    }

    // Legacy support for old function name
    function getOrdinal(n) {
        return getOrdinalWithSuffix(n);
    }
});
