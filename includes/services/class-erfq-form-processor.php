<?php
/**
 * Form Processor service
 *
 * @package Event_RFQ_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ERFQ_Form_Processor
 *
 * Handles form submission processing
 */
class ERFQ_Form_Processor {

    /**
     * Process a form submission
     *
     * @param int   $form_id    Form ID
     * @param array $post_data  $_POST data
     * @param array $files_data $_FILES data
     *
     * @return array Result with success/error
     */
    public function process($form_id, $post_data, $files_data = array()) {
        // Get the form
        $form = ERFQ_Form::get_by_id($form_id);

        if (!$form) {
            return array(
                'success' => false,
                'message' => __('Form not found.', 'event-rfq-manager'),
            );
        }

        if (!$form->is_active()) {
            return array(
                'success' => false,
                'message' => __('This form is not currently accepting submissions.', 'event-rfq-manager'),
            );
        }

        // Security checks
        $security_check = $this->security_checks($form, $post_data);
        if (is_wp_error($security_check)) {
            return array(
                'success' => false,
                'message' => $security_check->get_error_message(),
            );
        }

        // Get submitted field data
        $field_data = isset($post_data['erfq_fields']) ? $post_data['erfq_fields'] : array();

        // Process conditional logic to determine visible fields
        $visible_fields = $this->get_visible_fields($form, $field_data);

        // Validate fields
        $validation = $this->validate_submission($form, $field_data, $visible_fields);
        if (is_wp_error($validation)) {
            return array(
                'success' => false,
                'message' => $validation->get_error_message(),
                'errors'  => $validation->get_error_data(),
            );
        }

        // Process file uploads
        $uploaded_files = array();
        if (!empty($files_data['erfq_files'])) {
            $file_result = $this->process_file_uploads($form, $files_data['erfq_files'], $visible_fields);
            if (is_wp_error($file_result)) {
                return array(
                    'success' => false,
                    'message' => $file_result->get_error_message(),
                );
            }
            $uploaded_files = $file_result;
        }

        // Sanitize data
        $sanitized_data = $this->sanitize_submission($form, $field_data, $visible_fields);

        // Create entry
        $entry = new ERFQ_Entry();
        $entry->set_form_id($form_id);
        $entry->set_data($sanitized_data);
        $entry->set_status('new');
        $entry->set_ip_address($this->get_client_ip());
        $entry->set_user_agent(isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '');
        $entry->set_submitted_at(current_time('mysql'));

        if (!empty($uploaded_files)) {
            $entry->set_files($uploaded_files);
        }

        $entry_id = $entry->save();

        if (is_wp_error($entry_id)) {
            // Clean up uploaded files on error
            $this->cleanup_uploaded_files($uploaded_files);

            return array(
                'success' => false,
                'message' => __('Failed to save submission. Please try again.', 'event-rfq-manager'),
            );
        }

        // Send notifications
        $this->send_notifications($form, $entry);

        // Get success response
        $success_message = $form->get_setting('success_message');
        if (empty($success_message)) {
            $global_settings = get_option('erfq_global_settings', array());
            $success_message = isset($global_settings['success_message'])
                ? $global_settings['success_message']
                : __('Thank you! Your submission has been received.', 'event-rfq-manager');
        }

        $response = array(
            'success'  => true,
            'message'  => $success_message,
            'entry_id' => $entry_id,
        );

        // Add redirect URL if set
        $redirect_url = $form->get_setting('redirect_url');
        if (!empty($redirect_url)) {
            $response['redirect'] = esc_url($redirect_url);
        }

        // Allow filtering the response
        return apply_filters('erfq_submission_response', $response, $entry, $form);
    }

