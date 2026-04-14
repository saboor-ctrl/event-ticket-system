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

    // Handle reserved tickets
    if ($type === 'reserved') {

        if ($remaining <= 0) {
            echo "<p style='text-align:center;'>❌ Sold Out</p>";
            return;
        }

        if ($tickets > $remaining) {
            echo "<p style='text-align:center;'>❌ Not enough seats available</p>";
            return;
        }

        update_option("remaining_seats_$screening", $remaining - $tickets);
    }

    // Email headers (for HTML emails)
    $headers = ['Content-Type: text/html; charset=UTF-8'];

    // Email content
    if ($type === "reserved") {

        // ✅ UPDATED SUBJECT (Reserved)
        $subject = "Your Reserved Seat is Confirmed – " . $data['name'];

        $message = "
        <div style='font-family: Arial; max-width: 600px; margin: auto;'>
            <h2>Your Tickets Are Reserved 🎉</h2>

            <p>Hi <strong>$first $last</strong>,</p>

            <p>Thank you for reserving your ticket(s)! Here are your details:</p>

            <div style='background:#f5f5f5; padding:15px; border-radius:8px;'>
                <p><strong>🎬 Screening:</strong> {$data['name']}</p>
                <p><strong>📅 Date:</strong> {$data['date']}</p>
                <p><strong>📍 Location:</strong> {$data['location']}</p>
                <p><strong>🎟️ Tickets:</strong> $tickets</p>
            </div>

            <p style='margin-top:15px;'>
                Please bring this email as proof of reservation when you arrive.
            </p>

            <p>We look forward to seeing you!</p>

            <hr>
            <p style='font-size:12px; color:gray;'>Toronto Horror Film Festival</p>
        </div>
        ";

    } else {

        // ✅ UPDATED SUBJECT (FCFS)
        $subject = "You're Registered (FCFS – Arrive Early) – " . $data['name'];

        $message = "
        <div style='font-family: Arial; max-width: 600px; margin: auto;'>
            <h2>You're Signed Up! 👻</h2>

            <p>Hi <strong>$first $last</strong>,</p>

            <p>You’ve successfully signed up for the following screening:</p>

            <div style='background:#f5f5f5; padding:15px; border-radius:8px;'>
                <p><strong>🎬 Screening:</strong> {$data['name']}</p>
                <p><strong>📅 Date:</strong> {$data['date']}</p>
                <p><strong>📍 Location:</strong> {$data['location']}</p>
                <p><strong>🎟️ Tickets:</strong> $tickets</p>
            </div>

            <p style='margin-top:15px;'>
                ⚠️ Seating is <strong>first come, first serve</strong> and not guaranteed.
                Please arrive early to secure your spot.
            </p>

            <p>See you there!</p>

            <hr>
            <p style='font-size:12px; color:gray;'>Toronto Horror Film Festival</p>
        </div>
        ";
    }

    // Send email
    wp_mail($email, $subject, $message, $headers);

    echo "<p style='text-align:center;'>✅ Success! Check your email.</p>";
}