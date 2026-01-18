<?php
/**
 * Shortcodes class
 *
 * @package Event_RFQ_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ERFQ_Shortcodes
 *
 * Registers and handles plugin shortcodes
 */
class ERFQ_Shortcodes {

    /**
     * Register shortcodes
     */
    public function register() {
        add_shortcode('erfq_form', array($this, 'render_form'));
    }

    /**
     * Render form shortcode
     *
     * @param array $atts Shortcode attributes
     *
     * @return string
     */
    public function render_form($atts) {
        $atts = shortcode_atts(array(
            'id'    => 0,
            'class' => '',
        ), $atts, 'erfq_form');

        $form_id = absint($atts['id']);

        if (!$form_id) {
            if (current_user_can('manage_options')) {
                return '<p class="erfq-error">' . esc_html__('Please specify a form ID.', 'event-rfq-manager') . '</p>';
            }
            return '';
        }

        $form = ERFQ_Form::get_by_id($form_id);

        if (!$form) {
            if (current_user_can('manage_options')) {
                return '<p class="erfq-error">' . esc_html__('Form not found.', 'event-rfq-manager') . '</p>';
            }
            return '';
        }

        // Check if form is published
        if ($form->get_status() !== 'publish' && !current_user_can('manage_options')) {
            return '';
        }

        // Enqueue assets
        $this->enqueue_form_assets($form);

        // Render form
        $renderer = new ERFQ_Form_Renderer($form);
        return $renderer->render($atts['class']);
    }

    /**
     * Enqueue form assets
     *
     * @param ERFQ_Form $form Form object
     */
    protected function enqueue_form_assets($form) {
        // Styles
        wp_enqueue_style(
            'erfq-public',
            ERFQ_PLUGIN_URL . 'assets/css/public/form.css',
            array(),
            ERFQ_VERSION
        );

        // Scripts
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

        // Conditional logic JS if form uses it
        $fields = $form->get_fields();
        $has_conditional = false;
        foreach ($fields as $field) {
            if (!empty($field['conditional_enabled'])) {
                $has_conditional = true;
                break;
            }
        }

        if ($has_conditional) {
            wp_enqueue_script(
                'erfq-conditional-logic',
                ERFQ_PLUGIN_URL . 'assets/js/public/conditional-logic.js',
                array('jquery', 'erfq-form-handler'),
                ERFQ_VERSION,
                true
            );
        }

        // Multi-step JS if form uses it
        $settings = $form->get_settings();
        if (!empty($settings['multistep_enabled'])) {
            wp_enqueue_script(
                'erfq-multi-step',
                ERFQ_PLUGIN_URL . 'assets/js/public/multi-step.js',
                array('jquery', 'erfq-form-handler'),
                ERFQ_VERSION,
                true
            );
        }

        // Repeater JS if form has repeater fields
        $has_repeater = false;
        foreach ($fields as $field) {
            if ($field['type'] === 'repeater') {
                $has_repeater = true;
                break;
            }
        }

        if ($has_repeater) {
            wp_enqueue_script(
                'erfq-repeater',
                ERFQ_PLUGIN_URL . 'assets/js/public/repeater.js',
                array('jquery', 'erfq-form-handler'),
                ERFQ_VERSION,
                true
            );
        }

        // Localize
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
                'nextStep'         => __('Next', 'event-rfq-manager'),
                'prevStep'         => __('Previous', 'event-rfq-manager'),
                'addRow'           => __('Add Row', 'event-rfq-manager'),
                'removeRow'        => __('Remove', 'event-rfq-manager'),
            ),
        ));

        // reCAPTCHA
        if (!empty($settings['recaptcha_enabled'])) {
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
    }
}
