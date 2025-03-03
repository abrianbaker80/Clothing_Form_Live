<?php
/**
 * Admin Submissions Management
 *
 * Handles displaying and managing clothing submissions in the WordPress admin.
 *
 * @package PreownedClothingForm
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Add submissions management page to admin menu
 */
function preowned_clothing_admin_submissions_menu() {
    add_menu_page(
        'Clothing Submissions',
        'Clothing Items',
        'manage_options',
        'clothing-submissions',
        'preowned_clothing_submissions_page',
        'dashicons-products',
        30
    );
}
add_action('admin_menu', 'preowned_clothing_admin_submissions_menu');

/**
 * Enqueue admin styles and scripts
 */
function preowned_clothing_admin_enqueue_scripts($hook) {
    if ($hook != 'toplevel_page_clothing-submissions') {
        return;
    }

    // Enqueue Font Awesome for admin with fallback
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css', array(), '5.15.4');
    
    // Check if files exist before enqueueing
    $admin_icons_fallback = plugin_dir_path(dirname(__FILE__)) . 'assets/css/admin-icons-fallback.css';
    if (file_exists($admin_icons_fallback)) {
        wp_enqueue_style('preowned-clothing-admin-icons-fallback', plugin_dir_url(dirname(__FILE__)) . 'assets/css/admin-icons-fallback.css', array('font-awesome'), '1.0.0');
    }
    
    // Enqueue base admin styles
    wp_enqueue_style('preowned-clothing-admin-style', plugin_dir_url(dirname(__FILE__)) . 'assets/css/admin-style.css', array(), '1.1.0');
    
    // Enqueue card-based layout enhancements for admin if exists
    $admin_card_layout = plugin_dir_path(dirname(__FILE__)) . 'assets/css/admin-card-layout.css';
    if (file_exists($admin_card_layout)) {
        wp_enqueue_style('preowned-clothing-admin-card-layout', plugin_dir_url(dirname(__FILE__)) . 'assets/css/admin-card-layout.css', array('preowned-clothing-admin-style'), '1.0.0');
    }
    
    // Enqueue scripts if exists
    $admin_script = plugin_dir_path(dirname(__FILE__)) . 'assets/js/admin-script.js';
    if (file_exists($admin_script)) {
        wp_enqueue_script('preowned-clothing-admin-script', plugin_dir_url(dirname(__FILE__)) . 'assets/js/admin-script.js', array('jquery'), '1.1.0', true);
    }
    
    // Add mobile admin enhancements if exists
    $admin_mobile = plugin_dir_path(dirname(__FILE__)) . 'assets/css/admin-mobile.css';
    if (file_exists($admin_mobile)) {
        wp_enqueue_style('preowned-clothing-admin-mobile', plugin_dir_url(dirname(__FILE__)) . 'assets/css/admin-mobile.css', array(), '1.0.0');
    }
}
add_action('admin_enqueue_scripts', 'preowned_clothing_admin_enqueue_scripts');

/**
 * Handle export requests
 */
