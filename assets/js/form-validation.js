/**
 * Form Validation for Clothing Form
 * Handles form field validation and feedback
 */
(function($) {
    'use strict';
    
    // Initialize form validation
    function initFormValidation() {
        // Set up validation for required fields
        setupRequiredFieldValidation();
        
        // Set up description quality meter
        setupDescriptionQualityMeter();
        
        // Handle form submission
        $('#clothing-form').on('submit', function(e) {
            if (!validateForm()) {
                e.preventDefault();
                showFormErrors();
            }
        });
    }
    
    // Validate the entire form
    function validateForm() {
        let isValid = true;
        
        // Check required fields
        $('#clothing-form [required]').each(function() {
            if (!$(this).val().trim()) {
                $(this).addClass('error-field');
                isValid = false;
            } else {
                $(this).removeClass('error-field');
            }
        });
        
        // Validate email format
        const emailField = $('#clothing-form input[type="email"]');
        if (emailField.length && emailField.val() && !isValidEmail(emailField.val())) {
            emailField.addClass('error-field');
            isValid = false;
        }
        
        // Check if at least one item has required fields filled
        const hasItemWithRequiredFields = $('.clothing-item-container').length > 0 && 
            $('.clothing-item-container').filter(function() {
                return $(this).find('select[name^="items"][name*="category_level_0"]').val() !== '' &&
                       $(this).find('select[name^="items"][name*="size"]').val() !== '' &&
                       $(this).find('textarea[name^="items"][name*="description"]').val().trim() !== '';
            }).length > 0;
        
        if (!hasItemWithRequiredFields) {
            isValid = false;
            // Highlight item fields
            $('.clothing-item-container').each(function() {
                const $container = $(this);
                const categorySelect = $container.find('select[name^="items"][name*="category_level_0"]');
                const sizeSelect = $container.find('select[name^="items"][name*="size"]');
                const description = $container.find('textarea[name^="items"][name*="description"]');
                
                if (categorySelect.val() === '') {
                    categorySelect.addClass('error-field');
                }
                if (sizeSelect.val() === '') {
                    sizeSelect.addClass('error-field');
                }
                if (description.val().trim() === '') {
                    description.addClass('error-field');
                }
            });
        }
        
        return isValid;
    }
    
    // Show form errors and scroll to first error
    function showFormErrors() {
        const $firstError = $('.error-field').first();
        
        if ($firstError.length) {
            // Scroll to first error
            $('html, body').animate({
                scrollTop: $firstError.offset().top - 100
            }, 300);
            
            // Show error message above field
            const $container = $firstError.closest('.form-group');
            if (!$container.find('.field-error-message').length) {
                $container.prepend('<div class="field-error-message">This field is required</div>');
            }
            
            // Focus the field
            $firstError.focus();
        }
    }
    
    // Set up validation for required fields
    function setupRequiredFieldValidation() {
        // Clear error state on input
        $('#clothing-form').on('input change', '[required]', function() {
            $(this).removeClass('error-field');
            $(this).closest('.form-group').find('.field-error-message').remove();
        });
    }
    
    // Set up description quality meter
    function setupDescriptionQualityMeter() {
        // Listen for input in description fields
        $('#clothing-form').on('input', 'textarea[name^="items"][name*="description"]', function() {
            const $textarea = $(this);
            const text = $textarea.val().trim();
            const minLength = $textarea.data('min-length') || 25;
            let qualityClass = '';
            let width = '0%';
            
            // Calculate quality based on length
            if (text.length < minLength) {
                qualityClass = 'poor';
                width = `${Math.max(25, (text.length / minLength) * 100)}%`;
            } else if (text.length < minLength * 2) {
                qualityClass = 'fair';
                width = '50%';
            } else if (text.length < minLength * 3) {
                qualityClass = 'good';
                width = '75%';
            } else {
                qualityClass = 'excellent';
                width = '100%';
            }
            
            // Update meter
            const $meter = $textarea.closest('.form-group').find('.quality-fill');
            $meter.removeClass('poor fair good excellent').addClass(qualityClass);
            $meter.css('width', width);
        });
    }
    
    // Validate email format
    function isValidEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
    
    // Initialize on document ready
    $(document).ready(function() {
        initFormValidation();
    });
    
})(jQuery);
