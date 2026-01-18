<?php
/**
 * Entries List Table class
 *
 * @package Event_RFQ_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Class ERFQ_Entries_List_Table
 *
 * Displays entries in a WordPress list table
 */
class ERFQ_Entries_List_Table extends WP_List_Table {

    /**
     * Form ID filter
     *
     * @var int
     */
    protected $form_id = 0;

    /**
     * Status filter
     *
     * @var string
     */
    protected $status = '';

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct(array(
            'singular' => 'entry',
            'plural'   => 'entries',
            'ajax'     => false,
        ));

        $this->form_id = isset($_GET['form_id']) ? absint($_GET['form_id']) : 0;
        $this->status = isset($_GET['status']) ? sanitize_key($_GET['status']) : '';
    }

    /**
     * Get columns
     *
     * @return array
     */
    public function get_columns() {
        return array(
            'cb'           => '<input type="checkbox" />',
            'entry_id'     => __('ID', 'event-rfq-manager'),
            'form'         => __('Form', 'event-rfq-manager'),
            'primary_field' => __('Primary Field', 'event-rfq-manager'),
            'status'       => __('Status', 'event-rfq-manager'),
            'submitted_at' => __('Submitted', 'event-rfq-manager'),
        );
    }

    /**
     * Get sortable columns
     *
     * @return array
     */
    public function get_sortable_columns() {
        return array(
            'entry_id'     => array('entry_id', false),
            'submitted_at' => array('submitted_at', true),
        );
    }

    /**
     * Prepare items for display
     */
    public function prepare_items() {
        $per_page = 20;
        $current_page = $this->get_pagenum();
        $orderby = isset($_GET['orderby']) ? sanitize_key($_GET['orderby']) : 'date';
        $order = isset($_GET['order']) ? sanitize_key($_GET['order']) : 'DESC';
        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

        // Build query args
        $args = array(
            'post_type'      => 'erfq_entry',
            'posts_per_page' => $per_page,
            'paged'          => $current_page,
            'post_status'    => 'publish',
            'orderby'        => $orderby === 'entry_id' ? 'ID' : 'date',
            'order'          => $order,
        );

        // Meta query for filters
        $meta_query = array();

        if ($this->form_id) {
            $meta_query[] = array(
                'key'   => '_erfq_entry_form_id',
                'value' => $this->form_id,
            );
        }

        if ($this->status) {
            $meta_query[] = array(
                'key'   => '_erfq_entry_status',
                'value' => $this->status,
            );
        }

        if (!empty($meta_query)) {
            $args['meta_query'] = $meta_query;
        }

        // Search
        if ($search) {
            $args['s'] = $search;
        }

        // Execute query
        $query = new WP_Query($args);

        $this->items = array();
        foreach ($query->posts as $post) {
            $this->items[] = new ERFQ_Entry($post);
        }

        // Pagination
        $this->set_pagination_args(array(
            'total_items' => $query->found_posts,
            'per_page'    => $per_page,
            'total_pages' => ceil($query->found_posts / $per_page),
        ));

        // Set column headers
        $this->_column_headers = array(
            $this->get_columns(),
            array(),
            $this->get_sortable_columns(),
        );
    }

    /**
     * Checkbox column
     *
     * @param ERFQ_Entry $item Entry object
     *
     * @return string
     */
    public function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="entry_ids[]" value="%s" />',
            $item->get_id()
        );
    }

    /**
     * Entry ID column
     *
     * @param ERFQ_Entry $item Entry object
     *
     * @return string
     */
    public function column_entry_id($item) {
        $view_url = admin_url('admin.php?page=erfq-entries&action=view&entry_id=' . $item->get_id());

        $actions = array(
            'view'   => sprintf('<a href="%s">%s</a>', $view_url, __('View', 'event-rfq-manager')),
            'delete' => sprintf(
                '<a href="%s" class="erfq-delete-entry" data-id="%d">%s</a>',
                wp_nonce_url(admin_url('admin.php?page=erfq-entries&action=delete&entry_id=' . $item->get_id()), 'erfq_delete_entry'),
                $item->get_id(),
                __('Delete', 'event-rfq-manager')
            ),
        );

        return sprintf(
            '<a href="%s"><strong>#%d</strong></a>%s',
            $view_url,
            $item->get_id(),
            $this->row_actions($actions)
        );
    }

    /**
     * Form column
     *
     * @param ERFQ_Entry $item Entry object
     *
     * @return string
     */
    public function column_form($item) {
        $form = $item->get_form();
        if (!$form) {
            return '<em>' . __('(Deleted form)', 'event-rfq-manager') . '</em>';
        }

        $filter_url = add_query_arg('form_id', $form->get_id(), admin_url('admin.php?page=erfq-entries'));
        return sprintf('<a href="%s">%s</a>', $filter_url, esc_html($form->get_title()));
    }

    /**
     * Primary field column (email or first field)
     *
     * @param ERFQ_Entry $item Entry object
     *
     * @return string
     */
    public function column_primary_field($item) {
        $data = $item->get_data();

        // Look for email first
        foreach ($data as $value) {
            if (is_string($value) && is_email($value)) {
                return esc_html($value);
            }
        }

        // Otherwise show first non-empty value
        foreach ($data as $value) {
            if (!empty($value) && is_string($value)) {
                return esc_html(wp_trim_words($value, 10));
            }
        }

        return '-';
    }

    /**
     * Status column
     *
     * @param ERFQ_Entry $item Entry object
     *
     * @return string
     */
    public function column_status($item) {
        $status = $item->get_status();
        $label = $item->get_status_label();

        $class = 'erfq-status erfq-status-' . $status;

        return sprintf('<span class="%s">%s</span>', esc_attr($class), esc_html($label));
    }

    /**
     * Submitted at column
     *
     * @param ERFQ_Entry $item Entry object
     *
     * @return string
     */
    public function column_submitted_at($item) {
        return $item->get_submitted_at();
    }

    /**
     * Default column handler
     *
     * @param ERFQ_Entry $item        Entry object
     * @param string     $column_name Column name
     *
     * @return string
     */
    public function column_default($item, $column_name) {
        return '';
    }

    /**
     * Get bulk actions
     *
     * @return array
     */
    public function get_bulk_actions() {
        return array(
            'mark_read'      => __('Mark as Read', 'event-rfq-manager'),
            'mark_processed' => __('Mark as Processed', 'event-rfq-manager'),
            'export_csv'     => __('Export to CSV', 'event-rfq-manager'),
            'delete'         => __('Delete', 'event-rfq-manager'),
        );
    }

    /**
     * Process bulk actions
     */
    public function process_bulk_action() {
        $action = $this->current_action();

        if (!$action) {
            return;
        }

        $entry_ids = isset($_REQUEST['entry_ids']) ? array_map('absint', $_REQUEST['entry_ids']) : array();

        if (empty($entry_ids)) {
            return;
        }

        switch ($action) {
            case 'mark_read':
                ERFQ_Post_Type_Entry::bulk_update_status($entry_ids, 'read');
                break;

            case 'mark_processed':
                ERFQ_Post_Type_Entry::bulk_update_status($entry_ids, 'processed');
                break;

            case 'export_csv':
                $export = new ERFQ_Export_Service();
                $export->download_csv($entry_ids, $this->form_id);
                break;

            case 'delete':
                ERFQ_Post_Type_Entry::bulk_delete($entry_ids);
                break;
        }
    }

    /**
     * Extra table navigation
     *
     * @param string $which Top or bottom
     */
    public function extra_tablenav($which) {
        if ($which !== 'top') {
            return;
        }

        // Get all forms for filter dropdown
        $forms = ERFQ_Form::get_all(array('post_status' => 'publish'));

        echo '<div class="alignleft actions">';

        // Form filter
        echo '<select name="form_id">';
        echo '<option value="">' . esc_html__('All Forms', 'event-rfq-manager') . '</option>';
        foreach ($forms as $form) {
            printf(
                '<option value="%d" %s>%s</option>',
                $form->get_id(),
                selected($this->form_id, $form->get_id(), false),
                esc_html($form->get_title())
            );
        }
        echo '</select>';

        // Status filter
        echo '<select name="status">';
        echo '<option value="">' . esc_html__('All Statuses', 'event-rfq-manager') . '</option>';
        foreach (ERFQ_Entry::$statuses as $value => $label) {
            printf(
                '<option value="%s" %s>%s</option>',
                esc_attr($value),
                selected($this->status, $value, false),
                esc_html($label)
            );
        }
        echo '</select>';

        submit_button(__('Filter', 'event-rfq-manager'), '', 'filter_action', false);

        echo '</div>';
    }

    /**
     * Display status tabs
     *
     * @return array
     */
    public function get_views() {
        $counts = ERFQ_Entry::count_by_status($this->form_id ?: null);
        $current = $this->status ?: 'all';

        $views = array();

        // All
        $views['all'] = sprintf(
            '<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
            remove_query_arg('status'),
            $current === 'all' ? 'current' : '',
            __('All', 'event-rfq-manager'),
            $counts['total']
        );

        // Individual statuses
        foreach (ERFQ_Entry::$statuses as $status => $label) {
            if (!isset($counts[$status]) || $counts[$status] === 0) {
                continue;
            }

            $views[$status] = sprintf(
                '<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
                add_query_arg('status', $status),
                $current === $status ? 'current' : '',
                $label,
                $counts[$status]
            );
        }

        return $views;
    }

    /**
     * No items message
     */
    public function no_items() {
        esc_html_e('No entries found.', 'event-rfq-manager');
    }
}
