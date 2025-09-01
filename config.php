<?php
error_reporting(E_ALL); // Keep these for now to confirm success, then remove later
ini_set('display_errors', 1); 

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "wisetech";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
