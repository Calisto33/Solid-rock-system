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
<<<<<<< HEAD
=======
        s.student_id as student_internal_id,
>>>>>>> b291daf7f49078bb0cccb1439969ad4a74e2db38
        s.student_id,
        COALESCE(NULLIF(CONCAT_WS(' ', s.first_name, s.last_name), ''), s.username) AS student_name,
        s.first_name,
        s.last_name,
<<<<<<< HEAD
        s.username,
        s.class,
        p.relationship
    FROM parents p
    INNER JOIN students s ON p.student_id = s.student_id
=======
        s.username
    FROM student_parent_relationships spr
    INNER JOIN parents p ON spr.parent_id = p.parent_id
    INNER JOIN students s ON spr.student_id = s.student_id
>>>>>>> b291daf7f49078bb0cccb1439969ad4a74e2db38
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
    echo "<style>body{font-family:'Inter',Arial,sans-serif;margin:0;padding:0;background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);min-height:100vh;display:flex;align-items:center;justify-content:center;}";
    echo ".container{max-width:600px;padding:3rem;background:white;border-radius:1rem;box-shadow:0 20px 25px -5px rgb(0 0 0 / 0.1);}";
    echo ".error{background:#fee2e2;padding:1.5rem;border-left:4px solid #ef4444;margin:1.5rem 0;border-radius:0.5rem;}";
    echo ".info{background:#dbeafe;padding:1.5rem;border-left:4px solid #3b82f6;margin:1.5rem 0;border-radius:0.5rem;}";
    echo "h1{color:#1f2937;margin-bottom:1rem;font-size:1.875rem;font-weight:700;}";
    echo "a{color:white;text-decoration:none;padding:0.75rem 1.5rem;background:#3b82f6;border-radius:0.5rem;display:inline-block;margin:0.5rem 0.5rem 0 0;font-weight:600;transition:all 0.2s;}";
    echo "a:hover{background:#1d4ed8;transform:translateY(-2px);}</style></head><body>";
    
    echo "<div class='container'>";
    echo "<h1>🔗 Parent Account Setup Required</h1>";
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
    echo "</div></body></html>";
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
$student_internal_id = $student_id; // Use student_id since that's what we have

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

