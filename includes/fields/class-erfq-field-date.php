<?php
/**
 * Date field type
 *
 * @package Event_RFQ_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ERFQ_Field_Date
 */
class ERFQ_Field_Date extends ERFQ_Field_Type_Abstract {

    protected $type = 'date';
    protected $name = 'Date';
    protected $icon = 'dashicons-calendar';
    protected $category = 'basic';
    protected $description = 'Date picker input';

    protected function get_type_settings() {
        return array(
            'min_date' => array(
                'type'        => 'text',
                'label'       => __('Minimum Date', 'event-rfq-manager'),
                'default'     => '',
                'description' => __('YYYY-MM-DD format or "today"', 'event-rfq-manager'),
            ),
            'max_date' => array(
                'type'        => 'text',
                'label'       => __('Maximum Date', 'event-rfq-manager'),
                'default'     => '',
                'description' => __('YYYY-MM-DD format or "today"', 'event-rfq-manager'),
            ),
            'date_format' => array(
                'type'    => 'select',
                'label'   => __('Display Format', 'event-rfq-manager'),
                'default' => 'Y-m-d',
                'options' => array(
                    'Y-m-d'  => __('YYYY-MM-DD', 'event-rfq-manager'),
                    'd/m/Y'  => __('DD/MM/YYYY', 'event-rfq-manager'),
                    'm/d/Y'  => __('MM/DD/YYYY', 'event-rfq-manager'),
                    'F j, Y' => __('Month DD, YYYY', 'event-rfq-manager'),
                ),
            ),
        );
    }

    public function render($field_config, $value = null) {
        if ($value === null && isset($field_config['default_value'])) {
            $value = $field_config['default_value'];
        }

        $extra_attrs = array();

        // Handle min date
        if (!empty($field_config['min_date'])) {
            if ($field_config['min_date'] === 'today') {
                $extra_attrs['min'] = date('Y-m-d');
            } else {
                $extra_attrs['min'] = $field_config['min_date'];
            }
        }

        // Handle max date
        if (!empty($field_config['max_date'])) {
            if ($field_config['max_date'] === 'today') {
                $extra_attrs['max'] = date('Y-m-d');
            } else {
                $extra_attrs['max'] = $field_config['max_date'];
            }
        }

        $attrs = $this->build_attributes($field_config, $extra_attrs);

        $input = '<input type="date"' . $attrs . ' value="' . esc_attr($value) . '">';

        return $this->wrap_field($field_config, $input);
    }

    protected function validate_type($field_config, $value) {
        if (empty($value)) {
            return true;
        }

        $label = $field_config['label'] ?? __('Date', 'event-rfq-manager');

        // Validate date format
        $date = DateTime::createFromFormat('Y-m-d', $value);
        if (!$date || $date->format('Y-m-d') !== $value) {
            return new WP_Error(
                'invalid_date',
                sprintf(__('%s must be a valid date.', 'event-rfq-manager'), $label)
            );
        }

        // Check min date
        if (!empty($field_config['min_date'])) {
            $min = $field_config['min_date'] === 'today' ? date('Y-m-d') : $field_config['min_date'];
            if ($value < $min) {
                return new WP_Error(
                    'date_too_early',
                    sprintf(__('%s must be on or after %s.', 'event-rfq-manager'), $label, $min)
                );
            }
        }

        // Check max date
        if (!empty($field_config['max_date'])) {
            $max = $field_config['max_date'] === 'today' ? date('Y-m-d') : $field_config['max_date'];
            if ($value > $max) {
                return new WP_Error(
                    'date_too_late',
                    sprintf(__('%s must be on or before %s.', 'event-rfq-manager'), $label, $max)
                );
            }
        }

        return true;
    }

    public function sanitize($field_config, $value) {
        if (empty($value)) {
            return '';
        }

        // Ensure Y-m-d format
        $date = DateTime::createFromFormat('Y-m-d', $value);
        if ($date) {
            return $date->format('Y-m-d');
        }

        return sanitize_text_field($value);
    }

    public function get_display_value($field_config, $value) {
        if (empty($value)) {
            return '';
        }

        $format = isset($field_config['date_format']) ? $field_config['date_format'] : get_option('date_format');
        $date = DateTime::createFromFormat('Y-m-d', $value);

        if ($date) {
            return $date->format($format);
        }

        return $value;
    }
}
