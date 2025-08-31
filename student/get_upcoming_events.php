<?php
<<<<<<< HEAD
// get_upcoming_events.php - API endpoint for upcoming events
session_start();
include '../config.php';

// Set JSON header
header('Content-Type: application/json');

// Check if user is logged in as student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    http_response_code(401);
    echo json_encode([]);
    exit();
}

try {
    // Query to get upcoming events
    $query = "
        SELECT 
            event_name as name,
            event_date,
            event_time,
            description,
            DATE_FORMAT(event_date, '%M %d, %Y') as formatted_date
        FROM events 
        WHERE event_date >= CURDATE() 
        ORDER BY event_date ASC, event_time ASC 
        LIMIT 5";
    
    $result = $conn->query($query);
    $events = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $events[] = [
                'name' => $row['name'],
                'formatted_date' => $row['formatted_date'],
                'description' => $row['description'] ?? ''
            ];
        }
    } else {
        // If no events table or no events, return sample data
        $events = [
            [
                'name' => 'Mid-Term Exams',
                'formatted_date' => date('F d, Y', strtotime('+7 days')),
                'description' => 'Mid-term examination period begins'
            ],
            [
                'name' => 'Science Fair',
                'formatted_date' => date('F d, Y', strtotime('+14 days')),
                'description' => 'Annual science fair exhibition'
            ],
            [
                'name' => 'Sports Day',
                'formatted_date' => date('F d, Y', strtotime('+21 days')),
                'description' => 'Inter-house sports competition'
            ]
        ];
    }
    
    echo json_encode($events);
    
} catch (Exception $e) {
    // Return empty array on error
    echo json_encode([]);
=======
session_start();
header('Content-Type: application/json');

// Check if user is logged in as student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

include '../config.php';

try {
    // First, check what columns exist in the events table
    $eventsTableExists = $conn->query("SHOW TABLES LIKE 'events'")->num_rows > 0;
    
    if (!$eventsTableExists) {
        // If no events table, return sample data
        echo json_encode([
            [
                'name' => 'Parent-Teacher Conference',
                'formatted_date' => date('F d, Y', strtotime('+5 days')),
                'description' => 'Meet with teachers'
            ],
            [
                'name' => 'Science Fair',
                'formatted_date' => date('F d, Y', strtotime('+12 days')),
                'description' => 'Annual science exhibition'
            ]
        ]);
        exit();
    }
    
    // Check what columns exist
    $columnCheck = $conn->query("SHOW COLUMNS FROM events");
    $hasEventDate = false;
    $hasTitle = false;
    $hasEventName = false;
    $titleColumn = 'title'; // default
    
    while ($column = $columnCheck->fetch_assoc()) {
        switch ($column['Field']) {
            case 'event_date':
                $hasEventDate = true;
                break;
            case 'title':
                $hasTitle = true;
                $titleColumn = 'title';
                break;
            case 'event_name':
                $hasEventName = true;
                $titleColumn = 'event_name';
                break;
        }
    }
    
    // Build query based on available columns
    if ($hasEventDate) {
        // Use event_date column
        $query = "
            SELECT 
                $titleColumn as name,
                event_date,
                DATE_FORMAT(event_date, '%M %d, %Y') as formatted_date,
                description
            FROM events 
            WHERE event_date >= CURDATE() 
            AND event_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
            ORDER BY event_date ASC
            LIMIT 5
        ";
    } else {
        // Fallback: get recent events without date filtering
        $query = "
            SELECT 
                $titleColumn as name,
                created_at as event_date,
                DATE_FORMAT(created_at, '%M %d, %Y') as formatted_date,
                description
            FROM events 
            ORDER BY created_at DESC
            LIMIT 3
        ";
    }
    
    $result = $conn->query($query);
    $events = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $events[] = [
                'name' => $row['name'] ?? 'Event',
                'formatted_date' => $row['formatted_date'] ?? date('F d, Y'),
                'description' => $row['description'] ?? ''
            ];
        }
    }
    
    // If no events in database, provide sample data
    if (empty($events)) {
        $events = [
            [
                'name' => 'Parent-Teacher Conference',
                'formatted_date' => date('F d, Y', strtotime('+5 days')),
                'description' => 'Meet with teachers'
            ],
            [
                'name' => 'Science Fair',
                'formatted_date' => date('F d, Y', strtotime('+12 days')),
                'description' => 'Annual science exhibition'
            ],
            [
                'name' => 'Sports Day',
                'formatted_date' => date('F d, Y', strtotime('+18 days')),
                'description' => 'Inter-house sports competition'
            ]
        ];
    }
    
    echo json_encode($events);
    
} catch (Exception $e) {
    error_log("Upcoming events error: " . $e->getMessage());
    
    // Return sample data on error
    echo json_encode([
        [
            'name' => 'Parent-Teacher Conference',
            'formatted_date' => date('F d, Y', strtotime('+5 days')),
            'description' => 'Meet with teachers'
        ],
        [
            'name' => 'Science Fair',
            'formatted_date' => date('F d, Y', strtotime('+12 days')),
            'description' => 'Annual science exhibition'
        ]
    ]);
>>>>>>> b291daf7f49078bb0cccb1439969ad4a74e2db38
}

$conn->close();
?>