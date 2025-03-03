<?php
/**
 * Admin notification email template
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; }
        .header { background-color: #0073aa; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background-color: #f9f9f9; }
        .item { background-color: white; padding: 15px; margin-bottom: 10px; border-left: 4px solid #0073aa; }
        .footer { padding: 15px; text-align: center; font-size: 12px; color: #666; }
        .button { display: inline-block; background-color: #0073aa; color: white; padding: 10px 15px; text-decoration: none; border-radius: 3px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>New Clothing Submission</h1>
        </div>
        
        <div class="content">
            <p>A new clothing submission has been received.</p>
            
            <h3>Submission Details</h3>
            <p>
                <strong>Submission ID:</strong> <?php echo $submission_id; ?><br>
                <strong>Name:</strong> <?php echo esc_html($name); ?><br>
                <strong>Email:</strong> <a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a><br>
                <strong>Date:</strong> <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format')); ?><br>
                <strong>Items Submitted:</strong> <?php echo count($items); ?>
            </p>
            
            <h3>Items Summary</h3>
            <?php foreach($items as $index => $item): ?>
                <div class="item">
                    <p><strong>Item <?php echo $index + 1; ?>:</strong></p>
                    
                    <?php 
                    $category_path = array();
                    if (!empty($item['category_level_0'])) $category_path[] = $item['category_level_0'];
                    if (!empty($item['category_level_1'])) $category_path[] = $item['category_level_1'];
                    if (!empty($item['category_level_2'])) $category_path[] = $item['category_level_2'];
                    $category_text = !empty($category_path) ? implode(' > ', $category_path) : 'Not specified';
                    ?>
                    
                    <p><strong>Category:</strong> <?php echo esc_html($category_text); ?></p>
                    
                    <?php if(!empty($item['size'])): ?>
                        <p><strong>Size:</strong> <?php echo esc_html($item['size']); ?></p>
                    <?php endif; ?>
                    
                    <p><strong>Description:</strong><br><?php echo esc_html($item['description']); ?></p>
                </div>
            <?php endforeach; ?>
            
            <p style="margin-top: 20px;">
                <a href="<?php echo admin_url('admin.php?page=clothing-submissions&view=detail&submission_id=' . $submission_id); ?>" class="button">
                    View Submission Details
                </a>
            </p>
        </div>
        
        <div class="footer">
            <p>This is an automated email from the Preowned Clothing Form plugin on <?php echo get_bloginfo('name'); ?>.</p>
        </div>
    </div>
</body>
</html>
