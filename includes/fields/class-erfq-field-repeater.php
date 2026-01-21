<?php
/**
 * Repeater field type - Simple "Add More" functionality
 *
 * @package Event_RFQ_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ERFQ_Field_Repeater
 *
 * Allows users to add multiple values for a field by clicking (+)
 */
class ERFQ_Field_Repeater extends ERFQ_Field_Type_Abstract {

    protected $type = 'repeater';
    protected $name = 'Repeater (Add More)';
    protected $icon = 'dashicons-plus-alt';
    protected $category = 'advanced';
    protected $description = 'Allow users to add multiple entries for a field';

    protected function get_type_settings() {
        return array(
            'field_type' => array(
                'type'    => 'select',
                'label'   => __('Field Type', 'event-rfq-manager'),
                'default' => 'text',
                'options' => array(
                    'text'   => __('Text', 'event-rfq-manager'),
                    'email'  => __('Email', 'event-rfq-manager'),
                    'phone'  => __('Phone', 'event-rfq-manager'),
                    'number' => __('Number', 'event-rfq-manager'),
                    'date'   => __('Date', 'event-rfq-manager'),
                ),
            ),
            'min_rows' => array(
                'type'    => 'number',
                'label'   => __('Minimum Entries', 'event-rfq-manager'),
                'default' => 1,
                'min'     => 0,
            ),
            'max_rows' => array(
                'type'    => 'number',
                'label'   => __('Maximum Entries', 'event-rfq-manager'),
                'default' => 10,
                'min'     => 1,
            ),
            'add_button_text' => array(
                'type'    => 'text',
                'label'   => __('Add Button Text', 'event-rfq-manager'),
                'default' => '+ Add More',
            ),
        );
    }

    public function render($field_config, $value = null) {
        $field_id = isset($field_config['id']) ? $field_config['id'] : '';
        $name = isset($field_config['name']) ? $field_config['name'] : $field_id;
        $field_type = isset($field_config['field_type']) ? $field_config['field_type'] : 'text';
        $min_rows = isset($field_config['min_rows']) ? absint($field_config['min_rows']) : 1;
        $max_rows = isset($field_config['max_rows']) ? absint($field_config['max_rows']) : 10;
        $add_text = isset($field_config['add_button_text']) ? $field_config['add_button_text'] : __('+ Add More', 'event-rfq-manager');
        $placeholder = isset($field_config['placeholder']) ? $field_config['placeholder'] : '';

        // Parse existing values
        $values = is_array($value) ? array_values($value) : array();

        // Ensure minimum rows
        while (count($values) < max(1, $min_rows)) {
            $values[] = '';
        }

        $input_type = $this->get_input_type($field_type);

        $html = '<div class="erfq-repeater-wrapper" ';
        $html .= 'data-field-id="' . esc_attr($field_id) . '" ';
        $html .= 'data-field-type="' . esc_attr($field_type) . '" ';
        $html .= 'data-min-rows="' . esc_attr($min_rows) . '" ';
        $html .= 'data-max-rows="' . esc_attr($max_rows) . '" ';
        $html .= 'data-placeholder="' . esc_attr($placeholder) . '">';

        // Rows container
        $html .= '<div class="erfq-repeater-rows">';

        foreach ($values as $index => $row_value) {
            $html .= $this->render_row($name, $index, $row_value, $input_type, $placeholder, count($values) <= $min_rows);
        }

        $html .= '</div>'; // .erfq-repeater-rows

        // Row template for JavaScript
        $html .= '<script type="text/template" class="erfq-repeater-row-template">';
        $html .= $this->render_row($name, '{{INDEX}}', '', $input_type, $placeholder, false);
        $html .= '</script>';

        // Add button
        $html .= '<button type="button" class="erfq-repeater-add button">' . esc_html($add_text) . '</button>';

        $html .= '</div>'; // .erfq-repeater-wrapper

        return $this->wrap_field($field_config, $html);
    }

    /**
     * Get HTML input type from field type
     *
     * @param string $field_type Field type
     *
     * @return string Input type
     */
    protected function get_input_type($field_type) {
        $type_map = array(
            'text'   => 'text',
            'email'  => 'email',
            'phone'  => 'tel',
            'number' => 'number',
            'date'   => 'date',
        );

        return isset($type_map[$field_type]) ? $type_map[$field_type] : 'text';
    }

