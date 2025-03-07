<?php
/**
 * Database operations for Preowned Clothing Form
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// We no longer need to define the function here as it's in database-setup.php

/**
 * Get all submissions from the database
 * @param int $limit Number of submissions to return
 * @param int $offset Offset for pagination
 * @param string $status Filter by status (optional)
 * @return array Array of submissions
 */
function preowned_clothing_get_submissions($limit = 20, $offset = 0, $status = null)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'preowned_clothing_submissions';

    // Make sure table exists
    if (function_exists('preowned_clothing_check_table')) {
        preowned_clothing_check_table();
    }

    $sql = "SELECT * FROM $table_name";

    if ($status) {
        $sql .= $wpdb->prepare(" WHERE status = %s", $status);
    }

    $sql .= " ORDER BY submission_date DESC";
    $sql .= $wpdb->prepare(" LIMIT %d OFFSET %d", $limit, $offset);

    $results = $wpdb->get_results($sql, ARRAY_A);
    return $results ? $results : array();
}

/**
 * Count total submissions
 * @param string $status Filter by status (optional)
 * @return int Number of submissions
 */
function preowned_clothing_count_submissions($status = null)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'preowned_clothing_submissions';

    // Make sure table exists
    if (function_exists('preowned_clothing_check_table')) {
        preowned_clothing_check_table();
    }

    $sql = "SELECT COUNT(*) FROM $table_name";

    if ($status) {
        $sql .= $wpdb->prepare(" WHERE status = %s", $status);
    }

    return (int) $wpdb->get_var($sql);
}
