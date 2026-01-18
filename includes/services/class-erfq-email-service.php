<?php
/**
 * Email Service
 *
 * @package Event_RFQ_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ERFQ_Email_Service
 *
 * Handles email notifications
 */
class ERFQ_Email_Service {

    /**
     * Send admin notification email
     *
     * @param ERFQ_Form  $form  Form object
     * @param ERFQ_Entry $entry Entry object
     *
     * @return bool Whether the email was sent
     */
    public function send_admin_notification($form, $entry) {
        $recipient = $form->get_setting('email_recipient');

        if (empty($recipient)) {
            $recipient = get_option('erfq_default_email', get_option('admin_email'));
        }

        if (empty($recipient)) {
            return false;
        }

        $subject = $this->process_merge_tags(
            $form->get_setting('email_subject') ?: __('New Form Submission: {form_title}', 'event-rfq-manager'),
            $form,
            $entry
        );

        $message = $this->build_admin_email_body($form, $entry);

        $headers = $this->get_email_headers($form, $entry);

        return $this->send_email($recipient, $subject, $message, $headers);
    }

    /**
     * Send user confirmation email
     *
     * @param ERFQ_Form  $form  Form object
     * @param ERFQ_Entry $entry Entry object
     *
     * @return bool Whether the email was sent
     */
    public function send_user_confirmation($form, $entry) {
        // Find email field in submission
        $email = $this->find_email_in_entry($form, $entry);

        if (empty($email)) {
            return false;
        }

        $subject = $this->process_merge_tags(
            $form->get_setting('confirmation_subject') ?: __('Thank you for your submission', 'event-rfq-manager'),
            $form,
            $entry
        );

        $message = $form->get_setting('confirmation_message');
        if (empty($message)) {
            $message = $this->get_default_confirmation_message();
        }

        $message = $this->process_merge_tags($message, $form, $entry);

        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>',
        );

