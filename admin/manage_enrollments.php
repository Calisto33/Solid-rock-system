<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

include '../config.php';

$message = "";
$message_type = "info";

// Handle enrollment creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create_enrollments_table') {
        // Create enrollments table if it doesn't exist
        $create_table_sql = "
            CREATE TABLE IF NOT EXISTS enrollments (
                enrollment_id INT PRIMARY KEY AUTO_INCREMENT,
                student_id INT NOT NULL,
                class_id INT NOT NULL,
                subject_id INT NOT NULL,
                enrollment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                status ENUM('active', 'inactive') DEFAULT 'active',
                UNIQUE KEY unique_enrollment (student_id, class_id, subject_id),
                FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
                FOREIGN KEY (class_id) REFERENCES classes(class_id) ON DELETE CASCADE,
                FOREIGN KEY (subject_id) REFERENCES subjects(subject_id) ON DELETE CASCADE
            )
        ";
        
        if ($conn->query($create_table_sql)) {
            $message = "Enrollments table created successfully.";
            $message_type = "success";
        } else {
            $message = "Error creating enrollments table: " . $conn->error;
            $message_type = "error";
        }
    } elseif ($_POST['action'] === 'auto_enroll') {
        $class_id = $_POST['class_id'];
        $subject_id = $_POST['subject_id'];
        
        // Get all students in the selected class
        $students_query = "SELECT student_id FROM students WHERE class_id = ?";
        $stmt = $conn->prepare($students_query);
        if ($stmt) {
            $stmt->bind_param("i", $class_id);
            $stmt->execute();
            $students_result = $stmt->get_result();
            
            $enrolled_count = 0;
            $error_count = 0;
            
            while ($student = $students_result->fetch_assoc()) {
                $enroll_query = "INSERT IGNORE INTO enrollments (student_id, class_id, subject_id) VALUES (?, ?, ?)";
                $stmt_enroll = $conn->prepare($enroll_query);
                if ($stmt_enroll) {
                    $stmt_enroll->bind_param("iii", $student['student_id'], $class_id, $subject_id);
                    if ($stmt_enroll->execute()) {
                        $enrolled_count++;
                    } else {
                        $error_count++;
                    }
                    $stmt_enroll->close();
                }
            }
            $stmt->close();
            
            $message = "Auto-enrollment completed: $enrolled_count students enrolled, $error_count errors.";
            $message_type = "success";
        }
    }
}

// Check if enrollments table exists
$table_exists = false;
$check_table = $conn->query("SHOW TABLES LIKE 'enrollments'");
if ($check_table && $check_table->num_rows > 0) {
    $table_exists = true;
}

// Get classes and subjects
$classes_query = "SELECT class_id, class_name FROM classes ORDER BY class_name";
$classes_result = $conn->query($classes_query);

$subjects_query = "SELECT subject_id, subject_name FROM subjects ORDER BY subject_name";
$subjects_result = $conn->query($subjects_query);

// Get current enrollments if table exists
$enrollments = [];
if ($table_exists) {
    $enrollments_query = "
        SELECT e.enrollment_id, s.username, c.class_name, sub.subject_name, e.enrollment_date
        FROM enrollments e
        JOIN students s ON e.student_id = s.student_id
        JOIN classes c ON e.class_id = c.class_id
        JOIN subjects sub ON e.subject_id = sub.subject_id
        ORDER BY c.class_name, sub.subject_name, s.username
    ";
    $enrollments_result = $conn->query($enrollments_query);
    if ($enrollments_result) {
        $enrollments = $enrollments_result->fetch_all(MYSQLI_ASSOC);
    }
}

$pageTitle = "Student Enrollment Manager";
include 'header.php';
?>

<style>
    .card { background-color: #fff; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); margin-bottom: 2rem; }
    .card-header { padding: 1.5rem 2rem; border-bottom: 1px solid #e0e0e0; }
    .card-title { font-size: 1.5rem; font-weight: 600; margin: 0; }
    .card-body { padding: 2rem; }
    .alert { padding: 1rem 1.5rem; border-radius: 8px; margin-bottom: 1rem; }
    .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .alert-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    .alert-info { background-color: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
    .alert-warning { background-color: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
    .btn { padding: 0.75rem 1.5rem; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; text-decoration: none; display: inline-block; }
    .btn-primary { background-color: #007bff; color: white; }
    .btn-success { background-color: #28a745; color: white; }
    .btn-warning { background-color: #ffc107; color: #212529; }
    .form-group { margin-bottom: 1rem; }
    .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; }
    .form-group select { width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px; }
    table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
    th, td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #dee2e6; }
    th { background-color: #f8f9fa; font-weight: 600; }
    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
    .stat-card { background: #f8f9fa; padding: 1rem; border-radius: 8px; text-align: center; }
    .stat-number { font-size: 2rem; font-weight: bold; color: #007bff; }
    .stat-label { color: #6c757d; }
</style>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Student Enrollment Manager</h2>
    </div>
    <div class="card-body">
        <?php if ($message): ?>
            <div class="alert alert-<?= $message_type ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if (!$table_exists): ?>
            <div class="alert alert-warning">
                <strong>Setup Required:</strong> The enrollments table doesn't exist yet. Click the button below to create it.
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="create_enrollments_table">
                <button type="submit" class="btn btn-primary">Create Enrollments Table</button>
            </form>
        <?php else: ?>
            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?= count($enrollments) ?></div>
                    <div class="stat-label">Total Enrollments</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $classes_result ? $classes_result->num_rows : 0 ?></div>
                    <div class="stat-label">Classes</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $subjects_result ? $subjects_result->num_rows : 0 ?></div>
                    <div class="stat-label">Subjects</div>
                </div>
            </div>

            <!-- Auto Enrollment Form -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Auto-Enroll Students</h3>
                </div>
                <div class="card-body">
                    <p>Automatically enroll all students in a class to a specific subject.</p>
                    <form method="POST">
                        <input type="hidden" name="action" value="auto_enroll">
                        <div class="form-group">
                            <label for="class_id">Select Class:</label>
                            <select name="class_id" id="class_id" required>
                                <option value="">-- Select Class --</option>
                                <?php 
                                $classes_result->data_seek(0); // Reset result pointer
                                while ($class = $classes_result->fetch_assoc()): ?>
                                    <option value="<?= $class['class_id'] ?>"><?= htmlspecialchars($class['class_name']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="subject_id">Select Subject:</label>
                            <select name="subject_id" id="subject_id" required>
                                <option value="">-- Select Subject --</option>
                                <?php 
                                $subjects_result->data_seek(0); // Reset result pointer
                                while ($subject = $subjects_result->fetch_assoc()): ?>
                                    <option value="<?= $subject['subject_id'] ?>"><?= htmlspecialchars($subject['subject_name']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-success">Auto-Enroll All Students</button>
                    </form>
                </div>
            </div>

            <!-- Current Enrollments -->
            <h3>Current Enrollments</h3>
            <?php if (empty($enrollments)): ?>
                <div class="alert alert-info">
                    No enrollments found. Use the auto-enroll feature above to get started.
                </div>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Class</th>
                                <th>Subject</th>
                                <th>Enrollment Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($enrollments as $enrollment): ?>
                                <tr>
                                    <td><?= htmlspecialchars($enrollment['username']) ?></td>
                                    <td><?= htmlspecialchars($enrollment['class_name']) ?></td>
                                    <td><?= htmlspecialchars($enrollment['subject_name']) ?></td>
                                    <td><?= htmlspecialchars($enrollment['enrollment_date']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php
include 'footer.php';
?>