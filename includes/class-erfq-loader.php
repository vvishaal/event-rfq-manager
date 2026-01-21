<?php
/**
 * The loader class responsible for managing hooks and running the plugin
 *
 * @package Event_RFQ_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ERFQ_Loader
 *
 * Orchestrates the hooks and runs the plugin
 */
class ERFQ_Loader {

    /**
     * The array of actions registered with WordPress
     *
     * @var array
     */
    protected $actions = array();

    /**
     * The array of filters registered with WordPress
     *
     * @var array
     */
    protected $filters = array();

    /**
     * Whether the plugin has been initialized
     *
     * @var bool
     */
    protected static $initialized = false;

    /**
     * Initialize the loader
     */
    public function __construct() {
        $this->define_hooks();
    }

    /**
     * Add a new action to the collection
     *
     * @param string   $hook          The name of the WordPress action
     * @param object   $component     A reference to the instance of the object
     * @param string   $callback      The name of the function on the component
     * @param int      $priority      Optional. The priority at which the function should be fired
     * @param int      $accepted_args Optional. The number of arguments that should be passed
     */
    public function add_action($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->actions = $this->add($this->actions, $hook, $component, $callback, $priority, $accepted_args);
    }

    /**
     * Add a new filter to the collection
     *
     * @param string   $hook          The name of the WordPress filter
     * @param object   $component     A reference to the instance of the object
     * @param string   $callback      The name of the function on the component
     * @param int      $priority      Optional. The priority at which the function should be fired
     * @param int      $accepted_args Optional. The number of arguments that should be passed
     */
    public function add_filter($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->filters = $this->add($this->filters, $hook, $component, $callback, $priority, $accepted_args);
    }

    /**
     * A utility function used to register hooks
     *
     * @param array  $hooks         The collection of hooks that is being registered
     * @param string $hook          The name of the WordPress filter/action
     * @param object $component     A reference to the instance of the object
     * @param string $callback      The name of the function on the component
     * @param int    $priority      The priority at which the function should be fired
     * @param int    $accepted_args The number of arguments that should be passed
     *
     * @return array The collection of hooks
     */
    private function add($hooks, $hook, $component, $callback, $priority, $accepted_args) {
        $hooks[] = array(
            'hook'          => $hook,
            'component'     => $component,
            'callback'      => $callback,
            'priority'      => $priority,
            'accepted_args' => $accepted_args,
        );

        return $hooks;
    }

    /**
     * Define all hooks for the plugin
     */
    private function define_hooks() {
        // Post types
        $this->add_action('init', $this, 'register_post_types');

        // Register field types
        $this->add_action('init', $this, 'register_field_types', 5);

        // Check for migration needs
        $this->add_action('admin_init', $this, 'maybe_migrate');

        // Initialize admin
        if (is_admin()) {
            $this->define_admin_hooks();
        }

        // Initialize public
        $this->define_public_hooks();
    }

    /**
     * Register custom post types
     */
    public function register_post_types() {
        ERFQ_Post_Type_Form::register();
        ERFQ_Post_Type_Entry::register();
    }

    /**
     * Register all built-in field types
     */
    public function register_field_types() {
        $registry = ERFQ_Field_Registry::get_instance();

        // Load all field type classes
        $field_types = array(
            'text'     => ERFQ_PLUGIN_DIR . 'includes/fields/class-erfq-field-text.php',
            'email'    => ERFQ_PLUGIN_DIR . 'includes/fields/class-erfq-field-email.php',
            'phone'    => ERFQ_PLUGIN_DIR . 'includes/fields/class-erfq-field-phone.php',
            'textarea' => ERFQ_PLUGIN_DIR . 'includes/fields/class-erfq-field-textarea.php',
            'number'   => ERFQ_PLUGIN_DIR . 'includes/fields/class-erfq-field-number.php',
            'address'  => ERFQ_PLUGIN_DIR . 'includes/fields/class-erfq-field-address.php',
            'date'     => ERFQ_PLUGIN_DIR . 'includes/fields/class-erfq-field-date.php',
            'time'     => ERFQ_PLUGIN_DIR . 'includes/fields/class-erfq-field-time.php',
            'select'   => ERFQ_PLUGIN_DIR . 'includes/fields/class-erfq-field-select.php',
            'checkbox' => ERFQ_PLUGIN_DIR . 'includes/fields/class-erfq-field-checkbox.php',
            'radio'    => ERFQ_PLUGIN_DIR . 'includes/fields/class-erfq-field-radio.php',
            'file'     => ERFQ_PLUGIN_DIR . 'includes/fields/class-erfq-field-file.php',
            'hidden'   => ERFQ_PLUGIN_DIR . 'includes/fields/class-erfq-field-hidden.php',
            'repeater' => ERFQ_PLUGIN_DIR . 'includes/fields/class-erfq-field-repeater.php',
            'section'  => ERFQ_PLUGIN_DIR . 'includes/fields/class-erfq-field-section.php',
            'html'     => ERFQ_PLUGIN_DIR . 'includes/fields/class-erfq-field-html.php',
        );

        foreach ($field_types as $type => $file) {
            if (file_exists($file)) {
                require_once $file;
            }
        }

        // Register field types
        $registry->register('text', 'ERFQ_Field_Text');
        $registry->register('email', 'ERFQ_Field_Email');
        $registry->register('phone', 'ERFQ_Field_Phone');
        $registry->register('textarea', 'ERFQ_Field_Textarea');
        $registry->register('number', 'ERFQ_Field_Number');
        $registry->register('address', 'ERFQ_Field_Address');
        $registry->register('date', 'ERFQ_Field_Date');
        $registry->register('time', 'ERFQ_Field_Time');
        $registry->register('select', 'ERFQ_Field_Select');
        $registry->register('checkbox', 'ERFQ_Field_Checkbox');
        $registry->register('radio', 'ERFQ_Field_Radio');
        $registry->register('file', 'ERFQ_Field_File');
        $registry->register('hidden', 'ERFQ_Field_Hidden');
        $registry->register('repeater', 'ERFQ_Field_Repeater');
        $registry->register('section', 'ERFQ_Field_Section');
        $registry->register('html', 'ERFQ_Field_HTML');

        // Allow plugins to register custom field types
        do_action('erfq_register_field_types', $registry);
    }

