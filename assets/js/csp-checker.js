/**
 * CSP Diagnostic Script
 * 
 * Runs on init to check if Content Security Policy is blocking ES6+ features
 */
(function() {
    try {
        // Try various ES6+ features
        const arrowFunction = () => true;
        const template = `test`;
        const spreadTest = {...{test: true}};
        const asyncTest = async () => await Promise.resolve();
        
        // If we get here, ES6+ is working
        console.log('CSP Check: Modern JavaScript is working correctly');
        
        // Safely add a flag to body for CSS checks
        document.addEventListener('DOMContentLoaded', function() {
            // Make sure document.body exists before trying to use it
            if (document.body) {
                document.body.classList.add('es6-supported');
            }
        });
    } catch (e) {
        // Log the error for debugging
        console.error('CSP Check: Modern JavaScript error:', e.message);
        
        // Create visible error only in debug mode and when DOM is ready
        document.addEventListener('DOMContentLoaded', function() {
            // Check if we're in debug mode
            const isDebugMode = window.WP_DEBUG || 
                               document.body.classList.contains('debug-mode') || 
                               location.search.indexOf('debug=1') > -1;
            
            if (isDebugMode) {
                // Only proceed if document.body exists
                if (!document.body) return;
                
                const errorDiv = document.createElement('div');
                errorDiv.style.cssText = 'padding:15px;background:#f8d7da;color:#721c24;border:1px solid #f5c6cb;margin:15px 0;display:none;';
                errorDiv.innerHTML = `<strong>JavaScript CSP Error:</strong> ${e.message}`;
                
                // Insert after our form if it exists, otherwise at top of body
                const form = document.querySelector('.clothing-submission-form');
                if (form && form.parentNode) {
                    form.parentNode.insertBefore(errorDiv, form.nextSibling);
                } else if (document.body.firstChild) {
                    document.body.insertBefore(errorDiv, document.body.firstChild);
                } else {
                    document.body.appendChild(errorDiv);
                }
                
                // Only show for admins if we can detect them
                try {
                    if (typeof wp !== 'undefined' && wp.currentUser && wp.currentUser.capabilities && wp.currentUser.capabilities.manage_options) {
                        errorDiv.style.display = 'block';
                    } else {
                        // Fallback for showing to all users in debug mode
                        errorDiv.style.display = 'block';
                    }
                } catch (err) {
                    // If we can't check capabilities, show to everyone in debug mode
                    errorDiv.style.display = 'block';
                }
            }
        });
    }
})();
