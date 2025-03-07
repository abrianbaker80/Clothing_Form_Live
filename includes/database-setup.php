<?php
/**
 * Database Setup for Preowned Clothing Form
 *
 * This file handles the creation and management of database tables for storing
 * clothing submissions.
 *
 * @package PreownedClothingForm
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// IMPORTANT: Removed all activation hooks

/**
 * Check table exists and recreate if necessary
 * This is the primary function that should be called to ensure tables exist
 */
function preowned_clothing_check_table()
{
    global $wpdb;
    $submissions_table = $wpdb->prefix . 'preowned_clothing_submissions';
    $items_table = $wpdb->prefix . 'preowned_clothing_items';

    $submissions_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $submissions_table)) === $submissions_table;
    $items_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $items_table)) === $items_table;

    if (!$submissions_exists || !$items_exists) {
        return _preowned_clothing_create_tables();
    }

    return true;
}

/**
 * Creates or updates the submissions database tables
 * Renamed with underscore prefix to avoid conflicts with activation hooks
 * 
 * @param bool $force_recreate Whether to drop and recreate the tables
 * @return bool True on success, false on failure
 */
function _preowned_clothing_create_tables($force_recreate = false)
{
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $submissions_table = $wpdb->prefix . 'preowned_clothing_submissions';
    $items_table = $wpdb->prefix . 'preowned_clothing_items';
    $success = true;

    // Drop tables if forced recreation
    if ($force_recreate) {
        $wpdb->query("DROP TABLE IF EXISTS $items_table");
        $wpdb->query("DROP TABLE IF EXISTS $submissions_table");
    }

    // Create main submissions table (customer info)
    $sql = "CREATE TABLE IF NOT EXISTS $submissions_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        submission_date timestamp DEFAULT CURRENT_TIMESTAMP NOT NULL,
        name varchar(255) NOT NULL,
        email varchar(255) NOT NULL,
        status varchar(50) DEFAULT 'pending',
        notes text,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    if ($wpdb->last_error) {
        error_log('Preowned Clothing Form: Submissions table creation error - ' . $wpdb->last_error);
        $success = false;
    }

    // Create items table (individual clothing items)
    $sql = "CREATE TABLE IF NOT EXISTS $items_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        submission_id mediumint(9) NOT NULL,
        category_level_0 varchar(255),
        category_level_1 varchar(255),
        category_level_2 varchar(255),
        category_level_3 varchar(255),
        size varchar(100),
        description text NOT NULL,
        image_front varchar(255),
        image_back varchar(255),
        image_brand_tag varchar(255),
        image_material_tag varchar(255),
        image_detail varchar(255),
        PRIMARY KEY  (id),
        KEY submission_id (submission_id)
    ) $charset_collate;";

    dbDelta($sql);

    if ($wpdb->last_error) {
        error_log('Preowned Clothing Form: Items table creation error - ' . $wpdb->last_error);
        $success = false;
    }

    return $success;
}

// For backward compatibility, maintain original function name but make it call our new function
function preowned_clothing_create_submission_table($force_recreate = false)
{
    // During regular usage, call the new function
    if (function_exists('_preowned_clothing_create_tables')) {
        return _preowned_clothing_create_tables($force_recreate);
    }

    // During activation, just return success
    return true;
}

/**
 * Migrate data from old single-table to new multi-table structure
 */
function preowned_clothing_migrate_data()
{
    global $wpdb;
    $old_table = $wpdb->prefix . 'preowned_clothing_submissions';
    $new_submissions_table = $wpdb->prefix . 'preowned_clothing_submissions';
    $new_items_table = $wpdb->prefix . 'preowned_clothing_items';

    // Check if migration flag is set
    $migration_done = get_option('preowned_clothing_migration_done', false);
    if ($migration_done) {
        return true;
    }

    // Check if old table exists with the old structure
    $old_table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $old_table)) === $old_table;
    if (!$old_table_exists) {
        update_option('preowned_clothing_migration_done', true);
        return true;
    }

    // Check for the description column to confirm it's the old structure
    $has_description_column = $wpdb->get_var("SHOW COLUMNS FROM `{$old_table}` LIKE 'description'");
    if (!$has_description_column) {
        update_option('preowned_clothing_migration_done', true);
        return true;
    }

    // Get old records
    $old_records = $wpdb->get_results("SELECT * FROM $old_table");

    if (!empty($old_records)) {
        foreach ($old_records as $record) {
            // Insert into new submissions table
            $wpdb->insert(
                $new_submissions_table,
                array(
                    'id' => $record->id,
                    'submission_date' => $record->submission_date,
                    'name' => $record->name,
                    'email' => $record->email,
                    'status' => $record->status ?? 'pending'
                )
            );

            // Insert into new items table
            $wpdb->insert(
                $new_items_table,
                array(
                    'submission_id' => $record->id,
                    'category_level_0' => $record->category_level_0 ?? '',
                    'category_level_1' => $record->category_level_1 ?? '',
                    'category_level_2' => $record->category_level_2 ?? '',
                    'category_level_3' => $record->category_level_3 ?? '',
                    'size' => $record->size ?? '',
                    'description' => $record->description ?? '',
                    'image_front' => $record->image_1 ?? '',
                    'image_back' => $record->image_2 ?? '',
                    'image_brand_tag' => $record->image_3 ?? ''
                )
            );
        }
    }

    update_option('preowned_clothing_migration_done', true);
    return true;
}

// Hook into admin_init to run migration if needed
add_action('admin_init', function () {
    if (current_user_can('manage_options')) {
        preowned_clothing_migrate_data();
    }
});

// Hook to run data migration on admin_init
add_action('admin_init', function () {
    if (isset($_GET['pcf_migrate_data']) && current_user_can('manage_options')) {
        preowned_clothing_migrate_data();
        add_action('admin_notices', function () {
            echo '<div class="notice notice-success"><p>Data migration completed.</p></div>';
        });
    }
});

// Admin commands to manage the database table
function preowned_clothing_maybe_recreate_table()
{
    if (isset($_GET['pcf_recreate_table']) && current_user_can('manage_options')) {
        $force = isset($_GET['force']) && $_GET['force'] == '1';
        $result = $force ?
            preowned_clothing_create_submission_table(true) :
            preowned_clothing_create_submission_table();

        add_action('admin_notices', function () use ($result, $force) {
            $class = $result ? 'notice-success' : 'notice-error';
            $message = $result ?
                'Preowned Clothing Form: ' . ($force ? 'Forced recreation' : 'Update') . ' of database tables completed successfully.' :
                'Preowned Clothing Form: Failed to recreate database tables. Check error logs.';

            echo '<div class="notice ' . $class . '"><p>' . $message . '</p></div>';
        });
    }
}
add_action('admin_init', 'preowned_clothing_maybe_recreate_table');