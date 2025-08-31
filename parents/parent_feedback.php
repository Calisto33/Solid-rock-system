<?php
session_start();
include '../config.php';

// Check if the user is logged in and is a parent
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'parent') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// --- FETCH ALL CHILDREN ASSIGNED TO THIS PARENT ---
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

// --- DETERMINE WHICH CHILD TO DISPLAY ---
$selectedChildIndex = 0;
if (isset($_GET['child']) && is_numeric($_GET['child'])) {
    $requestedIndex = intval($_GET['child']);
    if ($requestedIndex >= 0 && $requestedIndex < count($children)) {
        $selectedChildIndex = $requestedIndex;
    }
}

// Fetch the parent ID from the parents table
$parentQuery = "SELECT parent_id FROM parents WHERE user_id = ?";
$stmt = $conn->prepare($parentQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$parentResult = $stmt->get_result();
$parentData = $parentResult->fetch_assoc();

if (!$parentData) {
    die("Error: Parent profile not found.");
}

$parent_id = $parentData['parent_id'];
$stmt->close();

// Handle feedback submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['feedback'])) {
    $feedback = trim($_POST['feedback']);
    
    if (!empty($feedback)) {
        // Generate a smaller unique feedback_id that fits in INT range
        $feedback_id = rand(100000, 999999); // 6-digit random number
        
        // Check if this ID already exists and generate a new one if needed
        $checkQuery = "SELECT feedback_id FROM parent_feedback WHERE feedback_id = ?";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bind_param("i", $feedback_id);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        // If ID exists, generate a new one
        while ($result->num_rows > 0) {
            $feedback_id = rand(100000, 999999);
            $checkStmt->bind_param("i", $feedback_id);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
        }
        $checkStmt->close();
        
        // Insert with explicit feedback_id
        $stmt = $conn->prepare("INSERT INTO parent_feedback (feedback_id, parent_id, feedback, status) VALUES (?, ?, ?, 'Pending')");
        $stmt->bind_param("iis", $feedback_id, $parent_id, $feedback);
        
        if ($stmt->execute()) {
            $successMessage = "Thank you for your feedback! It has been submitted successfully.";
        } else {
            $errorMessage = "Error submitting feedback: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $errorMessage = "Please enter your feedback before submitting.";
    }
}

// Fetch all feedback submitted by the parent
$feedbackResult = null;
try {
    $feedbackQuery = "SELECT feedback, status, admin_response, created_at FROM parent_feedback WHERE parent_id = ? ORDER BY created_at DESC";
    $stmt = $conn->prepare($feedbackQuery);
    $stmt->bind_param("i", $parent_id);
    $stmt->execute();
    $feedbackResult = $stmt->get_result();
    $stmt->close();
} catch (mysqli_sql_exception $e) {
    // Handle case where query fails
    $feedbackResult = null;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parent Feedback - Parent Portal</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #3b82f6;
            --background-light: #f8f9fa;
            --background-white: #ffffff;
            --text-dark: #212529;
            --text-muted: #6c757d;
            --border-color: #dee2e6;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
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
            background-color: #ffebee;
            color: var(--danger-color);
            font-weight: 600;
            transition: all 0.2s ease-in-out;
        }
        .logout-btn i { margin-right: 0.5rem; }
        .logout-btn:hover { background-color: var(--danger-color); color: white; transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.1); }

        /* --- Child Selector --- */
        .child-selector {
            margin-bottom: 2rem;
            padding: 1rem;
            background-color: #e0f2fe;
            border-radius: 0.75rem;
            border: 1px solid #bae6fd;
        }
        .child-selector h3 {
            font-size: 0.9rem;
            color: #0ea5e9;
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
            color: #0ea5e9;
            border-color: #bae6fd;
        }
        .child-tab:not(.active):hover {
            background-color: #f0f9ff;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .child-tab.active {
            background-color: #0ea5e9;
            color: white;
            border-color: #0ea5e9;
            box-shadow: 0 2px 8px rgba(14, 165, 233, 0.3);
        }

        /* --- Main Content --- */
        .main-content { flex: 1; background-color: var(--background-white); padding: 2rem; overflow-y: auto; }
        .main-header { margin-bottom: 2rem; }
        .header-title h2 { font-weight: 700; font-size: 1.75rem; margin-bottom: 0.5rem; }
        .header-title p { color: var(--text-muted); }

        /* --- Feedback Grid --- */
        .feedback-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-top: 2rem;
        }

        .card {
            background: var(--background-white);
            border-radius: 0.75rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border: 1px solid var(--border-color);
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary-color), #1e40af);
            color: white;
            padding: 1.5rem;
        }

        .card-header h3 {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0;
        }

        .card-body {
            padding: 1.5rem;
        }

        /* --- Form Styles --- */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-dark);
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            font-size: 1rem;
            transition: border-color 0.2s;
            resize: vertical;
            min-height: 120px;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-submit {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-submit:hover {
            background-color: #2563eb;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        /* --- Message Boxes --- */
        .message-box {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid;
        }

        .message-box.success {
            background-color: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }

        .message-box.error {
            background-color: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }

        /* --- Feedback History --- */
        .feedback-history-list {
            max-height: 600px;
            overflow-y: auto;
        }

        .feedback-item {
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            padding: 1rem;
        }

        .feedback-item-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-reviewed {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        .status-resolved {
            background-color: #d4edda;
            color: #155724;
        }

        .date {
            color: var(--text-muted);
            font-size: 0.875rem;
        }

        .user-feedback {
            margin-bottom: 1rem;
            line-height: 1.6;
        }

        .admin-response {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 0.5rem;
            border-left: 4px solid var(--primary-color);
        }

        .admin-response strong {
            color: var(--primary-color);
        }

        .empty-feedback {
            text-align: center;
            padding: 3rem;
            color: var(--text-muted);
        }

        .empty-feedback i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        /* Responsive Design */
        @media (max-width: 1200px) { .feedback-grid { grid-template-columns: 1fr; } .sidebar { width: 220px; } }
        @media (max-width: 768px) { 
            body { flex-direction: column; height: auto; overflow: auto; } 
            .sidebar { width: 100%; height: auto; border-right: none; border-bottom: 1px solid var(--border-color); } 
            .main-content { padding: 1rem; } 
            .feedback-grid { grid-template-columns: 1fr; gap: 1rem; }
            .child-tabs { flex-direction: column; }
        }
    </style>
</head>
<body>
    <div class="dashboard-wrapper">
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="../images/logo.jpg" alt="School Logo">
                <h1>Parent Portal</h1>
            </div>
            <nav class="sidebar-nav">
                <h3>Menu</h3>
                <ul>
                    <li><a href="parent_home.php?child=<?= $selectedChildIndex ?>"><i class="fas fa-home"></i> Home</a></li>
                    <li><a href="parent_results.php?child=<?= $selectedChildIndex ?>"><i class="fas fa-poll"></i> Check Result</a></li>
                </ul>
                
                <h3>School</h3>
                <ul>
                    <li><a href="student_details.php?child=<?= $selectedChildIndex ?>"><i class="fas fa-user-graduate"></i> Student Details</a></li>
                    <li><a href="teachers_profiles.php"><i class="fas fa-chalkboard-teacher"></i> Teachers</a></li>
                    <li><a href="parent_notices.php"><i class="fas fa-bullhorn"></i> Notices</a></li>
                    <li><a href="parent_events.php"><i class="fas fa-calendar-alt"></i> Events</a></li>
                    <li><a href="parent_feedback.php" class="active"><i class="fas fa-comments"></i> Feedback</a></li>
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
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <header class="main-header">
                <div class="header-title">
                    <h2><i class="fas fa-comments"></i> Parent Feedback</h2>
                    <p>Share your thoughts, suggestions, or concerns with us.</p>
                </div>
            </header>

            <div class="feedback-grid">
                <div class="card feedback-form-card">
                    <div class="card-header">
                        <h3>Submit New Feedback</h3>
                    </div>
                    <div class="card-body">
                        <?php if (isset($successMessage)): ?>
                            <div class="message-box success"><?= htmlspecialchars($successMessage) ?></div>
                        <?php elseif (isset($errorMessage)): ?>
                            <div class="message-box error"><?= htmlspecialchars($errorMessage) ?></div>
                        <?php endif; ?>

                        <form method="POST" action="parent_feedback.php">
                            <div class="form-group">
                                <label for="feedback" class="form-label">Your Message</label>
                                <textarea id="feedback" name="feedback" class="form-control" placeholder="Your feedback helps us improve our services. Please share your thoughts, suggestions, or concerns..." required rows="8"></textarea>
                            </div>
                            <button type="submit" class="btn btn-submit" style="width: 100%; margin-top: 1rem;">
                                <i class="fas fa-paper-plane"></i> Send Feedback
                            </button>
                        </form>
                    </div>
                </div>

                <div class="card feedback-history-card">
                    <div class="card-header">
                        <h3>Your Submission History</h3>
                    </div>
                    <div class="card-body">
                        <div class="feedback-history-list">
                            <?php if ($feedbackResult && $feedbackResult->num_rows > 0): ?>
                                <?php while ($row = $feedbackResult->fetch_assoc()): ?>
                                    <div class="feedback-item">
                                        <div class="feedback-item-header">
                                            <span class="status-badge status-<?= strtolower(htmlspecialchars($row['status'] ?? 'pending')) ?>">
                                                <?= htmlspecialchars($row['status'] ?? 'Pending') ?>
                                            </span>
                                            <span class="date">
                                                <i class="fas fa-calendar-alt"></i> <?= date('F j, Y', strtotime($row['created_at'])) ?>
                                            </span>
                                        </div>
                                        <div class="feedback-item-body">
                                            <p class="user-feedback"><?= nl2br(htmlspecialchars($row['feedback'])) ?></p>

                                            <?php if (!empty($row['admin_response'])): ?>
                                                <div class="admin-response">
                                                    <strong>Admin Response:</strong>
                                                    <p><?= nl2br(htmlspecialchars($row['admin_response'])) ?></p>
                                                </div>
                                            <?php else: ?>
                                                <div class="admin-response">
                                                    <strong>Admin Response:</strong>
                                                    <p style="font-style: italic; color: var(--text-muted);">No response yet. The administration will review your feedback shortly.</p>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="empty-feedback">
                                    <i class="fas fa-comments"></i>
                                    <h4>No Feedback History</h4>
                                    <p>Your submitted feedback will appear here. Submit your first feedback using the form on the left.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>