<?php
/**
 * Conditional Logic engine
 *
 * @package Event_RFQ_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ERFQ_Conditional_Logic
 *
 * Evaluates conditional logic rules for form fields
 */
class ERFQ_Conditional_Logic {

    /**
     * Available operators
     *
     * @var array
     */
    public static $operators = array(
        'is'           => 'is',
        'is_not'       => 'is not',
        'contains'     => 'contains',
        'not_contains' => 'does not contain',
        'starts_with'  => 'starts with',
        'ends_with'    => 'ends with',
        'is_empty'     => 'is empty',
        'is_not_empty' => 'is not empty',
        'greater_than' => 'greater than',
        'less_than'    => 'less than',
        'in'           => 'is one of',
        'not_in'       => 'is not one of',
    );

    /**
     * Available actions
     *
     * @var array
     */
    public static $actions = array(
        'show' => 'Show',
        'hide' => 'Hide',
    );

    /**
     * Logic types
     *
     * @var array
     */
    public static $logic_types = array(
        'all' => 'All conditions must be met',
        'any' => 'Any condition must be met',
    );

    /**
     * Evaluate conditional logic rules against submitted data
     *
     * @param array $rules Rules configuration
     * @param array $data  Submitted form data
     *
     * @return bool Whether the conditions are met
     */
    public static function evaluate($rules, $data) {
        if (empty($rules) || !isset($rules['conditions'])) {
            return true;
        }

        $conditions = $rules['conditions'];
        $logic_type = isset($rules['logic']) ? $rules['logic'] : 'all';

        if (empty($conditions)) {
            return true;
        }

        $results = array();

        foreach ($conditions as $condition) {
            $results[] = self::evaluate_condition($condition, $data);
        }

        if ($logic_type === 'all') {
            return !in_array(false, $results, true);
        } else {
            return in_array(true, $results, true);
        }
    }

    /**
     * Evaluate a single condition
     *
     * @param array $condition Condition configuration
     * @param array $data      Submitted form data
     *
     * @return bool
     */
    protected static function evaluate_condition($condition, $data) {
        if (!isset($condition['field']) || !isset($condition['operator'])) {
            return true;
        }

        $field_id = $condition['field'];
        $operator = $condition['operator'];
        $compare_value = isset($condition['value']) ? $condition['value'] : '';

        // Get the field value from submitted data
        $field_value = isset($data[$field_id]) ? $data[$field_id] : '';

        // Handle arrays (checkboxes, multi-select)
        if (is_array($field_value)) {
            $field_value = array_map('strval', $field_value);
        } else {
            $field_value = strval($field_value);
        }

        return self::compare($field_value, $operator, $compare_value);
    }

    /**
     * Compare values using the specified operator
     *
     * @param mixed  $value         The field value
     * @param string $operator      The comparison operator
     * @param mixed  $compare_value The value to compare against
     *
     * @return bool
     */
    protected static function compare($value, $operator, $compare_value) {
        // Handle array values
        if (is_array($value)) {
            switch ($operator) {
                case 'is':
                case 'in':
                    $compare_array = is_array($compare_value) ? $compare_value : array($compare_value);
                    return !empty(array_intersect($value, $compare_array));

                case 'is_not':
                case 'not_in':
                    $compare_array = is_array($compare_value) ? $compare_value : array($compare_value);
                    return empty(array_intersect($value, $compare_array));

                case 'contains':
                    return in_array($compare_value, $value, true);

                case 'not_contains':
                    return !in_array($compare_value, $value, true);

                case 'is_empty':
                    return empty($value);

                case 'is_not_empty':
                    return !empty($value);

                default:
                    return false;
            }
        }

        // Handle scalar values
        switch ($operator) {
            case 'is':
                return strtolower($value) === strtolower($compare_value);

            case 'is_not':
                return strtolower($value) !== strtolower($compare_value);

            case 'contains':
                return stripos($value, $compare_value) !== false;

            case 'not_contains':
                return stripos($value, $compare_value) === false;

            case 'starts_with':
                return stripos($value, $compare_value) === 0;

            case 'ends_with':
                $length = strlen($compare_value);
                if ($length === 0) {
                    return true;
                }
                return strtolower(substr($value, -$length)) === strtolower($compare_value);

            case 'is_empty':
                return $value === '' || $value === null;

            case 'is_not_empty':
                return $value !== '' && $value !== null;

            case 'greater_than':
                return is_numeric($value) && is_numeric($compare_value) && floatval($value) > floatval($compare_value);

            case 'less_than':
                return is_numeric($value) && is_numeric($compare_value) && floatval($value) < floatval($compare_value);

            case 'in':
                $compare_array = is_array($compare_value) ? $compare_value : explode(',', $compare_value);
                $compare_array = array_map('trim', $compare_array);
                $compare_array = array_map('strtolower', $compare_array);
                return in_array(strtolower($value), $compare_array, true);

            case 'not_in':
                $compare_array = is_array($compare_value) ? $compare_value : explode(',', $compare_value);
                $compare_array = array_map('trim', $compare_array);
                $compare_array = array_map('strtolower', $compare_array);
                return !in_array(strtolower($value), $compare_array, true);

            default:
                return true;
        }
    }

