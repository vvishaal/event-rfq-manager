<?php
/**
 * Field Group model class
 *
 * @package Event_RFQ_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ERFQ_Field_Group
 *
 * Represents a group of related fields
 */
class ERFQ_Field_Group {

    /**
     * Group ID
     *
     * @var string
     */
    protected $id;

    /**
     * Group label
     *
     * @var string
     */
    protected $label;

    /**
     * Group description
     *
     * @var string
     */
    protected $description;

    /**
     * Fields in this group
     *
     * @var array
     */
    protected $fields = array();

    /**
     * CSS class
     *
     * @var string
     */
    protected $css_class;

    /**
     * Layout type (horizontal, vertical, grid)
     *
     * @var string
     */
    protected $layout = 'vertical';

    /**
     * Number of columns for grid layout
     *
     * @var int
     */
    protected $columns = 2;

    /**
     * Conditional logic
     *
     * @var array
     */
    protected $conditional_logic = array();

    /**
     * Constructor
     *
     * @param array $config Group configuration
     */
    public function __construct($config = array()) {
        if (!empty($config)) {
            $this->load_from_array($config);
        }
    }

    /**
     * Load group from configuration array
     *
     * @param array $config Group configuration
     */
    public function load_from_array($config) {
        $this->id               = isset($config['id']) ? sanitize_key($config['id']) : 'group_' . wp_generate_uuid4();
        $this->label            = isset($config['label']) ? sanitize_text_field($config['label']) : '';
        $this->description      = isset($config['description']) ? sanitize_text_field($config['description']) : '';
        $this->css_class        = isset($config['css_class']) ? sanitize_text_field($config['css_class']) : '';
        $this->layout           = isset($config['layout']) ? sanitize_key($config['layout']) : 'vertical';
        $this->columns          = isset($config['columns']) ? absint($config['columns']) : 2;
        $this->conditional_logic = isset($config['conditional_logic']) ? $config['conditional_logic'] : array();

        if (isset($config['fields']) && is_array($config['fields'])) {
            foreach ($config['fields'] as $field_config) {
                $this->fields[] = new ERFQ_Field($field_config);
            }
        }
    }

    /**
     * Get group ID
     *
     * @return string
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * Set group ID
     *
     * @param string $id Group ID
     */
    public function set_id($id) {
        $this->id = sanitize_key($id);
    }

    /**
     * Get label
     *
     * @return string
     */
    public function get_label() {
        return $this->label;
    }

    /**
     * Set label
     *
     * @param string $label Group label
     */
    public function set_label($label) {
        $this->label = sanitize_text_field($label);
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
     * Set description
     *
     * @param string $description Group description
     */
    public function set_description($description) {
        $this->description = sanitize_text_field($description);
    }

    /**
     * Get fields
     *
     * @return ERFQ_Field[]
     */
    public function get_fields() {
        return $this->fields;
    }

    /**
     * Add a field to the group
     *
     * @param ERFQ_Field|array $field Field object or configuration
     */
    public function add_field($field) {
        if (is_array($field)) {
            $field = new ERFQ_Field($field);
        }
        $field->set_group_id($this->id);
        $this->fields[] = $field;
    }

    /**
     * Remove a field by ID
     *
     * @param string $field_id Field ID
     */
    public function remove_field($field_id) {
        $this->fields = array_filter($this->fields, function($field) use ($field_id) {
            return $field->get_id() !== $field_id;
        });
        $this->fields = array_values($this->fields);
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
     * @param string $css_class CSS class
     */
    public function set_css_class($css_class) {
        $this->css_class = sanitize_text_field($css_class);
    }

    /**
     * Get layout type
     *
     * @return string
     */
    public function get_layout() {
        return $this->layout;
    }

    /**
     * Set layout type
     *
     * @param string $layout Layout type
     */
    public function set_layout($layout) {
        $allowed = array('vertical', 'horizontal', 'grid');
        if (in_array($layout, $allowed, true)) {
            $this->layout = $layout;
        }
    }

    /**
     * Get number of columns
     *
     * @return int
     */
    public function get_columns() {
        return $this->columns;
    }

    /**
     * Set number of columns
     *
     * @param int $columns Number of columns
     */
    public function set_columns($columns) {
        $this->columns = absint($columns);
    }

    /**
     * Get conditional logic
     *
     * @return array
     */
    public function get_conditional_logic() {
        return $this->conditional_logic;
    }

    /**
     * Set conditional logic
     *
     * @param array $logic Conditional logic rules
     */
    public function set_conditional_logic($logic) {
        $this->conditional_logic = is_array($logic) ? $logic : array();
    }

    /**
     * Check if group has conditional logic
     *
     * @return bool
     */
    public function has_conditional_logic() {
        return !empty($this->conditional_logic);
    }

    /**
     * Get group classes
     *
     * @return string
     */
    public function get_group_classes() {
        $classes = array(
            'erfq-field-group',
            'erfq-group-' . $this->layout,
        );

        if ($this->css_class) {
            $classes[] = $this->css_class;
        }

        if ($this->layout === 'grid') {
            $classes[] = 'erfq-grid-cols-' . $this->columns;
        }

        return implode(' ', $classes);
    }

    /**
     * Convert group to array
     *
     * @return array
     */
    public function to_array() {
        $fields = array();
        foreach ($this->fields as $field) {
            $fields[] = $field->to_array();
        }

        return array(
            'id'                => $this->id,
            'label'             => $this->label,
            'description'       => $this->description,
            'css_class'         => $this->css_class,
            'layout'            => $this->layout,
            'columns'           => $this->columns,
            'conditional_logic' => $this->conditional_logic,
            'fields'            => $fields,
        );
    }

    /**
     * Create group from array
     *
     * @param array $config Group configuration
     *
     * @return ERFQ_Field_Group
     */
    public static function create($config) {
        return new self($config);
    }
}
