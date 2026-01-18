<?php
/**
 * Entry Detail Page class
 *
 * @package Event_RFQ_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ERFQ_Entry_Detail_Page
 *
 * Displays a single entry's details
 */
class ERFQ_Entry_Detail_Page {

    /**
     * Entry object
     *
     * @var ERFQ_Entry
     */
    protected $entry;

    /**
     * Form object
     *
     * @var ERFQ_Form
     */
    protected $form;

    /**
     * Constructor
     *
     * @param int $entry_id Entry ID
     */
    public function __construct($entry_id) {
        $this->entry = ERFQ_Entry::get_by_id($entry_id);

        if ($this->entry) {
            $this->form = $this->entry->get_form();

            // Mark as read when viewing
            $this->entry->mark_as_read();
        }
    }

    /**
     * Render the entry detail page
     */
    public function render() {
        if (!$this->entry) {
            wp_die(__('Entry not found.', 'event-rfq-manager'));
        }

        include ERFQ_PLUGIN_DIR . 'admin/views/entry-detail.php';
    }

    /**
     * Get entry data formatted for display
     *
     * @return array
     */
    public function get_formatted_data() {
        if (!$this->entry || !$this->form) {
            return array();
        }

        $registry = ERFQ_Field_Registry::get_instance();
        $fields = $this->form->get_fields();
        $data = $this->entry->get_data();
        $formatted = array();

        foreach ($fields as $field) {
            $field_id = isset($field['id']) ? $field['id'] : '';
            $label = isset($field['label']) ? $field['label'] : $field_id;

            if (!isset($data[$field_id])) {
                continue;
            }

            $value = $data[$field_id];
            $display_value = $registry->get_display_value($field, $value);

            $formatted[] = array(
                'label' => $label,
                'value' => $display_value,
                'raw'   => $value,
                'type'  => isset($field['type']) ? $field['type'] : 'text',
            );
        }

        return $formatted;
    }

    /**
     * Get entry files
     *
     * @return array
     */
    public function get_files() {
        if (!$this->entry) {
            return array();
        }

        return $this->entry->get_files();
    }

    /**
     * Get entry notes
     *
     * @return array
     */
    public function get_notes() {
        if (!$this->entry) {
            return array();
        }

        $notes = $this->entry->get_notes();

        // Add user info to notes
        foreach ($notes as &$note) {
            if (isset($note['user_id'])) {
                $user = get_user_by('id', $note['user_id']);
                $note['user_name'] = $user ? $user->display_name : __('Unknown', 'event-rfq-manager');
            }
        }

        return $notes;
    }
}
