<?php
/*
Plugin Name: Ticket System
Description: Custom ticket booking system
Version: 1.0
*/

// Include files
require_once plugin_dir_path(__FILE__) . 'screenings.php';
require_once plugin_dir_path(__FILE__) . 'form.php';
require_once plugin_dir_path(__FILE__) . 'handler.php';

// Set default seats ONCE
function sts_set_default_seats() {
    $screenings = sts_get_screenings();

    foreach ($screenings as $key => $data) {
        if (get_option("remaining_seats_$key") === false) {
            update_option("remaining_seats_$key", 200);
        }
    }
}
add_action('init', 'sts_set_default_seats');

// Register shortcode
add_shortcode('ticket_form', 'sts_render_ticket_form');

// Handle form submission
add_action('init', 'sts_handle_ticket_submission');