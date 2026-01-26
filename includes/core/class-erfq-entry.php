<?php
/**
 * Entry model class
 *
 * @package Event_RFQ_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ERFQ_Entry
 *
 * Represents a form submission entry
 */
class ERFQ_Entry {

    /**
     * Entry post ID
     *
     * @var int
     */
    protected $id;

    /**
     * Associated form ID
     *
     * @var int
     */
    protected $form_id;

    /**
     * Entry data (field values)
     *
     * @var array
     */
    protected $data = array();

    /**
     * Entry status
     *
     * @var string
     */
    protected $status = 'new';

    /**
     * Submitter IP address
     *
     * @var string
     */
    protected $ip_address;

    /**
     * User agent
     *
     * @var string
     */
    protected $user_agent;

    /**
     * Submitted timestamp
     *
     * @var string
     */
    protected $submitted_at;

    /**
     * File attachments
     *
     * @var array
     */
    protected $files = array();

    /**
     * Admin notes
     *
     * @var array
     */
    protected $notes = array();

    /**
     * Valid statuses
     *
     * @var array
     */
    public static $statuses = array(
        'new'       => 'New',
        'read'      => 'Read',
        'processed' => 'Processed',
        'spam'      => 'Spam',
    );

    /**
     * Constructor
     *
     * @param int|WP_Post $entry Entry post ID or post object
     */
    public function __construct($entry = null) {
        if ($entry instanceof WP_Post) {
            $this->load_from_post($entry);
        } elseif (is_numeric($entry)) {
            $post = get_post($entry);
            if ($post && $post->post_type === 'erfq_entry') {
                $this->load_from_post($post);
            }
        }
    }

    /**
     * Load entry data from a post object
     *
     * @param WP_Post $post The entry post
     */
    protected function load_from_post($post) {
        $this->id           = $post->ID;
        $this->form_id      = (int) get_post_meta($post->ID, '_erfq_entry_form_id', true);
        $this->status       = get_post_meta($post->ID, '_erfq_entry_status', true) ?: 'new';
        $this->ip_address   = get_post_meta($post->ID, '_erfq_entry_ip', true);
        $this->user_agent   = get_post_meta($post->ID, '_erfq_entry_user_agent', true);
        $this->submitted_at = get_post_meta($post->ID, '_erfq_entry_submitted_at', true) ?: $post->post_date;

        // Load entry data
        $data = get_post_meta($post->ID, '_erfq_entry_data', true);
        $this->data = is_array($data) ? $data : array();

        // Load files
        $files = get_post_meta($post->ID, '_erfq_entry_files', true);
        $this->files = is_array($files) ? $files : array();

        // Load notes
        $notes = get_post_meta($post->ID, '_erfq_entry_notes', true);
        $this->notes = is_array($notes) ? $notes : array();
    }

    /**
     * Get entry ID
     *
     * @return int
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * Get form ID
     *
     * @return int
     */
    public function get_form_id() {
        return $this->form_id;
    }

    /**
     * Set form ID
     *
     * @param int $form_id Form ID
     */
    public function set_form_id($form_id) {
        $this->form_id = (int) $form_id;
    }

    /**
     * Get associated form
     *
     * @return ERFQ_Form|null
     */
    public function get_form() {
        if (!$this->form_id) {
            return null;
        }
        return ERFQ_Form::get_by_id($this->form_id);
    }

    /**
     * Get entry data
     *
     * @return array
     */
    public function get_data() {
        return $this->data;
    }

    /**
     * Get a specific field value
     *
     * @param string $field_id Field ID
     * @param mixed  $default  Default value
     *
     * @return mixed
     */
    public function get_field_value($field_id, $default = null) {
        return isset($this->data[$field_id]) ? $this->data[$field_id] : $default;
    }

    /**
     * Set entry data
     *
     * @param array $data Field values
     */
    public function set_data($data) {
        $this->data = is_array($data) ? $data : array();
    }

    /**
     * Set a specific field value
     *
     * @param string $field_id Field ID
     * @param mixed  $value    Field value
     */
    public function set_field_value($field_id, $value) {
        $this->data[$field_id] = $value;
    }

    /**
     * Get entry status
     *
     * @return string
     */
    public function get_status() {
        return $this->status;
    }

    /**
     * Get status label
     *
     * @return string
     */
    public function get_status_label() {
        return isset(self::$statuses[$this->status]) ? self::$statuses[$this->status] : $this->status;
    }

