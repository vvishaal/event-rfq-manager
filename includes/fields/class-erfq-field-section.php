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
 * Section/heading field for grouping form fields visually.
 * This is a layout element that does not collect data.
 */
class ERFQ_Field_Section extends ERFQ_Field_Type_Abstract {

    protected $type = 'section';
    protected $name = 'Section';
    protected $icon = 'dashicons-heading';
    protected $category = 'layout';
    protected $description = 'Add a section heading to organize your form.';

    /**
     * Get type-specific settings
     *
     * @return array
     */
    protected function get_type_settings() {
        return array(
            'heading_tag' => array(
                'type'    => 'select',
                'label'   => __('Heading Tag', 'event-rfq-manager'),
                'default' => 'h3',
                'options' => array(
                    'h2' => 'H2',
                    'h3' => 'H3',
                    'h4' => 'H4',
                    'h5' => 'H5',
                ),
            ),
            'show_divider' => array(
                'type'    => 'checkbox',
                'label'   => __('Show divider line', 'event-rfq-manager'),
                'default' => true,
            ),
        );
    }

    /**
     * Override common settings - Section doesn't need most of them
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
     * @param mixed $value        Current value
     *
     * @return string HTML output
     */
    public function render($field_config, $value = null) {
        $id = isset($field_config['id']) ? esc_attr($field_config['id']) : 'section-' . uniqid();
        $label = isset($field_config['label']) ? trim($field_config['label']) : '';
        $description = isset($field_config['description']) ? trim($field_config['description']) : '';
        $tag = isset($field_config['heading_tag']) && in_array($field_config['heading_tag'], array('h2', 'h3', 'h4', 'h5'), true)
            ? $field_config['heading_tag']
            : 'h3';

        // Handle show_divider - could be boolean, string "true"/"false", or "1"/"0"
        $show_divider = true;
        if (isset($field_config['show_divider'])) {
            if (is_bool($field_config['show_divider'])) {
                $show_divider = $field_config['show_divider'];
            } else {
                $show_divider = filter_var($field_config['show_divider'], FILTER_VALIDATE_BOOLEAN);
            }
        }

        $css_class = isset($field_config['css_class']) ? trim($field_config['css_class']) : '';

        // Build classes
        $classes = array('erfq-section', 'erfq-field-wrapper', 'erfq-field-type-section');
        if ($show_divider) {
            $classes[] = 'erfq-section-divider';
        }
        if ($css_class) {
            $classes[] = esc_attr($css_class);
        }

        $html = '<div class="' . esc_attr(implode(' ', $classes)) . '" id="' . esc_attr($id) . '">';

        if ($label) {
            $html .= '<' . $tag . ' class="erfq-section-title">' . esc_html($label) . '</' . $tag . '>';
        }

        if ($description) {
            $html .= '<p class="erfq-section-description">' . esc_html($description) . '</p>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Validate field value
     *
     * @param array $field_config Field configuration
     * @param mixed $value        Value to validate
     *
     * @return true|WP_Error
     */
    public function validate($field_config, $value) {
        // Section fields don't have values to validate
        return true;
    }

    /**
     * Sanitize field value
     *
     * @param array $field_config Field configuration
     * @param mixed $value        Value to sanitize
     *
     * @return mixed
     */
    public function sanitize($field_config, $value) {
        return '';
    }

    /**
     * Get display value
     *
     * @param array $field_config Field configuration
     * @param mixed $value        Field value
     *
     * @return string
     */
    public function get_display_value($field_config, $value) {
        return '';
    }
}
