<?php
/**
 * Plugin Name: Event RFQ Manager
 * Plugin URI: https://yourwebsite.com/event-rfq-manager
 * Description: Comprehensive event quotation request management system for venues, accommodation, conference setups, and services
 * Version: 1.0.0
 * Author: Vishal Claode
 * Author URI: https://yourwebsite.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: event-rfq-manager
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('ERFQ_VERSION', '1.0.0');
define('ERFQ_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ERFQ_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ERFQ_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Plugin activation hook
 */
function erfq_activate() {
    // Register custom post type
    erfq_register_post_type();
    
    // Flush rewrite rules
    flush_rewrite_rules();
    
    // Set default options
    add_option('erfq_admin_email', get_option('admin_email'));
    add_option('erfq_enable_notifications', '1');
}
register_activation_hook(__FILE__, 'erfq_activate');

/**
 * Plugin deactivation hook
 */
function erfq_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'erfq_deactivate');

/**
 * Register custom post type
 */
function erfq_register_post_type() {
    $labels = array(
        'name'                  => _x('Event RFQs', 'Post Type General Name', 'event-rfq-manager'),
        'singular_name'         => _x('Event RFQ', 'Post Type Singular Name', 'event-rfq-manager'),
        'menu_name'             => __('Event RFQs', 'event-rfq-manager'),
        'name_admin_bar'        => __('Event RFQ', 'event-rfq-manager'),
        'archives'              => __('RFQ Archives', 'event-rfq-manager'),
        'attributes'            => __('RFQ Attributes', 'event-rfq-manager'),
        'parent_item_colon'     => __('Parent RFQ:', 'event-rfq-manager'),
        'all_items'             => __('All RFQs', 'event-rfq-manager'),
        'add_new_item'          => __('Add New RFQ', 'event-rfq-manager'),
        'add_new'               => __('Add New', 'event-rfq-manager'),
        'new_item'              => __('New RFQ', 'event-rfq-manager'),
        'edit_item'             => __('Edit RFQ', 'event-rfq-manager'),
        'update_item'           => __('Update RFQ', 'event-rfq-manager'),
        'view_item'             => __('View RFQ', 'event-rfq-manager'),
        'view_items'            => __('View RFQs', 'event-rfq-manager'),
        'search_items'          => __('Search RFQ', 'event-rfq-manager'),
    );

    $args = array(
        'label'                 => __('Event RFQ', 'event-rfq-manager'),
        'description'           => __('Event Request for Quotation', 'event-rfq-manager'),
        'labels'                => $labels,
        'supports'              => array('title'),
        'hierarchical'          => false,
        'public'                => false,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'menu_position'         => 25,
        'menu_icon'             => 'dashicons-calendar-alt',
        'show_in_admin_bar'     => true,
        'show_in_nav_menus'     => false,
        'can_export'            => true,
        'has_archive'           => false,
        'exclude_from_search'   => true,
        'publicly_queryable'    => false,
        'capability_type'       => 'post',
        'capabilities'          => array(
            'create_posts' => 'do_not_allow',
        ),
        'map_meta_cap'          => true,
    );

    register_post_type('event_rfq', $args);
}
add_action('init', 'erfq_register_post_type');

/**
 * Enqueue frontend styles and scripts
 */
function erfq_enqueue_frontend_assets() {
    if (is_singular() || is_page()) {
        global $post;
        if (has_shortcode($post->post_content, 'event_rfq_form')) {
            wp_enqueue_style('erfq-frontend', ERFQ_PLUGIN_URL . 'assets/css/frontend.css', array(), ERFQ_VERSION);
            wp_enqueue_script('erfq-frontend', ERFQ_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), ERFQ_VERSION, true);
            
            wp_localize_script('erfq-frontend', 'erfqAjax', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('erfq_form_nonce')
            ));
        }
    }
}
add_action('wp_enqueue_scripts', 'erfq_enqueue_frontend_assets');

/**
 * Enqueue admin styles and scripts
 */
