<?php
/**
 * Form model class
 *
 * @package Event_RFQ_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ERFQ_Form
 *
 * Represents a form definition
 */
class ERFQ_Form {

    /**
     * Form post ID
     *
     * @var int
     */
    protected $id;

    /**
     * Form title
     *
     * @var string
     */
    protected $title;

    /**
     * Form fields
     *
     * @var array
     */
    protected $fields = array();

    /**
     * Form steps (for multi-step forms)
     *
     * @var array
     */
    protected $steps = array();

    /**
     * Form settings
     *
     * @var array
     */
    protected $settings = array();

    /**
     * Form status
     *
     * @var string
     */
    protected $status;

    /**
     * Default settings
     *
     * @var array
     */
    protected static $default_settings = array(
        'email_recipient'      => '',
        'email_subject'        => '',
        'success_message'      => '',
        'error_message'        => '',
        'redirect_url'         => '',
        'honeypot_enabled'     => true,
        'recaptcha_enabled'    => false,
        'css_class'            => '',
        'submit_button_text'   => 'Submit',
        'enable_ajax'          => true,
        'confirmation_email'   => false,
        'confirmation_subject' => '',
        'confirmation_message' => '',
    );

    /**
     * Constructor
     *
     * @param int|WP_Post $form Form post ID or post object
     */
    public function __construct($form = null) {
        if ($form instanceof WP_Post) {
            $this->load_from_post($form);
        } elseif (is_numeric($form)) {
            $post = get_post($form);
            if ($post && $post->post_type === 'erfq_form') {
                $this->load_from_post($post);
            }
        }
    }

    /**
     * Load form data from a post object
     *
     * @param WP_Post $post The form post
     */
    protected function load_from_post($post) {
        $this->id     = $post->ID;
        $this->title  = $post->post_title;
        $this->status = $post->post_status;

        // Load fields
        $fields = get_post_meta($post->ID, '_erfq_form_fields', true);
        $this->fields = is_array($fields) ? $fields : array();

        // Load steps
        $steps = get_post_meta($post->ID, '_erfq_form_steps', true);
        $this->steps = is_array($steps) ? $steps : array();

        // Load settings with defaults
        $settings = get_post_meta($post->ID, '_erfq_form_settings', true);
        $this->settings = wp_parse_args(
            is_array($settings) ? $settings : array(),
            self::$default_settings
        );
    }

    /**
     * Get form ID
     *
     * @return int
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * Get form title
     *
     * @return string
     */
    public function get_title() {
        return $this->title;
    }

    /**
     * Set form title
     *
     * @param string $title The form title
     */
    public function set_title($title) {
        $this->title = sanitize_text_field($title);
    }

    /**
     * Get all form fields
     *
     * @return array
     */
    public function get_fields() {
        return $this->fields;
    }

    /**
     * Get a specific field by ID
     *
     * @param string $field_id The field ID
     *
     * @return array|null Field data or null if not found
     */
    public function get_field($field_id) {
        foreach ($this->fields as $field) {
            if (isset($field['id']) && $field['id'] === $field_id) {
                return $field;
            }
        }
        return null;
    }

    /**
     * Set form fields
     *
     * @param array $fields Array of field configurations
     */
    public function set_fields($fields) {
        $this->fields = is_array($fields) ? $fields : array();
    }

    /**
     * Add a field to the form
     *
     * @param array $field Field configuration
     */
    public function add_field($field) {
        if (!isset($field['id'])) {
            $field['id'] = $this->generate_field_id();
        }
        $this->fields[] = $field;
    }

    /**
     * Remove a field by ID
     *
     * @param string $field_id The field ID to remove
     */
    public function remove_field($field_id) {
        $this->fields = array_filter($this->fields, function($field) use ($field_id) {
            return !isset($field['id']) || $field['id'] !== $field_id;
        });
        $this->fields = array_values($this->fields);
    }

    /**
     * Get form steps
     *
     * @return array
     */
    public function get_steps() {
        return $this->steps;
    }

    /**
     * Set form steps
     *
     * @param array $steps Step configurations
     */
    public function set_steps($steps) {
        $this->steps = is_array($steps) ? $steps : array();
    }

    /**
     * Check if form is multi-step
     *
     * @return bool
     */
    public function is_multi_step() {
        return !empty($this->steps) && count($this->steps) > 1;
    }

    /**
     * Get form settings
     *
     * @return array
     */
    public function get_settings() {
        return $this->settings;
    }

    /**
     * Get a specific setting
     *
     * @param string $key     Setting key
     * @param mixed  $default Default value
     *
     * @return mixed
     */
    public function get_setting($key, $default = null) {
        if (isset($this->settings[$key])) {
            return $this->settings[$key];
        }
        if (isset(self::$default_settings[$key])) {
            return self::$default_settings[$key];
        }
        return $default;
    }

    /**
     * Set a setting value
     *
     * @param string $key   Setting key
     * @param mixed  $value Setting value
     */
    public function set_setting($key, $value) {
        $this->settings[$key] = $value;
    }

