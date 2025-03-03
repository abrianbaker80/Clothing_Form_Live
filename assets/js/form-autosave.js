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
