<?php
/**
 * Entry custom post type
 *
 * @package Event_RFQ_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ERFQ_Post_Type_Entry
 *
 * Registers and manages the erfq_entry post type
 */
class ERFQ_Post_Type_Entry {

    /**
     * Post type name
     */
    const POST_TYPE = 'erfq_entry';

    /**
     * Register the post type
     */
    public static function register() {
        $labels = array(
            'name'                  => _x('Entries', 'Post Type General Name', 'event-rfq-manager'),
            'singular_name'         => _x('Entry', 'Post Type Singular Name', 'event-rfq-manager'),
            'menu_name'             => __('Entries', 'event-rfq-manager'),
            'name_admin_bar'        => __('Entry', 'event-rfq-manager'),
            'archives'              => __('Entry Archives', 'event-rfq-manager'),
            'attributes'            => __('Entry Attributes', 'event-rfq-manager'),
            'parent_item_colon'     => __('Parent Entry:', 'event-rfq-manager'),
            'all_items'             => __('All Entries', 'event-rfq-manager'),
            'add_new_item'          => __('Add New Entry', 'event-rfq-manager'),
            'add_new'               => __('Add New', 'event-rfq-manager'),
            'new_item'              => __('New Entry', 'event-rfq-manager'),
            'edit_item'             => __('View Entry', 'event-rfq-manager'),
            'update_item'           => __('Update Entry', 'event-rfq-manager'),
            'view_item'             => __('View Entry', 'event-rfq-manager'),
            'view_items'            => __('View Entries', 'event-rfq-manager'),
            'search_items'          => __('Search Entry', 'event-rfq-manager'),
            'not_found'             => __('Not found', 'event-rfq-manager'),
            'not_found_in_trash'    => __('Not found in Trash', 'event-rfq-manager'),
        );

        $args = array(
            'label'               => __('Entry', 'event-rfq-manager'),
            'description'         => __('Form submission entries', 'event-rfq-manager'),
            'labels'              => $labels,
            'supports'            => array('title'),
            'hierarchical'        => false,
            'public'              => false,
            'show_ui'             => false, // We use custom admin pages
            'show_in_menu'        => false,
            'menu_position'       => 26,
            'menu_icon'           => 'dashicons-email-alt',
            'show_in_admin_bar'   => false,
            'show_in_nav_menus'   => false,
            'can_export'          => true,
            'has_archive'         => false,
            'exclude_from_search' => true,
            'publicly_queryable'  => false,
            'capability_type'     => 'post',
            'capabilities'        => array(
                'create_posts' => 'do_not_allow',
            ),
            'map_meta_cap'        => true,
            'show_in_rest'        => false,
        );

        register_post_type(self::POST_TYPE, $args);
    }

    /**
     * Get entries for a specific form
     *
     * @param int   $form_id Form ID
     * @param array $args    Query arguments
     *
     * @return WP_Post[]
     */
    public static function get_by_form($form_id, $args = array()) {
        $defaults = array(
            'post_type'      => self::POST_TYPE,
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'meta_query'     => array(
                array(
                    'key'   => '_erfq_entry_form_id',
                    'value' => $form_id,
                ),
            ),
            'orderby'        => 'date',
            'order'          => 'DESC',
        );

        return get_posts(wp_parse_args($args, $defaults));
    }

    /**
     * Get entry count for a form
     *
     * @param int $form_id Form ID
     *
     * @return int
     */
    public static function get_count_by_form($form_id) {
        global $wpdb;

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(p.ID) FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = %s
            AND p.post_status = 'publish'
            AND pm.meta_key = '_erfq_entry_form_id'
            AND pm.meta_value = %d",
            self::POST_TYPE,
            $form_id
        ));
    }

    /**
     * Get total entry count
     *
     * @return int
     */
    public static function get_total_count() {
        $counts = wp_count_posts(self::POST_TYPE);
        return isset($counts->publish) ? $counts->publish : 0;
    }

    /**
     * Get unread entry count
     *
     * @param int|null $form_id Form ID (null for all forms)
     *
     * @return int
     */
    public static function get_unread_count($form_id = null) {
        global $wpdb;

        $query = "
            SELECT COUNT(p.ID) FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = %s
            AND p.post_status = 'publish'
            AND pm.meta_key = '_erfq_entry_status'
            AND pm.meta_value = 'new'
        ";

        $params = array(self::POST_TYPE);

        if ($form_id) {
            $query .= "
                AND p.ID IN (
                    SELECT post_id FROM {$wpdb->postmeta}
                    WHERE meta_key = '_erfq_entry_form_id' AND meta_value = %d
                )
            ";
            $params[] = $form_id;
        }

        return (int) $wpdb->get_var($wpdb->prepare($query, $params));
    }

    /**
     * Delete all entries for a form
     *
     * @param int  $form_id      Form ID
     * @param bool $force_delete Whether to bypass trash
     *
     * @return int Number of entries deleted
     */
    public static function delete_by_form($form_id, $force_delete = false) {
        $entries = self::get_by_form($form_id);
        $count = 0;

        foreach ($entries as $entry) {
            if (wp_delete_post($entry->ID, $force_delete)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Bulk update entry status
     *
     * @param array  $entry_ids Entry IDs
     * @param string $status    New status
     *
     * @return int Number of entries updated
     */
    public static function bulk_update_status($entry_ids, $status) {
        $valid_statuses = array('new', 'read', 'processed', 'spam');
        if (!in_array($status, $valid_statuses, true)) {
            return 0;
        }

        $count = 0;
        foreach ($entry_ids as $entry_id) {
            $entry = get_post($entry_id);
            if ($entry && $entry->post_type === self::POST_TYPE) {
                update_post_meta($entry_id, '_erfq_entry_status', $status);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Bulk delete entries
     *
     * @param array $entry_ids    Entry IDs
     * @param bool  $force_delete Whether to bypass trash
     *
     * @return int Number of entries deleted
     */
    public static function bulk_delete($entry_ids, $force_delete = false) {
        $count = 0;
        foreach ($entry_ids as $entry_id) {
            $entry = get_post($entry_id);
            if ($entry && $entry->post_type === self::POST_TYPE) {
                // Delete associated files
                $files = get_post_meta($entry_id, '_erfq_entry_files', true);
                if (is_array($files)) {
                    foreach ($files as $field_files) {
                        foreach ((array) $field_files as $file) {
                            if (isset($file['path']) && file_exists($file['path'])) {
                                wp_delete_file($file['path']);
                            }
                        }
                    }
                }

                if (wp_delete_post($entry_id, $force_delete)) {
                    $count++;
                }
            }
        }

        return $count;
    }
}
