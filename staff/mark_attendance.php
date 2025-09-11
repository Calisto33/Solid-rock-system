<?php
// Set the page title for the header
$pageTitle = "Mark Attendance"; 

// Include the new header. It handles security, session, db connection, and the sidebar.
include 'header.php'; 

// --- PHP LOGIC SPECIFIC TO THE ATTENDANCE PAGE ---

date_default_timezone_set('Africa/Harare'); 

$selected_date = date("Y-m-d"); // Date is ALWAYS today
$selected_class = $_GET['class'] ?? null;
$message = "";
$message_type = ""; 

// Fetch all available classes for the dropdown
$classes = [];
$classQuery = "SELECT DISTINCT class FROM students ORDER BY class";
$classResult = $conn->query($classQuery);
if ($classResult) {
    while ($row = $classResult->fetch_assoc()) {
        $classes[] = $row['class'];
    }
}

// Handle the form submission when attendance is saved
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_attendance'])) {
    $posted_date = date("Y-m-d");
    $posted_class = $_POST['selected_class'] ?? null; 
    $attendance_data = $_POST['attendance'] ?? [];

    if (empty($posted_class)) { 
        $message = "Class must be selected to submit attendance.";
        $message_type = "error";
    } elseif (empty($attendance_data)) {
        $message = "No attendance data submitted. Please mark students.";
        $message_type = "error";
    } else {
        // First, delete any existing attendance for this class and date to prevent duplicates
        $deleteQuery = "DELETE FROM attendance WHERE attendance_date = ? AND class = ?";
        $deleteStmt = $conn->prepare($deleteQuery);
        $deleteStmt->bind_param("ss", $posted_date, $posted_class);
        $deleteStmt->execute();
        $deleteStmt->close();
        
        $success_count = 0;
        
        // Now insert the new attendance records
        foreach ($attendance_data as $student_id_key => $data) {
            $student_id = trim($student_id_key);
            $status = ucfirst(strtolower(trim($data['status'] ?? '')));
            $comments = trim($data['comments'] ?? '');

            // Only process valid statuses and valid student IDs
            if (in_array($status, ['Present', 'Absent']) && !empty($student_id)) {
                $insertQuery = "INSERT INTO attendance (student_id, class, attendance_date, status, period, subject, notes, created_at) VALUES (?, ?, ?, ?, 'Full Day', 'General', ?, NOW())";
                $stmtInsert = $conn->prepare($insertQuery);
                
                if ($stmtInsert) {
                    $stmtInsert->bind_param("sssss", $student_id, $posted_class, $posted_date, $status, $comments);
                    if ($stmtInsert->execute()) {
                        $success_count++;
                    }
                    $stmtInsert->close();
                }
            }
        }
        
        if ($success_count > 0) {
            $message = "Successfully marked attendance for $success_count students in " . htmlspecialchars($posted_class) . ".";
            $message_type = "success";
        } else {
            $message = "No attendance was recorded. Please try again.";
            $message_type = "error";
        }
        
        $selected_class = $posted_class;
    }
}

// Fetch students for the selected class who have NOT been marked today
$students = [];
if ($selected_class) {

$query = "
    SELECT s.student_id, s.username, s.first_name, s.last_name, s.class
    FROM students s
    WHERE s.class = ? AND s.student_id NOT IN (
        SELECT att.student_id FROM attendance att 
        WHERE att.attendance_date = ? AND att.class = ? AND att.student_id IS NOT NULL
    )
    ORDER BY s.first_name, s.last_name
";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("sss", $selected_class, $selected_date, $selected_class);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            // Create display name
            $first_name = trim($row['first_name'] ?? '');
            $last_name = trim($row['last_name'] ?? '');
            
            if (!empty($first_name) && !empty($last_name)) {
                $row['display_name'] = $first_name . ' ' . $last_name;
            } elseif (!empty($first_name)) {
                $row['display_name'] = $first_name;
            } elseif (!empty($last_name)) {
                $row['display_name'] = $last_name;
            } else {
                $row['display_name'] = $row['username'];
            }
            
            $students[] = $row;
        }
        if (empty($students) && !empty($selected_class) && empty($message)) {
            $message = "All students in '" . htmlspecialchars(ucwords(str_replace('_',' ',$selected_class))) . "' have been marked for today.";
            $message_type = "info";
        }
        $stmt->close();
    }
}

