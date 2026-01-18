<?php
/**
 * Entries List View
 *
 * @package Event_RFQ_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

// Create list table instance
$list_table = new ERFQ_Entries_List_Table();
$list_table->process_bulk_action();
$list_table->prepare_items();
?>
<div class="wrap erfq-entries-list-wrap">
    <h1 class="wp-heading-inline"><?php esc_html_e('Form Entries', 'event-rfq-manager'); ?></h1>
    <hr class="wp-header-end">

    <form id="erfq-entries-form" method="get">
        <input type="hidden" name="page" value="erfq-entries">

        <?php
        $list_table->views();
        $list_table->search_box(__('Search Entries', 'event-rfq-manager'), 'entry');
        $list_table->display();
        ?>
    </form>
</div>

<style>
.erfq-entries-list-wrap .erfq-status {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 500;
}

.erfq-status-new {
    background: #e1f5fe;
    color: #0277bd;
}

.erfq-status-read {
    background: #fff3e0;
    color: #ef6c00;
}

.erfq-status-processed {
    background: #e8f5e9;
    color: #2e7d32;
}

.erfq-status-spam {
    background: #ffebee;
    color: #c62828;
}

.erfq-entries-list-wrap .column-entry_id {
    width: 80px;
}

.erfq-entries-list-wrap .column-status {
    width: 100px;
}

.erfq-entries-list-wrap .column-submitted_at {
    width: 150px;
}
</style>
