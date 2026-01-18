/**
 * Event RFQ Frontend JavaScript
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        
        console.log('Event RFQ JS Loaded');
        
        // Counter for dynamically added rows
        let arrivalCounter = 1;
        let departureCounter = 1;
        let sightseeingCounter = 1;

        // Debug: Check if venue-list exists
        console.log('Venue list element:', $('#venue-list').length);

        // Add venue row
        $(document).on('click', '.erfq-add-row[data-target="venue-list"]', function(e) {
            e.preventDefault();
            console.log('Add Venue button clicked!');
            
            const newRow = `
                <div class="erfq-venue-row">
                    <input type="text" name="venues[]" placeholder="Enter venue name">
                    <button type="button" class="erfq-remove-row">Remove</button>
                </div>
            `;
            $('#venue-list').append(newRow);
            console.log('New venue row added');
        });

        // Add arrival transfer row
        $(document).on('click', '.erfq-add-row[data-target="arrival-transfers"]', function(e) {
            e.preventDefault();
            const newRow = `
                <div class="erfq-transfer-row">
                    <input type="date" name="arrival_transfers[${arrivalCounter}][date]" placeholder="Date">
                    <input type="time" name="arrival_transfers[${arrivalCounter}][time]" placeholder="Arrival Time">
                    <input type="text" name="arrival_transfers[${arrivalCounter}][flight]" placeholder="Flight Number">
                    <input type="number" name="arrival_transfers[${arrivalCounter}][pax]" placeholder="Number of Pax" min="0">
                    <button type="button" class="erfq-remove-row">Remove</button>
                </div>
            `;
            $('#arrival-transfers').append(newRow);
            arrivalCounter++;
        });

        // Add departure transfer row
        $(document).on('click', '.erfq-add-row[data-target="departure-transfers"]', function(e) {
            e.preventDefault();
            const newRow = `
                <div class="erfq-transfer-row">
                    <input type="date" name="departure_transfers[${departureCounter}][date]" placeholder="Date">
                    <input type="time" name="departure_transfers[${departureCounter}][time]" placeholder="Departure Time">
                    <input type="text" name="departure_transfers[${departureCounter}][flight]" placeholder="Flight Number">
                    <input type="number" name="departure_transfers[${departureCounter}][pax]" placeholder="Number of Pax" min="0">
                    <button type="button" class="erfq-remove-row">Remove</button>
                </div>
            `;
            $('#departure-transfers').append(newRow);
            departureCounter++;
        });

        // Add sightseeing row
        $(document).on('click', '.erfq-add-row[data-target="sightseeing-list"]', function(e) {
            e.preventDefault();
            const newRow = `
                <div class="erfq-sightseeing-row">
                    <input type="date" name="sightseeing[${sightseeingCounter}][date]" placeholder="Date">
                    <input type="time" name="sightseeing[${sightseeingCounter}][time]" placeholder="Time">
                    <select name="sightseeing[${sightseeingCounter}][service]">
                        <option value="">Select Service</option>
                        <option value="half-day">Half-Day</option>
                        <option value="full-day">Full-Day</option>
                        <option value="evening">Evening</option>
                    </select>
                    <input type="number" name="sightseeing[${sightseeingCounter}][pax]" placeholder="Number of Pax" min="0">
                    <button type="button" class="erfq-remove-row">Remove</button>
                </div>
            `;
            $('#sightseeing-list').append(newRow);
            sightseeingCounter++;
        });

        // Remove row
        $(document).on('click', '.erfq-remove-row', function(e) {
            e.preventDefault();
            $(this).closest('.erfq-transfer-row, .erfq-sightseeing-row, .erfq-venue-row').remove();
        });

        // Form submission
        $('#erfq-form').on('submit', function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const $submitBtn = $form.find('.erfq-submit-btn');
            const $message = $('#erfq-message');
            
            // Disable submit button
            $submitBtn.prop('disabled', true).text('Submitting...');
            $message.hide();
            
            // Get form data
            const formData = new FormData(this);
            formData.append('action', 'erfq_submit_form');
            
            // AJAX request
            $.ajax({
                url: erfqAjax.ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        $message
                            .removeClass('error')
                            .addClass('success')
                            .html(response.data.message)
                            .show();
                        
                        // Reset form
                        $form[0].reset();
                        
                        // Scroll to message
                        $('html, body').animate({
                            scrollTop: $message.offset().top - 100
                        }, 500);
                        
                    } else {
                        $message
                            .removeClass('success')
                            .addClass('error')
                            .html(response.data.message)
                            .show();
                    }
                },
                error: function() {
                    $message
                        .removeClass('success')
                        .addClass('error')
                        .html('An error occurred. Please try again.')
                        .show();
                },
                complete: function() {
                    $submitBtn.prop('disabled', false).text('Submit Request for Quotation');
                }
            });
        });

        // Date validation: To date should be after From date
        $('#date_from, #date_to').on('change', function() {
            const dateFrom = $('#date_from').val();
            const dateTo = $('#date_to').val();
            
            if (dateFrom && dateTo && dateTo < dateFrom) {
                alert('End date must be after start date');
                $('#date_to').val('');
            }
        });

    });

})(jQuery);