<?php
error_reporting(E_ALL);
ini_set('display_errors', 1); 

$servername = "localhost";
$username = "phpmyadmin";
$password = "Daniel@090501";
$dbname = "wisetech";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>