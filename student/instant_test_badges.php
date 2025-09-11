<?php
// Instant working version - no session checks, just returns test data
header('Content-Type: application/json');

$response = [
    'assignments' => 5,
    'news'        => 3,
    'results'     => 2,
    'events'      => 1,
    'notices'     => 4,
    'status'      => 'test_data_working'
];

echo json_encode($response);
?>