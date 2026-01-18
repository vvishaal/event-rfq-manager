<?php
/**
 * Form Renderer service
 *
 * @package Event_RFQ_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ERFQ_Form_Renderer
 *
 * Renders forms on the frontend
 */
class ERFQ_Form_Renderer {

    /**
     * Render a form
     *
     * @param ERFQ_Form|int $form     Form object or ID
     * @param array         $options  Rendering options
     *
     * @return string HTML output
     */
    public function render($form, $options = array()) {
        if (is_numeric($form)) {
            $form = ERFQ_Form::get_by_id($form);
        }

        if (!$form || !$form->is_active()) {
            return '';
        }

        $defaults = array(
            'ajax'       => $form->get_setting('enable_ajax', true),
            'class'      => '',
            'show_title' => false,
        );
        $options = wp_parse_args($options, $defaults);

        // Check if multi-step
        $is_multi_step = $form->is_multi_step();

        $output = $this->render_form_open($form, $options);

        if ($is_multi_step) {
            $output .= $this->render_multi_step_form($form);
        } else {
            $output .= $this->render_single_step_form($form);
        }

        $output .= $this->render_form_close($form, $options);

        return $output;
    }

    /**
     * Render form opening tags
     *
     * @param ERFQ_Form $form    Form object
     * @param array     $options Rendering options
     *
     * @return string
     */
    protected function render_form_open($form, $options) {
        $form_id = $form->get_id();
        $css_class = $form->get_setting('css_class', '');

        $classes = array('erfq-form');

        if ($form->is_multi_step()) {
            $classes[] = 'erfq-form-multi-step';
        }

        if ($css_class) {
            $classes[] = $css_class;
        }

        if ($options['class']) {
            $classes[] = $options['class'];
        }

        if ($options['ajax']) {
            $classes[] = 'erfq-form-ajax';
        }

        $output = '<div class="erfq-form-wrapper" id="erfq-form-wrapper-' . esc_attr($form_id) . '">';

        if ($options['show_title']) {
            $output .= '<h3 class="erfq-form-title">' . esc_html($form->get_title()) . '</h3>';
        }

        $output .= '<form id="erfq-form-' . esc_attr($form_id) . '" ';
        $output .= 'class="' . esc_attr(implode(' ', $classes)) . '" ';
        $output .= 'method="post" ';
        $output .= 'enctype="multipart/form-data" ';
        $output .= 'data-form-id="' . esc_attr($form_id) . '">';

        // Nonce field
        $output .= wp_nonce_field('erfq_submit_form_' . $form_id, 'erfq_nonce', true, false);

        // Hidden form ID
        $output .= '<input type="hidden" name="erfq_form_id" value="' . esc_attr($form_id) . '">';

        // Honeypot (if enabled)
        if ($form->get_setting('honeypot_enabled', true)) {
            $output .= ERFQ_Honeypot::render();
        }

        return $output;
    }

    /**
     * Render form closing tags
     *
     * @param ERFQ_Form $form    Form object
     * @param array     $options Rendering options
     *
     * @return string
     */
    protected function render_form_close($form, $options) {
        $submit_text = $form->get_setting('submit_button_text', __('Submit', 'event-rfq-manager'));

        $output = '<div class="erfq-form-actions">';

        // For multi-step, navigation is handled by steps
        if (!$form->is_multi_step()) {
            $output .= '<button type="submit" class="erfq-submit-btn">' . esc_html($submit_text) . '</button>';
        }

        $output .= '</div>';

        // Message container
        $output .= '<div class="erfq-form-message" style="display:none;"></div>';

        // Loading overlay
        $output .= '<div class="erfq-form-loading" style="display:none;">';
        $output .= '<div class="erfq-spinner"></div>';
        $output .= '</div>';

        $output .= '</form>';
        $output .= '</div>'; // .erfq-form-wrapper

        return $output;
    }

    /**
     * Render single-step form content
     *
     * @param ERFQ_Form $form Form object
     *
     * @return string
     */
    protected function render_single_step_form($form) {
        $fields = $form->get_fields();
        return $this->render_fields($fields);
    }

