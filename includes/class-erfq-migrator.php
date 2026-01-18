<?php
/**
 * Handles migration from v1.0 to v2.0
 *
 * @package Event_RFQ_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ERFQ_Migrator
 *
 * Migrates data from v1.0 format to v2.0 format
 */
class ERFQ_Migrator {

    /**
     * Check if migration is needed and run if so
     */
    public function maybe_migrate() {
        // Skip if already migrated
        if (get_option('erfq_migrated_v1')) {
            return;
        }

        // Check if there are v1.0 entries to migrate
        $v1_entries = get_posts(array(
            'post_type'      => 'event_rfq',
            'posts_per_page' => 1,
            'post_status'    => 'any',
            'fields'         => 'ids',
        ));

        if (empty($v1_entries)) {
            // No v1.0 data, mark as migrated
            update_option('erfq_migrated_v1', true);
            return;
        }

        // Show migration notice
        add_action('admin_notices', array($this, 'migration_notice'));
    }

    /**
     * Display migration notice
     */
    public function migration_notice() {
        $migrate_url = wp_nonce_url(
            admin_url('admin-post.php?action=erfq_run_migration'),
            'erfq_migration'
        );

        $skip_url = wp_nonce_url(
            admin_url('admin-post.php?action=erfq_skip_migration'),
            'erfq_skip_migration'
        );

        ?>
        <div class="notice notice-warning">
            <p>
                <strong><?php esc_html_e('Event RFQ Manager: Migration Required', 'event-rfq-manager'); ?></strong>
            </p>
            <p>
                <?php esc_html_e('We detected data from version 1.0. Would you like to migrate your existing RFQ submissions to the new format?', 'event-rfq-manager'); ?>
            </p>
            <p>
                <a href="<?php echo esc_url($migrate_url); ?>" class="button button-primary">
                    <?php esc_html_e('Migrate Data', 'event-rfq-manager'); ?>
                </a>
                <a href="<?php echo esc_url($skip_url); ?>" class="button">
                    <?php esc_html_e('Skip Migration', 'event-rfq-manager'); ?>
                </a>
            </p>
        </div>
        <?php

        // Register handlers
        add_action('admin_post_erfq_run_migration', array($this, 'run_migration'));
        add_action('admin_post_erfq_skip_migration', array($this, 'skip_migration'));
    }