    /**
     * Check if migration is needed
     */
    public function maybe_migrate() {
        $migrator = new ERFQ_Migrator();
        $migrator->maybe_migrate();
    }

    /**
     * Define admin hooks
     */
    private function define_admin_hooks() {
        $admin = new ERFQ_Admin();
        $menu = new ERFQ_Admin_Menu();
        $settings = new ERFQ_Admin_Settings();
        $ajax = new ERFQ_Ajax_Handlers();

        // Admin initialization
        $this->add_action('admin_init', $admin, 'admin_init');

        // Admin menu
        $this->add_action('admin_menu', $menu, 'add_menu_pages');

        // Admin assets
        $this->add_action('admin_enqueue_scripts', $admin, 'enqueue_styles');
        $this->add_action('admin_enqueue_scripts', $admin, 'enqueue_scripts');

        // Settings
        $this->add_action('admin_init', $settings, 'register_settings');

        // AJAX handlers
        $this->add_action('wp_ajax_erfq_save_form', $ajax, 'save_form');
        $this->add_action('wp_ajax_erfq_load_form', $ajax, 'load_form');
        $this->add_action('wp_ajax_erfq_delete_form', $ajax, 'delete_form');
        $this->add_action('wp_ajax_erfq_duplicate_form', $ajax, 'duplicate_form');
        $this->add_action('wp_ajax_erfq_update_entry_status', $ajax, 'update_entry_status');
        $this->add_action('wp_ajax_erfq_delete_entry', $ajax, 'delete_entry');
        $this->add_action('wp_ajax_erfq_export_entries', $ajax, 'export_entries');
        $this->add_action('wp_ajax_erfq_add_entry_note', $ajax, 'add_entry_note');
    }

    /**
     * Define public hooks
     */
    private function define_public_hooks() {
        $public = new ERFQ_Public();
        $shortcodes = new ERFQ_Shortcodes();
        $ajax = new ERFQ_Ajax_Public();

        // Public assets
        $this->add_action('wp_enqueue_scripts', $public, 'enqueue_styles');
        $this->add_action('wp_enqueue_scripts', $public, 'enqueue_scripts');

        // Shortcodes
        $this->add_action('init', $shortcodes, 'register');

        // Form preview
        $this->add_action('template_redirect', $public, 'handle_preview');

        // Public AJAX
        $this->add_action('wp_ajax_erfq_submit_form', $ajax, 'submit_form');
        $this->add_action('wp_ajax_nopriv_erfq_submit_form', $ajax, 'submit_form');
        $this->add_action('wp_ajax_erfq_upload_file', $ajax, 'upload_file');
        $this->add_action('wp_ajax_nopriv_erfq_upload_file', $ajax, 'upload_file');
        $this->add_action('wp_ajax_erfq_remove_file', $ajax, 'remove_file');
        $this->add_action('wp_ajax_nopriv_erfq_remove_file', $ajax, 'remove_file');
    }

    /**
     * Run the loader to execute all of the hooks with WordPress
     */
    public function run() {
        if (self::$initialized) {
            return;
        }

        // Register all filters
        foreach ($this->filters as $hook) {
            add_filter(
                $hook['hook'],
                array($hook['component'], $hook['callback']),
                $hook['priority'],
                $hook['accepted_args']
            );
        }

        // Register all actions
        foreach ($this->actions as $hook) {
            add_action(
                $hook['hook'],
                array($hook['component'], $hook['callback']),
                $hook['priority'],
                $hook['accepted_args']
            );
        }

        self::$initialized = true;
    }
}
