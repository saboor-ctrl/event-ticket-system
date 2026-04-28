<?php

/**
 * Standalone Google Sheets API test script
 * Usage: php test-sheets.php
 */

// Configuration
$credentials_file = __DIR__ . '/toronto-horror-film-festival-01544b154a0a.json';
$sheet_id = '15uzyGEO4gvpPwg9alFY_5p06o0McJ6Z1gCcJ3sb8yTI';
$sheet_tab = 'Sheet1';

// Test data
$test_row = [
    'TEST',                      // Timestamp
    'Jane',                       // First Name
    'Doe',                        // Last Name
    'test@test.com',              // Email
    'Where Darkness Dwells',      // Screening Name
    'Apr 10 7PM',                 // Date
    'reserved',                   // Ticket Type
    '2',                          // Number of Tickets
    'REF123'                      // Reference Number
];

// Check if credentials file exists
if (!file_exists($credentials_file)) {
    echo "ERROR: Credentials file not found at $credentials_file\n";
    exit(1);
}

// Read credentials
$credentials_json = file_get_contents($credentials_file);
if (!$credentials_json) {
    echo "ERROR: Failed to read credentials file\n";
    exit(1);
}

$credentials = json_decode($credentials_json, true);
if (!$credentials) {
    echo "ERROR: Failed to decode credentials JSON\n";
    exit(1);
}

echo "[*] Credentials loaded\n";

// Create JWT
$jwt = create_jwt($credentials);
if (!$jwt) {
    echo "ERROR: Failed to create JWT\n";
    exit(1);
}

echo "[*] JWT created\n";

// Get access token
$access_token = get_access_token($jwt);
if (!$access_token) {
    echo "ERROR: Failed to obtain access token\n";
    exit(1);
}

echo "[*] Access token obtained\n";

// Append to sheet
$success = append_to_sheet($sheet_id, $sheet_tab, [$test_row], $access_token);

if ($success) {
    echo "[✓] SUCCESS: Test row appended to Google Sheet\n";
    exit(0);
} else {
    echo "[✗] FAILED: Could not append row to Google Sheet\n";
    exit(1);
}

/**
 * Create JWT for Google API authentication
 */
function create_jwt($credentials) {
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
        echo "ERROR: Failed to sign JWT\n";
        return false;
    }
    
    $signature_encoded = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
    
    return $signature_input . '.' . $signature_encoded;
}

/**
 * Exchange JWT for access token using curl
 */
function get_access_token($jwt) {
    $url = 'https://oauth2.googleapis.com/token';
    
    $data = [
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion' => $jwt
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    
    if ($curl_error) {
        echo "ERROR: curl request failed: $curl_error\n";
        return false;
    }
    
    $data = json_decode($response, true);
    
    if (!isset($data['access_token'])) {
        echo "ERROR: No access token in response\n";
        echo "Response: $response\n";
        return false;
    }
    
    return $data['access_token'];
}

/**
 * Append values to Google Sheet using curl
 */
function append_to_sheet($sheet_id, $sheet_tab, $values, $access_token) {
    $range = $sheet_tab . '!A:I';
    $url = 'https://sheets.googleapis.com/v4/spreadsheets/' . urlencode($sheet_id) . '/values/' . urlencode($range) . ':append?valueInputOption=RAW';
    
    $body = json_encode([
        'values' => $values,
        'majorDimension' => 'ROWS'
    ]);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $access_token,
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    
    if ($curl_error) {
        echo "ERROR: curl request failed: $curl_error\n";
        return false;
    }
    
    if ($http_code < 200 || $http_code >= 300) {
        echo "ERROR: Google Sheets API returned HTTP $http_code\n";
        echo "Response: $response\n";
        return false;
    }
    
    return true;
}
