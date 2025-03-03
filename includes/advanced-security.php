<?php
/**
 * Advanced Security Features
 * 
 * Enhanced security protections for the clothing submission form
 * 
 * @package PreownedClothingForm
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Enhanced honeypot trap to detect bot submissions
 */
class Preowned_Clothing_Bot_Protection {
    /**
     * Initialize bot protection
     */
    public static function init() {
        add_action('preowned_clothing_form_before_submit', array(__CLASS__, 'add_honeypot_field'));
        add_filter('preowned_clothing_validate_submission', array(__CLASS__, 'check_honeypot'), 10, 1);
        add_filter('preowned_clothing_validate_submission', array(__CLASS__, 'check_submission_speed'), 20, 1);
        add_filter('preowned_clothing_validate_submission', array(__CLASS__, 'check_user_agent'), 30, 1);
    }

    /**
     * Add honeypot field to the form
     */
    public static function add_honeypot_field() {
        // Create a timestamp to measure submission speed
        $_SESSION['preowned_form_time'] = time();
        
        // Output honeypot field - hidden from humans but visible to bots
        ?>
        <div class="contact-field-wrapper" style="display:none !important; visibility:hidden !important; opacity:0 !important;">
            <label for="website_hp">Please leave this field empty</label>
            <input type="text" name="website_hp" id="website_hp" tabindex="-1" autocomplete="off">
        </div>
        <?php
    }

    /**
     * Check if honeypot field was filled (indicates a bot)
     * 
     * @param array $validation_result Current validation result
     * @return array Updated validation result
     */
    public static function check_honeypot($validation_result) {
        // If honeypot is filled, it's likely a bot
        if (!empty($_POST['website_hp'])) {
            $validation_result['valid'] = false;
            $validation_result['error'] = 'Bot submission detected';
            
            // Log the bot attempt
            self::log_bot_attempt('Honeypot field filled', $_SERVER['REMOTE_ADDR']);
        }
        return $validation_result;
    }

    /**
     * Check submission speed (too fast indicates a bot)
     * 
     * @param array $validation_result Current validation result
     * @return array Updated validation result
     */
    public static function check_submission_speed($validation_result) {
        // Skip if already invalid
        if (!$validation_result['valid']) {
            return $validation_result;
        }
        
        // Check if submission was made too quickly (less than 3 seconds)
        if (isset($_SESSION['preowned_form_time'])) {
            $elapsed_time = time() - $_SESSION['preowned_form_time'];
            if ($elapsed_time < 3) {
                $validation_result['valid'] = false;
                $validation_result['error'] = 'Submission was too fast';
                
                // Log the bot attempt
                self::log_bot_attempt('Submission too fast: ' . $elapsed_time . ' seconds', $_SERVER['REMOTE_ADDR']);
            }
        }
        return $validation_result;
    }

    /**
     * Check user agent for common bot patterns
     * 
     * @param array $validation_result Current validation result
     * @return array Updated validation result
     */
    public static function check_user_agent($validation_result) {
        // Skip if already invalid
        if (!$validation_result['valid']) {
            return $validation_result;
        }
        
        // Known bot patterns in user agent strings
        $bot_patterns = array(
            'bot', 'spider', 'crawl', 'slurp', 'wget', 'curl', 'fetch', 'apache',
            'empty user agent', 'perl', 'ruby', 'python', 'phantom', 'zgrab', 'zmap',
            'semrush', 'screaming frog', 'ahrefs', 'majestic'
        );
        
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? strtolower($_SERVER['HTTP_USER_AGENT']) : 'empty user agent';
        
        foreach ($bot_patterns as $pattern) {
            if (strpos($user_agent, $pattern) !== false) {
                $validation_result['valid'] = false;
                $validation_result['error'] = 'Suspicious user agent detected';
                
                // Log the bot attempt
                self::log_bot_attempt('Bot pattern in user agent: ' . $pattern, $_SERVER['REMOTE_ADDR'], $user_agent);
                break;
            }
        }
        
        return $validation_result;
    }

    /**
     * Log bot attempt to the security log
     * 
     * @param string $reason Reason for marking as bot
     * @param string $ip IP address
     * @param string $user_agent User agent string
     */
    private static function log_bot_attempt($reason, $ip, $user_agent = '') {
        // Use existing logging function if available
        if (function_exists('preowned_clothing_log_failed_submission')) {
            $data = array(
                'type' => 'bot_attempt',
                'user_agent' => $user_agent
            );
            preowned_clothing_log_failed_submission('Bot detected: ' . $reason, $data);
        }
    }
}
// Initialize bot protection
Preowned_Clothing_Bot_Protection::init();

/**
 * Rate limiting to prevent form submission flooding
 */
class Preowned_Clothing_Rate_Limiter {
    /**
     * Initialize rate limiting
     */
    public static function init() {
        add_filter('preowned_clothing_validate_submission', array(__CLASS__, 'check_rate_limit'), 10, 1);
    }

