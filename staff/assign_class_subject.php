<?php
$pageTitle = "Assign Class & Subject";
include 'header.php';

$teacher_id_to_assign = $_SESSION['user_id'];

// Fetch all available subjects from database
$subjectsQuery = "SELECT subject_id, subject_name FROM subjects ORDER BY subject_name ASC";
$subjectsResult = $conn->query($subjectsQuery);

// Fetch all available classes from database  
$classesQuery = "SELECT class_id, class_name FROM classes ORDER BY class_name ASC";
$classesResult = $conn->query($classesQuery);

// Check if queries were successful
if (!$subjectsResult) {
    die("Error fetching subjects: " . $conn->error);
}

if (!$classesResult) {
    die("Error fetching classes: " . $conn->error);
}

// Handle form submission
$message = "";
$message_type = "success";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['subject_id']) && isset($_POST['class_id'])) {
    
    $subject_id = intval($_POST['subject_id']);
    $class_id = intval($_POST['class_id']);

    // Validate that both IDs are positive integers
    if ($subject_id > 0 && $class_id > 0) {
        // Check if assignment already exists
        $checkQuery = "SELECT assignment_id FROM teacher_subjects WHERE teacher_id = ? AND subject_id = ? AND class_id = ?";
        $stmt_check = $conn->prepare($checkQuery);
        
        if ($stmt_check) {
            $stmt_check->bind_param("iii", $teacher_id_to_assign, $subject_id, $class_id);
            $stmt_check->execute();
            $checkResult = $stmt_check->get_result();
            
            if ($checkResult->num_rows > 0) {
                $message = "This exact assignment (Teacher, Subject, Class) already exists.";
                $message_type = "error";
            } else {
                // Insert new assignment
                $assignQuery = "INSERT INTO teacher_subjects (teacher_id, subject_id, class_id) VALUES (?, ?, ?)";
                $stmt_assign = $conn->prepare($assignQuery);
                
                if (!$stmt_assign) {
                    $message = "Error preparing statement: " . $conn->error;
                    $message_type = "error";
                } else {
                    $stmt_assign->bind_param("iii", $teacher_id_to_assign, $subject_id, $class_id);

                    if ($stmt_assign->execute()) {
                        $message = "Class and subject assigned to your profile successfully.";
                    } else {
                        $message = "Error assigning: " . $stmt_assign->error;
                        $message_type = "error";
                    }
                    $stmt_assign->close();
                }
            }
            $stmt_check->close();
        } else {
            $message = "Error checking existing assignment: " . $conn->error;
            $message_type = "error";
        }
    } else {
        $message = "Please select both a valid class and subject.";
        $message_type = "error";
    }
}

// Fetch current teacher's assignments
$currentAssignmentsQuery = "
    SELECT ts.assignment_id, s.subject_name, c.class_name 
    FROM teacher_subjects ts
    JOIN subjects s ON ts.subject_id = s.subject_id
    JOIN classes c ON ts.class_id = c.class_id
    WHERE ts.teacher_id = ?
    ORDER BY c.class_name, s.subject_name
";
$stmt_current = $conn->prepare($currentAssignmentsQuery);
$stmt_current->bind_param("i", $teacher_id_to_assign);
$stmt_current->execute();
$currentAssignments = $stmt_current->get_result();
?>

