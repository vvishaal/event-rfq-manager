<?php
/**
 * Forms List View
 *
 * @package Event_RFQ_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get all forms
$forms = ERFQ_Form::get_all(array('post_status' => array('publish', 'draft')));
?>
<div class="wrap erfq-forms-list-wrap">
    <h1 class="wp-heading-inline"><?php esc_html_e('RFQ Forms', 'event-rfq-manager'); ?></h1>
    <a href="<?php echo esc_url(admin_url('admin.php?page=erfq-form-builder')); ?>" class="page-title-action">
        <?php esc_html_e('Add New', 'event-rfq-manager'); ?>
    </a>
    <hr class="wp-header-end">

    <?php if (empty($forms)) : ?>
        <div class="erfq-empty-forms">
            <div class="erfq-empty-icon">
                <span class="dashicons dashicons-feedback"></span>
            </div>
            <h2><?php esc_html_e('No Forms Yet', 'event-rfq-manager'); ?></h2>
            <p><?php esc_html_e('Create your first form to start collecting submissions.', 'event-rfq-manager'); ?></p>
            <a href="<?php echo esc_url(admin_url('admin.php?page=erfq-form-builder')); ?>" class="button button-primary button-hero">
                <?php esc_html_e('Create Your First Form', 'event-rfq-manager'); ?>
            </a>
        </div>
    <?php else : ?>
        <table class="wp-list-table widefat fixed striped erfq-forms-table">
            <thead>
                <tr>
                    <th scope="col" class="manage-column column-title column-primary"><?php esc_html_e('Form', 'event-rfq-manager'); ?></th>
                    <th scope="col" class="manage-column column-shortcode"><?php esc_html_e('Shortcode', 'event-rfq-manager'); ?></th>
                    <th scope="col" class="manage-column column-entries"><?php esc_html_e('Entries', 'event-rfq-manager'); ?></th>
                    <th scope="col" class="manage-column column-date"><?php esc_html_e('Date', 'event-rfq-manager'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($forms as $form) : ?>
                    <?php
                    $form_id = $form->get_id();
                    $edit_url = admin_url('admin.php?page=erfq-forms&action=edit&form_id=' . $form_id);
                    $entries_url = admin_url('admin.php?page=erfq-entries&form_id=' . $form_id);
                    $entries_count = ERFQ_Entry::count_by_form($form_id);
                    $status = $form->get_status();
                    ?>
                    <tr>
                        <td class="column-title column-primary" data-colname="<?php esc_attr_e('Form', 'event-rfq-manager'); ?>">
                            <strong>
                                <a href="<?php echo esc_url($edit_url); ?>" class="row-title">
                                    <?php echo esc_html($form->get_title() ?: __('(no title)', 'event-rfq-manager')); ?>
                                </a>
                                <?php if ($status === 'draft') : ?>
                                    <span class="post-state"> — <?php esc_html_e('Draft', 'event-rfq-manager'); ?></span>
                                <?php endif; ?>
                            </strong>
                            <div class="row-actions">
                                <span class="edit">
                                    <a href="<?php echo esc_url($edit_url); ?>"><?php esc_html_e('Edit', 'event-rfq-manager'); ?></a> |
                                </span>
                                <span class="entries">
                                    <a href="<?php echo esc_url($entries_url); ?>"><?php esc_html_e('Entries', 'event-rfq-manager'); ?></a> |
                                </span>
                                <span class="duplicate">
                                    <a href="#" class="erfq-duplicate-form" data-form-id="<?php echo esc_attr($form_id); ?>"><?php esc_html_e('Duplicate', 'event-rfq-manager'); ?></a> |
                                </span>
                                <span class="preview">
                                    <a href="#" class="erfq-preview-form" data-form-id="<?php echo esc_attr($form_id); ?>"><?php esc_html_e('Preview', 'event-rfq-manager'); ?></a> |
                                </span>
                                <span class="trash">
                                    <a href="#" class="erfq-delete-form" data-form-id="<?php echo esc_attr($form_id); ?>" data-form-title="<?php echo esc_attr($form->get_title()); ?>"><?php esc_html_e('Delete', 'event-rfq-manager'); ?></a>
                                </span>
                            </div>
                        </td>
                        <td class="column-shortcode" data-colname="<?php esc_attr_e('Shortcode', 'event-rfq-manager'); ?>">
                            <code class="erfq-shortcode-code">[erfq_form id="<?php echo esc_attr($form_id); ?>"]</code>
                            <button type="button" class="button button-small erfq-copy-shortcode" data-shortcode='[erfq_form id="<?php echo esc_attr($form_id); ?>"]' title="<?php esc_attr_e('Copy to Clipboard', 'event-rfq-manager'); ?>">
                                <span class="dashicons dashicons-clipboard"></span>
                            </button>
                        </td>
                        <td class="column-entries" data-colname="<?php esc_attr_e('Entries', 'event-rfq-manager'); ?>">
                            <a href="<?php echo esc_url($entries_url); ?>"><?php echo esc_html($entries_count); ?></a>
                        </td>
                        <td class="column-date" data-colname="<?php esc_attr_e('Date', 'event-rfq-manager'); ?>">
                            <?php echo esc_html(get_the_date('', $form_id)); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th scope="col" class="manage-column column-title column-primary"><?php esc_html_e('Form', 'event-rfq-manager'); ?></th>
                    <th scope="col" class="manage-column column-shortcode"><?php esc_html_e('Shortcode', 'event-rfq-manager'); ?></th>
                    <th scope="col" class="manage-column column-entries"><?php esc_html_e('Entries', 'event-rfq-manager'); ?></th>
                    <th scope="col" class="manage-column column-date"><?php esc_html_e('Date', 'event-rfq-manager'); ?></th>
                </tr>
            </tfoot>
        </table>
    <?php endif; ?>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Copy shortcode to clipboard
    $('.erfq-copy-shortcode').on('click', function() {
        var shortcode = $(this).data('shortcode') || $(this).siblings('code').text();
        navigator.clipboard.writeText(shortcode).then(function() {
            alert('<?php echo esc_js(__('Shortcode copied to clipboard!', 'event-rfq-manager')); ?>');
        });
    });

    // Delete form
    $('.erfq-delete-form').on('click', function(e) {
        e.preventDefault();
        var formId = $(this).data('form-id');
        var formTitle = $(this).data('form-title') || 'this form';

        if (confirm('<?php echo esc_js(__('Are you sure you want to delete', 'event-rfq-manager')); ?> "' + formTitle + '"? <?php echo esc_js(__('This will also delete all entries.', 'event-rfq-manager')); ?>')) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'erfq_delete_form',
                    form_id: formId,
                    nonce: '<?php echo esc_js(wp_create_nonce('erfq_admin_nonce')); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message || '<?php echo esc_js(__('Error deleting form.', 'event-rfq-manager')); ?>');
                    }
                }
            });
        }
    });

    // Duplicate form
    $('.erfq-duplicate-form').on('click', function(e) {
        e.preventDefault();
        var formId = $(this).data('form-id');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'erfq_duplicate_form',
                form_id: formId,
                nonce: '<?php echo esc_js(wp_create_nonce('erfq_admin_nonce')); ?>'
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message || '<?php echo esc_js(__('Error duplicating form.', 'event-rfq-manager')); ?>');
                }
            }
        });
    });
});
</script>
