<?php
/**
 * GitHub API Handler
 *
 * Handles all communication with the GitHub API
 *
 * @package PreownedClothingForm
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * GitHub API Class
 */
class Preowned_Clothing_GitHub_API {
    /**
     * GitHub username
     *
     * @var string
     */
    private $username;
    
    /**
     * GitHub repository name
     *
     * @var string
     */
    private $repository;
    
    /**
     * GitHub personal access token
     *
     * @var string
     */
    private $token;
    
    /**
     * Debug mode flag
     *
     * @var boolean
     */
    private $debug_mode;
    
    /**
     * Constructor
     *
     * @param string  $username    GitHub username
     * @param string  $repository  GitHub repository name
     * @param string  $token       Optional GitHub personal access token
     * @param boolean $debug_mode  Whether to enable debug logging
     */
    public function __construct($username, $repository, $token = '', $debug_mode = false) {
        $this->username = $username;
        $this->repository = $repository;
        $this->token = $token;
        $this->debug_mode = $debug_mode;
    }
    
    /**
     * Get the latest release from GitHub
     *
     * @param boolean $force_refresh Whether to bypass any caching
     * @return array|WP_Error Response data or error
     */
    public function get_latest_release($force_refresh = false) {
        // Check transient cache unless forcing refresh
        if (!$force_refresh) {
            $cached = get_transient('preowned_clothing_github_release_' . $this->username . '_' . $this->repository);
            if ($cached) {
                if ($this->debug_mode) {
                    error_log('GitHub API: Using cached release data');
                }
                return $cached;
            }
        }

        $url = $this->get_api_url('releases/latest');
        $args = $this->get_request_args();
        
        if ($this->debug_mode) {
            error_log("GitHub API: Making request to $url");
        }
        
        $response = $this->make_api_request($url, $args);
        
        // Check for errors
        if (is_wp_error($response)) {
            if ($this->debug_mode) {
                error_log('GitHub API Error: ' . $response->get_error_message());
            }
            return $response;
        }
        
        // Cache successful responses for 1 hour
        set_transient('preowned_clothing_github_release_' . $this->username . '_' . $this->repository, $response, HOUR_IN_SECONDS);
        
        return $response;
    }
    
    /**
     * Check if the repository exists
     *
     * @return array|WP_Error Response data or error 
     */
    public function check_repository() {
        $url = $this->get_api_url();
        $args = $this->get_request_args();
        
        if ($this->debug_mode) {
            error_log("GitHub API: Checking repository at $url");
        }
        
        return $this->make_api_request($url, $args);
    }
    
    /**
     * Get all releases
     *
     * @return array|WP_Error Response data or error
     */
    public function get_all_releases() {
        $url = $this->get_api_url('releases');
        $args = $this->get_request_args();
        
        if ($this->debug_mode) {
            error_log("GitHub API: Getting all releases from $url");
        }
        
        return $this->make_api_request($url, $args);
    }
    
    /**
     * Get a specific release by tag name
     *
     * @param string $tag_name The release tag name
     * @return array|WP_Error Response data or error
     */
    public function get_release_by_tag($tag_name) {
        $url = $this->get_api_url('releases/tags/' . $tag_name);
        $args = $this->get_request_args();
        
        if ($this->debug_mode) {
            error_log("GitHub API: Getting release by tag $tag_name");
        }
        
        return $this->make_api_request($url, $args);
    }
    
    /**
     * Download a specific release asset
     *
     * @param string $url The download URL
     * @return string|WP_Error Path to downloaded file or error
     */
    public function download_release($url) {
        // If we have a token and this is a GitHub URL, add it
        if ($this->token && strpos($url, 'github.com') !== false) {
            $url = add_query_arg('access_token', $this->token, $url);
        }
        
        if ($this->debug_mode) {
            error_log("GitHub API: Downloading release from $url");
        }
        
        $download_file = download_url($url);
        
        if (is_wp_error($download_file)) {
            if ($this->debug_mode) {
                error_log('GitHub API: Download failed: ' . $download_file->get_error_message());
            }
        }
        
        return $download_file;
    }
    
