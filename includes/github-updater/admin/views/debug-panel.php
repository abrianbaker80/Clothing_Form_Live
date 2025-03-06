<?php
/**
 * GitHub Updater Debug Panel Template
 * 
 * @package PreownedClothingForm
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get debug log content
$debug_log = isset($debug_log) ? $debug_log : (function_exists('preowned_clothing_get_debug_log') ? preowned_clothing_get_debug_log() : '');
?>
<div class="github-updater-log">
    <h2>Debug Log</h2>
    <p>
        <button id="toggle-debug-log" class="button">Show Debug Log</button>
        <button id="refresh-debug-log" class="button">Refresh</button>
        <button id="clear-debug-log" class="button">Clear Log</button>
    </p>
    <div id="debug-log-container" style="display: none;">
        <pre id="debug-log"><?php echo esc_html($debug_log); ?></pre>
    </div>
</div>

<script>
    jQuery(document).ready(function($) {
        $('#toggle-debug-log').on('click', function() {
            $('#debug-log-container').toggle();
            $(this).text($(this).text() === 'Show Debug Log' ? 'Hide Debug Log' : 'Show Debug Log');
        });
        
        $('#refresh-debug-log').on('click', function() {
            $.post(ajaxurl, {
                action: 'preowned_clothing_refresh_debug_log',
                nonce: '<?php echo wp_create_nonce('preowned_clothing_refresh_debug_log'); ?>'
            }, function(response) {
                if (response.success) {
                    $('#debug-log').text(response.data);
                }
            });
        });
        
        $('#clear-debug-log').on('click', function() {
            if (confirm('Are you sure you want to clear the debug log?')) {
                $.post(ajaxurl, {
                    action: 'preowned_clothing_clear_debug_log',
                    nonce: '<?php echo wp_create_nonce('preowned_clothing_clear_debug_log'); ?>'
                }, function(response) {
                    if (response.success) {
                        $('#debug-log').text('');
                    }
                });
            }
        });
    });
</script>

<style>
    #debug-log-container {
        background: #f5f5f5;
        padding: 10px;
        margin-top: 10px;
        border: 1px solid #ddd;
    }
    #debug-log {
        max-height: 300px;
        overflow: auto;
        margin: 0;
    }
</style>
