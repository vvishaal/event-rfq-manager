<?php
/**
 * Repeater field type
 *
 * @package Event_RFQ_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ERFQ_Field_Repeater
 */
class ERFQ_Field_Repeater extends ERFQ_Field_Type_Abstract {

    protected $type = 'repeater';
    protected $name = 'Repeater';
    protected $icon = 'dashicons-plus-alt';
    protected $category = 'advanced';
    protected $description = 'Dynamic repeatable field group';

    protected function get_type_settings() {
        return array(
            'sub_fields' => array(
                'type'    => 'repeater_fields',
                'label'   => __('Sub Fields', 'event-rfq-manager'),
                'default' => array(),
            ),
            'min_rows' => array(
                'type'    => 'number',
                'label'   => __('Minimum Rows', 'event-rfq-manager'),
                'default' => 0,
                'min'     => 0,
            ),
            'max_rows' => array(
                'type'    => 'number',
                'label'   => __('Maximum Rows', 'event-rfq-manager'),
                'default' => 10,
                'min'     => 1,
            ),
            'add_button_text' => array(
                'type'    => 'text',
                'label'   => __('Add Button Text', 'event-rfq-manager'),
                'default' => '+ Add Row',
            ),
            'layout' => array(
                'type'    => 'select',
                'label'   => __('Layout', 'event-rfq-manager'),
                'default' => 'row',
                'options' => array(
                    'row'   => __('Row', 'event-rfq-manager'),
                    'block' => __('Block', 'event-rfq-manager'),
                    'table' => __('Table', 'event-rfq-manager'),
                ),
            ),
        );
    }

    public function render($field_config, $value = null) {
        $field_id = $field_config['id'] ?? '';
        $name = $field_config['name'] ?? $field_id;
        $sub_fields = isset($field_config['sub_fields']) ? $field_config['sub_fields'] : array();
        $min_rows = isset($field_config['min_rows']) ? absint($field_config['min_rows']) : 0;
        $max_rows = isset($field_config['max_rows']) ? absint($field_config['max_rows']) : 10;
        $add_text = isset($field_config['add_button_text']) ? $field_config['add_button_text'] : __('+ Add Row', 'event-rfq-manager');
        $layout = isset($field_config['layout']) ? $field_config['layout'] : 'row';

        // Parse existing values
        $rows = is_array($value) ? $value : array();

        // Ensure minimum rows
        while (count($rows) < $min_rows) {
            $rows[] = array();
        }

        // If no rows and min is 0, add one empty row
        if (empty($rows)) {
            $rows[] = array();
        }

        $input = '<div class="erfq-repeater-wrapper" ';
        $input .= 'data-field-id="' . esc_attr($field_id) . '" ';
        $input .= 'data-min-rows="' . esc_attr($min_rows) . '" ';
        $input .= 'data-max-rows="' . esc_attr($max_rows) . '" ';
        $input .= 'data-layout="' . esc_attr($layout) . '">';

        // Rows container
        $input .= '<div class="erfq-repeater-rows">';

        foreach ($rows as $row_index => $row_values) {
            $input .= $this->render_row($field_id, $name, $sub_fields, $row_index, $row_values, $layout);
        }

        $input .= '</div>'; // .erfq-repeater-rows

        // Row template for JavaScript
        $input .= '<script type="text/template" class="erfq-repeater-row-template">';
        $input .= $this->render_row($field_id, $name, $sub_fields, '{{INDEX}}', array(), $layout);
        $input .= '</script>';

        // Add button
        $input .= '<button type="button" class="erfq-repeater-add button">' . esc_html($add_text) . '</button>';

        $input .= '</div>'; // .erfq-repeater-wrapper

        return $this->wrap_field($field_config, $input);
    }

    /**
     * Render a single repeater row
     *
     * @param string $field_id   Parent field ID
     * @param string $name       Parent field name
     * @param array  $sub_fields Sub field configurations
     * @param mixed  $row_index  Row index
     * @param array  $values     Row values
     * @param string $layout     Layout type
     *
     * @return string
     */
    protected function render_row($field_id, $name, $sub_fields, $row_index, $values, $layout) {
        $row = '<div class="erfq-repeater-row erfq-repeater-layout-' . esc_attr($layout) . '" data-row-index="' . esc_attr($row_index) . '">';

        $row .= '<div class="erfq-repeater-row-content">';

        $registry = ERFQ_Field_Registry::get_instance();

        foreach ($sub_fields as $sub_field) {
            $sub_id = isset($sub_field['id']) ? $sub_field['id'] : '';
            $sub_type = isset($sub_field['type']) ? $sub_field['type'] : 'text';
            $sub_value = isset($values[$sub_id]) ? $values[$sub_id] : '';

            // Modify field config for repeater context
            $sub_config = $sub_field;
            $sub_config['id'] = $field_id . '_' . $row_index . '_' . $sub_id;
            $sub_config['name'] = $name . '][' . $row_index . '][' . $sub_id;

            // Get the field type renderer
            $field_type = $registry->get($sub_type);
            if ($field_type) {
                $row .= '<div class="erfq-repeater-sub-field erfq-repeater-sub-' . esc_attr($sub_id) . '">';
                $row .= $field_type->render($sub_config, $sub_value);
                $row .= '</div>';
            }
        }

        $row .= '</div>'; // .erfq-repeater-row-content

        $row .= '<div class="erfq-repeater-row-actions">';
        $row .= '<button type="button" class="erfq-repeater-remove button" title="' . esc_attr__('Remove', 'event-rfq-manager') . '">&times;</button>';
        $row .= '</div>';

        $row .= '</div>'; // .erfq-repeater-row

        return $row;
    }

