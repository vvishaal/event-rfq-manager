<?php
/**
 * Number field type
 *
 * @package Event_RFQ_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ERFQ_Field_Number
 */
class ERFQ_Field_Number extends ERFQ_Field_Type_Abstract {

    protected $type = 'number';
    protected $name = 'Number';
    protected $icon = 'dashicons-editor-ol';
    protected $category = 'basic';
    protected $description = 'Numeric input';

    protected function get_type_settings() {
        return array(
            'min' => array(
                'type'    => 'number',
                'label'   => __('Minimum Value', 'event-rfq-manager'),
                'default' => '',
            ),
            'max' => array(
                'type'    => 'number',
                'label'   => __('Maximum Value', 'event-rfq-manager'),
                'default' => '',
            ),
            'step' => array(
                'type'    => 'number',
                'label'   => __('Step', 'event-rfq-manager'),
                'default' => 1,
                'min'     => 0.001,
                'step'    => 'any',
            ),
            'prefix' => array(
                'type'    => 'text',
                'label'   => __('Prefix', 'event-rfq-manager'),
                'default' => '',
                'description' => __('e.g., $ for currency', 'event-rfq-manager'),
            ),
            'suffix' => array(
                'type'    => 'text',
                'label'   => __('Suffix', 'event-rfq-manager'),
                'default' => '',
                'description' => __('e.g., kg, items', 'event-rfq-manager'),
            ),
        );
    }

    public function render($field_config, $value = null) {
        if ($value === null && isset($field_config['default_value'])) {
            $value = $field_config['default_value'];
        }

        $extra_attrs = array();
        if (isset($field_config['min']) && $field_config['min'] !== '') {
            $extra_attrs['min'] = $field_config['min'];
        }
        if (isset($field_config['max']) && $field_config['max'] !== '') {
            $extra_attrs['max'] = $field_config['max'];
        }
        if (isset($field_config['step']) && $field_config['step'] !== '') {
            $extra_attrs['step'] = $field_config['step'];
        }

        $attrs = $this->build_attributes($field_config, $extra_attrs);

        $input = '';

        $has_prefix = !empty($field_config['prefix']);
        $has_suffix = !empty($field_config['suffix']);

        if ($has_prefix || $has_suffix) {
            $input .= '<div class="erfq-input-group">';
            if ($has_prefix) {
                $input .= '<span class="erfq-input-prefix">' . esc_html($field_config['prefix']) . '</span>';
            }
        }

        $input .= '<input type="number"' . $attrs . ' value="' . esc_attr($value) . '">';

        if ($has_prefix || $has_suffix) {
            if ($has_suffix) {
                $input .= '<span class="erfq-input-suffix">' . esc_html($field_config['suffix']) . '</span>';
            }
            $input .= '</div>';
        }

        return $this->wrap_field($field_config, $input);
    }

    protected function validate_type($field_config, $value) {
        if ($value === '' || $value === null) {
            return true;
        }

        $label = $field_config['label'] ?? __('This field', 'event-rfq-manager');

        if (!is_numeric($value)) {
            return new WP_Error(
                'not_numeric',
                sprintf(__('%s must be a number.', 'event-rfq-manager'), $label)
            );
        }

        $num = floatval($value);

        if (isset($field_config['min']) && $field_config['min'] !== '' && $num < floatval($field_config['min'])) {
            return new WP_Error(
                'min',
                sprintf(__('%s must be at least %s.', 'event-rfq-manager'), $label, $field_config['min'])
            );
        }

        if (isset($field_config['max']) && $field_config['max'] !== '' && $num > floatval($field_config['max'])) {
            return new WP_Error(
                'max',
                sprintf(__('%s must be no more than %s.', 'event-rfq-manager'), $label, $field_config['max'])
            );
        }

        return true;
    }

    public function sanitize($field_config, $value) {
        if ($value === '' || $value === null) {
            return '';
        }

        $step = isset($field_config['step']) ? floatval($field_config['step']) : 1;

        // If step is a whole number, return int, otherwise float
        if ($step == intval($step)) {
            return intval($value);
        }
        return floatval($value);
    }

    public function get_display_value($field_config, $value) {
        if ($value === '' || $value === null) {
            return '';
        }

        $display = '';

        if (!empty($field_config['prefix'])) {
            $display .= $field_config['prefix'];
        }

        $display .= $value;

        if (!empty($field_config['suffix'])) {
            $display .= ' ' . $field_config['suffix'];
        }

        return $display;
    }
}
