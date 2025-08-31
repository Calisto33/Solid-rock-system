<?php
session_start();
// Ensure the path to config.php is correct from the location of THIS file.
// If get_menu_badges.php is in 'staff/', and config.php is in the root 'wisetech/', then '../config.php' is correct.
include '../config.php';

header('Content-Type: application/json');

// Default response with all potential badges set to 0
$response = [
    'attendance'  => 0, // Placeholder, add logic if needed
    'results'     => 0, // Placeholder, add logic if needed
    'notices'     => 0, // Placeholder, add logic if needed
    'assignments' => 0, // Placeholder, add logic if needed
    'news'        => 0,
    'events'      => 0,
];

// Check if staff is logged in before proceeding
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff' || !isset($_SESSION['user_id'])) {
    // Log the attempt for security/debugging if desired
    error_log("get_menu_badges.php: Unauthorized access attempt or session expired.");
    echo json_encode($response); // Return default zeros
    exit();
}

$staff_id = $_SESSION['user_id'];

// --- Fetch Unread News Count (Using correct news_id) ---
// Counts news items that do NOT have a corresponding entry in user_read_news for this staff_id.
$sql_news = "
    SELECT COUNT(n.news_id) as unread_count
    FROM news n
    LEFT JOIN user_read_news urn ON n.news_id = urn.news_id AND urn.user_id = ?
    WHERE urn.user_id IS NULL
";
// Optional: If you want to filter news by audience 'staff' directly in this query:
// $sql_news = "
//     SELECT COUNT(n.news_id) as unread_count
//     FROM news n
//     LEFT JOIN user_read_news urn ON n.news_id = urn.news_id AND urn.user_id = ?
//     WHERE n.audience = 'staff' AND urn.user_id IS NULL  -- Assuming 'audience' column exists in 'news' table
// ";


$stmt_news = $conn->prepare($sql_news);

if ($stmt_news) {
    $stmt_news->bind_param("i", $staff_id);
    $stmt_news->execute();
    $result_news = $stmt_news->get_result();
    if ($row_news = $result_news->fetch_assoc()) {
        $response['news'] = (int)$row_news['unread_count'];
    }
    $stmt_news->close();
} else {
    error_log("Failed to prepare statement for news count in get_menu_badges.php: " . $conn->error);
}


// --- Fetch Unread Events Count (Using correct event_id) ---
// Counts events items that do NOT have a corresponding entry in user_read_events for this staff_id.
$sql_events = "
    SELECT COUNT(e.event_id) as unread_count
    FROM events e
    LEFT JOIN user_read_events ure ON e.event_id = ure.event_id AND ure.user_id = ?
    WHERE ure.user_id IS NULL
";

$stmt_events = $conn->prepare($sql_events);

if ($stmt_events) {
    $stmt_events->bind_param("i", $staff_id);
    $stmt_events->execute();
    $result_events = $stmt_events->get_result();
    if ($row_events = $result_events->fetch_assoc()) {
        $response['events'] = (int)$row_events['unread_count'];
    }
    $stmt_events->close();
} else {
    error_log("Failed to prepare statement for events count in get_menu_badges.php: " . $conn->error);
}

// --- Output the final response as JSON ---
echo json_encode($response);

// Close the database connection if it's not managed elsewhere (e.g., in config.php footer or by script termination)
if ($conn) {
    $conn->close();
}
?>
