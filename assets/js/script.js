/**
 * Preowned Clothing Form JavaScript
 * 
 * This file contains additional JavaScript functionality for the clothing form.
 */

// Define global vars for JS linting - will be ignored by browser
/* global clothing_categories, updateReviewSection */

document.addEventListener('DOMContentLoaded', function () {
    // File input preview functionality
    const fileInput = document.getElementById('images');

    if (fileInput && fileInput instanceof HTMLInputElement) {
        fileInput.addEventListener('change', function () {
            // Validate file size
            const maxSize = 2 * 1024 * 1024; // 2MB
            let valid = true;
            const files = this.files;

            for (let i = 0; i < files.length; i++) {
                if (files[i].size > maxSize) {
                    valid = false;
                    alert('One or more images exceed the maximum file size of 2MB. Please select smaller images.');
                    this.value = '';
                    break;
                }
            }

            // Validate number of files
            if (valid && this.files.length > 3) {
                alert('You can upload a maximum of 3 images. Only the first 3 will be processed.');
            }
        });
    }

    // Form validation on submit
    const form = document.getElementById('clothing-form');

    if (form) {
        form.addEventListener('submit', function (event) {
            const requiredFields = ['name', 'email', 'description'];
            let valid = true;

            requiredFields.forEach(function (field) {
                const element = document.getElementById(field);
                if (element && (element instanceof HTMLInputElement || element instanceof HTMLTextAreaElement) && !element.value.trim()) {
                    valid = false;
                    event.preventDefault();
                    element.style.borderColor = 'red';
                } else if (element) {
                    if (element instanceof HTMLInputElement || element instanceof HTMLTextAreaElement) {
                        element.style.borderColor = '';
                    }
                }
            });

            // Email validation
            const email = document.getElementById('email');
            if (email && email instanceof HTMLInputElement && email.value.trim() && !isValidEmail(email.value.trim())) {
                valid = false;
                event.preventDefault();
                email.style.borderColor = 'red';
            }

            if (!valid) {
                alert('Please fill in all required fields correctly.');
            }
        });
    }

    // UPDATED: Enhanced confirmation handling with more specific parameters
    const urlParams = new URLSearchParams(window.location.search);
    const showConfirmation = urlParams.get('pcf_submission_confirmed');
    const submissionStatus = urlParams.get('pcf_submission_status');

    console.log('Checking for confirmation:', showConfirmation, submissionStatus);

    if (showConfirmation === 'true' && submissionStatus === 'success') {
        console.log('Displaying confirmation modal');
        displayConfirmationModal();
    } else {
        // Check for inline success message as fallback
        const successMessage = document.querySelector('.submission-feedback.success');
        if (successMessage) {
            console.log('Found success message, highlighting');
            highlightSuccessMessage(successMessage);

            // Scroll to message
            successMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
        } else {
            console.log('No success message found');
        }
    }

    function displayConfirmationModal() {
        // Create modal elements
        const overlay = document.createElement('div');
        overlay.className = 'confirmation-modal-overlay';

        const modal = document.createElement('div');
        modal.className = 'confirmation-modal';

        const heading = document.createElement('h2');
        heading.textContent = 'Thank You For Your Submission!';

        const message = document.createElement('p');
        message.innerHTML = 'Your clothing item has been successfully submitted.<br><br>Someone from our team will be reaching out to you within <strong>24-48 hours</strong> to follow up on your submission.';

        const closeButton = document.createElement('button');
        closeButton.className = 'close-button';
        closeButton.textContent = 'Got it!';
        closeButton.addEventListener('click', function () {
            document.body.removeChild(overlay);

            // Update URL to remove query parameter without refreshing
            const url = new URL(window.location.href); // Use the string URL instead of Location object
            url.searchParams.delete('pcf_submission_confirmed');
            url.searchParams.delete('pcf_submission_status');
            url.searchParams.delete('pcf_t');
            window.history.pushState({}, '', url);
        });

        // Assemble and append modal
        modal.appendChild(heading);
        modal.appendChild(message);
        modal.appendChild(closeButton);
        overlay.appendChild(modal);
        document.body.appendChild(overlay);
    }

    function highlightSuccessMessage(element) {
        const paragraph = element.querySelector('p');
        if (paragraph) {
            const messageText = paragraph.innerHTML;
            paragraph.innerHTML = '<strong>Thank You For Your Submission!</strong><br><br>' +
                'Your clothing item has been successfully submitted.<br><br>' +
                'Someone from our team will be reaching out to you within <strong>24-48 hours</strong>.';
        }

        // Add a subtle animation to draw attention
        element.style.animation = 'pulse 2s infinite';

        // Add keyframes for the pulse animation
        if (!document.getElementById('success-animation')) {
            const style = document.createElement('style');
            style.id = 'success-animation';
            style.innerHTML = `
                @keyframes pulse {
                    0% { box-shadow: 0 0 0 0 rgba(10, 107, 49, 0.4); }
                    70% { box-shadow: 0 0 0 10px rgba(10, 107, 49, 0); }
                    100% { box-shadow: 0 0 0 0 rgba(10, 107, 49, 0); }
                }
            `;
            document.head.appendChild(style);
        }
    }

    // Email validation helper function
    function isValidEmail(email) {
        const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
        return re.test(String(email).toLowerCase());
    }
});