    /**
     * Check if submission rate limit has been exceeded
     * 
     * @param array $validation_result Current validation result
     * @return array Updated validation result
     */
    public static function check_rate_limit($validation_result) {
        // Skip if already invalid
        if (!$validation_result['valid']) {
            return $validation_result;
        }
        
        $ip = self::get_client_ip();
        $rate_limit = 5; // Maximum 5 submissions per hour
        $time_window = HOUR_IN_SECONDS;
        
        // Get stored rate data
        $rate_data = get_option('preowned_clothing_rate_limits', array());
        
        // Clean up old entries
        $current_time = time();
        foreach ($rate_data as $stored_ip => $submissions) {
            foreach ($submissions as $time => $count) {
                if ($current_time - $time > $time_window) {
                    unset($rate_data[$stored_ip][$time]);
                }
            }
            if (empty($rate_data[$stored_ip])) {
                unset($rate_data[$stored_ip]);
            }
        }
        
        // Count submissions from this IP in the time window
        $submission_count = 0;
        if (isset($rate_data[$ip])) {
            foreach ($rate_data[$ip] as $time => $count) {
                $submission_count += $count;
            }
        }
        
        // Check if limit exceeded
        if ($submission_count >= $rate_limit) {
            $validation_result['valid'] = false;
            $validation_result['error'] = 'Too many submissions. Please try again later.';
            
            // Log the rate limit breach
            if (function_exists('preowned_clothing_log_failed_submission')) {
                preowned_clothing_log_failed_submission('Rate limit exceeded: ' . $submission_count . ' submissions', array('ip' => $ip));
            }
            
            return $validation_result;
        }
        
        // Update rate data
        if (!isset($rate_data[$ip])) {
            $rate_data[$ip] = array();
        }
        $rate_data[$ip][$current_time] = 1;
        update_option('preowned_clothing_rate_limits', $rate_data);
        
        return $validation_result;
    }

    /**
     * Get client IP address accounting for proxies
     * 
     * @return string Client IP address
     */
    private static function get_client_ip() {
        $ip = '';
        
        // Check for proxy headers
        $headers = array(
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        );
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $addresses = explode(',', $_SERVER[$header]);
                $ip = trim($addresses[0]);
                break;
            }
        }
        
        return sanitize_text_field($ip);
    }
}
// Initialize rate limiting
Preowned_Clothing_Rate_Limiter::init();

/**
 * Enhanced malware detection for file uploads
 */
class Preowned_Clothing_Malware_Scanner {
    /**
     * Advanced scan for malicious content in files
     * 
     * @param string $file_path Path to the file
     * @return array Results with status and threats found
     */
    public static function scan_file($file_path) {
        $result = array(
            'status' => 'clean',
            'threats' => array()
        );
        
        if (!file_exists($file_path)) {
            return $result;
        }
        
        // Read the file content - limit to first 1MB for large files
        $fp = fopen($file_path, 'rb');
        $content = fread($fp, 1024 * 1024); // 1MB
        fclose($fp);
        
        // Patterns that indicate malware
        $threat_patterns = array(
            // PHP code patterns
            array(
                'name' => 'PHP code',
                'pattern' => '/(<\?php|<\?=|<\?)/i',
                'severity' => 'critical'
            ),
            array(
                'name' => 'PHP evaluator',
                'pattern' => '/(eval\s*\(|assert\s*\(|create_function\s*\(|call_user_func\s*\()/i',
                'severity' => 'critical'
            ),
            array(
                'name' => 'Base64 code',
                'pattern' => '/(base64_decode|base64_encode|str_rot13)\s*\(/i',
                'severity' => 'high'
            ),
            array(
                'name' => 'Shell commands',
                'pattern' => '/(exec|shell_exec|system|passthru|popen|proc_open|pcntl_exec)\s*\(/i',
                'severity' => 'critical'
            ),
            array(
                'name' => 'File operations',
                'pattern' => '/(file_get_contents|file_put_contents|fopen|readfile|include|require|include_once|require_once)\s*\(/i',
                'severity' => 'high'
            ),
            // JavaScript suspicious code
            array(
                'name' => 'JavaScript eval',
                'pattern' => '/(eval\s*\(|setTimeout\s*\(\s*"|\Weval\W)/i',
                'severity' => 'medium'
            ),
            array(
                'name' => 'JavaScript encoded URL',
                'pattern' => '/(fromCharCode|escape\s*\(|unescape\s*\(|String\.fromCharCode)/i',
                'severity' => 'medium'
            ),
            // Obfuscation techniques
            array(
                'name' => 'Hex encoding',
                'pattern' => '/(\\\x[0-9a-f]{2}){8,}/i', // 8+ hex-encoded chars
                'severity' => 'medium'
            ),
            // Exploits
            array(
                'name' => 'SQL injection',
                'pattern' => '/(UNION\s+SELECT|SELECT.*FROM|INSERT\s+INTO)/i',
                'severity' => 'high'
            ),
            array(
                'name' => 'XSS attack',
                'pattern' => '/(<script[^>]*>|javascript:)/i',
                'severity' => 'high'
            ),
        );
        
        // Check for each threat pattern
        foreach ($threat_patterns as $threat) {
            if (preg_match($threat['pattern'], $content, $matches)) {
                $result['status'] = 'threat_detected';
                $result['threats'][] = array(
                    'name' => $threat['name'],
                    'match' => substr($matches[0], 0, 30) . (strlen($matches[0]) > 30 ? '...' : ''),
                    'severity' => $threat['severity']
                );
            }
        }
        
        // Check file entropy (high entropy often indicates encrypted/obfuscated malware)
        $entropy = self::calculate_file_entropy($content);
        if ($entropy > 7.0) { // Suspicious entropy threshold
            $result['threats'][] = array(
                'name' => 'High entropy content',
                'match' => 'Entropy: ' . number_format($entropy, 2),
                'severity' => 'medium'
            );
            
            if ($result['status'] === 'clean') {
                $result['status'] = 'suspicious';
            }
        }
        
        return $result;
    }
    
