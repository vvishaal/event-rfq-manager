<?php
/**
 * Event RFQ Form Template
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="erfq-form-wrapper">
    <form id="erfq-form" class="erfq-form" method="post">
        <?php wp_nonce_field('erfq_form_nonce', 'erfq_nonce'); ?>
        
        <div class="erfq-form-section">
            <h2><?php esc_html_e('Basic Information', 'event-rfq-manager'); ?></h2>
            
            <div class="erfq-form-row">
                <div class="erfq-form-field">
                    <label for="date_from"><?php esc_html_e('Date From', 'event-rfq-manager'); ?> <span class="required">*</span></label>
                    <input type="date" id="date_from" name="date_from" required>
                </div>
                <div class="erfq-form-field">
                    <label for="date_to"><?php esc_html_e('Date To', 'event-rfq-manager'); ?> <span class="required">*</span></label>
                    <input type="date" id="date_to" name="date_to" required>
                </div>
            </div>

            <div class="erfq-form-field">
                <label for="destination"><?php esc_html_e('Destination', 'event-rfq-manager'); ?> <span class="required">*</span></label>
                <input type="text" id="destination" name="destination" placeholder="<?php esc_attr_e('Enter destination', 'event-rfq-manager'); ?>" required>
            </div>

            <div class="erfq-form-field">
                <label><?php esc_html_e('Venue Names', 'event-rfq-manager'); ?></label>
                <div id="venue-list">
                    <div class="erfq-venue-row">
                        <input type="text" name="venues[]" placeholder="<?php esc_attr_e('Enter venue name', 'event-rfq-manager'); ?>">
                    </div>
                </div>
                <button type="button" class="erfq-add-row" data-target="venue-list"><?php esc_html_e('+ Add Venue', 'event-rfq-manager'); ?></button>
            </div>

            <div class="erfq-form-field">
                <label for="category"><?php esc_html_e('Category', 'event-rfq-manager'); ?></label>
                <select id="category" name="category">
                    <option value=""><?php esc_html_e('Select Category', 'event-rfq-manager'); ?></option>
                    <option value="4star"><?php esc_html_e('4 Star', 'event-rfq-manager'); ?></option>
                    <option value="5star"><?php esc_html_e('5 Star', 'event-rfq-manager'); ?></option>
                    <option value="5star-luxury"><?php esc_html_e('5 Star Luxury', 'event-rfq-manager'); ?></option>
                </select>
            </div>

            <div class="erfq-form-row">
                <div class="erfq-form-field">
                    <label for="adults"><?php esc_html_e('Number of Adults', 'event-rfq-manager'); ?></label>
                    <input type="number" id="adults" name="adults" min="0" value="0">
                </div>
                <div class="erfq-form-field">
                    <label for="children"><?php esc_html_e('Number of Children', 'event-rfq-manager'); ?></label>
                    <input type="number" id="children" name="children" min="0" value="0">
                </div>
            </div>
        </div>

        <div class="erfq-form-section">
            <h2><?php esc_html_e('Accommodation', 'event-rfq-manager'); ?></h2>
            <div class="erfq-checkbox-group">
                <label><input type="checkbox" name="rooms[]" value="single"> <?php esc_html_e('Single', 'event-rfq-manager'); ?></label>
                <label><input type="checkbox" name="rooms[]" value="double"> <?php esc_html_e('Double', 'event-rfq-manager'); ?></label>
                <label><input type="checkbox" name="rooms[]" value="twin"> <?php esc_html_e('Twin', 'event-rfq-manager'); ?></label>
                <label><input type="checkbox" name="rooms[]" value="triple"> <?php esc_html_e('Triple', 'event-rfq-manager'); ?></label>
                <label><input type="checkbox" name="rooms[]" value="suite"> <?php esc_html_e('Suite', 'event-rfq-manager'); ?></label>
            </div>
        </div>

        <div class="erfq-form-section">
            <h2><?php esc_html_e('Food & Beverage', 'event-rfq-manager'); ?></h2>
            <div class="erfq-checkbox-group">
                <label><input type="checkbox" name="meals[]" value="breakfast"> <?php esc_html_e('Breakfast', 'event-rfq-manager'); ?></label>
                <label><input type="checkbox" name="meals[]" value="lunch"> <?php esc_html_e('Lunch', 'event-rfq-manager'); ?></label>
                <label><input type="checkbox" name="meals[]" value="dinner"> <?php esc_html_e('Dinner', 'event-rfq-manager'); ?></label>
                <label><input type="checkbox" name="meals[]" value="gala-dinner"> <?php esc_html_e('Gala Dinner', 'event-rfq-manager'); ?></label>
                <label><input type="checkbox" name="meals[]" value="bar"> <?php esc_html_e('Bar', 'event-rfq-manager'); ?></label>
            </div>
        </div>

        <div class="erfq-form-section">
            <h2><?php esc_html_e('Conference Setup', 'event-rfq-manager'); ?></h2>
            <div class="erfq-checkbox-group">
                <label><input type="checkbox" name="conference_setup[]" value="u-shape"> <?php esc_html_e('U Shape', 'event-rfq-manager'); ?></label>
                <label><input type="checkbox" name="conference_setup[]" value="theatre"> <?php esc_html_e('Theatre Style', 'event-rfq-manager'); ?></label>
                <label><input type="checkbox" name="conference_setup[]" value="boardroom"> <?php esc_html_e('Boardroom', 'event-rfq-manager'); ?></label>
                <label><input type="checkbox" name="conference_setup[]" value="classroom"> <?php esc_html_e('Classroom', 'event-rfq-manager'); ?></label>
                <label><input type="checkbox" name="conference_setup[]" value="cluster"> <?php esc_html_e('Cluster', 'event-rfq-manager'); ?></label>
                <label><input type="checkbox" name="conference_setup[]" value="banquet"> <?php esc_html_e('Banquet', 'event-rfq-manager'); ?></label>
                <label><input type="checkbox" name="conference_setup[]" value="t-shape"> <?php esc_html_e('T Shape', 'event-rfq-manager'); ?></label>
            </div>
        </div>

        <div class="erfq-form-section">
            <h2><?php esc_html_e('Audio Visual Requirements', 'event-rfq-manager'); ?></h2>
            <div class="erfq-checkbox-group">
                <label><input type="checkbox" name="av_requirements[]" value="projector-screen"> <?php esc_html_e('Projector & Screen', 'event-rfq-manager'); ?></label>
                <label><input type="checkbox" name="av_requirements[]" value="lapel-mics"> <?php esc_html_e('Lapel Mics', 'event-rfq-manager'); ?></label>
                <label><input type="checkbox" name="av_requirements[]" value="hand-mics"> <?php esc_html_e('Hand Mics', 'event-rfq-manager'); ?></label>
                <label><input type="checkbox" name="av_requirements[]" value="infrared-pointers"> <?php esc_html_e('Infrared Pointers', 'event-rfq-manager'); ?></label>
                <label><input type="checkbox" name="av_requirements[]" value="music-sound"> <?php esc_html_e('Music/Sound System', 'event-rfq-manager'); ?></label>
                <label><input type="checkbox" name="av_requirements[]" value="dance-floor"> <?php esc_html_e('Dance Floor', 'event-rfq-manager'); ?></label>
                <label><input type="checkbox" name="av_requirements[]" value="dj"> <?php esc_html_e('DJ', 'event-rfq-manager'); ?></label>
                <label><input type="checkbox" name="av_requirements[]" value="entertainers"> <?php esc_html_e('Entertainers', 'event-rfq-manager'); ?></label>
            </div>
        </div>

        <div class="erfq-form-section">
            <h2><?php esc_html_e('Transport - Airport Transfers', 'event-rfq-manager'); ?></h2>
            
            <h3><?php esc_html_e('Arrival', 'event-rfq-manager'); ?></h3>
            <div id="arrival-transfers">
                <div class="erfq-transfer-row">
                    <input type="date" name="arrival_transfers[0][date]" placeholder="<?php esc_attr_e('Date', 'event-rfq-manager'); ?>">
                    <input type="time" name="arrival_transfers[0][time]" placeholder="<?php esc_attr_e('Arrival Time', 'event-rfq-manager'); ?>">
                    <input type="text" name="arrival_transfers[0][flight]" placeholder="<?php esc_attr_e('Flight Number', 'event-rfq-manager'); ?>">
                    <input type="number" name="arrival_transfers[0][pax]" placeholder="<?php esc_attr_e('Number of Pax', 'event-rfq-manager'); ?>" min="0">
                </div>
            </div>
            <button type="button" class="erfq-add-row" data-target="arrival-transfers"><?php esc_html_e('+ Add Arrival', 'event-rfq-manager'); ?></button>

            <h3><?php esc_html_e('Departure', 'event-rfq-manager'); ?></h3>
            <div id="departure-transfers">
                <div class="erfq-transfer-row">
                    <input type="date" name="departure_transfers[0][date]" placeholder="<?php esc_attr_e('Date', 'event-rfq-manager'); ?>">
                    <input type="time" name="departure_transfers[0][time]" placeholder="<?php esc_attr_e('Departure Time', 'event-rfq-manager'); ?>">
                    <input type="text" name="departure_transfers[0][flight]" placeholder="<?php esc_attr_e('Flight Number', 'event-rfq-manager'); ?>">
                    <input type="number" name="departure_transfers[0][pax]" placeholder="<?php esc_attr_e('Number of Pax', 'event-rfq-manager'); ?>" min="0">
                </div>
            </div>
            <button type="button" class="erfq-add-row" data-target="departure-transfers"><?php esc_html_e('+ Add Departure', 'event-rfq-manager'); ?></button>
        </div>

        <div class="erfq-form-section">
            <h2><?php esc_html_e('Sightseeing/Excursions', 'event-rfq-manager'); ?></h2>
            <div id="sightseeing-list">
                <div class="erfq-sightseeing-row">
                    <input type="date" name="sightseeing[0][date]" placeholder="<?php esc_attr_e('Date', 'event-rfq-manager'); ?>">
                    <input type="time" name="sightseeing[0][time]" placeholder="<?php esc_attr_e('Time', 'event-rfq-manager'); ?>">
                    <select name="sightseeing[0][service]">
                        <option value=""><?php esc_html_e('Select Service', 'event-rfq-manager'); ?></option>
                        <option value="half-day"><?php esc_html_e('Half-Day', 'event-rfq-manager'); ?></option>
                        <option value="full-day"><?php esc_html_e('Full-Day', 'event-rfq-manager'); ?></option>
                        <option value="evening"><?php esc_html_e('Evening', 'event-rfq-manager'); ?></option>
                    </select>
                    <input type="number" name="sightseeing[0][pax]" placeholder="<?php esc_attr_e('Number of Pax', 'event-rfq-manager'); ?>" min="0">
                </div>
            </div>
            <button type="button" class="erfq-add-row" data-target="sightseeing-list"><?php esc_html_e('+ Add Excursion', 'event-rfq-manager'); ?></button>
        </div>

        <div class="erfq-form-section">
            <h2><?php esc_html_e('Special Add-On Services', 'event-rfq-manager'); ?></h2>
            <div class="erfq-form-field">
                <textarea name="special_services" rows="5" placeholder="<?php esc_attr_e('Please describe any special services or requirements...', 'event-rfq-manager'); ?>"></textarea>
            </div>
        </div>

        <div class="erfq-form-section">
            <h2><?php esc_html_e('Contact Information', 'event-rfq-manager'); ?></h2>
            
            <div class="erfq-form-field">
                <label for="contact_name"><?php esc_html_e('Name', 'event-rfq-manager'); ?> <span class="required">*</span></label>
                <input type="text" id="contact_name" name="contact_name" required>
            </div>

            <div class="erfq-form-field">
                <label for="contact_designation"><?php esc_html_e('Designation', 'event-rfq-manager'); ?></label>
                <input type="text" id="contact_designation" name="contact_designation">
            </div>

            <div class="erfq-form-field">
                <label for="contact_company"><?php esc_html_e('Company Name', 'event-rfq-manager'); ?></label>
                <input type="text" id="contact_company" name="contact_company">
            </div>

            <div class="erfq-form-field">
                <label for="contact_address"><?php esc_html_e('Address', 'event-rfq-manager'); ?></label>
                <textarea id="contact_address" name="contact_address" rows="3"></textarea>
            </div>

            <div class="erfq-form-row">
                <div class="erfq-form-field">
                    <label for="contact_mobile"><?php esc_html_e('Mobile Number', 'event-rfq-manager'); ?></label>
                    <input type="tel" id="contact_mobile" name="contact_mobile">
                </div>
                <div class="erfq-form-field">
                    <label for="contact_email"><?php esc_html_e('Email', 'event-rfq-manager'); ?> <span class="required">*</span></label>
                    <input type="email" id="contact_email" name="contact_email" required>
                </div>
            </div>
        </div>

        <div class="erfq-form-actions">
            <button type="submit" class="erfq-submit-btn"><?php esc_html_e('Submit Request for Quotation', 'event-rfq-manager'); ?></button>
        </div>

        <div id="erfq-message" class="erfq-message" style="display:none;"></div>
    </form>
</div>