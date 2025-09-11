<?php
// Example functions to mark items as read
// Include these functions in your individual pages (view_assignment.php, view_news.php, etc.)

include 'config.php';

/**
 * Mark an assignment as read for a specific student
 */
function markAssignmentAsRead($student_id, $assignment_id, $conn) {
    $sql = "INSERT IGNORE INTO student_read_assignments (user_id, assignment_id) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ii", $student_id, $assignment_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    return false;
}

/**
 * Mark a news item as read for a specific user
 */
function markNewsAsRead($user_id, $news_id, $conn) {
    $sql = "INSERT IGNORE INTO user_read_news (user_id, news_id) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ii", $user_id, $news_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    return false;
}

/**
 * Mark a result as read for a specific student
 */
function markResultAsRead($student_id, $result_id, $conn) {
    $sql = "INSERT IGNORE INTO student_read_results (user_id, result_id) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ii", $student_id, $result_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    return false;
}

/**
 * Mark an event as read for a specific user
 */
function markEventAsRead($user_id, $event_id, $conn) {
    $sql = "INSERT IGNORE INTO user_read_events (user_id, event_id) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ii", $user_id, $event_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    return false;
}

/**
 * Mark a notice as read for a specific user
 */
function markNoticeAsRead($user_id, $notice_id, $conn) {
    $sql = "INSERT IGNORE INTO user_read_notices (user_id, notice_id) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ii", $user_id, $notice_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    return false;
}
function markAllAssignmentsAsRead($student_id, $conn) {
    $sql = "INSERT IGNORE INTO student_read_assignments (user_id, assignment_id) 
            SELECT ?, assignment_id FROM assignments 
            WHERE assignment_id NOT IN (
                SELECT assignment_id FROM student_read_assignments WHERE user_id = ?
            )";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ii", $student_id, $student_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    return false;
}

function markAllNewsAsRead($user_id, $conn) {
    $sql = "INSERT IGNORE INTO user_read_news (user_id, news_id) 
            SELECT ?, news_id FROM news 
            WHERE (audience = 'students' OR audience = 'all')
            AND news_id NOT IN (
                SELECT news_id FROM user_read_news WHERE user_id = ?
            )";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ii", $user_id, $user_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    return false;
}

?>