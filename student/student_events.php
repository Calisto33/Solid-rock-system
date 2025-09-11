<?php
session_start();

// Security check - ensure student is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$pageTitle = "Campus Events"; // This sets the title in the header
include 'header.php';         // This includes the sidebar and unified styles
include '../config.php';      // Your database connection

// Check if events table exists and what columns it has
$eventsTableExists = $conn->query("SHOW TABLES LIKE 'events'")->num_rows > 0;
$events = [];

if ($eventsTableExists) {
    // Check what columns exist in events table
    $eventsColumns = [];
    $columnResult = $conn->query("SHOW COLUMNS FROM events");
    while ($col = $columnResult->fetch_assoc()) {
        $eventsColumns[] = $col['Field'];
    }
    
    // Determine which columns to use based on what exists
    $hasTargetAudience = in_array('target_audience', $eventsColumns);
    $hasCreatedAt = in_array('created_at', $eventsColumns);
    $hasEventDate = in_array('event_date', $eventsColumns);
    $hasAttachmentLink = in_array('attachment_link', $eventsColumns);
    $hasAttachmentType = in_array('attachment_type', $eventsColumns);
    
    // Build query based on available columns
    $selectColumns = "event_id, title, description";
    
    // Add date column if available
    if ($hasCreatedAt) {
        $selectColumns .= ", created_at";
        $dateColumn = "created_at";
    } elseif ($hasEventDate) {
        $selectColumns .= ", event_date as created_at";
        $dateColumn = "event_date";
    } else {
        $selectColumns .= ", NOW() as created_at";
        $dateColumn = "event_id"; // fallback for ordering
    }
    
    // Add attachment columns if available
    if ($hasAttachmentLink) {
        $selectColumns .= ", attachment_link";
    } else {
        $selectColumns .= ", NULL as attachment_link";
    }
    
    if ($hasAttachmentType) {
        $selectColumns .= ", attachment_type";
    } else {
        $selectColumns .= ", NULL as attachment_type";
    }
    
    // Build WHERE clause if target_audience column exists
    $whereClause = "";
    if ($hasTargetAudience) {
        $whereClause = "WHERE target_audience = 'student' OR target_audience = 'all'";
    }
    
    // FIXED: Use actual column names from your database
    $eventsQuery = "SELECT {$selectColumns} FROM events {$whereClause} ORDER BY {$dateColumn} DESC";
    
    $result = $conn->query($eventsQuery);
    
    if ($result) {
        while ($event = $result->fetch_assoc()) {
            $events[] = $event;
        }
    }
}
?>

<!-- Page-specific styles for the events page -->
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
    .events-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 1.5rem;
    }
    .event-card {
        background: var(--widget-bg);
        border-radius: var(--rounded-lg);
        box-shadow: var(--shadow);
        transition: var(--transition);
        display: flex;
        flex-direction: column;
        border-top: 5px solid var(--accent-purple);
    }
    .event-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-lg);
    }
    .event-card-header {
        padding: 1.25rem 1.5rem;
    }
    .event-card-header .event-title {
        font-size: 1.2rem;
        font-weight: 600;
        color: var(--primary-text);
    }
    .event-card-body {
        padding: 0 1.5rem 1.5rem 1.5rem;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }
    .event-description {
        line-height: 1.7;
        color: var(--secondary-text);
        flex-grow: 1;
        margin-bottom: 1.5rem;
    }
    .event-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        color: var(--secondary-text);
        font-size: 0.85rem;
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    .event-empty {
        grid-column: 1 / -1;
        text-align: center;
        padding: 3rem;
        background: var(--widget-bg);
        border-radius: var(--rounded-lg);
        box-shadow: var(--shadow);
    }
    .event-empty i {
        color: var(--secondary-text);
        opacity: 0.5;
    }
    .btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        background-color: var(--accent-purple);
        color: white;
        text-decoration: none;
        border-radius: 0.375rem;
        font-size: 0.875rem;
        font-weight: 500;
        transition: background-color 0.2s;
    }
    .btn:hover {
        background-color: var(--accent-blue);
        color: white;
    }
    :root {
        --widget-bg: #FFFFFF;
        --primary-text: #333;
        --secondary-text: #666;
        --shadow: 0 4px 15px rgba(0, 0, 0, 0.07);
        --shadow-lg: 0 10px 25px rgba(0, 0, 0, 0.1);
        --rounded-lg: 8px;
        --accent-purple: #6D28D9;
        --accent-blue: #2563EB;
        --transition: all 0.3s ease-in-out;
    }
</style>

<h1 class="page-title">Campus Events</h1>
<p class="page-intro">Find out about upcoming activities, workshops, and important dates for all students.</p>

<div class="events-grid">
    <?php if (!empty($events)): ?>
        <?php foreach ($events as $event): ?>
            <div class="event-card">
                <div class="event-card-header">
                    <h3 class="event-title"><?= htmlspecialchars($event['title'] ?? 'Untitled Event') ?></h3>
                </div>
                <div class="event-card-body">
                    <p class="event-description"><?= nl2br(htmlspecialchars($event['description'] ?? 'No description available.')) ?></p>
                    <div class="event-footer">
                        <span>
                            <i class="fas fa-calendar-alt fa-fw"></i> 
                            <?php 
                            $dateValue = $event['created_at'] ?? date('Y-m-d H:i:s');
                            if (is_numeric($dateValue)) {
                                echo "Event ID: " . $dateValue;
                            } else {
                                echo "Posted: " . date("d M, Y", strtotime($dateValue));
                            }
                            ?>
                        </span>
                        <?php if (!empty($event['attachment_link'])): ?>
                            <a href="<?= htmlspecialchars($event['attachment_link']) ?>" class="btn" download>
                                <i class="fas fa-download"></i> Attachment
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="event-empty">
            <i class="fas fa-calendar-times" style="font-size: 2.5rem; margin-bottom: 1rem;"></i>
            <p>There are no upcoming events at this time.</p>
        </div>
    <?php endif; ?>
</div>

<?php 
include 'footer.php'; 
?>