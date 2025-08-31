<?php
// Page-specific security check
// Check if session is not already started before calling session_start()
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../config.php'; // Include config at the top

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['staff', 'admin'])) {
    header("Location: ../login.php");
    exit();
}

$teacher_id = $_SESSION['user_id']; 
$pageTitle = "Select Class for Mark Entry";
include 'header.php'; 

// Get teacher information
$teacherQuery = "SELECT first_name, last_name, username FROM users WHERE id = ?";
$teacherStmt = $conn->prepare($teacherQuery);
$teacherStmt->bind_param("i", $teacher_id);
$teacherStmt->execute();
$teacherResult = $teacherStmt->get_result();
$teacher = $teacherResult->fetch_assoc();
$teacherName = trim(($teacher['first_name'] ?? '') . ' ' . ($teacher['last_name'] ?? '')) ?: $teacher['username'];

// Get available classes directly from students table
$classesQuery = "SELECT DISTINCT class FROM students WHERE student_id LIKE 'STU%' AND class IS NOT NULL ORDER BY class";
$classesResult = $conn->query($classesQuery);

// Define subjects based on Zimbabwe curriculum - hardcoded for now
$subjects = [
    // O-Level Subjects (Forms 1-4)
    'Mathematics' => 'Mathematics',
    'English Language' => 'English Language',
    'Science' => 'General Science',
    'Geography' => 'Geography',
    'History' => 'History',
    'Shona' => 'Shona Language',
    'Physical Education' => 'Physical Education',
    'Art' => 'Visual Arts',
    
    // O-Level Specialized (Form 4)
    'Physics' => 'Physics',
    'Chemistry' => 'Chemistry', 
    'Biology' => 'Biology',
    'Accounts' => 'Principles of Accounts',
    'Business Studies' => 'Business Studies',
    'Computer Science' => 'Computer Science',
    
    // A-Level Subjects (Forms 5-6)
    'Pure Mathematics' => 'Pure Mathematics',
    'Applied Mathematics' => 'Applied Mathematics',
    'Economics' => 'Economics',
    'Literature' => 'English Literature'
];
?>

