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
 * Section/heading field for grouping form fields
 */
class ERFQ_Field_Section extends ERFQ_Field_Type_Abstract {

    /**
     * Get field type identifier
     *
     * @return string
     */
    public function get_type() {
        return 'section';
    }

    /**
     * Get field display name
     *
     * @return string
     */
    public function get_name() {
        return __('Section', 'event-rfq-manager');
    }

    /**
     * Get field icon
     *
     * @return string
     */
    public function get_icon() {
        return 'dashicons-heading';
    }

    /**
     * Get field category
     *
     * @return string
     */
    public function get_category() {
        return 'layout';
    }

    /**
     * Get field description
     *
     * @return string
     */
    public function get_description() {
        return __('Add a section heading to organize your form.', 'event-rfq-manager');
    }

    /**
     * Get field settings schema
     *
     * @return array
     */
    public function get_settings_schema() {
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
     * Render the field
     *
     * @param array $field_config Field configuration
     * @param mixed $value        Current value
     *
     * @return string HTML output
     */
    public function render($field_config, $value = null) {
        $id = isset($field_config['id']) ? esc_attr($field_config['id']) : '';
        $label = isset($field_config['label']) ? $field_config['label'] : '';
        $description = isset($field_config['description']) ? $field_config['description'] : '';
        $tag = isset($field_config['heading_tag']) ? $field_config['heading_tag'] : 'h3';
        $show_divider = isset($field_config['show_divider']) ? $field_config['show_divider'] : true;
        $css_class = isset($field_config['css_class']) ? esc_attr($field_config['css_class']) : '';

        $allowed_tags = array('h2', 'h3', 'h4', 'h5');
        if (!in_array($tag, $allowed_tags, true)) {
            $tag = 'h3';
        }

        $classes = 'erfq-section';
        if ($show_divider) {
            $classes .= ' erfq-section-divider';
        }
        if ($css_class) {
            $classes .= ' ' . $css_class;
        }

        $html = '<div class="' . esc_attr($classes) . '" id="' . $id . '">';

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
