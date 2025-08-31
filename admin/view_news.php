<?php
// --- Mark all existing news as read for this user (FROM SNIPPET) ---
session_start(); // Snippet's session_start
// Ensure the path to config.php is correct from the location of your news viewing file.
// Example: If view_news.php is in 'staff/', and config.php is in the root 'wisetech/', then '../config.php' is correct.
include '../config.php'; // Snippet's include

// Only run this if a staff member is logged in
if (isset($_SESSION['role']) && $_SESSION['role'] === 'staff' && isset($_SESSION['user_id'])) {

    $staff_id_for_marking = $_SESSION['user_id']; // Use a distinct variable name to avoid conflict

    // Check if user_read_news table exists before trying to insert
    $tableExists = $conn->query("SHOW TABLES LIKE 'user_read_news'")->num_rows > 0;
    
    if ($tableExists) {
        // INSERT IGNORE ensures it doesn't cause an error if a record for a particular news_id/user_id already exists.
        // This query attempts to add an entry for *every* news item for the current user.
        // This approach marks all news as read as soon as the staff member visits the news listing page.
        $sql_mark_read = "INSERT IGNORE INTO user_read_news (user_id, news_id)
                          SELECT ?, news.news_id FROM news"; // Selects all news_ids from the news table

        $stmt_mark = $conn->prepare($sql_mark_read);

        if ($stmt_mark) {
            $stmt_mark->bind_param("i", $staff_id_for_marking);
            $stmt_mark->execute();
            $stmt_mark->close();
        } else {
            // Log an error if the prepare failed, but don't stop the page load
            error_log("Failed to prepare statement for marking news as read in view_news.php: " . $conn->error);
        }
    }
}
// --- End of Mark as Read Snippet ---

// --- USER'S ORIGINAL view_news.php code starts here ---
// session_start(); // This was already in user's code, snippet handles its own session context
// include '../config.php'; // This was already in user's code, snippet handles its own include for marking

// Check if the user is logged in and get their role
if (!isset($_SESSION['role'])) { // This check is still relevant for displaying news
    header("Location: ../login.php");
    exit();
}

$role = $_SESSION['role']; // 'student', 'staff', or 'parent'

// Define the audience based on the role
$audience = "";
$dashboard_link = ''; // Initialize dashboard link

switch ($role) {
    case 'student':
        $audience = 'students';
        $dashboard_link = '../student/student_home.php'; // Link for students - Adjusted path assuming view_news is in a general folder or admin
        break;
    case 'staff':
        $audience = 'staff';
        $dashboard_link = '../staff/staff_home.php'; // Link for staff
        break;
    case 'parent':
        $audience = 'parents';
        $dashboard_link = '../parent/parent_home.php'; // Link for parents - Adjusted path
        break;
    default:
        // Consider a more graceful error or redirect
        error_log("Invalid role encountered in view_news.php: " . $role);
        // For now, redirect to a generic login if role is unexpected after login
        header("Location: ../login.php");
        exit();
}

// Check if news table exists and what columns it has
$newsTableExists = $conn->query("SHOW TABLES LIKE 'news'")->num_rows > 0;
$newsResult = false;

