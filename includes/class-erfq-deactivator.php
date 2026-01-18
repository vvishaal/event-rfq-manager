<?php
/**
 * Fired during plugin deactivation
 *
 * @package Event_RFQ_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ERFQ_Deactivator
 *
 * Handles plugin deactivation tasks
 */
class ERFQ_Deactivator {

    /**
     * Deactivate the plugin
     */
    public static function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();

        // Clear scheduled events
        self::clear_scheduled_events();

        // Clear transients
        self::clear_transients();
    }

    /**
     * Clear any scheduled events
     */
    private static function clear_scheduled_events() {
        wp_clear_scheduled_hook('erfq_cleanup_rate_limits');
        wp_clear_scheduled_hook('erfq_cleanup_temp_files');
    }

    /**
     * Clear plugin transients
     */
    private static function clear_transients() {
        global $wpdb;

        $wpdb->query(
            "DELETE FROM {$wpdb->options}
            WHERE option_name LIKE '_transient_erfq_%'
            OR option_name LIKE '_transient_timeout_erfq_%'"
        );
    }
}
