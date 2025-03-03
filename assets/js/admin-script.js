/**
 * Preowned Clothing Form - Admin JavaScript
 * 
 * Handles admin-specific functionality for the clothing submissions.
 */

(function($) {
    'use strict';

    // Document ready
    $(function() {
        // Initialize checkboxes in bulk actions
        $('#cb-select-all-1, #cb-select-all-2').on('click', function() {
            const isChecked = $(this).prop('checked');
            $('input[name="submission_ids[]"]').prop('checked', isChecked);
        });

        // Enhanced image gallery functionality
        $('.image-container img').on('click', function() {
            const imgSrc = $(this).attr('src');
            
            // Create lightbox overlay
            const lightbox = $('<div class="pc-lightbox-overlay"></div>');
            const content = $('<div class="pc-lightbox-content"></div>');
            const closeBtn = $('<span class="pc-lightbox-close">&times;</span>');
            
            // Add image to lightbox
            content.append($('<img>').attr('src', imgSrc));
            content.append(closeBtn);
            lightbox.append(content);
            
            // Add to document body
            $('body').append(lightbox);
            
            // Close handlers
            closeBtn.add(lightbox).on('click', function() {
                lightbox.remove();
            });
            
            // Prevent propagation from image clicks
            content.find('img').on('click', function(e) {
                e.stopPropagation();
            });
            
            // Allow Escape key to close
            $(document).one('keyup', function(e) {
                if (e.key === 'Escape') {
                    lightbox.remove();
                }
            });
        });

        // Confirm deletion actions
        $('.action-delete').on('click', function() {
            return confirm('Are you sure you want to delete this submission? This action cannot be undone.');
        });
    });
    
    /**
     * Item tab navigation
     */
    function initItemTabs() {
        // If no item tabs exist, exit early
        if ($('.items-tabs').length === 0) return;
        
        // Initially hide all items
        $('.item-detail-card').hide();
        
        // If we have item ID in the URL, show that item
        const urlParams = new URLSearchParams(window.location.search);
        let activeItemId = urlParams.get('item_id');
        
        if (activeItemId && $('#item-' + activeItemId).length) {
            // Show the active item
            $('#item-' + activeItemId).show();
            $('.item-tab a[data-item-id="' + activeItemId + '"]').parent().addClass('active');
        } else {
            // Show the first item by default
            $('.item-detail-card:first').show();
            $('.item-tab:first').addClass('active');
        }
        
        // Tab click handling
        $('.item-tab a').on('click', function(e) {
            e.preventDefault();
            
            // Get the target item ID
            const itemId = $(this).attr('href').substring(1);
            
            // Hide all items and show the selected one
            $('.item-detail-card').hide();
            $('#' + itemId).show();
            
            // Update active tab
            $('.item-tab').removeClass('active');
            $(this).parent().addClass('active');
            
            // Update URL without page reload for better navigation
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('item_id', $(this).data('item-id'));
            window.history.pushState({}, '', currentUrl);
        });
    }

    /**
     * Initialize image lightbox functionality
     */
    function initImageLightbox() {
        $('.item-image').on('click', function() {
            const imgSrc = $(this).attr('src');
            const imgAlt = $(this).attr('alt');
            
            // Create lightbox elements
            const lightbox = $('<div class="pc-lightbox-overlay"></div>');
            const content = $('<div class="pc-lightbox-content"></div>');
            const img = $('<img src="' + imgSrc + '" alt="' + imgAlt + '">');
            const close = $('<div class="pc-lightbox-close">&times;</div>');
            
            // Assemble and append to body
            content.append(img);
            content.append(close);
            lightbox.append(content);
            $('body').append(lightbox);
            
            // Add close functionality
            close.on('click', function() {
                lightbox.fadeOut(300, function() {
                    $(this).remove();
                });
            });
            
            lightbox.on('click', function(e) {
                if ($(e.target).hasClass('pc-lightbox-overlay')) {
                    lightbox.fadeOut(300, function() {
                        $(this).remove();
                    });
                }
            });
            
            // Show with animation
            img.css('opacity', 0);
            lightbox.fadeIn(300);
            img.animate({ opacity: 1 }, 300);
        });
    }
    
    /**
     * Enhanced filter functionality
     */
    function initFilters() {
        $('.filter-group input, .filter-group select').on('keyup change', function() {
            // Add visual indication that filters have changed
            $(this).addClass('filter-changed');
        });
        
        // Add datepicker if available
        if ($.datepicker) {
            $('.date-filter').datepicker({
                dateFormat: 'yy-mm-dd',
                changeMonth: true,
                changeYear: true
            });
        }
    }
    
    /**
     * Add animation effects to status changes
     */
    function initStatusButtonEffects() {
        $('.status-button').on('click', function(e) {
            // Don't prevent default - let the link work normally
            // Just add visual feedback
            const originalText = $(this).text();
            const originalWidth = $(this).width();
            $(this).width(originalWidth); // Prevent button width from changing
            $(this).html('<i class="fas fa-spinner fa-spin"></i> Processing...');
            
            // If we wanted to prevent the default and do an AJAX update:
            // e.preventDefault();
            // const href = $(this).attr('href');
            // $.get(href, function(response) {
            //     // Handle response
            //     window.location.reload();
            // });
        });
    }
    
    /**
     * Confirmation for delete operations
     */
    function initDeleteConfirmations() {
        $('.delete-button').on('click', function(e) {
            const confirmMsg = $(this).data('confirm') || 'Are you sure you want to delete this item?';
            if (!confirm(confirmMsg)) {
                e.preventDefault();
                return false;
            }
        });
    }
    
    /**
     * Bulk action confirmation
     */
    function initBulkActionConfirmation() {
        $('form').on('submit', function(e) {
            const action = $(this).find('select[name="bulk_action"], select[name="bulk_action2"]').val();
            const selected = $(this).find('input[name="submission_ids[]"]:checked').length;
            
            if (action === 'delete' && selected > 0) {
                if (!confirm('Are you sure you want to delete the ' + selected + ' selected submissions? This cannot be undone.')) {
                    e.preventDefault();
                    return false;
                }
            }
        });
    }
    
    /**
     * Autosize text areas
     */
    function initAutosizeTextareas() {
        $('.notes-field textarea').on('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        }).trigger('input');
    }
    
    /**
     * Enhanced notifications
     */
    function initNotifications() {
        $('.notice').each(function() {
            $(this).hide().slideDown(300);
            
            const self = $(this);
            if (self.hasClass('is-dismissible')) {
                setTimeout(function() {
                    self.slideUp(300);
                }, 5000);
            }
        });
    }
    
    /**
     * Checkbox select all functionality 
     */
    function initCheckboxSelectAll() {
        $('#cb-select-all-1, #cb-select-all-2').on('click', function() {
            const isChecked = $(this).prop('checked');
            $('input[name="submission_ids[]"]').prop('checked', isChecked);
            
            // Update the other "select all" checkbox
            const id = $(this).attr('id');
            const otherId = id === 'cb-select-all-1' ? '#cb-select-all-2' : '#cb-select-all-1';
            $(otherId).prop('checked', isChecked);
        });
    }
    
    // Initialize all admin functions - with better error handling
    function init() {
        try {
            initItemTabs();
            initImageLightbox();
            initFilters();
            initStatusButtonEffects();
            initDeleteConfirmations(); 
            initBulkActionConfirmation();
            initAutosizeTextareas();
            initNotifications();
            initCheckboxSelectAll();
            
            console.log('Admin UI enhancements initialized successfully');
        } catch(e) {
            console.error('Error initializing admin UI enhancements:', e);
        }
    }
    
    // Call init function when DOM is ready
    $(document).ready(function() {
        init();
    });
    
})(jQuery);