<style>
    .card-header { background-color: var(--primary-light); color: var(--primary-dark); padding: 1.5rem; }
    .card-title { font-size: 1.5rem; font-weight: 600; margin: 0; display: flex; align-items: center; gap: 0.75rem; }
    .card-body { padding: 2rem; }
    .message { padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.8rem; }
    .success-message { background-color: #dcfce7; color: #16a34a; border-left: 4px solid #16a34a; }
    .error-message { background-color: #fee2e2; color: #ef4444; border-left: 4px solid #ef4444; }
    .form-container { display: flex; flex-direction: column; gap: 1.8rem; }
    .form-group { display: flex; flex-direction: column; gap: 0.6rem; }
    label { font-weight: 500; color: var(--text-light); }
    select { padding: 0.9rem 1rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 1rem; width: 100%; background-color: #f8fafc; transition: all 0.3s ease; }
    select:focus { outline: none; border-color: var(--primary-color); box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1); }
    button { background-color: var(--primary-color); color: var(--white); padding: 1rem; border: none; border-radius: 8px; cursor: pointer; font-size: 1rem; font-weight: 600; transition: all 0.3s ease; display: flex; justify-content: center; align-items: center; gap: 0.5rem; }
    button:hover { background-color: var(--primary-dark); transform: translateY(-2px); }
    .info-box { background-color: #f0f9ff; border: 1px solid #0284c7; border-radius: 8px; padding: 1rem; margin-bottom: 1rem; }
    .info-box p { margin: 0; color: #0284c7; font-size: 0.9rem; }
    .assignments-list { margin-top: 2rem; }
    .assignment-item { background: #f8fafc; padding: 1rem; border-radius: 8px; margin-bottom: 0.5rem; display: flex; justify-content: space-between; align-items: center; }
    .assignment-info { display: flex; align-items: center; gap: 1rem; }
    .assignment-badge { background: var(--primary-color); color: white; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.8rem; }
    .no-assignments { text-align: center; color: #64748b; padding: 2rem; background: #f8fafc; border-radius: 8px; }
</style>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-chalkboard-teacher"></i> Assign Class & Subject to Yourself</h3>
    </div>
    <div class="card-body">
        <div class="info-box">
            <p><i class="fas fa-info-circle"></i> 
                Available Classes: <?= $classesResult->num_rows ?> | 
                Available Subjects: <?= $subjectsResult->num_rows ?> |
                Your Current Assignments: <?= $currentAssignments->num_rows ?>
            </p>
        </div>

        <?php if ($message): ?>
            <div class="message <?= $message_type === 'success' ? 'success-message' : 'error-message' ?>">
                <i class="fas <?= $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form action="assign_class_subject.php" method="POST" class="form-container">
            
            <div class="form-group">
                <label for="class_id"><i class="fas fa-users"></i> Select Class</label>
                <select name="class_id" id="class_id" required>
                    <option value="">-- Select Class --</option>
                    <?php
                    if ($classesResult && $classesResult->num_rows > 0) {
                        while ($class = $classesResult->fetch_assoc()):
                    ?>
                        <option value="<?= htmlspecialchars($class['class_id']) ?>">
                            <?= htmlspecialchars($class['class_name']) ?>
                        </option>
                    <?php 
                        endwhile;
                    } else {
                        echo '<option value="">No classes available - Contact admin to add classes</option>';
                    }
                    ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="subject_id"><i class="fas fa-book"></i> Select Subject</label>
                <select name="subject_id" id="subject_id" required>
                    <option value="">-- Select Subject --</option>
                    <?php
                    if ($subjectsResult && $subjectsResult->num_rows > 0) {
                        while ($subject = $subjectsResult->fetch_assoc()):
                    ?>
                        <option value="<?= htmlspecialchars($subject['subject_id']) ?>">
                            <?= htmlspecialchars($subject['subject_name']) ?>
                        </option>
                    <?php 
                        endwhile; 
                    } else {
                        echo '<option value="">No subjects available - Contact admin to add subjects</option>';
                    }
                    ?>
                </select>
            </div>

            <button type="submit" <?= ($classesResult->num_rows == 0 || $subjectsResult->num_rows == 0) ? 'disabled' : '' ?>>
                <i class="fas fa-save"></i> Assign to My Profile
            </button>
        </form>

        <!-- Display Current Assignments -->
        <div class="assignments-list">
            <h4><i class="fas fa-list"></i> Your Current Assignments</h4>
            <?php if ($currentAssignments && $currentAssignments->num_rows > 0): ?>
                <?php while ($assignment = $currentAssignments->fetch_assoc()): ?>
                    <div class="assignment-item">
                        <div class="assignment-info">
                            <span class="assignment-badge"><?= htmlspecialchars($assignment['class_name']) ?></span>
                            <span><?= htmlspecialchars($assignment['subject_name']) ?></span>
                        </div>
                        <i class="fas fa-check-circle" style="color: #10b981;"></i>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-assignments">
                    <i class="fas fa-info-circle" style="font-size: 2rem; margin-bottom: 1rem; color: #64748b;"></i>
                    <p>You haven't assigned yourself to any classes or subjects yet.</p>
                    <p>Use the form above to select a class and subject.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$stmt_current->close();
$conn->close();
include 'footer.php';
?>