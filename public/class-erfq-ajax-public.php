<?php
/**
 * Public AJAX Handlers
 *
 * @package Event_RFQ_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ERFQ_Ajax_Public
 *
 * Handles public-facing AJAX requests
 */
class ERFQ_Ajax_Public {

    /**
     * Submit form
     */
    public function submit_form() {
        // Verify nonce
        check_ajax_referer('erfq_public_nonce', 'nonce');

        $form_id = isset($_POST['form_id']) ? absint($_POST['form_id']) : 0;

        if (!$form_id) {
            wp_send_json_error(array('message' => __('Invalid form.', 'event-rfq-manager')));
        }

        $form = ERFQ_Form::get_by_id($form_id);

        if (!$form) {
            wp_send_json_error(array('message' => __('Form not found.', 'event-rfq-manager')));
        }

        // Check form status
        if ($form->get_status() !== 'publish') {
            wp_send_json_error(array('message' => __('This form is not accepting submissions.', 'event-rfq-manager')));
        }

        $settings = $form->get_settings();

        // Security checks
        $security_result = $this->run_security_checks($form);
        if (is_wp_error($security_result)) {
            wp_send_json_error(array('message' => $security_result->get_error_message()));
        }

        // Process form data
        $processor = new ERFQ_Form_Processor($form);
        $result = $processor->process($_POST, $_FILES);

        if (is_wp_error($result)) {
            wp_send_json_error(array(
                'message' => $result->get_error_message(),
                'errors'  => $result->get_error_data(),
            ));
        }

        // Get success message
        $success_message = !empty($settings['success_message'])
            ? $settings['success_message']
            : __('Thank you for your submission!', 'event-rfq-manager');

        $response = array(
            'message'  => $success_message,
            'entry_id' => $result,
        );

        // Add redirect URL if set
        if (!empty($settings['redirect_url'])) {
            $response['redirect'] = esc_url($settings['redirect_url']);
        }

        wp_send_json_success($response);
    }

    /**
     * Run security checks
     *
     * @param ERFQ_Form $form Form object
     *
     * @return true|WP_Error
     */
    protected function run_security_checks($form) {
        $settings = $form->get_settings();

        // Honeypot check
        if (!empty($settings['honeypot_enabled']) || get_option('erfq_honeypot_enabled', '1') === '1') {
            $honeypot = new ERFQ_Honeypot();
            if (!$honeypot->validate($_POST)) {
                return new WP_Error('spam_detected', __('Spam submission detected.', 'event-rfq-manager'));
            }
        }

        // Rate limiting check
        if (get_option('erfq_rate_limit_enabled', '1') === '1') {
            $rate_limiter = new ERFQ_Rate_Limiter();
            if (!$rate_limiter->check()) {
                return new WP_Error('rate_limited', __('Too many submissions. Please try again later.', 'event-rfq-manager'));
            }
        }

        // reCAPTCHA check
        if (!empty($settings['recaptcha_enabled'])) {
            $recaptcha = new ERFQ_Recaptcha();
            $token = isset($_POST['recaptcha_token']) ? sanitize_text_field($_POST['recaptcha_token']) : '';

            if (!$recaptcha->verify($token)) {
                return new WP_Error('recaptcha_failed', __('reCAPTCHA verification failed.', 'event-rfq-manager'));
            }
        }

        return true;
    }

    /**
     * Upload file via AJAX
     */
    public function upload_file() {
        check_ajax_referer('erfq_public_nonce', 'nonce');

        if (empty($_FILES['file'])) {
            wp_send_json_error(array('message' => __('No file uploaded.', 'event-rfq-manager')));
        }

        $form_id = isset($_POST['form_id']) ? absint($_POST['form_id']) : 0;
        $field_id = isset($_POST['field_id']) ? sanitize_key($_POST['field_id']) : '';

        if (!$form_id || !$field_id) {
            wp_send_json_error(array('message' => __('Invalid request.', 'event-rfq-manager')));
        }

        // Validate file
        $max_size = get_option('erfq_file_upload_max_size', 5) * 1024 * 1024;
        $allowed_types = get_option('erfq_file_upload_types', 'pdf,doc,docx,jpg,jpeg,png,gif');
        $allowed_types = array_map('trim', explode(',', $allowed_types));

        $file = $_FILES['file'];

        // Check file size
        if ($file['size'] > $max_size) {
            wp_send_json_error(array(
                'message' => sprintf(
                    __('File is too large. Maximum size is %s.', 'event-rfq-manager'),
                    size_format($max_size)
                ),
            ));
        }

        // Check file type
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!empty($allowed_types) && !in_array($file_ext, $allowed_types, true)) {
            wp_send_json_error(array(
                'message' => sprintf(
                    __('File type not allowed. Allowed types: %s', 'event-rfq-manager'),
                    implode(', ', $allowed_types)
                ),
            ));
        }

        // Upload file
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        // Set upload directory
        add_filter('upload_dir', array($this, 'set_upload_dir'));

        $attachment_id = media_handle_upload('file', 0);

        remove_filter('upload_dir', array($this, 'set_upload_dir'));

        if (is_wp_error($attachment_id)) {
            wp_send_json_error(array('message' => $attachment_id->get_error_message()));
        }

        // Store as temporary until form is submitted
        update_post_meta($attachment_id, '_erfq_temp_upload', 1);
        update_post_meta($attachment_id, '_erfq_form_id', $form_id);
        update_post_meta($attachment_id, '_erfq_field_id', $field_id);

        wp_send_json_success(array(
            'attachment_id' => $attachment_id,
            'filename'      => basename(get_attached_file($attachment_id)),
            'url'           => wp_get_attachment_url($attachment_id),
        ));
    }

    /**
     * Remove uploaded file
     */
    public function remove_file() {
        check_ajax_referer('erfq_public_nonce', 'nonce');

        $attachment_id = isset($_POST['attachment_id']) ? absint($_POST['attachment_id']) : 0;

        if (!$attachment_id) {
            wp_send_json_error(array('message' => __('Invalid request.', 'event-rfq-manager')));
        }

        // Only allow removing temporary uploads
        $is_temp = get_post_meta($attachment_id, '_erfq_temp_upload', true);

        if (!$is_temp) {
            wp_send_json_error(array('message' => __('Cannot remove this file.', 'event-rfq-manager')));
        }

        wp_delete_attachment($attachment_id, true);

        wp_send_json_success();
    }

    /**
     * Set custom upload directory for form files
     *
     * @param array $uploads Upload directory info
     *
     * @return array
     */
    public function set_upload_dir($uploads) {
        $uploads['subdir'] = '/erfq-uploads' . $uploads['subdir'];
        $uploads['path'] = $uploads['basedir'] . $uploads['subdir'];
        $uploads['url'] = $uploads['baseurl'] . $uploads['subdir'];

        return $uploads;
    }
}
