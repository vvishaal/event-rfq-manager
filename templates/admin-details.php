<?php
/**
 * Admin RFQ Details Template
 */

if (!defined('ABSPATH')) {
    exit;
}

$post_id = $post->ID;

// Get all meta data
$date_from = get_post_meta($post_id, '_erfq_date_from', true);
$date_to = get_post_meta($post_id, '_erfq_date_to', true);
$destination = get_post_meta($post_id, '_erfq_destination', true);
$venues = get_post_meta($post_id, '_erfq_venues', true);
$category = get_post_meta($post_id, '_erfq_category', true);
$adults = get_post_meta($post_id, '_erfq_adults', true);
$children = get_post_meta($post_id, '_erfq_children', true);
$rooms = get_post_meta($post_id, '_erfq_rooms', true);
$meals = get_post_meta($post_id, '_erfq_meals', true);
$conference_setup = get_post_meta($post_id, '_erfq_conference_setup', true);
$av_requirements = get_post_meta($post_id, '_erfq_av_requirements', true);
$arrival_transfers = get_post_meta($post_id, '_erfq_arrival_transfers', true);
$departure_transfers = get_post_meta($post_id, '_erfq_departure_transfers', true);
$sightseeing = get_post_meta($post_id, '_erfq_sightseeing', true);
$special_services = get_post_meta($post_id, '_erfq_special_services', true);
$contact_name = get_post_meta($post_id, '_erfq_contact_name', true);
$contact_designation = get_post_meta($post_id, '_erfq_contact_designation', true);
$contact_company = get_post_meta($post_id, '_erfq_contact_company', true);
$contact_address = get_post_meta($post_id, '_erfq_contact_address', true);
$contact_mobile = get_post_meta($post_id, '_erfq_contact_mobile', true);
$contact_email = get_post_meta($post_id, '_erfq_contact_email', true);
$status = get_post_meta($post_id, '_erfq_status', true);
$submission_date = get_post_meta($post_id, '_erfq_submission_date', true);
?>

