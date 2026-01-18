<?php
/**
 * Form custom post type
 *
 * @package Event_RFQ_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ERFQ_Post_Type_Form
 *
 * Registers and manages the erfq_form post type
 */
class ERFQ_Post_Type_Form {

    /**
     * Post type name
     */
    const POST_TYPE = 'erfq_form';

    /**
     * Register the post type
     */
    public static function register() {
        $labels = array(
            'name'                  => _x('Forms', 'Post Type General Name', 'event-rfq-manager'),
            'singular_name'         => _x('Form', 'Post Type Singular Name', 'event-rfq-manager'),
            'menu_name'             => __('RFQ Forms', 'event-rfq-manager'),
            'name_admin_bar'        => __('Form', 'event-rfq-manager'),
            'archives'              => __('Form Archives', 'event-rfq-manager'),
            'attributes'            => __('Form Attributes', 'event-rfq-manager'),
            'parent_item_colon'     => __('Parent Form:', 'event-rfq-manager'),
            'all_items'             => __('All Forms', 'event-rfq-manager'),
            'add_new_item'          => __('Add New Form', 'event-rfq-manager'),
            'add_new'               => __('Add New', 'event-rfq-manager'),
            'new_item'              => __('New Form', 'event-rfq-manager'),
            'edit_item'             => __('Edit Form', 'event-rfq-manager'),
            'update_item'           => __('Update Form', 'event-rfq-manager'),
            'view_item'             => __('View Form', 'event-rfq-manager'),
            'view_items'            => __('View Forms', 'event-rfq-manager'),
            'search_items'          => __('Search Form', 'event-rfq-manager'),
            'not_found'             => __('Not found', 'event-rfq-manager'),
            'not_found_in_trash'    => __('Not found in Trash', 'event-rfq-manager'),
            'featured_image'        => __('Featured Image', 'event-rfq-manager'),
            'set_featured_image'    => __('Set featured image', 'event-rfq-manager'),
            'remove_featured_image' => __('Remove featured image', 'event-rfq-manager'),
            'use_featured_image'    => __('Use as featured image', 'event-rfq-manager'),
            'insert_into_item'      => __('Insert into form', 'event-rfq-manager'),
            'uploaded_to_this_item' => __('Uploaded to this form', 'event-rfq-manager'),
            'items_list'            => __('Forms list', 'event-rfq-manager'),
            'items_list_navigation' => __('Forms list navigation', 'event-rfq-manager'),
            'filter_items_list'     => __('Filter forms list', 'event-rfq-manager'),
        );

        $args = array(
            'label'               => __('Form', 'event-rfq-manager'),
            'description'         => __('Form definitions', 'event-rfq-manager'),
            'labels'              => $labels,
            'supports'            => array('title'),
            'hierarchical'        => false,
            'public'              => false,
            'show_ui'             => false, // We use custom admin pages
            'show_in_menu'        => false,
            'menu_position'       => 25,
            'menu_icon'           => 'dashicons-feedback',
            'show_in_admin_bar'   => false,
            'show_in_nav_menus'   => false,
            'can_export'          => true,
            'has_archive'         => false,
            'exclude_from_search' => true,
            'publicly_queryable'  => false,
            'capability_type'     => 'post',
            'show_in_rest'        => false,
        );

        register_post_type(self::POST_TYPE, $args);
    }

    /**
     * Get all forms
     *
     * @param array $args Query arguments
     *
     * @return WP_Post[]
     */
    public static function get_all($args = array()) {
        $defaults = array(
            'post_type'      => self::POST_TYPE,
            'posts_per_page' => -1,
            'post_status'    => 'any',
            'orderby'        => 'title',
            'order'          => 'ASC',
        );

        return get_posts(wp_parse_args($args, $defaults));
    }

    /**
     * Get form count
     *
     * @return int
     */
    public static function get_count() {
        $counts = wp_count_posts(self::POST_TYPE);
        return isset($counts->publish) ? $counts->publish : 0;
    }

    /**
     * Create a new form
     *
     * @param string $title    Form title
     * @param array  $fields   Form fields
     * @param array  $settings Form settings
     *
     * @return int|WP_Error Form ID or error
     */
    public static function create($title, $fields = array(), $settings = array()) {
        $post_id = wp_insert_post(array(
            'post_type'   => self::POST_TYPE,
            'post_title'  => sanitize_text_field($title),
            'post_status' => 'publish',
        ), true);

        if (is_wp_error($post_id)) {
            return $post_id;
        }

        if (!empty($fields)) {
            update_post_meta($post_id, '_erfq_form_fields', $fields);
        }

        if (!empty($settings)) {
            update_post_meta($post_id, '_erfq_form_settings', $settings);
        }

        return $post_id;
    }

    /**
     * Duplicate a form
     *
     * @param int $form_id Form ID to duplicate
     *
     * @return int|WP_Error New form ID or error
     */
    public static function duplicate($form_id) {
        $form = get_post($form_id);
        if (!$form || $form->post_type !== self::POST_TYPE) {
            return new WP_Error('invalid_form', __('Invalid form ID.', 'event-rfq-manager'));
        }

        $new_title = $form->post_title . ' ' . __('(Copy)', 'event-rfq-manager');

        $new_id = wp_insert_post(array(
            'post_type'   => self::POST_TYPE,
            'post_title'  => $new_title,
            'post_status' => 'draft',
        ), true);

        if (is_wp_error($new_id)) {
            return $new_id;
        }

        // Copy meta data
        $meta_keys = array(
            '_erfq_form_fields',
            '_erfq_form_steps',
            '_erfq_form_settings',
        );

        foreach ($meta_keys as $key) {
            $value = get_post_meta($form_id, $key, true);
            if ($value) {
                update_post_meta($new_id, $key, $value);
            }
        }

        return $new_id;
    }
}
