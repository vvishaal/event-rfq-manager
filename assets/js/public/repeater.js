/**
 * Repeater Field JavaScript
 *
 * @package Event_RFQ_Manager
 */

(function($) {
    'use strict';

    var ERFQRepeater = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            var self = this;

            // Add row
            $(document).on('click', '.erfq-repeater-add', function(e) {
                e.preventDefault();
                self.addRow($(this).closest('.erfq-repeater-wrapper'));
            });

            // Remove row
            $(document).on('click', '.erfq-repeater-remove', function(e) {
                e.preventDefault();
                self.removeRow($(this).closest('.erfq-repeater-row'));
            });
        },

        addRow: function($wrapper) {
            var $rowsContainer = $wrapper.find('.erfq-repeater-rows');
            var $template = $wrapper.find('.erfq-repeater-row-template');
            var maxRows = parseInt($wrapper.data('max-rows')) || 10;
            var currentRows = $rowsContainer.find('.erfq-repeater-row').length;

            if (currentRows >= maxRows) {
                alert(erfqPublic.i18n.maxRowsReached || 'Maximum rows reached');
                return;
            }

            // Get template HTML and replace index placeholder
            var templateHtml = $template.html();
            var newIndex = currentRows;
            var newRowHtml = templateHtml.replace(/\{\{INDEX\}\}/g, newIndex);

            // Add new row
            var $newRow = $(newRowHtml);
            $rowsContainer.append($newRow);

            // Update add button visibility
            this.updateAddButton($wrapper);

            // Trigger event for other scripts
            $(document).trigger('erfq_repeater_row_added', [$newRow, $wrapper]);
        },

        removeRow: function($row) {
            var $wrapper = $row.closest('.erfq-repeater-wrapper');
            var $rowsContainer = $wrapper.find('.erfq-repeater-rows');
            var minRows = parseInt($wrapper.data('min-rows')) || 0;
            var currentRows = $rowsContainer.find('.erfq-repeater-row').length;

            if (currentRows <= minRows) {
                alert(erfqPublic.i18n.minRowsRequired || 'Minimum rows required');
                return;
            }

            // Animate removal
            $row.slideUp(200, function() {
                $row.remove();

                // Reindex remaining rows
                $rowsContainer.find('.erfq-repeater-row').each(function(index) {
                    var $thisRow = $(this);
                    $thisRow.attr('data-row-index', index);

                    // Update field names and IDs
                    $thisRow.find('input, select, textarea').each(function() {
                        var $field = $(this);
                        var name = $field.attr('name');
                        var id = $field.attr('id');

                        if (name) {
                            $field.attr('name', name.replace(/\]\[\d+\]\[/, '][' + index + ']['));
                        }
                        if (id) {
                            $field.attr('id', id.replace(/_\d+_/, '_' + index + '_'));
                        }
                    });

                    // Update label for attributes
                    $thisRow.find('label').each(function() {
                        var $label = $(this);
                        var forAttr = $label.attr('for');
                        if (forAttr) {
                            $label.attr('for', forAttr.replace(/_\d+_/, '_' + index + '_'));
                        }
                    });
                });

                // Update add button visibility
                ERFQRepeater.updateAddButton($wrapper);

                // Trigger event for other scripts
                $(document).trigger('erfq_repeater_row_removed', [$wrapper]);
            });
        },

        updateAddButton: function($wrapper) {
            var $rowsContainer = $wrapper.find('.erfq-repeater-rows');
            var $addButton = $wrapper.find('.erfq-repeater-add');
            var maxRows = parseInt($wrapper.data('max-rows')) || 10;
            var currentRows = $rowsContainer.find('.erfq-repeater-row').length;

            if (currentRows >= maxRows) {
                $addButton.hide();
            } else {
                $addButton.show();
            }
        }
    };

    $(document).ready(function() {
        ERFQRepeater.init();
    });

})(jQuery);
