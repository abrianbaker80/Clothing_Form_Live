<?php

/**
 * Directory Map for the Preowned Clothing Form plugin
 * Version: 1.1.0
 * 
 * This file provides a comprehensive map of the plugin's directory structure
 * and documents the purpose of each component.
 */

// Main directories with descriptions
$directories = [
    // Core asset directories
    'assets/' => 'Frontend assets (CSS, JS, images)',
    'assets/css/' => 'Stylesheet files including modern theme and form controls',
    'assets/js/' => 'JavaScript functionality including form validation and image handling',
    'assets/js/vendor/' => 'Third-party JavaScript libraries',
    'assets/images/' => 'Image assets and SVG placeholders',
    'assets/icons/' => 'UI icons and visual indicators',

    // Core functionality directories
    'includes/' => 'Core PHP functionality and main classes',
    'includes/form/' => 'Form processing and rendering components',
    'includes/admin/' => 'WordPress admin interface components',
    'includes/github-updater/' => 'Automatic update system via GitHub',
    'includes/security/' => 'Security features and protections',
    'includes/templates/' => 'Email and form templates',
    'includes/api/' => 'API endpoints and integrations',
    'includes/utilities/' => 'Helper functions and utility classes',

    // Data management
    'data/' => 'Configuration and data files',
    'data/cache/' => 'Cached data and temporary files',
    'data/logs/' => 'Debug and error logs',
];

// Critical files with descriptions
$key_files = [
    // Core plugin files
    'preowned-clothing-form.php' => 'Main plugin file and entry point',
    'uninstall.php' => 'Clean up on plugin removal',
    'debug-form.php' => 'Debugging tools and diagnostics',
    'CHANGELOG.md' => 'Version history and updates',
    'README.md' => 'Plugin documentation and setup guide',

    // Include files
    'includes/utilities.php' => 'Common utility functions',
    'includes/form/form-renderer.php' => 'Main form rendering class',
    'includes/form/validation.php' => 'Form data validation',
    'includes/form/session-manager.php' => 'Form session handling',
    'includes/form/image-uploader.php' => 'Image upload processing',
    'includes/form/database.php' => 'Database operations',
    'includes/form-display.php' => 'Form display logic',
    'includes/form-submission-handler.php' => 'Form submission processing',
    'includes/advanced-security.php' => 'Security enhancements',
    'includes/performance-enhancements.php' => 'Performance optimizations',
    'includes/email-notifications.php' => 'Email handling system',

    // Admin components
    'includes/admin/settings.php' => 'Plugin settings page',
    'includes/admin/category-manager.php' => 'Category management interface',
    'includes/admin/size-manager.php' => 'Size management system',
    'includes/admin/form-field-manager.php' => 'Form field customization',
    'includes/admin-submissions.php' => 'Submission management interface',
    'includes/admin-image-test.php' => 'Image upload testing tool',
    'includes/dashboard-widget.php' => 'WordPress dashboard widget',

    // GitHub updater
    'includes/github-updater/loader.php' => 'GitHub updater initialization',
    'includes/github-updater.php' => 'Legacy update system',
    'includes/github-updater-admin.php' => 'Update system admin interface',

    // JavaScript components
    'assets/js/script.js' => 'Main JavaScript functionality',
    'assets/js/wizard-interface.js' => 'Multi-step form wizard',
    'assets/js/category-handler.js' => 'Category selection logic',
    'assets/js/image-upload.js' => 'Image upload interface',
    'assets/js/form-validation.js' => 'Client-side validation',
    'assets/js/form-storage.js' => 'Form data persistence',
    'assets/js/item-management.js' => 'Form item management',
    'assets/js/form-autosave.js' => 'Autosave functionality',
    'assets/js/keyboard-accessibility.js' => 'Accessibility enhancements',

    // Stylesheets
    'assets/css/style.css' => 'Main stylesheet',
    'assets/css/modern-theme.css' => 'Modern UI theme',
    'assets/css/form-controls.css' => 'Form control styles',
    'assets/css/wizard-interface.css' => 'Wizard UI styles',
    'assets/css/category-selection.css' => 'Category UI styles',
    'assets/css/real-time-feedback.css' => 'Interactive feedback styles',
    'assets/css/admin-style.css' => 'Admin interface styles',
];

// Data files
$data_files = [
    'data/clothing-categories.php' => 'Category configuration',
    'data/clothing-sizes.php' => 'Size configuration',
    'data/form-fields.php' => 'Form field configuration',
];
