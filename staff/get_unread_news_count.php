<?php
session_start();
include '../config.php'; // Make sure this path to your database connection is correct!

// Check if the user is logged in and is staff
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff' || !isset($_SESSION['user_id'])) {
    // If not, and this script is called via AJAX, return an error
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized', 'count' => 0]);
    exit();
}

$staff_id = $_SESSION['user_id'];
$unread_count = 0;

/*
 * =========================================================================
 * IMPORTANT: YOU MUST ADAPT THIS SQL QUERY!
 *
 * This is a *sample* query. It assumes:
 * 1. You have a table named 'news' where news items are stored.
 * 2. You have a table named 'news_read_status' with at least 'news_id'
 * and 'user_id' columns. When a user reads a news item, a row is
 * added here.
 * 3. Your 'news' table might have a way to target 'staff', or maybe all
 * news is visible to staff. This example assumes all news is potentially
 * visible and relies on the 'news_read_status' to filter.
 * =========================================================================
 */

// --- SAMPLE QUERY (Adapt this!) ---
// Counts news items that do NOT have a 'read' record for this user.
$sql = "SELECT COUNT(n.id) as unread_count
        FROM news n
        LEFT JOIN news_read_status nrs ON n.id = nrs.news_id AND nrs.user_id = ?
        WHERE nrs.user_id IS NULL";
        // Optional: Add more WHERE clauses if needed, e.g.,
        // "AND (n.target_group = 'staff' OR n.target_group = 'all')"

$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("i", $staff_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result) {
        $row = $result->fetch_assoc();
        $unread_count = (int)$row['unread_count']; // Get the count
    } else {
        error_log("Failed to get result for unread news count: " . $conn->error);
    }
    $stmt->close();
} else {
    // Log error but avoid sending PHP errors to the frontend AJAX call
    error_log("Failed to prepare statement for unread news count: " . $conn->error);
    $unread_count = 0; // Default to 0 on error
}

// Close the database connection (if your config.php doesn't do it automatically)
// $conn->close();

// Set the content type to JSON and output the count
header('Content-Type: application/json');
echo json_encode(['count' => $unread_count]);

?>