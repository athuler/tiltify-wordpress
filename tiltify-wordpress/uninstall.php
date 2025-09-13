<?php
/**
 * Uninstall script for Tiltify WordPress
 * Cleans up all plugin data when the plugin is deleted
 *
 * @package TiltifyWordPress
 */

// Prevent direct access
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Clean up all plugin data
 */
function tiltify_wordpress_uninstall() {
    // Remove plugin options
    $options_to_delete = array(
        'tiltify_client_id',
        'tiltify_client_secret',
        'tiltify_campaign_id',
        'tiltify_refresh_interval',
        'tiltify_cache_duration'
    );

    foreach ($options_to_delete as $option) {
        delete_option($option);
    }

    // Clear all transients (cached data)
    tiltify_clear_all_transients();

    // Remove custom database tables
    tiltify_drop_custom_tables();

    // Remove any scheduled hooks
    wp_clear_scheduled_hook('tiltify_cleanup_cache');

    // Remove any uploaded files (if any were created)
    tiltify_remove_uploaded_files();

    // Remove user meta if any were set
    tiltify_remove_user_meta();

    // Clear any rewrite rules
    flush_rewrite_rules();
}

/**
 * Clear all plugin-related transients
 */
function tiltify_clear_all_transients() {
    global $wpdb;

    // Delete all transients that start with 'tiltify_'
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
            '_transient_tiltify_%',
            '_transient_timeout_tiltify_%'
        )
    );

    // For multisite, also clear site transients
    if (is_multisite()) {
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE %s OR meta_key LIKE %s",
                '_site_transient_tiltify_%',
                '_site_transient_timeout_tiltify_%'
            )
        );
    }
}

/**
 * Drop custom database tables created by the plugin
 */
function tiltify_drop_custom_tables() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'tiltify_donations';
    
    // Check if table exists before attempting to drop
    $table_exists = $wpdb->get_var($wpdb->prepare(
        "SHOW TABLES LIKE %s",
        $table_name
    ));

    if ($table_exists) {
        $wpdb->query("DROP TABLE IF EXISTS {$table_name}");
    }

    // If there were other custom tables, drop them here
    // Example:
    // $log_table = $wpdb->prefix . 'tiltify_logs';
    // $wpdb->query("DROP TABLE IF EXISTS {$log_table}");
}

/**
 * Remove any uploaded files created by the plugin
 */
function tiltify_remove_uploaded_files() {
    $upload_dir = wp_upload_dir();
    $plugin_upload_dir = $upload_dir['basedir'] . '/tiltify-integration';

    if (is_dir($plugin_upload_dir)) {
        tiltify_delete_directory($plugin_upload_dir);
    }
}

/**
 * Recursively delete a directory and its contents
 */
function tiltify_delete_directory($dir) {
    if (!is_dir($dir)) {
        return false;
    }

    $files = array_diff(scandir($dir), array('.', '..'));

    foreach ($files as $file) {
        $path = $dir . '/' . $file;
        if (is_dir($path)) {
            tiltify_delete_directory($path);
        } else {
            unlink($path);
        }
    }

    return rmdir($dir);
}

/**
 * Remove any user meta data created by the plugin
 */
function tiltify_remove_user_meta() {
    global $wpdb;

    // Remove any user meta keys that start with 'tiltify_'
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE %s",
            'tiltify_%'
        )
    );
}

/**
 * Clean up multisite options if this is a network installation
 */
function tiltify_multisite_uninstall() {
    if (!is_multisite()) {
        return;
    }

    global $wpdb;

    // Get all blog IDs
    $blog_ids = $wpdb->get_col("SELECT blog_id FROM {$wpdb->blogs}");

    foreach ($blog_ids as $blog_id) {
        switch_to_blog($blog_id);
        tiltify_wordpress_uninstall();
        restore_current_blog();
    }

    // Remove network-wide options if any
    delete_site_option('tiltify_network_settings');
}

/**
 * Log the uninstall event (optional, for debugging)
 */
function tiltify_log_uninstall() {
    $log_message = sprintf(
        '[%s] Tiltify WordPress plugin uninstalled. WordPress version: %s, PHP version: %s',
        current_time('mysql'),
        get_bloginfo('version'),
        phpversion()
    );

    // Log to WordPress debug log if enabled
    if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
        error_log($log_message);
    }
}

/**
 * Main uninstall execution
 */
try {
    // Log the uninstall attempt
    tiltify_log_uninstall();

    // Check if this is a multisite installation
    if (is_multisite()) {
        tiltify_multisite_uninstall();
    } else {
        tiltify_wordpress_uninstall();
    }

    // Final cleanup - remove any remaining plugin traces
    wp_cache_flush(); // Clear object cache
    
    // Remove plugin version option (if it exists)
    delete_option('tiltify_wordpress_version');
    
    // Remove any activation/deactivation timestamps
    delete_option('tiltify_wordpress_activated');
    delete_option('tiltify_wordpress_deactivated');

} catch (Exception $e) {
    // Log any errors during uninstall
    if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
        error_log('Tiltify WordPress uninstall error: ' . $e->getMessage());
    }
}

// Note: WordPress will automatically remove the plugin files after this script runs
// No need to manually delete plugin files