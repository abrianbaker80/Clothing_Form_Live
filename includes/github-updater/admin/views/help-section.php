<?php
/**
 * GitHub Updater Help Section Template
 * 
 * @package PreownedClothingForm
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get passed variables
$username = isset($username) ? $username : '';
$repository = isset($repository) ? $repository : '';
?>
<div class="github-updater-help">
    <h2>GitHub Release Guide</h2>
    
    <div class="help-section">
        <h3>Creating a New Release on GitHub</h3>
        <ol>
            <li>Go to your GitHub repository</li>
            <li>Click on "Releases" in the right sidebar</li>
            <li>Click the "Create a new release" button</li>
            <li>Enter a version tag (e.g., "v2.5.9")</li>
            <li>Enter a title for the release (e.g., "Version 2.5.9")</li>
            <li>Add release notes in the description field</li>
            <li>Click "Publish release"</li>
        </ol>
        
        <p><strong>Note:</strong> The version tag (without the "v" prefix) should match the version number in your plugin's main PHP file.</p>
    </div>
    
    <div class="help-section">
        <h3>Version Naming</h3>
        <p>WordPress uses semantic versioning for plugins:</p>
        <ul>
            <li><strong>Major.Minor.Patch</strong> (e.g., 2.5.9)</li>
            <li><strong>Major:</strong> Big changes that might break compatibility</li>
            <li><strong>Minor:</strong> New features that are backward compatible</li>
            <li><strong>Patch:</strong> Bug fixes and small improvements</li>
        </ul>
    </div>
    
    <div class="help-section">
        <h3>Quick Links</h3>
        <?php if (!empty($username) && !empty($repository)) : ?>
        <ul>
            <li><a href="https://github.com/<?php echo esc_attr($username); ?>/<?php echo esc_attr($repository); ?>/releases/new" target="_blank">Create a New Release</a></li>
            <li><a href="https://github.com/<?php echo esc_attr($username); ?>/<?php echo esc_attr($repository); ?>/releases" target="_blank">View All Releases</a></li>
            <li><a href="https://github.com/<?php echo esc_attr($username); ?>/<?php echo esc_attr($repository); ?>/tags" target="_blank">View Tags</a></li>
        </ul>
        <?php else : ?>
        <p><em>Configure your repository settings first to see quick links.</em></p>
        <?php endif; ?>
    </div>
</div>
<style>
    .github-updater-help {
        background: #fff;
        padding: 15px;
        margin-top: 20px;
        border: 1px solid #ddd;
        border-left: 4px solid #00a0d2;
    }
    .help-section {
        margin-bottom: 20px;
    }
    .help-section h3 {
        margin-top: 15px;
        margin-bottom: 5px;
        color: #0073aa;
    }
</style>
