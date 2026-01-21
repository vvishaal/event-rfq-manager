<?php
/**
 * Settings Page View
 *
 * @package Event_RFQ_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get current settings
$default_email = get_option('erfq_default_email', get_option('admin_email'));
$from_email = get_option('erfq_from_email', get_option('admin_email'));
$from_name = get_option('erfq_from_name', get_bloginfo('name'));
$recaptcha_site_key = get_option('erfq_recaptcha_site_key', '');
$recaptcha_secret_key = get_option('erfq_recaptcha_secret_key', '');
$honeypot_enabled = get_option('erfq_honeypot_enabled', '1');
$rate_limit_enabled = get_option('erfq_rate_limit_enabled', '1');
$rate_limit_count = get_option('erfq_rate_limit_count', 5);
$rate_limit_period = get_option('erfq_rate_limit_period', 60);
$file_max_size = get_option('erfq_file_upload_max_size', 5);
$file_types = get_option('erfq_file_upload_types', 'pdf,doc,docx,jpg,jpeg,png,gif');
$global_settings = get_option('erfq_global_settings', array());
?>
<div class="wrap erfq-settings-wrap">
    <h1><?php esc_html_e('Event RFQ Manager Settings', 'event-rfq-manager'); ?></h1>

    <?php settings_errors('erfq_settings'); ?>

    <form method="post" action="">
        <?php wp_nonce_field('erfq_settings_nonce'); ?>

        <div class="erfq-settings-tabs">
            <nav class="nav-tab-wrapper">
                <a href="#general" class="nav-tab nav-tab-active"><?php esc_html_e('General', 'event-rfq-manager'); ?></a>
                <a href="#email" class="nav-tab"><?php esc_html_e('Email', 'event-rfq-manager'); ?></a>
                <a href="#security" class="nav-tab"><?php esc_html_e('Security', 'event-rfq-manager'); ?></a>
                <a href="#files" class="nav-tab"><?php esc_html_e('File Uploads', 'event-rfq-manager'); ?></a>
            </nav>

            <!-- General Settings -->
            <div id="general" class="erfq-settings-section active">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="erfq_success_message"><?php esc_html_e('Default Success Message', 'event-rfq-manager'); ?></label>
                        </th>
                        <td>
                            <textarea name="erfq_success_message" id="erfq_success_message" class="large-text" rows="3"><?php echo esc_textarea($global_settings['success_message'] ?? __('Thank you for your submission! We will get back to you soon.', 'event-rfq-manager')); ?></textarea>
                            <p class="description"><?php esc_html_e('Default message shown after successful form submission. Can be overridden per form.', 'event-rfq-manager'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="erfq_error_message"><?php esc_html_e('Default Error Message', 'event-rfq-manager'); ?></label>
                        </th>
                        <td>
                            <textarea name="erfq_error_message" id="erfq_error_message" class="large-text" rows="3"><?php echo esc_textarea($global_settings['error_message'] ?? __('Something went wrong. Please try again.', 'event-rfq-manager')); ?></textarea>
                            <p class="description"><?php esc_html_e('Default message shown when form submission fails.', 'event-rfq-manager'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Email Settings -->
            <div id="email" class="erfq-settings-section" style="display: none;">
                <h2><?php esc_html_e('Sender Settings', 'event-rfq-manager'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="erfq_from_name"><?php esc_html_e('From Name', 'event-rfq-manager'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="erfq_from_name" id="erfq_from_name" class="regular-text" value="<?php echo esc_attr($from_name); ?>">
                            <p class="description"><?php esc_html_e('Name that appears in the "From" field of notification emails.', 'event-rfq-manager'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="erfq_from_email"><?php esc_html_e('From Email', 'event-rfq-manager'); ?></label>
                        </th>
                        <td>
                            <input type="email" name="erfq_from_email" id="erfq_from_email" class="regular-text" value="<?php echo esc_attr($from_email); ?>">
                            <p class="description"><?php esc_html_e('Email address used in the "From" header of notification emails. This should be an email from your domain.', 'event-rfq-manager'); ?></p>
                        </td>
                    </tr>
                </table>

                <h2><?php esc_html_e('Notification Settings', 'event-rfq-manager'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="erfq_default_email"><?php esc_html_e('Default Notification Email', 'event-rfq-manager'); ?></label>
                        </th>
                        <td>
                            <input type="email" name="erfq_default_email" id="erfq_default_email" class="regular-text" value="<?php echo esc_attr($default_email); ?>">
                            <p class="description"><?php esc_html_e('Email address that receives form submission notifications. Can be overridden per form.', 'event-rfq-manager'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Email Notifications', 'event-rfq-manager'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="erfq_enable_admin_notifications" value="1" <?php checked($global_settings['enable_admin_notifications'] ?? true, true); ?>>
                                <?php esc_html_e('Enable admin notifications by default', 'event-rfq-manager'); ?>
                            </label>
                            <br><br>
                            <label>
                                <input type="checkbox" name="erfq_enable_user_confirmations" value="1" <?php checked($global_settings['enable_user_confirmations'] ?? false, true); ?>>
                                <?php esc_html_e('Enable user confirmation emails by default', 'event-rfq-manager'); ?>
                            </label>
                            <p class="description"><?php esc_html_e('These settings can be overridden per form.', 'event-rfq-manager'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Security Settings -->
            <div id="security" class="erfq-settings-section" style="display: none;">
                <h2><?php esc_html_e('reCAPTCHA v3', 'event-rfq-manager'); ?></h2>
                <p class="description">
                    <?php
                    printf(
                        esc_html__('Get your reCAPTCHA v3 keys from the %sGoogle reCAPTCHA admin console%s.', 'event-rfq-manager'),
                        '<a href="https://www.google.com/recaptcha/admin" target="_blank">',
                        '</a>'
                    );
                    ?>
                </p>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="erfq_recaptcha_site_key"><?php esc_html_e('Site Key', 'event-rfq-manager'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="erfq_recaptcha_site_key" id="erfq_recaptcha_site_key" class="regular-text" value="<?php echo esc_attr($recaptcha_site_key); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="erfq_recaptcha_secret_key"><?php esc_html_e('Secret Key', 'event-rfq-manager'); ?></label>
                        </th>
                        <td>
                            <input type="password" name="erfq_recaptcha_secret_key" id="erfq_recaptcha_secret_key" class="regular-text" value="<?php echo esc_attr($recaptcha_secret_key); ?>">
                        </td>
                    </tr>
                </table>

                <h2><?php esc_html_e('Spam Protection', 'event-rfq-manager'); ?></h2>

                <table class="form-table">
                    <tr>
                        <th scope="row"><?php esc_html_e('Honeypot', 'event-rfq-manager'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="erfq_honeypot_enabled" value="1" <?php checked($honeypot_enabled, '1'); ?>>
                                <?php esc_html_e('Enable honeypot anti-spam by default', 'event-rfq-manager'); ?>
                            </label>
                            <p class="description"><?php esc_html_e('Adds a hidden field that catches automated spam bots.', 'event-rfq-manager'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Rate Limiting', 'event-rfq-manager'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="erfq_rate_limit_enabled" value="1" <?php checked($rate_limit_enabled, '1'); ?>>
                                <?php esc_html_e('Enable submission rate limiting', 'event-rfq-manager'); ?>
                            </label>
                            <p class="description"><?php esc_html_e('Limits the number of submissions from the same IP address.', 'event-rfq-manager'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="erfq_rate_limit_count"><?php esc_html_e('Rate Limit', 'event-rfq-manager'); ?></label>
                        </th>
                        <td>
                            <input type="number" name="erfq_rate_limit_count" id="erfq_rate_limit_count" class="small-text" value="<?php echo esc_attr($rate_limit_count); ?>" min="1" max="100">
                            <?php esc_html_e('submissions per', 'event-rfq-manager'); ?>
                            <input type="number" name="erfq_rate_limit_period" id="erfq_rate_limit_period" class="small-text" value="<?php echo esc_attr($rate_limit_period); ?>" min="1" max="3600">
                            <?php esc_html_e('seconds', 'event-rfq-manager'); ?>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- File Upload Settings -->
            <div id="files" class="erfq-settings-section" style="display: none;">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="erfq_file_upload_max_size"><?php esc_html_e('Maximum File Size', 'event-rfq-manager'); ?></label>
                        </th>
                        <td>
                            <input type="number" name="erfq_file_upload_max_size" id="erfq_file_upload_max_size" class="small-text" value="<?php echo esc_attr($file_max_size); ?>" min="1" max="100">
                            <?php esc_html_e('MB', 'event-rfq-manager'); ?>
                            <p class="description">
                                <?php
                                printf(
                                    esc_html__('Maximum file size for uploads. Server maximum: %s', 'event-rfq-manager'),
                                    esc_html(size_format(wp_max_upload_size()))
                                );
                                ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="erfq_file_upload_types"><?php esc_html_e('Allowed File Types', 'event-rfq-manager'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="erfq_file_upload_types" id="erfq_file_upload_types" class="large-text" value="<?php echo esc_attr($file_types); ?>">
                            <p class="description"><?php esc_html_e('Comma-separated list of allowed file extensions. Leave empty to allow all WordPress-supported types.', 'event-rfq-manager'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <p class="submit">
            <input type="submit" name="erfq_save_settings" class="button button-primary" value="<?php esc_attr_e('Save Settings', 'event-rfq-manager'); ?>">
        </p>
    </form>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Tab switching
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        var target = $(this).attr('href');

        // Update tabs
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');

        // Update sections
        $('.erfq-settings-section').hide();
        $(target).show();
    });
});
</script>

<style>
.erfq-settings-wrap .erfq-settings-section {
    background: #fff;
    padding: 20px;
    margin-top: 20px;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.erfq-settings-wrap .erfq-settings-section h2 {
    margin-top: 0;
    padding-top: 0;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
}

.erfq-settings-wrap .erfq-settings-section h2:not(:first-child) {
    margin-top: 30px;
}
</style>