function erfq_enqueue_admin_assets($hook) {
    if ('post.php' === $hook || 'post-new.php' === $hook || 'edit.php' === $hook) {
        global $post_type;
        if ('event_rfq' === $post_type) {
            wp_enqueue_style('erfq-admin', ERFQ_PLUGIN_URL . 'assets/css/admin.css', array(), ERFQ_VERSION);
            wp_enqueue_script('erfq-admin', ERFQ_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), ERFQ_VERSION, true);
        }
    }
}
add_action('admin_enqueue_scripts', 'erfq_enqueue_admin_assets');

/**
 * Register shortcode for RFQ form
 */
function erfq_form_shortcode($atts) {
    $template_path = ERFQ_PLUGIN_DIR . 'templates/form-template.php';
    
    // Check if template file exists
    if (!file_exists($template_path)) {
        if (current_user_can('manage_options')) {
            return '<div class="erfq-error" style="padding:20px;background:#f8d7da;color:#721c24;border:1px solid #f5c6cb;border-radius:4px;">
                <strong>Event RFQ Error:</strong> Template file not found at: ' . esc_html($template_path) . '
                <br><small>Please ensure form-template.php exists in the templates folder.</small>
            </div>';
        }
        return '';
    }
    
    ob_start();
    include $template_path;
    return ob_get_clean();
}
add_shortcode('event_rfq_form', 'erfq_form_shortcode');

/**
 * Handle AJAX form submission
 */
