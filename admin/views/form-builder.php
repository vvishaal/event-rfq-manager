<?php
/**
 * Form Builder View
 *
 * @package Event_RFQ_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap erfq-form-builder-wrap">
    <h1 class="wp-heading-inline">
        <?php echo $this->form_id ? esc_html__('Edit Form', 'event-rfq-manager') : esc_html__('Add New Form', 'event-rfq-manager'); ?>
    </h1>

    <?php if ($this->form_id) : ?>
        <a href="<?php echo esc_url(admin_url('admin.php?page=erfq-form-builder')); ?>" class="page-title-action">
            <?php esc_html_e('Add New', 'event-rfq-manager'); ?>
        </a>
    <?php endif; ?>

    <hr class="wp-header-end">

    <div id="erfq-form-builder" class="erfq-form-builder" data-form-id="<?php echo esc_attr($this->form_id); ?>">
        <!-- Top Bar -->
        <div class="erfq-builder-topbar">
            <div class="erfq-form-title-wrap">
                <input type="text" id="erfq-form-title" class="erfq-form-title"
                       value="<?php echo esc_attr($form_data['title']); ?>"
                       placeholder="<?php esc_attr_e('Form Title', 'event-rfq-manager'); ?>">
            </div>
            <div class="erfq-builder-actions">
                <?php if ($this->form_id) : ?>
                    <button type="button" class="button erfq-preview-btn">
                        <span class="dashicons dashicons-visibility"></span>
                        <?php esc_html_e('Preview', 'event-rfq-manager'); ?>
                    </button>
                <?php endif; ?>
                <button type="button" class="button button-primary erfq-save-btn">
                    <span class="dashicons dashicons-saved"></span>
                    <?php esc_html_e('Save Form', 'event-rfq-manager'); ?>
                </button>
            </div>
        </div>

        <div class="erfq-builder-main">
            <!-- Field Palette (Left Sidebar) -->
            <div class="erfq-field-palette">
                <div class="erfq-palette-header">
                    <h3><?php esc_html_e('Add Fields', 'event-rfq-manager'); ?></h3>
                </div>
                <div class="erfq-palette-search">
                    <input type="text" id="erfq-field-search" placeholder="<?php esc_attr_e('Search fields...', 'event-rfq-manager'); ?>">
                </div>
                <div class="erfq-palette-fields">
                    <?php foreach ($categories as $cat_key => $cat_label) : ?>
                        <div class="erfq-field-category" data-category="<?php echo esc_attr($cat_key); ?>">
                            <h4 class="erfq-category-title"><?php echo esc_html($cat_label); ?></h4>
                            <div class="erfq-category-fields">
                                <?php foreach ($field_types as $type => $meta) : ?>
                                    <?php if (isset($meta['category']) && $meta['category'] === $cat_key) : ?>
                                        <div class="erfq-palette-field" data-type="<?php echo esc_attr($type); ?>">
                                            <span class="erfq-field-icon dashicons <?php echo esc_attr($meta['icon']); ?>"></span>
                                            <span class="erfq-field-name"><?php echo esc_html($meta['label']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Form Canvas (Center) -->
            <div class="erfq-form-canvas">
                <!-- Multi-Step Tabs -->
                <div class="erfq-steps-nav" style="display: none;">
                    <ul class="erfq-step-tabs">
                        <li class="erfq-step-tab active" data-step="0">
                            <span class="step-name"><?php esc_html_e('Step 1', 'event-rfq-manager'); ?></span>
                            <button type="button" class="erfq-edit-step" title="<?php esc_attr_e('Edit Step', 'event-rfq-manager'); ?>">
                                <span class="dashicons dashicons-edit"></span>
                            </button>
                        </li>
                    </ul>
                    <button type="button" class="erfq-add-step button button-small">
                        <span class="dashicons dashicons-plus"></span>
                        <?php esc_html_e('Add Step', 'event-rfq-manager'); ?>
                    </button>
                </div>

                <!-- Form Fields Container -->
                <div class="erfq-canvas-header">
                    <h3><?php esc_html_e('Form Fields', 'event-rfq-manager'); ?></h3>
                    <p class="description"><?php esc_html_e('Drag fields from the left panel or click to add. Drag to reorder.', 'event-rfq-manager'); ?></p>
                </div>

                <div id="erfq-fields-container" class="erfq-fields-container">
                    <div class="erfq-empty-state">
                        <span class="dashicons dashicons-plus-alt2"></span>
                        <p><?php esc_html_e('Drag fields here to build your form', 'event-rfq-manager'); ?></p>
                    </div>
                </div>

                <!-- Shortcode Display -->
                <?php if ($this->form_id) : ?>
                    <div class="erfq-shortcode-display">
                        <label><?php esc_html_e('Shortcode:', 'event-rfq-manager'); ?></label>
                        <code id="erfq-shortcode">[erfq_form id="<?php echo esc_attr($this->form_id); ?>"]</code>
                        <button type="button" class="button button-small erfq-copy-shortcode" title="<?php esc_attr_e('Copy to Clipboard', 'event-rfq-manager'); ?>">
                            <span class="dashicons dashicons-clipboard"></span>
                        </button>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Field Settings Panel (Right Sidebar) -->
            <div class="erfq-settings-panel">
                <div class="erfq-panel-tabs">
                    <button type="button" class="erfq-panel-tab active" data-panel="field-settings">
                        <?php esc_html_e('Field', 'event-rfq-manager'); ?>
                    </button>
                    <button type="button" class="erfq-panel-tab" data-panel="form-settings">
                        <?php esc_html_e('Form', 'event-rfq-manager'); ?>
                    </button>
                </div>

                <!-- Field Settings -->
                <div class="erfq-panel-content erfq-field-settings active" data-panel="field-settings">
                    <div class="erfq-no-field-selected">
                        <span class="dashicons dashicons-admin-generic"></span>
                        <p><?php esc_html_e('Select a field to edit its settings', 'event-rfq-manager'); ?></p>
                    </div>
                    <div class="erfq-field-settings-content" style="display: none;">
                        <!-- Populated dynamically via JS -->
                    </div>
                </div>

                <!-- Form Settings -->
                <div class="erfq-panel-content erfq-form-settings" data-panel="form-settings" style="display: none;">
                    <div class="erfq-setting-group">
                        <h4><?php esc_html_e('General', 'event-rfq-manager'); ?></h4>

                        <div class="erfq-setting-row">
                            <label for="erfq-form-css-class"><?php esc_html_e('CSS Class', 'event-rfq-manager'); ?></label>
                            <input type="text" id="erfq-form-css-class" class="erfq-form-setting" data-setting="css_class"
                                   value="<?php echo esc_attr($form_data['settings']['css_class'] ?? ''); ?>"
                                   placeholder="my-custom-form">
                        </div>

                        <div class="erfq-setting-row">
                            <label for="erfq-form-submit-label"><?php esc_html_e('Submit Button Text', 'event-rfq-manager'); ?></label>
                            <input type="text" id="erfq-form-submit-label" class="erfq-form-setting" data-setting="submit_label"
                                   value="<?php echo esc_attr($form_data['settings']['submit_label'] ?? __('Submit', 'event-rfq-manager')); ?>">
                        </div>
                    </div>

                    <div class="erfq-setting-group">
                        <h4><?php esc_html_e('After Submission', 'event-rfq-manager'); ?></h4>

                        <div class="erfq-setting-row">
                            <label for="erfq-form-success-message"><?php esc_html_e('Success Message', 'event-rfq-manager'); ?></label>
                            <textarea id="erfq-form-success-message" class="erfq-form-setting" data-setting="success_message" rows="3"><?php echo esc_textarea($form_data['settings']['success_message'] ?? __('Thank you for your submission!', 'event-rfq-manager')); ?></textarea>
                        </div>

                        <div class="erfq-setting-row">
                            <label for="erfq-form-redirect-url"><?php esc_html_e('Redirect URL (optional)', 'event-rfq-manager'); ?></label>
                            <input type="url" id="erfq-form-redirect-url" class="erfq-form-setting" data-setting="redirect_url"
                                   value="<?php echo esc_url($form_data['settings']['redirect_url'] ?? ''); ?>"
                                   placeholder="https://example.com/thank-you">
                        </div>
                    </div>

                    <div class="erfq-setting-group">
                        <h4><?php esc_html_e('Email Notifications', 'event-rfq-manager'); ?></h4>

                        <div class="erfq-setting-row">
                            <label>
                                <input type="checkbox" id="erfq-form-admin-email" class="erfq-form-setting" data-setting="admin_notification"
                                       <?php checked(!empty($form_data['settings']['admin_notification']), true); ?>>
                                <?php esc_html_e('Send admin notification', 'event-rfq-manager'); ?>
                            </label>
                        </div>

                        <div class="erfq-setting-row">
                            <label for="erfq-form-email-recipient"><?php esc_html_e('Email Recipient', 'event-rfq-manager'); ?></label>
                            <input type="email" id="erfq-form-email-recipient" class="erfq-form-setting" data-setting="email_recipient"
                                   value="<?php echo esc_attr($form_data['settings']['email_recipient'] ?? get_option('admin_email')); ?>">
                        </div>

                        <div class="erfq-setting-row">
                            <label>
                                <input type="checkbox" id="erfq-form-user-confirmation" class="erfq-form-setting" data-setting="user_confirmation"
                                       <?php checked(!empty($form_data['settings']['user_confirmation']), true); ?>>
                                <?php esc_html_e('Send confirmation to submitter', 'event-rfq-manager'); ?>
                            </label>
                            <p class="description"><?php esc_html_e('Requires an email field in the form.', 'event-rfq-manager'); ?></p>
                        </div>
                    </div>

                    <div class="erfq-setting-group">
                        <h4><?php esc_html_e('Security', 'event-rfq-manager'); ?></h4>

                        <div class="erfq-setting-row">
                            <label>
                                <input type="checkbox" id="erfq-form-honeypot" class="erfq-form-setting" data-setting="honeypot_enabled"
                                       <?php checked($form_data['settings']['honeypot_enabled'] ?? true, true); ?>>
                                <?php esc_html_e('Enable honeypot anti-spam', 'event-rfq-manager'); ?>
                            </label>
                        </div>

                        <div class="erfq-setting-row">
                            <label>
                                <input type="checkbox" id="erfq-form-recaptcha" class="erfq-form-setting" data-setting="recaptcha_enabled"
                                       <?php checked(!empty($form_data['settings']['recaptcha_enabled']), true); ?>>
                                <?php esc_html_e('Enable reCAPTCHA v3', 'event-rfq-manager'); ?>
                            </label>
                            <?php if (!get_option('erfq_recaptcha_site_key')) : ?>
                                <p class="description">
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=erfq-settings')); ?>">
                                        <?php esc_html_e('Configure reCAPTCHA keys first', 'event-rfq-manager'); ?>
                                    </a>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="erfq-setting-group">
                        <h4><?php esc_html_e('Multi-Step', 'event-rfq-manager'); ?></h4>

                        <div class="erfq-setting-row">
                            <label>
                                <input type="checkbox" id="erfq-form-multistep" class="erfq-form-setting" data-setting="multistep_enabled"
                                       <?php checked(!empty($form_data['settings']['multistep_enabled']), true); ?>>
                                <?php esc_html_e('Enable multi-step form', 'event-rfq-manager'); ?>
                            </label>
                        </div>

                        <div class="erfq-setting-row">
                            <label>
                                <input type="checkbox" id="erfq-form-progress-bar" class="erfq-form-setting" data-setting="show_progress_bar"
                                       <?php checked(!empty($form_data['settings']['show_progress_bar']), true); ?>>
                                <?php esc_html_e('Show progress bar', 'event-rfq-manager'); ?>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Field Settings Template -->
    <script type="text/template" id="tmpl-erfq-field-settings">
        <div class="erfq-field-settings-header">
            <span class="erfq-field-type-icon dashicons {{ data.icon }}"></span>
            <span class="erfq-field-type-name">{{ data.type_label }}</span>
        </div>

        <div class="erfq-setting-group">
            <h4><?php esc_html_e('Basic Settings', 'event-rfq-manager'); ?></h4>

            <div class="erfq-setting-row">
                <label for="erfq-field-label"><?php esc_html_e('Label', 'event-rfq-manager'); ?></label>
                <input type="text" id="erfq-field-label" class="erfq-field-setting" data-setting="label" value="{{ data.label }}">
            </div>

            <div class="erfq-setting-row">
                <label for="erfq-field-name"><?php esc_html_e('Field Name (ID)', 'event-rfq-manager'); ?></label>
                <input type="text" id="erfq-field-name" class="erfq-field-setting" data-setting="id" value="{{ data.id }}" pattern="[a-z0-9_-]+">
                <p class="description"><?php esc_html_e('Lowercase letters, numbers, underscore, hyphen only.', 'event-rfq-manager'); ?></p>
            </div>

            <# if (data.supports_placeholder) { #>
            <div class="erfq-setting-row">
                <label for="erfq-field-placeholder"><?php esc_html_e('Placeholder', 'event-rfq-manager'); ?></label>
                <input type="text" id="erfq-field-placeholder" class="erfq-field-setting" data-setting="placeholder" value="{{ data.placeholder }}">
            </div>
            <# } #>

            <div class="erfq-setting-row">
                <label for="erfq-field-description"><?php esc_html_e('Description', 'event-rfq-manager'); ?></label>
                <textarea id="erfq-field-description" class="erfq-field-setting" data-setting="description" rows="2">{{ data.description }}</textarea>
            </div>

            <# if (data.supports_default) { #>
            <div class="erfq-setting-row">
                <label for="erfq-field-default"><?php esc_html_e('Default Value', 'event-rfq-manager'); ?></label>
                <input type="text" id="erfq-field-default" class="erfq-field-setting" data-setting="default" value="{{ data.default }}">
            </div>
            <# } #>
        </div>

        <# if (data.supports_options) { #>
        <div class="erfq-setting-group">
            <h4><?php esc_html_e('Options', 'event-rfq-manager'); ?></h4>
            <div class="erfq-options-list">
                <# _.each(data.options || [], function(option, index) { #>
                <div class="erfq-option-row" data-index="{{ index }}">
                    <span class="erfq-option-drag dashicons dashicons-menu"></span>
                    <input type="text" class="erfq-option-label" value="{{ option.label }}" placeholder="<?php esc_attr_e('Label', 'event-rfq-manager'); ?>">
                    <input type="text" class="erfq-option-value" value="{{ option.value }}" placeholder="<?php esc_attr_e('Value', 'event-rfq-manager'); ?>">
                    <button type="button" class="erfq-remove-option" title="<?php esc_attr_e('Remove', 'event-rfq-manager'); ?>">
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                </div>
                <# }); #>
            </div>
            <button type="button" class="button erfq-add-option">
                <span class="dashicons dashicons-plus"></span>
                <?php esc_html_e('Add Option', 'event-rfq-manager'); ?>
            </button>
        </div>
        <# } #>

        <div class="erfq-setting-group">
            <h4><?php esc_html_e('Validation', 'event-rfq-manager'); ?></h4>

            <div class="erfq-setting-row">
                <label>
                    <input type="checkbox" class="erfq-field-setting" data-setting="required" <# if (data.required) { #>checked<# } #>>
                    <?php esc_html_e('Required field', 'event-rfq-manager'); ?>
                </label>
            </div>

            <# if (data.supports_min_max) { #>
            <div class="erfq-setting-row erfq-inline-settings">
                <div>
                    <label for="erfq-field-min"><?php esc_html_e('Min', 'event-rfq-manager'); ?></label>
                    <input type="number" id="erfq-field-min" class="erfq-field-setting" data-setting="min" value="{{ data.min }}">
                </div>
                <div>
                    <label for="erfq-field-max"><?php esc_html_e('Max', 'event-rfq-manager'); ?></label>
                    <input type="number" id="erfq-field-max" class="erfq-field-setting" data-setting="max" value="{{ data.max }}">
                </div>
            </div>
            <# } #>

            <# if (data.supports_pattern) { #>
            <div class="erfq-setting-row">
                <label for="erfq-field-pattern"><?php esc_html_e('Validation Pattern (regex)', 'event-rfq-manager'); ?></label>
                <input type="text" id="erfq-field-pattern" class="erfq-field-setting" data-setting="pattern" value="{{ data.pattern }}">
            </div>
            <# } #>
        </div>

        <div class="erfq-setting-group">
            <h4><?php esc_html_e('Appearance', 'event-rfq-manager'); ?></h4>

            <div class="erfq-setting-row">
                <label for="erfq-field-width"><?php esc_html_e('Field Width', 'event-rfq-manager'); ?></label>
                <select id="erfq-field-width" class="erfq-field-setting" data-setting="width">
                    <option value="100" <# if (data.width == '100') { #>selected<# } #>><?php esc_html_e('Full Width (100%)', 'event-rfq-manager'); ?></option>
                    <option value="75" <# if (data.width == '75') { #>selected<# } #>><?php esc_html_e('Three Quarters (75%)', 'event-rfq-manager'); ?></option>
                    <option value="66" <# if (data.width == '66') { #>selected<# } #>><?php esc_html_e('Two Thirds (66%)', 'event-rfq-manager'); ?></option>
                    <option value="50" <# if (data.width == '50') { #>selected<# } #>><?php esc_html_e('Half (50%)', 'event-rfq-manager'); ?></option>
                    <option value="33" <# if (data.width == '33') { #>selected<# } #>><?php esc_html_e('One Third (33%)', 'event-rfq-manager'); ?></option>
                    <option value="25" <# if (data.width == '25') { #>selected<# } #>><?php esc_html_e('One Quarter (25%)', 'event-rfq-manager'); ?></option>
                </select>
            </div>

            <div class="erfq-setting-row">
                <label for="erfq-field-css"><?php esc_html_e('CSS Class', 'event-rfq-manager'); ?></label>
                <input type="text" id="erfq-field-css" class="erfq-field-setting" data-setting="css_class" value="{{ data.css_class }}">
            </div>
        </div>

        <div class="erfq-setting-group">
            <h4><?php esc_html_e('Conditional Logic', 'event-rfq-manager'); ?></h4>

            <div class="erfq-setting-row">
                <label>
                    <input type="checkbox" class="erfq-field-setting erfq-conditional-toggle" data-setting="conditional_enabled" <# if (data.conditional_enabled) { #>checked<# } #>>
                    <?php esc_html_e('Enable conditional logic', 'event-rfq-manager'); ?>
                </label>
            </div>

            <div class="erfq-conditional-settings" <# if (!data.conditional_enabled) { #>style="display:none"<# } #>>
                <div class="erfq-conditional-action">
                    <select class="erfq-field-setting" data-setting="conditional_action">
                        <option value="show" <# if (data.conditional_action === 'show') { #>selected<# } #>><?php esc_html_e('Show', 'event-rfq-manager'); ?></option>
                        <option value="hide" <# if (data.conditional_action === 'hide') { #>selected<# } #>><?php esc_html_e('Hide', 'event-rfq-manager'); ?></option>
                    </select>
                    <span><?php esc_html_e('this field if', 'event-rfq-manager'); ?></span>
                    <select class="erfq-field-setting" data-setting="conditional_logic">
                        <option value="all" <# if (data.conditional_logic === 'all') { #>selected<# } #>><?php esc_html_e('all', 'event-rfq-manager'); ?></option>
                        <option value="any" <# if (data.conditional_logic === 'any') { #>selected<# } #>><?php esc_html_e('any', 'event-rfq-manager'); ?></option>
                    </select>
                    <span><?php esc_html_e('conditions match', 'event-rfq-manager'); ?></span>
                </div>
                <div class="erfq-conditional-rules">
                    <!-- Populated by JS -->
                </div>
                <button type="button" class="button erfq-add-condition">
                    <span class="dashicons dashicons-plus"></span>
                    <?php esc_html_e('Add Condition', 'event-rfq-manager'); ?>
                </button>
            </div>
        </div>

        <div class="erfq-field-actions">
            <button type="button" class="button erfq-duplicate-field">
                <span class="dashicons dashicons-admin-page"></span>
                <?php esc_html_e('Duplicate', 'event-rfq-manager'); ?>
            </button>
            <button type="button" class="button erfq-delete-field">
                <span class="dashicons dashicons-trash"></span>
                <?php esc_html_e('Delete', 'event-rfq-manager'); ?>
            </button>
        </div>
    </script>

    <!-- Field Row Template -->
    <script type="text/template" id="tmpl-erfq-field-row">
        <div class="erfq-field-row<# if (data.is_section) { #> erfq-section-field<# } #><# if (data.is_sub_field) { #> erfq-sub-field<# } #>" data-field-id="{{ data.id }}" data-field-type="{{ data.type }}">
            <div class="erfq-field-header">
                <span class="erfq-field-drag dashicons dashicons-menu"></span>
                <span class="erfq-field-icon dashicons {{ data.icon }}"></span>
                <span class="erfq-field-label">{{ data.label }}</span>
                <# if (data.required) { #><span class="erfq-required">*</span><# } #>
                <span class="erfq-field-type">{{ data.type_label }}</span>
                <# if (data.is_section && data.repeatable) { #>
                <span class="erfq-section-repeatable-indicator" title="<?php esc_attr_e('Repeatable Section', 'event-rfq-manager'); ?>">
                    <span class="dashicons dashicons-controls-repeat"></span>
                </span>
                <# } #>
                <div class="erfq-field-actions-inline">
                    <button type="button" class="erfq-field-edit" title="<?php esc_attr_e('Edit', 'event-rfq-manager'); ?>">
                        <span class="dashicons dashicons-edit"></span>
                    </button>
                    <button type="button" class="erfq-field-duplicate" title="<?php esc_attr_e('Duplicate', 'event-rfq-manager'); ?>">
                        <span class="dashicons dashicons-admin-page"></span>
                    </button>
                    <button type="button" class="erfq-field-delete" title="<?php esc_attr_e('Delete', 'event-rfq-manager'); ?>">
                        <span class="dashicons dashicons-trash"></span>
                    </button>
                </div>
            </div>
            <div class="erfq-field-content">
                <div class="erfq-field-preview">
                    <!-- Field preview rendered here -->
                </div>
                <# if (data.is_section) { #>
                <div class="erfq-section-dropzone">
                    <p class="erfq-section-empty-msg">
                        <span class="dashicons dashicons-plus-alt"></span>
                        <?php esc_html_e('Drag fields here to add to this section', 'event-rfq-manager'); ?>
                    </p>
                    <!-- Sub-fields rendered here by JS -->
                </div>
                <# } #>
            </div>
        </div>
    </script>

    <!-- Conditional Rule Template -->
    <script type="text/template" id="tmpl-erfq-conditional-rule">
        <div class="erfq-conditional-rule" data-rule-index="{{ data.index }}">
            <select class="erfq-rule-field">
                <# _.each(data.fields, function(field) { #>
                <option value="{{ field.id }}" <# if (data.rule.field === field.id) { #>selected<# } #>>{{ field.label }}</option>
                <# }); #>
            </select>
            <select class="erfq-rule-operator">
                <# _.each(data.operators, function(label, key) { #>
                <option value="{{ key }}" <# if (data.rule.operator === key) { #>selected<# } #>>{{ label }}</option>
                <# }); #>
            </select>
            <input type="text" class="erfq-rule-value" value="{{ data.rule.value }}" placeholder="<?php esc_attr_e('Value', 'event-rfq-manager'); ?>">
            <button type="button" class="erfq-remove-rule" title="<?php esc_attr_e('Remove', 'event-rfq-manager'); ?>">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>
    </script>

    <!-- Section Field Settings Template -->
    <script type="text/template" id="tmpl-erfq-section-settings">
        <div class="erfq-field-settings-header">
            <span class="erfq-field-type-icon dashicons {{ data.icon }}"></span>
            <span class="erfq-field-type-name">{{ data.type_label }}</span>
        </div>

        <div class="erfq-setting-group">
            <h4><?php esc_html_e('Section Settings', 'event-rfq-manager'); ?></h4>

            <div class="erfq-setting-row">
                <label for="erfq-field-label"><?php esc_html_e('Section Title', 'event-rfq-manager'); ?></label>
                <input type="text" id="erfq-field-label" class="erfq-field-setting" data-setting="label" value="{{ data.label }}">
            </div>

            <div class="erfq-setting-row">
                <label for="erfq-field-description"><?php esc_html_e('Description', 'event-rfq-manager'); ?></label>
                <textarea id="erfq-field-description" class="erfq-field-setting" data-setting="description" rows="2">{{ data.description }}</textarea>
            </div>
        </div>

        <div class="erfq-setting-group">
            <h4><?php esc_html_e('Repeater Settings', 'event-rfq-manager'); ?></h4>
            <p class="description"><?php esc_html_e('Allow users to add multiple instances of this section on the frontend.', 'event-rfq-manager'); ?></p>

            <div class="erfq-setting-row">
                <label>
                    <input type="checkbox" class="erfq-field-setting" data-setting="repeatable" <# if (data.repeatable) { #>checked<# } #>>
                    <?php esc_html_e('Make this section repeatable', 'event-rfq-manager'); ?>
                </label>
            </div>

            <div class="erfq-setting-row erfq-inline-settings">
                <div>
                    <label for="erfq-field-min-instances"><?php esc_html_e('Min Instances', 'event-rfq-manager'); ?></label>
                    <input type="number" id="erfq-field-min-instances" class="erfq-field-setting" data-setting="min_instances" value="{{ data.min_instances || 1 }}" min="1" max="20">
                </div>
                <div>
                    <label for="erfq-field-max-instances"><?php esc_html_e('Max Instances', 'event-rfq-manager'); ?></label>
                    <input type="number" id="erfq-field-max-instances" class="erfq-field-setting" data-setting="max_instances" value="{{ data.max_instances || 10 }}" min="1" max="50">
                </div>
            </div>

            <div class="erfq-setting-row">
                <label for="erfq-field-add-button-text"><?php esc_html_e('Add Button Text', 'event-rfq-manager'); ?></label>
                <input type="text" id="erfq-field-add-button-text" class="erfq-field-setting" data-setting="add_button_text" value="{{ data.add_button_text || '+ Add Another' }}">
            </div>
        </div>

        <div class="erfq-setting-group">
            <h4><?php esc_html_e('Appearance', 'event-rfq-manager'); ?></h4>

            <div class="erfq-setting-row">
                <label for="erfq-field-css"><?php esc_html_e('CSS Class', 'event-rfq-manager'); ?></label>
                <input type="text" id="erfq-field-css" class="erfq-field-setting" data-setting="css_class" value="{{ data.css_class }}">
            </div>
        </div>

        <div class="erfq-setting-group">
            <h4><?php esc_html_e('Section Fields', 'event-rfq-manager'); ?></h4>
            <p class="description">
                <?php esc_html_e('Drag fields from the left panel into the section area in the form builder to add them.', 'event-rfq-manager'); ?>
            </p>
            <div class="erfq-section-fields-count">
                <span class="dashicons dashicons-forms"></span>
                <strong>{{ (data.sub_fields || []).length }}</strong> <?php esc_html_e('field(s) in this section', 'event-rfq-manager'); ?>
            </div>
        </div>

        <div class="erfq-field-actions">
            <button type="button" class="button erfq-duplicate-field">
                <span class="dashicons dashicons-admin-page"></span>
                <?php esc_html_e('Duplicate', 'event-rfq-manager'); ?>
            </button>
            <button type="button" class="button erfq-delete-field">
                <span class="dashicons dashicons-trash"></span>
                <?php esc_html_e('Delete', 'event-rfq-manager'); ?>
            </button>
        </div>
    </script>

    <!-- HTML Field Settings Template -->
    <script type="text/template" id="tmpl-erfq-html-settings">
        <div class="erfq-field-settings-header">
            <span class="erfq-field-type-icon dashicons {{ data.icon }}"></span>
            <span class="erfq-field-type-name">{{ data.type_label }}</span>
        </div>

        <div class="erfq-setting-group">
            <h4><?php esc_html_e('HTML Content', 'event-rfq-manager'); ?></h4>

            <div class="erfq-setting-row">
                <label for="erfq-field-label"><?php esc_html_e('Label (for reference)', 'event-rfq-manager'); ?></label>
                <input type="text" id="erfq-field-label" class="erfq-field-setting" data-setting="label" value="{{ data.label }}">
            </div>

            <div class="erfq-setting-row">
                <label for="erfq-field-html-content"><?php esc_html_e('HTML Content', 'event-rfq-manager'); ?></label>
                <textarea id="erfq-field-html-content" class="erfq-field-setting erfq-code-editor" data-setting="html_content" rows="8">{{ data.html_content }}</textarea>
                <p class="description"><?php esc_html_e('Enter HTML content. Basic HTML tags are allowed.', 'event-rfq-manager'); ?></p>
            </div>
        </div>

        <div class="erfq-setting-group">
            <h4><?php esc_html_e('Appearance', 'event-rfq-manager'); ?></h4>

            <div class="erfq-setting-row">
                <label for="erfq-field-css"><?php esc_html_e('CSS Class', 'event-rfq-manager'); ?></label>
                <input type="text" id="erfq-field-css" class="erfq-field-setting" data-setting="css_class" value="{{ data.css_class }}">
            </div>
        </div>

        <div class="erfq-field-actions">
            <button type="button" class="button erfq-duplicate-field">
                <span class="dashicons dashicons-admin-page"></span>
                <?php esc_html_e('Duplicate', 'event-rfq-manager'); ?>
            </button>
            <button type="button" class="button erfq-delete-field">
                <span class="dashicons dashicons-trash"></span>
                <?php esc_html_e('Delete', 'event-rfq-manager'); ?>
            </button>
        </div>
    </script>

    <!-- Repeater Field Settings Template -->
    <script type="text/template" id="tmpl-erfq-repeater-settings">
        <div class="erfq-field-settings-header">
            <span class="erfq-field-type-icon dashicons {{ data.icon }}"></span>
            <span class="erfq-field-type-name">{{ data.type_label }}</span>
        </div>

        <div class="erfq-setting-group">
            <h4><?php esc_html_e('Basic Settings', 'event-rfq-manager'); ?></h4>

            <div class="erfq-setting-row">
                <label for="erfq-field-label"><?php esc_html_e('Label', 'event-rfq-manager'); ?></label>
                <input type="text" id="erfq-field-label" class="erfq-field-setting" data-setting="label" value="{{ data.label }}">
            </div>

            <div class="erfq-setting-row">
                <label for="erfq-field-name"><?php esc_html_e('Field Name (ID)', 'event-rfq-manager'); ?></label>
                <input type="text" id="erfq-field-name" class="erfq-field-setting" data-setting="id" value="{{ data.id }}" pattern="[a-z0-9_-]+">
            </div>

            <div class="erfq-setting-row">
                <label for="erfq-field-placeholder"><?php esc_html_e('Placeholder', 'event-rfq-manager'); ?></label>
                <input type="text" id="erfq-field-placeholder" class="erfq-field-setting" data-setting="placeholder" value="{{ data.placeholder }}">
            </div>

            <div class="erfq-setting-row">
                <label for="erfq-field-description"><?php esc_html_e('Description', 'event-rfq-manager'); ?></label>
                <textarea id="erfq-field-description" class="erfq-field-setting" data-setting="description" rows="2">{{ data.description }}</textarea>
            </div>
        </div>

        <div class="erfq-setting-group">
            <h4><?php esc_html_e('Repeater Options', 'event-rfq-manager'); ?></h4>

            <div class="erfq-setting-row">
                <label for="erfq-field-field-type"><?php esc_html_e('Input Type', 'event-rfq-manager'); ?></label>
                <select id="erfq-field-field-type" class="erfq-field-setting" data-setting="field_type">
                    <option value="text" <# if (data.field_type === 'text') { #>selected<# } #>><?php esc_html_e('Text', 'event-rfq-manager'); ?></option>
                    <option value="email" <# if (data.field_type === 'email') { #>selected<# } #>><?php esc_html_e('Email', 'event-rfq-manager'); ?></option>
                    <option value="phone" <# if (data.field_type === 'phone') { #>selected<# } #>><?php esc_html_e('Phone', 'event-rfq-manager'); ?></option>
                    <option value="number" <# if (data.field_type === 'number') { #>selected<# } #>><?php esc_html_e('Number', 'event-rfq-manager'); ?></option>
                    <option value="date" <# if (data.field_type === 'date') { #>selected<# } #>><?php esc_html_e('Date', 'event-rfq-manager'); ?></option>
                </select>
                <p class="description"><?php esc_html_e('Type of input field for each entry.', 'event-rfq-manager'); ?></p>
            </div>

            <div class="erfq-setting-row erfq-inline-settings">
                <div>
                    <label for="erfq-field-min-rows"><?php esc_html_e('Min Entries', 'event-rfq-manager'); ?></label>
                    <input type="number" id="erfq-field-min-rows" class="erfq-field-setting" data-setting="min_rows" value="{{ data.min_rows }}" min="0">
                </div>
                <div>
                    <label for="erfq-field-max-rows"><?php esc_html_e('Max Entries', 'event-rfq-manager'); ?></label>
                    <input type="number" id="erfq-field-max-rows" class="erfq-field-setting" data-setting="max_rows" value="{{ data.max_rows }}" min="1">
                </div>
            </div>

            <div class="erfq-setting-row">
                <label for="erfq-field-add-text"><?php esc_html_e('Add Button Text', 'event-rfq-manager'); ?></label>
                <input type="text" id="erfq-field-add-text" class="erfq-field-setting" data-setting="add_button_text" value="{{ data.add_button_text }}">
            </div>

            <div class="erfq-setting-row">
                <label>
                    <input type="checkbox" class="erfq-field-setting" data-setting="required" <# if (data.required) { #>checked<# } #>>
                    <?php esc_html_e('Required field', 'event-rfq-manager'); ?>
                </label>
            </div>
        </div>

        <div class="erfq-setting-group">
            <h4><?php esc_html_e('Appearance', 'event-rfq-manager'); ?></h4>

            <div class="erfq-setting-row">
                <label for="erfq-field-css"><?php esc_html_e('CSS Class', 'event-rfq-manager'); ?></label>
                <input type="text" id="erfq-field-css" class="erfq-field-setting" data-setting="css_class" value="{{ data.css_class }}">
            </div>
        </div>

        <div class="erfq-field-actions">
            <button type="button" class="button erfq-duplicate-field">
                <span class="dashicons dashicons-admin-page"></span>
                <?php esc_html_e('Duplicate', 'event-rfq-manager'); ?>
            </button>
            <button type="button" class="button erfq-delete-field">
                <span class="dashicons dashicons-trash"></span>
                <?php esc_html_e('Delete', 'event-rfq-manager'); ?>
            </button>
        </div>
    </script>

    <!-- Hidden form data -->
    <input type="hidden" id="erfq-form-data" value="<?php echo esc_attr(json_encode($form_data)); ?>">
</div>