/**
 * Main JavaScript functionality for the Preowned Clothing Form
 */

// Function to update the review section with current item data
function updateReviewSection() {
    // Collect data from all clothing items
    const items = $('.clothing-item-container').map(function () {
        const itemId = $(this).data('item-id');
        return {
            id: itemId,
            category: $('#category-select-container-' + itemId + ' select').map(function () {
                return $(this).find('option:selected').text();
            }).get().filter(text => text !== 'Select Gender' && text !== 'Select Category').join(' - '),
            size: $('#size-' + itemId).val(),
            brand: $('#brand-' + itemId).val(),
            condition: $('#condition-' + itemId).val(),
            description: $('#description-' + itemId).val()
        };
    }).get();

    // Update review section with collected data
    const reviewSection = $('#review-section');
    if (reviewSection.length) {
        reviewSection.html('');

        items.forEach(item => {
            reviewSection.append(`
                <div class="review-item">
                    <h4>Item ${item.id}</h4>
                    <p><strong>Category:</strong> ${item.category || 'Not specified'}</p>
                    <p><strong>Size:</strong> ${item.size || 'Not specified'}</p>
                    <p><strong>Brand:</strong> ${item.brand || 'Not specified'}</p>
                    <p><strong>Condition:</strong> ${item.condition || 'Not specified'}</p>
                    <p><strong>Description:</strong> ${item.description || 'Not provided'}</p>
                </div>
            `);
        });
    }
}

