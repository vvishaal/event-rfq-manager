<?php
/**
 * Phone field type
 *
 * @package Event_RFQ_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ERFQ_Field_Phone
 */
class ERFQ_Field_Phone extends ERFQ_Field_Type_Abstract {

    protected $type = 'phone';
    protected $name = 'Phone';
    protected $icon = 'dashicons-phone';
    protected $category = 'basic';
    protected $description = 'Phone number input';

    protected function get_type_settings() {
        return array(
            'format' => array(
                'type'    => 'select',
                'label'   => __('Format', 'event-rfq-manager'),
                'default' => 'international',
                'options' => array(
                    'international' => __('International', 'event-rfq-manager'),
                    'us'            => __('US Format', 'event-rfq-manager'),
                    'any'           => __('Any Format', 'event-rfq-manager'),
                ),
            ),
        );
    }

    public function render($field_config, $value = null) {
        if ($value === null && isset($field_config['default_value'])) {
            $value = $field_config['default_value'];
        }

        $attrs = $this->build_attributes($field_config, array(
            'type' => 'tel',
        ));

        $input = '<input' . $attrs . ' value="' . esc_attr($value) . '">';

        return $this->wrap_field($field_config, $input);
    }

    protected function validate_type($field_config, $value) {
        if (empty($value)) {
            return true;
        }

        $label = $field_config['label'] ?? __('Phone', 'event-rfq-manager');
        $format = $field_config['format'] ?? 'any';

        // Remove common formatting characters for validation
        $cleaned = preg_replace('/[\s\-\.\(\)]+/', '', $value);

        // Check if it contains only valid phone characters
        if (!preg_match('/^\+?[0-9]+$/', $cleaned)) {
            return new WP_Error(
                'invalid_phone',
                sprintf(__('%s must be a valid phone number.', 'event-rfq-manager'), $label)
            );
        }

        // Format-specific validation
        if ($format === 'us') {
            // US format: 10 digits (with or without country code)
            if (!preg_match('/^(\+?1)?[0-9]{10}$/', $cleaned)) {
                return new WP_Error(
                    'invalid_phone',
                    sprintf(__('%s must be a valid US phone number.', 'event-rfq-manager'), $label)
                );
            }
        } elseif ($format === 'international') {
            // International: at least 7 digits
            if (strlen($cleaned) < 7) {
                return new WP_Error(
                    'invalid_phone',
                    sprintf(__('%s must be a valid phone number.', 'event-rfq-manager'), $label)
                );
            }
        }

        return true;
    }

    public function sanitize($field_config, $value) {
        // Keep original formatting but sanitize
        return preg_replace('/[^0-9\+\-\.\(\)\s]/', '', $value);
    }

    public function get_display_value($field_config, $value) {
        if (empty($value)) {
            return '';
        }
        return '<a href="tel:' . esc_attr(preg_replace('/[^0-9\+]/', '', $value)) . '">' . esc_html($value) . '</a>';
    }
}
