<?php
/**
 * Field model class
 *
 * @package Event_RFQ_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ERFQ_Field
 *
 * Represents a single form field configuration
 */
class ERFQ_Field {

    /**
     * Field ID
     *
     * @var string
     */
    protected $id;

    /**
     * Field type
     *
     * @var string
     */
    protected $type;

    /**
     * Field label
     *
     * @var string
     */
    protected $label;

    /**
     * Field name attribute
     *
     * @var string
     */
    protected $name;

    /**
     * Placeholder text
     *
     * @var string
     */
    protected $placeholder;

    /**
     * Default value
     *
     * @var mixed
     */
    protected $default_value;

    /**
     * Whether field is required
     *
     * @var bool
     */
    protected $required = false;

    /**
     * Field description/help text
     *
     * @var string
     */
    protected $description;

    /**
     * CSS classes
     *
     * @var string
     */
    protected $css_class;

    /**
     * Field options (for select, checkbox, radio)
     *
     * @var array
     */
    protected $options = array();

    /**
     * Validation rules
     *
     * @var array
     */
    protected $validation = array();

    /**
     * Conditional logic rules
     *
     * @var array
     */
    protected $conditional_logic = array();

    /**
     * Field group ID (if part of a group)
     *
     * @var string|null
     */
    protected $group_id;

    /**
     * Additional attributes
     *
     * @var array
     */
    protected $attributes = array();

    /**
     * Constructor
     *
     * @param array $config Field configuration
     */
    public function __construct($config = array()) {
        if (!empty($config)) {
            $this->load_from_array($config);
        }
    }

    /**
     * Load field from configuration array
     *
     * @param array $config Field configuration
     */
    public function load_from_array($config) {
        $this->id               = isset($config['id']) ? sanitize_key($config['id']) : '';
        $this->type             = isset($config['type']) ? sanitize_key($config['type']) : 'text';
        $this->label            = isset($config['label']) ? sanitize_text_field($config['label']) : '';
        $this->name             = isset($config['name']) ? sanitize_key($config['name']) : $this->id;
        $this->placeholder      = isset($config['placeholder']) ? sanitize_text_field($config['placeholder']) : '';
        $this->default_value    = isset($config['default_value']) ? $config['default_value'] : '';
        $this->required         = isset($config['required']) ? (bool) $config['required'] : false;
        $this->description      = isset($config['description']) ? sanitize_text_field($config['description']) : '';
        $this->css_class        = isset($config['css_class']) ? sanitize_text_field($config['css_class']) : '';
        $this->options          = isset($config['options']) && is_array($config['options']) ? $config['options'] : array();
        $this->validation       = isset($config['validation']) && is_array($config['validation']) ? $config['validation'] : array();
        $this->conditional_logic = isset($config['conditional_logic']) && is_array($config['conditional_logic']) ? $config['conditional_logic'] : array();
        $this->group_id         = isset($config['group_id']) ? sanitize_key($config['group_id']) : null;
        $this->attributes       = isset($config['attributes']) && is_array($config['attributes']) ? $config['attributes'] : array();
    }

    /**
     * Get field ID
     *
     * @return string
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * Set field ID
     *
     * @param string $id Field ID
     */
    public function set_id($id) {
        $this->id = sanitize_key($id);
    }

    /**
     * Get field type
     *
     * @return string
     */
    public function get_type() {
        return $this->type;
    }

    /**
     * Set field type
     *
     * @param string $type Field type
     */
    public function set_type($type) {
        $this->type = sanitize_key($type);
    }

    /**
     * Get field label
     *
     * @return string
     */
    public function get_label() {
        return $this->label;
    }

    /**
     * Set field label
     *
     * @param string $label Field label
     */
    public function set_label($label) {
        $this->label = sanitize_text_field($label);
    }

    /**
     * Get field name
     *
     * @return string
     */
    public function get_name() {
        return $this->name ?: $this->id;
    }

    /**
     * Set field name
     *
     * @param string $name Field name
     */
    public function set_name($name) {
        $this->name = sanitize_key($name);
    }

    /**
     * Get placeholder
     *
     * @return string
     */
    public function get_placeholder() {
        return $this->placeholder;
    }

    /**
     * Set placeholder
     *
     * @param string $placeholder Placeholder text
     */
    public function set_placeholder($placeholder) {
        $this->placeholder = sanitize_text_field($placeholder);
    }

    /**
     * Get default value
     *
     * @return mixed
     */
    public function get_default_value() {
        return $this->default_value;
    }

    /**
     * Set default value
     *
     * @param mixed $value Default value
     */
    public function set_default_value($value) {
        $this->default_value = $value;
    }

    /**
     * Check if field is required
     *
     * @return bool
     */
    public function is_required() {
        return $this->required;
    }

    /**
     * Set required status
     *
     * @param bool $required Whether field is required
     */
    public function set_required($required) {
        $this->required = (bool) $required;
    }

    /**
     * Get field description
     *
     * @return string
     */
    public function get_description() {
        return $this->description;
    }

    /**
     * Set field description
     *
     * @param string $description Field description
     */
    public function set_description($description) {
        $this->description = sanitize_text_field($description);
    }

