<?php
/**
 * File upload field type
 *
 * @package Event_RFQ_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ERFQ_Field_File
 */
class ERFQ_Field_File extends ERFQ_Field_Type_Abstract {

    protected $type = 'file';
    protected $name = 'File Upload';
    protected $icon = 'dashicons-upload';
    protected $category = 'advanced';
    protected $description = 'File upload field';

    protected function get_type_settings() {
        return array(
            'allowed_types' => array(
                'type'        => 'text',
                'label'       => __('Allowed File Types', 'event-rfq-manager'),
                'default'     => 'pdf,doc,docx,jpg,jpeg,png',
                'description' => __('Comma-separated list of extensions', 'event-rfq-manager'),
            ),
            'max_size' => array(
                'type'        => 'number',
                'label'       => __('Maximum File Size (MB)', 'event-rfq-manager'),
                'default'     => 5,
                'min'         => 1,
                'max'         => 100,
            ),
            'multiple' => array(
                'type'    => 'checkbox',
                'label'   => __('Allow Multiple Files', 'event-rfq-manager'),
                'default' => false,
            ),
            'max_files' => array(
                'type'    => 'number',
                'label'   => __('Maximum Number of Files', 'event-rfq-manager'),
                'default' => 5,
                'min'     => 1,
                'max'     => 20,
            ),
        );
    }

    public function render($field_config, $value = null) {
        $field_id = $field_config['id'] ?? '';
        $name = $field_config['name'] ?? $field_id;
        $required = !empty($field_config['required']);
        $multiple = !empty($field_config['multiple']);
        $allowed_types = isset($field_config['allowed_types']) ? $field_config['allowed_types'] : 'pdf,doc,docx,jpg,jpeg,png';
        $max_size = isset($field_config['max_size']) ? $field_config['max_size'] : 5;

        // Convert extensions to accept attribute format
        $types = array_map('trim', explode(',', $allowed_types));
        $accept = implode(',', array_map(function($ext) {
            return '.' . ltrim($ext, '.');
        }, $types));

        $input = '<div class="erfq-file-upload-wrapper" data-max-size="' . esc_attr($max_size) . '" data-allowed-types="' . esc_attr($allowed_types) . '">';

        $input .= '<input type="file" ';
        $input .= 'id="erfq-field-' . esc_attr($field_id) . '" ';
        $input .= 'name="erfq_files[' . esc_attr($name) . ']' . ($multiple ? '[]' : '') . '" ';
        $input .= 'class="erfq-field erfq-field-file" ';
        $input .= 'accept="' . esc_attr($accept) . '" ';

        if ($multiple) {
            $input .= 'multiple ';
        }

        if ($required) {
            $input .= 'required aria-required="true" ';
        }

        $input .= '>';

        // File info
        $input .= '<p class="erfq-file-info">';
        $input .= sprintf(
            esc_html__('Allowed types: %s. Max size: %sMB', 'event-rfq-manager'),
            esc_html($allowed_types),
            esc_html($max_size)
        );
        $input .= '</p>';

        // Preview area
        $input .= '<div class="erfq-file-preview"></div>';

        $input .= '</div>';

        return $this->wrap_field($field_config, $input);
    }

    protected function validate_type($field_config, $value) {
        // File validation is handled during upload in the processor
        return true;
    }

