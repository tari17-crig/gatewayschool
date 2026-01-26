<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
include 'conn.php';

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $subject = $_POST['subject'];
    $message = $_POST['message'];
    
    // Insert into database (removed email column)
    $sql = "INSERT INTO contacts (name, phone, subject, message, submission_date) 
            VALUES (?, ?, ?, ?, NOW())";
    
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        die("Prepare failed: " . $conn->error);
    }
    
    // Changed to 4 parameters (ssss) since we removed email
    $bind_result = $stmt->bind_param("ssss", $name, $phone, $subject, $message);

    if ($bind_result === false) {
        die("Bind failed: " . $stmt->error);
    }

    if ($stmt->execute()) {
        // Redirect to prevent form resubmission
        header("Location: contact-thank-you.html");
        exit();
    } else {
        die("Error: " . $stmt->error);
    }
    
    $stmt->close();
    $conn->close();
} else {
    // If someone tries to access this page directly
    header("Location: contactus.html");
    exit();
}
?>