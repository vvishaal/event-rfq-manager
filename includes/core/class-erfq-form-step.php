<?php
/**
 * Form Step model class for multi-step forms
 *
 * @package Event_RFQ_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ERFQ_Form_Step
 *
 * Represents a step in a multi-step form
 */
class ERFQ_Form_Step {

    /**
     * Step ID
     *
     * @var string
     */
    protected $id;

    /**
     * Step title
     *
     * @var string
     */
    protected $title;

    /**
     * Step description
     *
     * @var string
     */
    protected $description;

    /**
     * Step order
     *
     * @var int
     */
    protected $order = 0;

    /**
     * Field IDs in this step
     *
     * @var array
     */
    protected $field_ids = array();

    /**
     * Button text for next step
     *
     * @var string
     */
    protected $next_button_text;

    /**
     * Button text for previous step
     *
     * @var string
     */
    protected $prev_button_text;

    /**
     * CSS class for the step
     *
     * @var string
     */
    protected $css_class;

    /**
     * Icon class (for progress indicator)
     *
     * @var string
     */
    protected $icon;

    /**
     * Constructor
     *
     * @param array $config Step configuration
     */
    public function __construct($config = array()) {
        if (!empty($config)) {
            $this->load_from_array($config);
        }
    }

    /**
     * Load step from configuration array
     *
     * @param array $config Step configuration
     */
    public function load_from_array($config) {
        $this->id               = isset($config['id']) ? sanitize_key($config['id']) : 'step_' . wp_generate_uuid4();
        $this->title            = isset($config['title']) ? sanitize_text_field($config['title']) : '';
        $this->description      = isset($config['description']) ? sanitize_text_field($config['description']) : '';
        $this->order            = isset($config['order']) ? absint($config['order']) : 0;
        $this->field_ids        = isset($config['field_ids']) && is_array($config['field_ids']) ? $config['field_ids'] : array();
        $this->next_button_text = isset($config['next_button_text']) ? sanitize_text_field($config['next_button_text']) : __('Next', 'event-rfq-manager');
        $this->prev_button_text = isset($config['prev_button_text']) ? sanitize_text_field($config['prev_button_text']) : __('Previous', 'event-rfq-manager');
        $this->css_class        = isset($config['css_class']) ? sanitize_text_field($config['css_class']) : '';
        $this->icon             = isset($config['icon']) ? sanitize_text_field($config['icon']) : '';
    }

    /**
     * Get step ID
     *
     * @return string
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * Set step ID
     *
     * @param string $id Step ID
     */
    public function set_id($id) {
        $this->id = sanitize_key($id);
    }

    /**
     * Get step title
     *
     * @return string
     */
    public function get_title() {
        return $this->title;
    }

    /**
     * Set step title
     *
     * @param string $title Step title
     */
    public function set_title($title) {
        $this->title = sanitize_text_field($title);
    }

    /**
     * Get step description
     *
     * @return string
     */
    public function get_description() {
        return $this->description;
    }

    /**
     * Set step description
     *
     * @param string $description Step description
     */
    public function set_description($description) {
        $this->description = sanitize_text_field($description);
    }

    /**
     * Get step order
     *
     * @return int
     */
    public function get_order() {
        return $this->order;
    }

    /**
     * Set step order
     *
     * @param int $order Step order
     */
    public function set_order($order) {
        $this->order = absint($order);
    }

    /**
     * Get field IDs
     *
     * @return array
     */
    public function get_field_ids() {
        return $this->field_ids;
    }

    /**
     * Set field IDs
     *
     * @param array $field_ids Field IDs
     */
    public function set_field_ids($field_ids) {
        $this->field_ids = is_array($field_ids) ? $field_ids : array();
    }

    /**
     * Add a field to the step
     *
     * @param string $field_id Field ID
     */
    public function add_field($field_id) {
        if (!in_array($field_id, $this->field_ids, true)) {
            $this->field_ids[] = $field_id;
        }
    }

    /**
     * Remove a field from the step
     *
     * @param string $field_id Field ID
     */
    public function remove_field($field_id) {
        $this->field_ids = array_filter($this->field_ids, function($id) use ($field_id) {
            return $id !== $field_id;
        });
        $this->field_ids = array_values($this->field_ids);
    }

    /**
     * Check if step contains a field
     *
     * @param string $field_id Field ID
     *
     * @return bool
     */
    public function has_field($field_id) {
        return in_array($field_id, $this->field_ids, true);
    }

    /**
     * Get next button text
     *
     * @return string
     */
    public function get_next_button_text() {
        return $this->next_button_text;
    }

    /**
     * Set next button text
     *
     * @param string $text Button text
     */
    public function set_next_button_text($text) {
        $this->next_button_text = sanitize_text_field($text);
    }

    /**
     * Get previous button text
     *
     * @return string
     */
    public function get_prev_button_text() {
        return $this->prev_button_text;
    }

    /**
     * Set previous button text
     *
     * @param string $text Button text
     */
    public function set_prev_button_text($text) {
        $this->prev_button_text = sanitize_text_field($text);
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
     * Get icon class
     *
     * @return string
     */
    public function get_icon() {
        return $this->icon;
    }

    /**
     * Set icon class
     *
     * @param string $icon Icon class
     */
    public function set_icon($icon) {
        $this->icon = sanitize_text_field($icon);
    }

    /**
     * Get step wrapper classes
     *
     * @param bool $is_active Whether this is the active step
     *
     * @return string
     */
    public function get_step_classes($is_active = false) {
        $classes = array('erfq-form-step');

        if ($is_active) {
            $classes[] = 'erfq-step-active';
        }

        if ($this->css_class) {
            $classes[] = $this->css_class;
        }

        return implode(' ', $classes);
    }

    /**
     * Convert step to array
     *
     * @return array
     */
    public function to_array() {
        return array(
            'id'               => $this->id,
            'title'            => $this->title,
            'description'      => $this->description,
            'order'            => $this->order,
            'field_ids'        => $this->field_ids,
            'next_button_text' => $this->next_button_text,
            'prev_button_text' => $this->prev_button_text,
            'css_class'        => $this->css_class,
            'icon'             => $this->icon,
        );
    }

    /**
     * Create step from array
     *
     * @param array $config Step configuration
     *
     * @return ERFQ_Form_Step
     */
    public static function create($config) {
        return new self($config);
    }
}
