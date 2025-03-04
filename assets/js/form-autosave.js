/**
 * Form autosave functionality with proper cleanup
 */
(function($) {
    'use strict';
    
    // Initialize when document is ready
    $(function() {
        // Check if we're on a success page (URL parameter or success message present)
        if (getUrlParameter('success') === '1' || 
            $('.submission-feedback.success').length > 0) {
            // Clear any saved form data
            clearSavedFormData();
            console.log('Form submitted successfully - cleared saved data');
        } else {
            // Initialize autosave functionality
            initAutosave();
        }
        
        // Add "Clear Form" button to the form header
        addClearFormButton();
    });
    
    /**
     * Initialize the autosave functionality
     */
    function initAutosave() {
        // Check for form data in local storage
        const formData = localStorage.getItem('clothingFormData');
        if (formData) {
            try {
                const parsedData = JSON.parse(formData);
                
                // Show autosave notification
                const notification = $('<div class="autosave-notification"><p>You have a previously unsaved form. Would you like to <a href="#" class="restore-form">restore it</a>? <a href="#" class="clear-autosave">Clear saved data</a></p><button class="close-notification">&times;</button></div>');
                
                $('.clothing-submission-form').prepend(notification);
                
                // Restore form data
                notification.find('.restore-form').on('click', function(e) {
                    e.preventDefault();
                    restoreFormData(parsedData);
                    notification.fadeOut(300);
                });
                
                // Clear autosaved data
                notification.find('.clear-autosave').on('click', function(e) {
                    e.preventDefault();
                    clearSavedFormData();
                    clearFormFields();
                    notification.fadeOut(300);
                });
                
                // Close notification
                notification.find('.close-notification').on('click', function() {
                    notification.fadeOut(300);
                });
            } catch (e) {
                console.error('Error parsing saved form data', e);
                clearSavedFormData();
            }
        }
        
        // Set up autosave on form changes
        let autosaveTimer;
        $('.clothing-submission-form input, .clothing-submission-form select, .clothing-submission-form textarea').on('change', function() {
            clearTimeout(autosaveTimer);
            autosaveTimer = setTimeout(function() {
                saveFormData();
            }, 1000);
        });
        
        // Clear saved data when form is submitted
        $('.clothing-submission-form').on('submit', function() {
            clearSavedFormData();
        });
    }
    
    /**
     * Add a clear form button to the form header
     */
    function addClearFormButton() {
        const $formHeader = $('.clothing-submission-form .form-header');
        if ($formHeader.length && !$formHeader.find('.clear-form-btn').length) {
            const $clearBtn = $('<button type="button" class="clear-form-btn">Clear Form</button>');
            $formHeader.append($clearBtn);
            
            $clearBtn.on('click', function(e) {
                e.preventDefault();
                if (confirm('Are you sure you want to clear all form data?')) {
                    clearSavedFormData();
                    clearFormFields();
                }
            });
        }
    }
    
    /**
     * Clear all form fields
     */
    function clearFormFields() {
        console.log('Clearing all form fields');
        
        // Reset the form
        const form = document.getElementById('clothing-form');
        if (form) {
            form.reset();
        }
        
        // Clear all text inputs and textareas
        $('.clothing-submission-form input[type="text"], .clothing-submission-form input[type="email"], .clothing-submission-form textarea').val('');
        
        // Reset all select elements and trigger change event
        $('.clothing-submission-form select').each(function() {
            $(this).val('');
            $(this).trigger('change');
        });
        
        // Remove additional items (keep only the first one)
        $('.clothing-item-container:not(:first)').remove();
        
        // Reset the first item's gender and category selects
        const $firstItem = $('.clothing-item-container').first();
        $firstItem.find('select').val('').trigger('change');
        
        // Clear image previews from all items
        $('.image-upload-box').removeClass('has-image');
        $('.image-preview').remove();
        $('.remove-image, .remove-preview-btn').remove();
        $('.upload-placeholder').show();
        
        // Clear file inputs (need to clone and replace due to security restrictions)
        $('.clothing-submission-form input[type="file"]').each(function() {
            const $input = $(this);
            
            // Create a new clean file input
            const $newInput = $('<input>').attr({
                'type': 'file',
                'name': $input.attr('name'),
                'id': $input.attr('id'),
                'class': $input.attr('class'),
                'accept': $input.attr('accept')
            });
            
            // Replace the old input
            $input.replaceWith($newInput);
        });
        
        // If the category handler has an initialization function, call it
        if (typeof initializeGenderBasedCategories === 'function') {
            setTimeout(initializeGenderBasedCategories, 100);
        }
        
        // Update review section if we're on the last step
        if (window.pcfWizard && typeof window.pcfWizard.updateReviewSection === 'function') {
            setTimeout(window.pcfWizard.updateReviewSection, 200);
        }
        
        console.log('Form data cleared');
    }
    
    /**
     * Save form data to local storage
     */
    function saveFormData() {
        const formData = {};
        
        // Collect form data
        $('.clothing-submission-form').find('input, select, textarea').each(function() {
            const input = $(this);
            if (input.attr('type') !== 'file' && input.attr('name')) {
                formData[input.attr('name')] = input.val();
            }
        });
        
        // Save to local storage
        localStorage.setItem('clothingFormData', JSON.stringify(formData));
    }
    
    /**
     * Clear saved form data from localStorage
     */
    function clearSavedFormData() {
        localStorage.removeItem('clothingFormData');
    }
    
    /**
     * Restore form data from saved state
     */
    function restoreFormData(data) {
        // First clear any existing data
        clearFormFields();
        
        // Populate form fields
        Object.keys(data).forEach(function(key) {
            const input = $('.clothing-submission-form').find('[name="' + key + '"]');
            if (input.length) {
                input.val(data[key]);
                
                // Trigger change for select elements to update dependent fields
                if (input.is('select')) {
                    input.trigger('change');
                }
            }
        });
        
        // If we have gender fields in the data, make sure categories are updated
        if (typeof initializeGenderBasedCategories === 'function') {
            setTimeout(initializeGenderBasedCategories, 100);
        }
    }
    
    /**
     * Get URL parameter by name
     */
    function getUrlParameter(name) {
        name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
        var regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
        var results = regex.exec(location.search);
        return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
    }
    
})(jQuery);
