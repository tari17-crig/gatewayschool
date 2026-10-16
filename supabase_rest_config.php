<?php

/**
 * Gateway Education Services - Supabase REST API Configuration & Helper
 *
 * Works on ALL hosting providers including InfinityFree, cPanel, shared hosts,
 * VPS, and local servers without needing any PostgreSQL PHP extension!
 *
 * Instructions:
 * 1. Set environment variables SUPABASE_URL and SUPABASE_KEY (or configure them in your hosting environment).
 * 2. In your Supabase Dashboard, go to: Project Settings -> API
 * 3. Copy "Project URL" and set as SUPABASE_URL.
 * 4. Copy "anon" public key (or service_role key) and set as SUPABASE_KEY.
 */

$supabaseUrl = getenv('SUPABASE_URL');
if ($supabaseUrl === false) {
    // Fallback for development only - remove in production
    $supabaseUrl = 'https://qtvmyjgmgmizeglrunjn.supabase.co';
    error_log('WARNING: SUPABASE_URL not set in environment. Using hardcoded value for development only.');
}
define('SUPABASE_URL', $supabaseUrl);

$supabaseKey = getenv('SUPABASE_KEY');
if ($supabaseKey === false) {
    // Fallback for development only - remove in production
    $supabaseKey = 'sb_publishable_CBayUb6GBwKIuKKgDQRW7g_dUVjxmfr';
    error_log('WARNING: SUPABASE_KEY not set in environment. Using hardcoded value for development only.');
}
define('SUPABASE_KEY', $supabaseKey);

/**
 * Inserts a record into a Supabase table using the REST API (PostgREST).
 *
 * @param string $table The name of the table (e.g., 'admissions', 'contacts')
 * @param array $data Associative array of column => value
 * @return array ['success' => bool, 'status' => int, 'response' => mixed, 'error' => string|null]
 */
function supabase_insert($table, array $data) {
    $endpoint = rtrim(SUPABASE_URL, '/' ) . '/rest/v1/' . urlencode($table);
    $jsonData = json_encode($data);

    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'apikey: ' . SUPABASE_KEY,
        'Authorization: Bearer ' . SUPABASE_KEY,
        'Prefer: return=minimal' // 'return=minimal' is faster, or 'return=representation' to get back inserted rows
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    // Allow connection on local Windows machines without pre-installed CA certs
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    if (PHP_VERSION_ID < 80500 && is_resource($ch)) {
        curl_close($ch);
    }

    if ($curlErr) {
        return [
            'success' => false,
            'status'  => 0,
            'response' => null,
            'error'   => 'cURL Error: ' . $curlErr
        ];
    }

    // 200 OK or 201 Created indicates successful insert
    if ($httpCode >= 200 && $httpCode < 300) {
        return [
            'success' => true,
            'status'  => $httpCode,
            'response' => json_decode($response, true),
            'error'   => null
        ];
    } else {
        return [
            'success' => false,
            'status'  => $httpCode,
            'response' => json_decode($response, true),
            'error'   => 'Supabase REST API Error (HTTP ' . $httpCode . '): ' . $response
        ];
    }
}
?>