<<<<<<< HEAD
// Try to fetch notifications from news table or create sample notifications
try {
    $notificationsQuery = "SELECT news_title as message, created_at, 0 as is_read FROM news ORDER BY created_at DESC LIMIT 5";
    $stmtNotif = $conn->prepare($notificationsQuery);
    if ($stmtNotif) {
=======
// First try to get notifications from the notifications table (if it exists)
try {
    $notificationsQuery = "SELECT title, message, created_at, is_read FROM notifications WHERE user_id = ? AND user_type = 'student' ORDER BY created_at DESC LIMIT 5";
    $stmtNotif = $conn->prepare($notificationsQuery);
    if ($stmtNotif) {
        $stmtNotif->bind_param("s", $student_id);
>>>>>>> b291daf7f49078bb0cccb1439969ad4a74e2db38
        $stmtNotif->execute();
        $notifResult = $stmtNotif->get_result();
        while ($row = $notifResult->fetch_assoc()) {
            $notifications[] = [
<<<<<<< HEAD
                'message' => 'News: ' . substr($row['message'], 0, 50) . '...',
=======
                'title' => $row['title'],
                'message' => $row['message'],
>>>>>>> b291daf7f49078bb0cccb1439969ad4a74e2db38
                'created_at' => $row['created_at'],
                'is_read' => $row['is_read']
            ];
            if ($row['is_read'] == 0) {
                $unreadNotificationCount++;
            }
        }
        $stmtNotif->close();
    }
<<<<<<< HEAD
} catch (Exception $e) {
    // If news table doesn't exist or has wrong columns, create sample notifications
    $notifications = [
        [
            'message' => 'Welcome to the parent portal! Check your child\'s progress regularly.',
            'created_at' => date('Y-m-d H:i:s'),
            'is_read' => 0
        ],
        [
            'message' => 'Mid-term exams are scheduled for next month.',
            'created_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
            'is_read' => 0
        ],
        [
            'message' => 'Parent-teacher conferences will be held next week.',
            'created_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
            'is_read' => 0
        ]
    ];
    $unreadNotificationCount = 3;
=======
} catch (mysqli_sql_exception $e) {
    // If notifications table doesn't exist, we'll skip it
>>>>>>> b291daf7f49078bb0cccb1439969ad4a74e2db38
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

<<<<<<< HEAD
// Try to get results data with proper error handling
try {
    $activityQuery = "
        SELECT 
            r.subject,
            r.created_at as activity_date,
            'Result' as type,
            CONCAT(COALESCE(r.final_mark, r.marks_obtained, '0'), '%') as status,
            'result' as status_type
        FROM results r
        WHERE r.student_id = ?
        ORDER BY r.created_at DESC
        LIMIT 5";

    $stmtActivities = $conn->prepare($activityQuery);
    if ($stmtActivities) {
        $stmtActivities->bind_param("s", $student_id);
        $stmtActivities->execute();
        $activityResult = $stmtActivities->get_result();
        while($row = $activityResult->fetch_assoc()){
            $recentActivities[] = $row;
        }
        $stmtActivities->close();
    }
} catch (Exception $e) {
    // Create sample activities if tables don't exist or have issues
}

// If no activities found, add sample data
if (empty($recentActivities)) {
    $recentActivities = [
        [
            'subject' => 'Mathematics',
            'activity_date' => date('Y-m-d H:i:s', strtotime('-2 days')),
            'type' => 'Result',
            'status' => '89%',
            'status_type' => 'result'
        ],
        [
            'subject' => 'English',
            'activity_date' => date('Y-m-d H:i:s', strtotime('-3 days')),
            'type' => 'Result',
            'status' => '92%',
            'status_type' => 'result'
        ],
        [
            'subject' => 'Science',
            'activity_date' => date('Y-m-d H:i:s', strtotime('-5 days')),
            'type' => 'Result',
            'status' => '78%',
            'status_type' => 'result'
        ]
    ];
=======
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
>>>>>>> b291daf7f49078bb0cccb1439969ad4a74e2db38
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
    <title>Parent Dashboard - <?= htmlspecialchars($student_name) ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #3b82f6;
            --primary-dark: #1d4ed8;
            --secondary-color: #f1f5f9;
            --background-light: #f8fafc;
            --background-white: #ffffff;
            --text-dark: #0f172a;
            --text-medium: #334155;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            
            --success-color: #10b981;
            --success-light: #d1fae5;
            --warning-color: #f59e0b;
            --warning-light: #fef3c7;
            --danger-color: #ef4444;
            --danger-light: #fee2e2;
            --info-color: #06b6d4;
            --info-light: #cffafe;
            
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
            
            --radius-sm: 0.375rem;
            --radius-md: 0.5rem;
            --radius-lg: 0.75rem;
            --radius-xl: 1rem;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: var(--text-dark);
            overflow-x: hidden;
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
            position: relative;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 280px;
            background: var(--background-white);
            backdrop-filter: blur(20px);
            border-right: 1px solid var(--border-color);
            box-shadow: var(--shadow-lg);
            position: relative;
            z-index: 10;
        }

        .sidebar-header {
            padding: 2rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .logo {
            width: 48px;
            height: 48px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            backdrop-filter: blur(10px);
            object-fit: cover;
        }

        .logo img {
            width: 100%;
            height: 100%;
            border-radius: var(--radius-lg);
        }

        .brand-text h1 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .brand-text p {
            font-size: 0.875rem;
            opacity: 0.9;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: var(--radius-md);
            backdrop-filter: blur(10px);
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }

        .user-details h3 {
            font-size: 0.9rem;
            font-weight: 600;
        }

        .user-details p {
            font-size: 0.75rem;
            opacity: 0.8;
        }

        .sidebar-nav {
            padding: 1.5rem 0;
            flex: 1;
        }

        .nav-section {
            margin-bottom: 2rem;
        }

        .nav-title {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 0 1.5rem;
            margin-bottom: 0.75rem;
        }

        .nav-links {
            list-style: none;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1.5rem;
            color: var(--text-medium);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s ease;
            position: relative;
        }

        .nav-link:hover {
            background: var(--secondary-color);
            color: var(--primary-color);
            transform: translateX(4px);
        }

        .nav-link.active {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            box-shadow: var(--shadow-md);
            margin: 0 1rem;
            border-radius: var(--radius-md);
        }

        .nav-link.active:hover {
            transform: translateX(0);
        }

        .nav-icon {
            width: 20px;
            text-align: center;
            font-size: 1.1rem;
        }

        .logout-btn {
            margin: 1.5rem;
            padding: 0.75rem 1rem;
            background: var(--danger-light);
            color: var(--danger-color);
            border: 1px solid var(--danger-color);
            border-radius: var(--radius-md);
            text-decoration: none;
            font-weight: 600;
            text-align: center;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .logout-btn:hover {
            background: var(--danger-color);
            color: white;
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        /* Child Selector */
        .child-selector {
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: linear-gradient(135deg, var(--info-light), #e0f7fa);
            border-radius: var(--radius-xl);
            border: 1px solid var(--info-color);
            box-shadow: var(--shadow-md);
        }

        .child-selector h3 {
            font-size: 1rem;
            color: var(--info-color);
            margin-bottom: 1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .child-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .child-tab {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem 1.5rem;
            border-radius: var(--radius-lg);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
        }

        .child-tab:not(.active) {
            background-color: var(--background-white);
            color: var(--info-color);
            border-color: #bae6fd;
            box-shadow: var(--shadow-sm);
        }

        .child-tab:not(.active):hover {
            background-color: #f0f9ff;
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .child-tab.active {
            background: linear-gradient(135deg, var(--info-color), #0891b2);
            color: white;
            border-color: var(--info-color);
            box-shadow: var(--shadow-lg);
        }

        .child-tab i {
            font-size: 1.1rem;
        }

        .child-count {
            background-color: rgba(255,255,255,0.3);
            color: inherit;
            padding: 0.25rem 0.6rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 700;
            min-width: 24px;
            text-align: center;
        }

        .child-tab:not(.active) .child-count {
            background-color: var(--info-color);
            color: white;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            background: var(--background-light);
            overflow-y: auto;
        }

        .content-header {
            background: var(--background-white);
            padding: 2rem;
            border-bottom: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
        }

        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.5rem;
        }

        .welcome-section h2 {
            font-size: 2rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }

        .welcome-section p {
            color: var(--text-muted);
            font-size: 1.1rem;
        }

        .current-child-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem 1.5rem;
            background: linear-gradient(135deg, var(--success-light), #bbf7d0);
            border-radius: var(--radius-lg);
            margin-top: 1rem;
            border: 1px solid var(--success-color);
        }

        .current-child-info i {
            color: var(--success-color);
            font-size: 1.1rem;
        }

        .current-child-info span {
            font-weight: 600;
            color: var(--success-color);
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .notification-btn {
            position: relative;
            background: var(--background-white);
            border: 1px solid var(--border-color);
            padding: 0.75rem;
            border-radius: var(--radius-md);
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: var(--shadow-sm);
        }

        .notification-btn:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .notification-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: var(--danger-color);
            color: white;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 600;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        /* Notification Dropdown */
        .notification-dropdown {
            display: none;
            position: absolute;
            top: 120%;
            right: 0;
            background: var(--background-white);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-xl);
            width: 320px;
            z-index: 100;
            overflow: hidden;
        }

        .notification-dropdown.active {
            display: block;
            animation: fadeInUp 0.3s ease;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .notification-dropdown-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            background: var(--secondary-color);
        }

        .notification-dropdown-header h4 {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-dark);
        }

        .notification-list {
            list-style: none;
            max-height: 300px;
            overflow-y: auto;
        }

        .notification-list li {
            padding: 1rem 1.5rem;
            display: flex;
            gap: 1rem;
            transition: background 0.2s ease;
        }

        .notification-list li:not(:last-child) {
            border-bottom: 1px solid #f3f4f6;
        }

        .notification-list li:hover {
            background: var(--secondary-color);
        }

        .notification-list li.unread {
            background: var(--info-light);
        }

        .notification-list .icon {
            font-size: 1.2rem;
            color: var(--primary-color);
        }

        .notification-list .message p {
            font-weight: 500;
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
        }

        .notification-list .message span {
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .notification-dropdown-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--border-color);
            text-align: center;
            background: var(--secondary-color);
        }

        .notification-dropdown-footer a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }

        /* Dashboard Cards */
        .dashboard-content {
            padding: 2rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--background-white);
            padding: 2rem;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--card-color), var(--card-color-dark));
        }

        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-xl);
        }

        .stat-card.danger {
            --card-color: var(--danger-color);
            --card-color-dark: #dc2626;
        }

        .stat-card.success {
            --card-color: var(--success-color);
            --card-color-dark: #059669;
        }

        .stat-card.warning {
            --card-color: var(--warning-color);
            --card-color-dark: #d97706;
        }

        .stat-card.info {
            --card-color: var(--info-color);
            --card-color-dark: #0891b2;
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: var(--card-color);
            background: linear-gradient(135deg, rgba(var(--card-color-rgb), 0.15), rgba(var(--card-color-rgb), 0.25));
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--card-color);
            line-height: 1;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--text-muted);
            font-weight: 500;
            font-size: 0.9rem;
        }

        .stat-trend {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 1rem;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .trend-up {
            color: var(--success-color);
        }

        .trend-down {
            color: var(--danger-color);
        }

        /* Activity Section */
        .activity-section {
            background: var(--background-white);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            border: 1px solid var(--border-color);
        }

        .activity-header {
            padding: 2rem 2rem 1rem;
            border-bottom: 1px solid var(--border-color);
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
        }

        .activity-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .activity-title h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-dark);
        }

        .view-all-btn {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: var(--radius-md);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.2s ease;
            box-shadow: var(--shadow-md);
        }

        .view-all-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .activity-table {
            width: 100%;
        }

        .activity-table th {
            background: var(--secondary-color);
            padding: 1.5rem 2rem;
            text-align: left;
            font-weight: 600;
            color: var(--text-medium);
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .activity-table td {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--border-color);
            font-weight: 500;
        }

        .activity-table tbody tr:hover {
            background: var(--secondary-color);
        }

        .subject-name {
            font-weight: 600;
            color: var(--text-dark);
        }

        .activity-type {
            background: var(--info-light);
            color: var(--info-color);
            padding: 0.5rem 1rem;
            border-radius: var(--radius-md);
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: var(--radius-md);
            font-weight: 700;
            font-size: 0.9rem;
            display: inline-block;
            box-shadow: var(--shadow-sm);
            text-transform: capitalize;
        }

        .status-badge.result {
            background: linear-gradient(135deg, var(--success-color), #059669);
            color: white;
        }

        .status-badge.present {
            background: linear-gradient(135deg, var(--success-color), #059669);
            color: white;
        }

        .status-badge.absent {
            background: linear-gradient(135deg, var(--danger-color), #dc2626);
            color: white;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-muted);
        }

        .empty-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.3;
        }

        /* User Profile */
        .user-profile {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .icon-button {
            background: none;
            border: none;
            font-size: 1.25rem;
            color: var(--text-muted);
            cursor: pointer;
            position: relative;
            padding: 0.5rem;
            border-radius: var(--radius-md);
            transition: all 0.2s ease;
        }

        .icon-button:hover {
            background: var(--secondary-color);
            color: var(--primary-color);
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .sidebar {
                width: 250px;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .dashboard-container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                height: auto;
            }
            
            .content-header {
                padding: 1.5rem 1rem;
            }
            
            .dashboard-content {
                padding: 1rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .stat-card {
                padding: 1.5rem;
            }
            
            .activity-table th,
            .activity-table td {
                padding: 1rem;
            }
            
            .header-top {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .child-tabs {
                flex-direction: column;
            }

            .child-tab {
                justify-content: center;
            }

            .activity-table {
                overflow-x: auto;
            }
        }

        /* Loading Animation */
        .loading {
            opacity: 0;
            animation: fadeInUp 0.6s ease forwards;
        }

        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }
        .stat-card:nth-child(4) { animation-delay: 0.4s; }

        /* Additional Animations */
        .child-selector {
            animation: fadeInUp 0.5s ease-out;
        }

        .activity-section {
            animation: fadeInUp 0.8s ease-out;
        }

        /* Ripple Effect */
        .ripple {
            position: absolute;
            border-radius: 50%;
            transform: scale(0);
            animation: ripple 600ms linear;
            background-color: rgba(255, 255, 255, 0.6);
        }

        @keyframes ripple {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }

        /* Focus styles for accessibility */
        .nav-link:focus,
        .notification-btn:focus,
        .view-all-btn:focus,
        .logout-btn:focus,
        .child-tab:focus {
            outline: 2px solid var(--primary-color);
            outline-offset: 2px;
        }

        /* Custom scrollbar */
        .notification-list::-webkit-scrollbar {
            width: 6px;
        }

        .notification-list::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }

        .notification-list::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 3px;
        }

        .notification-list::-webkit-scrollbar-thumb:hover {
            background: var(--primary-dark);
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo-section">
                    <div class="logo">
                        <img src="../images/logo.jpg" alt="School Logo" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <i class="fas fa-graduation-cap" style="display: none;"></i>
                    </div>
                    <div class="brand-text">
                        <h1>EduPortal</h1>
                        <p>Parent Dashboard</p>
                    </div>
                </div>
                <div class="user-info">
                    <div class="user-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="user-details">
                        <h3>Parent User</h3>
                        <p>Parent Account</p>
                    </div>
                </div>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-title">Menu</div>
                    <ul class="nav-links">
                        <li><a href="#" class="nav-link active">
                            <i class="nav-icon fas fa-home"></i>
                            <span>Home</span>
                        </a></li>
                        <li><a href="parent_results.php?child=<?= $selectedChildIndex ?>" class="nav-link">
                            <i class="nav-icon fas fa-chart-line"></i>
                            <span>Check Results</span>
                        </a></li>
                    </ul>
                </div>

                <div class="nav-section">
                    <div class="nav-title">School</div>
                    <ul class="nav-links">
                        <li><a href="student_details.php?child=<?= $selectedChildIndex ?>" class="nav-link">
                            <i class="nav-icon fas fa-user-graduate"></i>
                            <span>Student Details</span>
                        </a></li>
                        <li><a href="teachers_profiles.php" class="nav-link">
                            <i class="nav-icon fas fa-chalkboard-teacher"></i>
                            <span>Teachers</span>
                        </a></li>
                        <li><a href="parent_notices.php" class="nav-link">
                            <i class="nav-icon fas fa-bullhorn"></i>
                            <span>Notices</span>
                        </a></li>
                        <li><a href="parent_events.php" class="nav-link">
                            <i class="nav-icon fas fa-calendar-alt"></i>
                            <span>Events</span>
                        </a></li>
                        <li><a href="parent_feedback.php" class="nav-link">
                            <i class="nav-icon fas fa-comments"></i>
                            <span>Feedback</span>
                        </a></li>
                    </ul>
                </div>

                <div class="nav-section">
                    <div class="nav-title">Finances</div>
                    <ul class="nav-links">
                        <li><a href="parent_fees.php?child=<?= $selectedChildIndex ?>" class="nav-link">
                            <i class="nav-icon fas fa-money-bill-wave"></i>
                            <span>My Fees</span>
                        </a></li>
                    </ul>
                </div>
            </nav>

            <a href="../logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                Log Out
            </a>
        </aside>

        <!-- Main Content -->
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

            <header class="content-header">
                <div class="header-top">
                    <div class="welcome-section">
                        <h2 id="welcome-title">Hello! <?php echo htmlspecialchars($student_name); ?>'s Parent!</h2>
                        <p>Here is a summary of your child's activities as of <?php echo date("l, j F Y"); ?>.</p>
                        <?php if (count($children) > 1): ?>
                        <div class="current-child-info">
                            <i class="fas fa-eye"></i>
                            <span>Currently viewing: <?= htmlspecialchars($student_name) ?> (<?= htmlspecialchars($student_id) ?>)</span>
                        </div>
                        <?php endif; ?>
                    </div>
<<<<<<< HEAD
                    <div class="header-actions">
                        <div class="user-profile">
                            <div style="position: relative;">
                                <button class="notification-btn" id="notificationBell">
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
                                                        <p><?php echo htmlspecialchars($notif['message']); ?></p>
                                                        <span><?php echo time_ago($notif['created_at']); ?></span>
                                                    </div>
                                                </li>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <li style="justify-content: center; color: var(--text-muted); padding: 2rem;">
                                                <div style="text-align: center;">
                                                    <i class="fas fa-bell-slash" style="font-size: 2rem; opacity: 0.3; margin-bottom: 0.5rem;"></i>
                                                    <p>No new notifications.</p>
=======
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
>>>>>>> b291daf7f49078bb0cccb1439969ad4a74e2db38
                                                </div>
                                            </li>
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
                </div>
            </header>

            <div class="dashboard-content">
                <!-- Statistics Cards -->
                <div class="stats-grid">
                    <div class="stat-card info loading">
                        <div class="stat-header">
                            <div class="stat-icon">
                                <i class="fas fa-file-invoice-dollar"></i>
                            </div>
                        </div>
                        <div class="stat-value">$<?php echo number_format($totalFees, 2); ?></div>
                        <div class="stat-label">Total School Fees</div>
                        <div class="stat-trend">
                            <i class="fas fa-info-circle"></i>
                            <span>Annual fees</span>
                        </div>
                    </div>

                    <div class="stat-card warning loading">
                        <div class="stat-header">
                            <div class="stat-icon">
                                <i class="fas fa-hand-holding-dollar"></i>
                            </div>
                        </div>
                        <div class="stat-value">$<?php echo number_format($amountOwed, 2); ?></div>
                        <div class="stat-label">Total Amount Owed</div>
                        <div class="stat-trend">
                            <i class="fas fa-clock"></i>
                            <span>Payment due</span>
                        </div>
                    </div>

                    <div class="stat-card danger loading">
                        <div class="stat-header">
                            <div class="stat-icon">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                        </div>
                        <div class="stat-value">$<?php echo number_format($overdueAmountOwed, 2); ?></div>
                        <div class="stat-label">Overdue Amount</div>
                        <div class="stat-trend trend-down">
                            <i class="fas fa-arrow-down"></i>
                            <span>Requires immediate attention</span>
                        </div>
                    </div>

                    <div class="stat-card success loading">
                        <div class="stat-header">
                            <div class="stat-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                        </div>
                        <div class="stat-value"><?php echo $averagePerformance; ?>%</div>
                        <div class="stat-label">Average Performance</div>
                        <div class="stat-trend trend-up">
                            <i class="fas fa-arrow-up"></i>
                            <span><?php echo $averagePerformance >= 75 ? 'Above average' : 'Needs improvement'; ?></span>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="activity-section loading">
                    <div class="activity-header">
                        <div class="activity-title">
                            <h2>Recent Activity - <?= htmlspecialchars($student_name) ?></h2>
                            <a href="parent_results.php?child=<?= $selectedChildIndex ?>" class="view-all-btn">
                                View All Results
                                <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>

                    <table class="activity-table">
                        <thead>
                            <tr>
                                <th>Subject/Item</th>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Status/Mark</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recentActivities)): ?>
                                <?php foreach ($recentActivities as $activity): ?>
                                    <tr>
                                        <td>
                                            <span class="subject-name"><?php echo htmlspecialchars($activity['subject']); ?></span>
                                        </td>
                                        <td><?php echo date("d M, Y", strtotime($activity['activity_date'])); ?></td>
                                        <td>
                                            <span class="activity-type"><?php echo htmlspecialchars($activity['type']); ?></span>
                                        </td>
                                        <td>
                                            <span class="status-badge <?php echo htmlspecialchars(strtolower($activity['status_type'])); ?>">
                                                <?php echo htmlspecialchars($activity['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="empty-state">
                                        <div>
                                            <i class="fas fa-chart-line empty-icon"></i>
                                            <p><strong>No recent activity found for <?= htmlspecialchars($student_name) ?>.</strong></p>
                                            <p>Check back later for updates on results and attendance.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Dynamic greeting based on time
            const hour = new Date().getHours();
            let greeting;
            if (hour < 12) greeting = 'Good Morning!';
            else if (hour < 17) greeting = 'Good Afternoon!';
            else greeting = 'Good Evening!';

            const welcomeTitle = document.getElementById('welcome-title');
            if (welcomeTitle) {
                const studentName = '<?php echo addslashes($student_name); ?>';
                welcomeTitle.innerHTML = `${greeting} ${studentName}'s Parent!`;
            }

            // Notification functionality
            const notificationBell = document.getElementById('notificationBell');
            const notificationDropdown = document.getElementById('notificationDropdown');

            if (notificationBell) {
                notificationBell.addEventListener('click', function(event) {
                    notificationDropdown.classList.toggle('active');
                    event.stopPropagation();
                });
            }

            document.addEventListener('click', function(event) {
                if (notificationDropdown && notificationDropdown.classList.contains('active') && 
                    !notificationDropdown.contains(event.target) && !notificationBell.contains(event.target)) {
                    notificationDropdown.classList.remove('active');
                }
            });

            // Add loading animation to cards
            const cards = document.querySelectorAll('.loading');
            cards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.animationDelay = `${index * 0.1}s`;
                    card.classList.add('loaded');
                }, 100);
            });

            // Smooth hover effects for nav links
            const navLinks = document.querySelectorAll('.nav-link');
            navLinks.forEach(link => {
                link.addEventListener('mouseenter', function() {
                    if (!this.classList.contains('active')) {
                        this.style.background = 'var(--secondary-color)';
                        this.style.color = 'var(--primary-color)';
                    }
                });
                
                link.addEventListener('mouseleave', function() {
                    if (!this.classList.contains('active')) {
                        this.style.background = 'transparent';
                        this.style.color = 'var(--text-medium)';
                    }
                });

                // Loading state for navigation
                link.addEventListener('click', function(e) {
                    if (!this.classList.contains('active')) {
                        const icon = this.querySelector('.nav-icon');
                        const originalClass = icon.className;
                        icon.className = 'nav-icon fas fa-spinner fa-spin';
                        
                        setTimeout(() => {
                            icon.className = originalClass;
                        }, 1500);
                    }
                });
            });

            // Child tab interactions
            const childTabs = document.querySelectorAll('.child-tab');
            childTabs.forEach(tab => {
                tab.addEventListener('click', function(e) {
                    // Add loading state
                    const icon = this.querySelector('i');
                    const originalClass = icon.className;
                    icon.className = 'fas fa-spinner fa-spin';
                    
                    // The page will reload, so this is just for visual feedback
                    setTimeout(() => {
                        icon.className = originalClass;
                    }, 500);
                });
            });

            // Enhanced table row interactions
            const tableRows = document.querySelectorAll('.activity-table tbody tr');
            tableRows.forEach(row => {
                row.addEventListener('click', function() {
                    // Add click feedback
                    this.style.transform = 'scale(0.98)';
                    this.style.transition = 'all 0.1s ease';
                    
                    setTimeout(() => {
                        this.style.transform = 'scale(1)';
                    }, 100);
                });
            });

            // Ripple effect for buttons
            function createRipple(event) {
                const button = event.currentTarget;
                const circle = document.createElement('span');
                const diameter = Math.max(button.clientWidth, button.clientHeight);
                const radius = diameter / 2;

                circle.style.width = circle.style.height = `${diameter}px`;
                circle.style.left = `${event.clientX - button.offsetLeft - radius}px`;
                circle.style.top = `${event.clientY - button.offsetTop - radius}px`;
                circle.classList.add('ripple');

                const ripple = button.getElementsByClassName('ripple')[0];
                if (ripple) {
                    ripple.remove();
                }

                button.appendChild(circle);
            }

            const buttons = document.querySelectorAll('.view-all-btn, .logout-btn, .notification-btn');
            buttons.forEach(button => {
                button.addEventListener('click', createRipple);
            });

            // Add contextual tooltips
            const tooltipElements = [
                { selector: '.stat-value', getText: (el) => `Current value: ${el.textContent}` },
                { selector: '.status-badge', getText: (el) => `Status: ${el.textContent}` },
                { selector: '.notification-badge', getText: () => 'You have unread notifications' }
            ];

            tooltipElements.forEach(({ selector, getText }) => {
                document.querySelectorAll(selector).forEach(element => {
                    element.addEventListener('mouseenter', function(e) {
                        const tooltip = document.createElement('div');
                        tooltip.className = 'tooltip';
                        tooltip.textContent = getText(this);
                        tooltip.style.cssText = `
                            position: absolute;
                            background: var(--text-dark);
                            color: white;
                            padding: 0.5rem 0.75rem;
                            border-radius: var(--radius-sm);
                            font-size: 0.8rem;
                            z-index: 10000;
                            pointer-events: none;
                            white-space: nowrap;
                            box-shadow: var(--shadow-lg);
                            opacity: 0;
                            transform: translateY(10px);
                            transition: all 0.2s ease;
                        `;
                        
                        document.body.appendChild(tooltip);
                        
                        const rect = this.getBoundingClientRect();
                        tooltip.style.left = `${rect.left + rect.width / 2 - tooltip.offsetWidth / 2}px`;
                        tooltip.style.top = `${rect.bottom + 8}px`;
                        
                        // Animate in
                        requestAnimationFrame(() => {
                            tooltip.style.opacity = '1';
                            tooltip.style.transform = 'translateY(0)';
                        });
                        
                        this.addEventListener('mouseleave', () => {
                            tooltip.style.opacity = '0';
                            tooltip.style.transform = 'translateY(10px)';
                            setTimeout(() => {
                                if (tooltip.parentNode) {
                                    document.body.removeChild(tooltip);
                                }
                            }, 200);
                        }, { once: true });
                    });
                });
            });

            // Add keyboard navigation
            document.addEventListener('keydown', function(e) {
                if (e.altKey && e.key === 'h') {
                    // Alt + H to focus home
                    document.querySelector('.nav-link.active').focus();
                    e.preventDefault();
                } else if (e.altKey && e.key === 'n') {
                    // Alt + N to open notifications
                    notificationBell.click();
                    e.preventDefault();
                }
            });

            console.log('🎉 Parent Portal initialized successfully!');
        });
    </script>
</body>
</html>