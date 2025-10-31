<?php
$servername = "localhost";
// REPLACE with your Database name
$dbname = "s67160205";
// REPLACE with Database user
$username = "s67160205";
// REPLACE with Database user password
$password = "C2t6vBKm";

// Establish connection to MySQL database
$conn = new mysqli($servername, $username, $password, $dbname);

// Check if connection established successfully
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>