    /**
     * Render a single repeater row
     *
     * @param string $name        Field name
     * @param mixed  $index       Row index
     * @param string $value       Row value
     * @param string $input_type  HTML input type
     * @param string $placeholder Placeholder text
     * @param bool   $hide_remove Whether to hide remove button
     *
     * @return string
     */
    protected function render_row($name, $index, $value, $input_type, $placeholder, $hide_remove) {
        $row = '<div class="erfq-repeater-row" data-row-index="' . esc_attr($index) . '">';

        $row .= '<div class="erfq-repeater-row-content">';
        $row .= '<input type="' . esc_attr($input_type) . '" ';
        $row .= 'name="erfq_fields[' . esc_attr($name) . '][]" ';
        $row .= 'value="' . esc_attr($value) . '" ';
        $row .= 'placeholder="' . esc_attr($placeholder) . '" ';
        $row .= 'class="erfq-input erfq-repeater-input">';
        $row .= '</div>';

        $row .= '<div class="erfq-repeater-row-actions">';
        $remove_style = $hide_remove ? ' style="visibility:hidden;"' : '';
        $row .= '<button type="button" class="erfq-repeater-remove button"' . $remove_style . ' title="' . esc_attr__('Remove', 'event-rfq-manager') . '">&times;</button>';
        $row .= '</div>';

        $row .= '</div>'; // .erfq-repeater-row

        return $row;
    }

    protected function validate_type($field_config, $value) {
        if (!is_array($value)) {
            return true;
        }

        $label = isset($field_config['label']) ? $field_config['label'] : __('This field', 'event-rfq-manager');
        $min_rows = isset($field_config['min_rows']) ? absint($field_config['min_rows']) : 1;
        $max_rows = isset($field_config['max_rows']) ? absint($field_config['max_rows']) : 10;
        $field_type = isset($field_config['field_type']) ? $field_config['field_type'] : 'text';

        // Filter out empty values
        $filled_values = array_filter($value, function($v) {
            return $v !== '' && $v !== null;
        });

        $count = count($filled_values);

        if ($min_rows > 0 && $count < $min_rows) {
            return new WP_Error(
                'min_rows',
                sprintf(__('%s requires at least %d entries.', 'event-rfq-manager'), $label, $min_rows)
            );
        }

        if ($count > $max_rows) {
            return new WP_Error(
                'max_rows',
                sprintf(__('%s allows at most %d entries.', 'event-rfq-manager'), $label, $max_rows)
            );
        }

        // Validate each value based on field type
        foreach ($filled_values as $i => $v) {
            if ($field_type === 'email' && !is_email($v)) {
                return new WP_Error(
                    'invalid_email',
                    sprintf(__('%s: Entry %d must be a valid email address.', 'event-rfq-manager'), $label, $i + 1)
                );
            }
        }

        return true;
    }

    public function sanitize($field_config, $value) {
        if (!is_array($value)) {
            return array();
        }

        $field_type = isset($field_config['field_type']) ? $field_config['field_type'] : 'text';
        $sanitized = array();

        foreach ($value as $v) {
            if ($v === '' || $v === null) {
                continue;
            }

            switch ($field_type) {
                case 'email':
                    $sanitized[] = sanitize_email($v);
                    break;
                case 'number':
                    $sanitized[] = floatval($v);
                    break;
                default:
                    $sanitized[] = sanitize_text_field($v);
            }
        }

        return $sanitized;
    }

    public function get_display_value($field_config, $value) {
        if (!is_array($value) || empty($value)) {
            return '';
        }

        // Return as bulleted list
        $output = '<ul class="erfq-repeater-display">';
        foreach ($value as $v) {
            if ($v !== '' && $v !== null) {
                $output .= '<li>' . esc_html($v) . '</li>';
            }
        }
        $output .= '</ul>';

        return $output;
    }

    public function get_export_value($field_config, $value) {
        if (!is_array($value) || empty($value)) {
            return '';
        }

        // Filter empty values and join with separator
        $filled = array_filter($value, function($v) {
            return $v !== '' && $v !== null;
        });

        return implode(', ', $filled);
    }
}
