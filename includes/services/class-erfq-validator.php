<?php
/**
 * Validator service
 *
 * @package Event_RFQ_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ERFQ_Validator
 *
 * Handles field validation
 */
class ERFQ_Validator {

    /**
     * Validate a field value
     *
     * @param array $field_config Field configuration
     * @param mixed $value        Value to validate
     *
     * @return true|WP_Error
     */
    public function validate_field($field_config, $value) {
        $registry = ERFQ_Field_Registry::get_instance();
        $field_type = isset($field_config['type']) ? $field_config['type'] : 'text';

        // Get the field type handler
        $handler = $registry->get($field_type);
        if (!$handler) {
            return true; // Unknown field types pass validation
        }

        // Run type validation
        $validation = $handler->validate($field_config, $value);
        if (is_wp_error($validation)) {
            return $validation;
        }

        // Run custom validation rules
        if (isset($field_config['validation']) && is_array($field_config['validation'])) {
            $custom_validation = $this->run_custom_validation($field_config, $value);
            if (is_wp_error($custom_validation)) {
                return $custom_validation;
            }
        }

        return true;
    }

    /**
     * Run custom validation rules
     *
     * @param array $field_config Field configuration
     * @param mixed $value        Value to validate
     *
     * @return true|WP_Error
     */
    protected function run_custom_validation($field_config, $value) {
        $rules = $field_config['validation'];
        $label = isset($field_config['label']) ? $field_config['label'] : __('This field', 'event-rfq-manager');

        foreach ($rules as $rule => $rule_config) {
            $rule_value = is_array($rule_config) && isset($rule_config['value']) ? $rule_config['value'] : $rule_config;
            $message = is_array($rule_config) && isset($rule_config['message']) ? $rule_config['message'] : '';

            $result = $this->apply_rule($rule, $rule_value, $value, $label, $message);
            if (is_wp_error($result)) {
                return $result;
            }
        }

        return true;
    }

    /**
     * Apply a single validation rule
     *
     * @param string $rule       Rule name
     * @param mixed  $rule_value Rule configuration value
     * @param mixed  $value      Field value
     * @param string $label      Field label
     * @param string $message    Custom error message
     *
     * @return true|WP_Error
     */
    protected function apply_rule($rule, $rule_value, $value, $label, $message = '') {
        // Skip validation for empty values (unless required)
        if (($value === '' || $value === null) && $rule !== 'required') {
            return true;
        }

        switch ($rule) {
            case 'required':
                if ($rule_value && ($value === '' || $value === null || (is_array($value) && empty($value)))) {
                    return new WP_Error('required', $message ?: sprintf(__('%s is required.', 'event-rfq-manager'), $label));
                }
                break;

            case 'min_length':
                if (strlen($value) < intval($rule_value)) {
                    return new WP_Error('min_length', $message ?: sprintf(__('%s must be at least %d characters.', 'event-rfq-manager'), $label, $rule_value));
                }
                break;

            case 'max_length':
                if (strlen($value) > intval($rule_value)) {
                    return new WP_Error('max_length', $message ?: sprintf(__('%s must be no more than %d characters.', 'event-rfq-manager'), $label, $rule_value));
                }
                break;

            case 'min':
                if (is_numeric($value) && floatval($value) < floatval($rule_value)) {
                    return new WP_Error('min', $message ?: sprintf(__('%s must be at least %s.', 'event-rfq-manager'), $label, $rule_value));
                }
                break;

            case 'max':
                if (is_numeric($value) && floatval($value) > floatval($rule_value)) {
                    return new WP_Error('max', $message ?: sprintf(__('%s must be no more than %s.', 'event-rfq-manager'), $label, $rule_value));
                }
                break;

            case 'pattern':
            case 'regex':
                if (!preg_match('/' . $rule_value . '/', $value)) {
                    return new WP_Error('pattern', $message ?: sprintf(__('%s format is invalid.', 'event-rfq-manager'), $label));
                }
                break;

            case 'email':
                if ($rule_value && !is_email($value)) {
                    return new WP_Error('email', $message ?: sprintf(__('%s must be a valid email address.', 'event-rfq-manager'), $label));
                }
                break;

            case 'url':
                if ($rule_value && !filter_var($value, FILTER_VALIDATE_URL)) {
                    return new WP_Error('url', $message ?: sprintf(__('%s must be a valid URL.', 'event-rfq-manager'), $label));
                }
                break;

            case 'numeric':
                if ($rule_value && !is_numeric($value)) {
                    return new WP_Error('numeric', $message ?: sprintf(__('%s must be a number.', 'event-rfq-manager'), $label));
                }
                break;

            case 'alpha':
                if ($rule_value && !ctype_alpha(str_replace(' ', '', $value))) {
                    return new WP_Error('alpha', $message ?: sprintf(__('%s must contain only letters.', 'event-rfq-manager'), $label));
                }
                break;

            case 'alphanumeric':
                if ($rule_value && !ctype_alnum(str_replace(' ', '', $value))) {
                    return new WP_Error('alphanumeric', $message ?: sprintf(__('%s must contain only letters and numbers.', 'event-rfq-manager'), $label));
                }
                break;

            case 'in':
                $allowed = is_array($rule_value) ? $rule_value : explode(',', $rule_value);
                if (!in_array($value, array_map('trim', $allowed), true)) {
                    return new WP_Error('in', $message ?: sprintf(__('%s contains an invalid value.', 'event-rfq-manager'), $label));
                }
                break;

            case 'not_in':
                $disallowed = is_array($rule_value) ? $rule_value : explode(',', $rule_value);
                if (in_array($value, array_map('trim', $disallowed), true)) {
                    return new WP_Error('not_in', $message ?: sprintf(__('%s contains a disallowed value.', 'event-rfq-manager'), $label));
                }
                break;

            case 'date_after':
                if (strtotime($value) <= strtotime($rule_value)) {
                    return new WP_Error('date_after', $message ?: sprintf(__('%s must be after %s.', 'event-rfq-manager'), $label, $rule_value));
                }
                break;

            case 'date_before':
                if (strtotime($value) >= strtotime($rule_value)) {
                    return new WP_Error('date_before', $message ?: sprintf(__('%s must be before %s.', 'event-rfq-manager'), $label, $rule_value));
                }
                break;

            case 'custom':
                // Custom callback validation
                if (is_callable($rule_value)) {
                    $result = call_user_func($rule_value, $value, $label);
                    if (is_wp_error($result)) {
                        return $result;
                    }
                    if ($result === false) {
                        return new WP_Error('custom', $message ?: sprintf(__('%s is invalid.', 'event-rfq-manager'), $label));
                    }
                }
                break;
        }

        return true;
    }

