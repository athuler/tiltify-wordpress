<?php
/**
 * Plugin Name: Tiltify Wordpress Integration
 * Plugin URI: https://github.com/athuler/tiltify-wordpress
 * Description: Display live fundraising data from Tiltify campaigns with real-time updates, donation buttons, and customizable widgets.
 * Version: 0.1.1
 * Author: Andrei Thüler
 * Author URI: https://andreithuler.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: tiltify-wordpress
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 *
 * @package TiltifyWordPress
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('TILTIFY_INTEGRATION_VERSION', '0.1.1');
define('TILTIFY_INTEGRATION_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('TILTIFY_INTEGRATION_PLUGIN_URL', plugin_dir_url(__FILE__));
define('TILTIFY_INTEGRATION_PLUGIN_FILE', __FILE__);
define('TILTIFY_INTEGRATION_TEXT_DOMAIN', 'tiltify-wordpress');

/**
 * Main Tiltify WordPress plugin class that handles initialization and coordination
 */
class TiltifyWordPress {

    /**
     * Single instance of the plugin
     */
    private static $instance = null;

    /**
     * Plugin components
     */
    private $api;
    private $admin;
    private $shortcodes;
    private $widget;

    /**
     * Get single instance of the plugin
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor - Initialize the plugin
     */
    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Activation and deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Load plugin textdomain for internationalization
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        
        // Add settings link to plugin actions
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_settings_link'));
    }

    /**
     * Initialize plugin components after WordPress is loaded
     */
    public function init() {
        $this->load_dependencies();
        $this->init_components();
        $this->setup_ajax_handlers();
    }

    /**
     * Load required plugin files
     */
    private function load_dependencies() {
        require_once TILTIFY_INTEGRATION_PLUGIN_DIR . 'includes/class-tiltify-api.php';
        require_once TILTIFY_INTEGRATION_PLUGIN_DIR . 'includes/class-tiltify-admin.php';
        require_once TILTIFY_INTEGRATION_PLUGIN_DIR . 'includes/class-tiltify-shortcodes.php';
        require_once TILTIFY_INTEGRATION_PLUGIN_DIR . 'includes/class-tiltify-widget.php';
    }

    /**
     * Initialize plugin components
     */
    private function init_components() {
        $this->api = new Tiltify_API();
        $this->admin = new Tiltify_Admin();
        $this->shortcodes = new Tiltify_Shortcodes($this->api);
        $this->widget = new Tiltify_Widget($this->api);

        // Register widget
        add_action('widgets_init', array($this, 'register_widget'));
    }

    /**
     * Register the Tiltify widget
     */
    public function register_widget() {
        register_widget('Tiltify_Widget');
    }

    /**
     * Setup AJAX handlers for live updates
     */
    private function setup_ajax_handlers() {
        add_action('wp_ajax_tiltify_update_data', array($this, 'ajax_update_data'));
        add_action('wp_ajax_nopriv_tiltify_update_data', array($this, 'ajax_update_data'));
    }

    /**
     * Handle AJAX requests for live data updates
     */
    public function ajax_update_data() {
        // Verify nonce for security
        if (!wp_verify_nonce($_POST['nonce'], 'tiltify_update_nonce')) {
            wp_die(__('Security check failed', TILTIFY_INTEGRATION_TEXT_DOMAIN));
        }

        $campaign_id = sanitize_text_field($_POST['campaign_id']);
        $data = $this->api->get_campaign_data($campaign_id);

        if ($data && !is_wp_error($data)) {
            wp_send_json_success($data);
        } else {
            wp_send_json_error(__('Failed to fetch campaign data', TILTIFY_INTEGRATION_TEXT_DOMAIN));
        }
    }

    /**
     * Enqueue frontend styles and scripts
     */
    public function enqueue_frontend_assets() {
        wp_enqueue_style(
            'tiltify-integration-frontend',
            TILTIFY_INTEGRATION_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            TILTIFY_INTEGRATION_VERSION
        );

        wp_enqueue_script(
            'tiltify-integration-frontend',
            TILTIFY_INTEGRATION_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            TILTIFY_INTEGRATION_VERSION,
            true
        );

        // Localize script for AJAX
        $refresh_interval = get_option('tiltify_refresh_interval', 30);
        wp_localize_script('tiltify-integration-frontend', 'tiltifyAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('tiltify_update_nonce'),
            'refresh_interval' => $refresh_interval * 1000,
            'live_updates_enabled' => $refresh_interval > 0
        ));
    }

    /**
     * Enqueue admin styles and scripts
     */
    public function enqueue_admin_assets($hook) {
        if ('settings_page_tiltify-settings' !== $hook) {
            return;
        }

        wp_enqueue_style(
            'tiltify-integration-admin',
            TILTIFY_INTEGRATION_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            TILTIFY_INTEGRATION_VERSION
        );

        wp_enqueue_script(
            'tiltify-integration-admin',
            TILTIFY_INTEGRATION_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            TILTIFY_INTEGRATION_VERSION,
            true
        );

        wp_localize_script('tiltify-integration-admin', 'tiltifyAdmin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('tiltify_admin_nonce')
        ));
    }

    /**
     * Load plugin textdomain for translations
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            TILTIFY_INTEGRATION_TEXT_DOMAIN,
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
    }

    /**
     * Plugin activation hook
     */
    public function activate() {
        // Set default options
        add_option('tiltify_client_id', '');
        add_option('tiltify_client_secret', '');
        add_option('tiltify_campaign_id', '');
        add_option('tiltify_refresh_interval', 30);
        add_option('tiltify_cache_duration', 300);

        // Create database tables if needed (for future enhancements)
        $this->create_tables();

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation hook
     */
    public function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook('tiltify_cleanup_cache');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Create database tables for future enhancements
     */
    private function create_tables() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'tiltify_donations';
        
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            campaign_id varchar(100) NOT NULL,
            donation_id varchar(100) NOT NULL,
            amount decimal(10,2) NOT NULL,
            donor_name varchar(255),
            message text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY donation_id (donation_id),
            KEY campaign_id (campaign_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Get API instance
     */
    public function get_api() {
        return $this->api;
    }

    /**
     * Get admin instance
     */
    public function get_admin() {
        return $this->admin;
    }

    /**
     * Get shortcodes instance
     */
    public function get_shortcodes() {
        return $this->shortcodes;
    }

    /**
     * Get widget instance
     */
    public function get_widget() {
        return $this->widget;
    }

    /**
     * Add settings link to plugin actions
     */
    public function add_settings_link($links) {
        $settings_link = '<a href="' . admin_url('options-general.php?page=tiltify-settings') . '">' . __('Settings', TILTIFY_INTEGRATION_TEXT_DOMAIN) . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
}

// Initialize the plugin
function tiltify_wordpress() {
    return TiltifyWordPress::get_instance();
}

// Start the plugin
tiltify_wordpress();