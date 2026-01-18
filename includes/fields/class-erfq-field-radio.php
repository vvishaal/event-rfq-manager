<?php
/**
 * Radio field type
 *
 * @package Event_RFQ_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ERFQ_Field_Radio
 */
class ERFQ_Field_Radio extends ERFQ_Field_Type_Abstract {

    protected $type = 'radio';
    protected $name = 'Radio Buttons';
    protected $icon = 'dashicons-marker';
    protected $category = 'basic';
    protected $description = 'Single choice radio buttons';

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

        $layout_class = 'erfq-radio-layout-' . $layout;

        $input = '<div class="erfq-radio-group ' . esc_attr($layout_class) . '">';

        $index = 0;
        foreach ($options as $opt_value => $opt_label) {
            $opt_id = 'erfq-field-' . $field_id . '-' . $index;
            $checked = (strval($opt_value) === strval($value)) ? ' checked' : '';

            $input .= '<div class="erfq-radio-item">';
            $input .= '<label for="' . esc_attr($opt_id) . '">';
            $input .= '<input type="radio" id="' . esc_attr($opt_id) . '" ';
            $input .= 'name="erfq_fields[' . esc_attr($name) . ']" ';
            $input .= 'value="' . esc_attr($opt_value) . '"' . $checked;

            if ($required) {
                $input .= ' required';
            }

            $input .= '>';
            $input .= '<span class="erfq-radio-label">' . esc_html($opt_label) . '</span>';
            $input .= '</label>';
            $input .= '</div>';

            $index++;
        }

        $input .= '</div>';

        return $this->wrap_field($field_config, $input);
    }

    protected function validate_type($field_config, $value) {
        if (empty($value)) {
            return true;
        }

        $label = $field_config['label'] ?? __('This field', 'event-rfq-manager');
        $options = isset($field_config['options']) ? $field_config['options'] : array();
        $option_keys = array_keys($options);

        if (!in_array(strval($value), array_map('strval', $option_keys), true)) {
            return new WP_Error(
                'invalid_option',
                sprintf(__('%s contains an invalid selection.', 'event-rfq-manager'), $label)
            );
        }

        return true;
    }

    public function get_display_value($field_config, $value) {
        if (empty($value)) {
            return '';
        }

        $options = isset($field_config['options']) ? $field_config['options'] : array();

        if (isset($options[$value])) {
            return $options[$value];
        }

        return $value;
    }
}
