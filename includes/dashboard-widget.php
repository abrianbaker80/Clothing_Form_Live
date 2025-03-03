<?php
/**
 * Dashboard Widget for Clothing Form
 *
 * Adds a dashboard widget showing submission statistics
 *
 * @package PreownedClothingForm
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Register the dashboard widget
 */
function preowned_clothing_add_dashboard_widget() {
    wp_add_dashboard_widget(
        'preowned_clothing_stats_widget',
        'Clothing Submissions Overview',
        'preowned_clothing_stats_widget_output',
        null,
        null,
        'normal',
        'high'
    );
}
add_action('wp_dashboard_setup', 'preowned_clothing_add_dashboard_widget');

/**
 * Generate the dashboard widget output
 */
function preowned_clothing_stats_widget_output() {
    global $wpdb;
    $submissions_table = $wpdb->prefix . 'preowned_clothing_submissions';
    $items_table = $wpdb->prefix . 'preowned_clothing_items';
    
    // Check if tables exist
    $submissions_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $submissions_table)) === $submissions_table;
    $items_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $items_table)) === $items_table;
    
    if (!$submissions_exists || !$items_exists) {
        echo '<p>Tables not found. Please check your installation.</p>';
        return;
    }
    
    // Get submissions statistics
    $stats = array(
        'total_submissions' => $wpdb->get_var("SELECT COUNT(*) FROM {$submissions_table}"),
        'pending_submissions' => $wpdb->get_var("SELECT COUNT(*) FROM {$submissions_table} WHERE status = 'pending'"),
        'contacted_submissions' => $wpdb->get_var("SELECT COUNT(*) FROM {$submissions_table} WHERE status = 'contacted'"),
        'completed_submissions' => $wpdb->get_var("SELECT COUNT(*) FROM {$submissions_table} WHERE status = 'completed'"),
        'total_items' => $wpdb->get_var("SELECT COUNT(*) FROM {$items_table}"),
        'submissions_last_7days' => $wpdb->get_var("SELECT COUNT(*) FROM {$submissions_table} WHERE submission_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)"),
        'submissions_today' => $wpdb->get_var("SELECT COUNT(*) FROM {$submissions_table} WHERE DATE(submission_date) = CURDATE()")
    );
    
    // Get top categories
    $top_categories = $wpdb->get_results("
        SELECT category_level_0, COUNT(*) as count
        FROM {$items_table}
        WHERE category_level_0 != ''
        GROUP BY category_level_0
        ORDER BY count DESC
        LIMIT 5
    ");
    
    // Widget styles
    echo '<style>
        .clothing-stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 15px;
        }
        .stat-card {
            background: #f7f7f7;
            border-left: 4px solid #0073aa;
            padding: 10px 15px;
            border-radius: 2px;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
            position: relative;
        }
        .stat-card h4 {
            margin: 0 0 5px;
            color: #23282d;
        }
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #0073aa;
        }
        .status-bar {
            display: flex;
            margin-top: 15px;
            height: 20px;
            border-radius: 10px;
            overflow: hidden;
            background: #f1f1f1;
        }
        .status-segment {
            height: 100%;
            text-align: center;
            color: white;
            font-size: 11px;
            line-height: 20px;
            white-space: nowrap;
        }
        .segment-pending { background-color: #999; }
        .segment-contacted { background-color: #ffba00; }
        .segment-completed { background-color: #46b450; }
        .chart-container {
            margin-top: 15px;
        }
        .category-bar {
            display: flex;
            margin-bottom: 8px;
        }
        .category-name {
            flex: 0 0 100px;
            font-size: 12px;
            padding-right: 10px;
        }
        .category-value-bar {
            flex-grow: 1;
            background: #0073aa;
            height: 20px;
            border-radius: 3px;
            position: relative;
            color: white;
            font-size: 11px;
            line-height: 20px;
            padding: 0 5px;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
        }
    </style>';
    
    // Main stats
    echo '<div class="clothing-stats-grid">';
    
    // Total submissions
    echo '<div class="stat-card">
        <h4>Total Submissions</h4>
        <div class="stat-number">' . esc_html($stats['total_submissions']) . '</div>
    </div>';
    
    // New submissions today
    echo '<div class="stat-card">
        <h4>Today\'s Submissions</h4>
        <div class="stat-number">' . esc_html($stats['submissions_today']) . '</div>
    </div>';
    
    // Pending submissions
    echo '<div class="stat-card">
        <h4>Pending Submissions</h4>
        <div class="stat-number">' . esc_html($stats['pending_submissions']) . '</div>
    </div>';
    
    // Total items
    echo '<div class="stat-card">
        <h4>Total Items</h4>
        <div class="stat-number">' . esc_html($stats['total_items']) . '</div>
    </div>';
    
    echo '</div>';
    
    // Status breakdown bar
    $total = max(1, $stats['total_submissions']); // Avoid division by zero
    $pending_percent = round(($stats['pending_submissions'] / $total) * 100);
    $contacted_percent = round(($stats['contacted_submissions'] / $total) * 100);
    $completed_percent = round(($stats['completed_submissions'] / $total) * 100);
    
    echo '<h4>Status Breakdown</h4>';
    echo '<div class="status-bar">';
    if ($pending_percent > 0) {
        echo '<div class="status-segment segment-pending" style="width:' . $pending_percent . '%">Pending ' . $stats['pending_submissions'] . '</div>';
    }
    if ($contacted_percent > 0) {
        echo '<div class="status-segment segment-contacted" style="width:' . $contacted_percent . '%">Contacted ' . $stats['contacted_submissions'] . '</div>';
    }
    if ($completed_percent > 0) {
        echo '<div class="status-segment segment-completed" style="width:' . $completed_percent . '%">Completed ' . $stats['completed_submissions'] . '</div>';
    }
    echo '</div>';
    
    // Top categories chart
    if (!empty($top_categories)) {
        echo '<h4>Top Categories</h4>';
        echo '<div class="chart-container">';
        
        $max_count = 0;
        foreach ($top_categories as $category) {
            $max_count = max($max_count, $category->count);
        }
        
        foreach ($top_categories as $category) {
            $percent = round(($category->count / $max_count) * 100);
            echo '<div class="category-bar">';
            echo '<div class="category-name">' . esc_html($category->category_level_0) . '</div>';
            echo '<div class="category-value-bar" style="width:' . $percent . '%">' . esc_html($category->count) . '</div>';
            echo '</div>';
        }
        
        echo '</div>';
    }
    
    // Link to submissions page
    echo '<p><a href="' . admin_url('admin.php?page=clothing-submissions') . '" class="button button-primary">View All Submissions</a></p>';
}

/**
 * Add quick stats to the At a Glance dashboard widget
 */
function preowned_clothing_add_to_glance_items($items) {
    global $wpdb;
    $submissions_table = $wpdb->prefix . 'preowned_clothing_submissions';
    
    // Check if table exists
    $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $submissions_table)) === $submissions_table;
    
    if ($table_exists) {
        $pending_count = $wpdb->get_var("SELECT COUNT(*) FROM {$submissions_table} WHERE status = 'pending'");
        
        if ($pending_count > 0) {
            $text = sprintf(
                _n('%s pending clothing submission', '%s pending clothing submissions', $pending_count),
                number_format_i18n($pending_count)
            );
            
            $items[] = sprintf(
                '<a href="%s" class="clothing-pending-count">%s</a>',
                admin_url('admin.php?page=clothing-submissions&status=pending'),
                $text
            );
        }
    }
    
    return $items;
}
add_filter('dashboard_glance_items', 'preowned_clothing_add_to_glance_items');

/**
 * Add custom styling for the dashboard widget
 */
function preowned_clothing_dashboard_styles() {
    echo '<style>
        .clothing-pending-count:before {
            content: "\f163";
            color: #ca4a1f;
        }
        #preowned_clothing_stats_widget .inside {
            padding: 0;
            margin: 0;
        }
    </style>';
}
add_action('admin_head', 'preowned_clothing_dashboard_styles');