function erfq_handle_form_submission() {
    // Verify nonce
    if (!isset($_POST['erfq_nonce']) || !wp_verify_nonce($_POST['erfq_nonce'], 'erfq_form_nonce')) {
        wp_send_json_error(array('message' => __('Security check failed', 'event-rfq-manager')));
    }

    // Sanitize and validate data
    $date_from = sanitize_text_field($_POST['date_from']);
    $date_to = sanitize_text_field($_POST['date_to']);
    $destination = sanitize_text_field($_POST['destination']);
    $venues = isset($_POST['venues']) ? array_map('sanitize_text_field', $_POST['venues']) : array();
    $category = sanitize_text_field($_POST['category']);
    $adults = absint($_POST['adults']);
    $children = absint($_POST['children']);
    
    // Accommodation
    $rooms = isset($_POST['rooms']) ? array_map('sanitize_text_field', $_POST['rooms']) : array();
    
    // Food & Beverage
    $meals = isset($_POST['meals']) ? array_map('sanitize_text_field', $_POST['meals']) : array();
    
    // Conference Setup
    $conference_setup = isset($_POST['conference_setup']) ? array_map('sanitize_text_field', $_POST['conference_setup']) : array();
    
    // Audio Visual
    $av_requirements = isset($_POST['av_requirements']) ? array_map('sanitize_text_field', $_POST['av_requirements']) : array();
    
    // Contact Information
    $contact_name = sanitize_text_field($_POST['contact_name']);
    $contact_designation = sanitize_text_field($_POST['contact_designation']);
    $contact_company = sanitize_text_field($_POST['contact_company']);
    $contact_address = sanitize_textarea_field($_POST['contact_address']);
    $contact_mobile = sanitize_text_field($_POST['contact_mobile']);
    $contact_email = sanitize_email($_POST['contact_email']);

    // Validate required fields
    if (empty($date_from) || empty($destination) || empty($contact_name) || empty($contact_email)) {
        wp_send_json_error(array('message' => __('Please fill all required fields', 'event-rfq-manager')));
    }

    // Create post
    $post_title = sprintf('%s - %s - %s', $contact_company ?: $contact_name, $destination, $date_from);
    
    $post_id = wp_insert_post(array(
        'post_type' => 'event_rfq',
        'post_title' => $post_title,
        'post_status' => 'publish',
        'post_author' => get_current_user_id() ?: 1,
    ));

    if (is_wp_error($post_id)) {
        wp_send_json_error(array('message' => __('Failed to create RFQ', 'event-rfq-manager')));
    }

    // Save meta data
    update_post_meta($post_id, '_erfq_date_from', $date_from);
    update_post_meta($post_id, '_erfq_date_to', $date_to);
    update_post_meta($post_id, '_erfq_destination', $destination);
    update_post_meta($post_id, '_erfq_venues', $venues);
    update_post_meta($post_id, '_erfq_category', $category);
    update_post_meta($post_id, '_erfq_adults', $adults);
    update_post_meta($post_id, '_erfq_children', $children);
    update_post_meta($post_id, '_erfq_rooms', $rooms);
    update_post_meta($post_id, '_erfq_meals', $meals);
    update_post_meta($post_id, '_erfq_conference_setup', $conference_setup);
    update_post_meta($post_id, '_erfq_av_requirements', $av_requirements);
    update_post_meta($post_id, '_erfq_contact_name', $contact_name);
    update_post_meta($post_id, '_erfq_contact_designation', $contact_designation);
    update_post_meta($post_id, '_erfq_contact_company', $contact_company);
    update_post_meta($post_id, '_erfq_contact_address', $contact_address);
    update_post_meta($post_id, '_erfq_contact_mobile', $contact_mobile);
    update_post_meta($post_id, '_erfq_contact_email', $contact_email);
    update_post_meta($post_id, '_erfq_status', 'pending');
    update_post_meta($post_id, '_erfq_submission_date', current_time('mysql'));

    // Transport data - Arrival transfers
    $arrival_transfers = array();
    if (isset($_POST['arrival_transfers']) && is_array($_POST['arrival_transfers'])) {
        foreach ($_POST['arrival_transfers'] as $index => $transfer) {
            // Check if it's the new format (with sub-keys) or flat format
            if (is_array($transfer) && isset($transfer['date'])) {
                $arrival_transfers[] = array(
                    'date' => sanitize_text_field($transfer['date']),
                    'time' => sanitize_text_field($transfer['time']),
                    'flight' => sanitize_text_field($transfer['flight']),
                    'pax' => absint($transfer['pax']),
                );
            }
        }
        // Only save if we have data
        if (!empty($arrival_transfers)) {
            update_post_meta($post_id, '_erfq_arrival_transfers', $arrival_transfers);
        }
    }

    // Transport data - Departure transfers
    $departure_transfers = array();
    if (isset($_POST['departure_transfers']) && is_array($_POST['departure_transfers'])) {
        foreach ($_POST['departure_transfers'] as $index => $transfer) {
            // Check if it's the new format (with sub-keys) or flat format
            if (is_array($transfer) && isset($transfer['date'])) {
                $departure_transfers[] = array(
                    'date' => sanitize_text_field($transfer['date']),
                    'time' => sanitize_text_field($transfer['time']),
                    'flight' => sanitize_text_field($transfer['flight']),
                    'pax' => absint($transfer['pax']),
                );
            }
        }
        // Only save if we have data
        if (!empty($departure_transfers)) {
            update_post_meta($post_id, '_erfq_departure_transfers', $departure_transfers);
        }
    }

    // Sightseeing
    $sightseeing = array();
    if (isset($_POST['sightseeing']) && is_array($_POST['sightseeing'])) {
        foreach ($_POST['sightseeing'] as $index => $tour) {
            // Check if it's the new format (with sub-keys) or flat format
            if (is_array($tour) && isset($tour['date'])) {
                $sightseeing[] = array(
                    'date' => sanitize_text_field($tour['date']),
                    'time' => sanitize_text_field($tour['time']),
                    'service' => sanitize_text_field($tour['service']),
                    'pax' => absint($tour['pax']),
                );
            }
        }
        // Only save if we have data
        if (!empty($sightseeing)) {
            update_post_meta($post_id, '_erfq_sightseeing', $sightseeing);
        }
    }

    // Special services
    $special_services = sanitize_textarea_field($_POST['special_services']);
    update_post_meta($post_id, '_erfq_special_services', $special_services);

    // Send notification email
    $email_sent = erfq_send_notification_email($post_id);

    // Prepare success message
    $success_message = __('Your request has been submitted successfully!', 'event-rfq-manager');
    if (!$email_sent) {
        $success_message .= ' ' . __('(Note: Email notification could not be sent)', 'event-rfq-manager');
    }

    wp_send_json_success(array(
        'message' => $success_message,
        'rfq_id' => $post_id,
        'email_sent' => $email_sent
    ));
}
add_action('wp_ajax_erfq_submit_form', 'erfq_handle_form_submission');
add_action('wp_ajax_nopriv_erfq_submit_form', 'erfq_handle_form_submission');

/**
 * Send notification email
 */