    /**
     * Run the migration process
     */
    public function run_migration() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to perform this action.', 'event-rfq-manager'));
        }

        check_admin_referer('erfq_migration');

        // Create a default form for migrated entries
        $default_form_id = $this->create_default_form();

        // Get all v1.0 entries
        $v1_entries = get_posts(array(
            'post_type'      => 'event_rfq',
            'posts_per_page' => -1,
            'post_status'    => 'any',
        ));

        $migrated_count = 0;

        foreach ($v1_entries as $v1_entry) {
            if ($this->migrate_entry($v1_entry, $default_form_id)) {
                $migrated_count++;
            }
        }

        // Mark migration as complete
        update_option('erfq_migrated_v1', true);
        update_option('erfq_migration_count', $migrated_count);

        // Redirect with success message
        wp_redirect(admin_url('admin.php?page=erfq-entries&migrated=' . $migrated_count));
        exit;
    }

    /**
     * Skip the migration
     */
    public function skip_migration() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to perform this action.', 'event-rfq-manager'));
        }

        check_admin_referer('erfq_skip_migration');

        update_option('erfq_migrated_v1', true);

        wp_redirect(admin_url('admin.php?page=erfq-forms'));
        exit;
    }

    /**
     * Create a default form for migrated entries
     *
     * @return int Form post ID
     */
    private function create_default_form() {
        // Check if default form already exists
        $existing = get_posts(array(
            'post_type'      => 'erfq_form',
            'posts_per_page' => 1,
            'meta_key'       => '_erfq_is_migration_form',
            'meta_value'     => '1',
            'fields'         => 'ids',
        ));

        if (!empty($existing)) {
            return $existing[0];
        }

        // Create form post
        $form_id = wp_insert_post(array(
            'post_type'   => 'erfq_form',
            'post_title'  => __('Event RFQ Form (Migrated)', 'event-rfq-manager'),
            'post_status' => 'publish',
        ));

        if (is_wp_error($form_id)) {
            return 0;
        }

        // Define form fields based on v1.0 structure
        $fields = $this->get_v1_form_fields();

        // Save form meta
        update_post_meta($form_id, '_erfq_form_fields', $fields);
        update_post_meta($form_id, '_erfq_is_migration_form', '1');
        update_post_meta($form_id, '_erfq_form_settings', array(
            'email_recipient' => get_option('erfq_admin_email', get_option('admin_email')),
            'success_message' => __('Your request has been submitted successfully!', 'event-rfq-manager'),
            'honeypot_enabled' => true,
        ));

        return $form_id;
    }

    /**
     * Get the v1.0 form field structure
     *
     * @return array Field definitions
     */
    private function get_v1_form_fields() {
        return array(
            array(
                'id'       => 'date_from',
                'type'     => 'date',
                'label'    => __('Date From', 'event-rfq-manager'),
                'required' => true,
            ),
            array(
                'id'       => 'date_to',
                'type'     => 'date',
                'label'    => __('Date To', 'event-rfq-manager'),
                'required' => true,
            ),
            array(
                'id'       => 'destination',
                'type'     => 'text',
                'label'    => __('Destination', 'event-rfq-manager'),
                'required' => true,
            ),
            array(
                'id'       => 'venues',
                'type'     => 'repeater',
                'label'    => __('Venue Names', 'event-rfq-manager'),
                'required' => false,
            ),
            array(
                'id'       => 'category',
                'type'     => 'select',
                'label'    => __('Category', 'event-rfq-manager'),
                'required' => false,
                'options'  => array(
                    '4star'       => __('4 Star', 'event-rfq-manager'),
                    '5star'       => __('5 Star', 'event-rfq-manager'),
                    '5star-luxury' => __('5 Star Luxury', 'event-rfq-manager'),
                ),
            ),
            array(
                'id'       => 'adults',
                'type'     => 'number',
                'label'    => __('Number of Adults', 'event-rfq-manager'),
                'required' => false,
            ),
            array(
                'id'       => 'children',
                'type'     => 'number',
                'label'    => __('Number of Children', 'event-rfq-manager'),
                'required' => false,
            ),
            array(
                'id'       => 'rooms',
                'type'     => 'checkbox',
                'label'    => __('Accommodation', 'event-rfq-manager'),
                'options'  => array(
                    'single' => __('Single', 'event-rfq-manager'),
                    'double' => __('Double', 'event-rfq-manager'),
                    'twin'   => __('Twin', 'event-rfq-manager'),
                    'triple' => __('Triple', 'event-rfq-manager'),
                    'suite'  => __('Suite', 'event-rfq-manager'),
                ),
            ),
            array(
                'id'       => 'meals',
                'type'     => 'checkbox',
                'label'    => __('Food & Beverage', 'event-rfq-manager'),
                'options'  => array(
                    'breakfast'   => __('Breakfast', 'event-rfq-manager'),
                    'lunch'       => __('Lunch', 'event-rfq-manager'),
                    'dinner'      => __('Dinner', 'event-rfq-manager'),
                    'gala-dinner' => __('Gala Dinner', 'event-rfq-manager'),
                    'bar'         => __('Bar', 'event-rfq-manager'),
                ),
            ),
            array(
                'id'       => 'conference_setup',
                'type'     => 'checkbox',
                'label'    => __('Conference Setup', 'event-rfq-manager'),
                'options'  => array(
                    'u-shape'   => __('U Shape', 'event-rfq-manager'),
                    'theatre'   => __('Theatre Style', 'event-rfq-manager'),
                    'boardroom' => __('Boardroom', 'event-rfq-manager'),
                    'classroom' => __('Classroom', 'event-rfq-manager'),
                    'cluster'   => __('Cluster', 'event-rfq-manager'),
                    'banquet'   => __('Banquet', 'event-rfq-manager'),
                    't-shape'   => __('T Shape', 'event-rfq-manager'),
                ),
            ),
            array(
                'id'       => 'av_requirements',
                'type'     => 'checkbox',
                'label'    => __('Audio Visual Requirements', 'event-rfq-manager'),
                'options'  => array(
                    'projector-screen'  => __('Projector & Screen', 'event-rfq-manager'),
                    'lapel-mics'        => __('Lapel Mics', 'event-rfq-manager'),
                    'hand-mics'         => __('Hand Mics', 'event-rfq-manager'),
                    'infrared-pointers' => __('Infrared Pointers', 'event-rfq-manager'),
                    'music-sound'       => __('Music/Sound System', 'event-rfq-manager'),
                    'dance-floor'       => __('Dance Floor', 'event-rfq-manager'),
                    'dj'                => __('DJ', 'event-rfq-manager'),
                    'entertainers'      => __('Entertainers', 'event-rfq-manager'),
                ),
            ),
            array(
                'id'       => 'special_services',
                'type'     => 'textarea',
                'label'    => __('Special Add-On Services', 'event-rfq-manager'),
                'required' => false,
            ),
            array(
                'id'       => 'contact_name',
                'type'     => 'text',
                'label'    => __('Name', 'event-rfq-manager'),
                'required' => true,
            ),
            array(
                'id'       => 'contact_designation',
                'type'     => 'text',
                'label'    => __('Designation', 'event-rfq-manager'),
                'required' => false,
            ),
            array(
                'id'       => 'contact_company',
                'type'     => 'text',
                'label'    => __('Company Name', 'event-rfq-manager'),
                'required' => false,
            ),
            array(
                'id'       => 'contact_address',
                'type'     => 'textarea',
                'label'    => __('Address', 'event-rfq-manager'),
                'required' => false,
            ),
            array(
                'id'       => 'contact_mobile',
                'type'     => 'phone',
                'label'    => __('Mobile Number', 'event-rfq-manager'),
                'required' => false,
            ),
            array(
                'id'       => 'contact_email',
                'type'     => 'email',
                'label'    => __('Email', 'event-rfq-manager'),
                'required' => true,
            ),
        );
    }

    /**
     * Migrate a single v1.0 entry to v2.0 format
     *
     * @param WP_Post $v1_entry The v1.0 entry post
     * @param int     $form_id  The form ID to associate with
     *
     * @return bool Whether migration was successful
     */
    private function migrate_entry($v1_entry, $form_id) {
        // Gather all meta data from v1.0 entry
        $entry_data = array(
            'date_from'           => get_post_meta($v1_entry->ID, '_erfq_date_from', true),
            'date_to'             => get_post_meta($v1_entry->ID, '_erfq_date_to', true),
            'destination'         => get_post_meta($v1_entry->ID, '_erfq_destination', true),
            'venues'              => get_post_meta($v1_entry->ID, '_erfq_venues', true),
            'category'            => get_post_meta($v1_entry->ID, '_erfq_category', true),
            'adults'              => get_post_meta($v1_entry->ID, '_erfq_adults', true),
            'children'            => get_post_meta($v1_entry->ID, '_erfq_children', true),
            'rooms'               => get_post_meta($v1_entry->ID, '_erfq_rooms', true),
            'meals'               => get_post_meta($v1_entry->ID, '_erfq_meals', true),
            'conference_setup'    => get_post_meta($v1_entry->ID, '_erfq_conference_setup', true),
            'av_requirements'     => get_post_meta($v1_entry->ID, '_erfq_av_requirements', true),
            'arrival_transfers'   => get_post_meta($v1_entry->ID, '_erfq_arrival_transfers', true),
            'departure_transfers' => get_post_meta($v1_entry->ID, '_erfq_departure_transfers', true),
            'sightseeing'         => get_post_meta($v1_entry->ID, '_erfq_sightseeing', true),
            'special_services'    => get_post_meta($v1_entry->ID, '_erfq_special_services', true),
            'contact_name'        => get_post_meta($v1_entry->ID, '_erfq_contact_name', true),
            'contact_designation' => get_post_meta($v1_entry->ID, '_erfq_contact_designation', true),
            'contact_company'     => get_post_meta($v1_entry->ID, '_erfq_contact_company', true),
            'contact_address'     => get_post_meta($v1_entry->ID, '_erfq_contact_address', true),
            'contact_mobile'      => get_post_meta($v1_entry->ID, '_erfq_contact_mobile', true),
            'contact_email'       => get_post_meta($v1_entry->ID, '_erfq_contact_email', true),
        );

        // Map v1.0 status to v2.0 status
        $v1_status = get_post_meta($v1_entry->ID, '_erfq_status', true);
        $v2_status = ($v1_status === 'processed') ? 'processed' : 'new';

        // Create new v2.0 entry
        $entry_id = wp_insert_post(array(
            'post_type'   => 'erfq_entry',
            'post_title'  => $v1_entry->post_title,
            'post_status' => 'publish',
            'post_date'   => $v1_entry->post_date,
        ));

        if (is_wp_error($entry_id)) {
            return false;
        }

        // Save entry meta
        update_post_meta($entry_id, '_erfq_entry_form_id', $form_id);
        update_post_meta($entry_id, '_erfq_entry_data', $entry_data);
        update_post_meta($entry_id, '_erfq_entry_status', $v2_status);
        update_post_meta($entry_id, '_erfq_entry_submitted_at', get_post_meta($v1_entry->ID, '_erfq_submission_date', true));
        update_post_meta($entry_id, '_erfq_entry_migrated_from', $v1_entry->ID);

        return true;
    }
}
