/**
 * Public Form Handler JavaScript
 *
 * @package Event_RFQ_Manager
 */

(function($) {
    'use strict';

    window.ERFQForm = {
        init: function() {
            this.bindEvents();
            this.initFileUploads();
        },

        bindEvents: function() {
            var self = this;

            // Form submission
            $(document).on('submit', '.erfq-form', function(e) {
                e.preventDefault();
                self.submitForm($(this));
            });

            // Real-time validation
            $(document).on('blur', '.erfq-form .erfq-input, .erfq-form .erfq-textarea, .erfq-form .erfq-select', function() {
                self.validateField($(this));
            });

            // Clear error on input
            $(document).on('input change', '.erfq-form .erfq-input, .erfq-form .erfq-textarea, .erfq-form .erfq-select', function() {
                $(this).closest('.erfq-form-row').removeClass('erfq-has-error');
                $(this).closest('.erfq-form-row').find('.erfq-field-error').remove();
            });
        },

        submitForm: function($form) {
            var self = this;
            var formId = $form.data('form-id');
            var $submitBtn = $form.find('.erfq-submit-btn');

            // Validate all fields
            if (!this.validateForm($form)) {
                // Scroll to first error
                var $firstError = $form.find('.erfq-has-error').first();
                if ($firstError.length) {
                    $('html, body').animate({
                        scrollTop: $firstError.offset().top - 100
                    }, 300);
                }
                return;
            }

            // Disable submit button
            $submitBtn.prop('disabled', true).addClass('erfq-loading');
            var originalText = $submitBtn.find('.erfq-submit-text').text();
            $submitBtn.find('.erfq-submit-text').text(erfqPublic.i18n.submitting);

            // Gather form data
            var formData = new FormData($form[0]);
            formData.append('action', 'erfq_submit_form');
            formData.append('form_id', formId);
            formData.append('nonce', erfqPublic.nonce);

            // Add reCAPTCHA token if enabled
            if (typeof erfqRecaptcha !== 'undefined' && erfqRecaptcha.siteKey) {
                grecaptcha.ready(function() {
                    grecaptcha.execute(erfqRecaptcha.siteKey, {action: 'submit'}).then(function(token) {
                        formData.append('recaptcha_token', token);
                        self.sendFormData($form, formData, $submitBtn, originalText);
                    });
                });
            } else {
                this.sendFormData($form, formData, $submitBtn, originalText);
            }
        },

        sendFormData: function($form, formData, $submitBtn, originalText) {
            var self = this;

            $.ajax({
                url: erfqPublic.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        self.showMessage($form, response.data.message, 'success');

                        // Reset form
                        $form[0].reset();
                        $form.find('.erfq-file-preview').empty();

                        // Redirect if specified
                        if (response.data.redirect) {
                            setTimeout(function() {
                                window.location.href = response.data.redirect;
                            }, 1500);
                        }

                        // Trigger custom event
                        $form.trigger('erfq:submitted', [response.data]);
                    } else {
                        self.showMessage($form, response.data.message, 'error');

                        // Show field-specific errors
                        if (response.data.errors) {
                            $.each(response.data.errors, function(fieldId, error) {
                                var $field = $form.find('[name="' + fieldId + '"]');
                                if ($field.length) {
                                    self.showFieldError($field, error);
                                }
                            });
                        }

                        $form.trigger('erfq:error', [response.data]);
                    }
                },
                error: function() {
                    self.showMessage($form, erfqPublic.i18n.error, 'error');
                },
                complete: function() {
                    $submitBtn.prop('disabled', false).removeClass('erfq-loading');
                    $submitBtn.find('.erfq-submit-text').text(originalText);
                }
            });
        },

        validateForm: function($form) {
            var self = this;
            var isValid = true;

            // Clear previous errors
            $form.find('.erfq-has-error').removeClass('erfq-has-error');
            $form.find('.erfq-field-error').remove();

            // Validate each visible required field
            $form.find('.erfq-form-row:visible [required]').each(function() {
                if (!self.validateField($(this))) {
                    isValid = false;
                }
            });

            // Validate email fields
            $form.find('.erfq-form-row:visible input[type="email"]').each(function() {
                if ($(this).val() && !self.isValidEmail($(this).val())) {
                    self.showFieldError($(this), erfqPublic.i18n.invalidEmail);
                    isValid = false;
                }
            });

            return isValid;
        },

        validateField: function($field) {
            var value = $field.val();
            var isRequired = $field.prop('required');
            var type = $field.attr('type') || $field.prop('tagName').toLowerCase();

            // Check required
            if (isRequired) {
                if (type === 'checkbox' || type === 'radio') {
                    var name = $field.attr('name');
                    if (!$('input[name="' + name + '"]:checked').length) {
                        this.showFieldError($field, erfqPublic.i18n.requiredField);
                        return false;
                    }
                } else if (!value || (Array.isArray(value) && !value.length)) {
                    this.showFieldError($field, erfqPublic.i18n.requiredField);
                    return false;
                }
            }

            // Validate email
            if (type === 'email' && value && !this.isValidEmail(value)) {
                this.showFieldError($field, erfqPublic.i18n.invalidEmail);
                return false;
            }

            // Validate phone
            if ($field.hasClass('erfq-phone-input') && value && !this.isValidPhone(value)) {
                this.showFieldError($field, erfqPublic.i18n.invalidPhone);
                return false;
            }

            // Validate pattern
            var pattern = $field.attr('pattern');
            if (pattern && value) {
                var regex = new RegExp('^' + pattern + '$');
                if (!regex.test(value)) {
                    this.showFieldError($field, $field.data('pattern-error') || 'Invalid format.');
                    return false;
                }
            }

            return true;
        },

        showFieldError: function($field, message) {
            var $row = $field.closest('.erfq-form-row');
            $row.addClass('erfq-has-error');

            if (!$row.find('.erfq-field-error').length) {
                $row.append('<span class="erfq-field-error">' + message + '</span>');
            }
        },

        showMessage: function($form, message, type) {
            // Remove existing messages
            $form.find('.erfq-message').remove();

            var $message = $('<div class="erfq-message erfq-message-' + type + '">' + message + '</div>');
            $form.prepend($message);

            // Scroll to message
            $('html, body').animate({
                scrollTop: $message.offset().top - 50
            }, 300);

            // Auto-hide success messages
            if (type === 'success') {
                setTimeout(function() {
                    $message.fadeOut(function() {
                        $(this).remove();
                    });
                }, 5000);
            }
        },

        isValidEmail: function(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        },

        isValidPhone: function(phone) {
            return /^[\d\s\-\+\(\)]{7,20}$/.test(phone);
        },

        // File upload handling
        initFileUploads: function() {
            var self = this;

            // Drag and drop
            $(document).on('dragover dragenter', '.erfq-file-dropzone', function(e) {
                e.preventDefault();
                $(this).addClass('erfq-dragover');
            });

            $(document).on('dragleave drop', '.erfq-file-dropzone', function(e) {
                e.preventDefault();
                $(this).removeClass('erfq-dragover');
            });

            $(document).on('drop', '.erfq-file-dropzone', function(e) {
                e.preventDefault();
                var files = e.originalEvent.dataTransfer.files;
                var $input = $(this).find('.erfq-file-input');
                self.handleFileSelect($input, files);
            });

            // Click to select
            $(document).on('click', '.erfq-file-dropzone', function(e) {
                if (!$(e.target).hasClass('erfq-file-input')) {
                    $(this).find('.erfq-file-input').click();
                }
            });

            // File input change
            $(document).on('change', '.erfq-file-input', function() {
                self.handleFileSelect($(this), this.files);
            });

            // Remove file
            $(document).on('click', '.erfq-file-remove', function(e) {
                e.preventDefault();
                self.removeFile($(this));
            });
        },

        handleFileSelect: function($input, files) {
            var self = this;
            var formId = $input.closest('.erfq-form').data('form-id');
            var fieldId = $input.data('field-id');
            var $container = $input.closest('.erfq-file-upload');
            var $preview = $container.find('.erfq-file-preview');
            var multiple = $input.prop('multiple');

            if (!multiple) {
                $preview.empty();
            }

            Array.from(files).forEach(function(file) {
                self.uploadFile(file, formId, fieldId, $preview);
            });
        },

        uploadFile: function(file, formId, fieldId, $preview) {
            var formData = new FormData();
            formData.append('action', 'erfq_upload_file');
            formData.append('file', file);
            formData.append('form_id', formId);
            formData.append('field_id', fieldId);
            formData.append('nonce', erfqPublic.nonce);

            // Show uploading state
            var $item = $('<div class="erfq-file-item erfq-uploading">' +
                '<span class="erfq-file-item-icon">&#128196;</span>' +
                '<span class="erfq-file-item-name">' + file.name + '</span>' +
                '<span class="erfq-file-item-size">Uploading...</span>' +
                '</div>');
            $preview.append($item);

            $.ajax({
                url: erfqPublic.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        $item.removeClass('erfq-uploading');
                        $item.data('attachment-id', response.data.attachment_id);
                        $item.find('.erfq-file-item-size').text(self.formatFileSize(file.size));
                        $item.append('<button type="button" class="erfq-file-remove">Remove</button>');
                        $item.append('<input type="hidden" name="' + fieldId + '[]" value="' + response.data.attachment_id + '">');
                    } else {
                        $item.remove();
                        alert(response.data.message);
                    }
                },
                error: function() {
                    $item.remove();
                    alert(erfqPublic.i18n.error);
                }
            });
        },

        removeFile: function($btn) {
            var $item = $btn.closest('.erfq-file-item');
            var attachmentId = $item.data('attachment-id');

            if (attachmentId) {
                $.ajax({
                    url: erfqPublic.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'erfq_remove_file',
                        attachment_id: attachmentId,
                        nonce: erfqPublic.nonce
                    }
                });
            }

            $item.remove();
        },

        formatFileSize: function(bytes) {
            if (bytes === 0) return '0 Bytes';
            var k = 1024;
            var sizes = ['Bytes', 'KB', 'MB', 'GB'];
            var i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
    };

    $(document).ready(function() {
        ERFQForm.init();
    });

})(jQuery);
