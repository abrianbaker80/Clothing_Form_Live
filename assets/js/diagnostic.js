/**
 * Form Diagnostic Script
 * Helps identify issues with the clothing form
 */
(function() {
    'use strict';
    
    // Wait for DOM to be ready
    document.addEventListener('DOMContentLoaded', function() {
        console.log('PCF Diagnostic: DOM ready, checking form elements...');
        
        // Check if form exists
        const form = document.getElementById('clothing-form');
        if (form) {
            console.log('PCF Diagnostic: Found clothing form', form);
            
            // Check wizard steps
            const wizardSteps = document.querySelectorAll('.wizard-step');
            console.log(`PCF Diagnostic: Found ${wizardSteps.length} wizard steps`);
            
            // Check if the first step has the required fields
            const nameField = document.getElementById('name');
            const emailField = document.getElementById('email');
            const phoneField = document.getElementById('phone');
            const addressField = document.getElementById('address');
            
            if (nameField && emailField && phoneField && addressField) {
                console.log('PCF Diagnostic: Contact info fields found');
            } else {
                console.error('PCF Diagnostic: Missing contact info fields', {
                    name: !!nameField,
                    email: !!emailField,
                    phone: !!phoneField,
                    address: !!addressField
                });
            }
            
            // Check if gender select exists
            const genderSelect = document.getElementById('gender-1');
            if (genderSelect) {
                console.log('PCF Diagnostic: Gender select found');
            } else {
                console.error('PCF Diagnostic: Gender select not found');
            }
            
            // Check category container
            const categoryContainer = document.getElementById('category-select-container-1');
            if (categoryContainer) {
                console.log('PCF Diagnostic: Category container found');
            } else {
                console.error('PCF Diagnostic: Category container not found');
            }
            
            // Check for image upload boxes
            const imageBoxes = document.querySelectorAll('.image-upload-box');
            console.log(`PCF Diagnostic: Found ${imageBoxes.length} image upload boxes`);
            
            // See if the form scripts are loaded
            if (typeof jQuery !== 'undefined') {
                console.log('PCF Diagnostic: jQuery is loaded');
                
                // Check if our scripts are loaded
                if (window.pcfImageUpload) {
                    console.log('PCF Diagnostic: Image upload script is loaded');
                } else {
                    console.error('PCF Diagnostic: Image upload script is NOT loaded');
                }
                
                if (window.pcfFormOptions) {
                    console.log('PCF Diagnostic: Form options are loaded', window.pcfFormOptions);
                } else {
                    console.error('PCF Diagnostic: Form options are NOT loaded');
                }
            }
            
        } else {
            console.error('PCF Diagnostic: Clothing form NOT found');
        }
    });
})();
