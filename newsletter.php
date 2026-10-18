<?php
/**
 * Gateway Education Services - Newsletter Subscription Processor
 * Handles newsletter signups, saves to Supabase and local storage backup.
 * Supports both AJAX (JSON) requests and standard form submissions.
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);

// Include Supabase REST configuration & Mailer
require_once __DIR__ . '/supabase_rest_config.php';
require_once __DIR__ . '/mailer.php';

// Detect if this is an AJAX / fetch request
$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
    || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
    || (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false);

function sendResponse(bool $success, string $message, int $statusCode = 200, bool $isAjax = false) {
    if ($isAjax) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => $success,
            'message' => $message
        ]);
        exit();
    } else {
        // Fallback for standard browser form submission
        $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.html';
        $separator = (strpos($referer, '?') !== false) ? '&' : '?';
        $statusParam = $success ? 'newsletter=success' : 'newsletter=error&msg=' . urlencode($message);
        header("Location: " . $referer . $separator . $statusParam);
        exit();
    }
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    // If accessed directly via GET, redirect to home or return error
    if ($isAjax) {
        sendResponse(false, 'Method not allowed. Please use POST.', 405, true);
    } else {
        header("Location: index.html");
        exit();
    }
}

// Retrieve email from either JSON payload or standard POST
$email = '';
$contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';

if (strpos($contentType, 'application/json') !== false) {
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);
    if (is_array($data) && isset($data['email'])) {
        $email = trim($data['email']);
    }
}

if (empty($email) && isset($_POST['email'])) {
    $email = trim($_POST['email']);
}

// Validation
if (empty($email)) {
    sendResponse(false, 'Please enter your email address.', 400, $isAjax);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendResponse(false, 'Please provide a valid email address.', 400, $isAjax);
}

if (strlen($email) > 150) {
    sendResponse(false, 'Email address is too long (maximum 150 characters).', 400, $isAjax);
}

// 1. Local backup logging to ensure zero data loss
$dataDir = __DIR__ . '/data';
if (!is_dir($dataDir)) {
    @mkdir($dataDir, 0755, true);
}

$localLogFile = $dataDir . '/newsletter_subscribers.json';
$subscriberEntry = [
    'email'        => strtolower($email),
    'subscribed_at'=> date('c'),
    'ip_address'   => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    'user_agent'   => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
];

$existingList = [];
if (file_exists($localLogFile)) {
    $fileData = @file_get_contents($localLogFile);
    if (!empty($fileData)) {
        $existingList = json_decode($fileData, true) ?: [];
    }
}

// Check if already subscribed locally
$alreadySubscribed = false;
foreach ($existingList as $item) {
    if (isset($item['email']) && strtolower($item['email']) === strtolower($email)) {
        $alreadySubscribed = true;
        break;
    }
}

if (!$alreadySubscribed) {
    $existingList[] = $subscriberEntry;
    @file_put_contents($localLogFile, json_encode($existingList, JSON_PRETTY_PRINT), LOCK_EX);
}

// 2. Insert into Supabase
// First attempt: try dedicated 'newsletter_subscribers' table
$newsletterPayload = [
    'email'         => strtolower($email),
    'status'        => 'subscribed',
    'ip_address'    => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    'subscribed_at' => date('c')
];

$supabaseResult = supabase_insert('newsletter_subscribers', $newsletterPayload);

// If 'newsletter_subscribers' table doesn't exist (HTTP 404), fallback to 'newsletters' table
if (!$supabaseResult['success'] && $supabaseResult['status'] === 404) {
    $supabaseResult = supabase_insert('newsletters', $newsletterPayload);
}

// If neither dedicated table exists, fallback to 'contacts' table
if (!$supabaseResult['success'] && $supabaseResult['status'] === 404) {
    $contactsPayload = [
        'name'            => 'Newsletter Subscriber',
        'email'           => strtolower($email),
        'phone'           => '',
        'subject'         => 'Newsletter Subscription',
        'message'         => 'New subscriber for Gateway Education Services newsletter: ' . strtolower($email),
        'submission_date' => date('c')
    ];
    $supabaseResult = supabase_insert('contacts', $contactsPayload);
}

// 3. Dispatch confirmation email directly to the subscriber's inbox
$mailResult = send_newsletter_confirmation(strtolower($email));

// Check for duplicate key / already subscribed status
if (!$supabaseResult['success'] && $supabaseResult['status'] === 409) {
    $msg = $mailResult['success']
        ? 'You are already subscribed to our newsletter! A confirmation email has been sent to your inbox.'
        : 'You are already subscribed to our newsletter!';
    sendResponse(true, $msg, 200, $isAjax);
}

if ($supabaseResult['success'] || $alreadySubscribed) {
    if ($mailResult['success']) {
        sendResponse(true, 'Thank you for subscribing! A confirmation email has been sent to your email inbox.', 200, $isAjax);
    } else {
        error_log("Newsletter saved, but email dispatch failed: " . ($mailResult['error'] ?? 'Unknown mail error'));
        sendResponse(true, 'Thank you for subscribing! You are registered for our latest updates and news.', 200, $isAjax);
    }
} else {
    // If Supabase failed but we saved locally, we still provide a good user experience
    if (file_exists($localLogFile)) {
        error_log("Supabase insert notice: " . ($supabaseResult['error'] ?? 'Unknown error') . " - saved to local backup.");
        $msg = $mailResult['success']
            ? 'Thank you for subscribing! A confirmation email has been sent to your email inbox.'
            : 'Thank you for subscribing! You are registered for our latest updates and news.';
        sendResponse(true, $msg, 200, $isAjax);
    } else {
        error_log("Newsletter subscription error: " . ($supabaseResult['error'] ?? 'Unknown error'));
        sendResponse(false, 'Unable to subscribe at the moment. Please try again shortly.', 500, $isAjax);
    }
}
