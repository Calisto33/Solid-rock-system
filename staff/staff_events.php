<?php
// Set the page title for the header
$pageTitle = "Staff Events & Announcements";

// Include the new header. It handles security, session, db connection, and the sidebar.
include 'header.php';

// --- PHP LOGIC SPECIFIC TO THIS PAGE ---

// Mark all existing events as read for the logged-in staff member
// This runs every time the staff member visits this page.
$sql_mark_read = "INSERT IGNORE INTO user_read_events (user_id, event_id)
                  SELECT ?, events.event_id FROM events";
$stmt_mark = $conn->prepare($sql_mark_read);

if ($stmt_mark) {
    $stmt_mark->bind_param("i", $staff_id); // $staff_id comes from header.php
    $stmt_mark->execute();
    $stmt_mark->close();
} else {
    error_log("Failed to prepare statement for marking events as read in staff_events.php: " . $conn->error);
}

// Fetch events targeted at staff or all audiences
$eventsQuery = "
    SELECT event_id, title, description, attachment_type, attachment_link, created_at 
    FROM events 
    WHERE target_audience = 'staff' OR target_audience = 'all'
    ORDER BY created_at DESC";
$result = $conn->query($eventsQuery);
?>

<style>
    .section-title {
        font-size: 2rem; font-weight: 800; color: var(--text-color);
        margin-bottom: 2rem; text-align: center;
    }
    .section-title .fa-bullhorn { color: var(--primary-color); }
    .events-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 2rem;
    }
    .event-card {
        background: var(--card-bg); border-radius: 0.75rem; box-shadow: var(--shadow-md);
        overflow: hidden; transition: all 0.3s ease; border: 1px solid #e5e7eb;
        height: 100%; display: flex; flex-direction: column;
    }
    .event-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-lg); }
    .event-content { padding: 1.75rem; flex-grow: 1; display: flex; flex-direction: column; }
    .event-title { font-size: 1.35rem; font-weight: 700; margin-bottom: 0.75rem; }
    .event-description { color: #6b7280; font-size: 1rem; margin-bottom: 1.5rem; flex-grow: 1; white-space: pre-wrap; }
    .event-footer {
        display: flex; justify-content: space-between; align-items: center;
        margin-top: auto; padding-top: 1rem; border-top: 1px solid #e5e7eb;
    }
    .event-date { color: #6b7280; font-size: 0.875rem; display: flex; align-items: center; gap: 0.35rem; }
    .event-date .fa-clock { color: #7c3aed; }
    .event-attachment-link {
        display: inline-flex; align-items: center; color: var(--primary-color); text-decoration: none;
        font-weight: 600; font-size: 0.9rem; gap: 0.35rem; padding: 0.5rem 0.75rem;
        border-radius: 0.5rem; background-color: #f9fafb;
    }
    .event-attachment-link:hover { background-color: #e5e7eb; }
    .empty-state { text-align: center; padding: 4rem 2rem; color: #6b7280; background-color: #ffffff; border-radius: 0.75rem; }
    .empty-state .fa-calendar-times { font-size: 3rem; color: #e5e7eb; margin-bottom: 1rem; }
    .empty-state h3 { font-size: 1.25rem; margin-bottom: 0.5rem; }
</style>

<h2 class="section-title"><i class="fas fa-bullhorn"></i> Upcoming Events & Announcements</h2>

<div class="events-grid">
    <?php if ($result && $result->num_rows > 0): ?>
        <?php while ($event = $result->fetch_assoc()): ?>
            <div class="event-card">
                <div class="event-content">
                    <h3 class="event-title"><?= htmlspecialchars($event['title']) ?></h3>
                    <p class="event-description"><?= nl2br(htmlspecialchars($event['description'])) ?></p>
                    
                    <div class="event-footer">
                        <span class="event-date">
                            <i class="fas fa-clock"></i>
                            <?= date("D, d M Y", strtotime($event['created_at'])) ?>
                        </span>
                        
                        <?php if (!empty($event['attachment_link'])): ?>
                            <a href="<?= htmlspecialchars($event['attachment_link']) ?>" class="event-attachment-link" download>
                                <i class="fas fa-download"></i> Download
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-calendar-times"></i>
            <h3>No events available at the moment</h3>
            <p>Please check back later for upcoming staff events and announcements.</p>
        </div>
    <?php endif; ?>
</div>

<?php
// Include the new footer to close the page layout
if ($result) $result->close();
$conn->close();
include 'footer.php';
?>