function preowned_clothing_maybe_export_submissions() {
    if (isset($_GET['action']) && $_GET['action'] === 'export' && 
        isset($_GET['format']) && current_user_can('manage_options') &&
        check_admin_referer('export_submissions')) {
        
        global $wpdb;
        $submissions_table = $wpdb->prefix . 'preowned_clothing_submissions';
        $items_table = $wpdb->prefix . 'preowned_clothing_items';
        
        $format = sanitize_text_field($_GET['format']);
        
        // Define query
        $query = "
            SELECT s.id as submission_id, s.name, s.email, s.submission_date, s.status,
                   i.id as item_id, i.category_level_0, i.category_level_1, i.category_level_2, 
                   i.size, i.description
            FROM $submissions_table s
            LEFT JOIN $items_table i ON s.id = i.submission_id
            ORDER BY s.submission_date DESC, s.id, i.id
        ";
        
        $results = $wpdb->get_results($query);
        
        if ($results) {
            if ($format === 'csv') {
                // Set headers for CSV download
                header('Content-Type: text/csv');
                header('Content-Disposition: attachment; filename="clothing-submissions-' . date('Y-m-d') . '.csv"');
                
                // Create output handle
                $output = fopen('php://output', 'w');
                
                // Write CSV header
                fputcsv($output, array(
                    'Submission ID', 'Name', 'Email', 'Date', 'Status',
                    'Item ID', 'Category', 'Subcategory', 'Type', 
                    'Size', 'Description'
                ));
                
                // Write rows
                foreach ($results as $row) {
                    fputcsv($output, array(
                        $row->submission_id,
                        $row->name,
                        $row->email,
                        $row->submission_date,
                        $row->status,
                        $row->item_id,
                        $row->category_level_0,
                        $row->category_level_1,
                        $row->category_level_2,
                        $row->size,
                        $row->description
                    ));
                }
                
                fclose($output);
                exit;
            }
            elseif ($format === 'json') {
                // Set headers for JSON download
                header('Content-Type: application/json');
                header('Content-Disposition: attachment; filename="clothing-submissions-' . date('Y-m-d') . '.json"');
                
                // Group by submission
                $grouped_data = array();
                foreach ($results as $row) {
                    if (!isset($grouped_data[$row->submission_id])) {
                        $grouped_data[$row->submission_id] = array(
                            'submission_id' => $row->submission_id,
                            'name' => $row->name,
                            'email' => $row->email,
                            'date' => $row->submission_date,
                            'status' => $row->status,
                            'items' => array()
                        );
                    }
                    
                    $grouped_data[$row->submission_id]['items'][] = array(
                        'item_id' => $row->item_id,
                        'category' => $row->category_level_0,
                        'subcategory' => $row->category_level_1,
                        'type' => $row->category_level_2,
                        'size' => $row->size,
                        'description' => $row->description
                    );
                }
                
                echo json_encode(array_values($grouped_data), JSON_PRETTY_PRINT);
                exit;
            }
        }
    }
}
add_action('admin_init', 'preowned_clothing_maybe_export_submissions');

/**
 * Display the submissions management page
 */