    /**
     * Perform security checks
     *
     * @param ERFQ_Form $form      Form object
     * @param array     $post_data POST data
     *
     * @return true|WP_Error
     */
    protected function security_checks($form, $post_data) {
        // Verify nonce
        $nonce = isset($post_data['erfq_nonce']) ? $post_data['erfq_nonce'] : '';
        if (!wp_verify_nonce($nonce, 'erfq_submit_form_' . $form->get_id())) {
            return new WP_Error('invalid_nonce', __('Security check failed. Please refresh the page and try again.', 'event-rfq-manager'));
        }

        // Check honeypot
        if ($form->get_setting('honeypot_enabled', true)) {
            if (!ERFQ_Honeypot::validate($post_data)) {
                return new WP_Error('spam_detected', __('Submission blocked.', 'event-rfq-manager'));
            }
        }

        // Check rate limiting
        $rate_limiter = new ERFQ_Rate_Limiter();
        if (!$rate_limiter->check($form->get_id())) {
            return new WP_Error('rate_limited', __('Too many submissions. Please wait a while before trying again.', 'event-rfq-manager'));
        }

        // Check reCAPTCHA (if enabled)
        if ($form->get_setting('recaptcha_enabled', false)) {
            $recaptcha_token = isset($post_data['erfq_recaptcha_token']) ? $post_data['erfq_recaptcha_token'] : '';
            $recaptcha = new ERFQ_Recaptcha();
            if (!$recaptcha->verify($recaptcha_token)) {
                return new WP_Error('recaptcha_failed', __('reCAPTCHA verification failed.', 'event-rfq-manager'));
            }
        }

        return true;
    }

    /**
     * Get fields that should be visible based on conditional logic
     *
     * @param ERFQ_Form $form       Form object
     * @param array     $field_data Submitted data
     *
     * @return array Array of visible field IDs
     */
    protected function get_visible_fields($form, $field_data) {
        $all_fields = $form->get_fields();
        $visible = array();

        foreach ($all_fields as $field) {
            $field_id = isset($field['id']) ? $field['id'] : '';

            if (empty($field_id)) {
                continue;
            }

            // Check conditional logic
            if (!empty($field['conditional_logic'])) {
                if (!ERFQ_Conditional_Logic::should_show_field($field['conditional_logic'], $field_data)) {
                    continue;
                }
            }

            $visible[] = $field_id;
        }

        return $visible;
    }

    /**
     * Validate the submission
     *
     * @param ERFQ_Form $form           Form object
     * @param array     $field_data     Submitted data
     * @param array     $visible_fields Visible field IDs
     *
     * @return true|WP_Error
     */
    protected function validate_submission($form, $field_data, $visible_fields) {
        $validator = new ERFQ_Validator();
        $all_fields = $form->get_fields();
        $errors = array();

        foreach ($all_fields as $field) {
            $field_id = isset($field['id']) ? $field['id'] : '';
            $field_name = isset($field['name']) ? $field['name'] : $field_id;

            // Skip hidden fields
            if (!in_array($field_id, $visible_fields, true)) {
                continue;
            }

            $value = isset($field_data[$field_name]) ? $field_data[$field_name] : '';

            $validation = $validator->validate_field($field, $value);
            if (is_wp_error($validation)) {
                $errors[$field_id] = $validation->get_error_message();
            }
        }

        if (!empty($errors)) {
            $first_error = reset($errors);
            return new WP_Error('validation_failed', $first_error, $errors);
        }

        return true;
    }

    /**
     * Sanitize the submission data
     *
     * @param ERFQ_Form $form           Form object
     * @param array     $field_data     Submitted data
     * @param array     $visible_fields Visible field IDs
     *
     * @return array Sanitized data
     */
    protected function sanitize_submission($form, $field_data, $visible_fields) {
        $registry = ERFQ_Field_Registry::get_instance();
        $all_fields = $form->get_fields();
        $sanitized = array();

        foreach ($all_fields as $field) {
            $field_id = isset($field['id']) ? $field['id'] : '';
            $field_name = isset($field['name']) ? $field['name'] : $field_id;

            // Skip hidden fields but note they were hidden
            if (!in_array($field_id, $visible_fields, true)) {
                continue;
            }

            $value = isset($field_data[$field_name]) ? $field_data[$field_name] : '';
            $sanitized[$field_id] = $registry->sanitize_field($field, $value);
        }

        return $sanitized;
    }