    /**
     * Check if a field should be visible based on conditional logic
     *
     * @param array $rules Field's conditional logic rules
     * @param array $data  Submitted form data
     *
     * @return bool
     */
    public static function should_show_field($rules, $data) {
        if (empty($rules)) {
            return true;
        }

        $action = isset($rules['action']) ? $rules['action'] : 'show';
        $conditions_met = self::evaluate($rules, $data);

        if ($action === 'show') {
            return $conditions_met;
        } else {
            return !$conditions_met;
        }
    }

    /**
     * Generate JavaScript data for frontend conditional logic
     *
     * @param array $fields Form fields with conditional logic
     *
     * @return array
     */
    public static function get_js_config($fields) {
        $config = array();

        foreach ($fields as $field) {
            if (!isset($field['conditional_logic']) || empty($field['conditional_logic'])) {
                continue;
            }

            $config[$field['id']] = array(
                'action'     => isset($field['conditional_logic']['action']) ? $field['conditional_logic']['action'] : 'show',
                'logic'      => isset($field['conditional_logic']['logic']) ? $field['conditional_logic']['logic'] : 'all',
                'conditions' => isset($field['conditional_logic']['conditions']) ? $field['conditional_logic']['conditions'] : array(),
            );
        }

        return $config;
    }

    /**
     * Validate conditional logic configuration
     *
     * @param array $rules Rules to validate
     *
     * @return bool|WP_Error True if valid, WP_Error if invalid
     */
    public static function validate_rules($rules) {
        if (empty($rules)) {
            return true;
        }

        if (!isset($rules['conditions']) || !is_array($rules['conditions'])) {
            return new WP_Error('invalid_conditions', __('Conditions must be an array.', 'event-rfq-manager'));
        }

        if (isset($rules['action']) && !array_key_exists($rules['action'], self::$actions)) {
            return new WP_Error('invalid_action', __('Invalid action specified.', 'event-rfq-manager'));
        }

        if (isset($rules['logic']) && !array_key_exists($rules['logic'], self::$logic_types)) {
            return new WP_Error('invalid_logic', __('Invalid logic type specified.', 'event-rfq-manager'));
        }

        foreach ($rules['conditions'] as $condition) {
            if (!isset($condition['field'])) {
                return new WP_Error('missing_field', __('Each condition must specify a field.', 'event-rfq-manager'));
            }

            if (!isset($condition['operator'])) {
                return new WP_Error('missing_operator', __('Each condition must specify an operator.', 'event-rfq-manager'));
            }

            if (!array_key_exists($condition['operator'], self::$operators)) {
                return new WP_Error('invalid_operator', __('Invalid operator specified.', 'event-rfq-manager'));
            }
        }

        return true;
    }

    /**
     * Get operators suitable for a field type
     *
     * @param string $field_type Field type
     *
     * @return array
     */
    public static function get_operators_for_field_type($field_type) {
        $text_operators = array('is', 'is_not', 'contains', 'not_contains', 'starts_with', 'ends_with', 'is_empty', 'is_not_empty');
        $number_operators = array('is', 'is_not', 'greater_than', 'less_than', 'is_empty', 'is_not_empty');
        $choice_operators = array('is', 'is_not', 'in', 'not_in', 'is_empty', 'is_not_empty');

        switch ($field_type) {
            case 'number':
                $operators = $number_operators;
                break;

            case 'select':
            case 'radio':
            case 'checkbox':
                $operators = $choice_operators;
                break;

            case 'text':
            case 'email':
            case 'phone':
            case 'textarea':
            default:
                $operators = $text_operators;
                break;
        }

        $result = array();
        foreach ($operators as $key) {
            if (isset(self::$operators[$key])) {
                $result[$key] = self::$operators[$key];
            }
        }

        return $result;
    }
}
