<?php
// get_latest_news.php - API endpoint for latest news
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
    // This query fetches the 5 most recent news articles for students.
    // It uses the correct column 'title' from your database table.
    $query = "
        SELECT 
            title as news_title,
            news_content,
            created_at,
            CONCAT('Posted ', DATE_FORMAT(created_at, '%M %d, %Y')) as posted_info
        FROM news 
        WHERE audience = 'students' OR audience = 'all'
        ORDER BY created_at DESC 
        LIMIT 5";
    
    $result = $conn->query($query);
    $news = [];
    
    // The script will only add news items if they exist in your database
    // and are targeted to the correct audience.
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $news[] = [
                'news_title' => htmlspecialchars($row['news_title']),
                'posted_info' => $row['posted_info'],
                // Truncate content for the summary view on the dashboard
                'content' => htmlspecialchars(substr($row['news_content'], 0, 100)) . '...'
            ];
        }
    }
    
    echo json_encode($news);
    
} catch (Exception $e) {
    // Log the error for debugging
    error_log("Error in get_latest_news.php: " . $e->getMessage());

    // Send a structured error response to the client
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'A server error occurred while fetching news.']);
}

$conn->close();
?>
