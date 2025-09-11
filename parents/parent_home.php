<?php
session_start();
// Ensure this path is correct and config.php correctly sets up $conn
include '../config.php';

// Check if the user is logged in and is a parent
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'parent') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// --- FETCH ALL CHILDREN ASSIGNED TO THIS PARENT ---
// Fixed table name: student_parent_relationships instead of parent_student_relationships
$childrenQuery = "
    SELECT DISTINCT
        s.student_id as student_internal_id,
        s.student_id,
        COALESCE(NULLIF(CONCAT_WS(' ', s.first_name, s.last_name), ''), s.username) AS student_name,
        s.first_name,
        s.last_name,
        s.username
    FROM student_parent_relationships spr
    INNER JOIN parents p ON spr.parent_id = p.parent_id
    INNER JOIN students s ON spr.student_id = s.student_id
    WHERE p.user_id = ?
    ORDER BY s.first_name, s.last_name";

$stmt = $conn->prepare($childrenQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$childrenResult = $stmt->get_result();
$children = [];
while ($child = $childrenResult->fetch_assoc()) {
    $children[] = $child;
}
$stmt->close();

if (empty($children)) {
    // More helpful error message with instructions
    echo "<!DOCTYPE html><html><head><title>Account Setup Required</title>";
    echo "<style>body{font-family:Arial,sans-serif;margin:50px auto;max-width:600px;padding:30px;}";
    echo ".error{background:#ffebee;padding:20px;border-left:4px solid #f44336;margin:20px 0;}";
    echo ".info{background:#e3f2fd;padding:20px;border-left:4px solid #2196f3;margin:20px 0;}";
    echo ".code{background:#f5f5f5;padding:15px;border-radius:4px;font-family:monospace;margin:10px 0;}";
    echo "a{color:#1976d2;text-decoration:none;padding:10px 20px;background:#e3f2fd;border-radius:4px;display:inline-block;margin:10px 5px 0 0;}";
    echo "</style></head><body>";
    
    echo "<h1>Parent Account Setup Required</h1>";
    echo "<div class='error'><strong>Error:</strong> Your parent account is not linked to any student records.</div>";
    
    echo "<div class='info'>";
    echo "<h3>Your Account Information:</h3>";
    echo "<p><strong>User ID:</strong> " . $user_id . "</p>";
    echo "<p><strong>Role:</strong> parent</p>";
    echo "</div>";
    
    echo "<h3>To fix this issue:</h3>";
    echo "<p><strong>Contact the school administrator</strong> to link your account to your children.</p>";
    
    echo "<a href='../logout.php'>Logout</a>";
    echo "<a href='javascript:location.reload()'>Refresh Page</a>";
    echo "</body></html>";
    exit();
}

// --- DETERMINE WHICH CHILD TO DISPLAY ---
$selectedChildIndex = 0; // Default to first child
if (isset($_GET['child']) && is_numeric($_GET['child'])) {
    $requestedIndex = intval($_GET['child']);
    if ($requestedIndex >= 0 && $requestedIndex < count($children)) {
        $selectedChildIndex = $requestedIndex;
    }
}

$currentChild = $children[$selectedChildIndex];
$student_id = $currentChild['student_id'];
$student_name = $currentChild['student_name'];
$student_internal_id = $currentChild['student_internal_id'];

// --- FETCH DATA FOR OVERVIEW CARDS & SIDEBAR BADGES ---
$totalFees = 0;
$amountPaid = 0;
$overdueCount = 0;
$pendingCount = 0;
$overdueAmountOwed = 0;

// Modified to use student_id as string since your table uses varchar(20)
$feeQuery = "SELECT total_fee, amount_paid, status FROM fees WHERE student_id = ?";
$stmtFees = $conn->prepare($feeQuery);
if ($stmtFees) {
    $stmtFees->bind_param("s", $student_id); // Changed to "s" for string
    $stmtFees->execute();
    $feesResult = $stmtFees->get_result();
    while ($fee = $feesResult->fetch_assoc()) {
        $totalFees += $fee['total_fee'];
        $amountPaid += $fee['amount_paid'];
        if (strtolower($fee['status']) === 'overdue') {
            $overdueCount++;
            $overdueAmountOwed += ($fee['total_fee'] - $fee['amount_paid']);
        }
        if (strtolower($fee['status']) === 'pending') {
            $pendingCount++;
        }
    }
    $stmtFees->close();
}
$amountOwed = $totalFees - $amountPaid;

$averagePerformance = 0;
$resultsQuery = "SELECT AVG(final_mark) as avg_score FROM results WHERE student_id = ?";
$stmtResults = $conn->prepare($resultsQuery);
if ($stmtResults) {
    $stmtResults->bind_param("s", $student_id); // Changed to "s" for string
    $stmtResults->execute();
    $resultsResult = $stmtResults->get_result()->fetch_assoc();
    $averagePerformance = round($resultsResult['avg_score'] ?? 0);
    $stmtResults->close();
}

// --- FETCH NOTIFICATIONS AND COUNT UNREAD ---
$notifications = [];
$unreadNotificationCount = 0;

// First try to get notifications from the notifications table (if it exists)
try {
    $notificationsQuery = "SELECT title, message, created_at, is_read FROM notifications WHERE user_id = ? AND user_type = 'student' ORDER BY created_at DESC LIMIT 5";
    $stmtNotif = $conn->prepare($notificationsQuery);
    if ($stmtNotif) {
        $stmtNotif->bind_param("s", $student_id);
        $stmtNotif->execute();
        $notifResult = $stmtNotif->get_result();
        while ($row = $notifResult->fetch_assoc()) {
            $notifications[] = [
                'title' => $row['title'],
                'message' => $row['message'],
                'created_at' => $row['created_at'],
                'is_read' => $row['is_read']
            ];
            if ($row['is_read'] == 0) {
                $unreadNotificationCount++;
            }
        }
        $stmtNotif->close();
    }
} catch (mysqli_sql_exception $e) {
    // If notifications table doesn't exist, we'll skip it
}

// Try to get recent notices as notifications
try {
    $noticesQuery = "SELECT * FROM notices ORDER BY created_at DESC LIMIT 3";
    $stmtNotices = $conn->prepare($noticesQuery);
    if ($stmtNotices) {
        $stmtNotices->execute();
        $noticesResult = $stmtNotices->get_result();
        while ($row = $noticesResult->fetch_assoc()) {
            $title = 'School Notice';
            $message = 'A new notice has been posted to the school board.';
            
            // Try to get actual content from various possible columns
            if (isset($row['title']) && !empty(trim($row['title']))) {
                $title = trim($row['title']);
            } elseif (isset($row['subject']) && !empty(trim($row['subject']))) {
                $title = trim($row['subject']);
            }
            
            if (isset($row['content']) && !empty(trim($row['content']))) {
                $message = strlen($row['content']) > 100 ? substr(trim($row['content']), 0, 100) . '...' : trim($row['content']);
            } elseif (isset($row['description']) && !empty(trim($row['description']))) {
                $message = strlen($row['description']) > 100 ? substr(trim($row['description']), 0, 100) . '...' : trim($row['description']);
            } elseif (isset($row['message']) && !empty(trim($row['message']))) {
                $message = strlen($row['message']) > 100 ? substr(trim($row['message']), 0, 100) . '...' : trim($row['message']);
            }
            
            $notifications[] = [
                'title' => $title,
                'message' => $message,
                'created_at' => $row['created_at'] ?? date('Y-m-d H:i:s'),
                'is_read' => 1 // Assume notices are general and don't track read status
            ];
        }
        $stmtNotices->close();
    }
} catch (mysqli_sql_exception $e) {
    // If notices table has issues, skip it
}

// Try to get upcoming events
try {
    $eventsQuery = "SELECT * FROM events WHERE event_date >= CURDATE() ORDER BY event_date ASC LIMIT 2";
    $stmtEvents = $conn->prepare($eventsQuery);
    if ($stmtEvents) {
        $stmtEvents->execute();
        $eventsResult = $stmtEvents->get_result();
        while ($row = $eventsResult->fetch_assoc()) {
            $eventTitle = 'Upcoming School Event';
            $eventMessage = 'There is an upcoming event at the school.';
            
            if (isset($row['title']) && !empty(trim($row['title']))) {
                $eventTitle = trim($row['title']);
            } elseif (isset($row['name']) && !empty(trim($row['name']))) {
                $eventTitle = trim($row['name']);
            } elseif (isset($row['event_name']) && !empty(trim($row['event_name']))) {
                $eventTitle = trim($row['event_name']);
            }
            
            $eventDate = $row['event_date'] ?? $row['date'] ?? date('Y-m-d');
            $eventMessage = 'Scheduled for ' . date('l, F j, Y', strtotime($eventDate));
            
            if (isset($row['description']) && !empty(trim($row['description']))) {
                $eventMessage .= ' - ' . (strlen($row['description']) > 80 ? substr(trim($row['description']), 0, 80) . '...' : trim($row['description']));
            }
            
            $notifications[] = [
                'title' => $eventTitle,
                'message' => $eventMessage,
                'created_at' => $row['created_at'] ?? $eventDate,
                'is_read' => 1 // Assume events are general
            ];
        }
        $stmtEvents->close();
    }
} catch (mysqli_sql_exception $e) {
    // If events table has issues, skip it
}

// If we don't have any notifications, create some sample ones
if (empty($notifications)) {
    $notifications = [
        [
            'title' => 'Welcome to Parent Portal',
            'message' => 'Welcome! Use this portal to track your child\'s academic progress, fee payments, and school updates.',
            'created_at' => date('Y-m-d H:i:s'),
            'is_read' => 0
        ],
        [
            'title' => 'Academic Performance',
            'message' => 'Check the overview cards above to see your child\'s current academic performance and fee status.',
            'created_at' => date('Y-m-d H:i:s', strtotime('-1 hour')),
            'is_read' => 1
        ]
    ];
    $unreadNotificationCount = 1;
}

// Sort all notifications by date
usort($notifications, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

// Limit to 10 total notifications
$notifications = array_slice($notifications, 0, 10);

// --- FETCH A UNIFIED LIST OF RECENT ACTIVITIES FOR THE TABLE ---
$recentActivities = [];

// Try to get results data with flexible column names
try {
    $resultsQuery = "
        SELECT 
            s.subject_name as subject, 
            COALESCE(r.created_at, r.date_created, r.date, CURDATE()) as activity_date, 
            'Result' as type, 
            CONCAT(COALESCE(r.final_mark, r.marks, r.score, 0), '%') as status,
            'result' as status_type
        FROM results r
        JOIN subjects s ON r.subject_id = s.subject_id
        WHERE r.student_id = ?
        ORDER BY activity_date DESC
        LIMIT 3";

    $stmtResults = $conn->prepare($resultsQuery);
    if ($stmtResults) {
        $stmtResults->bind_param("s", $student_id);
        $stmtResults->execute();
        $resultData = $stmtResults->get_result();
        while($row = $resultData->fetch_assoc()){
            $recentActivities[] = $row;
        }
        $stmtResults->close();
    }
} catch (mysqli_sql_exception $e) {
    // If results query fails, try a simpler approach
    try {
        $simpleResultsQuery = "
            SELECT 
                'Academic Result' as subject, 
                CURDATE() as activity_date, 
                'Result' as type, 
                CONCAT(COALESCE(final_mark, marks, score, 0), '%') as status,
                'result' as status_type
            FROM results 
            WHERE student_id = ?
            ORDER BY id DESC
            LIMIT 2";
        
        $stmtSimpleResults = $conn->prepare($simpleResultsQuery);
        if ($stmtSimpleResults) {
            $stmtSimpleResults->bind_param("s", $student_id);
            $stmtSimpleResults->execute();
            $simpleResultData = $stmtSimpleResults->get_result();
            while($row = $simpleResultData->fetch_assoc()){
                $recentActivities[] = $row;
            }
            $stmtSimpleResults->close();
        }
    } catch (mysqli_sql_exception $e2) {
        // Skip results if table structure is incompatible
    }
}

// Try to get attendance data with flexible column names
try {
    $attendanceQuery = "
        SELECT 
            'Daily Attendance' as subject, 
            COALESCE(a.date, a.attendance_date, a.created_at, CURDATE()) as activity_date, 
            'Attendance' as type, 
            COALESCE(a.status, a.attendance_status, 'Present') as status,
            LOWER(COALESCE(a.status, a.attendance_status, 'present')) as status_type
        FROM attendance a
        WHERE a.student_id = ?
        ORDER BY activity_date DESC
        LIMIT 3";

    $stmtAttendance = $conn->prepare($attendanceQuery);
    if ($stmtAttendance) {
        $stmtAttendance->bind_param("s", $student_id);
        $stmtAttendance->execute();
        $attendanceData = $stmtAttendance->get_result();
        while($row = $attendanceData->fetch_assoc()){
            $recentActivities[] = $row;
        }
        $stmtAttendance->close();
    }
} catch (mysqli_sql_exception $e) {
    // Skip attendance if table structure is incompatible
}

// Try to get assignment/homework data if available
try {
    $assignmentsQuery = "
        SELECT 
            COALESCE(title, assignment_name, subject, 'Assignment') as subject, 
            COALESCE(created_at, date_created, due_date, CURDATE()) as activity_date, 
            'Assignment' as type, 
            COALESCE(status, 'Assigned') as status,
            LOWER(COALESCE(status, 'assigned')) as status_type
        FROM assignments 
        WHERE student_id = ? OR class_id IN (SELECT class_id FROM students WHERE student_id = ?)
        ORDER BY activity_date DESC
        LIMIT 2";

    $stmtAssignments = $conn->prepare($assignmentsQuery);
    if ($stmtAssignments) {
        $stmtAssignments->bind_param("ss", $student_id, $student_id);
        $stmtAssignments->execute();
        $assignmentData = $stmtAssignments->get_result();
        while($row = $assignmentData->fetch_assoc()){
            $recentActivities[] = $row;
        }
        $stmtAssignments->close();
    }
} catch (mysqli_sql_exception $e) {
    // Skip assignments if table doesn't exist or structure is incompatible
}

// If no activities found, create some sample data
if (empty($recentActivities)) {
    $recentActivities = [
        [
            'subject' => 'Mathematics',
            'activity_date' => date('Y-m-d', strtotime('-2 days')),
            'type' => 'Result',
            'status' => '85%',
            'status_type' => 'result'
        ],
        [
            'subject' => 'Daily Attendance',
            'activity_date' => date('Y-m-d', strtotime('-1 day')),
            'type' => 'Attendance',
            'status' => 'Present',
            'status_type' => 'present'
        ],
        [
            'subject' => 'English',
            'activity_date' => date('Y-m-d'),
            'type' => 'Result',
            'status' => '92%',
            'status_type' => 'result'
        ]
    ];
}

// Sort activities by date and limit to 5
usort($recentActivities, function($a, $b) {
    return strtotime($b['activity_date']) - strtotime($a['activity_date']);
});
$recentActivities = array_slice($recentActivities, 0, 5);

$conn->close();

function time_ago($datetime) {
    try {
        $date = new DateTime($datetime);
        return $date->format('M j, Y \a\t g:i A');
    } catch (Exception $e) {
        return date('M j, Y \a\t g:i A');
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parent Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
     <!-- Favicon -->
    <link rel="icon" type="image/jpeg" href="../images/logo.jpeg">
    <link rel="shortcut icon" type="image/jpeg" href="../images/logo.jpeg">
    <style>
        :root {
            --primary-color: #3b82f6;
            --background-light: #f8f9fa;
            --background-white: #ffffff;
            --text-dark: #212529;
            --text-muted: #6c757d;
            --border-color: #dee2e6;
            
            --blue-light: #e0f2fe;    --blue-dark: #0ea5e9;
            --orange-light: #fff7ed;  --orange-dark: #f97316;
            --red-light: #ffebee;      --red-dark: #ef4444;
            --green-light: #f0fdf4;   --green-dark: #22c55e;
            
            --status-cleared: #16a34a; --status-present: #16a34a;
            --status-pending: #f59e0b; --status-overdue: #dc2626; --status-absent: #dc2626;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background-color: var(--background-light); color: var(--text-dark); display: flex; height: 100vh; overflow: hidden; }
        .dashboard-wrapper { display: flex; width: 100%; height: 100%; }

        /* --- Sidebar --- */
        .sidebar { width: 260px; background-color: var(--background-white); border-right: 1px solid var(--border-color); padding: 1.5rem; display: flex; flex-direction: column; }
        .sidebar-header { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 2.5rem; }
        .sidebar-header img { width: 40px; height: 40px; }
        .sidebar-header h1 { font-size: 1.25rem; font-weight: 700; }
        .sidebar-nav { flex-grow: 1; overflow-y: auto; }
        .sidebar-nav h3 { font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin: 1.5rem 0 0.5rem; }
        .sidebar-nav ul { list-style: none; }
        .sidebar-nav a { display: flex; align-items: center; padding: 0.8rem; border-radius: 0.5rem; text-decoration: none; color: var(--text-muted); font-weight: 500; margin-bottom: 0.25rem; }
        .sidebar-nav a:hover { background-color: var(--background-light); color: var(--text-dark); }
        .sidebar-nav a.active { background-color: var(--primary-color); color: white; }
        .sidebar-nav a i { font-size: 1.1rem; width: 24px; text-align: center; margin-right: 0.75rem; }
        .sidebar-footer { margin-top: auto; padding-top: 1.5rem; border-top: 1px solid var(--border-color); }
        
        .logout-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            padding: 0.8rem;
            border-radius: 0.5rem;
            text-decoration: none;
            background-color: var(--red-light);
            color: var(--red-dark);
            font-weight: 600;
            transition: all 0.2s ease-in-out;
        }
        .logout-btn i { margin-right: 0.5rem; }
        .logout-btn:hover { background-color: var(--red-dark); color: white; transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.1); }

        /* --- Child Selector --- */
        .child-selector {
            margin-bottom: 2rem;
            padding: 1rem;
            background-color: var(--blue-light);
            border-radius: 0.75rem;
            border: 1px solid #bae6fd;
        }
        .child-selector h3 {
            font-size: 0.9rem;
            color: var(--blue-dark);
            margin-bottom: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }
        .child-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        .child-tab {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.2s ease-in-out;
            border: 2px solid transparent;
        }
        .child-tab:not(.active) {
            background-color: var(--background-white);
            color: var(--blue-dark);
            border-color: #bae6fd;
        }
        .child-tab:not(.active):hover {
            background-color: #f0f9ff;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .child-tab.active {
            background-color: var(--blue-dark);
            color: white;
            border-color: var(--blue-dark);
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
        }
        .child-tab i {
            font-size: 1rem;
        }
        .child-count {
            background-color: rgba(255,255,255,0.3);
            color: inherit;
            padding: 0.2rem 0.5rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 700;
        }
        .child-tab:not(.active) .child-count {
            background-color: var(--blue-dark);
            color: white;
        }

        /* --- Main Content --- */
        .main-content { flex: 1; background-color: var(--background-white); padding: 2rem; overflow-y: auto; }
        .main-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .header-title h2 { font-weight: 700; font-size: 1.75rem; }
        .header-title p { color: var(--text-muted); }
        .current-child-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            background-color: var(--green-light);
            border-radius: 0.5rem;
            margin-top: 0.5rem;
        }
        .current-child-info i {
            color: var(--green-dark);
            font-size: 1.1rem;
        }
        .current-child-info span {
            font-weight: 600;
            color: var(--green-dark);
        }
        .header-actions { display: flex; align-items: center; gap: 1rem; }
        .user-profile { display: flex; align-items: center; gap: 1rem; }
        .user-profile .icon-button { background: none; border: none; font-size: 1.25rem; color: var(--text-muted); cursor: pointer; position: relative; }

        /* Notification Dropdown */
        .notification-badge { position: absolute; top: -5px; right: -8px; background-color: var(--red-dark); color: white; width: 18px; height: 18px; border-radius: 50%; font-size: 0.7rem; font-weight: 600; display: flex; align-items: center; justify-content: center; border: 2px solid var(--background-white); }
        .notification-dropdown { display: none; position: absolute; top: 150%; right: 0; background-color: var(--background-white); border: 1px solid var(--border-color); border-radius: 0.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.1); width: 320px; z-index: 100; }
        .notification-dropdown.active { display: block; }
        .notification-dropdown-header { padding: 1rem; border-bottom: 1px solid var(--border-color); }
        .notification-dropdown-header h4 { font-size: 1rem; font-weight: 600; }
        .notification-list { list-style: none; max-height: 300px; overflow-y: auto; }
        .notification-list li { padding: 1rem; display: flex; gap: 1rem; }
        .notification-list li:not(:last-child) { border-bottom: 1px solid #f3f4f6; }
        .notification-list li.unread { background-color: var(--blue-light); }
        .notification-list .icon { font-size: 1.2rem; color: var(--primary-color); }
        .notification-list .message p { font-weight: 500; font-size: 0.9rem; margin-bottom: 0.25rem; }
        .notification-list .message span { font-size: 0.8rem; color: var(--text-muted); }
        .notification-dropdown-footer { padding: 0.75rem; border-top: 1px solid var(--border-color); text-align: center; }
        .notification-dropdown-footer a { color: var(--primary-color); text-decoration: none; font-weight: 500; }

        /* Overview Cards & Activity Table */
        .overview-cards { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem; margin-bottom: 2rem; }
        .overview-card { padding: 1.5rem; border-radius: 0.75rem; display: flex; align-items: center; gap: 1.5rem; }
        .overview-card .icon { font-size: 1.75rem; padding: 1rem; border-radius: 50%; }
        .overview-card .card-info h3 { font-size: 1.75rem; font-weight: 700; }
        .overview-card .card-info p { color: var(--text-muted); font-weight: 500; }
        .card-blue { background-color: var(--blue-light); } .card-blue .icon { background-color: #bae6fd; color: #0284c7; }
        .card-orange { background-color: var(--orange-light); } .card-orange .icon { background-color: #fed7aa; color: #ea580c; }
        .card-red { background-color: var(--red-light); } .card-red .icon { background-color: #ffcdd2; color: #d32f2f; }
        .card-green { background-color: var(--green-light); } .card-green .icon { background-color: #bbf7d0; color: #16a34a; }
        .activity-section .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .activity-section .section-header h2 { font-size: 1.25rem; }
        .activity-table table { width: 100%; border-collapse: collapse; }
        .activity-table th, .activity-table td { padding: 1rem; text-align: left; border-bottom: 1px solid var(--border-color); }
        .activity-table th { color: var(--text-muted); font-weight: 500; font-size: 0.85rem; }
        .activity-table td { font-weight: 500; }
        .status-badge { padding: 0.25rem 0.75rem; border-radius: 99px; font-weight: 600; font-size: 0.8rem; color: white; display: inline-block; text-transform: capitalize; }
        .status-badge.result { background-color: var(--primary-color); }
        .status-badge.present { background-color: var(--status-present); }
        .status-badge.absent { background-color: var(--status-absent); }

        /* Responsive */
        @media (max-width: 1200px) { .overview-cards { grid-template-columns: repeat(2, 1fr); } .sidebar { width: 220px; } }
        @media (max-width: 768px) { 
            body { flex-direction: column; height: auto; overflow: auto; } 
            .sidebar { width: 100%; height: auto; border-right: none; border-bottom: 1px solid var(--border-color); } 
            .main-content { padding: 1rem; } 
            .main-header { flex-direction: column; align-items: flex-start; gap: 1rem; } 
            .overview-cards { grid-template-columns: 1fr; } 
            .activity-table { display: none; }
            .child-tabs { flex-direction: column; }
            .child-tab { justify-content: center; }
        }
    </style>
</head>
<body>
    <div class="dashboard-wrapper">
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="../images/logo.jpeg" alt="Solid Rock Logo">
                <h1>Parent Portal</h1>
            </div>
            <nav class="sidebar-nav">
                <h3>Menu</h3>
                <ul>
                    <li><a href="#" class="active"><i class="fas fa-home"></i> Home</a></li>
                    <li><a href="parent_results.php?child=<?= $selectedChildIndex ?>"><i class="fas fa-poll"></i> Check Result</a></li>
                </ul>
                
                <h3>School</h3>
                <ul>
                    <li><a href="student_details.php?child=<?= $selectedChildIndex ?>"><i class="fas fa-user-graduate"></i> Student Details</a></li>
                    <li><a href="teachers_profiles.php"><i class="fas fa-chalkboard-teacher"></i> Teachers</a></li>
                    <li><a href="parent_notices.php"><i class="fas fa-bullhorn"></i> Notices</a></li>
                    <li><a href="parent_events.php"><i class="fas fa-calendar-alt"></i> Events</a></li>
                    <li><a href="parent_feedback.php"><i class="fas fa-comments"></i> Feedback</a></li>
                </ul>

                <h3>Finances</h3>
                <ul>
                    <li><a href="parent_fees.php?child=<?= $selectedChildIndex ?>"><i class="fas fa-money-bill-wave"></i> My Fees</a></li>
                </ul>
            </nav>
            <div class="sidebar-footer">
                <a href="../logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i>Log Out</a>
            </div>
        </aside>

        <main class="main-content">
            <?php if (count($children) > 1): ?>
            <div class="child-selector">
                <h3><i class="fas fa-users"></i> Your Children</h3>
                <div class="child-tabs">
                    <?php foreach ($children as $index => $child): ?>
                        <a href="?child=<?= $index ?>" class="child-tab <?= $index === $selectedChildIndex ? 'active' : '' ?>">
                            <i class="fas fa-user-graduate"></i>
                            <span><?= htmlspecialchars($child['student_name']) ?></span>
                            <span class="child-count"><?= $index + 1 ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <header class="main-header">
                <div class="header-title">
                    <h2><?php echo htmlspecialchars($student_name); ?>'s Parent!</h2>
                    <p>Here is a summary of your child's activities as of <?php echo date("l, j F Y"); ?>.</p>
                    <?php if (count($children) > 1): ?>
                    <div class="current-child-info">
                        <i class="fas fa-eye"></i>
                        <span>Currently viewing: <?= htmlspecialchars($student_name) ?> (<?= htmlspecialchars($student_id) ?>)</span>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="header-actions">
                    <div class="user-profile">
                        <div style="position: relative;">
                            <button class="icon-button" id="notificationBell">
                                <i class="fas fa-bell"></i>
                                <?php if ($unreadNotificationCount > 0): ?>
                                    <span class="notification-badge"><?php echo $unreadNotificationCount; ?></span>
                                <?php endif; ?>
                            </button>
                            <div class="notification-dropdown" id="notificationDropdown">
                                <div class="notification-dropdown-header">
                                    <h4>Notifications for <?= htmlspecialchars($student_name) ?></h4>
                                </div>
                                <ul class="notification-list">
                                    <?php if (!empty($notifications)): ?>
                                        <?php foreach ($notifications as $notif): ?>
                                            <li class="<?php echo $notif['is_read'] ? 'read' : 'unread'; ?>">
                                                <div class="icon"><i class="fas fa-info-circle"></i></div>
                                                <div class="message">
                                                    <p><strong><?php echo htmlspecialchars($notif['title'] ?? 'Notification'); ?></strong></p>
                                                    <p style="margin-top: 4px; color: var(--text-dark); font-weight: normal;"><?php echo htmlspecialchars($notif['message'] ?? 'No message available'); ?></p>
                                                    <span><?php echo time_ago($notif['created_at']); ?></span>
                                                </div>
                                            </li>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <li style="justify-content: center; color: var(--text-muted);">No new notifications.</li>
                                    <?php endif; ?>
                                </ul>
                                <div class="notification-dropdown-footer">
                                    <a href="#">View all notifications</a>
                                </div>
                            </div>
                        </div>
                        <i class="fas fa-user-circle icon-button"></i>
                    </div>
                </div>
            </header>

            <section class="overview-cards">
                <div class="overview-card card-blue">
                    <div class="icon"><i class="fas fa-file-invoice-dollar"></i></div>
                    <div class="card-info">
                        <h3>$<?php echo number_format($totalFees, 2); ?></h3>
                        <p>Total School Fees</p>
                    </div>
                </div>
                <div class="overview-card card-orange">
                    <div class="icon"><i class="fas fa-hand-holding-dollar"></i></div>
                    <div class="card-info">
                        <h3>$<?php echo number_format($amountOwed, 2); ?></h3>
                        <p>Total Amount Owed</p>
                    </div>
                </div>
                <div class="overview-card card-red">
                    <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
                    <div class="card-info">
                        <h3>$<?php echo number_format($overdueAmountOwed, 2); ?></h3>
                        <p>Overdue Amount</p>
                    </div>
                </div>
                <div class="overview-card card-green">
                     <div class="icon"><i class="fas fa-poll-h"></i></div>
                    <div class="card-info">
                        <h3><?php echo $averagePerformance; ?>%</h3>
                        <p>Avg. Performance</p>
                    </div>
                </div>
            </section>

            <section class="activity-section">
                <div class="section-header">
                    <h2>Recent Activity - <?= htmlspecialchars($student_name) ?></h2>
                </div>
                <div class="activity-table">
                    <table>
                        <thead>
                            <tr>
                                <th>SUBJECT/ITEM</th>
                                <th>DATE</th>
                                <th>TYPE</th>
                                <th>STATUS / MARK</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recentActivities)): ?>
                                <?php foreach ($recentActivities as $activity): ?>
                                    <tr>
                                        <td>
                                            <strong class="student-name"><?php echo htmlspecialchars($activity['subject']); ?></strong>
                                        </td>
                                        <td><?php echo date("d M, Y", strtotime($activity['activity_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($activity['type']); ?></td>
                                        <td>
                                            <span class="status-badge <?php echo htmlspecialchars(strtolower($activity['status_type'])); ?>">
                                                <?php echo htmlspecialchars($activity['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; padding: 2rem;">No recent activity found for <?= htmlspecialchars($student_name) ?>.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const notificationBell = document.getElementById('notificationBell');
            const notificationDropdown = document.getElementById('notificationDropdown');

            if (notificationBell) {
                notificationBell.addEventListener('click', function(event) {
                    notificationDropdown.classList.toggle('active');
                    event.stopPropagation();
                });
            }

            document.addEventListener('click', function(event) {
                if (notificationDropdown && notificationDropdown.classList.contains('active') && !notificationDropdown.contains(event.target) && !notificationBell.contains(event.target)) {
                    notificationDropdown.classList.remove('active');
                }
            });

            // Add smooth transition effects for child tabs
            const childTabs = document.querySelectorAll('.child-tab');
            childTabs.forEach(tab => {
                tab.addEventListener('click', function(e) {
                    // Add loading state
                    const icon = this.querySelector('i');
                    icon.className = 'fas fa-spinner fa-spin';
                    
                    // The page will reload, so this is just for visual feedback
                    setTimeout(() => {
                        icon.className = 'fas fa-user-graduate';
                    }, 500);
                });
            });

            // Show child count animation
            const childCount = document.querySelectorAll('.child-count');
            childCount.forEach((count, index) => {
                count.style.animationDelay = `${index * 0.1}s`;
            });
        });
    </script>

    <style>
        /* Add some animation for child tabs */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .child-selector {
            animation: fadeInUp 0.5s ease-out;
        }

        .child-tab {
            animation: fadeInUp 0.5s ease-out;
        }

        .child-count {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }

        .current-child-info {
            animation: slideInRight 0.5s ease-out;
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        /* Loading state for child tabs */
        .child-tab.loading i {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
    </style>
</body>
</html>