    /**
     * Calculate Shannon entropy of content to detect encoded/encrypted payloads
     * 
     * @param string $content File content
     * @return float Entropy value (higher means more random/encrypted data)
     */
    private static function calculate_file_entropy($content) {
        $byteArray = count_chars($content, 1);
        $fileSize = strlen($content);
        $entropy = 0;
        
        foreach ($byteArray as $byte => $count) {
            $probability = $count / $fileSize;
            $entropy -= $probability * log($probability, 2);
        }
        
        return $entropy;
    }
}

/**
 * Security headers for the form pages
 */
function preowned_clothing_security_headers() {
    // Only add these headers on pages with the clothing form
    global $post;
    if (!is_a($post, 'WP_Post') || !has_shortcode($post->post_content, 'preowned_clothing_form')) {
        return;
    }
    
    // Anti-clickjacking
    header('X-Frame-Options: SAMEORIGIN');
    
    // XSS protection
    header('X-XSS-Protection: 1; mode=block');
    header('X-Content-Type-Options: nosniff');
    
    // Comprehensive content security policy with better ES6+ script support
    header("Content-Security-Policy: 
        default-src 'self'; 
        script-src 'self' 'unsafe-inline' 'unsafe-eval' https://ajax.googleapis.com https://fonts.googleapis.com; 
        style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; 
        font-src 'self' data: https://fonts.gstatic.com; 
        img-src 'self' data: blob:; 
        connect-src 'self' https://fonts.googleapis.com; 
        worker-src 'self' blob:;
        child-src 'self' blob:;
        frame-src 'self';
        media-src 'self' blob:;
        object-src 'none';
        form-action 'self';"
    );
    
    // Referrer Policy
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // Permissions Policy - enable camera for mobile uploads
    header('Permissions-Policy: camera=(self), microphone=(), geolocation=()');
}
add_action('send_headers', 'preowned_clothing_security_headers');

/**
 * Modify file upload handling to include advanced malware scanning
 */
function preowned_clothing_enhanced_file_validation($file) {
    // First run the basic security check
    $basic_validation = preowned_clothing_validate_image($file);
    if ($basic_validation !== true) {
        return $basic_validation;
    }
    
    // Now run advanced malware scan
    $scan_result = Preowned_Clothing_Malware_Scanner::scan_file($file['tmp_name']);
    
    if ($scan_result['status'] === 'threat_detected') {
        // Log the security incident
        if (function_exists('preowned_clothing_log_failed_submission')) {
            preowned_clothing_log_failed_submission(
                'Malware detected in upload',
                array('threats' => $scan_result['threats'], 'filename' => $file['name'])
            );
        }
        
        // Return error message
        return 'Security threat detected in the file. Upload rejected.';
    }
    
    if ($scan_result['status'] === 'suspicious') {
        // Log the suspicious file but allow it if it passed basic validation
        if (function_exists('preowned_clothing_log_failed_submission')) {
            preowned_clothing_log_failed_submission(
                'Suspicious content in upload, but allowed',
                array('threats' => $scan_result['threats'], 'filename' => $file['name'])
            );
        }
    }
    
    return true;
}
// Replace the standard validation with enhanced validation
add_filter('preowned_clothing_file_validation', 'preowned_clothing_enhanced_file_validation');

/**
 * Prevent WordPress account enumeration attacks
 */
function preowned_clothing_prevent_enumeration($redirect, $path) {
    // Block user enumeration attempts
    if (preg_match('/^\/?author=([0-9]*)/', $path, $matches)) {
        // Log the attempt
        if (function_exists('preowned_clothing_log_failed_submission')) {
            preowned_clothing_log_failed_submission('User enumeration attempt blocked', 
                array('path' => $path, 'ip' => $_SERVER['REMOTE_ADDR'])
            );
        }
        
        // Redirect to home
        return home_url();
    }
    return $redirect;
}
add_filter('redirect_canonical', 'preowned_clothing_prevent_enumeration', 10, 2);