    /**
     * Render multi-step form content
     *
     * @param ERFQ_Form $form Form object
     *
     * @return string
     */
    protected function render_multi_step_form($form) {
        $steps = $form->get_steps();
        $fields = $form->get_fields();
        $submit_text = $form->get_setting('submit_button_text', __('Submit', 'event-rfq-manager'));

        // Build field map by ID
        $field_map = array();
        foreach ($fields as $field) {
            if (isset($field['id'])) {
                $field_map[$field['id']] = $field;
            }
        }

        $output = '';

        // Progress indicator
        $output .= $this->render_progress_indicator($steps);

        // Steps container
        $output .= '<div class="erfq-steps-container">';

        foreach ($steps as $index => $step_config) {
            $step = new ERFQ_Form_Step($step_config);
            $is_first = ($index === 0);
            $is_last = ($index === count($steps) - 1);

            $output .= '<div class="' . esc_attr($step->get_step_classes($is_first)) . '" ';
            $output .= 'data-step="' . esc_attr($index) . '" ';
            $output .= 'style="' . ($is_first ? '' : 'display:none;') . '">';

            // Step header
            if ($step->get_title()) {
                $output .= '<div class="erfq-step-header">';
                $output .= '<h4 class="erfq-step-title">' . esc_html($step->get_title()) . '</h4>';
                if ($step->get_description()) {
                    $output .= '<p class="erfq-step-description">' . esc_html($step->get_description()) . '</p>';
                }
                $output .= '</div>';
            }

            // Step fields
            $output .= '<div class="erfq-step-fields">';
            foreach ($step->get_field_ids() as $field_id) {
                if (isset($field_map[$field_id])) {
                    $output .= $this->render_field($field_map[$field_id]);
                }
            }
            $output .= '</div>';

            // Step navigation
            $output .= '<div class="erfq-step-navigation">';

            if (!$is_first) {
                $output .= '<button type="button" class="erfq-step-prev button">';
                $output .= esc_html($step->get_prev_button_text());
                $output .= '</button>';
            }

            if ($is_last) {
                $output .= '<button type="submit" class="erfq-submit-btn">';
                $output .= esc_html($submit_text);
                $output .= '</button>';
            } else {
                $output .= '<button type="button" class="erfq-step-next button button-primary">';
                $output .= esc_html($step->get_next_button_text());
                $output .= '</button>';
            }

            $output .= '</div>'; // .erfq-step-navigation
            $output .= '</div>'; // .erfq-form-step
        }

        $output .= '</div>'; // .erfq-steps-container

        return $output;
    }

    /**
     * Render progress indicator for multi-step forms
     *
     * @param array $steps Step configurations
     *
     * @return string
     */
    protected function render_progress_indicator($steps) {
        $output = '<div class="erfq-progress-indicator">';
        $output .= '<ul class="erfq-progress-steps">';

        foreach ($steps as $index => $step_config) {
            $step = new ERFQ_Form_Step($step_config);
            $classes = array('erfq-progress-step');

            if ($index === 0) {
                $classes[] = 'erfq-progress-active';
            }

            $output .= '<li class="' . esc_attr(implode(' ', $classes)) . '" data-step="' . esc_attr($index) . '">';

            if ($step->get_icon()) {
                $output .= '<span class="erfq-progress-icon ' . esc_attr($step->get_icon()) . '"></span>';
            } else {
                $output .= '<span class="erfq-progress-number">' . ($index + 1) . '</span>';
            }

            if ($step->get_title()) {
                $output .= '<span class="erfq-progress-label">' . esc_html($step->get_title()) . '</span>';
            }

            $output .= '</li>';
        }

        $output .= '</ul>';
        $output .= '<div class="erfq-progress-bar"><div class="erfq-progress-bar-fill" style="width: ' . (100 / count($steps)) . '%;"></div></div>';
        $output .= '</div>';

        return $output;
    }

    /**
     * Render a collection of fields
     *
     * @param array $fields Field configurations
     *
     * @return string
     */
    protected function render_fields($fields) {
        $output = '<div class="erfq-form-fields">';

        foreach ($fields as $field) {
            $output .= $this->render_field($field);
        }

        $output .= '</div>';

        return $output;
    }

    /**
     * Render a single field
     *
     * @param array $field_config Field configuration
     * @param mixed $value        Current value
     *
     * @return string
     */
    protected function render_field($field_config, $value = null) {
        $registry = ERFQ_Field_Registry::get_instance();
        return $registry->render_field($field_config, $value);
    }

    /**
     * Render form preview for admin
     *
     * @param ERFQ_Form $form Form object
     *
     * @return string
     */
    public function render_preview($form) {
        return $this->render($form, array(
            'ajax'       => false,
            'show_title' => true,
            'class'      => 'erfq-form-preview',
        ));
    }
}
