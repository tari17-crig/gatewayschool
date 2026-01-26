<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
include 'conn.php';

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Prepare data
    $surname = $_POST['surname'];
    $firstname = $_POST['firstname'];
    $dateofbirth = $_POST['dateofbirth'];
    $age = $_POST['age'];
    $classapplied = $_POST['classapplied'];
    $formerschool = $_POST['formerschool'];
    $parentname = $_POST['parentname'];
    $nationality = $_POST['nationality'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $message = $_POST['message'];
    $submission_date = date('Y-m-d H:i:s');
    
    // SQL with correct column names (fixing the 'classapplied' typo)
    $sql = "INSERT INTO admissions (
        surname, firstname, dateofbirth, age, classapplied, 
        formerschool, parentname, nationality, phone, 
        email, message, submission_date
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    // Prepare the statement
    $stmt = $conn->prepare($sql);
    
    // Check if prepare() succeeded
    if ($stmt === false) {
        die("Prepare failed: " . $conn->error);
    }
    
    // Bind parameters
    $bind_result = $stmt->bind_param(
        "sssissssssss", 
        $surname, $firstname, $dateofbirth, $age, $classapplied,
        $formerschool, $parentname, $nationality, $phone,
        $email, $message, $submission_date
    );
    
    if ($bind_result === false) {
        die("Bind failed: " . $stmt->error);
    }
    
    // Execute
    if ($stmt->execute()) {
        header("Location: admission-thank-you.html");
        exit();
    } else {
        die("Execute failed: " . $stmt->error);
    }
    
    $stmt->close();
    $conn->close();
} else {
    header("Location: addmission.html");
    exit();
}
?>