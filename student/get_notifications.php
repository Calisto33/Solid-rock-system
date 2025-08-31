<?php
session_start();
// *** ENSURE THIS PATH IS CORRECT ***
// If get_notifications.php is in your root wisetech folder, use: include 'config.php';
// If get_notifications.php is in your /student/ folder, use: include '../config.php';
include '../config.php';

// Set header to JSON
header('Content-Type: application/json');

// --- Default response ---
$default_response = [
    'assignments' => 0,
    'news'        => 0,
    'events'      => 0,
    'notices'     => 0,
    'results'     => 0, // Included results
    'error'       => null
];

// --- 1. Check Session ---
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student' || !isset($_SESSION['user_id'])) {
    $default_response['error'] = 'Unauthorized';
    echo json_encode($default_response);
    exit();
}
$student_id = $_SESSION['user_id']; // This is the ID from the 'users' table

if (!isset($_SESSION['class_id'])) {
    // For notices and assignments that use class_id. Results, News, Events might not.
    // If those queries don't need class_id, this check might be too strict for them.
    // However, since notices and assignments likely need it, we keep it.
    $default_response['error'] = 'Session incomplete (missing class_id)';
    echo json_encode($default_response);
    exit();
}
$class_id = $_SESSION['class_id']; // This is the class identifier, e.g., 'f_1'

// --- 2. Function to execute a count query ---
function get_count($conn, $sql, $types = "", $params = []) {
    $count = 0;
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        if (!empty($params) && !empty($types)) {
            $stmt->bind_param($types, ...$params);
        } elseif (!empty($params) && empty($types)) {
            error_log("Params provided but no types for SQL: " . $sql . " | Params: " . implode(",", $params));
            return 0; // Error if params exist but no types defined
        }

        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result) {
                $row = $result->fetch_assoc();
                $count = isset($row['unread_count']) ? (int)$row['unread_count'] : 0;
            } else {
                error_log("MySQLi get_result error: " . $stmt->error . " | SQL: " . $sql);
            }
        } else {
             error_log("MySQLi execute error: " . $stmt->error . " | SQL: " . $sql);
        }
        $stmt->close();
    } else {
        error_log("MySQLi prepare error: " . $conn->error . " | SQL: " . $sql);
    }
    return $count;
}

// --- 3. Define SQL Queries ---
$sql = [];

// News (Assumed correct based on your structures)
$sql['news'] = "SELECT COUNT(n.news_id) as unread_count
                 FROM news n
                 LEFT JOIN user_read_news urn ON n.news_id = urn.news_id AND urn.user_id = ?
                 WHERE (n.audience = 'students' OR n.audience = 'student' OR n.audience = 'all' OR n.audience IS NULL)
                 AND urn.user_id IS NULL";

// Events (Assumed correct based on your structures)
$sql['events'] = "SELECT COUNT(e.event_id) as unread_count
                  FROM events e
                  LEFT JOIN user_read_events ure ON e.event_id = ure.event_id AND ure.user_id = ?
                  WHERE (e.target_audience = 'students' OR e.target_audience = 'student' OR e.target_audience = 'all' OR e.target_audience IS NULL)
                  AND ure.user_id IS NULL";

// Assignments (Assumed correct based on your structures)
$sql['assignments'] = "SELECT COUNT(a.assignment_id) as unread_count
                       FROM assignments a
                       LEFT JOIN student_read_assignments sra ON a.assignment_id = sra.assignment_id AND sra.user_id = ?
                       WHERE a.class = ? AND sra.user_id IS NULL";

// Notices (Known to work from previous steps)
$sql['notices'] = "SELECT COUNT(n.notice_id) as unread_count
                   FROM notices n
                   LEFT JOIN student_notices sn ON n.notice_id = sn.notice_id AND sn.student_id = ?
                   WHERE (n.class = ? OR n.class IS NULL) AND sn.student_id IS NULL";

// Results - *** CORRECTED BASED ON TEST SCRIPT AND YOUR STRUCTURES ***
$sql['results'] = "SELECT COUNT(r.result_id) as unread_count
                   FROM results r
                   JOIN students s ON r.student_id = s.student_id
                   WHERE s.id = ?  -- s.id is the FK in students table that links to users.id
                   AND NOT EXISTS (
                       SELECT 1
                       FROM student_read_results srr
                       WHERE srr.result_id = r.result_id
                       AND srr.user_id = ? -- srr.user_id is the FK that links to users.id
                   )";

// --- 4. Fetch Counts ---
$notifications = $default_response; // Initialize with defaults

$notifications['news']        = get_count($conn, $sql['news'], "i", [$student_id]);
$notifications['events']      = get_count($conn, $sql['events'], "i", [$student_id]);
$notifications['assignments'] = get_count($conn, $sql['assignments'], "is", [$student_id, $class_id]);
$notifications['notices']     = get_count($conn, $sql['notices'], "is", [$student_id, $class_id]);
$notifications['results']     = get_count($conn, $sql['results'], "ii", [$student_id, $student_id]); // Two integers for user_id

unset($notifications['error']); // Remove error key if all went well

// --- 5. Output JSON ---
echo json_encode($notifications);

// --- 6. Close Connection (optional) ---
// if ($conn) { $conn->close(); }
?>