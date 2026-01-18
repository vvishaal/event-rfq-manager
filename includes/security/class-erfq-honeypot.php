<?php
/**
 * Honeypot anti-spam
 *
 * @package Event_RFQ_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ERFQ_Honeypot
 *
 * Provides honeypot anti-spam protection
 */
class ERFQ_Honeypot {

    /**
     * Field name for the honeypot
     */
    const FIELD_NAME = 'erfq_website_url';

    /**
     * Render the honeypot field
     *
     * @return string HTML output
     */
    public static function render() {
        $field_name = self::FIELD_NAME;

        // CSS to hide the field from humans but not bots
        $output = '<div class="erfq-hp-field" style="position:absolute;left:-9999px;top:-9999px;opacity:0;height:0;width:0;overflow:hidden;" aria-hidden="true">';
        $output .= '<label for="' . esc_attr($field_name) . '">' . __('Leave this field empty', 'event-rfq-manager') . '</label>';
        $output .= '<input type="text" name="' . esc_attr($field_name) . '" id="' . esc_attr($field_name) . '" value="" autocomplete="off" tabindex="-1">';
        $output .= '</div>';

        return $output;
    }

    /**
     * Validate honeypot field
     *
     * @param array $post_data $_POST data
     *
     * @return bool True if valid (empty), false if spam detected
     */
    public static function validate($post_data) {
        $field_name = self::FIELD_NAME;

        // If the honeypot field has a value, it's spam
        if (isset($post_data[$field_name]) && !empty($post_data[$field_name])) {
            return false;
        }

        return true;
    }
}
