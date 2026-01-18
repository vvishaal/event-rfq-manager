/**
 * Event RFQ Admin JavaScript
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        
        // Handle status update via AJAX
        $(document).on('change', '#erfq-status', function() {
            var $select = $(this);
            var status = $select.val();
            var postId = $('input[name="post_ID"]').val();
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'erfq_update_status',
                    post_id: postId,
                    status: status,
                    nonce: $select.data('nonce')
                },
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        $('<div class="notice notice-success is-dismissible"><p>Status updated successfully!</p></div>')
                            .insertAfter('.wp-header-end')
                            .delay(3000)
                            .fadeOut();
                    }
                },
                error: function() {
                    alert('Error updating status. Please try again.');
                }
            });
        });

        // Add export button functionality (if needed in future)
        $('.erfq-export-btn').on('click', function(e) {
            e.preventDefault();
            var postId = $(this).data('post-id');
            window.location.href = ajaxurl + '?action=erfq_export&post_id=' + postId;
        });

    });

})(jQuery);