<?php
$host = "localhost";       // Database host
$user = "root";   // Database username
$password = ""; // Database password
$database = "new"; // Database name

// Create connection
$conn = new mysqli($host, $user, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Optional: Set charset to utf8mb4
$conn->set_charset("utf8mb4");
?>
