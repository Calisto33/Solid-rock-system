<?php
// Simple test file to check basic functionality
header('Content-Type: application/json');

$response = [
    'status' => 'working',
    'timestamp' => date('Y-m-d H:i:s'),
    'assignments' => 2,
    'news' => 1,
    'results' => 0,
    'events' => 1,
    'notices' => 3
];

echo json_encode($response);
?>