    /**
     * Validate multiple fields at once
     *
     * @param array $fields     Field configurations
     * @param array $values     Field values keyed by field ID
     *
     * @return array Array of field_id => error_message for failed validations
     */
    public function validate_fields($fields, $values) {
        $errors = array();

        foreach ($fields as $field) {
            $field_id = isset($field['id']) ? $field['id'] : '';
            $field_name = isset($field['name']) ? $field['name'] : $field_id;
            $value = isset($values[$field_name]) ? $values[$field_name] : '';

            $result = $this->validate_field($field, $value);
            if (is_wp_error($result)) {
                $errors[$field_id] = $result->get_error_message();
            }
        }

        return $errors;
    }

    /**
     * Get validation rules for JavaScript
     *
     * @param array $fields Field configurations
     *
     * @return array
     */
    public function get_js_validation_rules($fields) {
        $rules = array();

        foreach ($fields as $field) {
            $field_id = isset($field['id']) ? $field['id'] : '';
            $field_rules = array();

            if (!empty($field['required'])) {
                $field_rules['required'] = true;
            }

            if (isset($field['validation']) && is_array($field['validation'])) {
                foreach ($field['validation'] as $rule => $config) {
                    $value = is_array($config) && isset($config['value']) ? $config['value'] : $config;
                    $message = is_array($config) && isset($config['message']) ? $config['message'] : '';

                    $field_rules[$rule] = array(
                        'value'   => $value,
                        'message' => $message,
                    );
                }
            }

            // Add type-specific rules
            switch ($field['type'] ?? 'text') {
                case 'email':
                    $field_rules['email'] = true;
                    break;
                case 'number':
                    $field_rules['numeric'] = true;
                    if (isset($field['min'])) {
                        $field_rules['min'] = $field['min'];
                    }
                    if (isset($field['max'])) {
                        $field_rules['max'] = $field['max'];
                    }
                    break;
            }

            if (!empty($field_rules)) {
                $rules[$field_id] = $field_rules;
            }
        }

        return $rules;
    }
}
