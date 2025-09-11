<?php
// get_upcoming_events.php - API endpoint for upcoming events
session_start();
include '../config.php';

// Set JSON header
header('Content-Type: application/json');

// Check if user is logged in as a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

try {
    // This query fetches real events from your database.
    // It uses 'title' as the event name, matching your table structure.
    $query = "
        SELECT 
            title as name,
            event_date,
            description,
            DATE_FORMAT(event_date, '%M %d, %Y') as formatted_date
        FROM events 
        WHERE event_date >= CURDATE() 
        ORDER BY event_date ASC
        LIMIT 5";
    
    $result = $conn->query($query);
    $events = [];
    
    // The script will only add events to the list if they exist in your database
    // and have an event_date that is today or in the future.
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $events[] = [
                'name' => htmlspecialchars($row['name']),
                'formatted_date' => $row['formatted_date'],
                'description' => htmlspecialchars($row['description'] ?? '')
            ];
        }
    } 
    // NOTE: The sample data block has been removed. 
    // If no events are found, an empty array will be returned,
    // and the dashboard will correctly show "No upcoming events."
    
    echo json_encode($events);
    
} catch (Exception $e) {
    // Log the error for debugging purposes
    error_log("Error in get_upcoming_events.php: " . $e->getMessage());

    // Send a structured error response to the client
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'A server error occurred while fetching events.']);
}

$conn->close();
?>

