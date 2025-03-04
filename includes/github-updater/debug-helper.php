<?php
/**
 * Debug Helper for GitHub Updater
 * 
 * @package PreownedClothingForm
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Get debug information about the GitHub updater
 * 
 * @return array Debug information
 */
function pcf_github_get_debug_info() {
    $debug = [];
    
    // Get plugin file
    $plugin_file = dirname(dirname(dirname(__FILE__))) . '/preowned-clothing-form.php';
    $debug['plugin_file_exists'] = file_exists($plugin_file);
    
    if ($debug['plugin_file_exists']) {
        // Get plugin data
        if (!function_exists('get_plugin_data')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $plugin_data = get_plugin_data($plugin_file);
        $debug['plugin_version'] = $plugin_data['Version'];
        
        // Check constant version
        $debug['pcf_version_constant'] = defined('PCF_VERSION') ? PCF_VERSION : 'Not defined';
        $debug['version_match'] = defined('PCF_VERSION') && PCF_VERSION === $plugin_data['Version'];
    }
    
    // Check update transient
    $update_transient = get_site_transient('update_plugins');
    $debug['update_transient_exists'] = is_object($update_transient);
    
    if ($debug['update_transient_exists']) {
        $plugin_basename = plugin_basename($plugin_file);
        $normalized_basename = 'Clothing_Form/preowned-clothing-form.php';
        
        $debug['plugin_basename'] = $plugin_basename;
        $debug['normalized_basename'] = $normalized_basename;
        $debug['in_checked'] = isset($update_transient->checked[$plugin_basename]) || 
                             isset($update_transient->checked[$normalized_basename]);
        $debug['in_response'] = isset($update_transient->response[$plugin_basename]) || 
                              isset($update_transient->response[$normalized_basename]);
    }
    
    // Check for GitHub releases
    $debug['github_releases_url'] = 'https://api.github.com/repos/abrianbaker80/Clothing_Form/releases';
    $response = wp_remote_get($debug['github_releases_url']);
    $debug['github_api_reachable'] = !is_wp_error($response);
    
    if ($debug['github_api_reachable']) {
        $releases = json_decode(wp_remote_retrieve_body($response), true);
        $debug['releases_count'] = is_array($releases) ? count($releases) : 0;
        
        if ($debug['releases_count'] > 0) {
            $latest = $releases[0];
            $debug['latest_tag'] = isset($latest['tag_name']) ? $latest['tag_name'] : 'Unknown';
            $debug['latest_version'] = isset($latest['tag_name']) ? ltrim($latest['tag_name'], 'v') : 'Unknown';
        }
    }
    
    return $debug;
}

/**
 * Display debug information in admin footer
 */
function pcf_github_display_debug_info() {
    // Only show to admins
    if (!current_user_can('manage_options')) {
        return;
    }
    
    $debug = pcf_github_get_debug_info();
    
    echo '<div class="pcf-github-debug" style="margin-top: 20px; padding: 15px; background: #f8f9fa; border: 1px solid #ddd;">';
    echo '<h4>GitHub Updater Debug Info</h4>';
    
    echo '<table style="width: 100%; border-collapse: collapse;">';
    foreach ($debug as $key => $value) {
        echo '<tr>';
        echo '<td style="padding: 5px; border-bottom: 1px solid #eee; font-weight: bold;">' . esc_html($key) . '</td>';
        echo '<td style="padding: 5px; border-bottom: 1px solid #eee;">';
        
        if (is_bool($value)) {
            echo $value ? '<span style="color:green;">Yes</span>' : '<span style="color:red;">No</span>';
        } else {
            echo esc_html(print_r($value, true));
        }
        
        echo '</td></tr>';
    }
    echo '</table>';
    
    echo '<p>Tip: If version_match is "No", make sure PCF_VERSION matches the Version in your plugin header.</p>';
    echo '</div>';
}

// Only add the debug info on the plugins page
add_action('admin_footer-plugins.php', 'pcf_github_display_debug_info');
