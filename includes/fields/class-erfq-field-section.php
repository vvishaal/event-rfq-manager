<?php
/**
 * Section Field Type
 *
 * @package Event_RFQ_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ERFQ_Field_Section
 *
 * Section/container field that groups other fields and supports repeater functionality.
 */
class ERFQ_Field_Section extends ERFQ_Field_Type_Abstract {

    protected $type = 'section';
    protected $name = 'Section';
    protected $icon = 'dashicons-category';
    protected $category = 'layout';
    protected $description = 'Group fields together with optional repeat functionality.';

    /**
     * Get type-specific settings
     *
     * @return array
     */
    protected function get_type_settings() {
        return array(
            'repeatable' => array(
                'type'    => 'checkbox',
                'label'   => __('Make this section repeatable', 'event-rfq-manager'),
                'default' => true,
            ),
            'min_instances' => array(
                'type'    => 'number',
                'label'   => __('Minimum Instances', 'event-rfq-manager'),
                'default' => 1,
                'min'     => 1,
                'max'     => 20,
            ),
            'max_instances' => array(
                'type'    => 'number',
                'label'   => __('Maximum Instances', 'event-rfq-manager'),
                'default' => 10,
                'min'     => 1,
                'max'     => 50,
            ),
            'add_button_text' => array(
                'type'    => 'text',
                'label'   => __('Add Button Text', 'event-rfq-manager'),
                'default' => '+ Add Another',
            ),
        );
    }

    /**
     * Override common settings - Section needs specific ones
     *
     * @return array
     */
    protected function get_common_settings() {
        return array(
            'label' => array(
                'type'    => 'text',
                'label'   => __('Section Title', 'event-rfq-manager'),
                'default' => '',
            ),
            'description' => array(
                'type'    => 'textarea',
                'label'   => __('Description', 'event-rfq-manager'),
                'default' => '',
            ),
            'css_class' => array(
                'type'    => 'text',
                'label'   => __('CSS Class', 'event-rfq-manager'),
                'default' => '',
            ),
        );
    }

    /**
     * Render the field
     *
     * @param array $field_config Field configuration
     * @param mixed $value        Current value (array of instances)
     *
     * @return string HTML output
     */
    public function render($field_config, $value = null) {
        $id = isset($field_config['id']) ? esc_attr($field_config['id']) : 'section-' . uniqid();
        $label = isset($field_config['label']) ? trim($field_config['label']) : '';
        $description = isset($field_config['description']) ? trim($field_config['description']) : '';
        $css_class = isset($field_config['css_class']) ? trim($field_config['css_class']) : '';
        $sub_fields = isset($field_config['sub_fields']) ? $field_config['sub_fields'] : array();

        // Repeater settings
        $repeatable = true;
        if (isset($field_config['repeatable'])) {
            $repeatable = filter_var($field_config['repeatable'], FILTER_VALIDATE_BOOLEAN);
        }
        $min_instances = isset($field_config['min_instances']) ? absint($field_config['min_instances']) : 1;
        $max_instances = isset($field_config['max_instances']) ? absint($field_config['max_instances']) : 10;
        $add_button_text = isset($field_config['add_button_text']) ? $field_config['add_button_text'] : __('+ Add Another', 'event-rfq-manager');

        // Ensure at least min_instances
        if ($min_instances < 1) $min_instances = 1;
        if ($max_instances < $min_instances) $max_instances = $min_instances;

        // Build classes
        $classes = array('erfq-section-container', 'erfq-field-wrapper', 'erfq-field-type-section');
        if ($repeatable) {
            $classes[] = 'erfq-section-repeatable';
        }
        if ($css_class) {
            $classes[] = esc_attr($css_class);
        }

        // Prepare value (array of instances)
        if (empty($value) || !is_array($value)) {
            // Create initial instances based on min_instances
            $value = array();
            for ($i = 0; $i < $min_instances; $i++) {
                $value[] = array();
            }
        }

        $html = '<div class="' . esc_attr(implode(' ', $classes)) . '" ';
        $html .= 'id="' . esc_attr($id) . '" ';
        $html .= 'data-section-id="' . esc_attr($id) . '" ';
        $html .= 'data-repeatable="' . ($repeatable ? 'true' : 'false') . '" ';
        $html .= 'data-min-instances="' . esc_attr($min_instances) . '" ';
        $html .= 'data-max-instances="' . esc_attr($max_instances) . '">';

        // Section header (shown once)
        if ($label || $description) {
            $html .= '<div class="erfq-section-header">';
            if ($label) {
                $html .= '<h3 class="erfq-section-title">' . esc_html($label) . '</h3>';
            }
            if ($description) {
                $html .= '<p class="erfq-section-description">' . esc_html($description) . '</p>';
            }
            $html .= '</div>';
        }

        // Instances container
        $html .= '<div class="erfq-section-instances">';

        // Render each instance
        foreach ($value as $index => $instance_data) {
            $html .= $this->render_instance($id, $index, $sub_fields, $instance_data, $repeatable, $min_instances, count($value));
        }

        $html .= '</div>'; // .erfq-section-instances

        // Add button (only if repeatable)
        if ($repeatable) {
            $html .= '<div class="erfq-section-add-wrapper">';
            $html .= '<button type="button" class="erfq-section-add-btn" data-section-id="' . esc_attr($id) . '">';
            $html .= esc_html($add_button_text);
            $html .= '</button>';
            $html .= '</div>';
        }

        // Template for new instances (hidden, used by JS)
        if ($repeatable) {
            $html .= '<script type="text/template" class="erfq-section-template" data-section-id="' . esc_attr($id) . '">';
            $html .= $this->render_instance($id, '{{INDEX}}', $sub_fields, array(), true, $min_instances, 0, true);
            $html .= '</script>';
        }

        $html .= '</div>'; // .erfq-section-container

        return $html;
    }

