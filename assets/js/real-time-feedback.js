/**
 * Real-Time Feedback for Preowned Clothing Form
 * 
 * Handles:
 * - Inline validation with error messages
 * - Character counting for textareas
 * - Smart autocomplete for categories
 */
(function($) {
    'use strict';

    // Configuration
    const config = {
        minDescriptionLength: 25, // Minimum characters for descriptions
        validationDelay: 500, // Milliseconds to wait before validating (prevents validation during typing)
        maxDescriptionLength: 1000, // Maximum allowed characters
        idealDescriptionLength: 100, // Ideal description length (for guidance)
        emailPattern: /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/
    };
    
    // Track validation timers to prevent too many validations during typing
    const validationTimers = {};
    
    // Keep track of initialized fields to prevent duplicate handlers
    const initializedFields = new Set();
    
    /**
     * Initialize all real-time feedback functionality
     */
    function initRealTimeFeedback() {
        initInlineValidation();
        initCharacterCounting();
    }
    
    /**
     * Initialize inline validation for form fields
     */
    function initInlineValidation() {
        // Add validation for name field
        setupFieldValidation('#name', function(value) {
            if (!value) return { valid: false, message: 'Please enter your name' };
            if (value.length < 2) return { valid: false, message: 'Name is too short' };
            return { valid: true };
        });
        
        // Add validation for email field
        setupFieldValidation('#email', function(value) {
            if (!value) return { valid: false, message: 'Please enter your email address' };
            if (!config.emailPattern.test(value)) return { valid: false, message: 'Please enter a valid email address' };
            return { valid: true };
        });
        
        // Initial validation for visible description fields
        $('textarea[id^="description-"]').each(function() {
            const id = $(this).attr('id');
            setupFieldValidation('#' + id, validateDescription);
        });
        
        // Add validation when selecting categories
        if (!initializedFields.has('category-validation')) {
            $(document).on('change', 'select[id^="clothing_category_level_0_"]', function() {
                const itemId = $(this).data('item-id');
                const value = $(this).val();
                
                if (!value) {
                    showValidationMessage($(this), 'Please select a category', false);
                } else {
                    hideValidationMessage($(this));
                }
            });
            
            initializedFields.add('category-validation');
        }
        
        // Setup validation for dynamically added description fields (only once)
        if (!initializedFields.has('dynamic-description-validation')) {
            $(document).on('itemAdded', function(e, itemId) {
                setTimeout(function() {
                    setupFieldValidation('#description-' + itemId, validateDescription);
                }, 100);
            });
            
            initializedFields.add('dynamic-description-validation');
        }
    }
    
    /**
     * Setup validation for a specific field
     */
    function setupFieldValidation(selector, validationFunction) {
        const $field = $(selector);
        if (!$field.length) return;
        
        // Skip if already initialized to prevent multiple handlers
        if ($field.data('validation-initialized')) {
            return;
        }
        
        // Mark as initialized
        $field.data('validation-initialized', true);
        
        // Clear any previous event handlers
        $field.off('input.validation change.validation blur.validation');
        
        // Add validation on input with debounce
        $field.on('input.validation', function() {
            const field = this;
            const fieldId = $field.attr('id');
            
            // Clear previous timer
            if (validationTimers[fieldId]) {
                clearTimeout(validationTimers[fieldId]);
            }
            
            // Set new timer
            validationTimers[fieldId] = setTimeout(function() {
                const result = validationFunction(field.value);
                
                if (!result.valid) {
                    showValidationMessage($field, result.message, result.warning);
                } else {
                    hideValidationMessage($field);
                    
                    // Add success indicator if specified
                    if (result.success) {
                        showSuccessIndicator($field);
                    }
                }
            }, config.validationDelay);
        });
        
        // Immediate validation on blur
        $field.on('blur.validation', function() {
            const result = validationFunction(this.value);
            
            if (!result.valid) {
                showValidationMessage($field, result.message, result.warning);
            } else {
                hideValidationMessage($field);
                
                // Add success indicator if specified
                if (result.success) {
                    showSuccessIndicator($field);
                }
            }
        });
        
        // Also validate on change for selects
        if ($field.is('select')) {
            $field.on('change.validation', function() {
                const result = validationFunction(this.value);
                
                if (!result.valid) {
                    showValidationMessage($field, result.message, result.warning);
                } else {
                    hideValidationMessage($field);
                }
            });
        }
        
        // Initial validation if field has a value
        if ($field.val()) {
            const result = validationFunction($field.val());
            
            if (!result.valid && !result.warning) {
                showValidationMessage($field, result.message, result.warning);
            }
        }
    }
    
    /**
     * Description field validation
     */
    function validateDescription(value) {
        if (!value) {
            return { valid: false, message: 'Please describe the item' };
        }
        
        if (value.length < config.minDescriptionLength) {
            return { 
                valid: false, 
                message: `Please provide more details (minimum ${config.minDescriptionLength} characters)`,
                warning: true 
            };
        }
        
        if (value.length > config.maxDescriptionLength) {
            return { 
                valid: false, 
                message: `Description is too long (maximum ${config.maxDescriptionLength} characters)` 
            };
        }
        
        // Check if the description is too generic
        if ((/^(good|nice|great|item|clothing)\s+.{0,10}$/i).test(value)) {
            return {
                valid: true,
                message: 'Your description is very brief. Consider adding details about condition, color, and material.',
                warning: true
            };
        }
        
        // Check for no condition mentioned
        if (!(/condition|state|quality|excellent|good|fair|poor|worn|used|new|like new|mint/i).test(value)) {
            return {
                valid: true,
                message: 'Tip: Include details about the item\'s condition',
                warning: true
            };
        }
        
        return { 
            valid: true,
            success: value.length >= config.idealDescriptionLength
        };
    }
    
    /**
     * Initialize character counting for textareas
     */
    function initCharacterCounting() {
        // Add character counting to all description textareas
        addCharacterCounter('textarea[id^="description-"]');
        
        // For dynamically added textareas - ensure we only add the event handler once
        if (!initializedFields.has('dynamic-character-counting')) {
            $(document).on('itemAdded', function(e, itemId) {
                setTimeout(function() {
                    addCharacterCounter(`#description-${itemId}`);
                }, 100);
            });
            
            initializedFields.add('dynamic-character-counting');
        }
    }
    
    /**
     * Add a character counter to a field
     */
    function addCharacterCounter(selector) {
        const $fields = $(selector);
        
        $fields.each(function() {
            const $field = $(this);
            const fieldId = $field.attr('id');
            
            // Skip if already has counter
            if ($field.data('character-counter-initialized')) {
                return;
            }
            
            // Mark as initialized
            $field.data('character-counter-initialized', true);
            
            // Create counter element
            const $counter = $('<div class="character-counter"><span class="current-count">0</span> / <span class="min-count">' + 
                               config.minDescriptionLength + '</span> chars</div>');
            
            // Insert after textarea
            $field.after($counter);
            
            // Update counter on input
            $field.on('input.charcount', function() {
                const length = $field.val().length;
                $counter.find('.current-count').text(length);
                
                // Update counter styling based on length
                if (length < config.minDescriptionLength) {
                    $counter.removeClass('count-warning count-success').addClass('count-error');
                } else if (length > config.maxDescriptionLength) {
                    $counter.removeClass('count-error count-success').addClass('count-warning');
                } else if (length >= config.idealDescriptionLength) {
                    $counter.removeClass('count-error count-warning').addClass('count-success');
                } else {
                    $counter.removeClass('count-error count-warning count-success');
                }
                
                // Also show when approaching the max
                if (length > config.maxDescriptionLength * 0.9) {
                    $counter.addClass('count-approaching-max');
                } else {
                    $counter.removeClass('count-approaching-max');
                }
            });
            
            // Initial update
            $field.trigger('input.charcount');
        });
    }
    
    /**
     * Show validation error message
     */
    function showValidationMessage($field, message, isWarning = false) {
        const messageClass = isWarning ? 'validation-warning' : 'validation-error';
        let $message = $field.next('.validation-message');
        
        // Create message element if it doesn't exist
        if (!$message.length) {
            $message = $('<div class="validation-message"></div>');
            $field.after($message);
        }
        
        // Update message
        $message.text(message)
                .removeClass('validation-error validation-warning')
                .addClass(messageClass)
                .slideDown(200);
        
        // Add error class to field
        if (!isWarning) {
            $field.addClass('error-field')
                  .removeClass('success-field');
        } else {
            // For warnings, don't add error class
            $field.removeClass('error-field success-field');
        }
    }
    
    /**
     * Hide validation message
     */
    function hideValidationMessage($field) {
        const $message = $field.next('.validation-message');
        
        if ($message.length) {
            $message.slideUp(200);
        }
        
        $field.removeClass('error-field');
    }
    
    /**
     * Show success indicator
     */
    function showSuccessIndicator($field) {
        // Add success class
        $field.removeClass('error-field')
              .addClass('success-field');
              
        // If a success message is needed, it would go here
    }
    
    // Initialize on document ready - only once
    $(function() {
        initRealTimeFeedback();
        
        // Re-initialize when form is reset or page is dynamically updated
        $(document).on('reset', 'form', function() {
            setTimeout(initRealTimeFeedback, 100);
        });
    });

})(jQuery);
