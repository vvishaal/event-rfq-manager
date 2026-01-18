<?php
/**
 * Admin Settings class
 *
 * @package Event_RFQ_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ERFQ_Admin_Settings
 *
 * Handles plugin settings
 */
class ERFQ_Admin_Settings {

    /**
     * Settings group name
     */
    const SETTINGS_GROUP = 'erfq_settings';

    /**
     * Register settings
     */
    public function register_settings() {
        // General settings
        register_setting(self::SETTINGS_GROUP, 'erfq_default_email', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_email',
        ));

        // reCAPTCHA settings
        register_setting(self::SETTINGS_GROUP, 'erfq_recaptcha_site_key', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ));

        register_setting(self::SETTINGS_GROUP, 'erfq_recaptcha_secret_key', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ));

        // Security settings
        register_setting(self::SETTINGS_GROUP, 'erfq_honeypot_enabled', array(
            'type'    => 'string',
            'default' => '1',
        ));

        register_setting(self::SETTINGS_GROUP, 'erfq_rate_limit_enabled', array(
            'type'    => 'string',
            'default' => '1',
        ));

        register_setting(self::SETTINGS_GROUP, 'erfq_rate_limit_count', array(
            'type'              => 'integer',
            'default'           => 5,
            'sanitize_callback' => 'absint',
        ));

        register_setting(self::SETTINGS_GROUP, 'erfq_rate_limit_period', array(
            'type'              => 'integer',
            'default'           => 60,
            'sanitize_callback' => 'absint',
        ));

        // File upload settings
        register_setting(self::SETTINGS_GROUP, 'erfq_file_upload_max_size', array(
            'type'              => 'integer',
            'default'           => 5,
            'sanitize_callback' => 'absint',
        ));

        register_setting(self::SETTINGS_GROUP, 'erfq_file_upload_types', array(
            'type'              => 'string',
            'default'           => 'pdf,doc,docx,jpg,jpeg,png,gif',
            'sanitize_callback' => 'sanitize_text_field',
        ));

        // Global settings
        register_setting(self::SETTINGS_GROUP, 'erfq_global_settings', array(
            'type'              => 'array',
            'sanitize_callback' => array($this, 'sanitize_global_settings'),
        ));

        // Add settings sections
        add_settings_section(
            'erfq_general_section',
            __('General Settings', 'event-rfq-manager'),
            array($this, 'render_general_section'),
            'erfq-settings'
        );

        add_settings_section(
            'erfq_recaptcha_section',
            __('reCAPTCHA Settings', 'event-rfq-manager'),
            array($this, 'render_recaptcha_section'),
            'erfq-settings'
        );

        add_settings_section(
            'erfq_security_section',
            __('Security Settings', 'event-rfq-manager'),
            array($this, 'render_security_section'),
            'erfq-settings'
        );

        add_settings_section(
            'erfq_file_section',
            __('File Upload Settings', 'event-rfq-manager'),
            array($this, 'render_file_section'),
            'erfq-settings'
        );
    }

    /**
     * Sanitize global settings
     *
     * @param array $value Settings array
     *
     * @return array
     */
    public function sanitize_global_settings($value) {
        if (!is_array($value)) {
            return array();
        }

        return array(
            'success_message'            => isset($value['success_message']) ? sanitize_textarea_field($value['success_message']) : '',
            'error_message'              => isset($value['error_message']) ? sanitize_textarea_field($value['error_message']) : '',
            'enable_admin_notifications' => isset($value['enable_admin_notifications']) ? (bool) $value['enable_admin_notifications'] : true,
            'enable_user_confirmations'  => isset($value['enable_user_confirmations']) ? (bool) $value['enable_user_confirmations'] : false,
            'date_format'                => isset($value['date_format']) ? sanitize_text_field($value['date_format']) : '',
            'time_format'                => isset($value['time_format']) ? sanitize_text_field($value['time_format']) : '',
        );
    }

    /**
     * Render general section description
     */
    public function render_general_section() {
        echo '<p>' . esc_html__('Configure general settings for form submissions.', 'event-rfq-manager') . '</p>';
    }

    /**
     * Render reCAPTCHA section description
     */
    public function render_recaptcha_section() {
        echo '<p>' . esc_html__('Configure Google reCAPTCHA v3 for spam protection.', 'event-rfq-manager') . '</p>';
    }

    /**
     * Render security section description
     */
    public function render_security_section() {
        echo '<p>' . esc_html__('Configure security and anti-spam settings.', 'event-rfq-manager') . '</p>';
    }

    /**
     * Render file section description
     */
    public function render_file_section() {
        echo '<p>' . esc_html__('Configure file upload settings.', 'event-rfq-manager') . '</p>';
    }

    /**
     * Render settings page
     */
    public function render_page() {
        // Handle form submission
        if (isset($_POST['erfq_save_settings'])) {
            $this->save_settings();
        }

        include ERFQ_PLUGIN_DIR . 'admin/views/settings-page.php';
    }

    /**
     * Save settings
     */
    protected function save_settings() {
        if (!current_user_can('manage_options')) {
            return;
        }

        check_admin_referer('erfq_settings_nonce');

        // Save each setting
        if (isset($_POST['erfq_default_email'])) {
            update_option('erfq_default_email', sanitize_email($_POST['erfq_default_email']));
        }

        if (isset($_POST['erfq_recaptcha_site_key'])) {
            update_option('erfq_recaptcha_site_key', sanitize_text_field($_POST['erfq_recaptcha_site_key']));
        }

        if (isset($_POST['erfq_recaptcha_secret_key'])) {
            update_option('erfq_recaptcha_secret_key', sanitize_text_field($_POST['erfq_recaptcha_secret_key']));
        }

        update_option('erfq_honeypot_enabled', isset($_POST['erfq_honeypot_enabled']) ? '1' : '0');
        update_option('erfq_rate_limit_enabled', isset($_POST['erfq_rate_limit_enabled']) ? '1' : '0');

        if (isset($_POST['erfq_rate_limit_count'])) {
            update_option('erfq_rate_limit_count', absint($_POST['erfq_rate_limit_count']));
        }

        if (isset($_POST['erfq_rate_limit_period'])) {
            update_option('erfq_rate_limit_period', absint($_POST['erfq_rate_limit_period']));
        }

        if (isset($_POST['erfq_file_upload_max_size'])) {
            update_option('erfq_file_upload_max_size', absint($_POST['erfq_file_upload_max_size']));
        }

        if (isset($_POST['erfq_file_upload_types'])) {
            update_option('erfq_file_upload_types', sanitize_text_field($_POST['erfq_file_upload_types']));
        }

        // Global settings
        $global_settings = array(
            'success_message'            => isset($_POST['erfq_success_message']) ? sanitize_textarea_field($_POST['erfq_success_message']) : '',
            'error_message'              => isset($_POST['erfq_error_message']) ? sanitize_textarea_field($_POST['erfq_error_message']) : '',
            'enable_admin_notifications' => isset($_POST['erfq_enable_admin_notifications']),
            'enable_user_confirmations'  => isset($_POST['erfq_enable_user_confirmations']),
        );
        update_option('erfq_global_settings', $global_settings);

        // Show success message
        add_settings_error('erfq_settings', 'settings_saved', __('Settings saved.', 'event-rfq-manager'), 'success');
    }
}
