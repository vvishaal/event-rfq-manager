/**
 * Form Builder Admin JavaScript
 *
 * @package Event_RFQ_Manager
 */

(function($) {
    'use strict';

    window.ERFQFormBuilder = {
        formId: 0,
        formData: { title: '', fields: [], steps: [], settings: {} },
        selectedFieldId: null,
        hasUnsavedChanges: false,

        init: function() {
            var self = this;
            this.formId = parseInt($('#erfq-form-builder').data('form-id')) || 0;

            var initialData = $('#erfq-form-data').val();
            if (initialData) {
                try { this.formData = JSON.parse(initialData); } catch (e) {}
            }

            this.initFieldPalette();
            this.initFieldsContainer();
            this.initSettingsPanel();
            this.initTopBar();

            this.renderFields();

            $(window).on('beforeunload', function() {
                if (self.hasUnsavedChanges) return erfqAdmin.i18n.unsavedChanges;
            });
        },

        initFieldPalette: function() {
            var self = this;

            $('.erfq-palette-field').draggable({
                helper: 'clone',
                connectToSortable: '#erfq-fields-container',
                revert: 'invalid',
                cursor: 'grabbing'
            });

            $('.erfq-palette-field').on('click', function() {
                self.addField($(this).data('type'));
            });

            $('#erfq-field-search').on('keyup', function() {
                var search = $(this).val().toLowerCase();
                $('.erfq-palette-field').each(function() {
                    var name = $(this).find('.erfq-field-name').text().toLowerCase();
                    $(this).toggle(name.indexOf(search) !== -1);
                });
                $('.erfq-field-category').each(function() {
                    $(this).toggle($(this).find('.erfq-palette-field:visible').length > 0);
                });
            });
        },

        initFieldsContainer: function() {
            var self = this;
            $('#erfq-fields-container').sortable({
                handle: '.erfq-field-drag',
                placeholder: 'erfq-sortable-placeholder',
                receive: function(e, ui) {
                    var type = ui.item.data('type');
                    if (type && ui.item.hasClass('erfq-palette-field')) {
                        var index = ui.item.index();
                        ui.item.remove();
                        self.addField(type, index);
                    }
                },
                update: function(e, ui) {
                    if (!ui.sender) self.updateFieldOrder();
                }
            });
        },

        initSettingsPanel: function() {
            var self = this;

            $('.erfq-panel-tab').on('click', function() {
                var panel = $(this).data('panel');
                $('.erfq-panel-tab').removeClass('active');
                $(this).addClass('active');
                $('.erfq-panel-content').hide().removeClass('active');
                $('[data-panel="' + panel + '"]').show().addClass('active');
            });

            $(document).on('change', '.erfq-form-setting', function() {
                var setting = $(this).data('setting');
                var value = $(this).is(':checkbox') ? $(this).is(':checked') : $(this).val();
                self.formData.settings[setting] = value;
                self.hasUnsavedChanges = true;
            });

            $(document).on('change', '.erfq-field-setting', function() {
                if (!self.selectedFieldId) return;
                var setting = $(this).data('setting');
                var value = $(this).is(':checkbox') ? $(this).is(':checked') : $(this).val();
                self.updateFieldSetting(self.selectedFieldId, setting, value);
                if (setting === 'conditional_enabled') {
                    $('.erfq-conditional-settings').toggle(value);
                }
            });

            $(document).on('click', '.erfq-add-option', function() { self.addOption(); });
            $(document).on('click', '.erfq-remove-option', function() {
                self.removeOption($(this).closest('.erfq-option-row').data('index'));
            });
            $(document).on('change', '.erfq-option-label, .erfq-option-value', function() { self.updateOptions(); });

            $(document).on('click', '.erfq-duplicate-field', function() {
                if (self.selectedFieldId) self.duplicateField(self.selectedFieldId);
            });
            $(document).on('click', '.erfq-delete-field', function() {
                if (self.selectedFieldId && confirm(erfqAdmin.i18n.confirmDelete)) {
                    self.deleteField(self.selectedFieldId);
                }
            });

            $(document).on('click', '.erfq-add-condition', function() { self.addCondition(); });
            $(document).on('click', '.erfq-remove-rule', function() {
                self.removeCondition($(this).closest('.erfq-conditional-rule').data('rule-index'));
            });
            $(document).on('change', '.erfq-conditional-rule select, .erfq-conditional-rule input', function() {
                self.updateConditions();
            });

            // Repeater sub-fields management
            $(document).on('click', '.erfq-add-sub-field', function() { self.addSubField(); });
            $(document).on('click', '.erfq-remove-sub-field', function() {
                self.removeSubField($(this).closest('.erfq-sub-field-row').data('index'));
            });
            $(document).on('change', '.erfq-sub-field-row input, .erfq-sub-field-row select', function() {
                self.updateSubFields();
            });
        },

        initTopBar: function() {
            var self = this;
            $('#erfq-form-title').on('change keyup', function() {
                self.formData.title = $(this).val();
                self.hasUnsavedChanges = true;
            });
            $('.erfq-save-btn').on('click', function() { self.saveForm(); });
            $('.erfq-preview-btn').on('click', function() { self.previewForm(); });
            $('.erfq-copy-shortcode').on('click', function() {
                navigator.clipboard.writeText($('#erfq-shortcode').text());
            });
        },

        addField: function(type, index) {
            var fieldMeta = erfqAdmin.fieldTypes[type];
            if (!fieldMeta) return;

            var field = {
                id: type + '_' + Date.now(),
                type: type,
                label: fieldMeta.label,
                placeholder: '',
                description: '',
                required: false,
                width: '100',
                css_class: '',
                options: fieldMeta.supports_options ? [{label: 'Option 1', value: 'option_1'}] : undefined,
                conditional_enabled: false,
                conditional_action: 'show',
                conditional_logic: 'all',
                conditional_rules: []
            };

            // Add type-specific defaults
            if (type === 'section') {
                field.heading_tag = 'h3';
                field.show_divider = true;
                field.label = 'Section Title';
            } else if (type === 'html') {
                field.html_content = '<p>Enter your custom HTML content here.</p>';
                field.label = 'HTML Content';
            } else if (type === 'repeater') {
                field.sub_fields = [{id: 'item', type: 'text', label: 'Item'}];
                field.min_rows = 0;
                field.max_rows = 10;
                field.add_button_text = '+ Add Row';
            }

            if (typeof index !== 'undefined') {
                this.formData.fields.splice(index, 0, field);
            } else {
                this.formData.fields.push(field);
            }

            this.hasUnsavedChanges = true;
            this.renderFields();
            this.selectField(field.id);
        },

        renderFields: function() {
            var self = this;
            var $container = $('#erfq-fields-container').empty();

            if (this.formData.fields.length === 0) {
                $container.html('<div class="erfq-empty-state"><span class="dashicons dashicons-plus-alt2"></span><p>Drag fields here to build your form</p></div>');
                return;
            }

            this.formData.fields.forEach(function(field) {
                $container.append(self.renderFieldRow(field));
            });
        },

        renderFieldRow: function(field) {
            var self = this;
            var fieldMeta = erfqAdmin.fieldTypes[field.type] || {};
            var template = wp.template('erfq-field-row');
            var $row = $(template($.extend({}, field, {
                icon: fieldMeta.icon || 'dashicons-admin-generic',
                type_label: fieldMeta.label || field.type
            })));

            if (field.id === this.selectedFieldId) $row.addClass('erfq-selected');

            $row.find('.erfq-field-header').on('click', function() { self.selectField(field.id); });
            $row.find('.erfq-field-edit').on('click', function(e) { e.stopPropagation(); self.selectField(field.id); });
            $row.find('.erfq-field-duplicate').on('click', function(e) { e.stopPropagation(); self.duplicateField(field.id); });
            $row.find('.erfq-field-delete').on('click', function(e) {
                e.stopPropagation();
                if (confirm(erfqAdmin.i18n.confirmDelete)) self.deleteField(field.id);
            });

            this.renderFieldPreview($row.find('.erfq-field-preview'), field);
            return $row;
        },

        renderFieldPreview: function($container, field) {
            var html = '';
            switch (field.type) {
                case 'text': case 'email': case 'phone': case 'number':
                    html = '<input type="text" disabled placeholder="' + (field.placeholder || field.label) + '">';
                    break;
                case 'textarea':
                    html = '<textarea disabled placeholder="' + (field.placeholder || field.label) + '"></textarea>';
                    break;
                case 'select':
                    html = '<select disabled><option>' + (field.placeholder || 'Select...') + '</option></select>';
                    break;
                case 'checkbox': case 'radio':
                    (field.options || [{label: 'Option'}]).forEach(function(opt) {
                        html += '<label><input type="' + field.type + '" disabled> ' + opt.label + '</label> ';
                    });
                    break;
                case 'date': html = '<input type="text" disabled placeholder="Select date...">'; break;
                case 'time': html = '<input type="text" disabled placeholder="Select time...">'; break;
                case 'file': html = '<div class="erfq-file-preview"><span class="dashicons dashicons-upload"></span> Choose file</div>'; break;
                case 'section':
                    var tag = field.heading_tag || 'h3';
                    html = '<div class="erfq-section-preview' + (field.show_divider ? ' erfq-has-divider' : '') + '">';
                    html += '<' + tag + '>' + (field.label || 'Section Title') + '</' + tag + '>';
                    if (field.description) html += '<p class="description">' + field.description + '</p>';
                    html += '</div>';
                    break;
                case 'html':
                    html = '<div class="erfq-html-preview">' + (field.html_content || '<em>HTML content will appear here</em>') + '</div>';
                    break;
                case 'repeater':
                    html = '<div class="erfq-repeater-preview"><span class="dashicons dashicons-list-view"></span> Repeater: ';
                    html += (field.sub_fields || []).length + ' sub-field(s)</div>';
                    break;
                default: html = '<input type="text" disabled>';
            }
            $container.html(html);
        },

        selectField: function(fieldId) {
            var field = this.getFieldById(fieldId);
            if (!field) return;

            this.selectedFieldId = fieldId;
            $('.erfq-field-row').removeClass('erfq-selected');
            $('[data-field-id="' + fieldId + '"]').addClass('erfq-selected');
            $('.erfq-panel-tab[data-panel="field-settings"]').click();
            this.renderFieldSettings(field);
        },

        renderFieldSettings: function(field) {
            var fieldMeta = erfqAdmin.fieldTypes[field.type] || {};
            var templateName = 'erfq-field-settings';

            // Use special templates for layout fields
            if (field.type === 'section') {
                templateName = 'erfq-section-settings';
            } else if (field.type === 'html') {
                templateName = 'erfq-html-settings';
            } else if (field.type === 'repeater') {
                templateName = 'erfq-repeater-settings';
            }

            var template = wp.template(templateName);
            var $settings = $(template($.extend({}, field, {
                icon: fieldMeta.icon || 'dashicons-admin-generic',
                type_label: fieldMeta.label || field.type,
                supports_placeholder: fieldMeta.supports_placeholder !== false,
                supports_default: fieldMeta.supports_default !== false,
                supports_options: fieldMeta.supports_options === true,
                supports_min_max: fieldMeta.supports_min_max === true,
                supports_pattern: fieldMeta.supports_pattern !== false
            })));

            $('.erfq-no-field-selected').hide();
            $('.erfq-field-settings-content').html($settings).show();
            this.renderConditionalRules(field);
        },

        updateFieldSetting: function(fieldId, setting, value) {
            var field = this.getFieldById(fieldId);
            if (!field) return;
            field[setting] = value;
            this.hasUnsavedChanges = true;
            this.renderFields();
            this.selectField(fieldId);
        },

        getFieldById: function(fieldId) {
            return this.formData.fields.find(function(f) { return f.id === fieldId; });
        },

        deleteField: function(fieldId) {
            var index = this.formData.fields.findIndex(function(f) { return f.id === fieldId; });
            if (index !== -1) {
                this.formData.fields.splice(index, 1);
                this.hasUnsavedChanges = true;
                if (this.selectedFieldId === fieldId) {
                    this.selectedFieldId = null;
                    $('.erfq-no-field-selected').show();
                    $('.erfq-field-settings-content').hide();
                }
                this.renderFields();
            }
        },

        duplicateField: function(fieldId) {
            var field = this.getFieldById(fieldId);
            if (!field) return;
            var index = this.formData.fields.findIndex(function(f) { return f.id === fieldId; });
            var newField = JSON.parse(JSON.stringify(field));
            newField.id = field.type + '_' + Date.now();
            newField.label = field.label + ' (Copy)';
            this.formData.fields.splice(index + 1, 0, newField);
            this.hasUnsavedChanges = true;
            this.renderFields();
            this.selectField(newField.id);
        },

        updateFieldOrder: function() {
            var self = this;
            var newOrder = [];
            $('#erfq-fields-container .erfq-field-row').each(function() {
                var field = self.getFieldById($(this).data('field-id'));
                if (field) newOrder.push(field);
            });
            this.formData.fields = newOrder;
            this.hasUnsavedChanges = true;
        },

        addOption: function() {
            var field = this.getFieldById(this.selectedFieldId);
            if (!field) return;
            if (!field.options) field.options = [];
            var index = field.options.length + 1;
            field.options.push({ label: 'Option ' + index, value: 'option_' + index });
            this.hasUnsavedChanges = true;
            this.renderFieldSettings(field);
        },

        removeOption: function(index) {
            var field = this.getFieldById(this.selectedFieldId);
            if (!field || !field.options) return;
            field.options.splice(index, 1);
            this.hasUnsavedChanges = true;
            this.renderFieldSettings(field);
        },

        updateOptions: function() {
            var field = this.getFieldById(this.selectedFieldId);
            if (!field) return;
            var options = [];
            $('.erfq-option-row').each(function() {
                options.push({ label: $(this).find('.erfq-option-label').val(), value: $(this).find('.erfq-option-value').val() });
            });
            field.options = options;
            this.hasUnsavedChanges = true;
        },

        renderConditionalRules: function(field) {
            var $container = $('.erfq-conditional-rules').empty();
            var rules = field.conditional_rules || [];
            var otherFields = this.formData.fields.filter(function(f) { return f.id !== field.id; });
            var template = wp.template('erfq-conditional-rule');

            rules.forEach(function(rule, index) {
                $container.append($(template({ index: index, rule: rule, fields: otherFields, operators: erfqAdmin.operators })));
            });
        },

        addCondition: function() {
            var field = this.getFieldById(this.selectedFieldId);
            if (!field) return;
            if (!field.conditional_rules) field.conditional_rules = [];
            var otherFields = this.formData.fields.filter(function(f) { return f.id !== field.id; });
            if (otherFields.length === 0) { alert('Add more fields first.'); return; }
            field.conditional_rules.push({ field: otherFields[0].id, operator: 'equals', value: '' });
            this.hasUnsavedChanges = true;
            this.renderConditionalRules(field);
        },

        removeCondition: function(index) {
            var field = this.getFieldById(this.selectedFieldId);
            if (!field || !field.conditional_rules) return;
            field.conditional_rules.splice(index, 1);
            this.hasUnsavedChanges = true;
            this.renderConditionalRules(field);
        },

        updateConditions: function() {
            var field = this.getFieldById(this.selectedFieldId);
            if (!field) return;
            var rules = [];
            $('.erfq-conditional-rule').each(function() {
                rules.push({
                    field: $(this).find('.erfq-rule-field').val(),
                    operator: $(this).find('.erfq-rule-operator').val(),
                    value: $(this).find('.erfq-rule-value').val()
                });
            });
            field.conditional_rules = rules;
            this.hasUnsavedChanges = true;
        },

        addSubField: function() {
            var field = this.getFieldById(this.selectedFieldId);
            if (!field || field.type !== 'repeater') return;
            if (!field.sub_fields) field.sub_fields = [];
            var index = field.sub_fields.length + 1;
            field.sub_fields.push({ id: 'field_' + index, type: 'text', label: 'Field ' + index });
            this.hasUnsavedChanges = true;
            this.renderFieldSettings(field);
            this.renderFields();
        },

        removeSubField: function(index) {
            var field = this.getFieldById(this.selectedFieldId);
            if (!field || !field.sub_fields) return;
            field.sub_fields.splice(index, 1);
            this.hasUnsavedChanges = true;
            this.renderFieldSettings(field);
            this.renderFields();
        },

        updateSubFields: function() {
            var field = this.getFieldById(this.selectedFieldId);
            if (!field || field.type !== 'repeater') return;
            var subFields = [];
            $('.erfq-sub-field-row').each(function() {
                subFields.push({
                    id: $(this).find('.erfq-sub-field-id').val(),
                    type: $(this).find('.erfq-sub-field-type').val(),
                    label: $(this).find('.erfq-sub-field-label').val()
                });
            });
            field.sub_fields = subFields;
            this.hasUnsavedChanges = true;
            this.renderFields();
        },

        saveForm: function() {
            var self = this;
            var $btn = $('.erfq-save-btn').prop('disabled', true).text(erfqAdmin.i18n.saving);

            $.ajax({
                url: erfqAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'erfq_save_form',
                    form_id: this.formId,
                    form_data: JSON.stringify(this.formData),
                    nonce: erfqAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.hasUnsavedChanges = false;
                        if (!self.formId && response.data.form_id) {
                            self.formId = response.data.form_id;
                            var newUrl = window.location.href + (window.location.href.indexOf('?') !== -1 ? '&' : '?') + 'form_id=' + response.data.form_id;
                            window.history.replaceState({}, '', newUrl);
                        }
                        $btn.text(erfqAdmin.i18n.saved);
                        setTimeout(function() { $btn.html('<span class="dashicons dashicons-saved"></span> Save Form'); }, 2000);
                    } else {
                        alert(response.data.message || erfqAdmin.i18n.error);
                        $btn.html('<span class="dashicons dashicons-saved"></span> Save Form');
                    }
                },
                error: function() {
                    alert(erfqAdmin.i18n.error);
                    $btn.html('<span class="dashicons dashicons-saved"></span> Save Form');
                },
                complete: function() { $btn.prop('disabled', false); }
            });
        },

        previewForm: function() {
            if (!this.formId) { alert('Save the form first.'); return; }
            window.open('/?erfq_preview=' + this.formId, '_blank');
        }
    };

    $(document).ready(function() {
        if ($('#erfq-form-builder').length) ERFQFormBuilder.init();
    });

})(jQuery);
