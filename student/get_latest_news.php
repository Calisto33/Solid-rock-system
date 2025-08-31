<?php
<<<<<<< HEAD
// get_latest_news.php - API endpoint for latest news
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
    // Query to get latest news
    $query = "
        SELECT 
            news_title,
            news_content,
            created_at,
            CONCAT('Posted ', DATE_FORMAT(created_at, '%M %d, %Y')) as posted_info
        FROM news 
        ORDER BY created_at DESC 
        LIMIT 5";
    
    $result = $conn->query($query);
    $news = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $news[] = [
                'news_title' => $row['news_title'],
                'posted_info' => $row['posted_info'],
                'content' => substr($row['news_content'], 0, 100) . '...'
            ];
        }
    } else {
        // If no news table or no news, return sample data
        $news = [
            [
                'news_title' => 'New Library Hours',
                'posted_info' => 'Posted ' . date('F d, Y', strtotime('-2 days')),
                'content' => 'The library will be open extended hours during exam period...'
            ],
            [
                'news_title' => 'Student Achievement Awards',
                'posted_info' => 'Posted ' . date('F d, Y', strtotime('-5 days')),
                'content' => 'Congratulations to our students who excelled in the recent competitions...'
            ],
            [
                'news_title' => 'Campus Maintenance Notice',
                'posted_info' => 'Posted ' . date('F d, Y', strtotime('-7 days')),
                'content' => 'Scheduled maintenance will be performed on the campus network...'
            ]
        ];
    }
    
    echo json_encode($news);
    
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
    // Check if news table exists and what columns it has
    $newsTableExists = $conn->query("SHOW TABLES LIKE 'news'")->num_rows > 0;
    
    if (!$newsTableExists) {
        // Return sample data if no news table
        echo json_encode([
            [
                'news_title' => 'Welcome to Wisetech College',
                'posted_info' => 'Posted on ' . date('F d, Y', strtotime('-2 days')),
                'posted_by' => 'Admin'
            ]
        ]);
        exit();
    }
    
    // Check what columns exist
    $columnCheck = $conn->query("SHOW COLUMNS FROM news");
    $hasNewsColumn = false;
    $hasNewsTitleColumn = false;
    $hasAudienceColumn = false;
    $titleColumn = 'news'; // default
    
    while ($column = $columnCheck->fetch_assoc()) {
        switch ($column['Field']) {
            case 'news':
                $hasNewsColumn = true;
                $titleColumn = 'news';
                break;
            case 'news_title':
                $hasNewsTitleColumn = true;
                $titleColumn = 'news_title';
                break;
            case 'audience':
                $hasAudienceColumn = true;
                break;
        }
    }
    
    // Build query based on available columns
    $whereClause = '';
    if ($hasAudienceColumn) {
        $whereClause = "WHERE audience = 'students' OR audience = 'all'";
    }
    
    $query = "
        SELECT 
            $titleColumn AS news_title,
            created_at 
        FROM news 
        $whereClause
        ORDER BY created_at DESC 
        LIMIT 5
    ";
    
    $result = $conn->query($query);
    $news = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $news[] = [
                'news_title' => $row['news_title'],
                'posted_info' => 'Posted on ' . date('F d, Y', strtotime($row['created_at'])),
                'posted_by' => 'Admin'
            ];
        }
    }
    
    // If no news in database, provide sample data
    if (empty($news)) {
        $news = [
            [
                'news_title' => 'New Library Hours',
                'posted_info' => 'Posted on ' . date('F d, Y', strtotime('-2 days')),
                'posted_by' => 'Admin'
            ],
            [
                'news_title' => 'Upcoming Holiday Schedule',
                'posted_info' => 'Posted on ' . date('F d, Y', strtotime('-5 days')),
                'posted_by' => 'Admin'
            ],
            [
                'news_title' => 'Student Achievement Awards',
                'posted_info' => 'Posted on ' . date('F d, Y', strtotime('-7 days')),
                'posted_by' => 'Principal'
            ]
        ];
    }
    
    echo json_encode($news);
    
} catch (Exception $e) {
    error_log("Latest news error: " . $e->getMessage());
    
    // Return sample data on error
    echo json_encode([
        [
            'news_title' => 'New Library Hours',
            'posted_info' => 'Posted on ' . date('F d, Y', strtotime('-2 days')),
            'posted_by' => 'Admin'
        ],
        [
            'news_title' => 'Student Achievement Awards',
            'posted_info' => 'Posted on ' . date('F d, Y', strtotime('-7 days')),
            'posted_by' => 'Principal'
        ]
    ]);
>>>>>>> b291daf7f49078bb0cccb1439969ad4a74e2db38
}

$conn->close();
?>