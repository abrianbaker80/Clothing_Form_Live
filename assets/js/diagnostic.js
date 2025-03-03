/**
 * Diagnostic script to help troubleshoot categories and form issues
 */
jQuery(document).ready(function($) {
    console.log('PCF Diagnostic script loaded');
    
    // Function to add a debug panel for admins
    function addDebugPanel() {
        // Only add for admin users
        if (!$('.debug-info').length) return;
        
        const $debugPanel = $('<div id="pcf-debug-panel" style="position:fixed;bottom:0;right:0;background:#fff;border:1px solid #ccc;padding:10px;z-index:9999;max-width:400px;max-height:50vh;overflow:auto;box-shadow:0 0 10px rgba(0,0,0,0.2);"></div>');
        
        $debugPanel.html(`
            <h4>PCF Debug Panel</h4>
            <button id="pcf-test-categories" class="button">Test Categories</button>
            <button id="pcf-check-form-renderer" class="button">Check Form Renderer</button>
            <button id="pcf-force-refresh" class="button">Force Refresh</button>
            <div id="pcf-debug-output" style="margin-top:10px;padding:10px;background:#f5f5f5;font-family:monospace;font-size:12px;"></div>
        `);
        
        $('body').append($debugPanel);
        
        // Test categories button
        $('#pcf-test-categories').on('click', function() {
            $('#pcf-debug-output').html('Testing categories...');
            
            // Make AJAX request to debug categories
            $.ajax({
                url: pcfFormOptions.ajax_url,
                type: 'POST',
                data: {
                    action: 'pcf_debug_categories',
                    nonce: pcfFormOptions.nonce
                },
                success: function(response) {
                    let output = '<h5>Categories Debug Result:</h5>';
                    output += '<p>Categories loaded: ' + (response.categories_loaded ? 'Yes' : 'No') + '</p>';
                    output += '<p>Categories count: ' + response.categories_count + '</p>';
                    output += '<p>Categories file: ' + response.categories_file_path + '</p>';
                    output += '<p>File exists: ' + (response.file_exists ? 'Yes' : 'No') + '</p>';
                    
                    if (response.categories_count > 0) {
                        output += '<p>Available categories:</p><ul>';
                        for (let key in response.categories) {
                            output += '<li>' + key + ': ' + response.categories[key].name + '</li>';
                        }
                        output += '</ul>';
                    }
                    
                    $('#pcf-debug-output').html(output);
                },
                error: function(xhr, status, error) {
                    $('#pcf-debug-output').html('<p style="color:red">Error: ' + error + '</p>');
                }
            });
        });
        
        // Check form renderer
        $('#pcf-check-form-renderer').on('click', function() {
            $('#pcf-debug-output').html('Checking form renderer...');
            
            let output = '<h5>Form Renderer Check:</h5>';
            // Check if categories exist in JavaScript
            output += '<p>pcfFormOptions exists: ' + (typeof pcfFormOptions !== 'undefined' ? 'Yes' : 'No') + '</p>';
            
            if (typeof pcfFormOptions !== 'undefined') {
                output += '<p>Categories in pcfFormOptions: ' + (typeof pcfFormOptions.categories !== 'undefined' ? 'Yes' : 'No') + '</p>';
                
                if (typeof pcfFormOptions.categories !== 'undefined') {
                    const categoryCount = Object.keys(pcfFormOptions.categories || {}).length;
                    output += '<p>Category count: ' + categoryCount + '</p>';
                    
                    if (categoryCount > 0) {
                        output += '<p>First category: ' + Object.keys(pcfFormOptions.categories)[0] + '</p>';
                    }
                }
            }
            
            // Check if form renderer elements exist in the DOM
            output += '<p>Category form elements found: ' + ($('.category-selection-container').length > 0 ? 'Yes' : 'No') + '</p>';
            output += '<p>Main category selects found: ' + ($('.main-category').length > 0 ? 'Yes' : 'No') + '</p>';
            output += '<p>Subcategory containers found: ' + ($('.subcategory-container').length > 0 ? 'Yes' : 'No') + '</p>';
            
            $('#pcf-debug-output').html(output);
        });
        
        // Force refresh button
        $('#pcf-force-refresh').on('click', function() {
            $('#pcf-debug-output').html('Forcing refresh...');
            
            // This will reload the page while bypassing the cache
            window.location.href = window.location.href + (window.location.href.indexOf('?') > -1 ? '&' : '?') + 'nocache=' + new Date().getTime();
        });
    }
    
    // Only run for admin users who can see the debug info
    if ($('.debug-info').length) {
        addDebugPanel();
    }
    
    // Check for any DOM changes that might add debug-info later
    const observer = new MutationObserver(function(mutations) {
        if ($('.debug-info').length && !$('#pcf-debug-panel').length) {
            addDebugPanel();
        }
    });
    
    // Start observing the document body for changes
    observer.observe(document.body, { childList: true, subtree: true });
});
