<?php
/**
 * Admin submission detail template
 * 
 * @package PreownedClothingForm
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

$item_count = count($submission_items);

// Get the current item to display first if item_id is specified
$current_item = null;
$item_id = isset($_GET['item_id']) ? intval($_GET['item_id']) : 0;

if ($item_id && $submission_items) {
    foreach ($submission_items as $item) {
        if ($item->id === $item_id) {
            $current_item = $item;
            break;
        }
    }
}

// If no specific item is selected or found, use the first one
if (!$current_item && !empty($submission_items)) {
    $current_item = $submission_items[0];
}
?>

<div class="submission-detail-container">
    <div class="submission-header">
        <div class="submission-title">
            <a href="<?php echo esc_url(admin_url('admin.php?page=clothing-submissions')); ?>" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
            <h2>
                <?php echo esc_html($single_submission->name); ?>'s Submission
                <span class="submission-id">(ID: <?php echo intval($single_submission->id); ?>)</span>
            </h2>
        </div>
        <div class="submission-date">
            <?php echo esc_html(date('F j, Y \a\t g:i a', strtotime($single_submission->submission_date))); ?>
        </div>
    </div>

    <div class="submission-meta">
        <div class="meta-col">
            <h3>Submission Info</h3>
            <div class="meta-item">
                <strong>Name:</strong> <?php echo esc_html($single_submission->name); ?>
            </div>
            <div class="meta-item">
                <strong>Email:</strong> <a href="mailto:<?php echo esc_attr($single_submission->email); ?>"><?php echo esc_html($single_submission->email); ?></a>
            </div>
            <div class="meta-item">
                <strong>Total Items:</strong> <?php echo $item_count; ?>
            </div>
        </div>
        
        <div class="meta-col">
            <h3>Status</h3>
            <div class="status-badge">
                <?php echo preowned_clothing_get_status_label($single_submission->status); ?>
            </div>
        </div>
        
        <div class="meta-col">
            <h3>Notes</h3>
            <div class="notes-field">
                <form method="post">
                    <?php wp_nonce_field('save_submission_notes', 'submission_notes_nonce'); ?>
                    <input type="hidden" name="submission_id" value="<?php echo intval($single_submission->id); ?>">
                    <textarea name="submission_notes" rows="4"><?php echo esc_textarea($single_submission->notes ?? ''); ?></textarea>
                    <button type="submit" name="save_notes" class="button button-primary">
                        Save Notes
                    </button>
                </form>
            </div>
        </div>
    </div>

    <?php if ($item_count > 0): ?>
        <div class="items-nav-container">
            <h3><i class="fas fa-tshirt"></i> Clothing Items (<?php echo $item_count; ?>)</h3>
            <ul class="items-tabs">
                <?php foreach ($submission_items as $index => $item): ?>
                    <li class="item-tab <?php echo ($current_item && $current_item->id === $item->id) ? 'active' : ''; ?>">
                        <a href="#item-<?php echo intval($item->id); ?>" data-item-id="<?php echo intval($item->id); ?>">
                            Item #<?php echo $index + 1; ?>: 
                            <?php
                            if (!empty($item->category_level_0)) {
                                echo esc_html($item->category_level_0);
                                if (!empty($item->category_level_1)) {
                                    echo ' - ' . esc_html($item->category_level_1);
                                }
                            } else {
                                echo 'Uncategorized Item';
                            }
                            ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <?php foreach ($submission_items as $item): ?>
        <div id="item-<?php echo intval($item->id); ?>" class="item-detail-card card" style="display: none;">
            <div class="card-header">
                <h3>
                    <i class="fas fa-tshirt"></i>
                    <?php 
                    $category_path = preowned_clothing_get_category_path($item);
                    if (!empty($category_path)) {
                        echo esc_html($category_path);
                    } else {
                        echo 'Uncategorized Item';
                    }
                    ?>
                </h3>
                <?php if (!empty($item->size)): ?>
                    <div class="item-size-badge">
                        <i class="fas fa-tag"></i> <?php echo esc_html($item->size); ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="card-body item-content">
                <div class="item-description">
                    <h4><i class="fas fa-align-left"></i> Description</h4>
                    <div class="description-text">
                        <?php echo wpautop(esc_html($item->description)); ?>
                    </div>
                </div>
                
                <div class="item-images">
                    <h4><i class="fas fa-images"></i> Images</h4>
                    <div class="image-gallery">
                        <?php
                        $image_fields = array(
                            'image_front' => 'Front',
                            'image_back' => 'Back',
                            'image_brand_tag' => 'Brand Tag',
                            'image_material_tag' => 'Material Tag',
                            'image_detail' => 'Detail'
                        );
                        
                        $has_images = false;
                        
                        foreach ($image_fields as $field => $label) {
                            if (!empty($item->$field)) {
                                $has_images = true;
                                echo '<div class="image-container">';
                                echo '<div class="image-label">' . esc_html($label) . '</div>';
                                echo '<img src="' . esc_url($item->$field) . '" alt="' . esc_attr($label) . '" class="item-image">';
                                echo '</div>';
                            }
                        }
                        
                        if (!$has_images) {
                            echo '<p class="no-images"><i class="fas fa-exclamation-triangle"></i> No images available for this item.</p>';
                        }
                        ?>
                    </div>
                </div>
            </div>
            
            <div class="card-footer">
                <?php
                $delete_item_link = wp_nonce_url(
                    add_query_arg(array(
                        'action' => 'delete_item',
                        'submission_id' => $single_submission->id,
                        'item_id' => $item->id,
                        'view' => 'detail'
                    ), admin_url('admin.php?page=clothing-submissions')),
                    'clothing_submission_action'
                );
                ?>
                <a href="<?php echo esc_url($delete_item_link); ?>" class="button delete-button" 
                   data-confirm="Are you sure you want to delete this item? This cannot be undone.">
                    <i class="fas fa-trash-alt"></i> Delete This Item
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="notice notice-error">
            <p><i class="fas fa-exclamation-circle"></i> No items found for this submission.</p>
        </div>
    <?php endif; ?>
    
    <div class="submission-actions card">
        <div class="card-header">
            <h3><i class="fas fa-cog"></i> Submission Actions</h3>
        </div>
        <div class="card-body">
            <div class="action-buttons">
                <?php
                $email_client_link = 'mailto:' . urlencode($single_submission->email) . 
                    '?subject=' . urlencode('Your Clothing Submission') . 
                    '&body=' . urlencode("Hello " . $single_submission->name . ",\n\nThank you for your clothing submission. ");
                ?>
                <a href="<?php echo esc_url($email_client_link); ?>" class="button button-primary">
                    <i class="fas fa-envelope"></i> Email Customer
                </a>
                
                <?php if ($single_submission->status !== 'completed'): ?>
                    <a href="<?php echo esc_url(wp_nonce_url(add_query_arg(array(
                        'action' => 'mark_completed', 
                        'submission_id' => $single_submission->id, 
                        'view' => 'detail'), admin_url('admin.php?page=clothing-submissions')), 'clothing_submission_action')); ?>" class="button">
                        <i class="fas fa-check-double"></i> Complete Submission
                    </a>
                <?php endif; ?>
                
                <?php 
                $delete_submission_url = wp_nonce_url(
                    add_query_arg(array(
                        'action' => 'delete',
                        'submission_id' => $single_submission->id
                    ), admin_url('admin.php?page=clothing-submissions')),
                    'clothing_submission_action'
                );
                ?>
                <a href="<?php echo esc_url($delete_submission_url); ?>" class="button delete-button" 
                   data-confirm="Are you sure you want to delete this entire submission with all items? This cannot be undone.">
                    <i class="fas fa-trash"></i> Delete Entire Submission
                </a>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Additional submission-specific JavaScript can be added here
    
    // Enhanced email composition
    $('.email-compose-btn').on('click', function() {
        const emailBase = $(this).data('email-base');
        $('#email-to').val(emailBase);
        $('#email-compose-modal').show();
    });
});
</script>
