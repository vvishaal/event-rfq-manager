<?php
/**
 * Entry Detail View
 *
 * @package Event_RFQ_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

$entry = $this->entry;
$form = $this->form;
$formatted_data = $this->get_formatted_data();
$files = $this->get_files();
$notes = $this->get_notes();

$back_url = admin_url('admin.php?page=erfq-entries');
if ($form) {
    $back_url = add_query_arg('form_id', $form->get_id(), $back_url);
}
?>
<div class="wrap erfq-entry-detail-wrap">
    <h1 class="wp-heading-inline">
        <?php printf(esc_html__('Entry #%d', 'event-rfq-manager'), $entry->get_id()); ?>
    </h1>
    <a href="<?php echo esc_url($back_url); ?>" class="page-title-action">
        <?php esc_html_e('Back to Entries', 'event-rfq-manager'); ?>
    </a>
    <hr class="wp-header-end">

    <div class="erfq-entry-detail">
        <div class="erfq-entry-main">
            <!-- Entry Data Card -->
            <div class="erfq-card erfq-entry-data">
                <div class="erfq-card-header">
                    <h2><?php esc_html_e('Submission Data', 'event-rfq-manager'); ?></h2>
                    <div class="erfq-card-actions">
                        <button type="button" class="button erfq-export-pdf" data-entry-id="<?php echo esc_attr($entry->get_id()); ?>">
                            <span class="dashicons dashicons-pdf"></span>
                            <?php esc_html_e('Export PDF', 'event-rfq-manager'); ?>
                        </button>
                    </div>
                </div>
                <div class="erfq-card-body">
                    <?php if (empty($formatted_data)) : ?>
                        <p class="erfq-no-data"><?php esc_html_e('No data available.', 'event-rfq-manager'); ?></p>
                    <?php else : ?>
                        <table class="erfq-data-table">
                            <tbody>
                                <?php foreach ($formatted_data as $item) : ?>
                                    <tr>
                                        <th scope="row"><?php echo esc_html($item['label']); ?></th>
                                        <td>
                                            <?php
                                            if ($item['type'] === 'file' && !empty($item['raw'])) {
                                                // Display file links
                                                $file_ids = (array) $item['raw'];
                                                foreach ($file_ids as $file_id) {
                                                    $file_url = wp_get_attachment_url($file_id);
                                                    $file_name = basename(get_attached_file($file_id));
                                                    if ($file_url) {
                                                        echo '<a href="' . esc_url($file_url) . '" target="_blank" class="erfq-file-link">';
                                                        echo '<span class="dashicons dashicons-media-default"></span> ';
                                                        echo esc_html($file_name);
                                                        echo '</a><br>';
                                                    }
                                                }
                                            } elseif (is_array($item['value'])) {
                                                echo esc_html(implode(', ', $item['value']));
                                            } else {
                                                echo nl2br(esc_html($item['value']));
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Files Card (if any) -->
            <?php if (!empty($files)) : ?>
                <div class="erfq-card erfq-entry-files">
                    <div class="erfq-card-header">
                        <h2><?php esc_html_e('Uploaded Files', 'event-rfq-manager'); ?></h2>
                    </div>
                    <div class="erfq-card-body">
                        <ul class="erfq-files-list">
                            <?php foreach ($files as $file_id) : ?>
                                <?php
                                $file_url = wp_get_attachment_url($file_id);
                                $file_path = get_attached_file($file_id);
                                $file_name = $file_path ? basename($file_path) : __('Unknown file', 'event-rfq-manager');
                                $file_type = wp_check_filetype($file_path);
                                $file_size = $file_path && file_exists($file_path) ? size_format(filesize($file_path)) : '';
                                ?>
                                <li class="erfq-file-item">
                                    <span class="erfq-file-icon dashicons dashicons-media-default"></span>
                                    <div class="erfq-file-info">
                                        <a href="<?php echo esc_url($file_url); ?>" target="_blank" class="erfq-file-name">
                                            <?php echo esc_html($file_name); ?>
                                        </a>
                                        <span class="erfq-file-meta">
                                            <?php echo esc_html($file_type['ext']); ?>
                                            <?php if ($file_size) : ?>
                                                &bull; <?php echo esc_html($file_size); ?>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                    <a href="<?php echo esc_url($file_url); ?>" class="button button-small" download>
                                        <?php esc_html_e('Download', 'event-rfq-manager'); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Notes Card -->
            <div class="erfq-card erfq-entry-notes">
                <div class="erfq-card-header">
                    <h2><?php esc_html_e('Notes', 'event-rfq-manager'); ?></h2>
                </div>
                <div class="erfq-card-body">
                    <div id="erfq-notes-list" class="erfq-notes-list">
                        <?php if (empty($notes)) : ?>
                            <p class="erfq-no-notes"><?php esc_html_e('No notes yet.', 'event-rfq-manager'); ?></p>
                        <?php else : ?>
                            <?php foreach ($notes as $note) : ?>
                                <div class="erfq-note">
                                    <div class="erfq-note-header">
                                        <span class="erfq-note-author"><?php echo esc_html($note['user_name']); ?></span>
                                        <span class="erfq-note-date"><?php echo esc_html($note['created_at']); ?></span>
                                    </div>
                                    <div class="erfq-note-content">
                                        <?php echo nl2br(esc_html($note['content'])); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <div class="erfq-add-note">
                        <textarea id="erfq-new-note" rows="3" placeholder="<?php esc_attr_e('Add a note...', 'event-rfq-manager'); ?>"></textarea>
                        <button type="button" id="erfq-add-note-btn" class="button button-primary">
                            <?php esc_html_e('Add Note', 'event-rfq-manager'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="erfq-entry-sidebar">
            <!-- Status Card -->
            <div class="erfq-card erfq-entry-status-card">
                <div class="erfq-card-header">
                    <h2><?php esc_html_e('Status', 'event-rfq-manager'); ?></h2>
                </div>
                <div class="erfq-card-body">
                    <select id="erfq-entry-status" class="erfq-status-select">
                        <?php foreach (ERFQ_Entry::$statuses as $value => $label) : ?>
                            <option value="<?php echo esc_attr($value); ?>" <?php selected($entry->get_status(), $value); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" id="erfq-update-status" class="button button-primary">
                        <?php esc_html_e('Update Status', 'event-rfq-manager'); ?>
                    </button>
                </div>
            </div>

            <!-- Info Card -->
            <div class="erfq-card erfq-entry-info">
                <div class="erfq-card-header">
                    <h2><?php esc_html_e('Details', 'event-rfq-manager'); ?></h2>
                </div>
                <div class="erfq-card-body">
                    <ul class="erfq-info-list">
                        <li>
                            <span class="erfq-info-label"><?php esc_html_e('Form', 'event-rfq-manager'); ?></span>
                            <span class="erfq-info-value">
                                <?php if ($form) : ?>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=erfq-forms&action=edit&form_id=' . $form->get_id())); ?>">
                                        <?php echo esc_html($form->get_title()); ?>
                                    </a>
                                <?php else : ?>
                                    <em><?php esc_html_e('(Deleted form)', 'event-rfq-manager'); ?></em>
                                <?php endif; ?>
                            </span>
                        </li>
                        <li>
                            <span class="erfq-info-label"><?php esc_html_e('Submitted', 'event-rfq-manager'); ?></span>
                            <span class="erfq-info-value"><?php echo esc_html($entry->get_submitted_at()); ?></span>
                        </li>
                        <li>
                            <span class="erfq-info-label"><?php esc_html_e('IP Address', 'event-rfq-manager'); ?></span>
                            <span class="erfq-info-value"><?php echo esc_html($entry->get_ip() ?: __('Unknown', 'event-rfq-manager')); ?></span>
                        </li>
                        <li>
                            <span class="erfq-info-label"><?php esc_html_e('User Agent', 'event-rfq-manager'); ?></span>
                            <span class="erfq-info-value erfq-user-agent"><?php echo esc_html($entry->get_user_agent() ?: __('Unknown', 'event-rfq-manager')); ?></span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Actions Card -->
            <div class="erfq-card erfq-entry-actions-card">
                <div class="erfq-card-header">
                    <h2><?php esc_html_e('Actions', 'event-rfq-manager'); ?></h2>
                </div>
                <div class="erfq-card-body">
                    <button type="button" class="button erfq-resend-notification" data-entry-id="<?php echo esc_attr($entry->get_id()); ?>">
                        <span class="dashicons dashicons-email"></span>
                        <?php esc_html_e('Resend Notification', 'event-rfq-manager'); ?>
                    </button>
                    <button type="button" class="button erfq-delete-entry" data-entry-id="<?php echo esc_attr($entry->get_id()); ?>">
                        <span class="dashicons dashicons-trash"></span>
                        <?php esc_html_e('Delete Entry', 'event-rfq-manager'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    var entryId = <?php echo esc_js($entry->get_id()); ?>;
    var nonce = '<?php echo esc_js(wp_create_nonce('erfq_admin_nonce')); ?>';

    // Update status
    $('#erfq-update-status').on('click', function() {
        var status = $('#erfq-entry-status').val();
        var $btn = $(this);

        $btn.prop('disabled', true);

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'erfq_update_entry_status',
                entry_id: entryId,
                status: status,
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('<?php echo esc_js(__('Status updated.', 'event-rfq-manager')); ?>');
                } else {
                    alert(response.data.message || '<?php echo esc_js(__('Error updating status.', 'event-rfq-manager')); ?>');
                }
            },
            complete: function() {
                $btn.prop('disabled', false);
            }
        });
    });

    // Add note
    $('#erfq-add-note-btn').on('click', function() {
        var note = $('#erfq-new-note').val().trim();
        if (!note) {
            return;
        }

        var $btn = $(this);
        $btn.prop('disabled', true);

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'erfq_add_entry_note',
                entry_id: entryId,
                note: note,
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    // Remove "no notes" message
                    $('.erfq-no-notes').remove();

                    // Add new note to list
                    var noteHtml = '<div class="erfq-note">' +
                        '<div class="erfq-note-header">' +
                        '<span class="erfq-note-author">' + response.data.note.user_name + '</span>' +
                        '<span class="erfq-note-date">' + response.data.note.created_at + '</span>' +
                        '</div>' +
                        '<div class="erfq-note-content">' + response.data.note.content.replace(/\n/g, '<br>') + '</div>' +
                        '</div>';

                    $('#erfq-notes-list').append(noteHtml);
                    $('#erfq-new-note').val('');
                } else {
                    alert(response.data.message || '<?php echo esc_js(__('Error adding note.', 'event-rfq-manager')); ?>');
                }
            },
            complete: function() {
                $btn.prop('disabled', false);
            }
        });
    });

    // Delete entry
    $('.erfq-delete-entry').on('click', function() {
        if (!confirm('<?php echo esc_js(__('Are you sure you want to delete this entry?', 'event-rfq-manager')); ?>')) {
            return;
        }

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'erfq_delete_entry',
                entry_id: entryId,
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    window.location.href = '<?php echo esc_js(admin_url('admin.php?page=erfq-entries')); ?>';
                } else {
                    alert(response.data.message || '<?php echo esc_js(__('Error deleting entry.', 'event-rfq-manager')); ?>');
                }
            }
        });
    });

    // Export PDF
    $('.erfq-export-pdf').on('click', function() {
        window.location.href = ajaxurl + '?action=erfq_export_entries&entry_ids[]=' + entryId + '&format=pdf&nonce=' + nonce;
    });
});
</script>
