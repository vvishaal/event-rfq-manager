<?php
/**
 * Hidden field type
 *
 * @package Event_RFQ_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ERFQ_Field_Hidden
 */
class ERFQ_Field_Hidden extends ERFQ_Field_Type_Abstract {

    protected $type = 'hidden';
    protected $name = 'Hidden';
    protected $icon = 'dashicons-hidden';
    protected $category = 'advanced';
    protected $description = 'Hidden field for passing data';

    protected function get_type_settings() {
        return array(
            'dynamic_value' => array(
                'type'    => 'select',
                'label'   => __('Dynamic Value', 'event-rfq-manager'),
                'default' => 'none',
                'options' => array(
                    'none'        => __('None (use default value)', 'event-rfq-manager'),
                    'current_url' => __('Current Page URL', 'event-rfq-manager'),
                    'referrer'    => __('Referrer URL', 'event-rfq-manager'),
                    'user_id'     => __('User ID', 'event-rfq-manager'),
                    'user_email'  => __('User Email', 'event-rfq-manager'),
                    'timestamp'   => __('Timestamp', 'event-rfq-manager'),
                    'post_id'     => __('Current Post ID', 'event-rfq-manager'),
                    'post_title'  => __('Current Post Title', 'event-rfq-manager'),
                ),
            ),
        );
    }

    public function render($field_config, $value = null) {
        $field_id = $field_config['id'] ?? '';
        $name = $field_config['name'] ?? $field_id;

        // Get dynamic value if set
        $dynamic = isset($field_config['dynamic_value']) ? $field_config['dynamic_value'] : 'none';

        if ($dynamic !== 'none') {
            $value = $this->get_dynamic_value($dynamic);
        } elseif ($value === null && isset($field_config['default_value'])) {
            $value = $field_config['default_value'];
        }

        $input = '<input type="hidden" ';
        $input .= 'id="erfq-field-' . esc_attr($field_id) . '" ';
        $input .= 'name="erfq_fields[' . esc_attr($name) . ']" ';
        $input .= 'value="' . esc_attr($value) . '" ';
        $input .= 'class="erfq-field erfq-field-hidden">';

        return $input; // No wrapper for hidden fields
    }

    /**
     * Get dynamic value based on type
     *
     * @param string $type Dynamic value type
     *
     * @return string
     */
    protected function get_dynamic_value($type) {
        switch ($type) {
            case 'current_url':
                return esc_url(home_url(add_query_arg(array(), $GLOBALS['wp']->request)));

            case 'referrer':
                return isset($_SERVER['HTTP_REFERER']) ? esc_url($_SERVER['HTTP_REFERER']) : '';

            case 'user_id':
                return get_current_user_id();

            case 'user_email':
                $user = wp_get_current_user();
                return $user->ID ? $user->user_email : '';

            case 'timestamp':
                return current_time('timestamp');

            case 'post_id':
                global $post;
                return $post ? $post->ID : '';

            case 'post_title':
                global $post;
                return $post ? $post->post_title : '';

            default:
                return '';
        }
    }

    protected function validate_type($field_config, $value) {
        // Hidden fields don't require special validation
        return true;
    }
}
