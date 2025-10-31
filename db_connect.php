<?php
$servername = "localhost";
// REPLACE with your Database name
$dbname = "username";
// REPLACE with Database user
$username = "your_username";
// REPLACE with Database user password
$password = "your_password";

// Establish connection to MySQL database
$conn = new mysqli($servername, $username, $password, $dbname);

// Check if connection established successfully
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
