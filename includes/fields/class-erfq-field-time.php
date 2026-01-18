<?php
/**
 * Time field type
 *
 * @package Event_RFQ_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ERFQ_Field_Time
 */
class ERFQ_Field_Time extends ERFQ_Field_Type_Abstract {

    protected $type = 'time';
    protected $name = 'Time';
    protected $icon = 'dashicons-clock';
    protected $category = 'basic';
    protected $description = 'Time picker input';

    protected function get_type_settings() {
        return array(
            'min_time' => array(
                'type'        => 'text',
                'label'       => __('Minimum Time', 'event-rfq-manager'),
                'default'     => '',
                'description' => __('HH:MM format (24-hour)', 'event-rfq-manager'),
            ),
            'max_time' => array(
                'type'        => 'text',
                'label'       => __('Maximum Time', 'event-rfq-manager'),
                'default'     => '',
                'description' => __('HH:MM format (24-hour)', 'event-rfq-manager'),
            ),
            'step' => array(
                'type'        => 'number',
                'label'       => __('Step (minutes)', 'event-rfq-manager'),
                'default'     => 1,
                'min'         => 1,
                'max'         => 60,
            ),
            'time_format' => array(
                'type'    => 'select',
                'label'   => __('Display Format', 'event-rfq-manager'),
                'default' => 'H:i',
                'options' => array(
                    'H:i'   => __('24-hour (HH:MM)', 'event-rfq-manager'),
                    'g:i A' => __('12-hour (H:MM AM/PM)', 'event-rfq-manager'),
                ),
            ),
        );
    }

    public function render($field_config, $value = null) {
        if ($value === null && isset($field_config['default_value'])) {
            $value = $field_config['default_value'];
        }

        $extra_attrs = array();

        if (!empty($field_config['min_time'])) {
            $extra_attrs['min'] = $field_config['min_time'];
        }

        if (!empty($field_config['max_time'])) {
            $extra_attrs['max'] = $field_config['max_time'];
        }

        if (!empty($field_config['step'])) {
            // Convert minutes to seconds for HTML5 step
            $extra_attrs['step'] = absint($field_config['step']) * 60;
        }

        $attrs = $this->build_attributes($field_config, $extra_attrs);

        $input = '<input type="time"' . $attrs . ' value="' . esc_attr($value) . '">';

        return $this->wrap_field($field_config, $input);
    }

    protected function validate_type($field_config, $value) {
        if (empty($value)) {
            return true;
        }

        $label = $field_config['label'] ?? __('Time', 'event-rfq-manager');

        // Validate time format (HH:MM or HH:MM:SS)
        if (!preg_match('/^([01]?[0-9]|2[0-3]):([0-5][0-9])(:[0-5][0-9])?$/', $value)) {
            return new WP_Error(
                'invalid_time',
                sprintf(__('%s must be a valid time.', 'event-rfq-manager'), $label)
            );
        }

        // Check min time
        if (!empty($field_config['min_time'])) {
            if ($value < $field_config['min_time']) {
                return new WP_Error(
                    'time_too_early',
                    sprintf(__('%s must be at or after %s.', 'event-rfq-manager'), $label, $field_config['min_time'])
                );
            }
        }

        // Check max time
        if (!empty($field_config['max_time'])) {
            if ($value > $field_config['max_time']) {
                return new WP_Error(
                    'time_too_late',
                    sprintf(__('%s must be at or before %s.', 'event-rfq-manager'), $label, $field_config['max_time'])
                );
            }
        }

        return true;
    }

    public function sanitize($field_config, $value) {
        if (empty($value)) {
            return '';
        }

        // Validate and normalize to HH:MM format
        if (preg_match('/^([01]?[0-9]|2[0-3]):([0-5][0-9])(:[0-5][0-9])?$/', $value, $matches)) {
            return sprintf('%02d:%02d', intval($matches[1]), intval($matches[2]));
        }

        return sanitize_text_field($value);
    }

    public function get_display_value($field_config, $value) {
        if (empty($value)) {
            return '';
        }

        $format = isset($field_config['time_format']) ? $field_config['time_format'] : get_option('time_format');
        $time = DateTime::createFromFormat('H:i', $value);

        if ($time) {
            return $time->format($format);
        }

        return $value;
    }
}
