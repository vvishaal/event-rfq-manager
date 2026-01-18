<?php
/**
 * Textarea field type
 *
 * @package Event_RFQ_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ERFQ_Field_Textarea
 */
class ERFQ_Field_Textarea extends ERFQ_Field_Type_Abstract {

    protected $type = 'textarea';
    protected $name = 'Textarea';
    protected $icon = 'dashicons-text';
    protected $category = 'basic';
    protected $description = 'Multi-line text input';

    protected function get_type_settings() {
        return array(
            'rows' => array(
                'type'    => 'number',
                'label'   => __('Rows', 'event-rfq-manager'),
                'default' => 5,
                'min'     => 2,
                'max'     => 20,
            ),
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
        );
    }

    public function render($field_config, $value = null) {
        if ($value === null && isset($field_config['default_value'])) {
            $value = $field_config['default_value'];
        }

        $rows = isset($field_config['rows']) ? absint($field_config['rows']) : 5;

        $extra_attrs = array(
            'rows' => $rows,
        );

        if (!empty($field_config['min_length'])) {
            $extra_attrs['minlength'] = absint($field_config['min_length']);
        }
        if (!empty($field_config['max_length'])) {
            $extra_attrs['maxlength'] = absint($field_config['max_length']);
        }

        $attrs = $this->build_attributes($field_config, $extra_attrs);

        $input = '<textarea' . $attrs . '>' . esc_textarea($value) . '</textarea>';

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

        return true;
    }

    public function sanitize($field_config, $value) {
        return sanitize_textarea_field($value);
    }

    public function get_display_value($field_config, $value) {
        if (empty($value)) {
            return '';
        }
        return nl2br(esc_html($value));
    }
}
