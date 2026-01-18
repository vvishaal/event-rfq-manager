<?php
/**
 * Admin class
 *
 * @package Event_RFQ_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ERFQ_Admin
 *
 * Main admin class
 */
class ERFQ_Admin {

    /**
     * Initialize admin
     */
    public function admin_init() {
        // Check for migration notice
        if (isset($_GET['migrated'])) {
            add_action('admin_notices', array($this, 'migration_success_notice'));
        }
    }

    /**
     * Enqueue admin styles
     *
     * @param string $hook Current admin page hook
     */
    public function enqueue_styles($hook) {
        // Only load on our pages
        if (!$this->is_erfq_page($hook)) {
            return;
        }

        wp_enqueue_style(
            'erfq-admin',
            ERFQ_PLUGIN_URL . 'assets/css/admin/form-builder.css',
            array(),
            ERFQ_VERSION
        );

        wp_enqueue_style(
            'erfq-admin-entries',
            ERFQ_PLUGIN_URL . 'assets/css/admin/entries.css',
            array(),
            ERFQ_VERSION
        );
    }

    /**
     * Enqueue admin scripts
     *
     * @param string $hook Current admin page hook
     */
    public function enqueue_scripts($hook) {
        if (!$this->is_erfq_page($hook)) {
            return;
        }

        // jQuery UI for drag-drop
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_script('jquery-ui-draggable');
        wp_enqueue_script('jquery-ui-droppable');

        // Form builder JS
        wp_enqueue_script(
            'erfq-form-builder',
            ERFQ_PLUGIN_URL . 'assets/js/admin/form-builder.js',
            array('jquery', 'jquery-ui-sortable', 'wp-util'),
            ERFQ_VERSION,
            true
        );

        wp_enqueue_script(
            'erfq-field-manager',
            ERFQ_PLUGIN_URL . 'assets/js/admin/field-manager.js',
            array('jquery', 'erfq-form-builder'),
            ERFQ_VERSION,
            true
        );

        wp_enqueue_script(
            'erfq-conditional-logic',
            ERFQ_PLUGIN_URL . 'assets/js/admin/conditional-logic.js',
            array('jquery', 'erfq-form-builder'),
            ERFQ_VERSION,
            true
        );

        // Localize scripts
        $this->localize_scripts();
    }

    /**
     * Localize admin scripts
     */
    protected function localize_scripts() {
        $registry = ERFQ_Field_Registry::get_instance();

        wp_localize_script('erfq-form-builder', 'erfqAdmin', array(
            'ajaxUrl'      => admin_url('admin-ajax.php'),
            'nonce'        => wp_create_nonce('erfq_admin_nonce'),
            'fieldTypes'   => $registry->get_all_with_meta(),
            'operators'    => ERFQ_Conditional_Logic::$operators,
            'i18n'         => array(
                'confirmDelete'      => __('Are you sure you want to delete this?', 'event-rfq-manager'),
                'unsavedChanges'     => __('You have unsaved changes. Are you sure you want to leave?', 'event-rfq-manager'),
                'saving'             => __('Saving...', 'event-rfq-manager'),
                'saved'              => __('Saved!', 'event-rfq-manager'),
                'error'              => __('An error occurred.', 'event-rfq-manager'),
                'addField'           => __('Add Field', 'event-rfq-manager'),
                'editField'          => __('Edit Field', 'event-rfq-manager'),
                'deleteField'        => __('Delete Field', 'event-rfq-manager'),
                'duplicateField'     => __('Duplicate Field', 'event-rfq-manager'),
                'fieldSettings'      => __('Field Settings', 'event-rfq-manager'),
                'conditionalLogic'   => __('Conditional Logic', 'event-rfq-manager'),
                'addCondition'       => __('Add Condition', 'event-rfq-manager'),
                'removeCondition'    => __('Remove', 'event-rfq-manager'),
                'showField'          => __('Show', 'event-rfq-manager'),
                'hideField'          => __('Hide', 'event-rfq-manager'),
                'allConditions'      => __('All conditions are met', 'event-rfq-manager'),
                'anyCondition'       => __('Any condition is met', 'event-rfq-manager'),
                'newStep'            => __('New Step', 'event-rfq-manager'),
            ),
        ));
    }

    /**
     * Check if current page is an ERFQ admin page
     *
     * @param string $hook Page hook
     *
     * @return bool
     */
    protected function is_erfq_page($hook) {
        $erfq_pages = array(
            'toplevel_page_erfq-forms',
            'rfq-forms_page_erfq-form-builder',
            'rfq-forms_page_erfq-entries',
            'rfq-forms_page_erfq-settings',
        );

        // Check if the page contains 'erfq'
        if (strpos($hook, 'erfq') !== false) {
            return true;
        }

        return in_array($hook, $erfq_pages, true);
    }

    /**
     * Show migration success notice
     */
    public function migration_success_notice() {
        $count = isset($_GET['migrated']) ? absint($_GET['migrated']) : 0;
        ?>
        <div class="notice notice-success is-dismissible">
            <p>
                <?php
                printf(
                    esc_html(_n(
                        'Successfully migrated %d entry from v1.0.',
                        'Successfully migrated %d entries from v1.0.',
                        $count,
                        'event-rfq-manager'
                    )),
                    $count
                );
                ?>
            </p>
        </div>
        <?php
    }
}