    /**
     * Get API URL for the repository or endpoint
     *
     * @param string $endpoint Optional endpoint to append to the base URL
     * @return string Full API URL
     */
    private function get_api_url($endpoint = '') {
        $base_url = "https://api.github.com/repos/{$this->username}/{$this->repository}";
        
        if (!empty($endpoint)) {
            return trailingslashit($base_url) . ltrim($endpoint, '/');
        }
        
        return $base_url;
    }
    
    /**
     * Get common request arguments for API calls
     *
     * @return array Request arguments
     */
    private function get_request_args() {
        $args = array(
            'headers' => array(
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'WordPress/' . get_bloginfo('version') . '; ' . get_bloginfo('url')
            ),
            'timeout' => 15, // Increased timeout to prevent failures
        );
        
        // Add authorization if token exists
        if (!empty($this->token)) {
            $args['headers']['Authorization'] = "token {$this->token}";
        }
        
        return $args;
    }
    
    /**
     * Make API request and handle common response processing
     *
     * @param string $url  API URL
     * @param array  $args Request arguments
     * @return array|WP_Error Processed response data or error
     */
    private function make_api_request($url, $args) {
        // Make the request
        $response = wp_remote_get($url, $args);
        
        // Check for connection errors
        if (is_wp_error($response)) {
            return $response;
        }
        
        // Process the response
        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        // Log rate limit info in debug mode
        if ($this->debug_mode) {
            $this->log_rate_limit_info($response);
        }
        
        // Handle error responses
        if ($response_code < 200 || $response_code >= 300) {
            $error_message = "GitHub API Error: HTTP $response_code";
            $error_data = array(
                'response_code' => $response_code,
                'body' => $body,
                'headers' => wp_remote_retrieve_headers($response),
                'url' => $url
            );
            
            return new WP_Error('github_api_error', $error_message, $error_data);
        }
        
        // Decode JSON response
        $data = json_decode($body, true);
        
        // Check for JSON decode errors
        if (json_last_error() !== JSON_ERROR_NONE) {
            $error_message = 'GitHub API: JSON decode error: ' . json_last_error_msg();
            $error_data = array(
                'json_error' => json_last_error(),
                'body' => $body
            );
            
            return new WP_Error('github_api_json_error', $error_message, $error_data);
        }
        
        // Check for empty response
        if (empty($data)) {
            return new WP_Error('github_api_empty', 'GitHub API returned empty response');
        }
        
        return $data;
    }
    
    /**
     * Log GitHub API rate limit information
     *
     * @param array $response The API response
     */
    private function log_rate_limit_info($response) {
        $headers = wp_remote_retrieve_headers($response);
        $rate_limit = isset($headers['x-ratelimit-limit']) ? $headers['x-ratelimit-limit'] : 'Unknown';
        $rate_remaining = isset($headers['x-ratelimit-remaining']) ? $headers['x-ratelimit-remaining'] : 'Unknown';
        $rate_reset = isset($headers['x-ratelimit-reset']) ? $headers['x-ratelimit-reset'] : 'Unknown';
        
        if ($rate_reset && is_numeric($rate_reset)) {
            $rate_reset_time = date('Y-m-d H:i:s', (int)$rate_reset);
            error_log("GitHub API: Rate Limit: $rate_limit, Remaining: $rate_remaining, Reset: $rate_reset_time");
        } else {
            error_log("GitHub API: Rate Limit: $rate_limit, Remaining: $rate_remaining, Reset: Unknown");
        }
        
        // Warn if rate limit is getting low
        if (is_numeric($rate_remaining) && (int)$rate_remaining < 10) {
            error_log("GitHub API: WARNING - Rate limit getting low: $rate_remaining requests remaining!");
        }
    }
    
