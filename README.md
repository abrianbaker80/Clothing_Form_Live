# Preowned Clothing Form

A WordPress plugin to create a form for submitting pre-owned clothing items. This plugin allows users to submit clothing items for consignment or resale, complete with detailed categorization, sizing, and image uploads.

## Features

- **Dynamic Category Selection**: Multi-level category selection system with automatic subcategory loading
- **Smart Size Options**: Size selections that dynamically update based on the selected gender and clothing category
- **Image Upload System**: Support for multiple image uploads with front, back, and detail views
- **Multi-item Support**: Submit multiple clothing items in a single form submission
- **Wizard Interface**: Step-by-step form interface that guides users through the submission process
- **Form Validation**: Client-side and server-side validation to ensure complete and accurate submissions
- **Responsive Design**: Mobile-friendly interface that works on all device sizes
- **Admin Dashboard**: Dedicated admin interface to manage and review submissions

## Installation

1. Upload the plugin files to the `/wp-content/plugins/clothing_form` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure the plugin settings under 'Settings > Preowned Clothing Form'
4. Place the shortcode `[preowned_clothing_form]` in any page or post where you want the form to appear

## Shortcodes

- `[preowned_clothing_form]` - Displays the clothing submission form

## Configuration Options

Navigate to 'Settings > Preowned Clothing Form' to customize the following options:

- **Form Title**: Customize the main form heading
- **Form Introduction**: Add custom instructions or welcome text
- **Max Items**: Set the maximum number of clothing items a user can submit at once
- **Image Upload Requirements**: Specify which images are required (front, back, brand tag, etc.)
- **Colors and Styling**: Customize the form's appearance to match your site's theme
- **Email Notifications**: Configure who receives notifications of new submissions

## Troubleshooting

### Category Selection Issues

If categories aren't displaying properly:

1. Check that the clothing-categories.php file exists and has the correct format
2. Enable debug mode to see diagnostics (add `define('WP_DEBUG', true);` to wp-config.php)
3. Check the browser console for JavaScript errors
4. Try refreshing the form with cache clearing (Ctrl+F5)

### Size Selection Issues

If size options aren't updating based on categories:

1. Ensure the clothing-sizes.php file exists and is properly formatted
2. Check that JavaScript is enabled in the user's browser
3. Verify the size data is being properly passed to JavaScript (check browser console)

## Developer Information

### File Structure

- `preowned-clothing-form.php` - Main plugin file
- `includes/clothing-categories.php` - Category data structure
- `includes/clothing-sizes.php` - Size data structure  
- `includes/form-display.php` - Form display handler
- `includes/form/form-renderer.php` - Form rendering class
- `assets/js/category-handler.js` - JavaScript for category and size selection

### Adding Custom Sizes or Categories

To add your own categories or sizes:

1. Edit the `includes/clothing-categories.php` file to add new categories or subcategories
2. Edit the `includes/clothing-sizes.php` file to add new size options
3. The format follows a hierarchical structure where each category can have subcategories

### Extending the Plugin

The plugin is designed to be extensible. You can:

1. Add new fields by modifying the form-renderer.php file
2. Add custom validation by extending the form-validation.php file
3. Create custom theme styling by adding CSS to your theme

## Database

The plugin creates a custom database table `wp_preowned_clothing_submissions` to store all form submissions.

## Changelog

### Version 2.5.7
- Fixed size selector to update based on selected category
- Added clothing-sizes.php to store size data by gender and category
- Enhanced debugging tools for troubleshooting

### Version 2.5.6
- Fixed category and subcategory display issues
- Improved error checking and debugging information
- Added fallback for missing category data

### Version 2.5.5
- Initial public release

## Project Structure

- `/assets` - Frontend assets (CSS, JS, images)
- `/includes` - Core PHP functionality
  - `/admin` - Admin-specific functionality
  - `/form` - Form handling functionality
  - `/github-updater` - Plugin update system
- `preowned-clothing-form.php` - Main plugin file

## Development

### Key Files
- `preowned-clothing-form.php` - Plugin entry point
- `includes/form/form-renderer.php` - Main form rendering logic
- `assets/js/item-management.js` - Form item management
- `includes/github-updater` - Plugin update system

## Version
Current version: 2.8.1.1
