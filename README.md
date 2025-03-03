# Preowned Clothing Form Plugin

A WordPress plugin to create a form for submitting pre-owned clothing items.

## Installation

1. Upload the plugin files to the `/wp-content/plugins/clothing_form` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Place the shortcode `[preowned_clothing_form]` in any page or post where you want the form to appear

## Features

- Multi-level category selection for clothing items
- Size options that change dynamically based on category selection
- Image upload capability (up to 3 images)
- Form validation on both client and server sides
- Responsive design

## Shortcodes

- `[preowned_clothing_form]` - Displays the clothing submission form

## Database

The plugin creates a custom database table `wp_preowned_clothing_submissions` to store all form submissions.
