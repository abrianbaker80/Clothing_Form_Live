/**
 * Form Experience Enhancements
 * 
 * Improves the user experience of the clothing submission form.
 */

(function($) {
    'use strict';
    
    // Document ready
    $(function() {
        if ($('.clothing-submission-form').length === 0) {
            return; // Exit if form not found
        }
        
        // Form autosave functionality
        initFormAutosave();
        
        // Enhanced client-side validation
        initEnhancedValidation();
        
        // Drag and drop file upload
        initDragDropUpload();
        
        // Form progress indicator
        initFormProgressIndicator();
        
        // Keyboard accessibility improvements
        initKeyboardAccessibility();
    });
    
    /**
     * Initialize form autosave
     */
    function initFormAutosave() {
        const AUTOSAVE_INTERVAL = 30000; // 30 seconds
        const formData = {};
        const $form = $('#clothing-form');
        const autosaveKey = 'clothing_form_autosave';
        
        // Load any existing autosaved data
        try {
            const savedData = localStorage.getItem(autosaveKey);
            if (savedData) {
                const parsedData = JSON.parse(savedData);
                
                // Check if data is still valid (less than 24 hours old)
                const now = new Date();
                const savedTime = new Date(parsedData.timestamp);
                const hoursDiff = Math.abs(now - savedTime) / 36e5; // hours difference
                
                if (hoursDiff < 24) {
                    restoreFormData(parsedData.data);
                    
                    // Show restore notification
                    showAutosaveNotification();
                } else {
                    // Clear expired data
                    localStorage.removeItem(autosaveKey);
                }
            }
        } catch(e) {
            console.error('Error loading autosaved form data:', e);
            localStorage.removeItem(autosaveKey);
        }
        
        // Set up autosave timer
        let autosaveTimer;
        
        function resetAutosaveTimer() {
            clearTimeout(autosaveTimer);
            autosaveTimer = setTimeout(saveFormData, AUTOSAVE_INTERVAL);
        }
        
        // Save form data when user makes changes
        $form.on('change', 'input, select, textarea', function() {
            resetAutosaveTimer();
        });
        
        $form.on('keyup', 'input[type="text"], input[type="email"], textarea', function() {
            resetAutosaveTimer();
        });
        
        // Initial timer start
        resetAutosaveTimer();
        
        // Save form data
        function saveFormData() {
            const $inputs = $form.find('input[type="text"], input[type="email"], textarea, select');
            const data = {};
            
            $inputs.each(function() {
                const $input = $(this);
                const name = $input.attr('name');
                
                // Only save fields with names
                if (name) {
                    data[name] = $input.val();
                }
            });
            
            // Save data to localStorage with timestamp
            try {
                localStorage.setItem(autosaveKey, JSON.stringify({
                    timestamp: new Date(),
                    data: data
                }));
            } catch(e) {
                console.error('Error saving form data:', e);
            }
            
            // Reset timer
            resetAutosaveTimer();
        }
        
        // Restore form data from autosave
        function restoreFormData(data) {
            Object.keys(data).forEach(key => {
                const $field = $form.find(`[name="${key}"]`);
                if ($field.length) {
                    $field.val(data[key]);
                }
            });
        }
        
        // Show autosave notification
        function showAutosaveNotification() {
            const $notification = $('<div class="autosave-notification">' +
                '<p>We\'ve restored your previously entered information. <button type="button" class="clear-autosave">Start Fresh</button></p>' +
                '<button type="button" class="close-notification">×</button>' +
                '</div>');
                
            $notification.prependTo($form);
            
            // Handle close button
            $notification.on('click', '.close-notification', function() {
                $notification.slideUp(200, function() {
                    $notification.remove();
                });
            });
            
            // Handle clear autosave button
            $notification.on('click', '.clear-autosave', function() {
                localStorage.removeItem(autosaveKey);
                window.location.reload();
            });
        }
    }
    
    /**
     * Enhanced client-side validation
     */
    function initEnhancedValidation() {
        const $form = $('#clothing-form');
        
        // Add aria-required to required fields
        $form.find(':required').attr('aria-required', 'true');
        
        // Add validation styles
        const validationStyles = `
            <style>
                .clothing-submission-form input:invalid:not(:placeholder-shown),
                .clothing-submission-form textarea:invalid:not(:placeholder-shown),
                .clothing-submission-form select:invalid:not(:placeholder-shown) {
                    border-color: #cc0000;
                    background-color: #fff8f8;
                }
                .validation-message {
                    color: #cc0000;
                    font-size: 12px;
                    margin-top: 3px;
                    display: none;
                }
                .field-validated .validation-message {
                    display: block;
                }
            </style>
        `;
        $(validationStyles).appendTo('head');
        
        // Add validation messages under required fields
        $form.find(':required').each(function() {
            const $field = $(this);
            const $wrapper = $field.closest('.form-group');
            const fieldType = this.tagName.toLowerCase();
            let message = 'This field is required.';
            
            if (fieldType === 'input') {
                const inputType = $field.attr('type');
                if (inputType === 'email') {
                    message = 'Please enter a valid email address.';
                    $field.attr('pattern', '[a-z0-9._%+-]+@[a-z0-9.-]+\\.[a-z]{2,}$');
                }
            } else if (fieldType === 'textarea') {
                message = 'Please provide a detailed description.';
                $field.attr('minlength', '10');
            }
            
            $('<div class="validation-message">' + message + '</div>').appendTo($wrapper);
        });
        
        // Real-time validation feedback
        $form.on('blur', ':required', function() {
            const $field = $(this);
            const $wrapper = $field.closest('.form-group');
            
            if ($field.is(':valid')) {
                $wrapper.removeClass('field-validated');
            } else {
                $wrapper.addClass('field-validated');
            }
        });
        
        // Validate entire form before submission
        $form.on('submit', function(e) {
            const $requiredFields = $form.find(':required');
            let isValid = true;
            
            $requiredFields.each(function() {
                const $field = $(this);
                const $wrapper = $field.closest('.form-group');
                
                if (!this.validity.valid) {
                    isValid = false;
                    $wrapper.addClass('field-validated');
                    
                    // Scroll to first invalid field
                    if (!isValid && this === $form.find(':invalid').first()[0]) {
                        $('html, body').animate({
                            scrollTop: $wrapper.offset().top - 100
                        }, 500);
                    }
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Please correct the highlighted fields before submitting.');
            }
        });
    }
    
    /**
     * Initialize drag and drop file upload
     */
    function initDragDropUpload() {
        $('.image-upload-box').each(function() {
            const $box = $(this);
            
            // Add drag-drop classes and aria attributes
            $box.attr({
                'aria-label': 'Image upload area. Click or drag files here.',
                'tabindex': '0'
            });
            
            // Handle drag events
            $box.on('dragover dragenter', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $box.addClass('drag-over');
            });
            
            $box.on('dragleave dragend drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $box.removeClass('drag-over');
            });
            
            // Handle drop
            $box.on('drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                if (e.originalEvent.dataTransfer && e.originalEvent.dataTransfer.files.length) {
                    const $input = $box.find('input[type="file"]');
                    $input[0].files = e.originalEvent.dataTransfer.files;
                    
                    // Trigger change event to process the file
                    $input.trigger('change');
                }
            });
            
            // Handle keyboard activation
            $box.on('keydown', function(e) {
                // Enter or Space activates the file input
                if (e.which === 13 || e.which === 32) {
                    e.preventDefault();
                    $box.find('input[type="file"]').trigger('click');
                }
            });
        });
    }
    
    /**
     * Initialize form progress indicator
     */
    function initFormProgressIndicator() {
        const $form = $('#clothing-form');
        const $container = $('<div class="form-progress-container"><div class="form-progress-bar"></div></div>');
        $form.prepend($container);
        
        updateFormProgress();
        
        // Update progress when form fields change
        $form.on('change keyup', 'input, select, textarea', function() {
            updateFormProgress();
        });
        
        function updateFormProgress() {
            const $requiredFields = $form.find(':required');
            const totalFields = $requiredFields.length;
            let filledFields = 0;
            
            $requiredFields.each(function() {
                if ($(this).val()) {
                    filledFields++;
                }
            });
            
            const progressPercent = Math.round((filledFields / totalFields) * 100);
            $container.find('.form-progress-bar').css('width', progressPercent + '%');
        }
    }
    
    /**
     * Keyboard accessibility improvements
     */
    function initKeyboardAccessibility() {
        // Add keyboard navigation for the item containers
        $('#add-item-btn').on('keydown', function(e) {
            // Tab or Arrow Down to navigate to the first item after adding
            if ((e.which === 9 || e.which === 40) && e.shiftKey === false) {
                const $items = $('.clothing-item-container');
                if ($items.length > 1) {
                    const $lastItem = $items.last();
                    const $firstInput = $lastItem.find('input, select, textarea').first();
                    
                    setTimeout(function() {
                        $firstInput.focus();
                    }, 100);
                }
            }
        });
        
        // Add keyboard shortcut for removing items (Alt+Delete)
        $(document).on('keydown', '.clothing-item-container', function(e) {
            if (e.which === 46 && e.altKey) { // Alt+Delete
                const $item = $(this);
                const $removeBtn = $item.find('.remove-item-btn');
                
                if ($removeBtn.is(':visible')) {
                    $removeBtn.trigger('click');
                }
            }
        });
    }
    
})(jQuery);
