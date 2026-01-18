<?php
/**
 * Form Builder Page class
 *
 * @package Event_RFQ_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ERFQ_Form_Builder_Page
 *
 * Handles the drag-and-drop form builder interface
 */
class ERFQ_Form_Builder_Page {

    /**
     * Form ID
     *
     * @var int
     */
    protected $form_id;

    /**
     * Form object
     *
     * @var ERFQ_Form|null
     */
    protected $form;

    /**
     * Constructor
     *
     * @param int $form_id Form ID (0 for new form)
     */
    public function __construct($form_id = 0) {
        $this->form_id = $form_id;

        if ($form_id) {
            $this->form = ERFQ_Form::get_by_id($form_id);
        }
    }

    /**
     * Render the form builder page
     */
    public function render() {
        $registry = ERFQ_Field_Registry::get_instance();
        $field_types = $registry->get_all_with_meta();
        $categories = $registry->get_categories();

        $form_data = $this->form ? $this->form->to_array() : array(
            'id'       => 0,
            'title'    => '',
            'fields'   => array(),
            'steps'    => array(),
            'settings' => array(),
        );

        include ERFQ_PLUGIN_DIR . 'admin/views/form-builder.php';
    }

    /**
     * Get form data as JSON for JavaScript
     *
     * @return string
     */
    public function get_form_json() {
        if (!$this->form) {
            return '{}';
        }
        return $this->form->to_json();
    }
}