if ($newsTableExists) {
    // Check what columns exist in news table
    $newsColumns = [];
    $columnResult = $conn->query("SHOW COLUMNS FROM news");
    while ($col = $columnResult->fetch_assoc()) {
        $newsColumns[] = $col['Field'];
    }
    
    // Determine which columns to use based on what exists
    $contentColumn = in_array('news_content', $newsColumns) ? 'news_content' : 
                    (in_array('content', $newsColumns) ? 'content' : 'title');
    $dateColumn = in_array('created_at', $newsColumns) ? 'created_at' : 
                 (in_array('publish_date', $newsColumns) ? 'publish_date' : 'news_id');
    $hasAudience = in_array('audience', $newsColumns);
    
    // Build query based on available columns
    if ($hasAudience) {
        // FIXED: Use actual column names from your database
        $newsQuery = "SELECT news_id, {$contentColumn} as news_content, {$dateColumn} as created_at FROM news WHERE audience = ? ORDER BY {$dateColumn} DESC";
        $stmt = $conn->prepare($newsQuery);
        if ($stmt) {
            $stmt->bind_param("s", $audience);
            $stmt->execute();
            $newsResult = $stmt->get_result();
        } else {
            error_log("Failed to prepare statement for fetching news in view_news.php: " . $conn->error);
            $newsResult = false;
        }
    } else {
        // No audience column, show all news
        $newsQuery = "SELECT news_id, {$contentColumn} as news_content, {$dateColumn} as created_at FROM news ORDER BY {$dateColumn} DESC";
        $stmt = $conn->prepare($newsQuery);
        if ($stmt) {
            $stmt->execute();
            $newsResult = $stmt->get_result();
        } else {
            error_log("Failed to prepare statement for fetching news in view_news.php: " . $conn->error);
            $newsResult = false;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(ucfirst($role)) ?> News</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
    body {
        font-family: 'Inter', sans-serif;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
        background-color: #f9fafb; /* bg-gray-50 */
        color: #374151; /* text-gray-800 */
        line-height: 1.5; /* leading-relaxed */
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        margin: 0;
    }

    /* Header Styles */
    .main-header {
        background: linear-gradient(to right, #2563eb, #1e40af); /* bg-gradient-to-r from-blue-600 to-blue-800 */
        color: #fff;
        padding-top: 1.5rem; /* py-6 */
        padding-bottom: 1.5rem; /* py-6 */
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); /* shadow-lg */
        position: sticky;
        top: 0;
        z-index: 50;
    }

    .header-content-wrapper {
        max-width: 80rem; /* max-w-7xl */
        margin-left: auto; /* mx-auto */
        margin-right: auto; /* mx-auto */
        padding-left: 1.5rem; /* px-6 */
        padding-right: 1.5rem; /* px-6 */
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap; /* Allow wrapping on smaller screens */
    }

    .header-title-group {
        display: flex;
        align-items: center;
        column-gap: 0.75rem; /* space-x-3 */
    }

    .header-icon {
        font-size: 1.875rem; /* text-3xl */
    }

    .header-title {
        font-size: 1.5rem; /* text-2xl */
        font-weight: 700; /* font-bold */
        margin: 0;
    }

    .dashboard-link {
        display: inline-flex; /* Changed to inline-flex for better alignment */
        align-items: center;
        column-gap: 0.5rem; /* space-x-2 */
        color: #93c5fd; /* text-blue-200 */
        transition-property: color, border-color; /* transition-all */
        transition-duration: 300ms; /* duration-300 */
        transition-timing-function: ease-in-out; /* ease-in-out */
        padding: 0.5rem 1rem; /* px-4 py-2 */
        border-radius: 0.5rem; /* rounded-lg */
        border: 1px solid #60a5fa; /* border border-blue-400 */
        text-decoration: none;
        font-weight: 500; /* Added for better visibility */
        margin-top: 0.5rem; /* Add margin for smaller screens when it wraps */
    }
    @media (min-width: 640px) { /* sm breakpoint or adjust as needed */
        .dashboard-link {
            margin-top: 0;
        }
    }


    .dashboard-link:hover {
        color: #fff;
        border-color: #fff;
        background-color: rgba(255, 255, 255, 0.1); /* Subtle hover effect */
    }

    .dashboard-icon {
        font-size: 1.25rem; /* text-xl */
    }

    /* Main Content Area */
    .main-content-wrapper {
        margin-left: auto; /* mx-auto */
        margin-right: auto; /* mx-auto */
        padding-left: 1.5rem; /* px-6 */
        padding-right: 1.5rem; /* px-6 */
        padding-top: 2rem; /* py-8 */
        padding-bottom: 2rem; /* py-8 */
        flex: 1; /* flex-1 */
        width: 100%; /* Use full width and control with max-width */
        max-width: 60rem; /* Adjusted for better readability, e.g., max-w-3xl or 4xl */
    }

    .news-container {
        background-color: #fff; /* bg-white */
        border-radius: 0.75rem; /* rounded-xl */
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05); /* shadow-xl */
        overflow: hidden;
        transition-property: all;
        transition-duration: 300ms;
        transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1); /* ease-in-out */
    }

    .news-container:hover {
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); /* shadow-2xl */
        /* transform: translateY(-0.25rem); */ /* -translate-y-1 - Optional hover effect */
    }

    .news-header {
        background-color: #eff6ff; /* bg-blue-50 */
        border-bottom: 1px solid #dbeafe; /* border-b border-blue-100 */
        padding-left: 1.5rem; /* px-6 */
        padding-right: 1.5rem; /* px-6 */
        padding-top: 1.25rem; /* py-5 */
        padding-bottom: 1.25rem; /* py-5 */
    }

    .news-header-title {
        color: #1e40af; /* text-blue-800 */
        font-size: 1.5rem; /* text-2xl */
        font-weight: 600; /* font-semibold */
        display: flex;
        align-items: center;
        column-gap: 0.75rem; /* space-x-3 */
        margin: 0;
    }

    .news-header-icon {
        font-size: 1.25rem; /* text-xl */
    }

    .news-body {
        padding: 1.5rem; /* p-6 */
    }

    .news-list {
        display: flex;
        flex-direction: column;
        row-gap: 1.25rem; /* space-y-5 */
    }

    .news-item {
        display: grid;
        grid-template-columns: 1fr; /* grid-cols-1 */
        gap: 1rem; /* gap-4 */
        padding: 1.25rem; /* p-5 */
        background-color: #f0f9ff; /* lighter blue - bg-sky-50 */
        border-left: 4px solid #0ea5e9; /* border-l-4 border-sky-500 */
        border-radius: 0.5rem; /* rounded-lg */
        transition-property: all;
        transition-duration: 300ms;
        transition-timing-function: ease-in-out;
    }

    @media (min-width: 768px) { /* md breakpoint */
        .news-item {
            grid-template-columns: 1fr auto; /* md:grid-cols-[1fr_auto] */
        }
    }

    .news-item:hover {
        background-color: #e0f2fe; /* hover:bg-sky-100 */
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); /* hover:shadow-md */
    }

    .news-content {
        color: #374151; /* text-gray-700 */
        white-space: pre-wrap; 
        word-wrap: break-word; 
        font-size: 0.95rem; /* Slightly adjusted for readability */
    }

    .news-date {
        color: #6b7280; /* text-gray-500 */
        font-size: 0.875rem; /* text-sm */
        display: flex;
        flex-direction: column;
        align-items: flex-start; /* Align to start on mobile */
        text-align: left; /* Align text to start on mobile */
        white-space: nowrap;
    }

    @media (min-width: 768px) { /* md breakpoint */
        .news-date {
            align-items: flex-end; /* md:items-end */
            text-align: right; /* Align text to end on larger screens */
        }
    }

    .date-day {
        font-size: 1.5rem; /* text-2xl */
        font-weight: 700; /* font-bold */
        color: #0ea5e9; /* text-sky-500 */
    }

    .clock-icon {
        margin-right: 0.25rem; /* mr-1 */
    }

    /* No News Message */
    .no-news-message {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 3rem; /* p-12 */
        text-align: center;
        color: #9ca3af; /* text-gray-400 */
    }

    .no-news-icon {
        font-size: 3.125rem; /* text-5xl */
        margin-bottom: 1rem; /* mb-4 */
        color: #d1d5db; /* text-gray-300 */
    }

    .no-news-text {
        font-size: 1.125rem; /* text-lg */
        margin: 0;
    }

    /* Footer Styles */
    .main-footer {
        background-color: #111827; /* bg-gray-900 */
        color: #fff;
        text-align: center;
        padding-top: 1.5rem; /* py-6 */
        padding-bottom: 1.5rem; /* py-6 */
        margin-top: auto; /* mt-auto */
    }

    .footer-content-wrapper {
        max-width: 80rem; /* max-w-7xl */
        margin-left: auto; /* mx-auto */
        margin-right: auto; /* mx-auto */
        padding-left: 1.5rem; /* px-6 */
        padding-right: 1.5rem; /* px-6 */
        display: flex;
        flex-direction: column;
        row-gap: 1rem; /* space-y-4 */
    }

    .footer-links {
        display: flex;
        justify-content: center;
        column-gap: 1.5rem; /* space-x-6 */
        flex-wrap: wrap;
    }

    .footer-link {
        color: #d1d5db; /* text-gray-300 */
        transition-property: color;
        transition-duration: 300ms;
        transition-timing-function: ease-in-out;
        text-decoration: none;
    }

    .footer-link:hover {
        color: #fff;
    }

    .footer-copyright {
        color: #6b7280; /* text-gray-500 */
        font-size: 0.875rem; /* text-sm */
    }
    </style>
