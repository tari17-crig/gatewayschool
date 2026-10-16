<?php
/**
 * Gateway Education Services - Contact Form Processor
 * Migrated to Supabase (REST API)
 * Bug fixed: Properly captures and saves the sender's email address.
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include Supabase REST configuration
require_once 'supabase_rest_config.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Sanitize and collect form inputs
    $name    = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email   = isset($_POST['email']) ? trim($_POST['email']) : '';
    $phone   = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $subject = isset($_POST['subject']) ? trim($_POST['subject']) : '';
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';

    // Basic validation
    if (empty($name) || empty($email) || empty($message)) {
        die("Please fill in all required fields (Name, Email, and Message).");
    }

    $data = [
        'name'            => $name,
        'email'           => $email,
        'phone'           => $phone,
        'subject'         => $subject,
        'message'         => $message,
        'submission_date' => date('c') // ISO 8601 UTC timestamp
    ];

    $result = supabase_insert('contacts', $data);

    if ($result['success']) {
        // Successfully submitted
        header("Location: contact-thank-you.html");
        exit();
    } else {
        error_log("Supabase Contact insert failed: " . $result['error']);
        die("<h3>Message submission failed</h3><p>" . htmlspecialchars($result['error']) . "</p><p><a href='contactus.html'>Return to form</a></p>");
    }
} else {
    // If accessed directly without POST
    header("Location: contactus.html");
    exit();
}
?>