/**
 * Lazy Loading Script with ES6+ features
 * 
 * Loads images only when they scroll into view
 */
(($) => {
    'use strict';
    
    // Initialize once DOM is ready
    $(() => {
        // Add lazy load class to all image elements
        $('.image-preview').addClass('lazy-load');
        
        // Initialize lazy loading
        initLazyLoading();
    });
    
    const initLazyLoading = () => {
        // Load visible images immediately
        loadVisibleImages();
        
        // Listen for scroll, resize and orientationchange events with passive option for better performance
        $(window).on('scroll.lazyload resize.lazyload orientationchange.lazyload', 
            throttle(loadVisibleImages, 200));
    };
    
    const loadVisibleImages = () => {
        $('.lazy-load').each(function() {
            const $img = $(this);
            
            // Skip already loaded images
            if ($img.hasClass('loaded')) {
                return;
            }
            
            if (isElementInViewport(this)) {
                // Load image by setting the src attribute
                if ($img.data('src')) {
                    $img.attr('src', $img.data('src')).removeAttr('data-src');
                }
                
                // Mark as loaded when image is loaded
                $img.on('load', () => $img.addClass('loaded'));
            }
        });
    };
    
    const isElementInViewport = (el) => {
        const rect = el.getBoundingClientRect();
        const viewportHeight = window.innerHeight || document.documentElement.clientHeight;
        const viewportWidth = window.innerWidth || document.documentElement.clientWidth;
        
        // Add 100px offset for pre-loading
        return (
            rect.top <= viewportHeight + 100 && 
            rect.bottom >= 0 &&
            rect.left <= viewportWidth + 100 &&
            rect.right >= 0
        );
    };
    
    // Throttle function to limit function calls
    const throttle = (func, limit) => {
        let inThrottle;
        return function() {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => {
                    inThrottle = false;
                }, limit);
            }
        };
    };
    
})(jQuery);
