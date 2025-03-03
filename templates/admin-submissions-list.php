<?php
/**
 * Admin submissions list template
 * 
 * @package PreownedClothingForm
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;
?>

<div class="submissions-list-container">
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-filter"></i> Filter Submissions</h2>
        </div>
        <div class="card-body">
            <form method="get">
                <input type="hidden" name="page" value="clothing-submissions">
                
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="s">Search:</label>
                        <input type="search" id="s" name="s" value="<?php echo esc_attr($search_term); ?>" placeholder="Search by name, email or description">
                    </div>
                    
                    <div class="filter-group">
                        <label for="status">Status:</label>
                        <select id="status" name="status">
                            <option value="">All Statuses</option>
                            <option value="pending" <?php selected($status_filter, 'pending'); ?>>Pending</option>
                            <option value="contacted" <?php selected($status_filter, 'contacted'); ?>>Contacted</option>
                            <option value="completed" <?php selected($status_filter, 'completed'); ?>>Completed</option>
                        </select>
                    </div>
                    
                    <?php if (!empty($available_categories)): ?>
                    <div class="filter-group">
                        <label for="category">Category:</label>
                        <select id="category" name="category">
                            <option value="">All Categories</option>
                            <?php foreach ($available_categories as $category): ?>
                                <option value="<?php echo esc_attr($category); ?>" <?php selected($category_filter, $category); ?>><?php echo esc_html($category); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    
                    <div class="filter-buttons">
                        <button type="submit" class="button button-primary">
                            <i class="fas fa-search"></i> Apply Filters
                        </button>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=clothing-submissions')); ?>" class="button">
                            <i class="fas fa-times"></i> Clear Filters
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <form method="post">
        <?php wp_nonce_field('clothing_submissions_bulk_action', '_wpnonce'); ?>
        
        <div class="tablenav top">
            <div class="alignleft actions bulkactions">
                <select name="bulk_action">
                    <option value="">Bulk Actions</option>
                    <option value="mark_contacted">Mark as Contacted</option>
                    <option value="mark_completed">Mark as Completed</option>
                    <option value="delete">Delete</option>
                </select>
                <button type="submit" class="button action">Apply</button>
            </div>
            
            <div class="tablenav-pages">
                <?php
                // Display pagination
                $page_links = paginate_links(array(
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '',
                    'prev_text' => '&laquo;',
                    'next_text' => '&raquo;',
                    'total' => $total_pages,
                    'current' => $current_page
                ));
                
                if ($page_links) {
                    echo '<span class="displaying-num">' . sprintf(_n('%s item', '%s items', $total), number_format_i18n($total)) . '</span>';
                    echo $page_links;
                }
                ?>
            </div>
        </div>
        
        <table class="wp-list-table widefat fixed striped submissions-table">
            <thead>
                <tr>
                    <th class="check-column"><input type="checkbox" id="cb-select-all-1"></th>
                    <th class="column-primary">Customer</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Items</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($submissions)): ?>
                    <?php foreach ($submissions as $submission): ?>
                        <tr>
                            <td class="check-column">
                                <input type="checkbox" name="submission_ids[]" value="<?php echo esc_attr($submission->id); ?>">
                            </td>
                            <td class="column-primary">
                                <strong><?php echo esc_html($submission->name); ?></strong>
                                <div class="row-actions">
                                    <span><a href="mailto:<?php echo esc_attr($submission->email); ?>"><?php echo esc_html($submission->email); ?></a></span>
                                </div>
                                <button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button>
                            </td>
                            <td data-colname="Date">
                                <?php echo esc_html(date('M j, Y', strtotime($submission->submission_date))); ?>
                                <br>
                                <small><?php echo esc_html(date('g:i a', strtotime($submission->submission_date))); ?></small>
                            </td>
                            <td data-colname="Status">
                                <?php echo preowned_clothing_get_status_label($submission->status); ?>
                            </td>
                            <td data-colname="Items" class="item-count-cell">
                                <div class="item-count"><?php echo intval($submission->item_count); ?></div>
                                <?php if (!empty($submission->primary_category)): ?>
                                <div class="item-type-indicator"><?php echo esc_html($submission->primary_category); ?></div>
                                <?php endif; ?>
                            </td>
                            <td data-colname="Actions" class="actions-cell">
                                <div class="action-buttons">
                                    <a href="<?php echo esc_url(add_query_arg(array('view' => 'detail', 'submission_id' => $submission->id), admin_url('admin.php?page=clothing-submissions'))); ?>" class="button">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <a href="<?php echo esc_url(wp_nonce_url(add_query_arg(array('action' => 'delete', 'submission_id' => $submission->id), admin_url('admin.php?page=clothing-submissions')), 'clothing_submission_action')); ?>" class="button delete-button" onclick="return confirm('Are you sure you want to delete this submission?');">
                                        <i class="fas fa-trash-alt"></i> Delete
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6">No submissions found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th class="check-column"><input type="checkbox" id="cb-select-all-2"></th>
                    <th class="column-primary">Customer</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Items</th>
                    <th>Actions</th>
                </tr>
            </tfoot>
        </table>
        
        <div class="tablenav bottom">
            <div class="alignleft actions bulkactions">
                <select name="bulk_action2">
                    <option value="">Bulk Actions</option>
                    <option value="mark_contacted">Mark as Contacted</option>
                    <option value="mark_completed">Mark as Completed</option>
                    <option value="delete">Delete</option>
                </select>
                <button type="submit" class="button action">Apply</button>
            </div>
            
            <div class="tablenav-pages">
                <?php
                // Display pagination again
                if ($page_links) {
                    echo '<span class="displaying-num">' . sprintf(_n('%s item', '%s items', $total), number_format_i18n($total)) . '</span>';
                    echo $page_links;
                }
                ?>
            </div>
        </div>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    // Add icons to status labels
    $('.status-pending').prepend('<i class="fas fa-clock"></i> ');
    $('.status-contacted').prepend('<i class="fas fa-phone-alt"></i> ');
    $('.status-completed').prepend('<i class="fas fa-check-circle"></i> ');
    
    // Checkbox select all functionality
    $('#cb-select-all-1, #cb-select-all-2').on('click', function() {
        $('input[name="submission_ids[]"]').prop('checked', $(this).prop('checked'));
    });
});
</script>