    /**
     * Set entry status
     *
     * @param string $status Entry status
     *
     * @return bool Whether status was updated
     */
    public function set_status($status) {
        if (!array_key_exists($status, self::$statuses)) {
            return false;
        }
        $this->status = $status;
        return true;
    }

    /**
     * Get IP address
     *
     * @return string
     */
    public function get_ip_address() {
        return $this->ip_address;
    }

    /**
     * Get IP address (alias)
     *
     * @return string
     */
    public function get_ip() {
        return $this->ip_address;
    }

    /**
     * Set IP address
     *
     * @param string $ip IP address
     */
    public function set_ip_address($ip) {
        $this->ip_address = sanitize_text_field($ip);
    }

    /**
     * Get user agent
     *
     * @return string
     */
    public function get_user_agent() {
        return $this->user_agent;
    }

    /**
     * Set user agent
     *
     * @param string $user_agent User agent string
     */
    public function set_user_agent($user_agent) {
        $this->user_agent = sanitize_text_field($user_agent);
    }

    /**
     * Get submitted timestamp
     *
     * @param string $format Date format
     *
     * @return string
     */
    public function get_submitted_at($format = '') {
        if (!$format) {
            $format = get_option('date_format') . ' ' . get_option('time_format');
        }
        return mysql2date($format, $this->submitted_at);
    }

    /**
     * Set submitted timestamp
     *
     * @param string $datetime MySQL datetime
     */
    public function set_submitted_at($datetime) {
        $this->submitted_at = $datetime;
    }

    /**
     * Get files
     *
     * @return array
     */
    public function get_files() {
        return $this->files;
    }

    /**
     * Set files
     *
     * @param array $files File data
     */
    public function set_files($files) {
        $this->files = is_array($files) ? $files : array();
    }

    /**
     * Add a file
     *
     * @param string $field_id Field ID
     * @param array  $file     File data
     */
    public function add_file($field_id, $file) {
        if (!isset($this->files[$field_id])) {
            $this->files[$field_id] = array();
        }
        $this->files[$field_id][] = $file;
    }

    /**
     * Get notes
     *
     * @return array
     */
    public function get_notes() {
        return $this->notes;
    }

    /**
     * Add a note
     *
     * @param string $content Note content
     * @param int    $user_id User ID (default: current user)
     */
    public function add_note($content, $user_id = null) {
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }

