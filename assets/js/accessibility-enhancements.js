/**
 * Accessibility Enhancements
 * 
 * Improves form accessibility for users with disabilities
 * 
 * @package PreownedClothingForm
 */

(function($) {
    'use strict';
    
    $(function() {
        // Only run on the form page
        if ($('.clothing-submission-form').length === 0) {
            return;
        }
        
        // Initialize accessibility enhancements
        initAccessibilityLabels();
        initScreenreaderAnnouncements();
        initHighContrastMode();
        initFontSizeAdjustment();
        initKeyboardShortcuts();
    });
    
    /**
     * Enhance ARIA labels and roles
     */
    function initAccessibilityLabels() {
        // Form region
        $('.clothing-submission-form').attr({
            'role': 'form',
            'aria-labelledby': 'form-heading'
        });
        
        // Main form heading
        $('.clothing-submission-form h2').first().attr('id', 'form-heading');
        
        // Form sections
        $('.clothing-submission-form h3').each(function(index) {
            const sectionId = 'form-section-' + index;
            $(this).attr('id', sectionId);
            
            // Mark the following content as a region until the next heading
            const $section = $(this).nextUntil('h3');
            $section.first().attr({
                'role': 'region',
                'aria-labelledby': sectionId
            });
        });
        
        // Item containers
        $('.clothing-item-container').each(function(index) {
            const itemId = index + 1;
            const containerId = 'item-container-' + itemId;
            
            $(this).attr({
                'role': 'group',
                'aria-labelledby': containerId + '-heading',
                'id': containerId
            });
            
            // Add heading ID
            $(this).find('.clothing-item-title').attr('id', containerId + '-heading');
        });
        
        // Image upload areas
        $('.image-upload-box').each(function() {
            const $box = $(this);
            const $label = $box.find('.upload-label');
            const labelText = $label.text();
            const isRequired = $box.hasClass('required');
            
            // Set descriptive ARIA attributes
            $box.attr({
                'role': 'button',
                'aria-label': labelText + (isRequired ? ' (required)' : ' (optional)') + '. Click or press Enter to upload image.',
                'tabindex': '0'
            });
        });
    }
    
    /**
     * Add screen reader announcements for dynamic content
     */
    function initScreenreaderAnnouncements() {
        // Create announcement area for screen readers
        const $announcer = $('<div>', {
            'aria-live': 'polite',
            'class': 'sr-only screen-reader-announcement',
            'id': 'form-announcer'
        }).css({
            'position': 'absolute',
            'width': '1px',
            'height': '1px',
            'padding': '0',
            'margin': '-1px',
            'overflow': 'hidden',
            'clip': 'rect(0, 0, 0, 0)',
            'white-space': 'nowrap',
            'border': '0'
        }).appendTo('.clothing-submission-form');
        
        // Announce when items are added or removed
        $('#add-item-btn').on('click', function() {
            setTimeout(function() {
                announceMessage('New item added. Please fill in the details for this item.');
            }, 500);
        });
        
        // Delegate for dynamically added elements
        $(document).on('click', '.remove-item-btn', function() {
            announceMessage('Item removed from the form.');
        });
        
        // Announce when images are added or removed
        $(document).on('change', '.image-upload-box input[type="file"]', function() {
            const $input = $(this);
            const $box = $input.closest('.image-upload-box');
            const imageType = $box.find('.upload-label').text() || 'Image';
            
            if ($input[0].files && $input[0].files[0]) {
                announceMessage(imageType + ' uploaded successfully.');
            }
        });
        
        $(document).on('click', '.remove-image', function() {
            const $box = $(this).closest('.image-upload-box');
            const imageType = $box.find('.upload-label').text() || 'Image';
            
            announceMessage(imageType + ' removed.');
        });
        
        // Announce form validation errors
        $('#clothing-form').on('submit', function(e) {
            const $invalidFields = $(this).find(':invalid');
            
            if ($invalidFields.length > 0) {
                const errorCount = $invalidFields.length;
                let errorMessage = 'Form has ' + errorCount + ' ' + 
                                   (errorCount === 1 ? 'error' : 'errors') + 
                                   ' that must be corrected before submitting: ';
                
                $invalidFields.each(function(index) {
                    let fieldName = $(this).prev('label').text() || 'Field';
                    errorMessage += fieldName.replace('*', '') + (index < $invalidFields.length - 1 ? ', ' : '');
                });
                
                announceMessage(errorMessage);
            }
        });
        
        function announceMessage(message) {
            $announcer.text(message);
        }
    }
    
    /**
     * Add high contrast mode toggle
     */
    function initHighContrastMode() {
        // Create toggle button
        const $toggleContainer = $('<div>', {
            'class': 'accessibility-controls',
            'style': 'text-align: right; margin-bottom: 10px;'
        });
        
        const $toggleButton = $('<button>', {
            'type': 'button',
            'class': 'a11y-toggle-btn high-contrast-toggle',
            'text': 'High Contrast',
            'aria-pressed': 'false'
        }).css({
            'background': 'transparent',
            'border': '1px solid #666',
            'border-radius': '3px',
            'padding': '3px 8px',
            'font-size': '12px',
            'cursor': 'pointer'
        });
        
        $toggleContainer.append($toggleButton);
        $('.clothing-submission-form').prepend($toggleContainer);
        
        // Check for saved preference
        const savedHighContrast = localStorage.getItem('highContrastMode') === 'true';
        if (savedHighContrast) {
            applyHighContrastMode();
            $toggleButton.attr('aria-pressed', 'true');
        }
        
        // Handle toggle click
        $toggleButton.on('click', function() {
            const isActive = $toggleButton.attr('aria-pressed') === 'true';
            
            if (isActive) {
                removeHighContrastMode();
                $toggleButton.attr('aria-pressed', 'false');
                localStorage.setItem('highContrastMode', 'false');
            } else {
                applyHighContrastMode();
                $toggleButton.attr('aria-pressed', 'true');
                localStorage.setItem('highContrastMode', 'true');
            }
        });
        
        function applyHighContrastMode() {
            const styles = `
                <style id="high-contrast-styles">
                    .clothing-submission-form {
                        background-color: #000 !important;
                        color: #fff !important;
                    }
                    .clothing-submission-form h2,
                    .clothing-submission-form h3,
                    .clothing-submission-form label {
                        color: #fff !important;
                    }
                    .clothing-submission-form input[type="text"],
                    .clothing-submission-form input[type="email"],
                    .clothing-submission-form select,
                    .clothing-submission-form textarea {
                        background-color: #222 !important;
                        color: #fff !important;
                        border: 1px solid #fff !important;
                    }
                    .clothing-item-container {
                        background-color: #333 !important;
                        border-color: #fff !important;
                    }
                    .form-guidance {
                        background-color: #333 !important;
                        border-left-color: yellow !important;
                    }
                    .submit-button,
                    .add-item-btn {
                        background-color: yellow !important;
                        color: #000 !important;
                        border: 2px solid #fff !important;
                    }
                    .item-number-badge {
                        background-color: yellow !important;
                        color: #000 !important;
                    }
                    .remove-item-btn {
                        background-color: #ff0000 !important;
                        color: #fff !important;
                    }
                    .image-upload-box {
                        background-color: #333 !important;
                        border-color: #fff !important;
                    }
                    .upload-label {
                        color: yellow !important;
                    }
                    .required-indicator {
                        color: yellow !important;
                    }
                </style>
            `;
            
            $('head').append(styles);
            $toggleButton.css({
                'background-color': 'yellow',
                'color': '#000',
                'font-weight': 'bold'
            });
        }
        
        function removeHighContrastMode() {
            $('#high-contrast-styles').remove();
            $toggleButton.css({
                'background-color': 'transparent',
                'color': '',
                'font-weight': 'normal'
            });
        }
    }
    
    /**
     * Add font size adjustment controls
     */
    function initFontSizeAdjustment() {
        // Create font size controls
        const $controlsContainer = $('<div>', {
            'class': 'accessibility-controls font-size-controls',
            'style': 'text-align: right; margin-bottom: 10px; display: inline-block; margin-left: 10px;'
        });
        
        const $decreaseBtn = $('<button>', {
            'type': 'button',
            'class': 'a11y-fontsize-btn decrease',
            'text': 'A-',
            'aria-label': 'Decrease font size'
        }).css({
            'background': 'transparent',
            'border': '1px solid #666',
            'border-radius': '3px 0 0 3px',
            'padding': '3px 8px',
            'font-size': '12px',
            'cursor': 'pointer'
        });
        
        const $increaseBtn = $('<button>', {
            'type': 'button',
            'class': 'a11y-fontsize-btn increase',
            'text': 'A+',
            'aria-label': 'Increase font size'
        }).css({
            'background': 'transparent',
            'border': '1px solid #666',
            'border-left': 'none',
            'border-radius': '0 3px 3px 0',
            'padding': '3px 8px',
            'font-size': '12px',
            'cursor': 'pointer'
        });
        
        $controlsContainer.append($decreaseBtn).append($increaseBtn);
        $('.accessibility-controls').first().after($controlsContainer);
        
        // Set initial font size from localStorage
        const savedFontSize = localStorage.getItem('clothingFormFontSize');
        if (savedFontSize) {
            applyFontSize(parseInt(savedFontSize, 10));
        }
        
        // Handle button clicks
        $decreaseBtn.on('click', function() {
            const currentSize = parseInt(localStorage.getItem('clothingFormFontSize') || '100', 10);
            const newSize = Math.max(70, currentSize - 10); // Don't go below 70%
            applyFontSize(newSize);
            localStorage.setItem('clothingFormFontSize', newSize.toString());
        });
        
        $increaseBtn.on('click', function() {
            const currentSize = parseInt(localStorage.getItem('clothingFormFontSize') || '100', 10);
            const newSize = Math.min(150, currentSize + 10); // Don't go above 150%
            applyFontSize(newSize);
            localStorage.setItem('clothingFormFontSize', newSize.toString());
        });
        
        function applyFontSize(sizePercent) {
            // Remove any existing font size styles
            $('#font-size-styles').remove();
            
            // Create and add new styles
            const styles = `
                <style id="font-size-styles">
                    .clothing-submission-form {
                        font-size: ${sizePercent}% !important;
                    }
                </style>
            `;
            
            $('head').append(styles);
        }
    }
    
    /**
     * Add keyboard shortcuts for common actions
     */
    function initKeyboardShortcuts() {
        // Add keyboard shortcut info
        const $shortcutsInfo = $('<div>', {
            'class': 'keyboard-shortcuts-info',
            'style': 'margin: 15px 0; padding: 10px; background: #f8f8f8; border: 1px solid #ddd; display: none;'
        }).html(`
            <h4 style="margin-top: 0;">Keyboard Shortcuts</h4>
            <ul style="margin-bottom: 0;">
                <li><strong>Shift+A</strong> - Add a new item</li>
                <li><strong>Alt+Delete</strong> - Remove current item</li>
                <li><strong>Alt+S</strong> - Submit form</li>
                <li><strong>Alt+H</strong> - Show/hide this help</li>
                <li><strong>Alt+C</strong> - Toggle high contrast mode</li>
            </ul>
        `);
        
        // Add toggle button
        const $shortcutsBtn = $('<button>', {
            'type': 'button',
            'class': 'keyboard-shortcuts-btn',
            'text': 'Keyboard Shortcuts',
            'aria-expanded': 'false',
            'aria-controls': 'shortcuts-info'
        }).css({
            'background': 'transparent',
            'border': '1px solid #666',
            'border-radius': '3px',
            'padding': '3px 8px',
            'font-size': '12px',
            'cursor': 'pointer',
            'margin-left': '10px'
        });
        
        $('.font-size-controls').after($shortcutsBtn);
        $shortcutsBtn.after($shortcutsInfo);
        
        // Toggle shortcuts info
        $shortcutsBtn.on('click', function() {
            const isExpanded = $shortcutsBtn.attr('aria-expanded') === 'true';
            $shortcutsInfo.slideToggle();
            $shortcutsBtn.attr('aria-expanded', !isExpanded);
        });
        
        // Handle keyboard shortcuts
        $(document).on('keydown', function(e) {
            // Alt+H: Toggle shortcuts help
            if (e.altKey && e.which === 72) {
                e.preventDefault();
                $shortcutsBtn.click();
            }
            
            // Alt+S: Submit form
            if (e.altKey && e.which === 83) {
                e.preventDefault();
                $('#clothing-form').submit();
            }
            
            // Shift+A: Add new item
            if (e.shiftKey && e.which === 65) {
                e.preventDefault();
                $('#add-item-btn').click();
            }
            
            // Alt+C: Toggle high contrast
            if (e.altKey && e.which === 67) {
                e.preventDefault();
                $('.high-contrast-toggle').click();
            }
        });
    }
    
})(jQuery);