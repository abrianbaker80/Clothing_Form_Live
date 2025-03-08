<?php
/**
 * Form Troubleshooter for Preowned Clothing Form
 * 
 * IMPORTANT: Delete this file in production!
 * This script helps identify issues with your form rendering and script loading.
 */

// Direct access check
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(dirname(dirname(dirname(__FILE__)))) . '/');
    require_once(ABSPATH . 'wp-config.php');
    require_once(ABSPATH . 'wp-includes/pluggable.php');
}

// Security check
if (!current_user_can('manage_options')) {
    wp_die('Unauthorized access');
}

// Function to check if required PHP files exist
function pcf_check_required_files()
{
    $plugin_dir = dirname(__FILE__);

    $required_files = [
        'preowned-clothing-form.php' => $plugin_dir . '/preowned-clothing-form.php',
        'includes/form-display.php' => $plugin_dir . '/includes/form-display.php',
        'includes/database-setup.php' => $plugin_dir . '/includes/database-setup.php',
        'includes/utilities.php' => $plugin_dir . '/includes/utilities.php',
        'includes/form/session-manager.php' => $plugin_dir . '/includes/form/session-manager.php',
        'includes/form/form-renderer.php' => $plugin_dir . '/includes/form/form-renderer.php',
        'assets/js/script.js' => $plugin_dir . '/assets/js/script.js',
        'assets/js/wizard-interface.js' => $plugin_dir . '/assets/js/wizard-interface.js',
        'assets/js/form-storage.js' => $plugin_dir . '/assets/js/form-storage.js',
        'assets/js/item-management.js' => $plugin_dir . '/assets/js/item-management.js',
    ];

    $results = [];

    foreach ($required_files as $name => $path) {
        $results[$name] = file_exists($path);
    }

    return $results;
}

// Function to check database tables
function pcf_check_db_tables()
{
    global $wpdb;
    $submissions_table = $wpdb->prefix . 'preowned_clothing_submissions';
    $items_table = $wpdb->prefix . 'preowned_clothing_items';

    $submissions_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $submissions_table)) === $submissions_table;
    $items_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $items_table)) === $items_table;

    return [
        'submissions_table' => $submissions_exists,
        'items_table' => $items_exists
    ];
}

// Function to check js file contents for common errors
function pcf_check_js_files()
{
    $plugin_dir = dirname(__FILE__);
    $js_files = [
        'script.js' => $plugin_dir . '/assets/js/script.js',
        'wizard-interface.js' => $plugin_dir . '/assets/js/wizard-interface.js',
        'form-storage.js' => $plugin_dir . '/assets/js/form-storage.js',
        'item-management.js' => $plugin_dir . '/assets/js/item-management.js',
    ];

    $results = [];

    foreach ($js_files as $name => $path) {
        if (file_exists($path)) {
            $content = file_get_contents($path);
            $results[$name] = [
                'size' => filesize($path),
                'jquery_check' => strpos($content, '$(document).ready') !== false || strpos($content, 'jQuery(document).ready') !== false,
                'element_selectors' => preg_match_all('/\$\([\'"]([#\.][a-zA-Z0-9_-]+)[\'"]\)/', $content, $matches) ? $matches[1] : [],
            ];
        } else {
            $results[$name] = ['error' => 'File not found'];
        }
    }

    return $results;
}

// Check WordPress options
function pcf_check_options()
{
    $options = [
        'preowned_clothing_version' => get_option('preowned_clothing_version'),
        'preowned_clothing_form_title' => get_option('preowned_clothing_form_title'),
        'preowned_clothing_form_intro' => get_option('preowned_clothing_form_intro'),
    ];

    return $options;
}

// Get active plugins
function pcf_get_active_plugins()
{
    return get_option('active_plugins');
}

// Run tests
$file_check = pcf_check_required_files();
$db_check = pcf_check_db_tables();
$js_check = pcf_check_js_files();
$options_check = pcf_check_options();
$active_plugins = pcf_get_active_plugins();
$php_version = phpversion();
$wp_version = get_bloginfo('version');

// Output results as HTML
?>
<!DOCTYPE html>
<html>

