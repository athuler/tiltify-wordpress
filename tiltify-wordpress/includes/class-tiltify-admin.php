<?php
/**
 * Admin settings page for Tiltify WordPress
 * Handles all admin-related functionality
 *
 * @package TiltifyWordPress
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles admin functionality for the Tiltify plugin
 */
class Tiltify_Admin {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'init_settings'));
        add_action('wp_ajax_tiltify_test_connection', array($this, 'ajax_test_connection'));
        add_action('wp_ajax_tiltify_clear_cache', array($this, 'ajax_clear_cache'));
    }

    /**
     * Add admin menu item
     */
    public function add_admin_menu() {
        add_options_page(
            __('Tiltify Integration Settings', TILTIFY_INTEGRATION_TEXT_DOMAIN),
            __('Tiltify Integration', TILTIFY_INTEGRATION_TEXT_DOMAIN),
            'manage_options',
            'tiltify-settings',
            array($this, 'settings_page')
        );
    }

    /**
     * Initialize settings
     */
    public function init_settings() {
        // Register settings
        register_setting(
            'tiltify_settings',
            'tiltify_client_id',
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => ''
            )
        );

        register_setting(
            'tiltify_settings',
            'tiltify_client_secret',
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => ''
            )
        );

        register_setting(
            'tiltify_settings',
            'tiltify_campaign_id',
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => ''
            )
        );

        register_setting(
            'tiltify_settings',
            'tiltify_refresh_interval',
            array(
                'type' => 'integer',
                'sanitize_callback' => array($this, 'sanitize_refresh_interval'),
                'default' => 30
            )
        );

        register_setting(
            'tiltify_settings',
            'tiltify_cache_duration',
            array(
                'type' => 'integer',
                'sanitize_callback' => array($this, 'sanitize_cache_duration'),
                'default' => 300
            )
        );


        // Add settings sections
        add_settings_section(
            'tiltify_api_section',
            __('API Configuration', TILTIFY_INTEGRATION_TEXT_DOMAIN),
            array($this, 'api_section_callback'),
            'tiltify-settings'
        );


        add_settings_section(
            'tiltify_performance_section',
            __('Performance Settings', TILTIFY_INTEGRATION_TEXT_DOMAIN),
            array($this, 'performance_section_callback'),
            'tiltify-settings'
        );

        // Add settings fields
        $this->add_settings_fields();
    }

    /**
     * Add all settings fields
     */
    private function add_settings_fields() {
        // API Configuration fields
        add_settings_field(
            'tiltify_client_id',
            __('Client ID', TILTIFY_INTEGRATION_TEXT_DOMAIN),
            array($this, 'client_id_callback'),
            'tiltify-settings',
            'tiltify_api_section'
        );

        add_settings_field(
            'tiltify_client_secret',
            __('Client Secret', TILTIFY_INTEGRATION_TEXT_DOMAIN),
            array($this, 'client_secret_callback'),
            'tiltify-settings',
            'tiltify_api_section'
        );

        add_settings_field(
            'tiltify_campaign_id',
            __('Campaign ID', TILTIFY_INTEGRATION_TEXT_DOMAIN),
            array($this, 'campaign_id_callback'),
            'tiltify-settings',
            'tiltify_api_section'
        );

        add_settings_field(
            'tiltify_test_connection',
            __('Test Connection', TILTIFY_INTEGRATION_TEXT_DOMAIN),
            array($this, 'test_connection_callback'),
            'tiltify-settings',
            'tiltify_api_section'
        );


        // Performance Settings fields
        add_settings_field(
            'tiltify_refresh_interval',
            __('Live Update Interval', TILTIFY_INTEGRATION_TEXT_DOMAIN),
            array($this, 'refresh_interval_callback'),
            'tiltify-settings',
            'tiltify_performance_section'
        );

        add_settings_field(
            'tiltify_cache_duration',
            __('Cache Duration', TILTIFY_INTEGRATION_TEXT_DOMAIN),
            array($this, 'cache_duration_callback'),
            'tiltify-settings',
            'tiltify_performance_section'
        );

        add_settings_field(
            'tiltify_clear_cache',
            __('Clear Cache', TILTIFY_INTEGRATION_TEXT_DOMAIN),
            array($this, 'clear_cache_callback'),
            'tiltify-settings',
            'tiltify_performance_section'
        );
    }

    /**
     * Settings page HTML
     */
    public function settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="tiltify-admin-header">
                <p><?php _e('Configure your Tiltify integration to display live fundraising data on your website.', TILTIFY_INTEGRATION_TEXT_DOMAIN); ?></p>
            </div>

            <form action="options.php" method="post">
                <?php
                settings_fields('tiltify_settings');
                do_settings_sections('tiltify-settings');
                submit_button(__('Save Settings', TILTIFY_INTEGRATION_TEXT_DOMAIN));
                ?>
            </form>

            <div class="tiltify-usage-info">
                <h2><?php _e('How to Use', TILTIFY_INTEGRATION_TEXT_DOMAIN); ?></h2>
                <div class="tiltify-usage-grid">
                    <div class="tiltify-usage-card">
                        <h3><?php _e('Shortcodes', TILTIFY_INTEGRATION_TEXT_DOMAIN); ?></h3>
                        <ul>
                            <li><code>[tiltify_amount]</code> - <?php _e('Display current amount raised', TILTIFY_INTEGRATION_TEXT_DOMAIN); ?></li>
                            <li><code>[tiltify_goal]</code> - <?php _e('Display campaign goal', TILTIFY_INTEGRATION_TEXT_DOMAIN); ?></li>
                            <li><code>[tiltify_progress]</code> - <?php _e('Display progress bar', TILTIFY_INTEGRATION_TEXT_DOMAIN); ?></li>
                            <li><code>[tiltify_donate]</code> - <?php _e('Display donation button', TILTIFY_INTEGRATION_TEXT_DOMAIN); ?></li>
                        </ul>
                    </div>
                    <div class="tiltify-usage-card">
                        <h3><?php _e('Widget', TILTIFY_INTEGRATION_TEXT_DOMAIN); ?></h3>
                        <p><?php _e('Go to Appearance → Widgets and add the "Tiltify Fundraising" widget to your sidebar.', TILTIFY_INTEGRATION_TEXT_DOMAIN); ?></p>
                    </div>
                    <div class="tiltify-usage-card">
                        <h3><?php _e('Shortcode Parameters', TILTIFY_INTEGRATION_TEXT_DOMAIN); ?></h3>
                        <ul>
                            <li><code>campaign_id</code> - <?php _e('Override default campaign ID', TILTIFY_INTEGRATION_TEXT_DOMAIN); ?></li>
                            <li><code>class</code> - <?php _e('Add custom CSS class', TILTIFY_INTEGRATION_TEXT_DOMAIN); ?></li>
                            <li><code>style</code> - <?php _e('Add inline CSS styles', TILTIFY_INTEGRATION_TEXT_DOMAIN); ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * API section callback
     */
    public function api_section_callback() {
        echo '<p>' . __('Configure your Tiltify API credentials. You can get your Client ID and Client Secret from your Tiltify application dashboard.', TILTIFY_INTEGRATION_TEXT_DOMAIN) . '</p>';
    }


    /**
     * Performance section callback
     */
    public function performance_section_callback() {
        echo '<p>' . __('Optimize performance and manage API usage.', TILTIFY_INTEGRATION_TEXT_DOMAIN) . '</p>';
    }

    /**
     * Client ID field callback
     */
    public function client_id_callback() {
        $value = get_option('tiltify_client_id', '');
        echo '<input type="text" id="tiltify_client_id" name="tiltify_client_id" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">' . __('Your Tiltify application Client ID', TILTIFY_INTEGRATION_TEXT_DOMAIN) . '</p>';
    }

    /**
     * Client Secret field callback
     */
    public function client_secret_callback() {
        $value = get_option('tiltify_client_secret', '');
        echo '<input type="password" id="tiltify_client_secret" name="tiltify_client_secret" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">' . __('Your Tiltify application Client Secret', TILTIFY_INTEGRATION_TEXT_DOMAIN) . '</p>';
    }

    /**
     * Campaign ID field callback
     */
    public function campaign_id_callback() {
        $value = get_option('tiltify_campaign_id', '');
        echo '<input type="text" id="tiltify_campaign_id" name="tiltify_campaign_id" value="' . esc_attr($value) . '" class="regular-text" required />';
        echo '<p class="description">' . __('Your Tiltify campaign ID (UUID format like "2f9e7c6f-42ea-4a49-b3bb-047a5dfefb5f" found in your private campaign URL)', TILTIFY_INTEGRATION_TEXT_DOMAIN) . '</p>';
    }

    /**
     * Test connection button callback
     */
    public function test_connection_callback() {
        echo '<button type="button" id="tiltify-test-connection" class="button button-secondary">' . __('Test Connection', TILTIFY_INTEGRATION_TEXT_DOMAIN) . '</button>';
        echo '<span id="tiltify-test-result"></span>';
        echo '<p class="description">' . __('Test your API credentials and campaign ID', TILTIFY_INTEGRATION_TEXT_DOMAIN) . '</p>';
    }


    /**
     * Refresh interval callback
     */
    public function refresh_interval_callback() {
        $value = get_option('tiltify_refresh_interval', 30);
        echo '<input type="number" id="tiltify_refresh_interval" name="tiltify_refresh_interval" value="' . esc_attr($value) . '" min="0" max="300" /> ';
        echo __('seconds', TILTIFY_INTEGRATION_TEXT_DOMAIN);
        echo '<p class="description">' . __('How often to update live data (0 = disabled, 10-300 seconds)', TILTIFY_INTEGRATION_TEXT_DOMAIN) . '</p>';
    }

    /**
     * Cache duration callback
     */
    public function cache_duration_callback() {
        $value = get_option('tiltify_cache_duration', 300);
        echo '<input type="number" id="tiltify_cache_duration" name="tiltify_cache_duration" value="' . esc_attr($value) . '" min="60" max="3600" /> ';
        echo __('seconds', TILTIFY_INTEGRATION_TEXT_DOMAIN);
        echo '<p class="description">' . __('How long to cache API responses (60-3600 seconds)', TILTIFY_INTEGRATION_TEXT_DOMAIN) . '</p>';
    }

    /**
     * Clear cache button callback
     */
    public function clear_cache_callback() {
        echo '<button type="button" id="tiltify-clear-cache" class="button button-secondary">' . __('Clear Cache', TILTIFY_INTEGRATION_TEXT_DOMAIN) . '</button>';
        echo '<span id="tiltify-cache-result"></span>';
        echo '<p class="description">' . __('Clear all cached Tiltify data to force fresh API requests', TILTIFY_INTEGRATION_TEXT_DOMAIN) . '</p>';
    }

    /**
     * Sanitize refresh interval
     */
    public function sanitize_refresh_interval($value) {
        $value = intval($value);
        if ($value === 0) {
            return 0; // Allow 0 to disable live updates
        }
        return max(10, min(300, $value));
    }

    /**
     * Sanitize cache duration
     */
    public function sanitize_cache_duration($value) {
        $value = intval($value);
        return max(60, min(3600, $value));
    }

    /**
     * AJAX handler for testing connection
     */
    public function ajax_test_connection() {
        check_ajax_referer('tiltify_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', TILTIFY_INTEGRATION_TEXT_DOMAIN));
        }

        $client_id = sanitize_text_field($_POST['client_id']);
        $client_secret = sanitize_text_field($_POST['client_secret']);
        $campaign_id = sanitize_text_field($_POST['campaign_id']);

        $api = new Tiltify_API();
        $result = $api->test_connection($client_id, $client_secret, $campaign_id);

        wp_send_json($result);
    }

    /**
     * AJAX handler for clearing cache
     */
    public function ajax_clear_cache() {
        check_ajax_referer('tiltify_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', TILTIFY_INTEGRATION_TEXT_DOMAIN));
        }

        $api = new Tiltify_API();
        $cleared = $api->clear_cache();

        wp_send_json_success(array(
            'message' => sprintf(__('Cleared %d cached items', TILTIFY_INTEGRATION_TEXT_DOMAIN), $cleared)
        ));
    }
}