    /**
     * Process file uploads
     *
     * @param ERFQ_Form $form           Form object
     * @param array     $files          $_FILES array
     * @param array     $visible_fields Visible field IDs
     *
     * @return array|WP_Error Uploaded file data or error
     */
    protected function process_file_uploads($form, $files, $visible_fields) {
        $registry = ERFQ_Field_Registry::get_instance();
        $all_fields = $form->get_fields();
        $uploaded = array();

        // Get upload directory
        $upload_dir = wp_upload_dir();
        $erfq_upload_dir = $upload_dir['basedir'] . '/erfq-uploads/' . date('Y/m');

        if (!file_exists($erfq_upload_dir)) {
            wp_mkdir_p($erfq_upload_dir);
        }

        foreach ($all_fields as $field) {
            if ($field['type'] !== 'file') {
                continue;
            }

            $field_id = isset($field['id']) ? $field['id'] : '';
            $field_name = isset($field['name']) ? $field['name'] : $field_id;

            if (!in_array($field_id, $visible_fields, true)) {
                continue;
            }

            if (!isset($files[$field_name]) || empty($files[$field_name]['name'])) {
                continue;
            }

            $file = $files[$field_name];
            $file_field = $registry->get('file');

            // Validate file
            $validation = $file_field->validate_upload($file, $field);
            if (is_wp_error($validation)) {
                return $validation;
            }

            // Handle multiple files
            if (is_array($file['name'])) {
                $uploaded[$field_id] = array();

                for ($i = 0; $i < count($file['name']); $i++) {
                    if (empty($file['name'][$i])) {
                        continue;
                    }

                    $result = $this->move_uploaded_file(
                        $file['tmp_name'][$i],
                        $file['name'][$i],
                        $erfq_upload_dir,
                        $upload_dir['baseurl']
                    );

                    if ($result) {
                        $uploaded[$field_id][] = $result;
                    }
                }
            } else {
                $result = $this->move_uploaded_file(
                    $file['tmp_name'],
                    $file['name'],
                    $erfq_upload_dir,
                    $upload_dir['baseurl']
                );

                if ($result) {
                    $uploaded[$field_id] = array($result);
                }
            }
        }

        return $uploaded;
    }

    /**
     * Move an uploaded file to permanent location
     *
     * @param string $tmp_name   Temporary file path
     * @param string $name       Original file name
     * @param string $upload_dir Upload directory path
     * @param string $base_url   Upload base URL
     *
     * @return array|false File data or false on failure
     */
    protected function move_uploaded_file($tmp_name, $name, $upload_dir, $base_url) {
        // Generate unique filename
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $filename = wp_unique_filename($upload_dir, sanitize_file_name($name));
        $filepath = $upload_dir . '/' . $filename;

        if (move_uploaded_file($tmp_name, $filepath)) {
            return array(
                'name' => $name,
                'path' => $filepath,
                'url'  => str_replace(ABSPATH, home_url('/'), $filepath),
                'size' => filesize($filepath),
                'type' => $extension,
            );
        }

        return false;
    }

    /**
     * Clean up uploaded files on error
     *
     * @param array $files Uploaded file data
     */
    protected function cleanup_uploaded_files($files) {
        foreach ($files as $field_files) {
            foreach ((array) $field_files as $file) {
                if (isset($file['path']) && file_exists($file['path'])) {
                    wp_delete_file($file['path']);
                }
            }
        }
    }

    /**
     * Send notifications
     *
     * @param ERFQ_Form  $form  Form object
     * @param ERFQ_Entry $entry Entry object
     */
    protected function send_notifications($form, $entry) {
        $email_service = new ERFQ_Email_Service();

        // Admin notification
        $global_settings = get_option('erfq_global_settings', array());
        $enable_admin_notifications = isset($global_settings['enable_admin_notifications'])
            ? $global_settings['enable_admin_notifications']
            : true;

        if ($enable_admin_notifications) {
            $email_service->send_admin_notification($form, $entry);
        }

        // User confirmation
        if ($form->get_setting('confirmation_email', false)) {
            $email_service->send_user_confirmation($form, $entry);
        }
    }

    /**
     * Get client IP address
     *
     * @return string
     */
    protected function get_client_ip() {
        $ip_keys = array(
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR',
        );

        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                // Handle comma-separated IPs (from proxies)
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }
}