    protected function validate_type($field_config, $value) {
        if (!is_array($value)) {
            return true;
        }

        $label = $field_config['label'] ?? __('This field', 'event-rfq-manager');
        $min_rows = isset($field_config['min_rows']) ? absint($field_config['min_rows']) : 0;
        $max_rows = isset($field_config['max_rows']) ? absint($field_config['max_rows']) : 10;
        $sub_fields = isset($field_config['sub_fields']) ? $field_config['sub_fields'] : array();

        // Filter out empty rows
        $filled_rows = array_filter($value, function($row) {
            return !empty(array_filter($row));
        });

        $row_count = count($filled_rows);

        if ($min_rows > 0 && $row_count < $min_rows) {
            return new WP_Error(
                'min_rows',
                sprintf(__('%s requires at least %d entries.', 'event-rfq-manager'), $label, $min_rows)
            );
        }

        if ($row_count > $max_rows) {
            return new WP_Error(
                'max_rows',
                sprintf(__('%s allows at most %d entries.', 'event-rfq-manager'), $label, $max_rows)
            );
        }

        // Validate sub-fields
        $registry = ERFQ_Field_Registry::get_instance();

        foreach ($filled_rows as $row_index => $row_values) {
            foreach ($sub_fields as $sub_field) {
                $sub_id = isset($sub_field['id']) ? $sub_field['id'] : '';
                $sub_value = isset($row_values[$sub_id]) ? $row_values[$sub_id] : '';

                $validation = $registry->validate_field($sub_field, $sub_value);
                if (is_wp_error($validation)) {
                    return new WP_Error(
                        'sub_field_error',
                        sprintf(__('%s (row %d): %s', 'event-rfq-manager'), $label, $row_index + 1, $validation->get_error_message())
                    );
                }
            }
        }

        return true;
    }

    public function sanitize($field_config, $value) {
        if (!is_array($value)) {
            return array();
        }

        $sub_fields = isset($field_config['sub_fields']) ? $field_config['sub_fields'] : array();
        $registry = ERFQ_Field_Registry::get_instance();
        $sanitized = array();

        foreach ($value as $row_index => $row_values) {
            if (!is_array($row_values)) {
                continue;
            }

            $sanitized_row = array();
            foreach ($sub_fields as $sub_field) {
                $sub_id = isset($sub_field['id']) ? $sub_field['id'] : '';
                $sub_value = isset($row_values[$sub_id]) ? $row_values[$sub_id] : '';
                $sanitized_row[$sub_id] = $registry->sanitize_field($sub_field, $sub_value);
            }

            // Only include non-empty rows
            if (!empty(array_filter($sanitized_row))) {
                $sanitized[] = $sanitized_row;
            }
        }

        return $sanitized;
    }

    public function get_display_value($field_config, $value) {
        if (!is_array($value) || empty($value)) {
            return '';
        }

        $sub_fields = isset($field_config['sub_fields']) ? $field_config['sub_fields'] : array();
        $registry = ERFQ_Field_Registry::get_instance();

        $output = '<table class="erfq-repeater-display"><thead><tr>';

        // Headers
        foreach ($sub_fields as $sub_field) {
            $output .= '<th>' . esc_html($sub_field['label'] ?? '') . '</th>';
        }
        $output .= '</tr></thead><tbody>';

        // Rows
        foreach ($value as $row_values) {
            $output .= '<tr>';
            foreach ($sub_fields as $sub_field) {
                $sub_id = isset($sub_field['id']) ? $sub_field['id'] : '';
                $sub_value = isset($row_values[$sub_id]) ? $row_values[$sub_id] : '';
                $display_value = $registry->get_display_value($sub_field, $sub_value);
                $output .= '<td>' . $display_value . '</td>';
            }
            $output .= '</tr>';
        }

        $output .= '</tbody></table>';

        return $output;
    }

    public function get_export_value($field_config, $value) {
        if (!is_array($value) || empty($value)) {
            return '';
        }

        $sub_fields = isset($field_config['sub_fields']) ? $field_config['sub_fields'] : array();
        $registry = ERFQ_Field_Registry::get_instance();

        $rows = array();
        foreach ($value as $row_values) {
            $row_parts = array();
            foreach ($sub_fields as $sub_field) {
                $sub_id = isset($sub_field['id']) ? $sub_field['id'] : '';
                $sub_value = isset($row_values[$sub_id]) ? $row_values[$sub_id] : '';
                $label = $sub_field['label'] ?? $sub_id;
                $display_value = $registry->get_display_value($sub_field, $sub_value);
                $row_parts[] = $label . ': ' . strip_tags($display_value);
            }
            $rows[] = implode(', ', $row_parts);
        }

        return implode(' | ', $rows);
    }
}
