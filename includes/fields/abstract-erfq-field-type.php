<?php
/**
 * Abstract Field Type class
 *
 * @package Event_RFQ_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ERFQ_Field_Type_Abstract
 *
 * Base class for all field types
 */
abstract class ERFQ_Field_Type_Abstract {

    /**
     * Field type identifier
     *
     * @var string
     */
    protected $type = '';

    /**
     * Display name
     *
     * @var string
     */
    protected $name = '';

    /**
     * Icon class (dashicon)
     *
     * @var string
     */
    protected $icon = 'dashicons-edit';

    /**
     * Category (basic, advanced, layout)
     *
     * @var string
     */
    protected $category = 'basic';

    /**
     * Description
     *
     * @var string
     */
    protected $description = '';

    /**
     * Get field type identifier
     *
     * @return string
     */
    public function get_type() {
        return $this->type;
    }

    /**
     * Get display name
     *
     * @return string
     */
    public function get_name() {
        return $this->name;
    }

    /**
     * Get icon class
     *
     * @return string
     */
    public function get_icon() {
        return $this->icon;
    }

    /**
     * Get category
     *
     * @return string
     */
    public function get_category() {
        return $this->category;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function get_description() {
        return $this->description;
    }

    /**
     * Get the settings schema for this field type
     *
     * @return array Settings configuration
     */
    public function get_settings_schema() {
        return array_merge($this->get_common_settings(), $this->get_type_settings());
    }

    /**
     * Get common settings shared by all field types
     *
     * @return array
     */
    protected function get_common_settings() {
        return array(
            'label' => array(
                'type'    => 'text',
                'label'   => __('Label', 'event-rfq-manager'),
                'default' => '',
            ),
            'name' => array(
                'type'    => 'text',
                'label'   => __('Field Name', 'event-rfq-manager'),
                'default' => '',
                'description' => __('Used as the field identifier', 'event-rfq-manager'),
            ),
            'placeholder' => array(
                'type'    => 'text',
                'label'   => __('Placeholder', 'event-rfq-manager'),
                'default' => '',
            ),
            'description' => array(
                'type'    => 'textarea',
                'label'   => __('Description', 'event-rfq-manager'),
                'default' => '',
            ),
            'default_value' => array(
                'type'    => 'text',
                'label'   => __('Default Value', 'event-rfq-manager'),
                'default' => '',
            ),
            'required' => array(
                'type'    => 'checkbox',
                'label'   => __('Required', 'event-rfq-manager'),
                'default' => false,
            ),
            'css_class' => array(
                'type'    => 'text',
                'label'   => __('CSS Class', 'event-rfq-manager'),
                'default' => '',
            ),
        );
    }

    /**
     * Get type-specific settings
     *
     * @return array
     */
    protected function get_type_settings() {
        return array();
    }

    /**
     * Render the field
     *
     * @param array $field_config Field configuration
     * @param mixed $value        Current value
     *
     * @return string HTML output
     */
    abstract public function render($field_config, $value = null);

    /**
     * Render field in admin settings panel
     *
     * @param array $field_config Field configuration
     *
     * @return string HTML output
     */
    public function render_settings_panel($field_config) {
        $settings = $this->get_settings_schema();
        $output = '';

        foreach ($settings as $key => $setting) {
            $value = isset($field_config[$key]) ? $field_config[$key] : $setting['default'];
            $output .= $this->render_setting_field($key, $setting, $value);
        }

        return $output;
    }

    /**
     * Render a single setting field
     *
     * @param string $key     Setting key
     * @param array  $setting Setting configuration
     * @param mixed  $value   Current value
     *
     * @return string HTML output
     */
    protected function render_setting_field($key, $setting, $value) {
        $id = 'erfq-field-setting-' . $key;
        $name = 'field_settings[' . $key . ']';

        $output = '<div class="erfq-setting-row">';
        $output .= '<label for="' . esc_attr($id) . '">' . esc_html($setting['label']) . '</label>';

        switch ($setting['type']) {
            case 'text':
                $output .= '<input type="text" id="' . esc_attr($id) . '" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '" class="erfq-setting-input">';
                break;

            case 'textarea':
                $output .= '<textarea id="' . esc_attr($id) . '" name="' . esc_attr($name) . '" class="erfq-setting-textarea">' . esc_textarea($value) . '</textarea>';
                break;

            case 'checkbox':
                $checked = $value ? 'checked' : '';
                $output .= '<input type="checkbox" id="' . esc_attr($id) . '" name="' . esc_attr($name) . '" value="1" ' . $checked . ' class="erfq-setting-checkbox">';
                break;

            case 'select':
                $output .= '<select id="' . esc_attr($id) . '" name="' . esc_attr($name) . '" class="erfq-setting-select">';
                foreach ($setting['options'] as $opt_value => $opt_label) {
                    $selected = selected($value, $opt_value, false);
                    $output .= '<option value="' . esc_attr($opt_value) . '" ' . $selected . '>' . esc_html($opt_label) . '</option>';
                }
                $output .= '</select>';
                break;

            case 'number':
                $min = isset($setting['min']) ? 'min="' . esc_attr($setting['min']) . '"' : '';
                $max = isset($setting['max']) ? 'max="' . esc_attr($setting['max']) . '"' : '';
                $step = isset($setting['step']) ? 'step="' . esc_attr($setting['step']) . '"' : '';
                $output .= '<input type="number" id="' . esc_attr($id) . '" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '" ' . $min . ' ' . $max . ' ' . $step . ' class="erfq-setting-number">';
                break;

            case 'options':
                $output .= $this->render_options_editor($key, $value);
                break;
        }

        if (isset($setting['description'])) {
            $output .= '<p class="description">' . esc_html($setting['description']) . '</p>';
        }

        $output .= '</div>';

        return $output;
    }

    /**
     * Render options editor for select/checkbox/radio fields
     *
     * @param string $key   Setting key
     * @param array  $value Current options
     *
     * @return string HTML output
     */
    protected function render_options_editor($key, $value) {
        $options = is_array($value) ? $value : array();

        $output = '<div class="erfq-options-editor" data-key="' . esc_attr($key) . '">';
        $output .= '<div class="erfq-options-list">';

        foreach ($options as $opt_value => $opt_label) {
            $output .= '<div class="erfq-option-row">';
            $output .= '<input type="text" class="erfq-option-value" value="' . esc_attr($opt_value) . '" placeholder="' . esc_attr__('Value', 'event-rfq-manager') . '">';
            $output .= '<input type="text" class="erfq-option-label" value="' . esc_attr($opt_label) . '" placeholder="' . esc_attr__('Label', 'event-rfq-manager') . '">';
            $output .= '<button type="button" class="erfq-remove-option button">&times;</button>';
            $output .= '</div>';
        }

        $output .= '</div>';
        $output .= '<button type="button" class="erfq-add-option button">' . esc_html__('Add Option', 'event-rfq-manager') . '</button>';
        $output .= '</div>';

        return $output;
    }

    /**
     * Validate field value
     *
     * @param array $field_config Field configuration
     * @param mixed $value        Value to validate
     *
     * @return true|WP_Error True if valid, WP_Error if invalid
     */
    public function validate($field_config, $value) {
        // Check required
        if (!empty($field_config['required'])) {
            if ($value === '' || $value === null || (is_array($value) && empty($value))) {
                return new WP_Error(
                    'required',
                    sprintf(
                        __('%s is required.', 'event-rfq-manager'),
                        $field_config['label'] ?? __('This field', 'event-rfq-manager')
                    )
                );
            }
        }

        // Run type-specific validation
        return $this->validate_type($field_config, $value);
    }

    /**
     * Type-specific validation
     *
     * @param array $field_config Field configuration
     * @param mixed $value        Value to validate
     *
     * @return true|WP_Error
     */
    protected function validate_type($field_config, $value) {
        return true;
    }

    /**
     * Sanitize field value
     *
     * @param array $field_config Field configuration
     * @param mixed $value        Value to sanitize
     *
     * @return mixed Sanitized value
     */
    public function sanitize($field_config, $value) {
        if (is_array($value)) {
            return array_map('sanitize_text_field', $value);
        }
        return sanitize_text_field($value);
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
        if (is_array($value)) {
            return implode(', ', $value);
        }
        return strval($value);
    }

    /**
     * Get export value for CSV/PDF
     *
     * @param array $field_config Field configuration
     * @param mixed $value        Field value
     *
     * @return string
     */
    public function get_export_value($field_config, $value) {
        return $this->get_display_value($field_config, $value);
    }

    /**
     * Build wrapper HTML
     *
     * @param array  $field_config Field configuration
     * @param string $inner_html   Inner HTML content
     *
     * @return string
     */
    protected function wrap_field($field_config, $inner_html) {
        $field_id = isset($field_config['id']) ? $field_config['id'] : '';
        $label = isset($field_config['label']) ? $field_config['label'] : '';
        $required = !empty($field_config['required']);
        $description = isset($field_config['description']) ? $field_config['description'] : '';
        $css_class = isset($field_config['css_class']) ? $field_config['css_class'] : '';

        $wrapper_classes = array(
            'erfq-field-wrapper',
            'erfq-field-type-' . $this->type,
        );

        if ($required) {
            $wrapper_classes[] = 'erfq-field-required';
        }

        if ($css_class) {
            $wrapper_classes[] = $css_class;
        }

        // Conditional logic data attributes
        $data_attrs = '';
        if (!empty($field_config['conditional_logic'])) {
            $data_attrs = ' data-conditional="' . esc_attr(wp_json_encode($field_config['conditional_logic'])) . '"';
        }

        $output = '<div class="' . esc_attr(implode(' ', $wrapper_classes)) . '"' . $data_attrs . '>';

        if ($label) {
            $output .= '<label for="erfq-field-' . esc_attr($field_id) . '" class="erfq-field-label">';
            $output .= esc_html($label);
            if ($required) {
                $output .= ' <span class="erfq-required-marker">*</span>';
            }
            $output .= '</label>';
        }

        $output .= '<div class="erfq-field-input">';
        $output .= $inner_html;
        $output .= '</div>';

        if ($description) {
            $output .= '<p class="erfq-field-description">' . esc_html($description) . '</p>';
        }

        $output .= '<div class="erfq-field-error" style="display:none;"></div>';
        $output .= '</div>';

        return $output;
    }

    /**
     * Build common HTML attributes
     *
     * @param array $field_config Field configuration
     * @param array $extra        Extra attributes
     *
     * @return string
     */
    protected function build_attributes($field_config, $extra = array()) {
        $field_id = isset($field_config['id']) ? $field_config['id'] : '';
        $name = isset($field_config['name']) ? $field_config['name'] : $field_id;

        $attrs = array_merge(array(
            'id'    => 'erfq-field-' . $field_id,
            'name'  => 'erfq_fields[' . $name . ']',
            'class' => 'erfq-field erfq-field-' . $this->type,
        ), $extra);

        if (!empty($field_config['placeholder'])) {
            $attrs['placeholder'] = $field_config['placeholder'];
        }

        if (!empty($field_config['required'])) {
            $attrs['required'] = 'required';
            $attrs['aria-required'] = 'true';
        }

        $output = '';
        foreach ($attrs as $key => $value) {
            if ($value === '' || $value === null) {
                continue;
            }
            $output .= ' ' . esc_attr($key) . '="' . esc_attr($value) . '"';
        }

        return $output;
    }
}
