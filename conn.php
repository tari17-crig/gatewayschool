<?php
$servername = "sql311.infinityfree.com";
$username = "if0_38666675	";  // default XAMPP username
$password = "Sillydogs12 ";      // default XAMPP password is empty
$dbname = "if0_38666675_gateway_education"; // database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "Connected successfully";
?>