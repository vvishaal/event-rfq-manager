<?php
/**
 * Email field type
 *
 * @package Event_RFQ_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ERFQ_Field_Email
 */
class ERFQ_Field_Email extends ERFQ_Field_Type_Abstract {

    protected $type = 'email';
    protected $name = 'Email';
    protected $icon = 'dashicons-email';
    protected $category = 'basic';
    protected $description = 'Email address input with validation';

    protected function get_type_settings() {
        return array(
            'confirm_email' => array(
                'type'    => 'checkbox',
                'label'   => __('Show Confirm Email Field', 'event-rfq-manager'),
                'default' => false,
            ),
        );
    }

    public function render($field_config, $value = null) {
        if ($value === null && isset($field_config['default_value'])) {
            $value = $field_config['default_value'];
        }

        $attrs = $this->build_attributes($field_config);

        $input = '<input type="email"' . $attrs . ' value="' . esc_attr($value) . '">';

        // Add confirmation field if enabled
        if (!empty($field_config['confirm_email'])) {
            $field_id = $field_config['id'] ?? '';
            $name = $field_config['name'] ?? $field_id;

            $input .= '<div class="erfq-confirm-email-wrapper" style="margin-top: 10px;">';
            $input .= '<label for="erfq-field-' . esc_attr($field_id) . '-confirm">' . esc_html__('Confirm Email', 'event-rfq-manager') . '</label>';
            $input .= '<input type="email" id="erfq-field-' . esc_attr($field_id) . '-confirm" name="erfq_fields[' . esc_attr($name) . '_confirm]" class="erfq-field erfq-field-email-confirm" data-confirm-for="' . esc_attr($field_id) . '">';
            $input .= '</div>';
        }

        return $this->wrap_field($field_config, $input);
    }

    protected function validate_type($field_config, $value) {
        if (empty($value)) {
            return true;
        }

        $label = $field_config['label'] ?? __('Email', 'event-rfq-manager');

        if (!is_email($value)) {
            return new WP_Error(
                'invalid_email',
                sprintf(__('%s must be a valid email address.', 'event-rfq-manager'), $label)
            );
        }

        return true;
    }

    public function sanitize($field_config, $value) {
        return sanitize_email($value);
    }

    public function get_display_value($field_config, $value) {
        if (empty($value)) {
            return '';
        }
        return '<a href="mailto:' . esc_attr($value) . '">' . esc_html($value) . '</a>';
    }
}
