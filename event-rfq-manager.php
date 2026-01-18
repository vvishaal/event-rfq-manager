<?php
/**
 * Plugin Name: Event RFQ Manager
 * Plugin URI: https://yourwebsite.com/event-rfq-manager
 * Description: Comprehensive form builder with drag-and-drop UI, conditional logic, multi-step forms, and entry management
 * Version: 2.0.0
 * Author: Vishal Claode
 * Author URI: https://yourwebsite.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: event-rfq-manager
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Plugin version
define('ERFQ_VERSION', '2.0.0');
define('ERFQ_PREVIOUS_VERSION', '1.0.0');

// Plugin paths
define('ERFQ_PLUGIN_FILE', __FILE__);
define('ERFQ_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ERFQ_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ERFQ_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Minimum requirements
define('ERFQ_MIN_WP_VERSION', '5.8');
define('ERFQ_MIN_PHP_VERSION', '7.4');

/**
 * PSR-4 style autoloader for ERFQ classes
 */
spl_autoload_register(function ($class) {
    // Plugin namespace prefix
    $prefix = 'ERFQ\\';

    // Base directory for the namespace prefix
    $base_dir = ERFQ_PLUGIN_DIR . 'includes/';

    // Does the class use the namespace prefix?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // No, move to the next registered autoloader
        return;
    }

    // Get the relative class name
    $relative_class = substr($class, $len);

    // Build the file path
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // If the file exists, require it
    if (file_exists($file)) {
        require $file;
        return;
    }

    // Fallback: Try WordPress naming convention (class-erfq-*.php)
    $parts = explode('\\', $relative_class);
    $class_name = array_pop($parts);
    $subdirectory = implode('/', array_map('strtolower', $parts));

    // Convert CamelCase to kebab-case
    $filename = 'class-erfq-' . strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $class_name)) . '.php';

    if (!empty($subdirectory)) {
        $file = $base_dir . strtolower($subdirectory) . '/' . $filename;
    } else {
        $file = $base_dir . $filename;
    }

    if (file_exists($file)) {
        require $file;
    }
});

/**
 * Check minimum requirements
 */
function erfq_check_requirements() {
    $errors = array();

    if (version_compare(PHP_VERSION, ERFQ_MIN_PHP_VERSION, '<')) {
        $errors[] = sprintf(
            __('Event RFQ Manager requires PHP version %s or higher. You are running PHP %s.', 'event-rfq-manager'),
            ERFQ_MIN_PHP_VERSION,
            PHP_VERSION
        );
    }

    if (version_compare(get_bloginfo('version'), ERFQ_MIN_WP_VERSION, '<')) {
        $errors[] = sprintf(
            __('Event RFQ Manager requires WordPress version %s or higher. You are running WordPress %s.', 'event-rfq-manager'),
            ERFQ_MIN_WP_VERSION,
            get_bloginfo('version')
        );
    }

    return $errors;
}

/**
 * Display admin notice for requirement errors
 */
function erfq_requirements_error() {
    $errors = erfq_check_requirements();
    foreach ($errors as $error) {
        echo '<div class="notice notice-error"><p>' . esc_html($error) . '</p></div>';
    }
}

/**
 * Plugin activation
 */
function erfq_activate() {
    // Check requirements
    $errors = erfq_check_requirements();
    if (!empty($errors)) {
        wp_die(implode('<br>', $errors));
    }

    // Include activation class
    require_once ERFQ_PLUGIN_DIR . 'includes/class-erfq-activator.php';
    ERFQ_Activator::activate();
}
register_activation_hook(__FILE__, 'erfq_activate');

/**
 * Plugin deactivation
 */
function erfq_deactivate() {
    require_once ERFQ_PLUGIN_DIR . 'includes/class-erfq-deactivator.php';
    ERFQ_Deactivator::deactivate();
}
register_deactivation_hook(__FILE__, 'erfq_deactivate');

/**
 * Initialize the plugin
 */