<head>
    <title>Preowned Clothing Form - Troubleshooter</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }

        .success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            padding: 10px;
            margin: 10px 0;
        }

        .warning {
            background: #fff3cd;
            border: 1px solid #ffeeba;
            padding: 10px;
            margin: 10px 0;
        }

        .error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 10px;
            margin: 10px 0;
        }

        h2 {
            margin-top: 30px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        table,
        th,
        td {
            border: 1px solid #ddd;
        }

        th,
        td {
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f0f0f0;
        }

        pre {
            background: #f5f5f5;
            padding: 10px;
            overflow: auto;
        }

        .fix-suggestion {
            background: #e8f4f8;
            border: 1px solid #b8e0ed;
            padding: 10px;
            margin: 10px 0;
        }
    </style>
</head>

<body>
    <h1>Preowned Clothing Form Troubleshooter</h1>
    <div class="warning">
        <strong>Warning:</strong> This tool is for development use only.
    </div>

    <h2>Environment</h2>
    <ul>
        <li>PHP Version: <?php echo $php_version; ?></li>
        <li>WordPress Version: <?php echo $wp_version; ?></li>
    </ul>

    <h2>Required Files</h2>
    <table>
        <tr>
            <th>File</th>
            <th>Status</th>
        </tr>
        <?php foreach ($file_check as $file => $exists): ?>
            <tr>
                <td><?php echo $file; ?></td>
                <td><?php echo $exists ? '✅ Present' : '❌ Missing'; ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <h2>Database Tables</h2>
    <table>
        <tr>
            <th>Table</th>
            <th>Status</th>
        </tr>
        <?php foreach ($db_check as $table => $exists): ?>
            <tr>
                <td><?php echo $table; ?></td>
                <td><?php echo $exists ? '✅ Present' : '❌ Missing'; ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <h2>JavaScript Files</h2>
    <?php foreach ($js_check as $name => $info): ?>
        <h3><?php echo $name; ?></h3>
        <?php if (isset($info['error'])): ?>
            <div class="error"><?php echo $info['error']; ?></div>
        <?php else: ?>
            <ul>
                <li>Size: <?php echo $info['size']; ?> bytes</li>
                <li>Has jQuery ready check: <?php echo $info['jquery_check'] ? 'Yes' : 'No'; ?></li>
            </ul>
            <p>Element selectors:</p>
            <ul>
                <?php foreach ($info['element_selectors'] as $selector): ?>
                    <li><?php echo htmlspecialchars($selector); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    <?php endforeach; ?>

    <h2>WordPress Options</h2>
    <table>
        <tr>
            <th>Option</th>
            <th>Value</th>
        </tr>
        <?php foreach ($options_check as $option => $value): ?>
            <tr>
                <td><?php echo $option; ?></td>
                <td><?php echo is_scalar($value) ? htmlspecialchars($value) : 'Complex value'; ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <h2>Active Plugins</h2>
    <ul>
        <?php foreach ($active_plugins as $plugin): ?>
            <li><?php echo htmlspecialchars($plugin); ?></li>
        <?php endforeach; ?>
    </ul>

    <h2>Common Issues and Solutions</h2>

    <div class="fix-suggestion">
        <h3>JavaScript Console Errors</h3>
        <p>If you're seeing errors like "Element not found" in the JavaScript console:</p>
        <ol>
            <li>Make sure form-display.php is outputting the correct HTML structure with the right element IDs</li>
            <li>Check if scripts are loading in the correct order in preowned-clothing-form.php</li>
            <li>Try clearing browser cache or testing in incognito mode</li>
        </ol>
    </div>

    <div class="fix-suggestion">
        <h3>Wizard Not Displaying</h3>
        <p>If the wizard interface isn't displaying:</p>
        <ol>
            <li>Check if wizard-interface.js is loading</li>
            <li>Make sure the HTML includes div.pcf-wizard-container elements</li>
            <li>Verify CSS for the wizard is being applied</li>
        </ol>
    </div>

    <div class="fix-suggestion">
        <h3>Add Item Button Not Working</h3>
        <p>If the "Add Item" button isn't working:</p>
        <ol>
            <li>Make sure the button has ID #add-another-item</li>
            <li>Ensure item-management.js is loading after form-storage.js</li>
            <li>Check if the button has proper event handlers</li>
        </ol>
    </div>

    <h2>Next Steps</h2>
    <p>Based on this troubleshooter, you should:</p>
    <ol>
        <li>Fix any missing files</li>
        <li>Recreate missing database tables using the admin-cleanup.php tool</li>
        <li>Check JavaScript console for additional errors</li>
        <li>Consider updating element IDs and selectors in your HTML output</li>
    </ol>
</body>

</html>