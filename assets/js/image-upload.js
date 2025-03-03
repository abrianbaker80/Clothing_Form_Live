/**
 * Image Upload Functionality
 * 
 * Handles image uploads, previews, and validation
 */
(function($) {
    'use strict';
    
    // Maximum image size in MB
    let maxImageSize = 2;
    
    // Image types configuration
    const imageTypes = {
        'front': {
            label: 'Front',
            hint: 'Show the front of the garment laid flat or on a hanger'
        },
        'back': {
            label: 'Back',
            hint: 'Show the back of the garment laid flat or on a hanger'
        },
        'brand_tag': {
            label: 'Brand Tag',
            hint: 'Close-up of the brand/size tag'
        },
        'material_tag': {
            label: 'Material Tag',
            hint: 'Close-up of the fabric/care label'
        },
        'detail': {
            label: 'Detail',
            hint: 'Any special details, damage, or distinctive features'
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        // Get max size from data attribute
        const maxSizeAttr = $('.image-upload-container').data('max-size');
        if (maxSizeAttr) {
            maxImageSize = maxSizeAttr;
        }
        
        // Initialize image uploads for first item
        initializeImageUploads(1);
        
        // Delegate event for dynamically added items
        $('#clothing-form').on('imageUploadInit', function(e, itemId) {
            initializeImageUploads(itemId);
        });
        
        // Drag and drop functionality
        setupDragAndDrop();
    });
    
    /**
     * Initialize image upload functionality for an item
     */
    function initializeImageUploads(itemId) {
        const fileInputs = document.querySelectorAll(`input[name^="items[${itemId}][images]"]`);
        
        fileInputs.forEach(input => {
            const box = input.closest('.image-upload-box');
            if (!box) return;
            
            // Add click handler to box
            box.addEventListener('click', function(e) {
                if (e.target !== input) {
                    input.click();
                }
            });
            
            // Handle file selection
            input.addEventListener('change', function() {
                handleFileSelect(this, box);
            });
            
            // Initialize drag and drop for this box
            setupSingleBoxDragDrop(box, input);
        });
    }
    
    /**
     * Handle file selection from input
     */
    function handleFileSelect(input, box) {
        if (!input.files || !input.files[0]) return;
        
        const file = input.files[0];
        
        // Check file size
        if (!validateFileSize(file, input)) return;
        
        // Check file type
        if (!validateFileType(file, input)) return;
        
        // Show preview
        showImagePreview(file, box);
        
        // Trigger form validation update
        if (window.pcfWizard && typeof window.pcfWizard.validateStep === 'function') {
            window.pcfWizard.validateStep(2); // 2 is the photo step index
        }
    }
    
    /**
     * Validate file size
     */
    function validateFileSize(file, input) {
        const maxSizeBytes = maxImageSize * 1024 * 1024;
        
        if (file.size > maxSizeBytes) {
            const errorMsg = `Image is too large. Please select an image smaller than ${maxImageSize}MB.`;
            alert(errorMsg);
            input.value = '';
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate file type
     */
    function validateFileType(file, input) {
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        
        if (!allowedTypes.includes(file.type)) {
            const errorMsg = 'Only JPEG, PNG, GIF, and WEBP images are allowed.';
            alert(errorMsg);
            input.value = '';
            return false;
        }
        
        return true;
    }
    
    /**
     * Show image preview
     */
    function showImagePreview(file, box) {
        // Create a progress indicator
        let progressBar = box.querySelector('.upload-progress');
        if (!progressBar) {
            progressBar = document.createElement('div');
            progressBar.className = 'upload-progress';
            progressBar.innerHTML = `
                <div class="progress-track">
                    <div class="progress-fill"></div>
                </div>
                <div class="progress-text">Reading image...</div>
            `;
            box.appendChild(progressBar);
        }
        
        // Hide placeholder
        const placeholder = box.querySelector('.upload-placeholder');
        if (placeholder) placeholder.style.display = 'none';
        
        // Show progress
        const progressFill = progressBar.querySelector('.progress-fill');
        const progressText = progressBar.querySelector('.progress-text');
        
        // Simulate progress 
        let progress = 0;
        const interval = setInterval(() => {
            progress += 5;
            progressFill.style.width = Math.min(progress, 90) + '%';
            progressText.textContent = 'Processing image...';
            
            if (progress >= 90) {
                clearInterval(interval);
            }
        }, 50);
        
        // Read the file and create preview
        const reader = new FileReader();
        reader.onload = function(e) {
            clearInterval(interval);
            progressFill.style.width = '100%';
            progressText.textContent = 'Complete!';
            
            // Create preview
            setTimeout(() => {
                // Remove progress bar
                progressBar.remove();
                
                // Create/update preview
                let preview = box.querySelector('.image-preview');
                if (!preview) {
                    preview = document.createElement('div');
                    preview.className = 'image-preview';
                    box.appendChild(preview);
                }
                
                // Set background image
                preview.style.backgroundImage = `url(${e.target.result})`;
                
                // Add remove button
                let removeBtn = box.querySelector('.remove-preview-btn');
                if (!removeBtn) {
                    removeBtn = document.createElement('button');
                    removeBtn.type = 'button';
                    removeBtn.className = 'remove-preview-btn';
                    removeBtn.innerHTML = '<i class="fas fa-times"></i>';
                    removeBtn.addEventListener('click', (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        
                        // Reset file input
                        const input = box.querySelector('input[type="file"]');
                        if (input) input.value = '';
                        
                        // Remove preview
                        preview.remove();
                        removeBtn.remove();
                        
                        // Show placeholder
                        if (placeholder) placeholder.style.display = '';
                        
                        // Update review section if available
                        if (window.pcfWizard && typeof window.pcfWizard.updateReviewSection === 'function') {
                            window.pcfWizard.updateReviewSection();
                        }
                        
                        // Re-validate
                        if (window.pcfWizard && typeof window.pcfWizard.validateStep === 'function') {
                            window.pcfWizard.validateStep(2);
                        }
                    });
                    box.appendChild(removeBtn);
                }
                
                // Add has-image class to box
                box.classList.add('has-image');
                
                // Update review section if available
                if (window.pcfWizard && typeof window.pcfWizard.updateReviewSection === 'function') {
                    window.pcfWizard.updateReviewSection();
                }
            }, 500);
        };
        
        reader.onerror = function() {
            clearInterval(interval);
            progressBar.remove();
            alert('There was an error reading the file.');
            
            const input = box.querySelector('input[type="file"]');
            if (input) input.value = '';
            
            if (placeholder) placeholder.style.display = '';
        };
        
        reader.readAsDataURL(file);
    }
    
    /**
     * Setup drag and drop functionality
     */
    function setupDragAndDrop() {
        // Add global drag and drop support
        const form = document.getElementById('clothing-form');
        if (!form) return;
        
        form.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Highlight drop zone if we're on the photos step
            const photoStep = document.querySelector('.wizard-step:nth-child(3)');
            if (photoStep && photoStep.classList.contains('active')) {
                photoStep.classList.add('drag-over');
            }
        });
        
        form.addEventListener('dragleave', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const photoStep = document.querySelector('.wizard-step:nth-child(3)');
            if (photoStep) {
                photoStep.classList.remove('drag-over');
            }
        });
        
        form.addEventListener('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const photoStep = document.querySelector('.wizard-step:nth-child(3)');
            if (photoStep) {
                photoStep.classList.remove('drag-over');
            }
            
            // Check if we're on the photos step
            if (!photoStep || !photoStep.classList.contains('active')) {
                return;
            }
            
            // Get the first available upload box
            const emptyBoxes = photoStep.querySelectorAll('.image-upload-box:not(.has-image)');
            if (emptyBoxes.length === 0) return;
            
            // Get the dropped files
            const files = e.dataTransfer.files;
            if (!files || files.length === 0) return;
            
            // Process each file (up to available boxes)
            const maxFiles = Math.min(files.length, emptyBoxes.length);
            for (let i = 0; i < maxFiles; i++) {
                const input = emptyBoxes[i].querySelector('input[type="file"]');
                if (input) {
                    // Set the file to the input
                    const fileList = new DataTransfer();
                    fileList.items.add(files[i]);
                    input.files = fileList.files;
                    
                    // Trigger change event
                    const event = new Event('change', { bubbles: true });
                    input.dispatchEvent(event);
                }
            }
        });
    }
    
    /**
     * Setup drag and drop for an individual upload box
     */
    function setupSingleBoxDragDrop(box, input) {
        box.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            box.classList.add('drag-over');
        });
        
        box.addEventListener('dragleave', function(e) {
            e.preventDefault();
            e.stopPropagation();
            box.classList.remove('drag-over');
        });
        
        box.addEventListener('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            box.classList.remove('drag-over');
            
            const files = e.dataTransfer.files;
            if (!files || files.length === 0) return;
            
            // Use only first file
            const fileList = new DataTransfer();
            fileList.items.add(files[0]);
            input.files = fileList.files;
            
            // Trigger change event
            const event = new Event('change', { bubbles: true });
            input.dispatchEvent(event);
        });
    }
    
    // Expose functions to global scope
    window.pcfImageUpload = {
        initializeImageUploads: initializeImageUploads,
        showImagePreview: showImagePreview
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