function erfq_send_notification_email($post_id) {
    $admin_email = get_option('erfq_admin_email', get_option('admin_email'));
    $enable_notifications = get_option('erfq_enable_notifications', '1');

    if ($enable_notifications !== '1') {
        return;
    }

    $contact_name = get_post_meta($post_id, '_erfq_contact_name', true);
    $contact_email = get_post_meta($post_id, '_erfq_contact_email', true);
    $contact_company = get_post_meta($post_id, '_erfq_contact_company', true);
    $destination = get_post_meta($post_id, '_erfq_destination', true);
    $date_from = get_post_meta($post_id, '_erfq_date_from', true);
    $date_to = get_post_meta($post_id, '_erfq_date_to', true);
    $adults = get_post_meta($post_id, '_erfq_adults', true);
    $children = get_post_meta($post_id, '_erfq_children', true);

    $subject = sprintf(__('New Event RFQ Submission - %s', 'event-rfq-manager'), $destination);
    
    $message = "New Event Request for Quotation Received\n\n";
    $message .= "==================================\n";
    $message .= "CONTACT INFORMATION\n";
    $message .= "==================================\n";
    $message .= "Name: " . $contact_name . "\n";
    if (!empty($contact_company)) {
        $message .= "Company: " . $contact_company . "\n";
    }
    $message .= "Email: " . $contact_email . "\n\n";
    
    $message .= "==================================\n";
    $message .= "EVENT DETAILS\n";
    $message .= "==================================\n";
    $message .= "Destination: " . $destination . "\n";
    $message .= "Dates: " . $date_from . " to " . $date_to . "\n";
    $message .= "Guests: " . $adults . " Adults, " . $children . " Children\n\n";
    
    $message .= "View full details and manage this RFQ:\n";
    $message .= admin_url('post.php?post=' . $post_id . '&action=edit') . "\n";

    // Set email headers - simpler format
    $site_name = get_bloginfo('name');
    $from_email = get_option('admin_email');
    
    $headers = array();
    $headers[] = 'Content-Type: text/plain; charset=UTF-8';
    //$headers[] = 'From: ' . $site_name . ' <' . $from_email . '>';
	$headers[] = 'From: ' . $site_name . ' <info@flatkhojo.com>';
    $headers[] = 'Reply-To: ' . $contact_email;

    // Send email
    $sent = wp_mail($admin_email, $subject, $message, $headers);
    
    // Log if email fails (for debugging)
    if (!$sent) {
        error_log('Event RFQ: Failed to send notification email for post ID ' . $post_id);
    }
    
    return $sent;
}

/**
 * Filter wp_mail from name
 */
function erfq_mail_from_name($name) {
    return get_bloginfo('name');
}
add_filter('wp_mail_from_name', 'erfq_mail_from_name');

/**
 * Add custom columns to RFQ list
 */
function erfq_add_custom_columns($columns) {
    $new_columns = array();
    $new_columns['cb'] = $columns['cb'];
    $new_columns['title'] = $columns['title'];
    $new_columns['destination'] = __('Destination', 'event-rfq-manager');
    $new_columns['dates'] = __('Dates', 'event-rfq-manager');
    $new_columns['contact'] = __('Contact', 'event-rfq-manager');
    $new_columns['status'] = __('Status', 'event-rfq-manager');
    $new_columns['date'] = $columns['date'];
    return $new_columns;
}
add_filter('manage_event_rfq_posts_columns', 'erfq_add_custom_columns');

/**
 * Add row actions (Edit, View, Trash, etc.)
 */
function erfq_add_row_actions($actions, $post) {
    if ($post->post_type === 'event_rfq') {
        // Add View action
        $view_url = admin_url('post.php?post=' . $post->ID . '&action=edit');
        $actions['view'] = sprintf(
            '<a href="%s">%s</a>',
            esc_url($view_url),
            __('View Details', 'event-rfq-manager')
        );
    }
    return $actions;
}
add_filter('post_row_actions', 'erfq_add_row_actions', 10, 2);

/**
 * Populate custom columns
 */