</head>
<body> 
    <header class="main-header">
        <div class="header-content-wrapper">
            <div class="header-title-group">
                <i class="fas fa-newspaper header-icon"></i>
                <h1 class="header-title"><?= htmlspecialchars(ucfirst($role)) ?> News Board</h1>
            </div>
            <?php if (!empty($dashboard_link)): ?>
                <a href="<?= htmlspecialchars($dashboard_link) ?>" class="dashboard-link">
                    <i class="fas fa-tachometer-alt dashboard-icon"></i> <span>Dashboard</span>
                </a>
            <?php endif; ?>
        </div>
    </header>

    <div class="main-content-wrapper">
        <div class="news-container">
            <div class="news-header">
                <h2 class="news-header-title">
                    <i class="fas fa-bell news-header-icon"></i>
                    Latest Notifications for <?= htmlspecialchars(ucfirst($role)) ?>
                </h2>
            </div>
            <div class="news-body">
                <?php if ($newsResult && $newsResult->num_rows > 0): ?>
                    <div class="news-list">
                        <?php while ($news = $newsResult->fetch_assoc()): ?>
                            <?php 
                                // Date formatting - handle different date formats
                                $dateValue = $news['created_at'];
                                if (is_numeric($dateValue)) {
                                    // If it's a timestamp
                                    $date = intval($dateValue);
                                } else {
                                    // If it's a date string
                                    $date = strtotime($dateValue);
                                    if ($date === false) {
                                        // If date parsing fails, use current time
                                        $date = time();
                                    }
                                }
                                
                                $day = date("d", $date);
                                $month = date("M", $date);
                                $year = date("Y", $date);
                                $time = date("g:i A", $date); // Changed to 12-hour format with AM/PM
                            ?>
                            <div class="news-item">
                                <div class="news-content"><?= nl2br(htmlspecialchars($news['news_content'] ?? 'No content available')) ?></div>
                                <div class="news-date">
                                    <span class="date-day"><?= $day ?></span>
                                    <span><?= $month ?> <?= $year ?></span>
                                    <span><i class="far fa-clock clock-icon"></i> <?= $time ?></span>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="no-news-message">
                        <i class="far fa-newspaper no-news-icon"></i>
                        <p class="no-news-text">No news available at the moment for <?= htmlspecialchars(ucfirst($role)) ?>.</p>
                    </div>
                <?php endif; ?>
                <?php if (isset($stmt) && $stmt) $stmt->close(); // Close statement for fetching news ?>
            </div>
        </div>
    </div>

    <footer class="main-footer">
        <div class="footer-content-wrapper">
            <div class="footer-links">
                <a href="#" class="footer-link">Home</a>
                <a href="#" class="footer-link">Portal</a>
                <a href="#" class="footer-link">Contact</a>
                <a href="#" class="footer-link">Help</a>
            </div>
            <div class="footer-copyright">
                &copy; <?php echo date("Y"); ?> Wisetech College Portal. All rights reserved.
            </div>
        </div>
    </footer>
    <?php if ($conn) $conn->close(); // Close connection at the very end ?>
</body>
</html>