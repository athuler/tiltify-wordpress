<?php
/**
 * Tiltify API handler class
 * Manages all communication with Tiltify API v5
 *
 * @package TiltifyWordPress
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles all Tiltify API interactions
 */
class Tiltify_API {

    /**
     * Tiltify API base URL
     */
    private $api_base = 'https://v5api.tiltify.com';

    /**
     * Client ID for authentication
     */
    private $client_id;

    /**
     * Client Secret for authentication
     */
    private $client_secret;

    /**
     * Access token (obtained via OAuth)
     */
    private $access_token;

    /**
     * Cache duration in seconds (default 5 minutes)
     */
    private $cache_duration;

    /**
     * Constructor
     */
    public function __construct() {
        $this->client_id = get_option('tiltify_client_id', '');
        $this->client_secret = get_option('tiltify_client_secret', '');
        $this->access_token = null;
        $this->cache_duration = get_option('tiltify_cache_duration', 300);
    }

    /**
     * Get campaign data from Tiltify API
     *
     * @param string $campaign_id Campaign ID
     * @return array|WP_Error Campaign data or error
     */
    public function get_campaign_data($campaign_id = null) {
        if (empty($campaign_id)) {
            $campaign_id = get_option('tiltify_campaign_id', '');
        }

        if (empty($campaign_id)) {
            return new WP_Error('no_campaign_id', __('Campaign ID is required', TILTIFY_INTEGRATION_TEXT_DOMAIN));
        }

        // Check cache first
        $cache_key = 'tiltify_campaign_' . $campaign_id;
        $cached_data = get_transient($cache_key);

        if (false !== $cached_data) {
            return $cached_data;
        }

        // Make API request
        $endpoint = "/api/public/campaigns/{$campaign_id}";
        $response = $this->make_request($endpoint);

        if (is_wp_error($response)) {
            return $response;
        }

        // Process and cache the data
        $campaign_data = $this->process_campaign_data($response);
        set_transient($cache_key, $campaign_data, $this->cache_duration);

        return $campaign_data;
    }

    /**
     * Get recent donations for a campaign
     *
     * @param string $campaign_id Campaign ID
     * @param int $limit Number of donations to retrieve (max 100)
     * @return array|WP_Error Donations data or error
     */
    public function get_recent_donations($campaign_id = null, $limit = 10) {
        if (empty($campaign_id)) {
            $campaign_id = get_option('tiltify_campaign_id', '');
        }

        if (empty($campaign_id)) {
            return new WP_Error('no_campaign_id', __('Campaign ID is required', TILTIFY_INTEGRATION_TEXT_DOMAIN));
        }

        $limit = min(max(1, intval($limit)), 100); // Ensure limit is between 1 and 100

        // Check cache first
        $cache_key = 'tiltify_donations_' . $campaign_id . '_' . $limit;
        $cached_data = get_transient($cache_key);

        if (false !== $cached_data) {
            return $cached_data;
        }

        // Make API request
        $endpoint = "/api/public/campaigns/{$campaign_id}/donations";
        $params = array('count' => $limit);
        $response = $this->make_request($endpoint, $params);

        if (is_wp_error($response)) {
            return $response;
        }

        // Process and cache the data
        $donations_data = $this->process_donations_data($response);
        set_transient($cache_key, $donations_data, $this->cache_duration);

        return $donations_data;
    }

