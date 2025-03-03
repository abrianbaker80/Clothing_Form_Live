<?php
/**
 * Email Notifications
 *
 * Handles email notifications for clothing submissions
 *
 * @package PreownedClothingForm
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Send notification emails when new submissions arrive
 *
 * @param int $submission_id The submission ID
 * @param array $submission_data The submission data
 * @param array $items The items data
 * @return bool Whether the email was sent successfully
 */
function preowned_clothing_send_notification_email($submission_id, $submission_data, $items) {
    // Check if notifications are enabled
    if (get_option('preowned_clothing_enable_notifications', '1') !== '1') {
        return false;
    }
    
    // Get notification recipient
    $to = get_option('preowned_clothing_notification_email', get_option('admin_email'));
    if (empty($to)) {
        return false;
    }
    
    // Build email subject
    $subject = sprintf(
        '[%s] New Clothing Submission from %s', 
        get_bloginfo('name'), 
        sanitize_text_field($submission_data['name'])
    );
    
    // Extract variables for template
    $name = sanitize_text_field($submission_data['name']);
    $email = sanitize_email($submission_data['email']);
    
    // Get template file path
    $template_path = plugin_dir_path(dirname(__FILE__)) . 'templates/emails/admin-notification.php';
    
    // Fall back to generating the message if template doesn't exist
    if (file_exists($template_path)) {
        ob_start();
        include $template_path;
        $message = ob_get_clean();
    } else {
        // Start building email content
        $message = sprintf(
            'Hello,<br><br>A new clothing submission has been received.<br><br>
            <strong>Submission ID:</strong> %d<br>
            <strong>Name:</strong> %s<br>
            <strong>Email:</strong> <a href="mailto:%s">%s</a><br>
            <strong>Date:</strong> %s<br><br>',
            $submission_id,
            sanitize_text_field($submission_data['name']),
            sanitize_email($submission_data['email']),
            sanitize_email($submission_data['email']),
            date_i18n(get_option('date_format') . ' ' . get_option('time_format'))
        );
        
        // Add items summary
        $message .= sprintf('<strong>Number of items submitted:</strong> %d<br><br>', count($items));
        
        if (!empty($items)) {
            $message .= '<h3>Items Summary:</h3><ul>';
            
            foreach ($items as $index => $item) {
                $category_parts = array();
                if (!empty($item['category_level_0'])) $category_parts[] = $item['category_level_0'];
                if (!empty($item['category_level_1'])) $category_parts[] = $item['category_level_1'];
                if (!empty($item['category_level_2'])) $category_parts[] = $item['category_level_2'];
                
                $category_text = !empty($category_parts) ? implode(' > ', $category_parts) : 'Not specified';
                
                $message .= sprintf(
                    '<li><strong>Item %d:</strong> %s - %s</li>',
                    $index + 1,
                    $category_text,
                    !empty($item['size']) ? $item['size'] : 'Size not specified'
                );
            }
            
            $message .= '</ul>';
        }
        
        // Add admin link
        $admin_url = admin_url('admin.php?page=clothing-submissions&view=detail&submission_id=' . $submission_id);
        $message .= sprintf(
            '<p><a href="%s" style="display:inline-block;background:#0073aa;color:white;padding:10px 15px;text-decoration:none;border-radius:3px;">View Submission Details</a></p>',
            esc_url($admin_url)
        );
        
        // Email footer
        $message .= sprintf(
            '<br><hr><p>This email was sent from <a href="%s">%s</a></p>',
            get_bloginfo('url'),
            get_bloginfo('name')
        );
    }
    
    // Email headers
    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
    );
    
    // Send the email
    $result = wp_mail($to, $subject, $message, $headers);
    
    // Log email sending result if debugging is enabled
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('Preowned Clothing Form - Notification email sent to ' . $to . ': ' . ($result ? 'success' : 'failed'));
    }
    
    return $result;
}

/**
 * Send confirmation email to customer
 *
 * @param int $submission_id The submission ID
 * @param string $customer_email The customer's email address
 * @param string $customer_name The customer's name
 * @param int $item_count The number of items submitted
 * @return bool Whether the email was sent successfully
 */
