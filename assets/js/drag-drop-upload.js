/**
 * Enhanced image upload functionality
 * - Drag and drop support
 * - Multiple file uploads
 * - Image preview gallery
 * - Mobile camera integration
 */
(function($) {
    'use strict';

    // Configuration
    const config = {
        maxFileSize: 0, // Will be set from PHP
        acceptedTypes: ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
        maxUploadsPerBox: 1, // Standard is 1 file per upload box
        thumbnailSize: 100
    };

    // Initialize the enhanced upload functionality
    function initEnhancedImageUploads() {
        // Set max file size from data attribute
        const maxSizeMB = $('.image-upload-container').data('max-size') || 2;
        config.maxFileSize = maxSizeMB * 1024 * 1024;

        // Process all upload boxes
        $('.image-upload-box').each(function() {
            const $uploadBox = $(this);
            
            // Skip if already initialized
            if ($uploadBox.hasClass('enhanced-upload-initialized')) {
                return;
            }
            
            // Mark as initialized
            $uploadBox.addClass('enhanced-upload-initialized');
            
            // Get the file input and form group
            const $fileInput = $uploadBox.find('input[type="file"]');
            const $formGroup = $uploadBox.closest('.form-group');
            
            // Add drag and drop functionality
            setupDragAndDrop($uploadBox, $fileInput);
            
            // Add camera button for mobile devices if supported
            if (isMobileDevice() && hasGetUserMedia()) {
                addCameraButton($uploadBox, $fileInput);
            }
            
            // Handle file selection
            $fileInput.on('change', function(event) {
                handleFiles(this.files, $uploadBox, $fileInput);
            });
            
            // Check if the input already has files (from autosave or browser cache)
            if ($fileInput[0].files && $fileInput[0].files.length > 0) {
                handleFiles($fileInput[0].files, $uploadBox, $fileInput);
            }
        });
    }

    // Set up drag and drop functionality
    function setupDragAndDrop($uploadBox, $fileInput) {
        // Add/remove drag-over class to provide visual feedback
        $uploadBox.on('dragover dragenter', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).addClass('drag-over');
        }).on('dragleave dragend drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('drag-over');
        });

        // Handle drop event
        $uploadBox.on('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Get the files from the drop event
            const dt = e.originalEvent.dataTransfer;
            const files = dt.files;
            
            handleFiles(files, $uploadBox, $fileInput);
        });

        // Add instructions for drag and drop
        if (!$uploadBox.find('.drop-instructions').length) {
            $uploadBox.find('.upload-placeholder').append(
                '<div class="drop-instructions">or drop image here</div>'
            );
        }
    }

    // Add camera button for mobile devices
    function addCameraButton($uploadBox, $fileInput) {
        // Create camera button if it doesn't exist
        if (!$uploadBox.find('.camera-capture-btn').length) {
            const $cameraBtn = $(
                '<button type="button" class="camera-capture-btn" aria-label="Take Photo">' +
                '<i class="fas fa-camera"></i>' +
                '</button>'
            );
            
            $uploadBox.append($cameraBtn);
            
            // Handle camera button click
            $cameraBtn.on('click', function(e) {
                e.preventDefault();
                
                // Create an input element specifically for camera capture
                const cameraInput = document.createElement('input');
                cameraInput.type = 'file';
                cameraInput.accept = 'image/*';
                cameraInput.capture = 'environment'; // Use the environment-facing camera (usually back camera)
                
                // Handle file selection
                cameraInput.addEventListener('change', function() {
                    if (this.files && this.files.length > 0) {
                        handleFiles(this.files, $uploadBox, $fileInput);
                    }
                });
                
                // Trigger the file selection dialog
                cameraInput.click();
            });
        }
    }

    // Handle the selected files
    function handleFiles(files, $uploadBox, $fileInput) {
        if (!files || files.length === 0) return;
        
        // When dealing with a standard file input, only use the first file
        // (unless we're in multi-file mode)
        const file = files[0];
        
        // Validate the file
        if (!validateFile(file, $uploadBox)) {
            return;
        }
        
        // Update the original file input
        if (typeof DataTransfer === 'function') {
            // Modern browsers support DataTransfer API
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);
            $fileInput[0].files = dataTransfer.files;
        } else {
            // For older browsers, we'll need to rely on the preview
            // but won't be able to programmatically update the input
            console.log('DataTransfer API not supported in this browser. Preview will work but form submission might not include the file.');
        }
        
        // Create and show image preview with progress
        createImagePreview(file, $uploadBox, $fileInput);
    }

    // Validate the file type and size
    function validateFile(file, $uploadBox) {
        // Check file type
        if (!config.acceptedTypes.includes(file.type)) {
            showError($uploadBox, 'Invalid file type. Please use JPEG, PNG or GIF images.');
            return false;
        }
        
        // Check file size
        if (file.size > config.maxFileSize) {
            const maxSizeMB = config.maxFileSize / (1024 * 1024);
            showError($uploadBox, `File is too large. Maximum size is ${maxSizeMB}MB.`);
            return false;
        }
        
        return true;
    }

    // Create and display the image preview with progress indicator
    function createImagePreview(file, $uploadBox, $fileInput) {
        // Hide the placeholder
        $uploadBox.find('.upload-placeholder').hide();
        
        // Remove any existing preview
        $uploadBox.find('.preview-container').remove();
        
        // Create the preview container
        const $previewContainer = $(
            '<div class="preview-container">' +
            '  <div class="preview-progress">' +
            '    <div class="preview-progress-bar"></div>' +
            '    <div class="preview-progress-text">Reading image...</div>' +
            '  </div>' +
            '</div>'
        );
        
        $uploadBox.append($previewContainer);
        
        // Read the file and show preview
        const reader = new FileReader();
        
        // Progress updates
        let progress = 0;
        const progressInterval = setInterval(function() {
            progress = Math.min(progress + 5, 90);
            $previewContainer.find('.preview-progress-bar').css('width', progress + '%');
            
            if (progress >= 90) {
                clearInterval(progressInterval);
            }
        }, 50);
        
        // When file is read
        reader.onload = function(e) {
            clearInterval(progressInterval);
            
            // Complete the progress bar
            $previewContainer.find('.preview-progress-bar').css('width', '100%');
            $previewContainer.find('.preview-progress-text').text('Complete');
            
            // Short delay to show completion
            setTimeout(function() {
                // Remove progress and show preview
                $previewContainer.find('.preview-progress').fadeOut(200, function() {
                    // Create the preview image
                    const $previewImage = $(
                        '<div class="preview-image" style="background-image: url(' + e.target.result + ')"></div>'
                    );
                    
                    // Add remove button
                    const $removeBtn = $(
                        '<button type="button" class="preview-remove" aria-label="Remove Image">' +
                        '<i class="fas fa-times"></i>' +
                        '</button>'
                    );
                    
                    // Handle remove button click
                    $removeBtn.on('click', function() {
                        // Clear the file input
                        $fileInput.val('');
                        
                        // Remove the preview
                        $previewContainer.remove();
                        
                        // Show the placeholder
                        $uploadBox.find('.upload-placeholder').show();
                        
                        // Trigger change event to update validation and review
                        $fileInput.trigger('change');
                    });
                    
                    // Add preview elements
                    $previewContainer.append($previewImage, $removeBtn);
                    
                    // Trigger change event for the file input
                    // This will update any validation or review sections
                    $fileInput.trigger('change');
                });
            }, 500);
        };
        
        // Handle errors
        reader.onerror = function() {
            clearInterval(progressInterval);
            $previewContainer.remove();
            $uploadBox.find('.upload-placeholder').show();
            showError($uploadBox, 'Failed to read the image file.');
        };
        
        // Start reading the file
        reader.readAsDataURL(file);
    }

    // Show error message
    function showError($uploadBox, message) {
        // Remove any existing error
        $uploadBox.find('.upload-error').remove();
        
        // Create and add error message
        const $error = $('<div class="upload-error">' + message + '</div>');
        $uploadBox.append($error);
        
        // Auto-remove after 5 seconds
        setTimeout(function() {
            $error.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    }

    // Check if the device is mobile
    function isMobileDevice() {
        return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    }

    // Check if getUserMedia is supported
    function hasGetUserMedia() {
        return !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia);
    }

    // Initialize when document is ready
    $(document).ready(function() {
        initEnhancedImageUploads();
        
        // Also initialize whenever new items are added
        $(document).on('itemAdded', function(e, itemId) {
            setTimeout(function() {
                initEnhancedImageUploads();
            }, 100); // Small delay to ensure DOM is updated
        });
    });

})(jQuery);