        $this->notes[] = array(
            'content'    => sanitize_textarea_field($content),
            'user_id'    => $user_id,
            'created_at' => current_time('mysql'),
        );
    }

    /**
     * Save the entry to database
     *
     * @return int|WP_Error Entry ID on success, WP_Error on failure
     */
    public function save() {
        // Generate title from email or first text field
        $title = $this->generate_title();

        $post_data = array(
            'post_type'   => 'erfq_entry',
            'post_title'  => $title,
            'post_status' => 'publish',
        );

        if ($this->id) {
            $post_data['ID'] = $this->id;
            $result = wp_update_post($post_data, true);
        } else {
            $result = wp_insert_post($post_data, true);
            if (!is_wp_error($result)) {
                $this->id = $result;
                $this->submitted_at = current_time('mysql');
            }
        }

        if (is_wp_error($result)) {
            return $result;
        }

        // Save meta
        update_post_meta($this->id, '_erfq_entry_form_id', $this->form_id);
        update_post_meta($this->id, '_erfq_entry_data', $this->data);
        update_post_meta($this->id, '_erfq_entry_status', $this->status);
        update_post_meta($this->id, '_erfq_entry_ip', $this->ip_address);
        update_post_meta($this->id, '_erfq_entry_user_agent', $this->user_agent);
        update_post_meta($this->id, '_erfq_entry_submitted_at', $this->submitted_at);
        update_post_meta($this->id, '_erfq_entry_files', $this->files);
        update_post_meta($this->id, '_erfq_entry_notes', $this->notes);

        return $this->id;
    }

    /**
     * Generate entry title
     *
     * @return string
     */
    protected function generate_title() {
        // Try to find email field
        foreach ($this->data as $key => $value) {
            if (is_string($value) && is_email($value)) {
                return $value;
            }
        }

        // Try common field names
        $common_fields = array('email', 'contact_email', 'name', 'contact_name', 'full_name');
        foreach ($common_fields as $field) {
            if (isset($this->data[$field]) && !empty($this->data[$field])) {
                return is_string($this->data[$field]) ? $this->data[$field] : '';
            }
        }

        // Default title
        return sprintf(__('Entry #%s', 'event-rfq-manager'), $this->id ?: 'new');
    }

    /**
     * Delete the entry
     *
     * @param bool $force_delete Whether to bypass trash
     *
     * @return bool Whether the entry was deleted
     */
    public function delete($force_delete = false) {
        if (!$this->id) {
            return false;
        }

        // Delete associated files
        foreach ($this->files as $field_files) {
            foreach ((array) $field_files as $file) {
                if (isset($file['path']) && file_exists($file['path'])) {
                    wp_delete_file($file['path']);
                }
            }
        }

        $result = wp_delete_post($this->id, $force_delete);
        return $result !== false;
    }

    /**
     * Mark entry as read
     */
    public function mark_as_read() {
        if ($this->status === 'new') {
            $this->set_status('read');
            if ($this->id) {
                update_post_meta($this->id, '_erfq_entry_status', 'read');
            }
        }
    }

    /**
     * Convert entry to array
     *
     * @return array
     */
    public function to_array() {
        return array(
            'id'           => $this->id,
            'form_id'      => $this->form_id,
            'data'         => $this->data,
            'status'       => $this->status,
            'ip_address'   => $this->ip_address,
            'user_agent'   => $this->user_agent,
            'submitted_at' => $this->submitted_at,
            'files'        => $this->files,
            'notes'        => $this->notes,
        );
    }

    /**
     * Get all entries for a form
     *
     * @param int   $form_id Form ID
     * @param array $args    Query arguments
     *
     * @return ERFQ_Entry[]
     */
    public static function get_by_form($form_id, $args = array()) {
        $defaults = array(
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'date',
            'order'          => 'DESC',
        );

        $args = wp_parse_args($args, $defaults);
        $args['post_type'] = 'erfq_entry';
        $args['meta_query'] = array(
            array(
                'key'   => '_erfq_entry_form_id',
                'value' => $form_id,
            ),
        );

        $posts = get_posts($args);
        $entries = array();

        foreach ($posts as $post) {
            $entries[] = new self($post);
        }

        return $entries;
    }

    /**
     * Get entry by ID
     *
     * @param int $id Entry ID
     *
     * @return ERFQ_Entry|null
     */
    public static function get_by_id($id) {
        $entry = new self($id);
        return $entry->get_id() ? $entry : null;
    }

    /**
     * Get entries by status
     *
     * @param string $status Entry status
     * @param array  $args   Query arguments
     *
     * @return ERFQ_Entry[]
     */
    public static function get_by_status($status, $args = array()) {
        $defaults = array(
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'date',
            'order'          => 'DESC',
        );

        $args = wp_parse_args($args, $defaults);
        $args['post_type'] = 'erfq_entry';
        $args['meta_query'] = array(
            array(
                'key'   => '_erfq_entry_status',
                'value' => $status,
            ),
        );

        $posts = get_posts($args);
        $entries = array();

        foreach ($posts as $post) {
            $entries[] = new self($post);
        }

        return $entries;
    }

    /**
     * Count entries by status
     *
     * @param int|null $form_id Form ID (null for all forms)
     *
     * @return array Status counts
     */
    public static function count_by_status($form_id = null) {
        global $wpdb;

        $counts = array();
        foreach (array_keys(self::$statuses) as $status) {
            $counts[$status] = 0;
        }

        $query = "
            SELECT pm.meta_value as status, COUNT(*) as count
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_erfq_entry_status'
            WHERE p.post_type = 'erfq_entry'
            AND p.post_status = 'publish'
        ";

        if ($form_id) {
            $query .= $wpdb->prepare("
                AND p.ID IN (
                    SELECT post_id FROM {$wpdb->postmeta}
                    WHERE meta_key = '_erfq_entry_form_id' AND meta_value = %d
                )
            ", $form_id);
        }

        $query .= " GROUP BY pm.meta_value";

        $results = $wpdb->get_results($query);

        foreach ($results as $row) {
            if (isset($counts[$row->status])) {
                $counts[$row->status] = (int) $row->count;
            }
        }

        $counts['total'] = array_sum($counts);

        return $counts;
    }

    /**
     * Count entries for a specific form
     *
     * @param int $form_id Form ID
     *
     * @return int Entry count
     */
    public static function count_by_form($form_id) {
        global $wpdb;

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'erfq_entry'
            AND p.post_status = 'publish'
            AND pm.meta_key = '_erfq_entry_form_id'
            AND pm.meta_value = %d",
            $form_id
        ));
    }
}
