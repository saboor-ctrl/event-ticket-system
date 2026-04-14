<?php

function sts_handle_ticket_submission() {

    if (!isset($_POST['submit_ticket'])) return;

    $screenings = sts_get_screenings();

    $screening = sanitize_text_field($_POST['screening']);
    $type = sanitize_text_field($_POST['ticket_type']);
    $first = sanitize_text_field($_POST['first_name']);
    $last = sanitize_text_field($_POST['last_name']);
    $email = sanitize_email($_POST['email']);
    $tickets = intval($_POST['tickets']);
    $ref = isset($_POST['reference_number']) ? sanitize_text_field($_POST['reference_number']) : '';

    if (!isset($screenings[$screening])) return;

    $data = $screenings[$screening];

    $remaining = get_option("remaining_seats_$screening", 200);

    if ($type === 'reserved') {
        if ($remaining <= 0) {
            echo "<p>Sold Out</p>";
            return;
        }

        if ($tickets > $remaining) {
            echo "<p>Not enough seats available</p>";
            return;
        }

        update_option("remaining_seats_$screening", $remaining - $tickets);
    }

    // Email content
    if ($type === "reserved") {
        $subject = "Your Tickets Have Been Reserved";
        $message = "Hi $first $last,

You have reserved $tickets ticket(s) for:
{$data['name']}
{$data['date']}
{$data['location']}

Show this email at entry.";
    } else {
        $subject = "FCFS Ticket Info";
        $message = "Hi $first $last,

You signed up for $tickets ticket(s) for:
{$data['name']}
{$data['date']}
{$data['location']}

Seats are first come, first serve.";
    }

    wp_mail($email, $subject, $message);

    echo "<p style='text-align:center;'>Success! Check your email.</p>";
}