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

    // Log to Google Sheets
    sts_log_to_sheets($first, $last, $email, $data['name'], $data['date'], $type, $tickets, $ref);

    echo "<p style='text-align:center;'>✅ Success! Check your email.</p>";
}

/**
 * Log ticket submission to Google Sheets
 */
function sts_log_to_sheets($first_name, $last_name, $email, $screening_name, $screening_date, $ticket_type, $num_tickets, $reference_number) {
    // Configuration
    $credentials_file = 'toronto-horror-film-festival-01544b154a0a.json';
    $sheet_id = '15uzyGEO4gvpPwg9alFY_5p06o0McJ6Z1gCcJ3sb8yTI';
    $sheet_tab = 'Sheet1';
    
    // Get the plugin/theme directory
    $base_dir = dirname(__FILE__);
    $credentials_path = $base_dir . '/' . $credentials_file;
    
    // Check if credentials file exists
    if (!file_exists($credentials_path)) {
        error_log('STS: Google Sheets credentials file not found at ' . $credentials_path);
        return;
    }
    
    // Read credentials
    $credentials_json = file_get_contents($credentials_path);
    if (!$credentials_json) {
        error_log('STS: Failed to read credentials file');
        return;
    }
    
    $credentials = json_decode($credentials_json, true);
    if (!$credentials) {
        error_log('STS: Failed to decode credentials JSON');
        return;
    }
    
    // Create JWT for authentication
    $jwt = sts_create_jwt($credentials);
    if (!$jwt) {
        error_log('STS: Failed to create JWT');
        return;
    }
    
    // Get access token
    $access_token = sts_get_access_token($jwt);
    if (!$access_token) {
        error_log('STS: Failed to obtain access token');
        return;
    }
    
    // Prepare row data
    $timestamp = current_time('mysql');
    $values = [
        [$timestamp, $first_name, $last_name, $email, $screening_name, $screening_date, $ticket_type, $num_tickets, $reference_number]
    ];
    
    // Append to sheet
    $result = sts_append_to_sheet($sheet_id, $sheet_tab, $values, $access_token);
    
    if (!$result) {
        error_log('STS: Failed to append row to Google Sheet');
    }
}

/**
 * Create JWT for Google API authentication
 */
function sts_create_jwt($credentials) {
    $now = time();
    $expire = $now + 3600;
    
    // Header
    $header = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
    $header_encoded = rtrim(strtr(base64_encode($header), '+/', '-_'), '=');
    
    // Payload
    $payload = [
        'iss' => $credentials['client_email'],
        'scope' => 'https://www.googleapis.com/auth/spreadsheets',
        'aud' => 'https://oauth2.googleapis.com/token',
        'exp' => $expire,
        'iat' => $now
    ];
    $payload_encoded = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');
    
    // Signature
    $signature_input = $header_encoded . '.' . $payload_encoded;
    $private_key = $credentials['private_key'];
    
    if (!openssl_sign($signature_input, $signature, $private_key, 'SHA256')) {
        error_log('STS: Failed to sign JWT');
        return false;
    }
    
    $signature_encoded = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
    
    return $signature_input . '.' . $signature_encoded;
}

/**
 * Exchange JWT for access token
 */
function sts_get_access_token($jwt) {
    $body = [
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion' => $jwt
    ];
    
    $response = wp_remote_post('https://oauth2.googleapis.com/token', [
        'method' => 'POST',
        'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
        'body' => http_build_query($body),
        'timeout' => 10
    ]);
    
    if (is_wp_error($response)) {
        error_log('STS: wp_remote_post failed: ' . $response->get_error_message());
        return false;
    }
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if (!isset($data['access_token'])) {
        error_log('STS: No access token in response: ' . $body);
        return false;
    }
    
    return $data['access_token'];
}

/**
 * Append values to Google Sheet
 */
function sts_append_to_sheet($sheet_id, $sheet_tab, $values, $access_token) {
    $range = $sheet_tab . '!A:I';
    $url = 'https://sheets.googleapis.com/v4/spreadsheets/' . urlencode($sheet_id) . '/values/' . urlencode($range) . ':append';
    
    $body = json_encode([
        'values' => $values,
        'majorDimension' => 'ROWS'
    ]);
    
    $response = wp_remote_post($url, [
        'method' => 'POST',
        'headers' => [
            'Authorization' => 'Bearer ' . $access_token,
            'Content-Type' => 'application/json'
        ],
        'body' => $body,
        'timeout' => 10
    ]);
    
    if (is_wp_error($response)) {
        error_log('STS: wp_remote_post failed: ' . $response->get_error_message());
        return false;
    }
    
    $http_code = wp_remote_retrieve_response_code($response);
    if ($http_code < 200 || $http_code >= 300) {
        error_log('STS: Google Sheets API error (HTTP ' . $http_code . '): ' . wp_remote_retrieve_body($response));
        return false;
    }
    
    return true;
}