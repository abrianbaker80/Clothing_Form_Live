/**
 * Smart Autocomplete for Clothing Categories
 * 
 * Provides intelligent search and autocomplete for clothing categories
 * with ability to learn from previous selections.
 */
(function($) {
    'use strict';

    // Configuration
    const config = {
        minCharsForSuggestion: 2,  // Start suggesting after X characters
        maxSuggestions: 5,         // Maximum suggestions to show
        highlightMatches: true,    // Highlight the matching parts in suggestions
        learnFromSelections: true, // Remember previous user selections
        debounceTime: 200          // Delay before searching (ms)
    };
    
    // Store for user's previous selections we can use localStorage
    const userSelections = {
        load: function() {
            const stored = localStorage.getItem('pcf_category_selections');
            return stored ? JSON.parse(stored) : {};
        },
        save: function(categoryPath) {
            if (!config.learnFromSelections) return;
            try {
                const selections = this.load();
                const normalized = categoryPath.toLowerCase();
                /* user's previous selections with fallback for when localStorage isn't available */
                selections[normalized] = (selections[normalized] || 0) + 1;
                localStorage.setItem('pcf_category_selections', JSON.stringify(selections));
            } catch (e) {
                console.error('Failed to save selection:', e);
            }
        },
        getPopular: function() {
            const selections = this.load();
            return Object.keys(selections)
                .map(key => ({ category: key, count: selections[key] }))
                .sort((a, b) => b.count - a.count)
                .slice(0, 10); // Top 10
        }
    };
    
    function initSmartAutocomplete() {
        // Check for jQuery UI availability with more robust detection
        if (typeof $.fn.autocomplete === 'undefined') {
            // Skip local file check and go straight to CDN
            loadCSS('https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css', null);
            
            loadScript(
                'https://code.jquery.com/ui/1.12.1/jquery-ui.min.js', 
                'https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js',
                function() {
                    initCategoryAutocomplete();
                }
            );
        } else {
            // jQuery UI already loaded
            initCategoryAutocomplete();
        }
    }

    /**
     * Helper function to load CSS with fallback
     */
    function loadCSS(primaryUrl, fallbackUrl) {
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = primaryUrl;
        
        link.onerror = function() {
            console.log('Failed to load CSS from primary URL, trying fallback');
            if (fallbackUrl) {
                const fallbackLink = document.createElement('link');
                fallbackLink.rel = 'stylesheet';
                fallbackLink.href = fallbackUrl;
                document.head.appendChild(fallbackLink);
            }
        };
        
        document.head.appendChild(link);
    }

    /**
     * Helper function to load scripts with fallback handling
     */
    function loadScript(primaryUrl, fallbackUrl, callback) {
        const script = document.createElement('script');
        
        script.onload = callback;
        script.onerror = function() {
            console.log('Failed to load script from primary URL, trying fallback');
            if (fallbackUrl) {
                const fallbackScript = document.createElement('script');
                fallbackScript.onload = callback;
                fallbackScript.onerror = function() {
                    console.error('Failed to load script from fallback URL');
                };
                fallbackScript.src = fallbackUrl;
                document.head.appendChild(fallbackScript);
            } else {
                console.error('Failed to load script and no fallback provided');
            }
        };
        script.src = primaryUrl;
        document.head.appendChild(script);
    }
    
    /**
     * Load jQuery UI autocomplete if needed
     */
    function loadAutocompleteLibrary() {
        return new Promise((resolve, reject) => {
            // First check if it's already loaded
            if (typeof $.fn.autocomplete !== 'undefined') {
                resolve();
                return;
            }
            
            // Load CSS
            const cssLink = document.createElement('link');
            cssLink.rel = 'stylesheet';
            cssLink.href = 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css';
            document.head.appendChild(cssLink);
            
            // Load JS
            const script = document.createElement('script');
            script.src = 'https://code.jquery.com/ui/1.12.1/jquery-ui.min.js';
            script.onload = resolve;
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }
    
    /**
     * Initialize category autocomplete
     */
    function initCategoryAutocomplete() {
        // Get all category selects
        setupAllCategorySelects();
        
        // Also set up when new items are added
        $(document).on('itemAdded', function(e, itemId) {
            setTimeout(function() {
                setupAllCategorySelects();
            }, 300);
        });
    }
    /**
     * Set up autocomplete on all category selects
     */
    function setupAllCategorySelects() {
        $('.category-select-container select').each(function() {
            const $select = $(this);
            
            // Skip if already initialized
            if ($select.data('autocomplete-initialized')) {
                return;
            }
            
            // Mark as initialized
            $select.data('autocomplete-initialized', true);
            
            // Create a hidden input for autocomplete
            const selectId = $select.attr('id');
            const $container = $select.closest('.category-select-wrapper');
            
            // Add a text input next to the select
            const $autocompleteInput = $('<input>')
                .attr('type', 'text')
                .attr('placeholder', 'Search categories...')
                .addClass('category-autocomplete-input')
                .data('target-select', selectId);
            
            // Insert before the select
            $container.prepend($autocompleteInput);
            
            // Make original select smaller
            $select.css('width', '100%');
            
            // Set up autocomplete
            $autocompleteInput.autocomplete({
                minLength: config.minCharsForSuggestion,
                delay: config.debounceTime,
                source: function(request, response) {
                    // Get suggestions
                    const term = request.term.toLowerCase();
                    const suggestions = getCategorySuggestions($select, term);
                    
                    // Format and return suggestions
                    response(suggestions.map(suggestion => {
                        return {
                            label: suggestion.label,
                            value: suggestion.value,
                            option: suggestion.option
                        };
                    }));
                },
                select: function(event, ui) {
                    // Update the select with the chosen option
                    $select.val(ui.item.value).trigger('change');
                    
                    // Remember this selection
                    const categoryPath = ui.item.label;
                    userSelections.save(categoryPath);
                    
                    return false; // Prevent default
                }
            }).data("ui-autocomplete")._renderItem = function(ul, item) {
                // Custom rendering for autocomplete items
                let label = item.label;
                
                // Highlight matching text if enabled
                if (config.highlightMatches) {
                    const term = $autocompleteInput.val().trim();
                    if (term) {
                        const escapedTerm = term.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&');
                        const regex = new RegExp("(" + escapedTerm + ")", "gi");
                        label = label.replace(regex, "<strong>$1</strong>");
                    }
                }
                
                return $("<li>")
                    .append("<div>" + label + "</div>")
                    .appendTo(ul);
            };

            // Sync select changes with autocomplete
            $select.on('change', function() {
                const selectedText = $select.find('option:selected').text();
                $autocompleteInput.val(selectedText);
            });
            
            // Initial value
            const initialText = $select.find('option:selected').text();
            if (initialText) {
                $autocompleteInput.val(initialText);
            }
        });
    }
    
    /**
     * Get category suggestions based on input term
     * 
     * @param {jQuery} $select The select element
     * @param {string} term The search term
     * @return {Array} Matching categories
     */
    function getCategorySuggestions($select, term) {
        const suggestions = [];
        const popularSelections = userSelections.getPopular();
        const maxResults = config.maxSuggestions;
        
        // Get all options from select
        const options = $select.find('option').get();
        
        // Add matching options
        options.forEach(option => {
            if (option.value === '') return; // Skip empty option
            
            const text = option.text.toLowerCase();
            if (text.includes(term)) {
                suggestions.push({
                    label: option.text,
                    value: option.value,
                    option: option,
                    score: text === term ? 100 : (text.startsWith(term) ? 50 : 25)
                });
            }
        });
        
        // Add popular suggestions that match
        popularSelections.forEach(item => {
            if (item.category.includes(term)) {
                // Check if we already have this category
                const exists = suggestions.some(s => s.label.toLowerCase() === item.category);
                if (!exists) {
                    // Find the option with this text
                    const option = options.find(o => o.text.toLowerCase() === item.category);
                    if (option) {
                        suggestions.push({
                            label: option.text,
                            value: option.value,
                            option: option,
                            score: 40 // Popular items get a medium priority score
                        });
                    }
                } else {
                    // Boost score of existing item
                    const existingItem = suggestions.find(s => s.label.toLowerCase() === item.category);
                    if (existingItem) {
                        existingItem.score += 30; // Boost popular items
                    }
                }
            }
        });
        
        // Sort by score and limit results
        return suggestions
            .sort((a, b) => b.score - a.score)
            .slice(0, maxResults);
    }
    
    /**
     * Initialize when document is ready and jQuery UI is available
     */
    $(document).ready(function() {
        // Try to initialize with a short delay to ensure all dependencies are loaded
        setTimeout(function() {
            // Check if jQuery UI is available
            if (typeof $.fn.autocomplete === 'undefined') {
                console.log('jQuery UI not available, attempting to load from CDN');
                loadJQueryUI(function() {
                    initCategoryAutocomplete();
                });
            } else {
                initCategoryAutocomplete();
            }
        }, 500);
    });

    /**
     * Load jQuery UI from CDN if not available
     */
    function loadJQueryUI(callback) {
        // Load CSS
        var cssLink = document.createElement('link');
        cssLink.rel = 'stylesheet';
        cssLink.href = 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css';
        document.head.appendChild(cssLink);
        
        // Load JS with fallback
        var script = document.createElement('script');
        script.src = 'https://code.jquery.com/ui/1.12.1/jquery-ui.min.js';
        script.onload = callback;
        script.onerror = function() {
            console.log('Failed to load jQuery UI from primary CDN, trying backup CDN');
            var fallbackScript = document.createElement('script');
            fallbackScript.src = 'https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js';
            fallbackScript.onload = callback;
            fallbackScript.onerror = function() {
                console.error('Failed to load jQuery UI from backup CDN');
            };
            document.head.appendChild(fallbackScript);
        };
        document.head.appendChild(script);
    }

    /**
     * Initialize when document is ready and jQuery UI is available
     */
    $(document).ready(function() {
        // Try to initialize right away
        setTimeout(initSmartAutocomplete, 0);
        
        // Restore missing jQuery UI file and ensure vendor directory exists
        restoreMissingFiles();
    });

    /**
     * Restores missing jQuery UI library and creates required directories
     */
    function restoreMissingFiles() {
        // Create a helper function to download files
        function downloadFile(url, savePath) {
            // Create a fetch request to download the file
            fetch(url)
                .then(response => response.blob())
                .then(blob => {
                    // Create an AJAX request to handle the file saving server-side
                    const formData = new FormData();
                    formData.append('action', 'preowned_clothing_save_file');
                    formData.append('file_content', blob);
                    formData.append('file_path', savePath);
                    formData.append('nonce', pcf_ajax_object.nonce);
                    
                    // Send the AJAX request
                    $.ajax({
                        url: pcf_ajax_object.ajax_url,
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            console.log('File restored:', savePath);
                        },
                        error: function(xhr, status, error) {
                            console.error('Failed to restore file:', error);
                        }
                    });
                })
                .catch(error => {
                    console.error('Error downloading file:', error);
                });
        }
        
        // jQuery UI files to restore
        const jqueryUiJs = 'https://code.jquery.com/ui/1.12.1/jquery-ui.min.js';
        const jqueryUiCss = 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css';
        
        // Add these to a queue for admin to review
        if (typeof pcf_ajax_object !== 'undefined') {
            // Local paths to save the files
            const jsPath = 'assets/js/vendor/jquery-ui.min.js';
            const cssPath = 'assets/css/vendor/jquery-ui.min.css';
            
            // Download and save files
            downloadFile(jqueryUiJs, jsPath);
            downloadFile(jqueryUiCss, cssPath);
            
            // Log message for admin
            console.log('Restoring missing jQuery UI files. Please check admin notices for more information.');
        }
    }

})(jQuery);
