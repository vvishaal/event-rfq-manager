<?php
/**
 * Fired during plugin activation
 *
 * @package Event_RFQ_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ERFQ_Activator
 *
 * Handles all plugin activation tasks
 */
class ERFQ_Activator {

    /**
     * Activate the plugin
     */
    public static function activate() {
        // Create database tables if needed
        self::create_tables();

        // Set default options
        self::set_default_options();

        // Create required directories
        self::create_directories();

        // Register post types (needed for flush_rewrite_rules)
        require_once ERFQ_PLUGIN_DIR . 'includes/post-types/class-erfq-post-type-form.php';
        require_once ERFQ_PLUGIN_DIR . 'includes/post-types/class-erfq-post-type-entry.php';
        ERFQ_Post_Type_Form::register();
        ERFQ_Post_Type_Entry::register();

        // Flush rewrite rules
        flush_rewrite_rules();

        // Set version
        update_option('erfq_version', ERFQ_VERSION);

        // Set activation timestamp
        if (!get_option('erfq_installed_at')) {
            update_option('erfq_installed_at', current_time('mysql'));
        }

        // Clear any cached data
        wp_cache_flush();
    }

    /**
     * Create custom database tables if needed
     */
    private static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Rate limiting table
        $table_name = $wpdb->prefix . 'erfq_rate_limits';

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            ip_address varchar(45) NOT NULL,
            form_id bigint(20) unsigned NOT NULL,
            submission_time datetime NOT NULL,
            PRIMARY KEY (id),
            KEY ip_form (ip_address, form_id),
            KEY submission_time (submission_time)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * Set default plugin options
     */
    private static function set_default_options() {
        $defaults = array(
            'erfq_default_email'      => get_option('admin_email'),
            'erfq_recaptcha_site_key' => '',
            'erfq_recaptcha_secret_key' => '',
            'erfq_honeypot_enabled'   => '1',
            'erfq_rate_limit_enabled' => '1',
            'erfq_rate_limit_count'   => '5',
            'erfq_rate_limit_period'  => '60', // 60 minutes
            'erfq_file_upload_max_size' => '5', // 5 MB
            'erfq_file_upload_types'  => 'pdf,doc,docx,jpg,jpeg,png,gif',
            'erfq_global_settings'    => array(
                'success_message' => __('Thank you! Your submission has been received.', 'event-rfq-manager'),
                'error_message'   => __('There was an error processing your submission. Please try again.', 'event-rfq-manager'),
                'enable_admin_notifications' => true,
                'enable_user_confirmations' => false,
                'date_format' => get_option('date_format'),
                'time_format' => get_option('time_format'),
            ),
        );

        foreach ($defaults as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }
    }

    /**
     * Create required directories
     */
    private static function create_directories() {
        $upload_dir = wp_upload_dir();
        $erfq_upload_dir = $upload_dir['basedir'] . '/erfq-uploads';

        if (!file_exists($erfq_upload_dir)) {
            wp_mkdir_p($erfq_upload_dir);

            // Create .htaccess to protect uploads
            $htaccess = $erfq_upload_dir . '/.htaccess';
            if (!file_exists($htaccess)) {
                $content = "# Protect uploads\n";
                $content .= "Options -Indexes\n";
                $content .= "<FilesMatch \"\\.(php|php5|phtml)$\">\n";
                $content .= "    Order Deny,Allow\n";
                $content .= "    Deny from all\n";
                $content .= "</FilesMatch>\n";
                file_put_contents($htaccess, $content);
            }

            // Create index.php for extra protection
            $index = $erfq_upload_dir . '/index.php';
            if (!file_exists($index)) {
                file_put_contents($index, '<?php // Silence is golden');
            }
        }
    }
}
