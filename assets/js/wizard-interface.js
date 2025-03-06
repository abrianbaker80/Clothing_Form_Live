/**
 * Wizard Interface for Clothing Form
 * Handles multi-step form navigation and validation
 */
(function ($) {
    'use strict';

    // Store the current step
    let currentStep = 0;

    // Dom elements
    let $wizardContainer, $steps, $navBtns, $progressBar, $indicators;

    // Image URL storage
    const reviewImageUrls = [];

    /**
     * Initialize the wizard interface
     */
    function initWizard() {
        console.log("Initializing wizard interface");

        // Cache DOM elements
        $wizardContainer = $('.wizard-container');
        $steps = $wizardContainer.find('.wizard-step');
        $navBtns = $('.wizard-navigation .wizard-btn');
        $progressBar = $('.progress-bar-fill');
        $indicators = $('.step-indicator');

        // Debug elements found
        console.log("Wizard steps found:", $steps.length);

        // Set up event listeners
        $('.wizard-next').on('click', nextStep);
        $('.wizard-prev').on('click', prevStep);

        // Update UI for the initial step
        updateUI();

        // Set up form validation events
        setupValidation();

        // Enable data persistence
        setupDataPersistence();

        // Mobile enhancements
        setupMobileEnhancements();
    }

    /**
     * Move to the next step
     */
    function nextStep() {
        if (!validateCurrentStep()) {
            showValidationErrors();
            return;
        }

        if (currentStep < $steps.length - 1) {
            currentStep++;
            updateUI();
            saveFormData();
        }

        // Scroll to top of form
        scrollToFormTop();
    }

    /**
     * Move to the previous step
     */
    function prevStep() {
        if (currentStep > 0) {
            currentStep--;
            updateUI();
        }

        // Scroll to top of form
        scrollToFormTop();
    }

    /**
     * Scroll to the top of the form
     */
    function scrollToFormTop() {
        const $form = $('#clothing-form');
        $('html, body').animate({
            scrollTop: $form.offset().top - 50
        }, 300);
    }

    /**
     * Update the UI based on the current step
     */
    function updateUI() {
        // Hide all steps, then show current
        $steps.removeClass('active');
        $($steps[currentStep]).addClass('active');

        // Update progress bar
        const progress = ((currentStep + 1) / $steps.length) * 100;
        $progressBar.css('width', progress + '%');

        // Update step indicators
        $indicators.removeClass('active completed');

        // Mark steps as completed or active
        $indicators.each(function (index) {
            if (index < currentStep) {
                $(this).addClass('completed');
            } else if (index === currentStep) {
                $(this).addClass('active');
            }
        });

        // Show/hide navigation buttons
        if (currentStep === 0) {
            $('.wizard-prev').hide();
        } else {
            $('.wizard-prev').show();
        }

        if (currentStep === $steps.length - 1) {
            $('.wizard-next').hide();
            $('.wizard-submit').show();
        } else {
            $('.wizard-next').show();
            $('.wizard-submit').hide();
        }
    }

    /**
     * Validate the current step
     */
    function validateCurrentStep() {
        const $currentStep = $($steps[currentStep]);
        let isValid = true;

        // Check all required fields in this step
        $currentStep.find('[required]').each(function () {
            if ((this instanceof HTMLInputElement ||
                this instanceof HTMLSelectElement ||
                this instanceof HTMLTextAreaElement) &&
                !this.checkValidity()) {
                isValid = false;
                $(this).addClass('error-field');
            } else if ($(this).val() === '') {
                // Fallback validation for elements without checkValidity
                isValid = false;
                $(this).addClass('error-field');
            } else {
                $(this).removeClass('error-field');
            }
        });

        // Additional validation for specific steps
        switch (currentStep) {
            case 0: // Contact info step
                // Validate email format
                const $email = $currentStep.find('input[type="email"]');
                if ($email.val() && !isValidEmail($email.val())) {
                    isValid = false;
                    $email.addClass('error-field');
                }
                break;

            case 1: // Item details step
                // Ensure at least one item has been filled out
                const hasItems = $currentStep.find('.clothing-item-container').length > 0;
                const hasDescription = $currentStep.find('textarea[name^="items"][name$="[description]"]').filter(function () {
                    return String($(this).val()).trim().length > 0;
                }).length > 0;

                if (!hasItems || !hasDescription) {
                    isValid = false;
                    // Show an error message
                    showStepError($currentStep, 'Please add at least one clothing item with a description.');
                }
                break;

            case 2: // Photos step
                // Ensure required photos are uploaded
                const requiredUploads = $currentStep.find('.image-upload-box.required').filter(function () {
                    // Check if this box has a file selected
                    const input = $(this).find('input[type="file"]')[0];
                    return !(input && input instanceof HTMLInputElement && input.files && input.files[0]); // Updated condition
                });

                if (requiredUploads.length > 0) {
                    isValid = false;
                    requiredUploads.addClass('error-field');
                    showStepError($currentStep, 'Please upload all required photos (marked with *).');
                }
                break;
        }

        return isValid;
    }

    /**
     * Show validation errors for the current step
     */
    function showValidationErrors() {
        const $currentStep = $($steps[currentStep]);

        // Show error message at top of step
        showStepError($currentStep, 'Please complete all required fields before proceeding.');

        // Highlight the first error field and scroll to it
        const $firstError = $currentStep.find('.error-field').first();
        if ($firstError.length) {
            $('html, body').animate({
                scrollTop: $firstError.offset().top - 100
            }, 300);

            $firstError.focus();
        }
    }

    /**
     * Show error message in a step
     */
    function showStepError($step, message) {
        // Remove any existing error message
        $step.find('.step-error-message').remove();

        // Add new error message
        const $error = $('<div class="step-error-message">' + message + '</div>');
        $step.prepend($error);

        // Auto-hide after 5 seconds
        setTimeout(function () {
            $error.fadeOut(300, function () {
                $(this).remove();
            });
        }, 5000);
    }

    /**
     * Set up form validation events
     */
    function setupValidation() {
        // Remove error class on input
        $('input, select, textarea').on('input change', function () {
            $(this).removeClass('error-field');
        });

        // Validate form on submission
        $('#clothing-form').on('submit', function (e) {
            // Validate all steps before submitting
            for (let i = 0; i < $steps.length; i++) {
                currentStep = i;
                if (!validateCurrentStep()) {
                    e.preventDefault();
                    updateUI();
                    showValidationErrors();
                    return false;
                }
            }

            // If we get here, the form is valid
            saveFormData();
            return true;
        });
    }

    /**
     * Set up data persistence using localStorage
     */
    function setupDataPersistence() {
        // Try to load previous data
        if (typeof (Storage) !== "undefined") {
            const savedData = localStorage.getItem('clothingFormData');
            if (savedData) {
                try {
                    const data = JSON.parse(savedData);
                    populateFormWithData(data);
                } catch (e) {
                    console.error('Error parsing saved form data', e);
                }
            }
        }

        // Save data on input changes (debounced)
        let saveTimeout;
        $('#clothing-form').on('input', 'input, select, textarea', function () {
            clearTimeout(saveTimeout);
            saveTimeout = setTimeout(saveFormData, 500);
        });
    }

    /**
     * Save form data to localStorage
     */
    function saveFormData() {
        if (typeof (Storage) === "undefined") {
            return;
        }

        const data = {
            contact: {
                name: $('#name').val(),
                email: $('#email').val()
            },
            items: []
        };

        // Collect item data
        $('.clothing-item-container').each(function () {
            const itemId = $(this).data('item-id');
            const itemData = {
                id: itemId,
                description: $(`#description-${itemId}`).val(),
                size: $(`#size-${itemId}`).val(),
                categories: {}
            };

            // Get category selections
            $(this).find('select[name^="items"][name*="category_level"]').each(function () {
                const level = $(this).data('level');
                itemData.categories[`level_${level}`] = $(this).val();
            });

            data.items.push(itemData);
        });

        localStorage.setItem('clothingFormData', JSON.stringify(data));
    }

    /**
     * Populate form with saved data
     */
    function populateFormWithData(data) {
        // Populate contact info
        if (data.contact) {
            $('#name').val(data.contact.name);
            $('#email').val(data.contact.email);
        }

        // Populate items - this is more complex and depends on
        // how your item addition/removal system works
        if (data.items && data.items.length > 0) {
            // Simplified implementation - assumes you have functions to add items
            // In a real implementation, you'd need to ensure categories are loaded first

            // For now, just populate the first item
            if (data.items[0]) {
                const firstItem = data.items[0];
                $('#description-1').val(firstItem.description);
                $('#size-1').val(firstItem.size);
            }

            // To add more items and populate them, you'd need more complex logic
            // based on your item addition system
        }
    }

    /**
     * Set up mobile-specific enhancements
     */
    function setupMobileEnhancements() {
        // Check if we're on a mobile device
        if (window.innerWidth < 768) {
            // Add touch-friendly classes
            $('.clothing-submission-form').addClass('touch-friendly');

            // Enhance dropdowns for touch
            $('.category-select, select').addClass('touch-select');

            // Enhance file inputs
            $('.image-upload-box').addClass('touch-upload');
        }
    }

    /**
     * Validate email format
     */
    function isValidEmail(email) {
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
    }

    /**
     * Safe getter for input values
     * Handles cases where the element may not be an input element
     */
    function getInputValue(element) {
        if (!element) return '';
        if (element instanceof HTMLInputElement ||
            element instanceof HTMLTextAreaElement ||
            element instanceof HTMLSelectElement) {
            return element.value || '';
        }
        return '';
    }

    /**
     * Update the review section in step 3
     */
    function updateReviewSection() {
        // Update contact info section
        const nameEl = document.getElementById('name');
        const emailEl = document.getElementById('email');
        const phoneEl = document.getElementById('phone');
        const addressEl = document.getElementById('address');
        const cityEl = document.getElementById('city');
        const stateEl = document.getElementById('state');
        const zipEl = document.getElementById('zip');

        // Check if elements exist before trying to access them
        const reviewNameEl = document.getElementById('review-name');
        const reviewEmailEl = document.getElementById('review-email');
        const reviewPhoneEl = document.getElementById('review-phone');
        const reviewAddressEl = document.getElementById('review-address');

        // Use safe getter for input values
        if (reviewNameEl) reviewNameEl.textContent = getInputValue(nameEl) || '[Not provided yet]';
        if (reviewEmailEl) reviewEmailEl.textContent = getInputValue(emailEl) || '[Not provided yet]';
        if (reviewPhoneEl) reviewPhoneEl.textContent = getInputValue(phoneEl) || '[Not provided yet]';

        let fullAddress = '';
        const addressValue = getInputValue(addressEl);
        const cityValue = getInputValue(cityEl);
        const stateValue = getInputValue(stateEl);
        const zipValue = getInputValue(zipEl);

        if (addressValue) fullAddress += addressValue;
        if (cityValue) fullAddress += (fullAddress ? ', ' : '') + cityValue;
        if (stateValue) fullAddress += (fullAddress ? ', ' : '') + stateValue;
        if (zipValue) fullAddress += (fullAddress ? ' ' : '') + zipValue;

        if (reviewAddressEl) reviewAddressEl.textContent = fullAddress || '[Not provided yet]';

        // Update items section
        const reviewItemsContainer = document.getElementById('review-items-container');
        if (!reviewItemsContainer) {
            console.error("Review items container not found");
            return;
        }

        reviewItemsContainer.innerHTML = '';

        // Get all item containers
        const itemContainers = document.querySelectorAll('.clothing-item-container');
        itemContainers.forEach(function (container) {
            const itemId = container.getAttribute('data-item-id');
            if (!itemId) return;

            // Get gender - add type checking for HTMLSelectElement
            const genderSelect = document.getElementById('gender-' + itemId);
            const gender = (genderSelect instanceof HTMLSelectElement && genderSelect.selectedIndex > 0) ?
                genderSelect.options[genderSelect.selectedIndex].text : 'Not selected';

            // Get category path - add type checking for select elements
            const categorySelects = container.querySelectorAll('.category-select');
            let categoryPath = '';
            categorySelects.forEach(select => {
                if (select instanceof HTMLSelectElement && select.value) {
                    const selectedOption = select.options[select.selectedIndex].text;
                    categoryPath += (categoryPath ? ' > ' : '') + selectedOption;
                }
            });

            // Get size - add type checking for HTMLSelectElement
            const sizeSelect = document.getElementById('size-' + itemId);
            const size = (sizeSelect instanceof HTMLSelectElement && sizeSelect.value) ?
                sizeSelect.value : 'Not specified';

            // Get description - add type checking for input/textarea elements
            const descriptionEl = document.getElementById('description-' + itemId);
            const description = (descriptionEl instanceof HTMLTextAreaElement || descriptionEl instanceof HTMLInputElement) &&
                descriptionEl.value ? descriptionEl.value : 'Not provided yet';

            // Count photos and create previews
            let photoCount = 0;
            let photoHtml = '<div class="review-photos">';

            // Find all file inputs in this item container
            const photoInputs = container.querySelectorAll('input[type="file"]');
            photoInputs.forEach(input => {
                if (input instanceof HTMLInputElement && input.files && input.files.length > 0) {
                    photoCount++;

                    try {
                        // Add thumbnail preview for the image
                        const file = input.files[0];

                        // Get file type from input name (e.g., items[1][images][front])
                        const nameParts = input.name.split('[');
                        let fileType = 'Image';

                        if (nameParts.length > 2) {
                            // Extract the image type (front, back, etc)
                            const typeMatch = input.name.match(/\[([^\]]+)\]$/);
                            if (typeMatch && typeMatch[1]) {
                                fileType = typeMatch[1].replace(/_/g, ' ');
                                fileType = fileType.charAt(0).toUpperCase() + fileType.slice(1);
                            }
                        }

                        // Create object URL for the image preview
                        const objectUrl = URL.createObjectURL(file);

                        photoHtml += `<div class="review-photo-thumb">
                            <div class="review-photo-label">${fileType}</div>
                            <img src="${objectUrl}" alt="${fileType}" class="review-thumb">
                        </div>`;

                        // Store the URL to revoke later - avoid using window
                        reviewImageUrls.push(objectUrl);
                    } catch (e) {
                        console.error('Error creating image preview:', e);
                    }
                }
            });

            photoHtml += '</div>';

            // Create review item
            const reviewItem = document.createElement('div');
            reviewItem.className = 'review-item-block';
            reviewItem.innerHTML = `
                <h5>Item ${itemId}: ${categoryPath || 'Not selected yet'}</h5>
                <div class="review-item"><strong>Gender:</strong> ${gender}</div>
                <div class="review-item"><strong>Size:</strong> ${size}</div>
                <div class="review-item"><strong>Description:</strong> ${description}</div>
                <div class="review-item"><strong>Photos:</strong> ${photoCount} photos uploaded</div>
                ${photoCount > 0 ? photoHtml : ''}
            `;

            reviewItemsContainer.appendChild(reviewItem);
        });

        // Clean up old blob URLs to prevent memory leaks
        cleanupReviewImageUrls();
    }

    /**
     * Clean up object URLs to prevent memory leaks
     */
    function cleanupReviewImageUrls() {
        // Get all currently displayed image URLs
        const currentUrls = [];
        document.querySelectorAll('.review-thumb').forEach(img => {
            // Add type checking to ensure img is an HTMLImageElement
            if (img instanceof HTMLImageElement) {
                currentUrls.push(img.src);
            }
        });

        // Revoke any URLs that are no longer displayed
        for (let i = reviewImageUrls.length - 1; i >= 0; i--) {
            const url = reviewImageUrls[i];
            // Use indexOf instead of includes for better compatibility with ES5
            if (currentUrls.indexOf(url) === -1) {
                URL.revokeObjectURL(url);
                reviewImageUrls.splice(i, 1);
            }
        }
    }

    // Initialize when document is ready
    $(document).ready(function () {
        console.log("Document ready, checking for wizard elements");
        if ($('.wizard-container').length) {
            console.log("Wizard container found, initializing");
            initWizard();
        } else {
            console.error("Wizard container not found!");
        }
    });

    // Expose functions to global scope properly - fixed to avoid TypeScript error
    window["pcfWizard"] = {
        updateReviewSection: updateReviewSection,
        validateStep: validateCurrentStep
    };

})(jQuery);