    /**
     * Render a single instance of the section
     *
     * @param string $section_id    Section ID
     * @param int    $index         Instance index
     * @param array  $sub_fields    Sub-field configurations
     * @param array  $instance_data Instance data values
     * @param bool   $repeatable    Whether section is repeatable
     * @param int    $min_instances Minimum instances
     * @param int    $total_count   Total current instances
     * @param bool   $is_template   Whether this is a template
     *
     * @return string HTML output
     */
    protected function render_instance($section_id, $index, $sub_fields, $instance_data, $repeatable, $min_instances, $total_count, $is_template = false) {
        $instance_classes = array('erfq-section-instance');
        if ($is_template) {
            $instance_classes[] = 'erfq-instance-template';
        }

        $html = '<div class="' . implode(' ', $instance_classes) . '" data-instance-index="' . esc_attr($index) . '">';

        // Instance header with remove button
        if ($repeatable) {
            $html .= '<div class="erfq-instance-header">';
            $html .= '<span class="erfq-instance-number">#<span class="erfq-instance-num">' . (is_numeric($index) ? ($index + 1) : '{{NUM}}') . '</span></span>';
            $html .= '<button type="button" class="erfq-instance-remove" title="' . esc_attr__('Remove', 'event-rfq-manager') . '"';
            // Disable remove if at minimum
            if (!$is_template && $total_count <= $min_instances) {
                $html .= ' disabled';
            }
            $html .= '>&times;</button>';
            $html .= '</div>';
        }

        // Instance fields
        $html .= '<div class="erfq-instance-fields">';

        if (!empty($sub_fields)) {
            $registry = ERFQ_Field_Registry::get_instance();

            foreach ($sub_fields as $sub_field) {
                $field_id = isset($sub_field['id']) ? $sub_field['id'] : '';
                $field_value = isset($instance_data[$field_id]) ? $instance_data[$field_id] : null;

                // Update field name to include section and instance index
                $sub_field_config = $sub_field;
                $sub_field_config['name'] = $section_id . '[' . $index . '][' . $field_id . ']';
                $sub_field_config['id'] = $section_id . '_' . $index . '_' . $field_id;

                $html .= $registry->render_field($sub_field_config, $field_value);
            }
        } else {
            $html .= '<p class="erfq-no-fields-msg">' . esc_html__('No fields in this section.', 'event-rfq-manager') . '</p>';
        }

        $html .= '</div>'; // .erfq-instance-fields
        $html .= '</div>'; // .erfq-section-instance

        return $html;
    }

    /**
     * Validate field value
     *
     * @param array $field_config Field configuration
     * @param mixed $value        Value to validate (array of instances)
     *
     * @return true|WP_Error
     */
    public function validate($field_config, $value) {
        $sub_fields = isset($field_config['sub_fields']) ? $field_config['sub_fields'] : array();
        $min_instances = isset($field_config['min_instances']) ? absint($field_config['min_instances']) : 1;

        if (empty($value) || !is_array($value)) {
            $value = array();
        }

        // Check minimum instances
        if (count($value) < $min_instances) {
            return new WP_Error(
                'min_instances',
                sprintf(
                    __('%s requires at least %d entries.', 'event-rfq-manager'),
                    $field_config['label'] ?? __('This section', 'event-rfq-manager'),
                    $min_instances
                )
            );
        }

        // Validate each instance's sub-fields
        $registry = ERFQ_Field_Registry::get_instance();

        foreach ($value as $index => $instance_data) {
            foreach ($sub_fields as $sub_field) {
                $field_id = isset($sub_field['id']) ? $sub_field['id'] : '';
                $field_value = isset($instance_data[$field_id]) ? $instance_data[$field_id] : '';

                $result = $registry->validate_field($sub_field, $field_value);
                if (is_wp_error($result)) {
                    // Add context about which instance
                    return new WP_Error(
                        $result->get_error_code(),
                        sprintf(
                            __('%s (Entry #%d)', 'event-rfq-manager'),
                            $result->get_error_message(),
                            $index + 1
                        )
                    );
                }
            }
        }

        return true;
    }

    /**
     * Sanitize field value
     *
     * @param array $field_config Field configuration
     * @param mixed $value        Value to sanitize
     *
     * @return array Sanitized array of instances
     */
    public function sanitize($field_config, $value) {
        $sub_fields = isset($field_config['sub_fields']) ? $field_config['sub_fields'] : array();
        $max_instances = isset($field_config['max_instances']) ? absint($field_config['max_instances']) : 10;

        if (empty($value) || !is_array($value)) {
            return array();
        }

        // Limit to max instances
        $value = array_slice($value, 0, $max_instances);

        $registry = ERFQ_Field_Registry::get_instance();
        $sanitized = array();

        foreach ($value as $index => $instance_data) {
            $sanitized_instance = array();

            foreach ($sub_fields as $sub_field) {
                $field_id = isset($sub_field['id']) ? $sub_field['id'] : '';
                $field_value = isset($instance_data[$field_id]) ? $instance_data[$field_id] : '';

                $sanitized_instance[$field_id] = $registry->sanitize_field($sub_field, $field_value);
            }

            $sanitized[] = $sanitized_instance;
        }

        return $sanitized;
    }

    /**
     * Get display value for admin/entries view
     *
     * @param array $field_config Field configuration
     * @param mixed $value        Field value
     *
     * @return string
     */
    public function get_display_value($field_config, $value) {
        if (empty($value) || !is_array($value)) {
            return '-';
        }

        $count = count($value);
        return sprintf(
            _n('%d entry', '%d entries', $count, 'event-rfq-manager'),
            $count
        );
    }
}
