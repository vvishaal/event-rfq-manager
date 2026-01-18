<?php
/**
 * Address composite field type
 *
 * @package Event_RFQ_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ERFQ_Field_Address
 */
class ERFQ_Field_Address extends ERFQ_Field_Type_Abstract {

    protected $type = 'address';
    protected $name = 'Address';
    protected $icon = 'dashicons-location';
    protected $category = 'advanced';
    protected $description = 'Composite address field';

    protected function get_type_settings() {
        return array(
            'show_street2' => array(
                'type'    => 'checkbox',
                'label'   => __('Show Street Address Line 2', 'event-rfq-manager'),
                'default' => true,
            ),
            'show_city' => array(
                'type'    => 'checkbox',
                'label'   => __('Show City', 'event-rfq-manager'),
                'default' => true,
            ),
            'show_state' => array(
                'type'    => 'checkbox',
                'label'   => __('Show State/Province', 'event-rfq-manager'),
                'default' => true,
            ),
            'show_zip' => array(
                'type'    => 'checkbox',
                'label'   => __('Show ZIP/Postal Code', 'event-rfq-manager'),
                'default' => true,
            ),
            'show_country' => array(
                'type'    => 'checkbox',
                'label'   => __('Show Country', 'event-rfq-manager'),
                'default' => true,
            ),
            'default_country' => array(
                'type'    => 'text',
                'label'   => __('Default Country', 'event-rfq-manager'),
                'default' => '',
            ),
        );
    }

    public function render($field_config, $value = null) {
        $field_id = $field_config['id'] ?? '';
        $name = $field_config['name'] ?? $field_id;
        $required = !empty($field_config['required']);

        // Parse value
        $values = is_array($value) ? $value : array();
        $defaults = array(
            'street1' => '',
            'street2' => '',
            'city'    => '',
            'state'   => '',
            'zip'     => '',
            'country' => isset($field_config['default_country']) ? $field_config['default_country'] : '',
        );
        $values = wp_parse_args($values, $defaults);

        // Field visibility settings
        $show_street2 = isset($field_config['show_street2']) ? $field_config['show_street2'] : true;
        $show_city = isset($field_config['show_city']) ? $field_config['show_city'] : true;
        $show_state = isset($field_config['show_state']) ? $field_config['show_state'] : true;
        $show_zip = isset($field_config['show_zip']) ? $field_config['show_zip'] : true;
        $show_country = isset($field_config['show_country']) ? $field_config['show_country'] : true;

        $input = '<div class="erfq-address-fields">';

        // Street Address 1
        $input .= '<div class="erfq-address-row">';
        $input .= '<label for="erfq-field-' . esc_attr($field_id) . '-street1">' . esc_html__('Street Address', 'event-rfq-manager') . '</label>';
        $input .= '<input type="text" id="erfq-field-' . esc_attr($field_id) . '-street1" ';
        $input .= 'name="erfq_fields[' . esc_attr($name) . '][street1]" ';
        $input .= 'value="' . esc_attr($values['street1']) . '" ';
        $input .= 'class="erfq-field erfq-address-street1"';
        if ($required) {
            $input .= ' required';
        }
        $input .= '>';
        $input .= '</div>';

        // Street Address 2
        if ($show_street2) {
            $input .= '<div class="erfq-address-row">';
            $input .= '<label for="erfq-field-' . esc_attr($field_id) . '-street2">' . esc_html__('Street Address Line 2', 'event-rfq-manager') . '</label>';
            $input .= '<input type="text" id="erfq-field-' . esc_attr($field_id) . '-street2" ';
            $input .= 'name="erfq_fields[' . esc_attr($name) . '][street2]" ';
            $input .= 'value="' . esc_attr($values['street2']) . '" ';
            $input .= 'class="erfq-field erfq-address-street2">';
            $input .= '</div>';
        }

        // City/State row
        if ($show_city || $show_state) {
            $input .= '<div class="erfq-address-row erfq-address-row-split">';

            if ($show_city) {
                $input .= '<div class="erfq-address-col">';
                $input .= '<label for="erfq-field-' . esc_attr($field_id) . '-city">' . esc_html__('City', 'event-rfq-manager') . '</label>';
                $input .= '<input type="text" id="erfq-field-' . esc_attr($field_id) . '-city" ';
                $input .= 'name="erfq_fields[' . esc_attr($name) . '][city]" ';
                $input .= 'value="' . esc_attr($values['city']) . '" ';
                $input .= 'class="erfq-field erfq-address-city"';
                if ($required) {
                    $input .= ' required';
                }
                $input .= '>';
                $input .= '</div>';
            }

            if ($show_state) {
                $input .= '<div class="erfq-address-col">';
                $input .= '<label for="erfq-field-' . esc_attr($field_id) . '-state">' . esc_html__('State/Province', 'event-rfq-manager') . '</label>';
                $input .= '<input type="text" id="erfq-field-' . esc_attr($field_id) . '-state" ';
                $input .= 'name="erfq_fields[' . esc_attr($name) . '][state]" ';
                $input .= 'value="' . esc_attr($values['state']) . '" ';
                $input .= 'class="erfq-field erfq-address-state">';
                $input .= '</div>';
            }

            $input .= '</div>';
        }

        // ZIP/Country row
        if ($show_zip || $show_country) {
            $input .= '<div class="erfq-address-row erfq-address-row-split">';

            if ($show_zip) {
                $input .= '<div class="erfq-address-col">';
                $input .= '<label for="erfq-field-' . esc_attr($field_id) . '-zip">' . esc_html__('ZIP/Postal Code', 'event-rfq-manager') . '</label>';
                $input .= '<input type="text" id="erfq-field-' . esc_attr($field_id) . '-zip" ';
                $input .= 'name="erfq_fields[' . esc_attr($name) . '][zip]" ';
                $input .= 'value="' . esc_attr($values['zip']) . '" ';
                $input .= 'class="erfq-field erfq-address-zip">';
                $input .= '</div>';
            }

            if ($show_country) {
                $input .= '<div class="erfq-address-col">';
                $input .= '<label for="erfq-field-' . esc_attr($field_id) . '-country">' . esc_html__('Country', 'event-rfq-manager') . '</label>';
                $input .= '<input type="text" id="erfq-field-' . esc_attr($field_id) . '-country" ';
                $input .= 'name="erfq_fields[' . esc_attr($name) . '][country]" ';
                $input .= 'value="' . esc_attr($values['country']) . '" ';
                $input .= 'class="erfq-field erfq-address-country">';
                $input .= '</div>';
            }

            $input .= '</div>';
        }

        $input .= '</div>';

        return $this->wrap_field($field_config, $input);
    }

