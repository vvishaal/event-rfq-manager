<?php
/**
 * Select field type
 *
 * @package Event_RFQ_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ERFQ_Field_Select
 */
class ERFQ_Field_Select extends ERFQ_Field_Type_Abstract {

    protected $type = 'select';
    protected $name = 'Dropdown';
    protected $icon = 'dashicons-arrow-down-alt2';
    protected $category = 'basic';
    protected $description = 'Dropdown select list';

    protected function get_type_settings() {
        return array(
            'options' => array(
                'type'    => 'options',
                'label'   => __('Options', 'event-rfq-manager'),
                'default' => array(),
            ),
            'multiple' => array(
                'type'    => 'checkbox',
                'label'   => __('Allow Multiple Selections', 'event-rfq-manager'),
                'default' => false,
            ),
            'empty_option' => array(
                'type'    => 'text',
                'label'   => __('Empty Option Text', 'event-rfq-manager'),
                'default' => '-- Select --',
                'description' => __('First option text (leave empty to remove)', 'event-rfq-manager'),
            ),
        );
    }

    public function render($field_config, $value = null) {
        if ($value === null && isset($field_config['default_value'])) {
            $value = $field_config['default_value'];
        }

        $options = isset($field_config['options']) ? $field_config['options'] : array();
        $multiple = !empty($field_config['multiple']);
        $empty_option = isset($field_config['empty_option']) ? $field_config['empty_option'] : '-- Select --';

        $extra_attrs = array();
        if ($multiple) {
            $extra_attrs['multiple'] = 'multiple';
            // Add [] to name for multiple values
            $field_id = $field_config['id'] ?? '';
            $name = $field_config['name'] ?? $field_id;
            $extra_attrs['name'] = 'erfq_fields[' . $name . '][]';
        }

        $attrs = $this->build_attributes($field_config, $extra_attrs);

        $input = '<select' . $attrs . '>';

        // Add empty option for single select
        if (!$multiple && !empty($empty_option)) {
            $input .= '<option value="">' . esc_html($empty_option) . '</option>';
        }

        // Normalize value for comparison
        $selected_values = is_array($value) ? $value : array($value);
        $selected_values = array_map('strval', $selected_values);

        foreach ($options as $opt_value => $opt_label) {
            $is_selected = in_array(strval($opt_value), $selected_values, true);
            $selected = $is_selected ? ' selected' : '';
            $input .= '<option value="' . esc_attr($opt_value) . '"' . $selected . '>' . esc_html($opt_label) . '</option>';
        }

        $input .= '</select>';

        return $this->wrap_field($field_config, $input);
    }

    protected function validate_type($field_config, $value) {
        if (empty($value)) {
            return true;
        }

        $label = $field_config['label'] ?? __('This field', 'event-rfq-manager');
        $options = isset($field_config['options']) ? $field_config['options'] : array();
        $option_keys = array_keys($options);

        // Handle multiple values
        $values = is_array($value) ? $value : array($value);

        foreach ($values as $val) {
            if (!in_array(strval($val), array_map('strval', $option_keys), true)) {
                return new WP_Error(
                    'invalid_option',
                    sprintf(__('%s contains an invalid selection.', 'event-rfq-manager'), $label)
                );
            }
        }

        return true;
    }

    public function sanitize($field_config, $value) {
        if (is_array($value)) {
            return array_map('sanitize_text_field', $value);
        }
        return sanitize_text_field($value);
    }

    public function get_display_value($field_config, $value) {
        if (empty($value)) {
            return '';
        }

        $options = isset($field_config['options']) ? $field_config['options'] : array();
        $values = is_array($value) ? $value : array($value);
        $labels = array();

        foreach ($values as $val) {
            if (isset($options[$val])) {
                $labels[] = $options[$val];
            } else {
                $labels[] = $val;
            }
        }

        return implode(', ', $labels);
    }
}
