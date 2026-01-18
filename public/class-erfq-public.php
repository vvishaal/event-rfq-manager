<?php
/**
 * Public-facing functionality
 *
 * @package Event_RFQ_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ERFQ_Public
 *
 * Handles public-facing functionality
 */
class ERFQ_Public {

    /**
     * Enqueue public styles
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            'erfq-public',
            ERFQ_PLUGIN_URL . 'assets/css/public/form.css',
            array(),
            ERFQ_VERSION
        );
    }

    /**
     * Enqueue public scripts
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            'erfq-form-handler',
            ERFQ_PLUGIN_URL . 'assets/js/public/form-handler.js',
            array('jquery'),
            ERFQ_VERSION,
            true
        );

        wp_enqueue_script(
            'erfq-validation',
            ERFQ_PLUGIN_URL . 'assets/js/public/validation.js',
            array('jquery', 'erfq-form-handler'),
            ERFQ_VERSION,
            true
        );

        // Localize scripts
        wp_localize_script('erfq-form-handler', 'erfqPublic', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('erfq_public_nonce'),
            'i18n'    => array(
                'submitting'       => __('Submitting...', 'event-rfq-manager'),
                'success'          => __('Thank you for your submission!', 'event-rfq-manager'),
                'error'            => __('Something went wrong. Please try again.', 'event-rfq-manager'),
                'requiredField'    => __('This field is required.', 'event-rfq-manager'),
                'invalidEmail'     => __('Please enter a valid email address.', 'event-rfq-manager'),
                'invalidPhone'     => __('Please enter a valid phone number.', 'event-rfq-manager'),
                'fileTooLarge'     => __('File is too large.', 'event-rfq-manager'),
                'invalidFileType'  => __('File type not allowed.', 'event-rfq-manager'),
            ),
        ));

        // reCAPTCHA if enabled
        $site_key = get_option('erfq_recaptcha_site_key');
        if ($site_key) {
            wp_enqueue_script(
                'google-recaptcha',
                'https://www.google.com/recaptcha/api.js?render=' . esc_attr($site_key),
                array(),
                null,
                true
            );

            wp_localize_script('erfq-form-handler', 'erfqRecaptcha', array(
                'siteKey' => $site_key,
            ));
        }
    }

    /**
     * Handle form preview
     */
    public function handle_preview() {
        if (!isset($_GET['erfq_preview'])) {
            return;
        }

        $form_id = absint($_GET['erfq_preview']);

        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to preview this form.', 'event-rfq-manager'));
        }

        $form = ERFQ_Form::get_by_id($form_id);

        if (!$form) {
            wp_die(__('Form not found.', 'event-rfq-manager'));
        }

        // Display preview
        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo('charset'); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title><?php echo esc_html($form->get_title()); ?> - <?php esc_html_e('Preview', 'event-rfq-manager'); ?></title>
            <?php wp_head(); ?>
            <style>
                body { background: #f0f0f1; padding: 40px 20px; margin: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif; }
                .erfq-preview-container { max-width: 800px; margin: 0 auto; background: #fff; padding: 30px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
                .erfq-preview-header { margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid #eee; }
                .erfq-preview-header h1 { margin: 0 0 10px; font-size: 24px; }
                .erfq-preview-notice { background: #fff3cd; border: 1px solid #ffc107; padding: 10px 15px; border-radius: 4px; margin-bottom: 20px; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class="erfq-preview-container">
                <div class="erfq-preview-header">
                    <h1><?php echo esc_html($form->get_title()); ?></h1>
                    <div class="erfq-preview-notice">
                        <?php esc_html_e('This is a preview. Form submissions in preview mode are not saved.', 'event-rfq-manager'); ?>
                    </div>
                </div>
                <?php
                $renderer = new ERFQ_Form_Renderer($form);
                echo $renderer->render();
                ?>
            </div>
            <?php wp_footer(); ?>
        </body>
        </html>
        <?php
        exit;
    }
}
