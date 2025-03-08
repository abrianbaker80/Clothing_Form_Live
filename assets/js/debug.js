/**
 * Debug utility for Preowned Clothing Form
 * This script helps identify missing elements or initialization issues
 */

jQuery(document).ready(function ($) {
    console.log('PCF Debug: Debug script loaded');

    // Function to check for required elements
    function checkElements() {
        const requiredElements = {
            // Form containers
            'form': $('#preowned-clothing-form'),
            'wizard': $('.pcf-wizard-container'),
            'reviewContainer': $('#review-items-container'),

            // Buttons
            'addItemButton': $('#add-another-item'),
            'nextButton': $('.next-step-button'),
            'prevButton': $('.prev-step-button'),
            'submitButton': $('#submit-clothing-form'),

            // Image upload
            'imageUpload': $('.image-upload-container'),

            // Form fields
            'nameField': $('input[name="name"]'),
            'emailField': $('input[name="email"]'),
            'categorySelectors': $('.category-selector'),
            'genderSelectors': $('select[name*="gender"]'),
        };

        console.log('PCF Debug: Checking for required elements');

        let missingElements = [];
        let foundElements = [];

        // Check each required element
        $.each(requiredElements, function (name, element) {
            if (element.length === 0) {
                missingElements.push(name);
            } else {
                foundElements.push(name);
            }
        });

        // Log results
        if (missingElements.length > 0) {
            console.warn('PCF Debug: Missing elements:', missingElements);
        } else {
            console.log('PCF Debug: All required elements found');
        }

        console.log('PCF Debug: Found elements:', foundElements);
    }

    // Run element check after a slight delay to ensure all content is loaded
    setTimeout(checkElements, 500);

    // Check if form options are loaded
    if (typeof pcf_ajax_object !== 'undefined') {
        console.log('PCF Debug: AJAX object available', pcf_ajax_object);
    } else {
        console.error('PCF Debug: AJAX object not available');
    }

    // Check if any forms exist on the page
    const allForms = $('form');
    if (allForms.length > 0) {
        console.log('PCF Debug: Found ' + allForms.length + ' forms on the page');
    } else {
        console.warn('PCF Debug: No forms found on page');
    }
});

// Add window error handler
window.addEventListener('error', function (e) {
    console.error('PCF Debug: JavaScript error:', e.message, 'in', e.filename, 'line', e.lineno);
});
