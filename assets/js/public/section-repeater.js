/**
 * Section Repeater Frontend JavaScript
 *
 * Handles adding/removing section instances on the frontend form
 *
 * @package Event_RFQ_Manager
 */

(function($) {
    'use strict';

    var ERFQSectionRepeater = {
        init: function() {
            this.bindEvents();
            this.updateAllSectionStates();
        },

        bindEvents: function() {
            var self = this;

            // Add instance
            $(document).on('click', '.erfq-section-add-btn', function(e) {
                e.preventDefault();
                var sectionId = $(this).data('section-id');
                self.addInstance(sectionId);
            });

            // Remove instance
            $(document).on('click', '.erfq-instance-remove', function(e) {
                e.preventDefault();
                if (!$(this).prop('disabled')) {
                    var $instance = $(this).closest('.erfq-section-instance');
                    var $container = $(this).closest('.erfq-section-container');
                    self.removeInstance($instance, $container);
                }
            });
        },

        addInstance: function(sectionId) {
            var $container = $('#' + sectionId);
            var $template = $container.find('.erfq-section-template[data-section-id="' + sectionId + '"]');
            var $instancesContainer = $container.find('.erfq-section-instances');

            var maxInstances = parseInt($container.data('max-instances')) || 10;
            var currentCount = $instancesContainer.find('.erfq-section-instance').length;

            // Check max limit
            if (currentCount >= maxInstances) {
                alert(erfqPublic.i18n.maxEntriesReached || 'Maximum entries reached.');
                return;
            }

            // Get template HTML
            var templateHtml = $template.html();
            var newIndex = this.getNextIndex($instancesContainer);

            // Replace placeholders
            var newHtml = templateHtml
                .replace(/\{\{INDEX\}\}/g, newIndex)
                .replace(/\{\{NUM\}\}/g, newIndex + 1);

            // Create new instance element
            var $newInstance = $(newHtml);
            $newInstance.removeClass('erfq-instance-template');
            $newInstance.hide();

            // Append to container
            $instancesContainer.append($newInstance);

            // Slide down animation
            $newInstance.slideDown(200, function() {
                // Focus first input
                $newInstance.find('input, select, textarea').first().focus();
            });

            // Update states
            this.updateSectionState($container);

            // Trigger event
            $(document).trigger('erfq:section:added', [$newInstance, sectionId]);
        },

        removeInstance: function($instance, $container) {
            var minInstances = parseInt($container.data('min-instances')) || 1;
            var $instancesContainer = $container.find('.erfq-section-instances');
            var currentCount = $instancesContainer.find('.erfq-section-instance').length;

            // Check min limit
            if (currentCount <= minInstances) {
                return;
            }

            var sectionId = $container.data('section-id');

            // Slide up and remove
            $instance.slideUp(200, function() {
                $(this).remove();

                // Re-number remaining instances
                ERFQSectionRepeater.renumberInstances($container);

                // Update states
                ERFQSectionRepeater.updateSectionState($container);

                // Trigger event
                $(document).trigger('erfq:section:removed', [sectionId]);
            });
        },

        getNextIndex: function($instancesContainer) {
            var maxIndex = -1;
            $instancesContainer.find('.erfq-section-instance').each(function() {
                var index = parseInt($(this).data('instance-index'));
                if (!isNaN(index) && index > maxIndex) {
                    maxIndex = index;
                }
            });
            return maxIndex + 1;
        },

        renumberInstances: function($container) {
            var sectionId = $container.data('section-id');
            var $instances = $container.find('.erfq-section-instances .erfq-section-instance');

            $instances.each(function(newIndex) {
                var $instance = $(this);
                var oldIndex = $instance.data('instance-index');

                // Update data attribute
                $instance.attr('data-instance-index', newIndex);
                $instance.data('instance-index', newIndex);

                // Update display number
                $instance.find('.erfq-instance-num').text(newIndex + 1);

                // Update field names and IDs
                $instance.find('[name]').each(function() {
                    var name = $(this).attr('name');
                    if (name) {
                        // Replace [oldIndex] with [newIndex] in name
                        var newName = name.replace(
                            new RegExp('\\[' + oldIndex + '\\]'),
                            '[' + newIndex + ']'
                        );
                        $(this).attr('name', newName);
                    }
                });

                $instance.find('[id]').each(function() {
                    var id = $(this).attr('id');
                    if (id && id.indexOf(sectionId + '_') === 0) {
                        // Replace _oldIndex_ with _newIndex_ in ID
                        var newId = id.replace(
                            new RegExp('_' + oldIndex + '_'),
                            '_' + newIndex + '_'
                        );
                        $(this).attr('id', newId);

                        // Also update corresponding label
                        var $label = $instance.find('label[for="' + id + '"]');
                        if ($label.length) {
                            $label.attr('for', newId);
                        }
                    }
                });
            });
        },

        updateSectionState: function($container) {
            var minInstances = parseInt($container.data('min-instances')) || 1;
            var maxInstances = parseInt($container.data('max-instances')) || 10;
            var $instancesContainer = $container.find('.erfq-section-instances');
            var currentCount = $instancesContainer.find('.erfq-section-instance').length;

            // Update add button
            var $addBtn = $container.find('.erfq-section-add-btn');
            if (currentCount >= maxInstances) {
                $addBtn.prop('disabled', true).addClass('erfq-btn-disabled');
            } else {
                $addBtn.prop('disabled', false).removeClass('erfq-btn-disabled');
            }

            // Update remove buttons
            var $removeButtons = $instancesContainer.find('.erfq-instance-remove');
            if (currentCount <= minInstances) {
                $removeButtons.prop('disabled', true).addClass('erfq-btn-disabled');
            } else {
                $removeButtons.prop('disabled', false).removeClass('erfq-btn-disabled');
            }
        },

        updateAllSectionStates: function() {
            var self = this;
            $('.erfq-section-container[data-repeatable="true"]').each(function() {
                self.updateSectionState($(this));
            });
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        ERFQSectionRepeater.init();
    });

    // Expose for external use
    window.ERFQSectionRepeater = ERFQSectionRepeater;

})(jQuery);