function erfq_init() {
    // Check requirements first
    $errors = erfq_check_requirements();
    if (!empty($errors)) {
        add_action('admin_notices', 'erfq_requirements_error');
        return;
    }

    // Load text domain
    load_plugin_textdomain('event-rfq-manager', false, dirname(ERFQ_PLUGIN_BASENAME) . '/languages');

    // Include required files
    require_once ERFQ_PLUGIN_DIR . 'includes/class-erfq-loader.php';
    require_once ERFQ_PLUGIN_DIR . 'includes/class-erfq-migrator.php';

    // Include core classes
    require_once ERFQ_PLUGIN_DIR . 'includes/core/class-erfq-form.php';
    require_once ERFQ_PLUGIN_DIR . 'includes/core/class-erfq-field.php';
    require_once ERFQ_PLUGIN_DIR . 'includes/core/class-erfq-entry.php';
    require_once ERFQ_PLUGIN_DIR . 'includes/core/class-erfq-field-group.php';
    require_once ERFQ_PLUGIN_DIR . 'includes/core/class-erfq-form-step.php';
    require_once ERFQ_PLUGIN_DIR . 'includes/core/class-erfq-conditional-logic.php';

    // Include field system
    require_once ERFQ_PLUGIN_DIR . 'includes/fields/class-erfq-field-registry.php';
    require_once ERFQ_PLUGIN_DIR . 'includes/fields/abstract-erfq-field-type.php';

    // Include post types
    require_once ERFQ_PLUGIN_DIR . 'includes/post-types/class-erfq-post-type-form.php';
    require_once ERFQ_PLUGIN_DIR . 'includes/post-types/class-erfq-post-type-entry.php';

    // Include services
    require_once ERFQ_PLUGIN_DIR . 'includes/services/class-erfq-form-renderer.php';
    require_once ERFQ_PLUGIN_DIR . 'includes/services/class-erfq-form-processor.php';
    require_once ERFQ_PLUGIN_DIR . 'includes/services/class-erfq-validator.php';
    require_once ERFQ_PLUGIN_DIR . 'includes/services/class-erfq-email-service.php';
    require_once ERFQ_PLUGIN_DIR . 'includes/services/class-erfq-export-service.php';

    // Include security
    require_once ERFQ_PLUGIN_DIR . 'includes/security/class-erfq-honeypot.php';
    require_once ERFQ_PLUGIN_DIR . 'includes/security/class-erfq-recaptcha.php';
    require_once ERFQ_PLUGIN_DIR . 'includes/security/class-erfq-rate-limiter.php';

    // Include admin classes
    if (is_admin()) {
        require_once ERFQ_PLUGIN_DIR . 'admin/class-erfq-admin.php';
        require_once ERFQ_PLUGIN_DIR . 'admin/class-erfq-admin-menu.php';
        require_once ERFQ_PLUGIN_DIR . 'admin/class-erfq-admin-settings.php';
        require_once ERFQ_PLUGIN_DIR . 'admin/class-erfq-form-builder-page.php';
        require_once ERFQ_PLUGIN_DIR . 'admin/class-erfq-entries-list-table.php';
        require_once ERFQ_PLUGIN_DIR . 'admin/class-erfq-entry-detail-page.php';
        require_once ERFQ_PLUGIN_DIR . 'admin/class-erfq-ajax-handlers.php';
    }

    // Include public classes
    require_once ERFQ_PLUGIN_DIR . 'public/class-erfq-public.php';
    require_once ERFQ_PLUGIN_DIR . 'public/class-erfq-shortcodes.php';
    require_once ERFQ_PLUGIN_DIR . 'public/class-erfq-ajax-public.php';

    // Initialize the loader
    $loader = new ERFQ_Loader();
    $loader->run();
}
add_action('plugins_loaded', 'erfq_init');

/**
 * Get the main plugin instance
 *
 * @return ERFQ_Loader
 */
function erfq() {
    static $instance = null;
    if (null === $instance) {
        $instance = new ERFQ_Loader();
    }
    return $instance;
}