    protected function validate_type($field_config, $value) {
        if (!is_array($value)) {
            return true;
        }

        $label = $field_config['label'] ?? __('Address', 'event-rfq-manager');
        $required = !empty($field_config['required']);

        if ($required) {
            if (empty($value['street1'])) {
                return new WP_Error(
                    'required',
                    sprintf(__('%s: Street address is required.', 'event-rfq-manager'), $label)
                );
            }

            $show_city = isset($field_config['show_city']) ? $field_config['show_city'] : true;
            if ($show_city && empty($value['city'])) {
                return new WP_Error(
                    'required',
                    sprintf(__('%s: City is required.', 'event-rfq-manager'), $label)
                );
            }
        }

        return true;
    }

    public function sanitize($field_config, $value) {
        if (!is_array($value)) {
            return array();
        }

        return array(
            'street1' => isset($value['street1']) ? sanitize_text_field($value['street1']) : '',
            'street2' => isset($value['street2']) ? sanitize_text_field($value['street2']) : '',
            'city'    => isset($value['city']) ? sanitize_text_field($value['city']) : '',
            'state'   => isset($value['state']) ? sanitize_text_field($value['state']) : '',
            'zip'     => isset($value['zip']) ? sanitize_text_field($value['zip']) : '',
            'country' => isset($value['country']) ? sanitize_text_field($value['country']) : '',
        );
    }

    public function get_display_value($field_config, $value) {
        if (!is_array($value) || empty(array_filter($value))) {
            return '';
        }

        $parts = array();

        if (!empty($value['street1'])) {
            $parts[] = $value['street1'];
        }
        if (!empty($value['street2'])) {
            $parts[] = $value['street2'];
        }

        $city_state_zip = array();
        if (!empty($value['city'])) {
            $city_state_zip[] = $value['city'];
        }
        if (!empty($value['state'])) {
            $city_state_zip[] = $value['state'];
        }
        if (!empty($value['zip'])) {
            $city_state_zip[] = $value['zip'];
        }

        if (!empty($city_state_zip)) {
            $parts[] = implode(', ', $city_state_zip);
        }

        if (!empty($value['country'])) {
            $parts[] = $value['country'];
        }

        return implode('<br>', array_map('esc_html', $parts));
    }

    public function get_export_value($field_config, $value) {
        if (!is_array($value)) {
            return '';
        }

        $parts = array_filter(array(
            $value['street1'] ?? '',
            $value['street2'] ?? '',
            $value['city'] ?? '',
            $value['state'] ?? '',
            $value['zip'] ?? '',
            $value['country'] ?? '',
        ));

        return implode(', ', $parts);
    }
}
