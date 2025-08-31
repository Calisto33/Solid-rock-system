<?php
session_start();
// This path is already correct and tells us the right structure!
include '../config.php';

// Check if the user is logged in and is a parent
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'parent') {
    header("Location: ../login.php");
    exit();
}

$parent_user_id = $_SESSION['user_id'];

// This query works with the tables we know exist.
$noticesQuery = "
    SELECT 
        n.notice_content,
        n.created_at
    FROM notices n
    JOIN students s ON n.class = s.class
    JOIN parents p ON s.student_id = p.student_id
    WHERE p.user_id = ?
    ORDER BY n.created_at DESC";
    
$stmt = $conn->prepare($noticesQuery);
$stmt->bind_param("i", $parent_user_id);
$stmt->execute();
$noticesResult = $stmt->get_result();

// --- Start of The Templating ---

// --- PATHS CORRECTED HERE ---
require 'header.php';
require 'sidebar.php';
?>

<main class="main-content">
    <h1 class="page-title">School Notices</h1>

    <div class="notices-container">
        <?php if ($noticesResult->num_rows > 0): ?>
            <?php while ($notice = $noticesResult->fetch_assoc()): ?>
                <div class="notice-card">
                    <div class="notice-card-header">
                        <h3>School Notice</h3>
                    </div>
                    <div class="notice-card-body">
                        <p><?= nl2br(htmlspecialchars($notice['notice_content'])) ?></p>
                    </div>
                    <div class="notice-card-footer">
                        <small><i class="fas fa-calendar-alt"></i> Posted on: <?= date('F j, Y, g:i a', strtotime($notice['created_at'])) ?></small>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-state" style="grid-column: 1 / -1;">
                <i class="fas fa-bullhorn"></i>
                <p>There are no new notices for you at the moment.</p>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php
// Close database resources
$stmt->close();
$conn->close();

// --- PATH CORRECTED HERE ---
require 'footer.php';
?>