    /**
     * Test API connection with provided credentials
     *
     * @param string $api_token API token to test
     * @param string $campaign_id Campaign ID to test
     * @return array Result of connection test
     */
    public function test_connection($client_id = null, $client_secret = null, $campaign_id = null) {
        $original_client_id = $this->client_id;
        $original_client_secret = $this->client_secret;

        if ($client_id !== null) {
            $this->client_id = $client_id;
        }
        if ($client_secret !== null) {
            $this->client_secret = $client_secret;
        }

        if (empty($campaign_id)) {
            $campaign_id = get_option('tiltify_campaign_id', '');
        }

        if (empty($campaign_id)) {
            return array(
                'success' => false,
                'message' => __('Campaign ID is required', TILTIFY_INTEGRATION_TEXT_DOMAIN)
            );
        }

        // Try to fetch campaign data
        $endpoint = "/api/public/campaigns/{$campaign_id}";
        $response = $this->make_request($endpoint, array(), false); // Don't cache test requests

        // Restore original credentials
        $this->client_id = $original_client_id;
        $this->client_secret = $original_client_secret;

        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => $response->get_error_message()
            );
        }

        return array(
            'success' => true,
            'message' => __('Connection successful!', TILTIFY_INTEGRATION_TEXT_DOMAIN),
            'campaign_name' => isset($response['data']['name']) ? $response['data']['name'] : __('Unknown Campaign', TILTIFY_INTEGRATION_TEXT_DOMAIN)
        );
    }

    /**
     * Make HTTP request to Tiltify API
     *
     * @param string $endpoint API endpoint
     * @param array $params Query parameters
     * @param bool $use_cache Whether to use caching
     * @return array|WP_Error API response or error
     */
    private function make_request($endpoint, $params = array(), $use_cache = true) {
        $url = $this->api_base . $endpoint;

        if (!empty($params)) {
            $url = add_query_arg($params, $url);
        }

        $args = array(
            'timeout' => 15,
            'headers' => array(
                'Accept' => 'application/json',
                'User-Agent' => 'Tiltify-WordPress-Plugin/' . TILTIFY_INTEGRATION_VERSION
            )
        );

        // Add authorization header if credentials are available
        // For public campaigns, authentication is optional
        if (!empty($this->access_token)) {
            $args['headers']['Authorization'] = 'Bearer ' . $this->access_token;
        }

        // Make the request
        $response = wp_remote_get($url, $args);

        if (is_wp_error($response)) {
            return new WP_Error('api_request_failed', __('Failed to connect to Tiltify API', TILTIFY_INTEGRATION_TEXT_DOMAIN));
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        if ($response_code !== 200) {
            $error_message = $this->get_error_message($response_code, $body);
            return new WP_Error('api_error', $error_message);
        }

        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('json_decode_error', __('Invalid JSON response from Tiltify API', TILTIFY_INTEGRATION_TEXT_DOMAIN));
        }

        return $data;
    }

    /**
     * Process campaign data from API response
     *
     * @param array $response API response
     * @return array Processed campaign data
     */
    private function process_campaign_data($response) {
        $data = $response['data'] ?? array();

        return array(
            'id' => $data['id'] ?? '',
            'name' => $data['name'] ?? '',
            'description' => $data['description'] ?? '',
            'amount_raised' => floatval($data['amount_raised']['value'] ?? 0),
            'amount_raised_formatted' => $data['amount_raised']['formatted'] ?? '$0',
            'goal' => floatval($data['goal']['value'] ?? 0),
            'goal_formatted' => $data['goal']['formatted'] ?? '$0',
            'currency' => $data['amount_raised']['currency'] ?? 'USD',
            'percentage' => $this->calculate_percentage($data),
            'status' => $data['status'] ?? 'unknown',
            'url' => $data['url'] ?? '',
            'thumbnail_url' => $data['thumbnail']['src'] ?? '',
            'slug' => $data['slug'] ?? '',
            'started_at' => $data['started_at'] ?? '',
            'ends_at' => $data['ends_at'] ?? '',
            'total_donations' => intval($data['supporting_amount'] ?? 0),
            'last_updated' => current_time('mysql')
        );
    }

    /**
     * Process donations data from API response
     *
     * @param array $response API response
     * @return array Processed donations data
     */
    private function process_donations_data($response) {
        $donations = array();
        $data = $response['data'] ?? array();

        foreach ($data as $donation) {
            $donations[] = array(
                'id' => $donation['id'] ?? '',
                'amount' => floatval($donation['amount']['value'] ?? 0),
                'amount_formatted' => $donation['amount']['formatted'] ?? '$0',
                'currency' => $donation['amount']['currency'] ?? 'USD',
                'donor_name' => $donation['donor_name'] ?? __('Anonymous', TILTIFY_INTEGRATION_TEXT_DOMAIN),
                'donor_comment' => $donation['donor_comment'] ?? '',
                'completed_at' => $donation['completed_at'] ?? '',
                'poll_option_title' => $donation['poll_option_title'] ?? '',
                'reward_title' => $donation['reward_title'] ?? ''
            );
        }

        return array(
            'donations' => $donations,
            'count' => count($donations),
            'last_updated' => current_time('mysql')
        );
    }

    /**
     * Calculate fundraising percentage
     *
     * @param array $data Campaign data
     * @return float Percentage (0-100)
     */
    private function calculate_percentage($data) {
        $raised = floatval($data['amount_raised']['value'] ?? 0);
        $goal = floatval($data['goal']['value'] ?? 0);

        if ($goal <= 0) {
            return 0;
        }

        return min(100, round(($raised / $goal) * 100, 2));
    }

    /**
     * Get human-readable error message from API response
     *
     * @param int $response_code HTTP response code
     * @param string $body Response body
     * @return string Error message
     */
    private function get_error_message($response_code, $body) {
        switch ($response_code) {
            case 401:
                return __('Invalid API token or unauthorized access', TILTIFY_INTEGRATION_TEXT_DOMAIN);
            case 403:
                return __('Access forbidden - check your API permissions', TILTIFY_INTEGRATION_TEXT_DOMAIN);
            case 404:
                return __('Campaign not found - check your campaign ID', TILTIFY_INTEGRATION_TEXT_DOMAIN);
            case 429:
                return __('Rate limit exceeded - please try again later', TILTIFY_INTEGRATION_TEXT_DOMAIN);
            case 500:
            case 502:
            case 503:
                return __('Tiltify API is currently unavailable', TILTIFY_INTEGRATION_TEXT_DOMAIN);
            default:
                // Try to parse error from response body
                $error_data = json_decode($body, true);
                if (isset($error_data['error']['message'])) {
                    return $error_data['error']['message'];
                }
                return sprintf(__('API error (HTTP %d)', TILTIFY_INTEGRATION_TEXT_DOMAIN), $response_code);
        }
    }

    /**
     * Clear all cached data
     *
     * @return int Number of cached items cleared
     */
    public function clear_cache() {
        global $wpdb;

        $transients_cleared = 0;
        $transients = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_tiltify_%'
            )
        );

        foreach ($transients as $transient) {
            $transient_name = str_replace('_transient_', '', $transient->option_name);
            if (delete_transient($transient_name)) {
                $transients_cleared++;
            }
        }

        return $transients_cleared;
    }

    /**
     * Get donation URL for a campaign
     *
     * @param string $campaign_id Campaign ID
     * @return string Donation URL
     */
    public function get_donation_url($campaign_id = null) {
        if (empty($campaign_id)) {
            $campaign_id = get_option('tiltify_campaign_id', '');
        }

        if (empty($campaign_id)) {
            return '';
        }

        return "https://tiltify.com/+/{$campaign_id}/donate";
    }

    /**
     * Format currency amount
     *
     * @param float $amount Amount to format
     * @param string $currency Currency code
     * @return string Formatted amount
     */
    public function format_currency($amount, $currency = 'USD') {
        $symbols = array(
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'CAD' => 'CA$',
            'AUD' => 'AU$'
        );

        $symbol = isset($symbols[$currency]) ? $symbols[$currency] : $currency . ' ';
        
        return $symbol . number_format($amount, 2);
    }
}