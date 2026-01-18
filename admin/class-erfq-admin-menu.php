<?php
/**
 * Admin Menu class
 *
 * @package Event_RFQ_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ERFQ_Admin_Menu
 *
 * Handles admin menu registration
 */
class ERFQ_Admin_Menu {

    /**
     * Add admin menu pages
     */
    public function add_menu_pages() {
        // Main menu - Forms list
        add_menu_page(
            __('RFQ Forms', 'event-rfq-manager'),
            __('RFQ Forms', 'event-rfq-manager'),
            'manage_options',
            'erfq-forms',
            array($this, 'render_forms_page'),
            'dashicons-feedback',
            25
        );

        // Forms submenu (same as main)
        add_submenu_page(
            'erfq-forms',
            __('All Forms', 'event-rfq-manager'),
            __('All Forms', 'event-rfq-manager'),
            'manage_options',
            'erfq-forms',
            array($this, 'render_forms_page')
        );

        // Add New Form
        add_submenu_page(
            'erfq-forms',
            __('Add New Form', 'event-rfq-manager'),
            __('Add New', 'event-rfq-manager'),
            'manage_options',
            'erfq-form-builder',
            array($this, 'render_form_builder_page')
        );

        // Entries
        $unread_count = ERFQ_Post_Type_Entry::get_unread_count();
        $entries_title = __('Entries', 'event-rfq-manager');
        if ($unread_count > 0) {
            $entries_title .= sprintf(' <span class="awaiting-mod">%d</span>', $unread_count);
        }

        add_submenu_page(
            'erfq-forms',
            __('Entries', 'event-rfq-manager'),
            $entries_title,
            'manage_options',
            'erfq-entries',
            array($this, 'render_entries_page')
        );

        // Settings
        add_submenu_page(
            'erfq-forms',
            __('Settings', 'event-rfq-manager'),
            __('Settings', 'event-rfq-manager'),
            'manage_options',
            'erfq-settings',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Render forms list page
     */
    public function render_forms_page() {
        // Check for actions
        $action = isset($_GET['action']) ? $_GET['action'] : '';

        if ($action === 'edit' && isset($_GET['form_id'])) {
            $this->render_form_builder_page();
            return;
        }

        include ERFQ_PLUGIN_DIR . 'admin/views/form-list.php';
    }

    /**
     * Render form builder page
     */
    public function render_form_builder_page() {
        $form_id = isset($_GET['form_id']) ? absint($_GET['form_id']) : 0;
        $builder = new ERFQ_Form_Builder_Page($form_id);
        $builder->render();
    }

    /**
     * Render entries page
     */
    public function render_entries_page() {
        $action = isset($_GET['action']) ? $_GET['action'] : '';

        if ($action === 'view' && isset($_GET['entry_id'])) {
            $detail_page = new ERFQ_Entry_Detail_Page(absint($_GET['entry_id']));
            $detail_page->render();
            return;
        }

        include ERFQ_PLUGIN_DIR . 'admin/views/entry-list.php';
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        $settings = new ERFQ_Admin_Settings();
        $settings->render_page();
    }
}