    /**
     * Format a version string by removing the 'v' prefix if present
     * and ensuring it's a valid version format
     *
     * @param string $version Version string possibly with 'v' prefix
     * @return string Cleaned version string
     */
    public function format_version($version) {
        // Remove 'v' prefix if present
        $version = ltrim($version, 'v');
        
        // Ensure it has the correct format (x.y.z)
        if ($this->debug_mode) {
            error_log('GitHub API: Formatting version: ' . $version);
        }
        
        // Validate version format
        if (!preg_match('/^\d+(\.\d+)*$/', $version)) {
            if ($this->debug_mode) {
                error_log('GitHub API: Warning - Version does not match standard format: ' . $version);
            }
        }
        
        return $version;
    }
    
    /**
     * Extract release details from response data
     *
     * @param array $release_data Release data from GitHub API
     * @return array Simplified release details
     */
    public function extract_release_details($release_data) {
        if (empty($release_data) || !is_array($release_data)) {
            if ($this->debug_mode) {
                error_log('GitHub API: Empty or invalid release data received');
            }
            return array();
        }
        
        $tag_name = isset($release_data['tag_name']) ? $release_data['tag_name'] : '';
        $version = $this->format_version($tag_name);
        
        if ($this->debug_mode) {
            error_log('GitHub API: Extracted tag name: ' . $tag_name . ', formatted version: ' . $version);
        }
        
        $details = array(
            'version' => $version,
            'tag_name' => $tag_name, // Keep original tag name
            'name' => isset($release_data['name']) ? $release_data['name'] : '',
            'published_at' => isset($release_data['published_at']) ? $release_data['published_at'] : '',
            'zipball_url' => isset($release_data['zipball_url']) ? $release_data['zipball_url'] : '',
            'tarball_url' => isset($release_data['tarball_url']) ? $release_data['tarball_url'] : '',
            'body' => isset($release_data['body']) ? $release_data['body'] : '',
            'prerelease' => isset($release_data['prerelease']) ? $release_data['prerelease'] : false,
        );
        
        // Extract additional metadata from release body
        $details = array_merge($details, $this->extract_metadata_from_body($details['body']));
        
        return $details;
    }
    
    /**
     * Extract metadata from release body
     *
     * @param string $body Release body content
     * @return array Extracted metadata
     */
    private function extract_metadata_from_body($body) {
        $metadata = array();
        
        // Extract WordPress compatibility info
        if (preg_match('/Tested up to:\s*([0-9.]+)/i', $body, $matches)) {
            $metadata['tested'] = $matches[1];
        }
        
        // Extract PHP requirements
        if (preg_match('/Requires PHP:\s*([0-9.]+)/i', $body, $matches)) {
            $metadata['requires_php'] = $matches[1];
        }
        
        // Extract WordPress requirements
        if (preg_match('/Requires WordPress:\s*([0-9.]+)/i', $body, $matches)) {
            $metadata['requires'] = $matches[1];
        }
        
        return $metadata;
    }
    
    /**
     * Check if update is available
     *
     * @param string $current_version Current plugin version
     * @return bool|array False if no update, or array of update info
     */
    public function check_for_update($current_version) {
        if ($this->debug_mode) {
            error_log('GitHub API: Checking for update - current version: ' . $current_version);
        }
        
        $release = $this->get_latest_release(true); // Force refresh to ensure latest data
        
        // Return false if we have an error
        if (is_wp_error($release)) {
            if ($this->debug_mode) {
                error_log('GitHub API: Error getting release: ' . $release->get_error_message());
            }
            return false;
        }
        
        // Extract release details
        $details = $this->extract_release_details($release);
        
        if (empty($details['version'])) {
            if ($this->debug_mode) {
                error_log('GitHub API: Empty version in release details');
            }
            return false;
        }
        
        // Check if this is a newer version
        $is_newer = version_compare($details['version'], $current_version, '>');
        
        if ($this->debug_mode) {
            error_log('GitHub API: Version comparison - GitHub: ' . $details['version'] . 
                     ', Current: ' . $current_version . ', Is newer: ' . ($is_newer ? 'Yes' : 'No'));
        }
        
        if (!$is_newer) {
            return false;
        }
        
        return $details;
    }
}