<style>
    :root {
        --primary-color: #2563eb;
        --primary-hover: #1d4ed8;
        --primary-light: #3b82f6;
        --background-color: #f1f5f9;
        --card-background: #ffffff;
        --text-color: #1e293b;
        --label-color: #475569;
        --border-color: #cbd5e1;
        --shadow-color: rgba(37, 99, 235, 0.08);
        --success-color: #0ea5e9;
        --blue-gradient: linear-gradient(135deg, #2563eb 0%, #3b82f6 50%, #0ea5e9 100%);
        --card-shadow: 0 10px 25px rgba(37, 99, 235, 0.1), 0 4px 10px rgba(37, 99, 235, 0.05);
    }
    
    body {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        background: transparent;
        color: var(--text-color);
        line-height: 1.6;
        padding: 1.5rem;
    }
    
    .teacher-info {
        background: var(--blue-gradient);
        color: white;
        padding: 2rem;
        border-radius: 16px;
        margin-bottom: 2rem;
        text-align: center;
        box-shadow: var(--card-shadow);
        position: relative;
        overflow: hidden;
    }
    
    .teacher-info::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 100%;
        height: 100%;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="25" cy="25" r="2" fill="rgba(255,255,255,0.1)"/><circle cx="75" cy="75" r="1.5" fill="rgba(255,255,255,0.08)"/><circle cx="50" cy="10" r="1" fill="rgba(255,255,255,0.12)"/></svg>');
        opacity: 0.3;
        transform: rotate(45deg);
    }
    
    .teacher-info h3 {
        margin: 0 0 0.5rem 0;
        font-size: 1.4rem;
        font-weight: 700;
        position: relative;
        z-index: 1;
    }
    
    .teacher-info p {
        margin: 0;
        opacity: 0.9;
        font-size: 1rem;
        position: relative;
        z-index: 1;
    }
    
    .teacher-info i {
        margin-right: 0.5rem;
        font-size: 1.2rem;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2.5rem;
    }
    
    .stat-card {
        background: var(--card-background);
        padding: 2rem 1.5rem;
        border-radius: 16px;
        box-shadow: var(--card-shadow);
        text-align: center;
        border: 1px solid rgba(37, 99, 235, 0.1);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    
    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: var(--blue-gradient);
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(37, 99, 235, 0.15), 0 6px 15px rgba(37, 99, 235, 0.1);
    }
    
    .stat-number {
        font-size: 2.5rem;
        font-weight: 800;
        background: var(--blue-gradient);
        background-clip: text;
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        margin-bottom: 0.5rem;
        line-height: 1;
    }
    
    .stat-label {
        color: var(--label-color);
        font-size: 0.95rem;
        font-weight: 500;
    }
    
    .card {
        background-color: var(--card-background);
        border: 1px solid rgba(37, 99, 235, 0.1);
        border-radius: 20px;
        box-shadow: var(--card-shadow);
        padding: 3rem 2.5rem;
        max-width: 800px;
        margin-left: auto;
        margin-right: auto;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    
    .card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 5px;
        background: var(--blue-gradient);
    }
    
    .page-title {
        font-size: 2rem;
        font-weight: 700;
        background: var(--blue-gradient);
        background-clip: text;
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        text-align: center;
        margin-bottom: 0.75rem;
    }
    
    .card > p {
        text-align: center;
        margin-bottom: 3rem;
        color: var(--label-color);
        font-size: 1.1rem;
    }
    
    .form-group {
        margin-bottom: 2rem;
    }
    
    label {
        display: block;
        font-weight: 600;
        margin-bottom: 0.75rem;
        color: var(--label-color);
        font-size: 1rem;
    }
    
    label i.fas {
        margin-right: 0.75rem;
        color: var(--primary-color);
        width: 20px;
        text-align: center;
    }
    
    input[type="text"],
    input[type="number"],
    select {
        width: 100%;
        padding: 1rem 1.25rem;
        border: 2px solid var(--border-color);
        border-radius: 12px;
        font-size: 1rem;
        background-color: var(--background-color);
        transition: all 0.3s ease;
        box-sizing: border-box;
        font-family: inherit;
    }
    
    input[type="text"]:focus,
    input[type="number"]:focus,
    select:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
        background-color: var(--card-background);
    }
    
    .submit-btn {
        width: 100%;
        background: var(--blue-gradient);
        color: white;
        padding: 1.25rem;
        border: none;
        border-radius: 12px;
        cursor: pointer;
        font-size: 1.1rem;
        font-weight: 700;
        margin-top: 1.5rem;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3);
        font-family: inherit;
    }
    
    .submit-btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(37, 99, 235, 0.4);
    }
    
    .submit-btn:active {
        transform: translateY(-1px);
    }
    
    .submit-btn i.fas {
        margin-right: 0.75rem;
        font-size: 1rem;
    }
    
    .subject-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 0.75rem;
        margin-top: 1.5rem;
        background: var(--background-color);
        padding: 1.5rem;
        border-radius: 16px;
        border: 1px solid rgba(37, 99, 235, 0.1);
    }
    
    .subject-tag {
        background: var(--blue-gradient);
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 25px;
        font-size: 0.85rem;
        font-weight: 600;
        text-align: center;
        box-shadow: 0 2px 8px rgba(37, 99, 235, 0.2);
        transition: all 0.3s ease;
    }
    
    .subject-tag:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
    }
    
    .note-box {
        background: rgba(37, 99, 235, 0.05);
        border: 1px solid rgba(37, 99, 235, 0.2);
        border-radius: 16px;
        padding: 1.5rem;
        margin-top: 2.5rem;
        color: var(--primary-color);
    }
    
    .note-box h4 {
        margin: 0 0 1rem 0;
        color: var(--primary-color);
        font-weight: 700;
        font-size: 1.1rem;
    }
    
    .note-box i {
        margin-right: 0.5rem;
    }
    
    /* Responsive Design */
    @media (max-width: 768px) {
        body {
            padding: 1rem;
        }
        
        .card {
            padding: 2rem 1.5rem;
            margin: 0;
            border-radius: 16px;
        }
        
        .page-title {
            font-size: 1.75rem;
        }
        
        .stats-grid {
            grid-template-columns: 1fr;
            gap: 1rem;
        }
        
        .stat-card {
            padding: 1.5rem;
        }
        
        .stat-number {
            font-size: 2rem;
        }
        
        .subject-grid {
            grid-template-columns: 1fr;
            padding: 1rem;
        }
        
        .teacher-info {
            padding: 1.5rem;
        }
        
        .teacher-info h3 {
            font-size: 1.2rem;
        }
        
        input[type="text"],
        input[type="number"],
        select {
            padding: 0.875rem 1rem;
        }
        
        .submit-btn {
            padding: 1rem;
            font-size: 1rem;
        }
    }
    
    @media (max-width: 480px) {
        body {
            padding: 0.75rem;
        }
        
        .card {
            padding: 1.5rem 1rem;
        }
        
        .page-title {
            font-size: 1.5rem;
        }
        
        .stats-grid {
            gap: 0.75rem;
        }
        
        .stat-card {
            padding: 1.25rem 1rem;
        }
        
        .teacher-info {
            padding: 1.25rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .note-box {
            padding: 1.25rem;
        }
    }
    
    /* Loading animation */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .card,
    .stat-card,
    .teacher-info {
        animation: fadeInUp 0.6s ease-out;
    }
    
    .stat-card:nth-child(2) {
        animation-delay: 0.1s;
    }
    
    .stat-card:nth-child(3) {
        animation-delay: 0.2s;
    }
</style>

<div class="teacher-info">
    <h3><i class="fas fa-chalkboard-teacher"></i> Welcome, <?= htmlspecialchars($teacherName) ?></h3>
    <p>Mark Entry Portal - Select class and subject to begin</p>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-number"><?= count($subjects) ?></div>
        <div class="stat-label">Available Subjects</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?= $classesResult ? $classesResult->num_rows : 0 ?></div>
        <div class="stat-label">Active Classes</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?= date('Y') ?></div>
        <div class="stat-label">Current Year</div>
    </div>
</div>

<div class="card">
    <h2 class="page-title">Mark Entry Portal</h2>
    <p>Select a class and subject to begin entering marks for your students.</p>

    <form action="enter_marks.php" method="POST">
        <div class="form-group">
            <label for="class_name"><i class="fas fa-users"></i> Select Class</label>
            <select name="class_name" id="class_name" required>
                <option value="">-- Choose a Class --</option>
                <?php 
                if ($classesResult && $classesResult->num_rows > 0) {
                    while ($class = $classesResult->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($class['class']) ?>">
                            <?= htmlspecialchars($class['class']) ?>
                        </option>
                <?php 
                    endwhile; 
                }
                ?>
            </select>
        </div>

        <div class="form-group">
            <label for="subject_name"><i class="fas fa-book"></i> Select Subject</label>
            <select name="subject_name" id="subject_name" required>
                <option value="">-- Choose a Subject --</option>
                <?php foreach ($subjects as $code => $name): ?>
                    <option value="<?= htmlspecialchars($code) ?>">
                        <?= htmlspecialchars($name) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="term"><i class="fas fa-calendar-alt"></i> Term</label>
            <select name="term" id="term" required>
                <option value="Term 1">Term 1</option>
                <option value="Term 2">Term 2</option>
                <option value="Term 3">Term 3</option>
            </select>
        </div>

        <div class="form-group">
            <label for="year"><i class="fas fa-calendar-day"></i> Academic Year</label>
            <input type="number" name="year" id="year" value="<?= date("Y") ?>" min="2020" max="2030" required>
        </div>

        <div class="form-group">
            <label for="assessment_type"><i class="fas fa-clipboard-check"></i> Assessment Type</label>
            <select name="assessment_type" id="assessment_type" required>
                <option value="Continuous Assessment">Continuous Assessment</option>
                <option value="Mid-Term Test">Mid-Term Test</option>
                <option value="End of Term Exam">End of Term Exam</option>
                <option value="Final Exam">Final Exam</option>
                <option value="Assignment">Assignment</option>
                <option value="Project">Project</option>
            </select>
        </div>

        <!-- Hidden field to pass teacher ID -->
        <input type="hidden" name="teacher_id" value="<?= $teacher_id ?>">

        <button type="submit" class="submit-btn">
            <i class="fas fa-arrow-right"></i> Load Student Mark Sheet
        </button>
    </form>

    <div class="note-box">
        <h4><i class="fas fa-info-circle"></i> Available Subjects</h4>
        <div class="subject-grid">
            <?php foreach ($subjects as $code => $name): ?>
                <div class="subject-tag"><?= htmlspecialchars($name) ?></div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add form validation and interactivity
    const form = document.querySelector('form');
    const submitBtn = document.querySelector('.submit-btn');
    
    // Add loading state to submit button
    form.addEventListener('submit', function() {
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
        submitBtn.disabled = true;
    });
    
    // Add smooth focus transitions
    const inputs = document.querySelectorAll('select, input');
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.style.transform = 'translateY(-2px)';
        });
        
        input.addEventListener('blur', function() {
            this.parentElement.style.transform = 'translateY(0)';
        });
    });
});
</script>

<?php
$teacherStmt->close();
$conn->close();
include 'footer.php';
?>