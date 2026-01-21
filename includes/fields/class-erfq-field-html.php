<?php
/**
 * HTML Field Type
 *
 * @package Event_RFQ_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ERFQ_Field_HTML
 *
 * Custom HTML content field
 */
class ERFQ_Field_HTML extends ERFQ_Field_Type_Abstract {

    /**
     * Get field type identifier
     *
     * @return string
     */
    public function get_type() {
        return 'html';
    }

    /**
     * Get field display name
     *
     * @return string
     */
    public function get_name() {
        return __('HTML Content', 'event-rfq-manager');
    }

    /**
     * Get field icon
     *
     * @return string
     */
    public function get_icon() {
        return 'dashicons-editor-code';
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
        return __('Add custom HTML content to your form.', 'event-rfq-manager');
    }

    /**
     * Get field settings schema
     *
     * @return array
     */
    public function get_settings_schema() {
        return array(
            'html_content' => array(
                'type'    => 'textarea',
                'label'   => __('HTML Content', 'event-rfq-manager'),
                'default' => '',
                'rows'    => 6,
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
        $html_content = isset($field_config['html_content']) ? $field_config['html_content'] : '';
        $css_class = isset($field_config['css_class']) ? esc_attr($field_config['css_class']) : '';

        $classes = 'erfq-html-content';
        if ($css_class) {
            $classes .= ' ' . $css_class;
        }

        // Allow safe HTML tags
        $allowed_tags = wp_kses_allowed_html('post');

        $html = '<div class="' . esc_attr($classes) . '" id="' . $id . '">';
        $html .= wp_kses($html_content, $allowed_tags);
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
        // HTML fields don't have values to validate
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