    /**
     * Get CSS class
     *
     * @return string
     */
    public function get_css_class() {
        return $this->css_class;
    }

    /**
     * Set CSS class
     *
     * @param string $css_class CSS class(es)
     */
    public function set_css_class($css_class) {
        $this->css_class = sanitize_text_field($css_class);
    }

    /**
     * Get field options
     *
     * @return array
     */
    public function get_options() {
        return $this->options;
    }

    /**
     * Set field options
     *
     * @param array $options Field options
     */
    public function set_options($options) {
        $this->options = is_array($options) ? $options : array();
    }

    /**
     * Add an option
     *
     * @param string $value Option value
     * @param string $label Option label
     */
    public function add_option($value, $label) {
        $this->options[$value] = $label;
    }

    /**
     * Get validation rules
     *
     * @return array
     */
    public function get_validation() {
        return $this->validation;
    }

    /**
     * Set validation rules
     *
     * @param array $rules Validation rules
     */
    public function set_validation($rules) {
        $this->validation = is_array($rules) ? $rules : array();
    }

    /**
     * Add a validation rule
     *
     * @param string $rule    Rule type
     * @param mixed  $value   Rule value/parameter
     * @param string $message Error message
     */
    public function add_validation_rule($rule, $value = true, $message = '') {
        $this->validation[$rule] = array(
            'value'   => $value,
            'message' => $message,
        );
    }

    /**
     * Get conditional logic rules
     *
     * @return array
     */
    public function get_conditional_logic() {
        return $this->conditional_logic;
    }

    /**
     * Set conditional logic rules
     *
     * @param array $rules Conditional logic rules
     */
    public function set_conditional_logic($rules) {
        $this->conditional_logic = is_array($rules) ? $rules : array();
    }

    /**
     * Check if field has conditional logic
     *
     * @return bool
     */
    public function has_conditional_logic() {
        return !empty($this->conditional_logic);
    }

    /**
     * Get group ID
     *
     * @return string|null
     */
    public function get_group_id() {
        return $this->group_id;
    }

    /**
     * Set group ID
     *
     * @param string $group_id Group ID
     */
    public function set_group_id($group_id) {
        $this->group_id = sanitize_key($group_id);
    }

    /**
     * Get additional attributes
     *
     * @return array
     */
    public function get_attributes() {
        return $this->attributes;
    }

    /**
     * Set additional attributes
     *
     * @param array $attributes Attributes
     */
    public function set_attributes($attributes) {
        $this->attributes = is_array($attributes) ? $attributes : array();
    }

    /**
     * Get a specific attribute
     *
     * @param string $key     Attribute key
     * @param mixed  $default Default value
     *
     * @return mixed
     */
    public function get_attribute($key, $default = null) {
        return isset($this->attributes[$key]) ? $this->attributes[$key] : $default;
    }

    /**
     * Set a specific attribute
     *
     * @param string $key   Attribute key
     * @param mixed  $value Attribute value
     */
    public function set_attribute($key, $value) {
        $this->attributes[$key] = $value;
    }

    /**
     * Convert field to array
     *
     * @return array
     */
    public function to_array() {
        return array(
            'id'                => $this->id,
            'type'              => $this->type,
            'label'             => $this->label,
            'name'              => $this->name,
            'placeholder'       => $this->placeholder,
            'default_value'     => $this->default_value,
            'required'          => $this->required,
            'description'       => $this->description,
            'css_class'         => $this->css_class,
            'options'           => $this->options,
            'validation'        => $this->validation,
            'conditional_logic' => $this->conditional_logic,
            'group_id'          => $this->group_id,
            'attributes'        => $this->attributes,
        );
    }

    /**
     * Build HTML attributes string
     *
     * @param array $extra Extra attributes to include
     *
     * @return string
     */
    public function build_attributes_string($extra = array()) {
        $attrs = array_merge(array(
            'id'          => 'erfq-field-' . $this->id,
            'name'        => 'erfq_fields[' . $this->get_name() . ']',
            'class'       => $this->get_field_classes(),
            'placeholder' => $this->placeholder,
        ), $this->attributes, $extra);

        if ($this->required) {
            $attrs['required'] = 'required';
            $attrs['aria-required'] = 'true';
        }

        $output = '';
        foreach ($attrs as $key => $value) {
            if ($value === '' || $value === null) {
                continue;
            }
            if (is_bool($value)) {
                if ($value) {
                    $output .= ' ' . esc_attr($key);
                }
            } else {
                $output .= ' ' . esc_attr($key) . '="' . esc_attr($value) . '"';
            }
        }

        return $output;
    }

    /**
     * Get field CSS classes
     *
     * @return string
     */
    public function get_field_classes() {
        $classes = array(
            'erfq-field',
            'erfq-field-' . $this->type,
        );

        if ($this->required) {
            $classes[] = 'erfq-required';
        }

        if ($this->css_class) {
            $classes[] = $this->css_class;
        }

        return implode(' ', $classes);
    }

    /**
     * Create field from array
     *
     * @param array $config Field configuration
     *
     * @return ERFQ_Field
     */
    public static function create($config) {
        return new self($config);
    }
}