        return $this->send_email($email, $subject, $this->wrap_html_email($message), $headers);
    }

    /**
     * Build admin notification email body
     *
     * @param ERFQ_Form  $form  Form object
     * @param ERFQ_Entry $entry Entry object
     *
     * @return string
     */
    protected function build_admin_email_body($form, $entry) {
        $registry = ERFQ_Field_Registry::get_instance();
        $fields = $form->get_fields();
        $data = $entry->get_data();

        $body = '<html><head><style>';
        $body .= 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif; }';
        $body .= 'table { border-collapse: collapse; width: 100%; max-width: 600px; }';
        $body .= 'th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e0e0e0; }';
        $body .= 'th { background: #f5f5f5; font-weight: 600; width: 35%; }';
        $body .= '.header { background: #0073aa; color: white; padding: 20px; }';
        $body .= '.footer { background: #f5f5f5; padding: 15px; font-size: 12px; color: #666; }';
        $body .= '</style></head><body>';

        $body .= '<div class="header">';
        $body .= '<h2 style="margin:0;">' . esc_html__('New Form Submission', 'event-rfq-manager') . '</h2>';
        $body .= '<p style="margin:5px 0 0 0;">' . esc_html($form->get_title()) . '</p>';
        $body .= '</div>';

        $body .= '<table>';

        foreach ($fields as $field) {
            $field_id = isset($field['id']) ? $field['id'] : '';
            $label = isset($field['label']) ? $field['label'] : $field_id;

            if (!isset($data[$field_id])) {
                continue;
            }

            $value = $data[$field_id];
            $display_value = $registry->get_display_value($field, $value);

            if (empty($display_value) && $display_value !== '0') {
                continue;
            }

            $body .= '<tr>';
            $body .= '<th>' . esc_html($label) . '</th>';
            $body .= '<td>' . $display_value . '</td>';
            $body .= '</tr>';
        }

        $body .= '</table>';

        // Entry meta info
        $body .= '<div class="footer">';
        $body .= '<p><strong>' . esc_html__('Submitted:', 'event-rfq-manager') . '</strong> ' . $entry->get_submitted_at() . '</p>';
        $body .= '<p><strong>' . esc_html__('IP Address:', 'event-rfq-manager') . '</strong> ' . esc_html($entry->get_ip_address()) . '</p>';

        $entry_url = admin_url('admin.php?page=erfq-entries&action=view&entry_id=' . $entry->get_id());
        $body .= '<p><a href="' . esc_url($entry_url) . '">' . esc_html__('View Entry in Admin', 'event-rfq-manager') . '</a></p>';

        $body .= '</div>';
        $body .= '</body></html>';

        return $body;
    }

    /**
     * Get default confirmation message
     *
     * @return string
     */
    protected function get_default_confirmation_message() {
        $message = '<h2>' . __('Thank you for your submission!', 'event-rfq-manager') . '</h2>';
        $message .= '<p>' . __('We have received your submission and will get back to you soon.', 'event-rfq-manager') . '</p>';
        $message .= '<p>' . __('Here is a summary of your submission:', 'event-rfq-manager') . '</p>';
        $message .= '{all_fields}';
        return $message;
    }

    /**
     * Process merge tags in text
     *
     * @param string     $text  Text with merge tags
     * @param ERFQ_Form  $form  Form object
     * @param ERFQ_Entry $entry Entry object
     *
     * @return string
     */
    public function process_merge_tags($text, $form, $entry) {
        $registry = ERFQ_Field_Registry::get_instance();
        $fields = $form->get_fields();
        $data = $entry->get_data();

        // Standard merge tags
        $replacements = array(
            '{form_title}'     => $form->get_title(),
            '{form_id}'        => $form->get_id(),
            '{entry_id}'       => $entry->get_id(),
            '{submitted_date}' => $entry->get_submitted_at(get_option('date_format')),
            '{submitted_time}' => $entry->get_submitted_at(get_option('time_format')),
            '{ip_address}'     => $entry->get_ip_address(),
            '{site_name}'      => get_bloginfo('name'),
            '{site_url}'       => home_url(),
            '{admin_email}'    => get_option('admin_email'),
        );

        // Field-specific merge tags
        foreach ($fields as $field) {
            $field_id = isset($field['id']) ? $field['id'] : '';
            $value = isset($data[$field_id]) ? $data[$field_id] : '';
            $display_value = $registry->get_display_value($field, $value);

            $replacements['{field:' . $field_id . '}'] = strip_tags($display_value);
            $replacements['{field_label:' . $field_id . '}'] = isset($field['label']) ? $field['label'] : $field_id;
        }

        // All fields merge tag
        if (strpos($text, '{all_fields}') !== false) {
            $all_fields_html = '<table style="border-collapse:collapse;width:100%;">';
            foreach ($fields as $field) {
                $field_id = isset($field['id']) ? $field['id'] : '';
                $label = isset($field['label']) ? $field['label'] : $field_id;
                $value = isset($data[$field_id]) ? $data[$field_id] : '';
                $display_value = $registry->get_display_value($field, $value);

                if (empty($display_value) && $display_value !== '0') {
                    continue;
                }

                $all_fields_html .= '<tr>';
                $all_fields_html .= '<th style="padding:10px;text-align:left;border:1px solid #ddd;background:#f5f5f5;">' . esc_html($label) . '</th>';
                $all_fields_html .= '<td style="padding:10px;border:1px solid #ddd;">' . $display_value . '</td>';
                $all_fields_html .= '</tr>';
            }
            $all_fields_html .= '</table>';
            $replacements['{all_fields}'] = $all_fields_html;
        }

        return str_replace(array_keys($replacements), array_values($replacements), $text);
    }

    /**
     * Find email address in entry data
     *
     * @param ERFQ_Form  $form  Form object
     * @param ERFQ_Entry $entry Entry object
     *
     * @return string|null
     */
    protected function find_email_in_entry($form, $entry) {
        $fields = $form->get_fields();
        $data = $entry->get_data();

        // First, look for email type fields
        foreach ($fields as $field) {
            if (isset($field['type']) && $field['type'] === 'email') {
                $field_id = isset($field['id']) ? $field['id'] : '';
                if (isset($data[$field_id]) && is_email($data[$field_id])) {
                    return $data[$field_id];
                }
            }
        }

        // Then look for common email field names
        $email_field_names = array('email', 'contact_email', 'your_email', 'user_email');
        foreach ($email_field_names as $name) {
            if (isset($data[$name]) && is_email($data[$name])) {
                return $data[$name];
            }
        }

        // Finally, search all fields for valid email
        foreach ($data as $value) {
            if (is_string($value) && is_email($value)) {
                return $value;
            }
        }

        return null;
    }

    /**
     * Get email headers
     *
     * @param ERFQ_Form  $form  Form object
     * @param ERFQ_Entry $entry Entry object
     *
     * @return array
     */
    protected function get_email_headers($form, $entry) {
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
        );

        $from_name = get_bloginfo('name');
        $from_email = get_option('admin_email');
        $headers[] = 'From: ' . $from_name . ' <' . $from_email . '>';

        // Add Reply-To from submitter email
        $reply_to = $this->find_email_in_entry($form, $entry);
        if ($reply_to) {
            $headers[] = 'Reply-To: ' . $reply_to;
        }

        return $headers;
    }

    /**
     * Wrap content in HTML email template
     *
     * @param string $content Email content
     *
     * @return string
     */
    protected function wrap_html_email($content) {
        $html = '<!DOCTYPE html><html><head>';
        $html .= '<meta charset="UTF-8">';
        $html .= '<style>';
        $html .= 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; line-height: 1.6; color: #333; }';
        $html .= '</style>';
        $html .= '</head><body>';
        $html .= '<div style="max-width: 600px; margin: 0 auto; padding: 20px;">';
        $html .= $content;
        $html .= '</div>';
        $html .= '</body></html>';

        return $html;
    }

    /**
     * Send an email
     *
     * @param string       $to      Recipient email
     * @param string       $subject Email subject
     * @param string       $message Email body
     * @param array|string $headers Email headers
     *
     * @return bool
     */
    protected function send_email($to, $subject, $message, $headers = array()) {
        $sent = wp_mail($to, $subject, $message, $headers);

        if (!$sent) {
            error_log('ERFQ Email failed to: ' . $to . ' Subject: ' . $subject);
        }

        return $sent;
    }
}
