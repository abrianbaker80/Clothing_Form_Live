<?php
/**
 * GitHub Updater Troubleshooting Template
 * 
 * @package PreownedClothingForm
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="github-updater-troubleshooting">
    <h2>Troubleshooting</h2>
    
    <div class="troubleshooting-section">
        <h3>Common Issues</h3>
        <ul>
            <li><strong>404 Not Found</strong>: The repository or releases page was not found. Check the username and repository name - they are case sensitive.</li>
            <li><strong>401 Unauthorized</strong>: Your token doesn't have the right permissions.</li>
            <li><strong>No releases found</strong>: You need to create <a href="https://docs.github.com/en/repositories/releasing-projects-on-github/managing-releases-in-a-repository" target="_blank">at least one release</a> on GitHub.</li>
            <li><strong>Updates not showing</strong>: Make sure the version in your plugin's main PHP file is lower than the GitHub release tag.</li>
        </ul>
    </div>
    
    <div class="troubleshooting-section">
        <h3>Release Requirements</h3>
        <p>For the updater to work properly:</p>
        <ul>
            <li>Your GitHub repository must have at least one published release</li>
            <li>Release tags should follow semantic versioning (e.g., "1.2.3" or "v1.2.3")</li>
            <li>The repository should contain a valid WordPress plugin with the same version number in its main file</li>
        </ul>
    </div>
    
    <div class="troubleshooting-section">
        <h3>Helpful Resources</h3>
        <p>
            <a href="https://docs.github.com/en/repositories/releasing-projects-on-github/managing-releases-in-a-repository" target="_blank" class="button button-secondary">GitHub Releases Documentation</a>
            <a href="https://github.com/settings/tokens" target="_blank" class="button button-secondary">Manage GitHub Tokens</a>
        </p>
    </div>
</div>

<style>
    .github-updater-troubleshooting {
        background: #fff;
        padding: 15px;
        margin-top: 20px;
        border: 1px solid #ddd;
        border-left: 4px solid #46b450;
    }
    .troubleshooting-section {
        margin-bottom: 20px;
    }
    .troubleshooting-section h3 {
        margin-top: 15px;
        margin-bottom: 5px;
        color: #0073aa;
    }
</style>
