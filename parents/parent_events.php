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

// Fetch events using actual table columns
$eventsQuery = "
    SELECT event_id, title, description, event_date
    FROM events 
    ORDER BY event_date DESC, event_id DESC
    LIMIT 20";
$result = $conn->query($eventsQuery);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Events - Parent Portal</title>
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

        /* --- Events Grid --- */
        .events-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .event-card {
            background: var(--background-white);
            border-radius: 0.75rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border: 1px solid var(--border-color);
            overflow: hidden;
            transition: all 0.2s ease-in-out;
        }

        .event-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .event-card-header {
            background: linear-gradient(135deg, var(--primary-color), #1e40af);
            color: white;
            padding: 1.5rem;
        }

        .event-card-header h3 {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0;
        }

        .event-card-body {
            padding: 1.5rem;
        }

        .event-card-body p {
            color: var(--text-dark);
            line-height: 1.6;
            margin-bottom: 1rem;
        }

        .event-card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 1rem;
            border-top: 1px solid var(--border-color);
        }

        .event-timestamp {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-muted);
            font-size: 0.875rem;
        }

        .event-timestamp i {
            color: var(--primary-color);
        }

        .event-date-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: #f0fdf4;
            color: var(--success-color);
            border-radius: 0.5rem;
            font-weight: 600;
            font-size: 0.875rem;
            margin-bottom: 1rem;
        }

        .no-events {
            text-align: center;
            padding: 3rem;
            color: var(--text-muted);
            grid-column: 1 / -1;
        }

        .no-events i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        /* Responsive Design */
        @media (max-width: 1200px) { .events-grid { grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); } .sidebar { width: 220px; } }
        @media (max-width: 768px) { 
            body { flex-direction: column; height: auto; overflow: auto; } 
            .sidebar { width: 100%; height: auto; border-right: none; border-bottom: 1px solid var(--border-color); } 
            .main-content { padding: 1rem; } 
            .events-grid { grid-template-columns: 1fr; gap: 1rem; }
            .child-tabs { flex-direction: column; }
        }
    </style>
</head>
<body>
    <div class="dashboard-wrapper">
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="../images/logo.jpeg" alt="Solid Rock  Logo">
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
                    <li><a href="parent_events.php" class="active"><i class="fas fa-calendar-alt"></i> Events</a></li>
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
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <header class="main-header">
                <div class="header-title">
                    <h2><i class="fas fa-calendar-alt"></i> School Events & Announcements</h2>
                    <p>Stay updated with the latest happenings at the school.</p>
                </div>
            </header>

            <div class="events-grid">
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($event = $result->fetch_assoc()): ?>
                        <div class="event-card">
                            <div class="event-card-header">
                                <h3><?= htmlspecialchars($event['title'] ?? 'School Event') ?></h3>
                            </div>
                            <div class="event-card-body">
                                <?php if (!empty($event['event_date'])): ?>
                                <div class="event-date-badge">
                                    <i class="fas fa-calendar"></i>
                                    <span><?= date('F j, Y', strtotime($event['event_date'])) ?></span>
                                </div>
                                <?php endif; ?>
                                
                                <p><?= nl2br(htmlspecialchars($event['description'] ?? 'No description available.')) ?></p>
                                
                                <div class="event-card-footer">
                                    <div class="event-timestamp">
                                        <i class="fas fa-info-circle"></i>
                                        <span>Event ID: #<?= htmlspecialchars($event['event_id']) ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-events">
                        <i class="fas fa-calendar-times"></i>
                        <h3>No Events Available</h3>
                        <p>There are currently no school events or announcements to display.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>