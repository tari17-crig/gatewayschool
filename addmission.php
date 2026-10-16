<?php
/**
 * Gateway Education Services - Admission Form Processor
 * Migrated to Supabase (REST API)
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include Supabase REST configuration
require_once 'supabase_rest_config.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Sanitize and collect form inputs
    $surname      = isset($_POST['surname']) ? trim($_POST['surname']) : '';
    $firstname    = isset($_POST['firstname']) ? trim($_POST['firstname']) : '';
    $dateofbirth  = !empty($_POST['dateofbirth']) ? trim($_POST['dateofbirth']) : null;
    $age          = !empty($_POST['age']) ? (int)$_POST['age'] : null;
    $classapplied = isset($_POST['classapplied']) ? trim($_POST['classapplied']) : '';
    $formerschool = isset($_POST['formerschool']) ? trim($_POST['formerschool']) : '';
    $parentname   = isset($_POST['parentname']) ? trim($_POST['parentname']) : '';
    $nationality  = isset($_POST['nationality']) ? trim($_POST['nationality']) : '';
    $phone        = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $email        = isset($_POST['email']) ? trim($_POST['email']) : '';
    $message      = isset($_POST['message']) ? trim($_POST['message']) : '';

    // Basic validation
    if (empty($surname) || empty($firstname) || empty($phone) || empty($email) || empty($classapplied)) {
        die("Please fill in all required fields.");
    }

    $data = [
        'surname'         => $surname,
        'firstname'       => $firstname,
        'dateofbirth'     => $dateofbirth,
        'age'             => $age,
        'classapplied'    => $classapplied,
        'formerschool'    => $formerschool,
        'parentname'      => $parentname,
        'nationality'     => $nationality,
        'phone'           => $phone,
        'email'           => $email,
        'message'         => $message,
        'submission_date' => date('c') // ISO 8601 UTC timestamp
    ];

    $result = supabase_insert('admissions', $data);

    if ($result['success']) {
        // Successfully submitted
        header("Location: admission-thank-you.html");
        exit();
    } else {
        error_log("Supabase Admission insert failed: " . $result['error']);
        die("<h3>Submission failed</h3><p>" . htmlspecialchars($result['error']) . "</p><p><a href='addmission.html'>Return to form</a></p>");
    }
} else {
    // If accessed directly without POST
    header("Location: addmission.html");
    exit();
}
?>