<?php
/**
 * Admin AJAX Handlers
 *
 * @package Event_RFQ_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ERFQ_Ajax_Handlers
 *
 * Handles admin AJAX requests
 */
class ERFQ_Ajax_Handlers {

    /**
     * Save form
     */
    public function save_form() {
        check_ajax_referer('erfq_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'event-rfq-manager')));
        }

        $form_id = isset($_POST['form_id']) ? absint($_POST['form_id']) : 0;
        $form_data = isset($_POST['form_data']) ? $_POST['form_data'] : array();

        // Decode if JSON string
        if (is_string($form_data)) {
            $form_data = json_decode(stripslashes($form_data), true);
        }

        if (!is_array($form_data)) {
            wp_send_json_error(array('message' => __('Invalid form data.', 'event-rfq-manager')));
        }

        // Get or create form
        if ($form_id) {
            $form = ERFQ_Form::get_by_id($form_id);
            if (!$form) {
                wp_send_json_error(array('message' => __('Form not found.', 'event-rfq-manager')));
            }
        } else {
            $form = new ERFQ_Form();
        }

        // Update form data
        if (isset($form_data['title'])) {
            $form->set_title($form_data['title']);
        }

        if (isset($form_data['fields'])) {
            $form->set_fields($form_data['fields']);
        }

        if (isset($form_data['steps'])) {
            $form->set_steps($form_data['steps']);
        }

        if (isset($form_data['settings'])) {
            $form->set_settings($form_data['settings']);
        }

        $status = isset($form_data['status']) ? $form_data['status'] : 'publish';
        $form->set_status($status);

        $result = $form->save();

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success(array(
            'message' => __('Form saved successfully.', 'event-rfq-manager'),
            'form_id' => $form->get_id(),
        ));
    }

    /**
     * Load form
     */
    public function load_form() {
        check_ajax_referer('erfq_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'event-rfq-manager')));
        }

        $form_id = isset($_POST['form_id']) ? absint($_POST['form_id']) : 0;

        if (!$form_id) {
            wp_send_json_error(array('message' => __('Invalid form ID.', 'event-rfq-manager')));
        }

        $form = ERFQ_Form::get_by_id($form_id);

        if (!$form) {
            wp_send_json_error(array('message' => __('Form not found.', 'event-rfq-manager')));
        }

        wp_send_json_success(array(
            'form' => $form->to_array(),
        ));
    }

    /**
     * Delete form
     */
    public function delete_form() {
        check_ajax_referer('erfq_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'event-rfq-manager')));
        }

        $form_id = isset($_POST['form_id']) ? absint($_POST['form_id']) : 0;

        if (!$form_id) {
            wp_send_json_error(array('message' => __('Invalid form ID.', 'event-rfq-manager')));
        }

        $form = ERFQ_Form::get_by_id($form_id);

        if (!$form) {
            wp_send_json_error(array('message' => __('Form not found.', 'event-rfq-manager')));
        }

        if ($form->delete(true)) {
            wp_send_json_success(array('message' => __('Form deleted.', 'event-rfq-manager')));
        } else {
            wp_send_json_error(array('message' => __('Failed to delete form.', 'event-rfq-manager')));
        }
    }

    /**
     * Duplicate form
     */
    public function duplicate_form() {
        check_ajax_referer('erfq_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'event-rfq-manager')));
        }

        $form_id = isset($_POST['form_id']) ? absint($_POST['form_id']) : 0;

        if (!$form_id) {
            wp_send_json_error(array('message' => __('Invalid form ID.', 'event-rfq-manager')));
        }

        $form = ERFQ_Form::get_by_id($form_id);

        if (!$form) {
            wp_send_json_error(array('message' => __('Form not found.', 'event-rfq-manager')));
        }

        $new_form = $form->duplicate();

        if (is_wp_error($new_form)) {
            wp_send_json_error(array('message' => $new_form->get_error_message()));
        }

        wp_send_json_success(array(
            'message' => __('Form duplicated.', 'event-rfq-manager'),
            'form_id' => $new_form->get_id(),
        ));
    }