    /**
     * Validate uploaded file
     *
     * @param array $file         $_FILES array for this field
     * @param array $field_config Field configuration
     *
     * @return true|WP_Error
     */
    public function validate_upload($file, $field_config) {
        $label = $field_config['label'] ?? __('File', 'event-rfq-manager');
        $allowed_types = isset($field_config['allowed_types']) ? $field_config['allowed_types'] : 'pdf,doc,docx,jpg,jpeg,png';
        $max_size = isset($field_config['max_size']) ? floatval($field_config['max_size']) : 5;
        $max_size_bytes = $max_size * 1024 * 1024;

        // Handle multiple files
        if (is_array($file['name'])) {
            $max_files = isset($field_config['max_files']) ? intval($field_config['max_files']) : 5;
            $file_count = count(array_filter($file['name']));

            if ($file_count > $max_files) {
                return new WP_Error(
                    'too_many_files',
                    sprintf(__('%s: Maximum %d files allowed.', 'event-rfq-manager'), $label, $max_files)
                );
            }

            for ($i = 0; $i < count($file['name']); $i++) {
                if (empty($file['name'][$i])) {
                    continue;
                }

                $validation = $this->validate_single_file(
                    $file['name'][$i],
                    $file['size'][$i],
                    $file['error'][$i],
                    $allowed_types,
                    $max_size_bytes,
                    $label
                );

                if (is_wp_error($validation)) {
                    return $validation;
                }
            }
        } else {
            if (empty($file['name'])) {
                return true;
            }

            return $this->validate_single_file(
                $file['name'],
                $file['size'],
                $file['error'],
                $allowed_types,
                $max_size_bytes,
                $label
            );
        }

        return true;
    }

    /**
     * Validate a single file
     *
     * @param string $name           File name
     * @param int    $size           File size in bytes
     * @param int    $error          Upload error code
     * @param string $allowed_types  Comma-separated allowed extensions
     * @param int    $max_size_bytes Maximum size in bytes
     * @param string $label          Field label
     *
     * @return true|WP_Error
     */
    protected function validate_single_file($name, $size, $error, $allowed_types, $max_size_bytes, $label) {
        // Check for upload errors
        if ($error !== UPLOAD_ERR_OK) {
            $error_messages = array(
                UPLOAD_ERR_INI_SIZE   => __('File exceeds server upload limit.', 'event-rfq-manager'),
                UPLOAD_ERR_FORM_SIZE  => __('File exceeds form upload limit.', 'event-rfq-manager'),
                UPLOAD_ERR_PARTIAL    => __('File was only partially uploaded.', 'event-rfq-manager'),
                UPLOAD_ERR_NO_FILE    => __('No file was uploaded.', 'event-rfq-manager'),
                UPLOAD_ERR_NO_TMP_DIR => __('Server configuration error.', 'event-rfq-manager'),
                UPLOAD_ERR_CANT_WRITE => __('Failed to write file.', 'event-rfq-manager'),
                UPLOAD_ERR_EXTENSION  => __('File upload blocked.', 'event-rfq-manager'),
            );

            $message = isset($error_messages[$error]) ? $error_messages[$error] : __('Unknown upload error.', 'event-rfq-manager');
            return new WP_Error('upload_error', $label . ': ' . $message);
        }

        // Check file size
        if ($size > $max_size_bytes) {
            return new WP_Error(
                'file_too_large',
                sprintf(__('%s: File exceeds maximum size of %sMB.', 'event-rfq-manager'), $label, round($max_size_bytes / 1024 / 1024))
            );
        }

        // Check file extension
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $allowed_array = array_map('trim', array_map('strtolower', explode(',', $allowed_types)));

        if (!in_array($extension, $allowed_array, true)) {
            return new WP_Error(
                'invalid_file_type',
                sprintf(__('%s: File type .%s is not allowed.', 'event-rfq-manager'), $label, $extension)
            );
        }

        // Security: Check for PHP files disguised with other extensions
        $dangerous_extensions = array('php', 'php3', 'php4', 'php5', 'phtml', 'exe', 'sh', 'bat');
        if (in_array($extension, $dangerous_extensions, true)) {
            return new WP_Error(
                'dangerous_file_type',
                sprintf(__('%s: This file type is not allowed for security reasons.', 'event-rfq-manager'), $label)
            );
        }

        return true;
    }

    public function sanitize($field_config, $value) {
        // File paths are sanitized during upload
        return $value;
    }

    public function get_display_value($field_config, $value) {
        if (empty($value)) {
            return '';
        }

        if (is_array($value)) {
            $links = array();
            foreach ($value as $file) {
                if (isset($file['url']) && isset($file['name'])) {
                    $links[] = '<a href="' . esc_url($file['url']) . '" target="_blank">' . esc_html($file['name']) . '</a>';
                }
            }
            return implode('<br>', $links);
        }

        return is_string($value) ? esc_html($value) : '';
    }
}
