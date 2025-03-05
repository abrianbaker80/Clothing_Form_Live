/**
 * Image Upload Functionality
 * 
 * Handles image uploads, previews, and validation
 */
(function($) {
    'use strict';
    
    // Initialize on document ready
    $(document).ready(function() {
        console.log("Image upload script initializing");
        
        // Get max size from data attribute
        const maxSizeAttr = $('.image-upload-container').data('max-size') || 2;
        
        // Initialize event handlers for upload boxes
        $('.image-upload-box').each(function(index) {
            const box = $(this);
            const input = box.find('input[type="file"]');
            
            console.log(`Initializing upload box ${index + 1}`);
            
            // Handle file selection
            input.on('change', function() {
                if (this.files && this.files[0]) {
                    const file = this.files[0];
                    console.log(`File selected: ${file.name}`);
                    
                    // Check file size
                    if (file.size > maxSizeAttr * 1024 * 1024) {
                        alert(`File is too large. Maximum size is ${maxSizeAttr}MB.`);
                        return;
                    }
                    
                    // Create image preview
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        // Remove existing preview
                        box.find('.image-preview, .remove-preview-btn').remove();
                        
                        // Hide placeholder
                        box.find('.upload-placeholder').hide();
                        
                        // Add preview elements
                        const preview = $('<div class="image-preview"></div>').css('background-image', `url(${e.target.result})`);
                        const removeBtn = $('<button type="button" class="remove-preview-btn" aria-label="Remove Image"><i class="fas fa-times"></i></button>');
                        
                        box.append(preview).append(removeBtn);
                        box.addClass('has-image');
                        
                        // Handle remove button
                        removeBtn.on('click', function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            
                            // Clear input
                            input.val('');
                            
                            // Remove preview
                            preview.remove();
                            removeBtn.remove();
                            
                            // Show placeholder
                            box.find('.upload-placeholder').show();
                            box.removeClass('has-image');
                        });
                    };
                    reader.readAsDataURL(file);
                }
            });
        });
        
        // Initialize drag and drop if the script is available
        if (typeof setupDragDropHandlers === 'function') {
            setupDragDropHandlers();
        }
    });
    
    // Public methods - expose if needed
    window.pcfImageUpload = {
        // Any functions you want to expose globally
    };
})(jQuery);

/**
 * Image Upload Handler for Clothing Form
 * Handles image preview and processing
 */
(function($) {
    'use strict';
    
    /**
     * Initialize image upload handlers
     */
    function initImageUploads() {
        // Handle file input changes
        $(document).on('change', '.image-upload-box input[type="file"]', function() {
            const $input = $(this);
            const $box = $input.closest('.image-upload-box');
            
            // Check if file was selected
            if (this.files && this.files[0]) {
                const file = this.files[0];
                
                // Check file size
                const maxSize = parseFloat($box.closest('.image-upload-container').data('max-size')) || 2;
                const maxSizeBytes = maxSize * 1024 * 1024;
                
                if (file.size > maxSizeBytes) {
                    showUploadError($box, `Image is too large. Maximum size is ${maxSize}MB.`);
                    $input.val('');
                    return;
                }
                
                // Create and show image preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    showImagePreview($box, e.target.result);
                };
                reader.readAsDataURL(file);
                
                // Add has-image class
                $box.addClass('has-image');
            } else {
                // No file selected or canceled
                removeImagePreview($box);
            }
        });
        
        // Handle remove button clicks
        $(document).on('click', '.image-upload-box .remove-image', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const $box = $(this).closest('.image-upload-box');
            removeImagePreview($box);
            
            // Clear file input
            $box.find('input[type="file"]').val('');
            $box.removeClass('has-image');
        });
        
        // Handle drag and drop
        setupDragDropHandlers();
    }
    
    /**
     * Show image preview
     */
    function showImagePreview($box, src) {
        // Remove any existing preview
        removeImagePreview($box);
        
        // Add preview elements
        const $preview = $('<div class="image-preview"></div>');
        $preview.css('background-image', `url(${src})`);
        
        const $removeBtn = $('<button type="button" class="remove-image">×</button>');
        
        $box.append($preview);
        $box.append($removeBtn);
        
        // Hide the placeholder
        $box.find('.upload-placeholder').hide();
    }
    
    /**
     * Remove image preview
     */
    function removeImagePreview($box) {
        $box.find('.image-preview').remove();
        $box.find('.remove-image').remove();
        $box.find('.upload-error').remove();
        $box.find('.upload-placeholder').show();
    }
    
    /**
     * Show upload error
     */
    function showUploadError($box, message) {
        // Remove any existing error
        $box.find('.upload-error').remove();
        
        // Add error message
        const $error = $('<div class="upload-error"></div>').text(message);
        $box.append($error);
        
        // Auto-hide after 5 seconds
        setTimeout(function() {
            $error.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    /**
     * Set up drag and drop handlers
     */
    function setupDragDropHandlers() {
        // Prevent default drag behaviors
        $(document).on('dragover dragenter', function(e) {
            e.preventDefault();
            e.stopPropagation();
        });
        
        // Highlight drop zone on drag over
        $(document).on('dragover dragenter', '.image-upload-box', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).addClass('drag-over');
        });
        
        // Remove highlight on drag leave
        $(document).on('dragleave dragend', '.image-upload-box', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('drag-over');
        });
        
        // Handle drop event
        $(document).on('drop', '.image-upload-box', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const $box = $(this);
            $box.removeClass('drag-over');
            
            // Get dropped files
            const dt = e.originalEvent.dataTransfer;
            if (dt && dt.files && dt.files.length) {
                const $input = $box.find('input[type="file"]');
                
                // Set the file input value with the dropped file
                $input[0].files = dt.files;
                
                // Trigger change event
                $input.trigger('change');
            }
        });
    }
    
    // Initialize on document ready
    $(document).ready(function() {
        initImageUploads();
    });
    
})(jQuery);