    /**
     * Update entry status
     */
    public function update_entry_status() {
        check_ajax_referer('erfq_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'event-rfq-manager')));
        }

        $entry_id = isset($_POST['entry_id']) ? absint($_POST['entry_id']) : 0;
        $status = isset($_POST['status']) ? sanitize_key($_POST['status']) : '';

        if (!$entry_id || !$status) {
            wp_send_json_error(array('message' => __('Invalid request.', 'event-rfq-manager')));
        }

        $entry = ERFQ_Entry::get_by_id($entry_id);

        if (!$entry) {
            wp_send_json_error(array('message' => __('Entry not found.', 'event-rfq-manager')));
        }

        if (!$entry->set_status($status)) {
            wp_send_json_error(array('message' => __('Invalid status.', 'event-rfq-manager')));
        }

        $entry->save();

        wp_send_json_success(array('message' => __('Status updated.', 'event-rfq-manager')));
    }

    /**
     * Delete entry
     */
    public function delete_entry() {
        check_ajax_referer('erfq_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'event-rfq-manager')));
        }

        $entry_id = isset($_POST['entry_id']) ? absint($_POST['entry_id']) : 0;

        if (!$entry_id) {
            wp_send_json_error(array('message' => __('Invalid entry ID.', 'event-rfq-manager')));
        }

        $entry = ERFQ_Entry::get_by_id($entry_id);

        if (!$entry) {
            wp_send_json_error(array('message' => __('Entry not found.', 'event-rfq-manager')));
        }

        if ($entry->delete(true)) {
            wp_send_json_success(array('message' => __('Entry deleted.', 'event-rfq-manager')));
        } else {
            wp_send_json_error(array('message' => __('Failed to delete entry.', 'event-rfq-manager')));
        }
    }

    /**
     * Export entries
     */
    public function export_entries() {
        check_ajax_referer('erfq_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'event-rfq-manager')));
        }

        $entry_ids = isset($_POST['entry_ids']) ? array_map('absint', $_POST['entry_ids']) : array();
        $form_id = isset($_POST['form_id']) ? absint($_POST['form_id']) : 0;
        $format = isset($_POST['format']) ? sanitize_key($_POST['format']) : 'csv';

        if (empty($entry_ids)) {
            wp_send_json_error(array('message' => __('No entries selected.', 'event-rfq-manager')));
        }

        $export = new ERFQ_Export_Service();

        if ($format === 'csv') {
            $export->download_csv($entry_ids, $form_id);
        } elseif ($format === 'pdf' && count($entry_ids) === 1) {
            $export->download_pdf($entry_ids[0]);
        } else {
            wp_send_json_error(array('message' => __('Invalid export format.', 'event-rfq-manager')));
        }
    }

    /**
     * Add entry note
     */
    public function add_entry_note() {
        check_ajax_referer('erfq_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'event-rfq-manager')));
        }

        $entry_id = isset($_POST['entry_id']) ? absint($_POST['entry_id']) : 0;
        $note = isset($_POST['note']) ? sanitize_textarea_field($_POST['note']) : '';

        if (!$entry_id || empty($note)) {
            wp_send_json_error(array('message' => __('Invalid request.', 'event-rfq-manager')));
        }

        $entry = ERFQ_Entry::get_by_id($entry_id);

        if (!$entry) {
            wp_send_json_error(array('message' => __('Entry not found.', 'event-rfq-manager')));
        }

        $entry->add_note($note);
        $entry->save();

        wp_send_json_success(array(
            'message' => __('Note added.', 'event-rfq-manager'),
            'note'    => array(
                'content'    => $note,
                'user_name'  => wp_get_current_user()->display_name,
                'created_at' => current_time('mysql'),
            ),
        ));
    }
}
