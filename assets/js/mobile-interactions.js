/**
 * Mobile touch interactions for the clothing form
 * 
 * Enhances the form experience on mobile devices
 */
(function ($) {
    'use strict';

    // Default options
    const defaults = {
        swipeThreshold: 30,
        touchDuration: 300,
        dragEnabled: true,
        usePassiveEvents: true // Use passive event listeners when available
    };

    // Detect passive event listener support
    let supportsPassive = false;
    try {
        const opts = Object.defineProperty({}, 'passive', {
            get: function () {
                supportsPassive = true;
                return true;
            }
        });
        window.addEventListener('testPassive', null, opts);
        window.removeEventListener('testPassive', null, opts);
    } catch (e) { }

    // Plugin settings
    const settings = $.extend({}, defaults);

    // When DOM is ready
    $(function () {
        // Initialize touch interactions
        initTouchInteractions();

        // Add mobile form class for special styling
        if (isMobileDevice()) {
            $('.clothing-submission-form').addClass('mobile-form');
        }

        // Fix iOS form zoom issue
        if (isIOS()) {
            $('input[type="text"], input[type="email"], select, textarea').on('touchstart focusin', function () {
                $(this).attr('autocomplete', $(this).attr('autocomplete') || 'off');
                $(this).attr('autocorrect', 'off');
                $(this).attr('autocapitalize', 'none');
            });
        }

        // Add autosave capability for forms
        initFormAutosave();
    });

    /**
     * Initialize touch interactions
     */
    function initTouchInteractions() {
        const passiveOption = supportsPassive && settings.usePassiveEvents ? { passive: true } : false;
        const passiveOptionWithCapture = supportsPassive && settings.usePassiveEvent ? { passive: true, capture: true } : true;

        // Touch feedback for buttons
        $('.submit-button, .add-item-btn, button, .button, .remove-item-btn').each(function () {
            // Using standard DOM API for passive listeners
            const element = this;

            element.addEventListener('touchstart', function () {
                $(element).addClass('touch-active');
            }, passiveOption);

            element.addEventListener('touchend', function () {
                $(element).removeClass('touch-active');
            }, passiveOption);
        });

        // Swipe gestures for items if enabled
        if (settings.dragEnabled) {
            let touchStartX = 0;
            let touchStartY = 0;
            let touchStartTime = 0;

            // Using jQuery's .on() with passive option
            $('.clothing-item-container').each(function () {
                // Using standard DOM API for passive listeners
                const element = this;

                element.addEventListener('touchstart', function (e) {
                    touchStartX = e.touches[0].clientX;
                    touchStartY = e.touches[0].clientY;
                    touchStartTime = Date.now();
                    $(element).addClass('being-touched');
                }, passiveOption);

                element.addEventListener('touchmove', function (e) {
                    if (!settings.dragEnabled) return;

                    const touchX = e.touches[0].clientX;
                    const touchY = e.touches[0].clientY;
                    const diffX = touchX - touchStartX;
                    const diffY = Math.abs(touchY - touchStartY);

                    // Only handle horizontal swipes
                    if (diffY < 30) {
                        if (Math.abs(diffX) > 10) {
                            $(element).css('transform', 'translateX(' + diffX / 3 + 'px)');
                        }
                    }
                }, passiveOption);

                element.addEventListener('touchend', function (e) {
                    const touchEndX = e.changedTouches[0].clientX;
                    const touchEndY = e.changedTouches[0].clientY;
                    const touchEndTime = Date.now();

                    const diffX = touchEndX - touchStartX;
                    const diffY = Math.abs(touchEndY - touchStartY);
                    const elapsedTime = touchEndTime - touchStartTime;

                    // Cleanup
                    $(element).removeClass('being-touched');
                    $(element).css('transform', '');

                    // Check if it's a swipe
                    if (Math.abs(diffX) > settings.swipeThreshold &&
                        diffY < 50 &&
                        elapsedTime < settings.touchDuration) {

                        if (diffX > 0) {
                            // Right swipe
                            $(element).trigger('swipeRight');
                        } else {
                            // Left swipe - show delete option
                            $(element).trigger('swipeLeft');
                        }
                    }
                }, passiveOption);
            });

            // Reset all items on body touch
            document.body.addEventListener('touchstart', function (e) {
                if (!$(e.target).closest('.clothing-item-container').length) {
                    $('.clothing-item-container').removeClass('swiped-left swiped-right');
                }
            }, passiveOptionWithCapture);
        }

        // Add pull-to-refresh capability
        initPullToRefresh();
    }

    /**
     * Initialize form autosave capability
     */
    function initFormAutosave() {
        // Check for form data in local storage
        const formData = localStorage.getItem('clothingFormData');
        if (formData) {
            const parsedData = JSON.parse(formData);

            // Show autosave notification
            const notification = $('<div class="autosave-notification"><p>You have a previously unsaved form. Would you like to <a href="#" class="restore-form">restore it</a>? <a href="#" class="clear-autosave">Clear saved data</a></p><button class="close-notification">&times;</button></div>');

            $('.clothing-submission-form').prepend(notification);

            // Restore form data
            notification.find('.restore-form').on('click', function (e) {
                e.preventDefault();
                restoreFormData(parsedData);
                notification.fadeOut(300);
            });

            // Clear autosaved data
            notification.find('.clear-autosave').on('click', function (e) {
                e.preventDefault();
                localStorage.removeItem('clothingFormData');
                notification.fadeOut(300);
            });

            // Close notification
            notification.find('.close-notification').on('click', function () {
                notification.fadeOut(300);
            });
        }

        // Set up autosave on form changes
        let autosaveTimer;
        $('.clothing-submission-form input, .clothing-submission-form select, .clothing-submission-form textarea').on('change', function () {
            clearTimeout(autosaveTimer);
            autosaveTimer = setTimeout(function () {
                saveFormData();
            }, 1000);
        });
    }

    /**
     * Save form data to local storage
     */
    function saveFormData() {
        const formData = {};

        // Collect form data
        $('.clothing-submission-form').find('input, select, textarea').each(function () {
            const input = $(this);
            if (input.attr('type') !== 'file' && input.attr('name')) {
                formData[input.attr('name')] = input.val();
            }
        });

        // Save to local storage
        localStorage.setItem('clothingFormData', JSON.stringify(formData));
    }

    /**
     * Restore form data from saved state
     */
    function restoreFormData(data) {
        // Populate form fields
        Object.keys(data).forEach(function (key) {
            const input = $('.clothing-submission-form').find('[name="' + key + '"]');
            if (input.length) {
                input.val(data[key]);
            }
        });
    }

    /**
     * Initialize pull-to-refresh functionality
     */
    function initPullToRefresh() {
        // Only on mobile devices
        if (!isMobileDevice()) return;

        let pullStartY = 0;
        let pullMoveY = 0;
        let isPulling = false;
        const maxPull = 80;

        // Add pull indicator
        $('.clothing-submission-form').addClass('pull-enabled')
            .prepend('<div class="pull-indicator">Pull down to refresh</div>');

        // Get indicator
        const pullIndicator = $('.pull-indicator');

        // Using standard DOM API for passive listeners
        document.addEventListener('touchstart', function (e) {
            // Only if we're at the top of the page
            if (window.scrollY === 0) {
                pullStartY = e.touches[0].clientY;
                isPulling = true;
                pullIndicator.css({ opacity: 0 });
            }
        }, supportsPassive ? { passive: true } : false);

        document.addEventListener('touchmove', function (e) {
            if (!isPulling) return;

            pullMoveY = e.touches[0].clientY;
            let pullDistance = pullMoveY - pullStartY;

            // Show indicator if we're pulling down
            if (pullDistance > 0 && window.scrollY === 0) {
                let opacity = Math.min(pullDistance / maxPull, 1);
                let transform = Math.min(pullDistance / 2, maxPull / 2);

                pullIndicator.css({
                    opacity: opacity,
                    transform: 'translateY(' + transform + 'px)'
                });

                // Prevent default if we're showing the indicator
                if (opacity > 0.5 && !supportsPassive) {
                    e.preventDefault();
                }
            }
        }, supportsPassive ? { passive: true } : false);

        document.addEventListener('touchend', function (e) {
            if (!isPulling) return;

            let pullDistance = pullMoveY - pullStartY;

            // Reset indicator
            pullIndicator.css({
                opacity: 0,
                transform: 'translateY(0)'
            });

            // Refresh if we pulled enough
            if (pullDistance > maxPull && window.scrollY === 0) {
                // Show loading
                pullIndicator.text('Refreshing...').css({ opacity: 1 });

                // Reload after a short delay
                setTimeout(function () {
                    window.location.reload();
                }, 500);
            }

            isPulling = false;
        }, supportsPassive ? { passive: true } : false);
    }

    /**
     * Check if current device is mobile
     */
    function isMobileDevice() {
        return (typeof window.orientation !== 'undefined') ||
            (navigator.userAgent.indexOf('IEMobile') !== -1) ||
            window.innerWidth <= 768;
    }

    /**
     * Check if the device is running iOS
     */
    function isIOS() {
        return /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.navigator.userAgent.includes('Windows');
    }

})(jQuery);
