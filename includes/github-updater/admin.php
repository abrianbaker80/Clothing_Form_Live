// ...existing code...

// Update capability in menu registration
add_action('admin_menu', function() {
    add_submenu_page(
        'options-general.php',
        'GitHub Updater',
        'GitHub Updater',
        'manage_options', // Changed to this standard capability
        'preowned-clothing-github-updater',
        'preowned_clothing_display_github_updater_page'
    );
});

// ...existing code...