<?php
/**
 * Settings page template for GitHub Updater
 *
 * @package PreownedClothingForm
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Variables passed to this template:
// $admin - Instance of the admin class
// $current_version - Current plugin version
// $github_version - Latest version from GitHub
// $update_available - Whether an update is available
// $release_info - Latest release data from GitHub

// Make sure we have a reference to the admin class via a global variable
global $preowned_clothing_github_admin;
$admin = is_a($preowned_clothing_github_admin, 'Preowned_Clothing_GitHub_Admin') ? $preowned_clothing_github_admin : null;
if (!$admin) {
    wp_die('Error: Admin class reference not available.');
}
?>
<div class="wrap github-updater-settings">
    <h1>GitHub Updater Settings</h1>

    <!-- Status Card -->
    <div class="github-updater-card">
        <h2>Update Status</h2>

        <!-- Add a troubleshooting section -->
        <?php if ($github_version === 'Unknown' || !$update_available): ?>
            <div class="github-updater-notice">
                <h4>Update Detection Issues?</h4>
                <p>If the updater isn't detecting available updates, try these steps:</p>
                <ol>
                    <li>Clear all caches with the button below</li>
                    <li>Verify your repository has releases with proper version tags</li>
                    <li>Check that the version in GitHub is higher than your current version</li>
                </ol>
                <a href="<?php echo esc_url(add_query_arg('preowned_clothing_clear_caches', '1')); ?>"
                    class="button button-secondary">Clear All GitHub Updater Caches</a>
            </div>
        <?php endif; ?>

        <table class="form-table">
            <tr>
                <th>Current Plugin Version:</th>
                <td><?php echo esc_html($current_version); ?></td>
            </tr>
            <tr>
                <th>Latest GitHub Version:</th>
                <td><?php echo esc_html($github_version); ?></td>
            </tr>
            <tr>
                <th>Update Available:</th>
                <td>
                    <?php if ($update_available): ?>
                        <span style="color: green; font-weight: bold;">Yes</span> -
                        <a href="<?php echo esc_url(admin_url('update-core.php')); ?>">Go to WordPress Updates</a>
                    <?php else: ?>
                        <span>No</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php
            $username = $admin->get_setting('username');
            $repository = $admin->get_setting('repository');
            if (!empty($username) && !empty($repository)):
                ?>
                <tr>
                    <th>Repository URL:</th>
                    <td>
                        <a href="https://github.com/<?php echo esc_attr($username); ?>/<?php echo esc_attr($repository); ?>"
                            target="_blank">
                            https://github.com/<?php echo esc_attr($username); ?>/<?php echo esc_attr($repository); ?>
                        </a>
                    </td>
                </tr>
                <tr>
                    <th>Releases URL:</th>
                    <td>
                        <a href="https://github.com/<?php echo esc_attr($username); ?>/<?php echo esc_attr($repository); ?>/releases"
                            target="_blank">
                            https://github.com/<?php echo esc_attr($username); ?>/<?php echo esc_attr($repository); ?>/releases
                        </a>
                    </td>
                </tr>
            <?php endif; ?>
            <?php if ($release_info && isset($release_info['published_at'])): ?>
                <tr>
                    <th>Last Release Date:</th>
                    <td><?php echo esc_html(date('F j, Y', strtotime($release_info['published_at']))); ?></td>
                </tr>
            <?php endif; ?>
            <tr>
                <th>Last Check:</th>
                <td>
                    <?php
                    $last_check = get_option('preowned_clothing_last_update_check');
                    echo $last_check ? esc_html(date('F j, Y H:i:s', $last_check)) : 'Never';
                    ?>
                </td>
            </tr>
        </table>

        <div class="action-buttons">
            <button type="button" class="button button-secondary" id="force-update-check">
                Force Update Check
            </button>
            <button type="button" class="button button-secondary" id="clear-cache">
                Clear Cache
            </button>
            <span id="status-message"></span>
        </div>
    </div>

    <!-- Settings Form -->
    <form method="post" action="options.php" class="github-updater-form">
        <?php settings_fields('github_updater_settings'); ?>
        <?php do_settings_sections('github-updater'); ?>

        <div class="github-updater-card">
            <div class="action-buttons">
                <?php submit_button('Save Settings', 'primary', 'submit', false); ?>
                <button type="button" class="button button-secondary" id="test-connection">
                    Test Connection
                </button>
            </div>
        </div>
    </form>

    <!-- Test Connection Results -->
    <div id="test-connection-results" class="github-updater-card" style="display: none;">
        <h2>Connection Test Results</h2>
        <div id="test-connection-content"></div>
    </div>
</div>

<style>
    .github-updater-card {
        background: #fff;
        border: 1px solid #ddd;
        padding: 20px;
        margin-top: 20px;
        box-shadow: 0 1px 1px rgba(0, 0, 0, .04);
    }

    .action-buttons {
        margin-top: 15px;
    }

    #status-message {
        display: inline-block;
        margin-left: 10px;
    }

    .github-updater-notice {
        background-color: #f8f8f8;
        border-left: 4px solid #00a0d2;
        margin-bottom: 15px;
        padding: 10px 15px;
    }
</style>

<script>
    jQuery(document).ready(function ($) {
        // Test connection
        $('#test-connection').on('click', function () {
            const username = $('#preowned_clothing_github_username').val();
            const repository = $('#preowned_clothing_github_repository').val();
            const token = $('#preowned_clothing_github_token').val();

            if (!username || !repository) {
                alert('Please enter both a GitHub username and repository name.');
                return;
            }

            $(this).prop('disabled', true).text('Testing...');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'pcf_github_test_connection',
                    nonce: '<?php echo wp_create_nonce("github_updater_test_connection"); ?>',
                    username: username,
                    repository: repository,
                    token: token
                },
                success: function (response) {
                    $('#test-connection').prop('disabled', false).text('Test Connection');
                    $('#test-connection-results').show();

                    let content = '';

                    if (response.success) {
                        const data = response.data;
                        content = '<div class="notice notice-success inline"><p>✅ Connection successful!</p></div>';
                        content += '<table class="widefat striped">';
                        content += '<tr><th>Repository</th><td>' + data.repository + '</td></tr>';
                        content += '<tr><th>Latest Version</th><td>' + data.version + '</td></tr>';
                        content += '<tr><th>Release Date</th><td>' + new Date(data.published_at).toLocaleDateString() + '</td></tr>';

                        if (data.release_url) {
                            content += '<tr><th>Release URL</th><td><a href="' + data.release_url + '" target="_blank">' + data.release_url + '</a></td></tr>';
                        }

                        content += '</table>';

                        if (data.body) {
                            content += '<h3>Release Notes</h3>';
                            content += '<div class="release-notes">' + data.body.replace(/\n/g, '<br>') + '</div>';
                        }
                    } else {
                        const errorData = response.data;
                        content = '<div class="notice notice-error inline"><p>❌ Connection failed!</p></div>';
                        content += '<p><strong>Error:</strong> ' + errorData.message + '</p>';

                        if (errorData.repository_exists) {
                            content += '<div class="notice notice-warning inline"><p>Repository exists but no releases were found.</p>';
                            content += '<p>Make sure you have created at least one release on GitHub.</p></div>';
                            content += '<p><a href="https://github.com/' + username + '/' + repository + '/releases/new" target="_blank" class="button">Create a new release</a></p>';
                        }

                        if (errorData.details) {
                            content += '<h3>Technical Details</h3>';
                            content += '<pre>' + JSON.stringify(errorData.details, null, 2) + '</pre>';
                        }
                    }

                    $('#test-connection-content').html(content);
                },
                error: function (xhr, status, error) {
                    $('#test-connection').prop('disabled', false).text('Test Connection');
                    $('#test-connection-results').show();

                    const content = '<div class="notice notice-error inline"><p>❌ AJAX Error!</p></div>' +
                        '<p>Status: ' + status + '</p>' +
                        '<p>Error: ' + error + '</p>';

                    $('#test-connection-content').html(content);
                }
            });
        });

        // Force update check
        $('#force-update-check').on('click', function () {
            $(this).prop('disabled', true).text('Checking...');
            $('#status-message').text('').removeClass('success error');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'pcf_github_force_update_check',
                    nonce: '<?php echo wp_create_nonce("github_updater_force_update_check"); ?>'
                },
                success: function (response) {
                    $('#force-update-check').prop('disabled', false).text('Force Update Check');

                    if (response.success) {
                        const message = response.data.message;
                        if (response.data.version && response.data.update_url) {
                            $('#status-message').html(message + ' <a href="' + response.data.update_url + '">Go to Updates</a>').addClass('success');
                        } else {
                            $('#status-message').text(message).addClass('success');
                        }
                    } else {
                        $('#status-message').text('Error: ' + response.data).addClass('error');
                    }

                    // Refresh the page after a delay to show updated info
                    setTimeout(function () {
                        location.reload();
                    }, 3000);
                },
                error: function () {
                    $('#force-update-check').prop('disabled', false).text('Force Update Check');
                    $('#status-message').text('AJAX error occurred').addClass('error');
                }
            });
        });

        // Clear cache
        $('#clear-cache').on('click', function () {
            $(this).prop('disabled', true).text('Clearing...');
            $('#status-message').text('').removeClass('success error');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'pcf_github_clear_cache',
                    nonce: '<?php echo wp_create_nonce("github_updater_clear_cache"); ?>'
                },
                success: function (response) {
                    $('#clear-cache').prop('disabled', false).text('Clear Cache');

                    if (response.success) {
                        $('#status-message').text(response.data.message).addClass('success');
                    } else {
                        $('#status-message').text('Error: ' + response.data).addClass('error');
                    }
                },
                error: function () {
                    $('#clear-cache').prop('disabled', false).text('Clear Cache');
                    $('#status-message').text('AJAX error occurred').addClass('error');
                }
            });
        });

        // Add success/error styles
        $('<style>').text(`
        #status-message.success { color: green; font-weight: bold; }
        #status-message.error { color: red; font-weight: bold; }
        .release-notes { max-height: 200px; overflow-y: auto; padding: 10px; border: 1px solid #ddd; margin-top: 10px; }
    `).appendTo('head');
    });
</script>