function preowned_clothing_send_confirmation_email($submission_id, $customer_email, $customer_name, $item_count) {
    // Get confirmation email settings
    $send_confirmation = get_option('preowned_clothing_send_confirmation', '1');
    if ($send_confirmation !== '1' || empty($customer_email)) {
        return false;
    }
    
    // Build email subject
    $subject = sprintf(
        '[%s] We\'ve Received Your Clothing Submission', 
        get_bloginfo('name')
    );
    
    // Get template file path
    $template_path = plugin_dir_path(dirname(__FILE__)) . 'templates/emails/customer-confirmation.php';
    
    // Generate email content
    if (file_exists($template_path)) {
        ob_start();
        include $template_path;
        $message = ob_get_clean();
    } else {
        // Email content
        $message = sprintf(
            'Hello %s,<br><br>
            <p>Thank you for submitting your clothing items to %s. We have received your submission and will review it shortly.</p>
            
            <p><strong>Submission Summary:</strong><br>
            Submission ID: %d<br>
            Items Submitted: %d<br>
            Date: %s</p>
            
            <p>Our team will review your submission within 24-48 hours and contact you with next steps.</p>
            
            <p>If you have any questions in the meantime, please reply to this email.</p>
            
            <p>Thank you,<br>
            The team at %s</p>',
            sanitize_text_field($customer_name),
            get_bloginfo('name'),
            $submission_id,
            $item_count,
            date_i18n(get_option('date_format') . ' ' . get_option('time_format')),
            get_bloginfo('name')
        );
    }
    
    // Email headers
    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
    );
    
    // Send the email
    $result = wp_mail($customer_email, $subject, $message, $headers);
    
    // Log if debugging is enabled
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('Preowned Clothing Form - Customer confirmation sent to ' . $customer_email . ': ' . ($result ? 'success' : 'failed'));
    }
    
    return $result;
}

/**
 * Hook into form submission to send notifications
 */
add_action('preowned_clothing_after_submission', 'preowned_clothing_handle_submission_emails', 10, 3);
function preowned_clothing_handle_submission_emails($submission_id, $submission_data, $items) {
    // Send admin notification
    preowned_clothing_send_notification_email($submission_id, $submission_data, $items);
    
    // Send customer confirmation
    if (!empty($submission_data['email']) && !empty($submission_data['name'])) {
        preowned_clothing_send_confirmation_email(
            $submission_id, 
            $submission_data['email'], 
            $submission_data['name'], 
            count($items)
        );
    }
}

/**
 * Add settings for email notifications
 */
function preowned_clothing_add_email_settings($settings) {
    $email_settings = array(
        'notification_email' => array(
            'title' => 'Notification Email',
            'description' => 'Email address where submission notifications will be sent.',
            'type' => 'email',
            'default' => get_option('admin_email'),
            'section' => 'notifications',
        ),
        
        'enable_notifications' => array(
            'title' => 'Enable Admin Notifications',
            'description' => 'Send email notifications when new submissions arrive.',
            'type' => 'checkbox',
            'default' => '1',
            'section' => 'notifications',
        ),
        
        'send_confirmation' => array(
            'title' => 'Send Customer Confirmations',
            'description' => 'Send confirmation emails to customers after they submit the form.',
            'type' => 'checkbox',
            'default' => '1',
            'section' => 'notifications',
        ),
        
        'admin_email_subject' => array(
            'title' => 'Admin Email Subject',
            'description' => 'Subject line for admin notification emails. Use {name} for submitter\'s name.',
            'type' => 'text',
            'default' => '[{site_name}] New Clothing Submission from {name}',
            'section' => 'notifications',
        ),
        
        'customer_email_subject' => array(
            'title' => 'Customer Email Subject',
            'description' => 'Subject line for customer confirmation emails.',
            'type' => 'text',
            'default' => '[{site_name}] We\'ve Received Your Clothing Submission',
            'section' => 'notifications',
        )
    );
    
    return array_merge($settings, $email_settings);
}
add_filter('preowned_clothing_settings_fields', 'preowned_clothing_add_email_settings');