// Check if there are students already marked today for this class
$already_marked = [];
if ($selected_class) {
    $markedQuery = "
        SELECT s.student_id, s.first_name, s.last_name, a.status, a.notes
        FROM students s
        JOIN attendance a ON s.student_id = a.student_id
        WHERE s.class = ? AND a.attendance_date = ? AND s.student_id LIKE 'STU%'
        ORDER BY s.first_name, s.last_name
    ";
    $stmt = $conn->prepare($markedQuery);
    if ($stmt) {
        $stmt->bind_param("ss", $selected_class, $selected_date);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $first_name = trim($row['first_name'] ?? '');
            $last_name = trim($row['last_name'] ?? '');
            
            if (!empty($first_name) && !empty($last_name)) {
                $row['display_name'] = $first_name . ' ' . $last_name;
            } else {
                $row['display_name'] = $row['student_id'];
            }
            
            $already_marked[] = $row;
        }
        $stmt->close();
    }
}
?>

<style>
    /* These styles apply only to this attendance page */
    .filters-form { display: flex; flex-wrap: wrap; gap: 1.5rem; margin-bottom: 2rem; padding: 1.5rem; background-color: #f1f3f5; border-radius: 8px; align-items: flex-end; }
    .filters-form > div { display: flex; flex-direction: column; gap: 0.5rem; }
    .filters-form label, .filters-form .date-display strong { font-weight: 500; font-size: 0.9rem; }
    .filters-form select { padding: 0.75rem; border: 1px solid #e9ecef; border-radius: 8px; font-size: 1rem; min-width: 200px; }
    .button-load-students { padding: 0.85rem 1.5rem; color: #fff; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; background-color: #2563eb; }
    .message { padding: 1.25rem; margin-bottom: 1.5rem; border-radius: 8px; font-weight: 500; }
    .message.success { background-color: #d1e7dd; color: #0f5132; }
    .message.error { background-color: #f8d7da; color: #842029; }
    .message.info { background-color: #cff4fc; color: #055160; }
    
    .attendance-section { margin-bottom: 2rem; }
    .section-title { font-size: 1.2rem; font-weight: 600; margin-bottom: 1rem; color: #2563eb; border-bottom: 2px solid #e9ecef; padding-bottom: 0.5rem; }
    
    .attendance-table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
    .attendance-table th, .attendance-table td { padding: 0.9rem 1rem; border: 1px solid #e9ecef; text-align: left; vertical-align: middle; }
    .attendance-table th { background-color: #f1f3f5; }
    .attendance-table input[type="text"] { width: 100%; padding: 0.6rem; border: 1px solid #e9ecef; border-radius: 4px; }
    .attendance-status-group { display: flex; gap: 0.5rem; }
    .status-button input[type="radio"] { opacity: 0; position: absolute; }
    .status-button-text { padding: 0.5rem 0.9rem; border: 1px solid #e9ecef; border-radius: 8px; transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 0.4rem; cursor: pointer; }
    .status-button input[type="radio"]:checked + .status-button-text.present-text { background-color: #06d6a0; color: #fff; }
    .status-button input[type="radio"]:checked + .status-button-text.absent-text { background-color: #ef476f; color: #fff; }
    .submit-all-btn { background-color: #06d6a0; display: block; width: fit-content; margin: 2rem auto 0; padding: 0.85rem 1.5rem; color: #fff; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; }
    
    .already-marked-table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
    .already-marked-table th, .already-marked-table td { padding: 0.9rem 1rem; border: 1px solid #e9ecef; text-align: left; vertical-align: middle; }
    .already-marked-table th { background-color: #f8f9fa; }
    .status-badge { padding: 0.3rem 0.7rem; border-radius: 15px; font-size: 0.8rem; font-weight: 600; }
    .status-present { background-color: #d1e7dd; color: #0f5132; }
    .status-absent { background-color: #f8d7da; color: #842029; }
    .status-late { background-color: #fff3cd; color: #664d03; }
    
    .stats-summary { display: flex; gap: 1rem; margin-bottom: 1rem; flex-wrap: wrap; }
    .stat-item { background: #f8f9fa; padding: 1rem; border-radius: 8px; text-align: center; flex: 1; min-width: 120px; }
    .stat-number { font-size: 1.5rem; font-weight: 700; color: #2563eb; }
    .stat-label { font-size: 0.9rem; color: #6c757d; }
</style>

<div class="card">
    <form method="GET" action="" class="filters-form">
        <div class="date-display">
            <label>Date:</label>
            <p><strong><?= htmlspecialchars(date("l, F j, Y")) ?></strong> (Today)</p>
        </div>
        <div>
            <label for="class-select">Select Class:</label>
            <select id="class-select" name="class" onchange="this.form.submit()">
                <option value="">-- Select Class --</option>
                <?php foreach ($classes as $class_option): ?>
                    <option value="<?= htmlspecialchars($class_option) ?>" <?= ($selected_class == $class_option) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($class_option) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>

    <?php if ($message): ?>
        <div class="message <?= htmlspecialchars($message_type) ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if ($selected_class): ?>
        <div class="stats-summary">
            <div class="stat-item">
                <div class="stat-number"><?= count($already_marked) ?></div>
                <div class="stat-label">Already Marked</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?= count($students) ?></div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?= count($already_marked) + count($students) ?></div>
                <div class="stat-label">Total Students</div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($already_marked)): ?>
        <div class="attendance-section">
            <h3 class="section-title">Already Marked Today - <?= htmlspecialchars($selected_class) ?></h3>
            <table class="already-marked-table">
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>Name</th>
                        <th>Status</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($already_marked as $student): ?>
                        <tr>
                            <td><?= htmlspecialchars($student['student_id']) ?></td>
                            <td><?= htmlspecialchars($student['display_name']) ?></td>
                            <td>
                                <span class="status-badge status-<?= strtolower($student['status']) ?>">
                                    <?php if ($student['status'] == 'Present'): ?>
                                        <i class="fas fa-check"></i> Present
                                    <?php elseif ($student['status'] == 'Absent'): ?>
                                        <i class="fas fa-times"></i> Absent
                                    <?php else: ?>
                                        <i class="fas fa-clock"></i> <?= htmlspecialchars($student['status']) ?>
                                    <?php endif; ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($student['notes'] ?? '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <?php if (!empty($students)): ?>
        <div class="attendance-section">
            <h3 class="section-title">Mark Attendance - <?= htmlspecialchars($selected_class) ?></h3>
            <form method="POST" action="">
                <input type="hidden" name="selected_class" value="<?= htmlspecialchars($selected_class) ?>">
                <table class="attendance-table">
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>Status</th>
                            <th>Comments (Optional)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td><?= htmlspecialchars($student['student_id']) ?></td>
                                <td><?= htmlspecialchars($student['display_name']) ?></td>
                                <td>
                                    <div class="attendance-status-group">
                                        <label class="status-button">
                                            <input type="radio" name="attendance[<?= htmlspecialchars($student['student_id']) ?>][status]" value="present" required>
                                            <span class="status-button-text present-text"><i class="fas fa-check"></i> Present</span>
                                        </label>
                                        <label class="status-button">
                                            <input type="radio" name="attendance[<?= htmlspecialchars($student['student_id']) ?>][status]" value="absent" required>
                                            <span class="status-button-text absent-text"><i class="fas fa-times"></i> Absent</span>
                                        </label>
                                    </div>
                                </td>
                                <td>
                                    <input type="text" name="attendance[<?= htmlspecialchars($student['student_id']) ?>][comments]" placeholder="e.g., Late 10min, Sick">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <button type="submit" name="submit_attendance" class="submit-all-btn">
                    <i class="fas fa-save"></i> Submit Attendance for <?= count($students) ?> Students
                </button>
            </form>
        </div>
    <?php elseif ($selected_class && empty($students) && empty($already_marked)): ?>
        <div style="text-align: center; margin-top: 2rem; padding: 2rem; background-color: #f8f9fa; border-radius: 8px;">
            <i class="fas fa-users" style="font-size: 3rem; color: #6c757d; margin-bottom: 1rem;"></i>
            <h3>No Students Found</h3>
            <p>No students found in <?= htmlspecialchars($selected_class) ?>.</p>
        </div>
    <?php elseif (!$selected_class): ?>
        <div style="text-align: center; margin-top: 2rem; padding: 2rem; background-color: #f8f9fa; border-radius: 8px;">
            <i class="fas fa-hand-pointer" style="font-size: 3rem; color: #6c757d; margin-bottom: 1rem;"></i>
            <h3>Select a Class</h3>
            <p>Please select a class from the dropdown above to view and mark attendance.</p>
        </div>
    <?php endif; ?>
</div>

<?php
// Include the new footer to close the page layout
$conn->close();
include 'footer.php';
?>