function preowned_clothing_submissions_page() {
    global $wpdb;
    $submissions_table = $wpdb->prefix . 'preowned_clothing_submissions';
    $items_table = $wpdb->prefix . 'preowned_clothing_items';
    
    // Handle notes submission first
    if (isset($_POST['save_notes']) && isset($_POST['submission_id']) && 
        check_admin_referer('save_submission_notes', 'submission_notes_nonce')) {
        
        $submission_id = intval($_POST['submission_id']);
        $notes = isset($_POST['submission_notes']) ? sanitize_textarea_field($_POST['submission_notes']) : '';
        
        $wpdb->update(
            $submissions_table,
            array('notes' => $notes),
            array('id' => $submission_id),
            array('%s'),
            array('%d')
        );
        
        echo '<div class="notice notice-success is-dismissible"><p>Notes updated successfully.</p></div>';
    }
    
    // Initialize view mode (list or detail)
    $view_mode = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'list';
    $submission_id = isset($_GET['submission_id']) ? intval($_GET['submission_id']) : 0;
    
    // Process any actions (delete, update status, etc.)
    if (isset($_GET['action']) && isset($_GET['submission_id']) && check_admin_referer('clothing_submission_action')) {
        $action = sanitize_text_field($_GET['action']);
        $submission_id = intval($_GET['submission_id']);
        
        switch ($action) {
            case 'delete':
                // Delete all associated items first
                $wpdb->delete(
                    $items_table, 
                    array('submission_id' => $submission_id), 
                    array('%d')
                );
                
                // Then delete the submission
                $wpdb->delete(
                    $submissions_table, 
                    array('id' => $submission_id), 
                    array('%d')
                );
                echo '<div class="notice notice-success is-dismissible"><p>Submission and all associated items deleted successfully.</p></div>';
                break;
            
            case 'mark_contacted':
                $wpdb->update(
                    $submissions_table, 
                    array('status' => 'contacted'), 
                    array('id' => $submission_id), 
                    array('%s'), 
                    array('%d')
                );
                echo '<div class="notice notice-success is-dismissible"><p>Submission marked as contacted.</p></div>';
                break;
                
            case 'mark_completed':
                $wpdb->update(
                    $submissions_table, 
                    array('status' => 'completed'), 
                    array('id' => $submission_id), 
                    array('%s'), 
                    array('%d')
                );
                echo '<div class="notice notice-success is-dismissible"><p>Submission marked as completed.</p></div>';
                break;
                
            case 'delete_item':
                $item_id = isset($_GET['item_id']) ? intval($_GET['item_id']) : 0;
                if ($item_id > 0) {
                    $wpdb->delete(
                        $items_table, 
                        array('id' => $item_id), 
                        array('%d')
                    );
                    echo '<div class="notice notice-success is-dismissible"><p>Item deleted successfully.</p></div>';
                }
                break;
        }
    }
    
    // Handle batch actions
    if (isset($_POST['bulk_action']) && isset($_POST['submission_ids']) && 
        check_admin_referer('clothing_submissions_bulk_action')) {
        
        $bulk_action = sanitize_text_field($_POST['bulk_action']);
        $submission_ids = array_map('intval', $_POST['submission_ids']);
        
        if (!empty($submission_ids)) {
            switch ($bulk_action) {
                case 'delete':
                    foreach ($submission_ids as $id) {
                        // Delete all associated items first
                        $wpdb->delete(
                            $items_table, 
                            array('submission_id' => $id), 
                            array('%d')
                        );
                        
                        // Then delete the submission
                        $wpdb->delete(
                            $submissions_table, 
                            array('id' => $id), 
                            array('%d')
                        );
                    }
                    echo '<div class="notice notice-success is-dismissible"><p>' . count($submission_ids) . ' submissions and their items deleted successfully.</p></div>';
                    break;
                
                case 'mark_contacted':
                    foreach ($submission_ids as $id) {
                        $wpdb->update(
                            $submissions_table, 
                            array('status' => 'contacted'), 
                            array('id' => $id), 
                            array('%s'), 
                            array('%d')
                        );
                    }
                    echo '<div class="notice notice-success is-dismissible"><p>' . count($submission_ids) . ' submissions marked as contacted.</p></div>';
                    break;
                    
                case 'mark_completed':
                    foreach ($submission_ids as $id) {
                        $wpdb->update(
                            $submissions_table, 
                            array('status' => 'completed'), 
                            array('id' => $id), 
                            array('%s'), 
                            array('%d')
                        );
                    }
                    echo '<div class="notice notice-success is-dismissible"><p>' . count($submission_ids) . ' submissions marked as completed.</p></div>';
                    break;
            }
        }
    }

    // Pagination settings
    $per_page = 20;
    $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $offset = ($current_page - 1) * $per_page;
    
    // Search functionality
    $search_term = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
    $search_query = '';
    $search_params = array();
    
    if (!empty($search_term)) {
        // Note: This is a simplified search - for a complex search across multiple tables, 
        // you might need a JOIN and more complex query
        $search_query = " WHERE (s.name LIKE %s OR s.email LIKE %s OR i.description LIKE %s) ";
        $search_params = array(
            '%' . $wpdb->esc_like($search_term) . '%',
            '%' . $wpdb->esc_like($search_term) . '%',
            '%' . $wpdb->esc_like($search_term) . '%'
        );
    }
    
    // Filter by status
    $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
    if (!empty($status_filter)) {
        $search_query .= empty($search_query) ? " WHERE " : " AND ";
        $search_query .= " s.status = %s ";
        $search_params[] = $status_filter;
    }
    
    // Filter by category
    $category_filter = isset($_GET['category']) ? sanitize_text_field($_GET['category']) : '';
    if (!empty($category_filter)) {
        $search_query .= empty($search_query) ? " WHERE " : " AND ";
        $search_query .= " (i.category_level_0 = %s OR i.category_level_1 = %s OR i.category_level_2 = %s) ";
        $search_params[] = $category_filter;
        $search_params[] = $category_filter;
        $search_params[] = $category_filter;
    }
    
    // Get submissions with counts of items
    $query = "
        SELECT DISTINCT s.*, 
               COUNT(i.id) as item_count,
               MAX(i.category_level_0) as primary_category
        FROM $submissions_table s
        LEFT JOIN $items_table i ON s.id = i.submission_id
        $search_query
        GROUP BY s.id
        ORDER BY s.submission_date DESC
        LIMIT %d OFFSET %d
    ";
    
    // Get total count for pagination
    $count_query = "
        SELECT COUNT(DISTINCT s.id)
        FROM $submissions_table s
        LEFT JOIN $items_table i ON s.id = i.submission_id
        $search_query
    ";
    
    // Prepare and execute the queries
    $prepared_query_params = array_merge($search_params, array($per_page, $offset));
    $submissions = $wpdb->get_results($wpdb->prepare($query, $prepared_query_params));
    $total = $wpdb->get_var($wpdb->prepare($count_query, $search_params));
    $total_pages = ceil($total / $per_page);
    
    // Get available categories for filtering
    $categories_query = "
        SELECT DISTINCT 
            CASE 
                WHEN category_level_0 != '' THEN category_level_0
                WHEN category_level_1 != '' THEN category_level_1
                WHEN category_level_2 != '' THEN category_level_2
            END as category
        FROM $items_table
        WHERE category_level_0 != '' OR category_level_1 != '' OR category_level_2 != ''
        ORDER BY category
    ";
    $available_categories = $wpdb->get_col($categories_query);
    
    // Check if we have a specific submission to view
    $single_submission = null;
    $submission_items = array();
    $current_item = null; // Initialize the current item variable
    
    if ($view_mode === 'detail' && $submission_id > 0) {
        // Get submission details
        $single_submission = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $submissions_table WHERE id = %d", 
            $submission_id
        ));
        
        // Get all items for this submission
        $submission_items = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $items_table WHERE submission_id = %d ORDER BY id", 
            $submission_id
        ));
        
        // Get the current item if specified
        $item_id = isset($_GET['item_id']) ? intval($_GET['item_id']) : 0;
        
        if ($item_id > 0 && !empty($submission_items)) {
            // Find the item with matching ID
            foreach ($submission_items as $item) {
                if ($item->id == $item_id) {
                    $current_item = $item;
                    break;
                }
            }
        }
        
        // If no specific item is selected or found, use the first one
        if (!$current_item && !empty($submission_items)) {
            $current_item = $submission_items[0];
        }
    }
    
    // Display the appropriate view
    if ($view_mode === 'detail' && $single_submission) {
        // Detail view with multiple items
        ?>
        <div class="wrap clothing-admin-wrapper">
            <h1 class="wp-heading-inline">
                <i class="fas fa-tshirt"></i> Clothing Submission Details
            </h1>
            <?php 
            // Check if template exists before including
            $template_path = plugin_dir_path(dirname(__FILE__)) . 'templates/admin-submission-detail.php';
            if (file_exists($template_path)) {
                include_once($template_path);
            } else {
                echo '<div class="notice notice-error"><p>Template file not found: templates/admin-submission-detail.php</p></div>';
            }
            ?>
        </div>
        <?php
    } else {
        // List view
        ?>
        <div class="wrap clothing-admin-wrapper">
            <h1 class="wp-heading-inline">
                <i class="fas fa-tshirt"></i> Clothing Submissions
            </h1>
            <a href="<?php echo esc_url(wp_nonce_url(add_query_arg(array('action' => 'export', 'format' => 'csv'), admin_url('admin.php?page=clothing-submissions')), 'export_submissions')); ?>" class="page-title-action">
                <i class="fas fa-file-export"></i> Export CSV
            </a>
            <a href="<?php echo esc_url(wp_nonce_url(add_query_arg(array('action' => 'export', 'format' => 'json'), admin_url('admin.php?page=clothing-submissions')), 'export_submissions')); ?>" class="page-title-action">
                <i class="fas fa-file-code"></i> Export JSON
            </a>
            <?php 
            // Check if template exists before including
            $template_path = plugin_dir_path(dirname(__FILE__)) . 'templates/admin-submissions-list.php';
            if (file_exists($template_path)) {
                include_once($template_path);
            } else {
                echo '<div class="notice notice-error"><p>Template file not found: templates/admin-submissions-list.php</p></div>';
            }
            ?>
        </div>
        <?php
    }
}

/**
 * Get status label with appropriate color coding
 */
function preowned_clothing_get_status_label($status) {
    switch ($status) {
        case 'pending':
            return '<span class="status-pending">Pending</span>';
        case 'contacted':
            return '<span class="status-contacted">Contacted</span>';
        case 'completed':
            return '<span class="status-completed">Completed</span>';
        default:
            return '<span class="status-pending">Pending</span>';
    }
}

/**
 * Get category path as string
 */
function preowned_clothing_get_category_path($item) {
    $categories = array();
    if (!empty($item->category_level_0)) $categories[] = $item->category_level_0;
    if (!empty($item->category_level_1)) $categories[] = $item->category_level_1;
    if (!empty($item->category_level_2)) $categories[] = $item->category_level_2;
    if (!empty($item->category_level_3)) $categories[] = $item->category_level_3;
    
    return implode(' > ', $categories);
}
