/**
 * Form Validation JavaScript
 *
 * @package Event_RFQ_Manager
 */

(function($) {
    'use strict';

    window.ERFQValidation = {
        rules: {
            required: function(value, $field) {
                if ($field.attr('type') === 'checkbox') {
                    return $field.is(':checked');
                }
                if ($field.attr('type') === 'radio') {
                    var name = $field.attr('name');
                    return $('input[name="' + name + '"]:checked').length > 0;
                }
                return value !== '' && value !== null && value !== undefined;
            },

            email: function(value) {
                if (!value) return true;
                return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
            },

            phone: function(value) {
                if (!value) return true;
                return /^[\d\s\-\+\(\)]{7,20}$/.test(value);
            },

            url: function(value) {
                if (!value) return true;
                try {
                    new URL(value);
                    return true;
                } catch (e) {
                    return false;
                }
            },

            minLength: function(value, $field, param) {
                if (!value) return true;
                return value.length >= parseInt(param, 10);
            },

            maxLength: function(value, $field, param) {
                if (!value) return true;
                return value.length <= parseInt(param, 10);
            },

            min: function(value, $field, param) {
                if (!value) return true;
                return parseFloat(value) >= parseFloat(param);
            },

            max: function(value, $field, param) {
                if (!value) return true;
                return parseFloat(value) <= parseFloat(param);
            },

            pattern: function(value, $field) {
                if (!value) return true;
                var pattern = $field.attr('pattern');
                if (!pattern) return true;
                var regex = new RegExp('^' + pattern + '$');
                return regex.test(value);
            },

            fileSize: function(value, $field, param) {
                if (!$field[0].files || !$field[0].files.length) return true;
                var maxSize = parseInt(param, 10) * 1024 * 1024;
                for (var i = 0; i < $field[0].files.length; i++) {
                    if ($field[0].files[i].size > maxSize) {
                        return false;
                    }
                }
                return true;
            },

            fileType: function(value, $field, param) {
                if (!$field[0].files || !$field[0].files.length) return true;
                var allowedTypes = param.split(',').map(function(t) { return t.trim().toLowerCase(); });
                for (var i = 0; i < $field[0].files.length; i++) {
                    var ext = $field[0].files[i].name.split('.').pop().toLowerCase();
                    if (allowedTypes.indexOf(ext) === -1) {
                        return false;
                    }
                }
                return true;
            }
        },

        messages: {
            required: erfqPublic.i18n.requiredField,
            email: erfqPublic.i18n.invalidEmail,
            phone: erfqPublic.i18n.invalidPhone,
            url: 'Please enter a valid URL.',
            minLength: 'Please enter at least {0} characters.',
            maxLength: 'Please enter no more than {0} characters.',
            min: 'Please enter a value greater than or equal to {0}.',
            max: 'Please enter a value less than or equal to {0}.',
            pattern: 'Please enter a valid value.',
            fileSize: erfqPublic.i18n.fileTooLarge,
            fileType: erfqPublic.i18n.invalidFileType
        },

        validate: function($field) {
            var value = $field.val();
            var errors = [];

            // Required
            if ($field.prop('required') && !this.rules.required(value, $field)) {
                errors.push(this.messages.required);
            }

            // Email
            if ($field.attr('type') === 'email' && !this.rules.email(value)) {
                errors.push(this.messages.email);
            }

            // Phone
            if ($field.hasClass('erfq-phone-input') && !this.rules.phone(value)) {
                errors.push(this.messages.phone);
            }

            // URL
            if ($field.attr('type') === 'url' && !this.rules.url(value)) {
                errors.push(this.messages.url);
            }

            // Min/Max length
            var minLength = $field.attr('minlength');
            var maxLength = $field.attr('maxlength');
            if (minLength && !this.rules.minLength(value, $field, minLength)) {
                errors.push(this.messages.minLength.replace('{0}', minLength));
            }
            if (maxLength && !this.rules.maxLength(value, $field, maxLength)) {
                errors.push(this.messages.maxLength.replace('{0}', maxLength));
            }

            // Min/Max value
            var min = $field.attr('min');
            var max = $field.attr('max');
            if (min && !this.rules.min(value, $field, min)) {
                errors.push(this.messages.min.replace('{0}', min));
            }
            if (max && !this.rules.max(value, $field, max)) {
                errors.push(this.messages.max.replace('{0}', max));
            }

            // Pattern
            if ($field.attr('pattern') && !this.rules.pattern(value, $field)) {
                errors.push($field.data('pattern-error') || this.messages.pattern);
            }

            // File validations
            if ($field.attr('type') === 'file') {
                var maxFileSize = $field.data('max-size');
                var allowedTypes = $field.data('allowed-types');

                if (maxFileSize && !this.rules.fileSize(value, $field, maxFileSize)) {
                    errors.push(this.messages.fileSize);
                }
                if (allowedTypes && !this.rules.fileType(value, $field, allowedTypes)) {
                    errors.push(this.messages.fileType);
                }
            }

            return errors;
        },

        validateForm: function($form) {
            var self = this;
            var isValid = true;

            $form.find('.erfq-has-error').removeClass('erfq-has-error');
            $form.find('.erfq-field-error').remove();

            $form.find('.erfq-form-row:visible').each(function() {
                var $row = $(this);
                var $field = $row.find('.erfq-input, .erfq-textarea, .erfq-select, .erfq-file-input').first();

                if (!$field.length) {
                    // Check for checkbox/radio groups
                    $field = $row.find('input[type="checkbox"], input[type="radio"]').first();
                }

                if ($field.length) {
                    var errors = self.validate($field);
                    if (errors.length > 0) {
                        isValid = false;
                        $row.addClass('erfq-has-error');
                        $row.append('<span class="erfq-field-error">' + errors[0] + '</span>');
                    }
                }
            });

            return isValid;
        }
    };

})(jQuery);