    /**
     * Set all settings
     *
     * @param array $settings Settings array
     */
    public function set_settings($settings) {
        $this->settings = wp_parse_args(
            is_array($settings) ? $settings : array(),
            self::$default_settings
        );
    }

    /**
     * Get form status
     *
     * @return string
     */
    public function get_status() {
        return $this->status;
    }

    /**
     * Set form status
     *
     * @param string $status The status (publish, draft, etc.)
     */
    public function set_status($status) {
        $this->status = $status;
    }

    /**
     * Check if form is published/active
     *
     * @return bool
     */
    public function is_active() {
        return $this->status === 'publish';
    }

    /**
     * Generate a unique field ID
     *
     * @return string
     */
    protected function generate_field_id() {
        return 'field_' . wp_generate_uuid4();
    }

    /**
     * Save the form to database
     *
     * @return int|WP_Error Form ID on success, WP_Error on failure
     */
    public function save() {
        $post_data = array(
            'post_type'   => 'erfq_form',
            'post_title'  => $this->title,
            'post_status' => $this->status ?: 'publish',
        );

        if ($this->id) {
            $post_data['ID'] = $this->id;
            $result = wp_update_post($post_data, true);
        } else {
            $result = wp_insert_post($post_data, true);
            if (!is_wp_error($result)) {
                $this->id = $result;
            }
        }

        if (is_wp_error($result)) {
            return $result;
        }

        // Save meta
        update_post_meta($this->id, '_erfq_form_fields', $this->fields);
        update_post_meta($this->id, '_erfq_form_steps', $this->steps);
        update_post_meta($this->id, '_erfq_form_settings', $this->settings);

        return $this->id;
    }

    /**
     * Delete the form
     *
     * @param bool $force_delete Whether to bypass trash
     *
     * @return bool Whether the form was deleted
     */
    public function delete($force_delete = false) {
        if (!$this->id) {
            return false;
        }

        $result = wp_delete_post($this->id, $force_delete);
        return $result !== false;
    }

    /**
     * Duplicate the form
     *
     * @return ERFQ_Form|WP_Error New form instance or error
     */
    public function duplicate() {
        $new_form = new self();
        $new_form->set_title($this->title . ' ' . __('(Copy)', 'event-rfq-manager'));
        $new_form->set_fields($this->fields);
        $new_form->set_steps($this->steps);
        $new_form->set_settings($this->settings);
        $new_form->set_status('draft');

        $result = $new_form->save();
        if (is_wp_error($result)) {
            return $result;
        }

        return $new_form;
    }

    /**
     * Export form as array
     *
     * @return array
     */
    public function to_array() {
        return array(
            'id'       => $this->id,
            'title'    => $this->title,
            'fields'   => $this->fields,
            'steps'    => $this->steps,
            'settings' => $this->settings,
            'status'   => $this->status,
        );
    }

    /**
     * Export form as JSON
     *
     * @return string
     */
    public function to_json() {
        return wp_json_encode($this->to_array());
    }

    /**
     * Import form from array
     *
     * @param array $data Form data
     *
     * @return ERFQ_Form
     */
    public static function from_array($data) {
        $form = new self();

        if (isset($data['title'])) {
            $form->set_title($data['title']);
        }
        if (isset($data['fields'])) {
            $form->set_fields($data['fields']);
        }
        if (isset($data['steps'])) {
            $form->set_steps($data['steps']);
        }
        if (isset($data['settings'])) {
            $form->set_settings($data['settings']);
        }
        if (isset($data['status'])) {
            $form->set_status($data['status']);
        }

        return $form;
    }

    /**
     * Import form from JSON
     *
     * @param string $json JSON string
     *
     * @return ERFQ_Form|null
     */
    public static function from_json($json) {
        $data = json_decode($json, true);
        if (!is_array($data)) {
            return null;
        }
        return self::from_array($data);
    }

    /**
     * Get all forms
     *
     * @param array $args Query arguments
     *
     * @return ERFQ_Form[]
     */
    public static function get_all($args = array()) {
        $defaults = array(
            'post_type'      => 'erfq_form',
            'posts_per_page' => -1,
            'post_status'    => 'any',
            'orderby'        => 'date',
            'order'          => 'DESC',
        );

        $args = wp_parse_args($args, $defaults);
        $posts = get_posts($args);

        $forms = array();
        foreach ($posts as $post) {
            $forms[] = new self($post);
        }

        return $forms;
    }

    /**
     * Get form by ID
     *
     * @param int $id Form ID
     *
     * @return ERFQ_Form|null
     */
    public static function get_by_id($id) {
        $form = new self($id);
        return $form->get_id() ? $form : null;
    }

    /**
     * Get entry count for this form
     *
     * @return int
     */
    public function get_entry_count() {
        global $wpdb;

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(p.ID) FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'erfq_entry'
            AND p.post_status = 'publish'
            AND pm.meta_key = '_erfq_entry_form_id'
            AND pm.meta_value = %d",
            $this->id
        ));
    }
}