jQuery(document).ready(function ($) {
    // Initialize dynamic category selects
    initializeAllCategorySelects();

    // Handle adding new items
    $('#add-item-btn').on('click', function () {
        const MAX_ITEMS = parseInt($(this).data('max-items') || 10);
        let itemCounter = $('.clothing-item-container').length;

        if (itemCounter >= MAX_ITEMS) {
            alert('You can only add up to ' + MAX_ITEMS + ' items in a single submission.');
            return;
        }

        // Increment counter
        itemCounter++;

        // Clone the template
        const template = document.getElementById('item-template');
        const container = document.getElementById('items-container');

        // Check that template exists and has content property (HTMLTemplateElement)
        if (!template || !(template instanceof HTMLTemplateElement)) {
            console.error('Template element not found or not an HTML template element');
            return;
        }

        const newItem = template.content.cloneNode(true);

        // Update IDs and names
        updateElementIds(newItem, itemCounter);

        // Add to container
        if (container) {
            container.appendChild(newItem);

            // Add animation class to the newly added item
            const newItemElement = container.lastElementChild;
            if (newItemElement) {
                newItemElement.classList.add('new-card');

                // Remove the animation class after animation completes
                setTimeout(function () {
                    $(newItemElement).removeClass('new-card');
                }, 500);
            }
        }

        // Initialize category selects for the new item
        initializeCategorySelects(itemCounter);

        // Also add photo upload section to Step 3
        const photoTemplate = document.getElementById('photos-template');

        // Check that photo template exists and has content property
        if (!photoTemplate || !(photoTemplate instanceof HTMLTemplateElement)) {
            console.error('Photo template element not found or not an HTML template element');
            return;
        }

        const photoContent = photoTemplate.content.cloneNode(true);

        // Update IDs and names in the photo section
        updateElementIds(photoContent, itemCounter);

        // Add to step 3
        const photoContainer = $('.wizard-step').eq(2);
        photoContainer.append(photoContent);

        // Show all remove buttons when we have more than one item
        $('.remove-item-btn').css('display', 'flex');

        // Trigger event for new item added - this will be caught by drag-drop-upload.js
        $(document).trigger('itemAdded', [itemCounter]);

        // Update the review section if it exists
        if (typeof updateReviewSection === 'function') {
            updateReviewSection();
        }
    });

    // Remove item functionality (delegated)
    $('#items-container').on('click', '.remove-item-btn', function () {
        const itemContainer = $(this).closest('.clothing-item-container');
        const itemId = itemContainer.data('item-id');

        if (itemContainer && $('.clothing-item-container').length > 1) {
            if (confirm('Are you sure you want to remove this item?')) {
                itemContainer.fadeOut(300, function () {
                    $(this).remove();

                    // Also remove the corresponding photo upload section
                    $(`.wizard-step:nth-child(3) .additional-photos:has(label:contains("Item ${itemId}"))`).remove();

                    // If we're down to 1 item, hide the remove button
                    if ($('.clothing-item-container').length === 1) {
                        $('.remove-item-btn').hide();
                    }

                    // Update the review section if it exists
                    if (typeof updateReviewSection === 'function') {
                        updateReviewSection();
                    }
                });
            }
        }
    });

    // Email validation on form submission
    const form = document.getElementById('clothing-form');
    if (form) {
        form.addEventListener('submit', function (event) {
            const email = document.getElementById('email');
            if (email && email instanceof HTMLInputElement && email.value.trim() && !isValidEmail(email.value.trim())) {
                event.preventDefault();
                email.style.borderColor = 'red';
                alert('Please enter a valid email address.');
            }
        });
    }

    // Email validation helper function (declare it again for this scope)
    function isValidEmail(email) {
        const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
        return re.test(String(email).toLowerCase());
    }

    // Process URL parameters for confirmation display
    const urlParams = new URLSearchParams(window.location.search);
    const submissionStatus = urlParams.get('pcf_submission_status');

    if (submissionStatus === 'success') {
        const successMessage = document.querySelector('.submission-feedback.success');
        if (successMessage) {
            successMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    // Helper function to convert numbers to ordinal text
    function getOrdinalText(num) {
        if (num === 1) return 'First';
        if (num === 2) return 'Second';
        if (num === 3) return 'Third';
        if (num === 4) return 'Fourth';
        if (num === 5) return 'Fifth';
        if (num === 6) return 'Sixth';
        if (num === 7) return 'Seventh';
        if (num === 8) return 'Eighth';
        if (num === 9) return 'Ninth';
        if (num === 10) return 'Tenth';
        return num + 'th'; // Fallback for any other number
    }

    // Initialize category selects for an item
    function initializeCategorySelects(itemId) {
        // Get container and load category data from global variable defined in PHP
        const container = $('#category-select-container-' + itemId);

        // Check if clothing_categories is defined in the global scope
        if (!container.length || typeof window['clothing_categories'] === 'undefined') {
            console.error('Container not found or clothing_categories not defined');
            return; // Exit if container not found or data not available
        }

        const clothingCategories = window['clothing_categories'];

        // Build the initial select
        buildCategorySelect(clothingCategories, container, 0, null, itemId);

        // Wire up change handlers
        container.on('change', 'select', function () {
            const level = parseInt($(this).data('level'));
            const selectedValue = $(this).val();
            const currentData = getCategoryDataAtLevel(level, $(this));

            // Clear subsequent selects
            clearSubsequentSelects(container, level);

            // Get the next level data and build select if available
            if (selectedValue && currentData[selectedValue] && currentData[selectedValue].subcategories) {
                buildCategorySelect(
                    currentData[selectedValue].subcategories,
                    container,
                    level + 1,
                    selectedValue,
                    itemId
                );
            }

            // Update size options
            updateSizeOptions(itemId);
        });
    }

    // Build a single category select dropdown
    function buildCategorySelect(categoryData, container, level, parentValue, itemId) {
        if (!categoryData) return;

        // Create select wrapper and select element
        const wrapper = $('<div class="category-select-wrapper"></div>');
        const select = $('<select></select>')
            .attr('name', `items[${itemId}][category_level_${level}]`)
            .attr('id', `clothing_category_level_${level}_${itemId}`)
            .data('level', level)
            .data('item-id', itemId);

        if (parentValue) {
            select.data('parent-value', parentValue);
        }

        if (level === 0) {
            select.attr('required', 'required');
        }

        // Add default option
        select.append(
            $('<option></option>')
                .val('')
                .text(level === 0 ? 'Select Gender' : 'Select Category')
        );

        // Add category options
        for (const key in categoryData) {
            if (categoryData[key].label) {
                select.append(
                    $('<option></option>')
                        .val(key)
                        .text(categoryData[key].label)
                );
            }
        }

        // Add to container
        wrapper.append(select);
        container.append(wrapper);
    }

    // Clear all selects after the given level
    function clearSubsequentSelects(container, level) {
        container.find('select').each(function () {
            if (parseInt($(this).data('level')) > level) {
                $(this).parent('.category-select-wrapper').remove();
            }
        });
    }

    // Get category data at a specific level
    function getCategoryDataAtLevel(level, select) {
        const clothingCategories = window['clothing_categories'];

        if (level === 0) {
            return clothingCategories;
        }

        // Navigate up the hierarchy
        let currentData = clothingCategories;
        const selects = select.closest('.category-select-container').find('select');

        for (let i = 0; i < level; i++) {
            const levelSelect = selects.filter(`[data-level="${i}"]`);
            const value = levelSelect.val();

            if (value && currentData[value]) {
                currentData = currentData[value].subcategories;
            } else {
                return {};
            }
        }

        return currentData;
    }

    // Update size options based on selected categories
    function updateSizeOptions(itemId) {
        const container = $('#category-select-container-' + itemId);
        const sizeSelect = $('#size-' + itemId);

        // Clear existing size options
        sizeSelect.find('option:not(:first)').remove();

        // Find the most specific selected category that has size data
        let sizeTypes = null;
        const clothingCategories = window['clothing_categories'];

        // Start from the most specific (highest level) category and work backwards
        for (let level = 10; level >= 0; level--) { // Arbitrary high number to cover all possible levels
            const levelSelect = container.find(`select[data-level="${level}"]`);
            if (levelSelect.length === 0) continue;

            const value = levelSelect.val();
            if (!value) continue;

            const parentValue = levelSelect.data('parent-value');
            const currentLevelData = getCategoryDataAtLevel(level, levelSelect);

            if (currentLevelData[value] && currentLevelData[value].size_types) {
                sizeTypes = currentLevelData[value].size_types;
                break;
            }
        }

        // Fallback to gender level if no specific size data found
        if (!sizeTypes) {
            const genderSelect = container.find('select[data-level="0"]');
            if (genderSelect.length && genderSelect.val()) {
                const gender = genderSelect.val();
                if (clothingCategories[gender] && clothingCategories[gender].size_types) {
                    sizeTypes = clothingCategories[gender].size_types;
                }
            }
        }

        // Add size options if we found some
        if (sizeTypes) {
            for (const sizeType in sizeTypes) {
                const sizes = sizeTypes[sizeType];

                sizes.forEach(size => {
                    sizeSelect.append(
                        $('<option></option>')
                            .val(`${sizeType}: ${size}`)
                            .text(`${sizeType}: ${size}`)
                    );
                });
            }
        }
    }

    // Initialize all category selects on page load
    function initializeAllCategorySelects() {
        $('.clothing-item-container').each(function () {
            const itemId = $(this).data('item-id');
            if (itemId) {
                initializeCategorySelects(itemId);
            }
        });
    }

    // Helper function to update element IDs and names in cloned templates
    function updateElementIds(element, itemId) {
        // Find all elements with ID or name attributes
        const nodes = element.querySelectorAll('[id], [name], [for], [data-item-id]');
        nodes.forEach(node => {
            // Update id attribute
            if (node.hasAttribute('id')) {
                const id = node.getAttribute('id');
                node.setAttribute('id', id.replace(/\d+/, itemId));
            }

            // Update name attribute
            if (node.hasAttribute('name')) {
                const name = node.getAttribute('name');
                node.setAttribute('name', name.replace(/items\[\d+\]/g, `items[${itemId}]`));
            }

            // Update for attribute
            if (node.hasAttribute('for')) {
                const forAttr = node.getAttribute('for');
                node.setAttribute('for', forAttr.replace(/\d+/, itemId));
            }

            // Update data-item-id attribute
            if (node.hasAttribute('data-item-id')) {
                node.setAttribute('data-item-id', itemId);
            }
        });

        // Update text content of elements with item number reference
        const itemNumberElements = element.querySelectorAll('.item-number-badge, .item-ordinal');
        itemNumberElements.forEach(node => {
            if (node.classList.contains('item-number-badge')) {
                node.textContent = itemId;
            } else if (node.classList.contains('item-ordinal')) {
                node.textContent = `${getOrdinalText(itemId)} Item`;
            }
        });
    }
});
