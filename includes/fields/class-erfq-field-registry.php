<?php
/**
 * Field Registry - Singleton for managing field types
 *
 * @package Event_RFQ_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ERFQ_Field_Registry
 *
 * Manages registration and retrieval of field types
 */
class ERFQ_Field_Registry {

    /**
     * Singleton instance
     *
     * @var ERFQ_Field_Registry
     */
    private static $instance = null;

    /**
     * Registered field types
     *
     * @var array
     */
    private $field_types = array();

    /**
     * Field type instances cache
     *
     * @var array
     */
    private $instances = array();

    /**
     * Private constructor for singleton
     */
    private function __construct() {}

    /**
     * Prevent cloning
     */
    private function __clone() {}

    /**
     * Prevent unserialization
     */
    public function __wakeup() {
        throw new Exception('Cannot unserialize singleton');
    }

    /**
     * Get the singleton instance
     *
     * @return ERFQ_Field_Registry
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Register a field type
     *
     * @param string $type  Field type identifier
     * @param string $class Class name that extends ERFQ_Field_Type_Abstract
     *
     * @return bool Whether registration was successful
     */
    public function register($type, $class) {
        $type = sanitize_key($type);

        if (empty($type)) {
            return false;
        }

        if (!class_exists($class)) {
            return false;
        }

        $this->field_types[$type] = $class;
        return true;
    }

    /**
     * Unregister a field type
     *
     * @param string $type Field type identifier
     *
     * @return bool
     */
    public function unregister($type) {
        if (isset($this->field_types[$type])) {
            unset($this->field_types[$type]);
            unset($this->instances[$type]);
            return true;
        }
        return false;
    }

    /**
     * Check if a field type is registered
     *
     * @param string $type Field type identifier
     *
     * @return bool
     */
    public function is_registered($type) {
        return isset($this->field_types[$type]);
    }

    /**
     * Get a field type instance
     *
     * @param string $type Field type identifier
     *
     * @return ERFQ_Field_Type_Abstract|null
     */
    public function get($type) {
        if (!isset($this->field_types[$type])) {
            return null;
        }

        if (!isset($this->instances[$type])) {
            $class = $this->field_types[$type];
            $this->instances[$type] = new $class();
        }

        return $this->instances[$type];
    }

    /**
     * Get all registered field types
     *
     * @return array Associative array of type => class
     */
    public function get_all() {
        return $this->field_types;
    }

    /**
     * Get all field types with their metadata
     *
     * @return array
     */
    public function get_all_with_meta() {
        $result = array();

        foreach ($this->field_types as $type => $class) {
            $instance = $this->get($type);
            if ($instance) {
                $settings = $instance->get_settings_schema();
                $result[$type] = array(
                    'type'                => $type,
                    'label'               => $instance->get_name(),
                    'name'                => $instance->get_name(),
                    'icon'                => $instance->get_icon(),
                    'category'            => $instance->get_category(),
                    'description'         => $instance->get_description(),
                    'settings'            => $settings,
                    'supports_options'    => in_array($type, array('select', 'checkbox', 'radio'), true),
                    'supports_placeholder'=> !in_array($type, array('checkbox', 'radio', 'file', 'hidden'), true),
                    'supports_default'    => !in_array($type, array('file', 'repeater'), true),
                    'supports_min_max'    => in_array($type, array('number', 'textarea'), true),
                );
            }
        }

        return $result;
    }

    /**
     * Get field types by category
     *
     * @param string $category Category name
     *
     * @return array
     */
    public function get_by_category($category) {
        $result = array();

        foreach ($this->field_types as $type => $class) {
            $instance = $this->get($type);
            if ($instance && $instance->get_category() === $category) {
                $result[$type] = $instance;
            }
        }

        return $result;
    }

    /**
     * Get available categories
     *
     * @return array
     */
    public function get_categories() {
        return array(
            'basic'    => __('Basic Fields', 'event-rfq-manager'),
            'advanced' => __('Advanced Fields', 'event-rfq-manager'),
            'layout'   => __('Layout Elements', 'event-rfq-manager'),
        );
    }

    /**
     * Render a field
     *
     * @param array $field_config Field configuration
     * @param mixed $value        Current field value
     *
     * @return string HTML output
     */
    public function render_field($field_config, $value = null) {
        if (!isset($field_config['type'])) {
            return '';
        }

        $instance = $this->get($field_config['type']);
        if (!$instance) {
            return '';
        }

        return $instance->render($field_config, $value);
    }

    /**
     * Validate a field value
     *
     * @param array $field_config Field configuration
     * @param mixed $value        Value to validate
     *
     * @return true|WP_Error True if valid, WP_Error if invalid
     */
    public function validate_field($field_config, $value) {
        if (!isset($field_config['type'])) {
            return new WP_Error('invalid_field', __('Invalid field type.', 'event-rfq-manager'));
        }

        $instance = $this->get($field_config['type']);
        if (!$instance) {
            return new WP_Error('unknown_field', __('Unknown field type.', 'event-rfq-manager'));
        }

        return $instance->validate($field_config, $value);
    }

    /**
     * Sanitize a field value
     *
     * @param array $field_config Field configuration
     * @param mixed $value        Value to sanitize
     *
     * @return mixed Sanitized value
     */
    public function sanitize_field($field_config, $value) {
        if (!isset($field_config['type'])) {
            return $value;
        }

        $instance = $this->get($field_config['type']);
        if (!$instance) {
            return $value;
        }

        return $instance->sanitize($field_config, $value);
    }

    /**
     * Get field value for display
     *
     * @param array $field_config Field configuration
     * @param mixed $value        Field value
     *
     * @return string Display value
     */
    public function get_display_value($field_config, $value) {
        if (!isset($field_config['type'])) {
            return is_array($value) ? implode(', ', $value) : $value;
        }

        $instance = $this->get($field_config['type']);
        if (!$instance) {
            return is_array($value) ? implode(', ', $value) : $value;
        }

        return $instance->get_display_value($field_config, $value);
    }
}
