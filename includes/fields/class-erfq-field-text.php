<?php
/**
 * Text field type
 *
 * @package Event_RFQ_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ERFQ_Field_Text
 */
class ERFQ_Field_Text extends ERFQ_Field_Type_Abstract {

    protected $type = 'text';
    protected $name = 'Text';
    protected $icon = 'dashicons-editor-textcolor';
    protected $category = 'basic';
    protected $description = 'Single line text input';

    protected function get_type_settings() {
        return array(
            'min_length' => array(
                'type'    => 'number',
                'label'   => __('Minimum Length', 'event-rfq-manager'),
                'default' => '',
                'min'     => 0,
            ),
            'max_length' => array(
                'type'    => 'number',
                'label'   => __('Maximum Length', 'event-rfq-manager'),
                'default' => '',
                'min'     => 0,
            ),
            'pattern' => array(
                'type'        => 'text',
                'label'       => __('Validation Pattern (Regex)', 'event-rfq-manager'),
                'default'     => '',
                'description' => __('Optional regex pattern for validation', 'event-rfq-manager'),
            ),
        );
    }

    public function render($field_config, $value = null) {
        if ($value === null && isset($field_config['default_value'])) {
            $value = $field_config['default_value'];
        }

        $extra_attrs = array();
        if (!empty($field_config['min_length'])) {
            $extra_attrs['minlength'] = absint($field_config['min_length']);
        }
        if (!empty($field_config['max_length'])) {
            $extra_attrs['maxlength'] = absint($field_config['max_length']);
        }
        if (!empty($field_config['pattern'])) {
            $extra_attrs['pattern'] = $field_config['pattern'];
        }

        $attrs = $this->build_attributes($field_config, $extra_attrs);

        $input = '<input type="text"' . $attrs . ' value="' . esc_attr($value) . '">';

        return $this->wrap_field($field_config, $input);
    }

    protected function validate_type($field_config, $value) {
        if (empty($value)) {
            return true;
        }

        $label = $field_config['label'] ?? __('This field', 'event-rfq-manager');

        if (!empty($field_config['min_length']) && strlen($value) < $field_config['min_length']) {
            return new WP_Error(
                'min_length',
                sprintf(__('%s must be at least %d characters.', 'event-rfq-manager'), $label, $field_config['min_length'])
            );
        }

        if (!empty($field_config['max_length']) && strlen($value) > $field_config['max_length']) {
            return new WP_Error(
                'max_length',
                sprintf(__('%s must be no more than %d characters.', 'event-rfq-manager'), $label, $field_config['max_length'])
            );
        }

        if (!empty($field_config['pattern']) && !preg_match('/' . $field_config['pattern'] . '/', $value)) {
            return new WP_Error(
                'pattern',
                sprintf(__('%s format is invalid.', 'event-rfq-manager'), $label)
            );
        }

        return true;
    }
}
