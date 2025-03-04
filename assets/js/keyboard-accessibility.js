/**
 * Keyboard Accessibility Enhancements
 */
(function($) {
    'use strict';
    
    /**
     * Initialize keyboard accessibility enhancements
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
    
    // Initialize on document ready
    $(document).ready(initKeyboardAccessibility);
    
})(jQuery);