function erfq_populate_custom_columns($column, $post_id) {
    switch ($column) {
        case 'destination':
            echo esc_html(get_post_meta($post_id, '_erfq_destination', true));
            break;
        case 'dates':
            $date_from = get_post_meta($post_id, '_erfq_date_from', true);
            $date_to = get_post_meta($post_id, '_erfq_date_to', true);
            echo esc_html($date_from . ' to ' . $date_to);
            break;
        case 'contact':
            $name = get_post_meta($post_id, '_erfq_contact_name', true);
            $email = get_post_meta($post_id, '_erfq_contact_email', true);
            echo esc_html($name) . '<br>' . esc_html($email);
            break;
        case 'status':
            $status = get_post_meta($post_id, '_erfq_status', true);
            $status_label = $status === 'processed' ? 'Processed' : 'Pending';
            $status_class = $status === 'processed' ? 'erfq-status-processed' : 'erfq-status-pending';
            echo '<span class="' . esc_attr($status_class) . '">' . esc_html($status_label) . '</span>';
            break;
    }
}
add_action('manage_event_rfq_posts_custom_column', 'erfq_populate_custom_columns', 10, 2);

/**
 * Add meta box to display RFQ details
 */
function erfq_add_meta_boxes() {
    add_meta_box(
        'erfq_details',
        __('RFQ Details', 'event-rfq-manager'),
        'erfq_render_meta_box',
        'event_rfq',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'erfq_add_meta_boxes');

/**
 * Render meta box content
 */
function erfq_render_meta_box($post) {
    include ERFQ_PLUGIN_DIR . 'templates/admin-details.php';
}

/**
 * Add settings page
 */
function erfq_add_settings_page() {
    add_submenu_page(
        'edit.php?post_type=event_rfq',
        __('Settings', 'event-rfq-manager'),
        __('Settings', 'event-rfq-manager'),
        'manage_options',
        'erfq-settings',
        'erfq_render_settings_page'
    );
}
add_action('admin_menu', 'erfq_add_settings_page');

/**
 * Handle status update AJAX
 */
function erfq_update_status_ajax() {
    check_ajax_referer('erfq_update_status', 'nonce');
    
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(array('message' => __('Permission denied', 'event-rfq-manager')));
    }
    
    $post_id = absint($_POST['post_id']);
    $status = sanitize_text_field($_POST['status']);
    
    if (in_array($status, array('pending', 'processed'))) {
        update_post_meta($post_id, '_erfq_status', $status);
        wp_send_json_success(array('message' => __('Status updated', 'event-rfq-manager')));
    } else {
        wp_send_json_error(array('message' => __('Invalid status', 'event-rfq-manager')));
    }
}
add_action('wp_ajax_erfq_update_status', 'erfq_update_status_ajax');

/**
 * Render settings page
 */
function erfq_render_settings_page() {
    if (isset($_POST['erfq_save_settings'])) {
        check_admin_referer('erfq_settings_nonce');
        update_option('erfq_admin_email', sanitize_email($_POST['erfq_admin_email']));
        update_option('erfq_enable_notifications', isset($_POST['erfq_enable_notifications']) ? '1' : '0');
        echo '<div class="notice notice-success"><p>' . esc_html__('Settings saved successfully!', 'event-rfq-manager') . '</p></div>';
    }

    $admin_email = get_option('erfq_admin_email', get_option('admin_email'));
    $enable_notifications = get_option('erfq_enable_notifications', '1');
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__('Event RFQ Settings', 'event-rfq-manager'); ?></h1>
        <form method="post">
            <?php wp_nonce_field('erfq_settings_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="erfq_admin_email"><?php esc_html_e('Admin Email', 'event-rfq-manager'); ?></label></th>
                    <td>
                        <input type="email" id="erfq_admin_email" name="erfq_admin_email" value="<?php echo esc_attr($admin_email); ?>" class="regular-text">
                        <p class="description"><?php esc_html_e('Email address to receive RFQ notifications', 'event-rfq-manager'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Email Notifications', 'event-rfq-manager'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="erfq_enable_notifications" value="1" <?php checked($enable_notifications, '1'); ?>>
                            <?php esc_html_e('Enable email notifications for new RFQ submissions', 'event-rfq-manager'); ?>
                        </label>
                    </td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="erfq_save_settings" class="button button-primary" value="<?php esc_attr_e('Save Settings', 'event-rfq-manager'); ?>">
            </p>
        </form>
    </div>
    <?php
}