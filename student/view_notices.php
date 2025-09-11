<?php
// *** FIX: Added session_start() at the very top.
session_start();

$pageTitle = "Notices"; 
include 'header.php';   
include '../config.php';

// *** FIX: Added the standard security block to check for user_id and role.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

// *** FIX: Using the correct session variable 'user_id'.
$user_id = $_SESSION['user_id'];

// FIXED: Fetch the student's class using user_id instead of id
$classQuery = "SELECT class FROM students WHERE user_id = ?";
$stmtClass = $conn->prepare($classQuery);
if (!$stmtClass) { die("Error preparing query: " . $conn->error); }
$stmtClass->bind_param("i", $user_id);
$stmtClass->execute();
$resultClass = $stmtClass->get_result();
$studentData = $resultClass->fetch_assoc();
$stmtClass->close();

if (!$studentData) {
    die("Error: Student data not found.");
}
$student_class = $studentData['class'];

// Check what tables exist for notices
$noticesTableExists = $conn->query("SHOW TABLES LIKE 'notices'")->num_rows > 0;
$subjectsTableExists = $conn->query("SHOW TABLES LIKE 'subjects'")->num_rows > 0;

$notices = [];

if ($noticesTableExists) {
    // Check if notices table has subject_id column
    $columnCheck = $conn->query("SHOW COLUMNS FROM notices LIKE 'subject_id'");
    $hasSubjectId = $columnCheck->num_rows > 0;
    
    if ($subjectsTableExists && $hasSubjectId) {
        // FIXED: Use 'subjects' table instead of 'table_subject'
        $noticesQuery = "
            SELECT n.notice_id, s.subject_name, n.notice_content, n.created_at
            FROM notices n
            LEFT JOIN subjects s ON n.subject_id = s.subject_id
            WHERE n.class = ?
            ORDER BY n.created_at DESC";
    } else {
        // Fallback query without subject join
        $noticesQuery = "
            SELECT n.notice_id, 'General' as subject_name, n.notice_content, n.created_at
            FROM notices n
            WHERE n.class = ?
            ORDER BY n.created_at DESC";
    }
    
    $stmtNotices = $conn->prepare($noticesQuery);
    if (!$stmtNotices) { die("Error preparing notices query: " . $conn->error); }
    $stmtNotices->bind_param("s", $student_class);
    $stmtNotices->execute();
    $noticesResult = $stmtNotices->get_result();
    
    while ($notice = $noticesResult->fetch_assoc()) {
        $notices[] = $notice;
    }
    
    $stmtNotices->close();
}
?>

<style>
    .page-title {
        font-weight: 700;
        color: var(--primary-text);
        font-size: 1.8rem;
        margin-bottom: 1rem;
    }
    .page-intro {
        margin-bottom: 2rem;
        color: var(--secondary-text);
        max-width: 70ch;
    }
    .notice-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 1.5rem;
    }
    .notice-card {
        background: var(--widget-bg);
        border-radius: var(--rounded-lg);
        box-shadow: var(--shadow);
        transition: var(--transition);
        display: flex;
        flex-direction: column;
        border-left: 5px solid var(--accent-blue);
    }
    .notice-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-lg);
    }
    .notice-card-header {
        padding: 1.25rem 1.5rem;
        font-weight: 600;
        font-size: 1.1rem;
        color: var(--accent-purple);
    }
    .notice-card-body {
        padding: 0 1.5rem 1.5rem 1.5rem;
        flex-grow: 1;
        line-height: 1.7;
        color: var(--secondary-text);
    }
    .notice-card-footer {
        padding: 1rem 1.5rem;
        border-top: 1px solid var(--border-color);
        background-color: #f9fafb;
        color: var(--secondary-text);
        font-size: 0.85rem;
    }
    .notice-empty {
        grid-column: 1 / -1;
        text-align: center;
        padding: 3rem;
        background: var(--widget-bg);
        border-radius: var(--rounded-lg);
        box-shadow: var(--shadow);
    }
    .notice-empty i {
        color: var(--secondary-text);
        opacity: 0.5;
    }
    :root {
        --widget-bg: #FFFFFF;
        --primary-text: #333;
        --secondary-text: #666;
        --border-color: #E8EDF3;
        --shadow: 0 4px 15px rgba(0, 0, 0, 0.07);
        --shadow-lg: 0 10px 25px rgba(0, 0, 0, 0.1);
        --rounded-lg: 8px;
        --accent-purple: #6D28D9;
        --accent-blue: #2563EB;
        --transition: all 0.3s ease-in-out;
    }
</style>

<h1 class="page-title">Notice Board</h1>
<p class="page-intro">Stay updated with important announcements and information relevant to your class (<?= htmlspecialchars($student_class) ?>).</p>

<div class="notice-grid">
    <?php if (!empty($notices)): ?>
        <?php foreach ($notices as $notice): ?>
            <div class="notice-card">
                <div class="notice-card-header">
                    <?= htmlspecialchars($notice['subject_name'] ?? 'General') ?>
                </div>
                <div class="notice-card-body">
                    <?= nl2br(htmlspecialchars($notice['notice_content'] ?? 'No content available')) ?>
                </div>
                <div class="notice-card-footer">
                    <i class="fas fa-calendar-alt fa-fw"></i> Posted on <?= date("d M Y, H:i", strtotime($notice['created_at'])) ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="notice-empty">
            <i class="fas fa-info-circle" style="font-size: 2.5rem; margin-bottom: 1rem;"></i>
            <p>No notices available for your class at the moment.</p>
        </div>
    <?php endif; ?>
</div>

<?php 
include 'footer.php'; 
?>