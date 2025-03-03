# Troubleshooting Guide

This guide will help you resolve common issues with the Preowned Clothing Form plugin.

## Categories and Subcategories Not Displaying

### Check Basic Setup
1. Verify the plugin is correctly activated
2. Check that you're using the correct shortcode: `[preowned_clothing_form]`
3. Clear your browser cache and refresh the page

### Check Files and Data
1. Verify that `includes/clothing-categories.php` exists
2. Check that the file contains properly formatted PHP array data
3. Look for PHP syntax errors in the file

### Enable Debugging
Add this to your wp-config.php to enable detailed logging:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Then check the debug.log file in your wp-content directory for error messages.

### JavaScript Issues
1. Open your browser's developer tools (F12)
2. Look for JavaScript errors in the console
3. Verify that the `pcfFormOptions` JavaScript object is loaded properly
4. Check that it contains category data

### Advanced Debugging
If you're logged in as an administrator, the plugin will display a debug panel. Use these tools:
1. Click "Test Categories" to check if categories are loaded
2. Click "Check Form Renderer" to verify the form is constructed correctly
3. Click "Force Refresh" to reload the page bypassing cache

## Size Options Not Updating

### Common Causes
1. Missing or incorrectly formatted `clothing-sizes.php` file
2. JavaScript errors preventing the size update function from running
3. Missing DOM elements (the size select field might not be found)

### Solutions
1. Check that `includes/clothing-sizes.php` exists and has proper data
2. Verify no JavaScript errors in browser console
3. Make sure size selectors have IDs matching the pattern `size-X` where X is the item index
4. Check that gender and category selectors are changing values correctly

### Manual Testing
To test if size selector JS functions work:
1. Open browser console
2. Run: `window.updateSizeOptions('womens', 'tops', null, 1)`
3. This should update the size dropdown for item #1 with women's tops sizes

## Image Upload Issues

### Common Problems
1. Files too large (check max upload size in PHP settings)
2. Missing permissions on server upload directory
3. JavaScript errors preventing upload

### Solutions
1. Check PHP settings for upload limits:
   - `post_max_size`
   - `upload_max_filesize`
   - `max_execution_time`
2. Verify your hosting allows file uploads
3. Check server directory permissions (755 for directories, 644 for files)
4. Try smaller image files as a test

## Form Submission Failures

### Common Causes
1. Server validation failures
2. PHP errors in form processing
3. Database connection issues
4. Issues with file uploads

### Debug Steps
1. Check PHP error logs
2. Enable WP_DEBUG and check for errors
3. Try submitting with minimal data to isolate the issue
4. Check that your database table exists and has the correct structure

### Database Issues
Verify the database table was created:
```sql
SHOW TABLES LIKE '%preowned_clothing_submissions%';
```

Check its structure:
```sql
DESCRIBE wp_preowned_clothing_submissions;
```

## Plugin Conflicts

The plugin may conflict with:
1. Form builders or other form plugins
2. Security plugins that restrict AJAX or form submissions
3. Caching plugins that cache dynamic content

### Testing for Conflicts
1. Temporarily deactivate all other plugins
2. Switch to a default WordPress theme
3. Test if the issue persists
4. Re-activate plugins one by one to identify the conflict

## Getting Support

If you've tried the troubleshooting steps and still have issues:

1. Gather information:
   - WordPress version
   - Plugin version
   - Theme name and version
   - List of active plugins
   - Error messages from debug logs
   - Screenshots of the issue

2. Contact support through one of these channels:
   - GitHub issues: https://github.com/abrianbaker80/Clothing_Form/issues
   - Plugin support forum
   - Direct email to plugin author