<div class="erfq-admin-details">
    
    <!-- Status Section -->
    <div class="erfq-admin-section">
        <h3><?php esc_html_e('Status', 'event-rfq-manager'); ?></h3>
        <p>
            <strong><?php esc_html_e('Current Status:', 'event-rfq-manager'); ?></strong>
            <select id="erfq-status" name="erfq_status">
                <option value="pending" <?php selected($status, 'pending'); ?>><?php esc_html_e('Pending', 'event-rfq-manager'); ?></option>
                <option value="processed" <?php selected($status, 'processed'); ?>><?php esc_html_e('Processed', 'event-rfq-manager'); ?></option>
            </select>
        </p>
        <p><strong><?php esc_html_e('Submission Date:', 'event-rfq-manager'); ?></strong> <?php echo esc_html($submission_date); ?></p>
    </div>

    <!-- Basic Information -->
    <div class="erfq-admin-section">
        <h3><?php esc_html_e('Basic Information', 'event-rfq-manager'); ?></h3>
        <table class="erfq-details-table">
            <tr>
                <th><?php esc_html_e('Date From:', 'event-rfq-manager'); ?></th>
                <td><?php echo esc_html($date_from); ?></td>
            </tr>
            <tr>
                <th><?php esc_html_e('Date To:', 'event-rfq-manager'); ?></th>
                <td><?php echo esc_html($date_to); ?></td>
            </tr>
            <tr>
                <th><?php esc_html_e('Destination:', 'event-rfq-manager'); ?></th>
                <td><?php echo esc_html($destination); ?></td>
            </tr>
            <?php if (!empty($venues) && is_array($venues)): ?>
            <tr>
                <th><?php esc_html_e('Venues:', 'event-rfq-manager'); ?></th>
                <td><?php echo esc_html(implode(', ', array_filter($venues))); ?></td>
            </tr>
            <?php endif; ?>
            <tr>
                <th><?php esc_html_e('Category:', 'event-rfq-manager'); ?></th>
                <td><?php echo esc_html(ucwords(str_replace('-', ' ', $category))); ?></td>
            </tr>
            <tr>
                <th><?php esc_html_e('Number of Guests:', 'event-rfq-manager'); ?></th>
                <td><?php echo esc_html(sprintf(__('%d Adults, %d Children', 'event-rfq-manager'), $adults, $children)); ?></td>
            </tr>
        </table>
    </div>

    <!-- Accommodation -->
    <?php if (!empty($rooms) && is_array($rooms)): ?>
    <div class="erfq-admin-section">
        <h3><?php esc_html_e('Accommodation', 'event-rfq-manager'); ?></h3>
        <p><?php echo esc_html(implode(', ', array_map('ucfirst', $rooms))); ?></p>
    </div>
    <?php endif; ?>

    <!-- Food & Beverage -->
    <?php if (!empty($meals) && is_array($meals)): ?>
    <div class="erfq-admin-section">
        <h3><?php esc_html_e('Food & Beverage', 'event-rfq-manager'); ?></h3>
        <p><?php echo esc_html(implode(', ', array_map('ucfirst', $meals))); ?></p>
    </div>
    <?php endif; ?>

    <!-- Conference Setup -->
    <?php if (!empty($conference_setup) && is_array($conference_setup)): ?>
    <div class="erfq-admin-section">
        <h3><?php esc_html_e('Conference Setup', 'event-rfq-manager'); ?></h3>
        <p><?php echo esc_html(implode(', ', array_map('ucwords', str_replace('-', ' ', $conference_setup)))); ?></p>
    </div>
    <?php endif; ?>

    <!-- Audio Visual Requirements -->
    <?php if (!empty($av_requirements) && is_array($av_requirements)): ?>
    <div class="erfq-admin-section">
        <h3><?php esc_html_e('Audio Visual Requirements', 'event-rfq-manager'); ?></h3>
        <p><?php echo esc_html(implode(', ', array_map('ucwords', str_replace('-', ' ', $av_requirements)))); ?></p>
    </div>
    <?php endif; ?>

    <!-- Airport Transfers -->
    <?php if ((!empty($arrival_transfers) && is_array($arrival_transfers)) || (!empty($departure_transfers) && is_array($departure_transfers))): ?>
    <div class="erfq-admin-section">
        <h3><?php esc_html_e('Airport Transfers', 'event-rfq-manager'); ?></h3>
        
        <?php if (!empty($arrival_transfers) && is_array($arrival_transfers)): ?>
        <h4><?php esc_html_e('Arrivals', 'event-rfq-manager'); ?></h4>
        <table class="erfq-details-table">
            <thead>
                <tr>
                    <th><?php esc_html_e('Date', 'event-rfq-manager'); ?></th>
                    <th><?php esc_html_e('Time', 'event-rfq-manager'); ?></th>
                    <th><?php esc_html_e('Flight', 'event-rfq-manager'); ?></th>
                    <th><?php esc_html_e('Pax', 'event-rfq-manager'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($arrival_transfers as $transfer): ?>
                <tr>
                    <td><?php echo esc_html($transfer['date'] ?? ''); ?></td>
                    <td><?php echo esc_html($transfer['time'] ?? ''); ?></td>
                    <td><?php echo esc_html($transfer['flight'] ?? ''); ?></td>
                    <td><?php echo esc_html($transfer['pax'] ?? ''); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>

        <?php if (!empty($departure_transfers) && is_array($departure_transfers)): ?>
        <h4><?php esc_html_e('Departures', 'event-rfq-manager'); ?></h4>
        <table class="erfq-details-table">
            <thead>
                <tr>
                    <th><?php esc_html_e('Date', 'event-rfq-manager'); ?></th>
                    <th><?php esc_html_e('Time', 'event-rfq-manager'); ?></th>
                    <th><?php esc_html_e('Flight', 'event-rfq-manager'); ?></th>
                    <th><?php esc_html_e('Pax', 'event-rfq-manager'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($departure_transfers as $transfer): ?>
                <tr>
                    <td><?php echo esc_html($transfer['date'] ?? ''); ?></td>
                    <td><?php echo esc_html($transfer['time'] ?? ''); ?></td>
                    <td><?php echo esc_html($transfer['flight'] ?? ''); ?></td>
                    <td><?php echo esc_html($transfer['pax'] ?? ''); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Sightseeing -->
    <?php if (!empty($sightseeing) && is_array($sightseeing)): ?>
    <div class="erfq-admin-section">
        <h3><?php esc_html_e('Sightseeing/Excursions', 'event-rfq-manager'); ?></h3>
        <table class="erfq-details-table">
            <thead>
                <tr>
                    <th><?php esc_html_e('Date', 'event-rfq-manager'); ?></th>
                    <th><?php esc_html_e('Time', 'event-rfq-manager'); ?></th>
                    <th><?php esc_html_e('Service', 'event-rfq-manager'); ?></th>
                    <th><?php esc_html_e('Pax', 'event-rfq-manager'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sightseeing as $tour): ?>
                <tr>
                    <td><?php echo esc_html($tour['date'] ?? ''); ?></td>
                    <td><?php echo esc_html($tour['time'] ?? ''); ?></td>
                    <td><?php echo esc_html(ucwords(str_replace('-', ' ', $tour['service'] ?? ''))); ?></td>
                    <td><?php echo esc_html($tour['pax'] ?? ''); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- Special Services -->
    <?php if (!empty($special_services)): ?>
    <div class="erfq-admin-section">
        <h3><?php esc_html_e('Special Add-On Services', 'event-rfq-manager'); ?></h3>
        <p><?php echo nl2br(esc_html($special_services)); ?></p>
    </div>
    <?php endif; ?>

    <!-- Contact Information -->
    <div class="erfq-admin-section">
        <h3><?php esc_html_e('Contact Information', 'event-rfq-manager'); ?></h3>
        <table class="erfq-details-table">
            <tr>
                <th><?php esc_html_e('Name:', 'event-rfq-manager'); ?></th>
                <td><?php echo esc_html($contact_name); ?></td>
            </tr>
            <?php if (!empty($contact_designation)): ?>
            <tr>
                <th><?php esc_html_e('Designation:', 'event-rfq-manager'); ?></th>
                <td><?php echo esc_html($contact_designation); ?></td>
            </tr>
            <?php endif; ?>
            <?php if (!empty($contact_company)): ?>
            <tr>
                <th><?php esc_html_e('Company:', 'event-rfq-manager'); ?></th>
                <td><?php echo esc_html($contact_company); ?></td>
            </tr>
            <?php endif; ?>
            <?php if (!empty($contact_address)): ?>
            <tr>
                <th><?php esc_html_e('Address:', 'event-rfq-manager'); ?></th>
                <td><?php echo nl2br(esc_html($contact_address)); ?></td>
            </tr>
            <?php endif; ?>
            <?php if (!empty($contact_mobile)): ?>
            <tr>
                <th><?php esc_html_e('Mobile:', 'event-rfq-manager'); ?></th>
                <td><?php echo esc_html($contact_mobile); ?></td>
            </tr>
            <?php endif; ?>
            <tr>
                <th><?php esc_html_e('Email:', 'event-rfq-manager'); ?></th>
                <td><a href="mailto:<?php echo esc_attr($contact_email); ?>"><?php echo esc_html($contact_email); ?></a></td>
            </tr>
        </table>
    </div>

</div>

<script>
jQuery(document).ready(function($) {
    $('#erfq-status').on('change', function() {
        var status = $(this).val();
        $.post(ajaxurl, {
            action: 'erfq_update_status',
            post_id: <?php echo $post_id; ?>,
            status: status,
            nonce: '<?php echo wp_create_nonce('erfq_update_status'); ?>'
        }, function(response) {
            if (response.success) {
                alert('Status updated successfully!');
            }
        });
    });
});
</script>