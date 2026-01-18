<?php
/**
 * Checkbox field type
 *
 * @package Event_RFQ_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ERFQ_Field_Checkbox
 */
class ERFQ_Field_Checkbox extends ERFQ_Field_Type_Abstract {

    protected $type = 'checkbox';
    protected $name = 'Checkboxes';
    protected $icon = 'dashicons-yes';
    protected $category = 'basic';
    protected $description = 'Multiple choice checkboxes';

    protected function get_type_settings() {
        return array(
            'options' => array(
                'type'    => 'options',
                'label'   => __('Options', 'event-rfq-manager'),
                'default' => array(),
            ),
            'layout' => array(
                'type'    => 'select',
                'label'   => __('Layout', 'event-rfq-manager'),
                'default' => 'vertical',
                'options' => array(
                    'vertical'   => __('Vertical', 'event-rfq-manager'),
                    'horizontal' => __('Horizontal', 'event-rfq-manager'),
                    'grid-2'     => __('2 Columns', 'event-rfq-manager'),
                    'grid-3'     => __('3 Columns', 'event-rfq-manager'),
                ),
            ),
            'min_selections' => array(
                'type'    => 'number',
                'label'   => __('Minimum Selections', 'event-rfq-manager'),
                'default' => '',
                'min'     => 0,
            ),
            'max_selections' => array(
                'type'    => 'number',
                'label'   => __('Maximum Selections', 'event-rfq-manager'),
                'default' => '',
                'min'     => 0,
            ),
        );
    }

    public function render($field_config, $value = null) {
        if ($value === null && isset($field_config['default_value'])) {
            $value = $field_config['default_value'];
        }

        $options = isset($field_config['options']) ? $field_config['options'] : array();
        $layout = isset($field_config['layout']) ? $field_config['layout'] : 'vertical';

        $field_id = $field_config['id'] ?? '';
        $name = $field_config['name'] ?? $field_id;
        $required = !empty($field_config['required']);

        // Normalize value for comparison
        $selected_values = is_array($value) ? $value : (empty($value) ? array() : array($value));
        $selected_values = array_map('strval', $selected_values);

        $layout_class = 'erfq-checkbox-layout-' . $layout;

        $input = '<div class="erfq-checkbox-group ' . esc_attr($layout_class) . '">';

        $index = 0;
        foreach ($options as $opt_value => $opt_label) {
            $opt_id = 'erfq-field-' . $field_id . '-' . $index;
            $is_checked = in_array(strval($opt_value), $selected_values, true);
            $checked = $is_checked ? ' checked' : '';

            $input .= '<div class="erfq-checkbox-item">';
            $input .= '<label for="' . esc_attr($opt_id) . '">';
            $input .= '<input type="checkbox" id="' . esc_attr($opt_id) . '" ';
            $input .= 'name="erfq_fields[' . esc_attr($name) . '][]" ';
            $input .= 'value="' . esc_attr($opt_value) . '"' . $checked;

            if ($required && $index === 0) {
                $input .= ' data-required="true"';
            }

            $input .= '>';
            $input .= '<span class="erfq-checkbox-label">' . esc_html($opt_label) . '</span>';
            $input .= '</label>';
            $input .= '</div>';

            $index++;
        }

        $input .= '</div>';

        return $this->wrap_field($field_config, $input);
    }

    protected function validate_type($field_config, $value) {
        $label = $field_config['label'] ?? __('This field', 'event-rfq-manager');
        $options = isset($field_config['options']) ? $field_config['options'] : array();

        $values = is_array($value) ? $value : (empty($value) ? array() : array($value));
        $count = count($values);

        // Check valid options
        $option_keys = array_keys($options);
        foreach ($values as $val) {
            if (!in_array(strval($val), array_map('strval', $option_keys), true)) {
                return new WP_Error(
                    'invalid_option',
                    sprintf(__('%s contains an invalid selection.', 'event-rfq-manager'), $label)
                );
            }
        }

        // Check min selections
        if (!empty($field_config['min_selections']) && $count < $field_config['min_selections']) {
            return new WP_Error(
                'min_selections',
                sprintf(__('%s requires at least %d selections.', 'event-rfq-manager'), $label, $field_config['min_selections'])
            );
        }

        // Check max selections
        if (!empty($field_config['max_selections']) && $count > $field_config['max_selections']) {
            return new WP_Error(
                'max_selections',
                sprintf(__('%s allows at most %d selections.', 'event-rfq-manager'), $label, $field_config['max_selections'])
            );
        }

        return true;
    }

    public function sanitize($field_config, $value) {
        if (!is_array($value)) {
            return empty($value) ? array() : array(sanitize_text_field($value));
        }
        return array_map('sanitize_text_field', $value);
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
