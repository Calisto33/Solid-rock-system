<?php
// session_start() must be the very first thing.
session_start();

// Define page-specific variables
$pageTitle = "Manage Students";
$currentPage = "students"; // This highlights the correct link in the sidebar

// Include database configuration
include '../config.php';

<<<<<<< HEAD
// Fetch all students - Fixed query to match actual database structure
$studentsQuery = "SELECT 
    student_id, 
    username, 
    first_name, 
    last_name, 
    course, 
    year, 
    class, 
    status,
    created_at 
FROM students 
WHERE student_id LIKE 'STU%'
ORDER BY created_at DESC";
=======
// Function to get formatted student name (consistent with other pages)
function getFormattedStudentName($first_name, $last_name, $username, $student_id) {
    $first_name = trim($first_name ?? '');
    $last_name = trim($last_name ?? '');
    $username = trim($username ?? '');
    
    // If we have both first and last name
    if (!empty($first_name) && !empty($last_name)) {
        return $first_name . ' ' . $last_name;
    }
    // If we have only first name
    elseif (!empty($first_name)) {
        return $first_name;
    }
    // If we have only last name
    elseif (!empty($last_name)) {
        return $last_name;
    }
    // If we have username
    elseif (!empty($username)) {
        return $username;
    }
    // Fallback to student ID
    else {
        return 'Student ' . $student_id;
    }
}

// Fetch all students - CORRECTED query to match actual database structure
$studentsQuery = "SELECT 
                    student_id, 
                    username, 
                    first_name, 
                    last_name, 
                    course, 
                    year, 
                    class, 
                    status,
                    date_of_birth,
                    phone,
                    address,
                    enrollment_date,
                    created_at,
                    updated_at,
                    email,
                    CASE 
                        WHEN TRIM(CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, ''))) != '' 
                        THEN TRIM(CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')))
                        WHEN username IS NOT NULL AND username != '' 
                        THEN username
                        ELSE CONCAT('Student ', student_id)
                    END AS student_name_computed
                  FROM students 
                  ORDER BY created_at DESC";
>>>>>>> b291daf7f49078bb0cccb1439969ad4a74e2db38

$studentsResult = $conn->query($studentsQuery);
if (!$studentsResult) {
    $fetch_error = "Error fetching students: " . $conn->error;
}

<<<<<<< HEAD
// Get statistics for the dashboard
$statsQuery = "SELECT 
    COUNT(*) as total_students,
    COUNT(CASE WHEN status = 'Active' THEN 1 END) as active_students,
    COUNT(CASE WHEN class LIKE 'Form 5%' OR class LIKE 'Form 6%' THEN 1 END) as a_level_students,
    COUNT(CASE WHEN class LIKE 'Form 1%' OR class LIKE 'Form 2%' OR class LIKE 'Form 3%' OR class LIKE 'Form 4%' THEN 1 END) as o_level_students
FROM students 
WHERE student_id LIKE 'STU%'";

$statsResult = $conn->query($statsQuery);
$stats = $statsResult ? $statsResult->fetch_assoc() : [
    'total_students' => 0,
    'active_students' => 0,
    'a_level_students' => 0,
    'o_level_students' => 0
];
=======
// Get statistics for dashboard cards
$stats = [];
$stats['total'] = 0;
$stats['active'] = 0;
$stats['pending'] = 0;
$stats['with_class'] = 0;

if ($studentsResult) {
    $students_data = $studentsResult->fetch_all(MYSQLI_ASSOC);
    $stats['total'] = count($students_data);
    
    foreach ($students_data as $student) {
        if ($student['status'] === 'Active' || (!empty($student['class']) && $student['class'] !== 'Unassigned')) {
            $stats['active']++;
        } else {
            $stats['pending']++;
        }
        
        if (!empty($student['class']) && $student['class'] !== 'Unassigned') {
            $stats['with_class']++;
        }
    }
    
    // Reset the result pointer for table display
    $studentsResult = $conn->query($studentsQuery);
}
>>>>>>> b291daf7f49078bb0cccb1439969ad4a74e2db38

// --- Now, include the main header and sidebar ---
include 'sa_header.php';
?>

<style>
/* Enhanced styling for the manage students page */
.content-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding: 0 0.5rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.content-header .title {
    font-size: 2rem;
    font-weight: 700;
    color: #2c3e50;
    margin: 0;
}

.stats-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1.5rem;
    border-radius: 12px;
    text-align: center;
<<<<<<< HEAD
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.2);
}

.stat-card h3 {
    font-size: 2rem;
    margin: 0;
    font-weight: 700;
}

.stat-card p {
    margin: 0.5rem 0 0 0;
    opacity: 0.9;
    font-size: 0.9rem;
=======
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    transition: transform 0.2s ease;
}

.stat-card:hover {
    transform: translateY(-3px);
}

.stat-number {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    display: block;
}

.stat-label {
    font-size: 0.9rem;
    opacity: 0.9;
>>>>>>> b291daf7f49078bb0cccb1439969ad4a74e2db38
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.card {
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0, 70, 150, 0.08);
    border: none;
    overflow: hidden;
}

.card-header {
<<<<<<< HEAD
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
=======
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    color: #2d3748;
>>>>>>> b291daf7f49078bb0cccb1439969ad4a74e2db38
    padding: 1.5rem 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
<<<<<<< HEAD
    border-bottom: 1px solid #eef2f7;
=======
    flex-wrap: wrap;
    gap: 1rem;
>>>>>>> b291daf7f49078bb0cccb1439969ad4a74e2db38
}

.card-title {
    font-size: 1.5rem;
    font-weight: 600;
    margin: 0;
<<<<<<< HEAD
    color: #2c3e50;
=======
    color: #2d3748;
>>>>>>> b291daf7f49078bb0cccb1439969ad4a74e2db38
}

.search-container {
    position: relative;
    width: 300px;
    min-width: 250px;
}

.search-icon {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #64748b;
    z-index: 1;
}

.search-input {
    width: 100%;
    padding: 12px 15px 12px 45px;
<<<<<<< HEAD
    border: 2px solid #e9ecef;
    border-radius: 25px;
    background: white;
=======
    border: 2px solid #e2e8f0;
    border-radius: 25px;
    background: #ffffff;
>>>>>>> b291daf7f49078bb0cccb1439969ad4a74e2db38
    font-size: 14px;
    transition: all 0.3s ease;
}

.search-input:focus {
    outline: none;
    border-color: #667eea;
<<<<<<< HEAD
=======
    background: #ffffff;
>>>>>>> b291daf7f49078bb0cccb1439969ad4a74e2db38
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

/* Ensure proper table layout and column widths */
.table-container {
    overflow-x: auto;
    padding: 0;
}

table {
    width: 100%;
    min-width: 1400px; /* Increased for better layout */
    border-collapse: collapse;
    font-size: 14px;
<<<<<<< HEAD
    table-layout: fixed; /* Fixed layout for consistent columns */
=======
    min-width: 1000px;
>>>>>>> b291daf7f49078bb0cccb1439969ad4a74e2db38
}

/* Define specific column widths */
table th:nth-child(1), table td:nth-child(1) { width: 110px; } /* Student ID */
table th:nth-child(2), table td:nth-child(2) { width: 200px; } /* Full Name */
table th:nth-child(3), table td:nth-child(3) { width: 180px; } /* Username */
table th:nth-child(4), table td:nth-child(4) { width: 150px; } /* Class - Increased width */
table th:nth-child(5), table td:nth-child(5) { width: 160px; } /* Combination */
table th:nth-child(6), table td:nth-child(6) { width: 140px; } /* Academic Year */
table th:nth-child(7), table td:nth-child(7) { width: 120px; } /* Status */
table th:nth-child(8), table td:nth-child(8) { width: 180px; } /* Actions */

thead th {
    background: #f1f5f9;
    color: #374151;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 12px;
    letter-spacing: 0.5px;
    padding: 20px;
    text-align: left;
    border-bottom: 2px solid #e5e7eb;
    position: sticky;
    top: 0;
    z-index: 10;
}

tbody td {
    padding: 18px 20px;
    border-bottom: 1px solid #f3f4f6;
    vertical-align: middle;
}

tbody tr {
    transition: background-color 0.2s ease;
}

tbody tr:hover {
    background-color: #f8faff;
}

.student-id-badge {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    color: white;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    display: inline-block;
    min-width: 80px;
    text-align: center;
}

<<<<<<< HEAD
/* Enhanced class badge styling with icons */
.class-badge-with-icon {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 8px 14px;
    border-radius: 25px;
    font-size: 12px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    white-space: nowrap;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    text-transform: none;
    border: 2px solid rgba(255, 255, 255, 0.2);
    transition: all 0.3s ease;
    min-width: 100px;
    justify-content: center;
}

.class-badge-with-icon:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(102, 126, 234, 0.4);
}

.class-badge-with-icon i {
    font-size: 11px;
    opacity: 0.9;
}

/* Different colors for different form levels */
.class-badge-with-icon.form-1 {
    background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
    box-shadow: 0 4px 12px rgba(255, 107, 107, 0.3);
}

.class-badge-with-icon.form-1:hover {
    box-shadow: 0 6px 18px rgba(255, 107, 107, 0.4);
}

.class-badge-with-icon.form-2 {
    background: linear-gradient(135deg, #4ecdc4 0%, #26d0ce 100%);
    box-shadow: 0 4px 12px rgba(78, 205, 196, 0.3);
}

.class-badge-with-icon.form-2:hover {
    box-shadow: 0 6px 18px rgba(78, 205, 196, 0.4);
}

.class-badge-with-icon.form-3 {
    background: linear-gradient(135deg, #45b7d1 0%, #2196f3 100%);
    box-shadow: 0 4px 12px rgba(69, 183, 209, 0.3);
}

.class-badge-with-icon.form-3:hover {
    box-shadow: 0 6px 18px rgba(69, 183, 209, 0.4);
}

.class-badge-with-icon.form-4 {
    background: linear-gradient(135deg, #f9ca24 0%, #f39c12 100%);
    color: #2c3e50;
    box-shadow: 0 4px 12px rgba(249, 202, 36, 0.3);
}

.class-badge-with-icon.form-4:hover {
    box-shadow: 0 6px 18px rgba(249, 202, 36, 0.4);
}

.class-badge-with-icon.form-5 {
    background: linear-gradient(135deg, #6c5ce7 0%, #5f27cd 100%);
    box-shadow: 0 4px 12px rgba(108, 92, 231, 0.3);
}

.class-badge-with-icon.form-5:hover {
    box-shadow: 0 6px 18px rgba(108, 92, 231, 0.4);
}

.class-badge-with-icon.form-6 {
    background: linear-gradient(135deg, #2d3436 0%, #636e72 100%);
    box-shadow: 0 4px 12px rgba(45, 52, 54, 0.3);
}

.class-badge-with-icon.form-6:hover {
    box-shadow: 0 6px 18px rgba(45, 52, 54, 0.4);
}

/* Improved course tag styling */
.course-tag {
    background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
    color: #8b4513;
    padding: 6px 12px;
    border-radius: 15px;
    font-size: 11px;
    font-weight: 600;
    display: inline-block;
    text-align: center;
    min-width: 100px;
    border: 1px solid rgba(139, 69, 19, 0.2);
    box-shadow: 0 2px 6px rgba(252, 182, 159, 0.3);
}

/* Responsive adjustments */
@media (max-width: 1200px) {
    table {
        min-width: 1200px;
    }
    
    table th:nth-child(4), table td:nth-child(4) { 
        width: 130px; 
    }
    
    .class-badge-with-icon {
        padding: 6px 10px;
        font-size: 11px;
        min-width: 90px;
    }
}

@media (max-width: 768px) {
    table {
        min-width: 1000px;
    }
    
    table th:nth-child(4), table td:nth-child(4) { 
        width: 120px; 
    }
    
    .class-badge-with-icon {
        padding: 5px 8px;
        font-size: 10px;
        min-width: 80px;
    }
    
    .class-badge-with-icon i {
        font-size: 9px;
    }
=======
.class-badge {
    background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
    color: #2d3748;
    padding: 6px 12px;
    border-radius: 15px;
    font-size: 12px;
    font-weight: 600;
    display: inline-block;
    min-width: 60px;
    text-align: center;
}

.course-badge {
    background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
    color: #744210;
    padding: 6px 12px;
    border-radius: 15px;
    font-size: 12px;
    font-weight: 600;
    display: inline-block;
    text-align: center;
>>>>>>> b291daf7f49078bb0cccb1439969ad4a74e2db38
}

.status-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    color: #fff;
    min-width: 70px;
    justify-content: center;
}

.status-active { 
<<<<<<< HEAD
    background: linear-gradient(45deg, #2ed573, #219d55); 
}

.status-inactive { 
    background: linear-gradient(45deg, #ff4757, #c0392b); 
}

.status-graduated { 
    background: linear-gradient(45deg, #3742fa, #2f3542); 
}

.status-transferred { 
    background: linear-gradient(45deg, #ffa726, #ff7043); 
=======
    background: linear-gradient(45deg, #10b981, #059669); 
}

.status-pending { 
    background: linear-gradient(45deg, #f59e0b, #d97706); 
}

.status-inactive { 
    background: linear-gradient(45deg, #ef4444, #dc2626); 
>>>>>>> b291daf7f49078bb0cccb1439969ad4a74e2db38
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    text-decoration: none;
    font-weight: 600;
    font-size: 13px;
    transition: all 0.2s ease;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.btn-success {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
}

.btn-success:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
}

.btn-secondary {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
    box-shadow: 0 4px 15px rgba(245, 87, 108, 0.3);
}

.btn-secondary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(245, 87, 108, 0.4);
}

.btn-sm {
    padding: 6px 12px;
    font-size: 12px;
}

.action-buttons {
    display: flex;
    gap: 8px;
}

.notification {
    padding: 15px 20px;
    margin-bottom: 20px;
    border-radius: 8px;
    font-weight: 500;
}

.notification.error {
    background-color: #fef2f2;
    color: #dc2626;
    border-left: 4px solid #dc2626;
}

.notification.success {
    background-color: #f0fdf4;
    color: #16a34a;
    border-left: 4px solid #16a34a;
}

.empty-state {
    text-align: center;
    padding: 3rem;
    color: #6b7280;
    font-size: 1.1em;
}

.empty-state i {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
    color: #d1d5db;
}

.unassigned-text {
    color: #f59e0b;
    font-style: italic;
    font-weight: 500;
}

.contact-info {
    font-size: 12px;
    color: #6b7280;
    margin-top: 2px;
}

@media (max-width: 768px) {
    .content-header {
        flex-direction: column;
        align-items: stretch;
        text-align: center;
    }
    
    .stats-container {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .card-header {
        flex-direction: column;
        align-items: stretch;
        text-align: center;
        gap: 1.5rem;
    }
    
    .search-container {
        width: 100%;
    }
    
    table {
        min-width: 800px;
    }
    
    .content-header .title {
        font-size: 1.5rem;
    }
}

@media (max-width: 480px) {
    .stats-container {
        grid-template-columns: 1fr;
    }
    
    tbody td {
        padding: 12px 8px;
        font-size: 13px;
    }
    
    thead th {
        padding: 15px 8px;
        font-size: 11px;
    }
}

.table-footer {
    padding: 15px 20px;
    background-color: #f8f9fa;
    border-top: 1px solid #eef2f7;
    text-align: right;
    color: #666;
    font-size: 14px;
}
</style>

<div class="content-header">
    <h1 class="title"><?= htmlspecialchars($pageTitle); ?></h1>
<<<<<<< HEAD
    <a href="add_student.php" class="btn btn-primary">
=======
    <a href="add_student.php" class="btn btn-success">
>>>>>>> b291daf7f49078bb0cccb1439969ad4a74e2db38
        <i class="fas fa-plus"></i> Add New Student
    </a>
</div>

<!-- Statistics Cards -->
<div class="stats-container">
    <div class="stat-card">
<<<<<<< HEAD
        <h3><?= $stats['total_students'] ?></h3>
        <p>Total Students</p>
    </div>
    <div class="stat-card">
        <h3><?= $stats['active_students'] ?></h3>
        <p>Active Students</p>
    </div>
    <div class="stat-card">
        <h3><?= $stats['o_level_students'] ?></h3>
        <p>O-Level Students</p>
    </div>
    <div class="stat-card">
        <h3><?= $stats['a_level_students'] ?></h3>
        <p>A-Level Students</p>
=======
        <span class="stat-number"><?= $stats['total'] ?></span>
        <div class="stat-label">Total Students</div>
    </div>
    <div class="stat-card">
        <span class="stat-number"><?= $stats['active'] ?></span>
        <div class="stat-label">Active Students</div>
    </div>
    <div class="stat-card">
        <span class="stat-number"><?= $stats['pending'] ?></span>
        <div class="stat-label">Pending Setup</div>
    </div>
    <div class="stat-card">
        <span class="stat-number"><?= $stats['with_class'] ?></span>
        <div class="stat-label">Assigned to Class</div>
>>>>>>> b291daf7f49078bb0cccb1439969ad4a74e2db38
    </div>
</div>

<?php if (isset($fetch_error)): ?>
    <div class="notification error">
        <strong>Error!</strong> <?= htmlspecialchars($fetch_error); ?>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['message'])): ?>
    <div class="notification success">
        <strong>Success!</strong> <?= htmlspecialchars($_SESSION['message']); ?>
    </div>
    <?php unset($_SESSION['message']); ?>
<?php endif; ?>

<div class="card">
    <div class="card-header">
<<<<<<< HEAD
        <h2 class="card-title">All Students (<?= $stats['total_students'] ?>)</h2>
=======
        <h2 class="card-title">
            <i class="fas fa-users"></i>
            All Students (<?= $stats['total'] ?>)
        </h2>
>>>>>>> b291daf7f49078bb0cccb1439969ad4a74e2db38
        <div class="search-container">
            <i class="fas fa-search search-icon"></i>
            <input type="text" id="searchInput" class="search-input" placeholder="Search by name, ID, or class...">
        </div>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
<<<<<<< HEAD
                    <th>Student ID</th>
                    <th>Full Name</th>
                    <th>Username</th>
                    <th>Class</th>
                    <th>Combination</th>
                    <th>Academic Year</th>
                    <th>Status</th>
                    <th>Actions</th>
=======
                    <th><i class="fas fa-id-card"></i> Student ID</th>
                    <th><i class="fas fa-user"></i> Username</th>
                    <th><i class="fas fa-user-graduate"></i> Full Name</th>
                    <th><i class="fas fa-users"></i> Class</th>
                    <th><i class="fas fa-book"></i> Course/Combination</th>
                    <th><i class="fas fa-calendar"></i> Year</th>
                    <th><i class="fas fa-info-circle"></i> Status</th>
                    <th><i class="fas fa-calendar-plus"></i> Enrolled</th>
                    <th><i class="fas fa-cogs"></i> Actions</th>
>>>>>>> b291daf7f49078bb0cccb1439969ad4a74e2db38
                </tr>
            </thead>
            <tbody id="studentTableBody">
                <?php if ($studentsResult && $studentsResult->num_rows > 0): ?>
                    <?php while ($student = $studentsResult->fetch_assoc()): ?>
                        <?php
<<<<<<< HEAD
                            $fullName = trim(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? ''));
                            $status = $student['status'] ?? 'Active';
                            $statusClass = 'status-' . strtolower(str_replace(' ', '-', $status));
                        ?>
                        <tr>
                            <td>
                                <span class="badge"><?= htmlspecialchars($student['student_id']) ?></span>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($fullName ?: 'N/A') ?></strong>
                            </td>
                            <td><?= htmlspecialchars($student['username'] ?? 'N/A') ?></td>
                            <td>
                                <?php if (!empty($student['class']) && $student['class'] !== 'Unassigned'): ?>
                                    <?php
                                        // Determine form level for styling
                                        $formLevel = '';
                                        if (strpos($student['class'], 'Form 1') !== false) $formLevel = 'form-1';
                                        elseif (strpos($student['class'], 'Form 2') !== false) $formLevel = 'form-2';
                                        elseif (strpos($student['class'], 'Form 3') !== false) $formLevel = 'form-3';
                                        elseif (strpos($student['class'], 'Form 4') !== false) $formLevel = 'form-4';
                                        elseif (strpos($student['class'], 'Form 5') !== false) $formLevel = 'form-5';
                                        elseif (strpos($student['class'], 'Form 6') !== false) $formLevel = 'form-6';
                                        
                                        // Add icon based on form level
                                        $icon = '';
                                        if (strpos($student['class'], 'Form 1') !== false || strpos($student['class'], 'Form 2') !== false) {
                                            $icon = '<i class="fas fa-seedling"></i>'; // Early years
                                        } elseif (strpos($student['class'], 'Form 3') !== false || strpos($student['class'], 'Form 4') !== false) {
                                            $icon = '<i class="fas fa-book"></i>'; // O-Level
                                        } elseif (strpos($student['class'], 'Form 5') !== false || strpos($student['class'], 'Form 6') !== false) {
                                            $icon = '<i class="fas fa-graduation-cap"></i>'; // A-Level
                                        }
                                    ?>
                                    <span class="class-badge-with-icon <?= $formLevel ?>">
                                        <?= $icon ?>
                                        <?= htmlspecialchars($student['class']) ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: #ffa726; font-style: italic; font-weight: 500;">
                                        <i class="fas fa-exclamation-triangle"></i> Unassigned
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($student['course']) && $student['course'] !== 'Unassigned'): ?>
                                    <span class="course-tag"><?= htmlspecialchars($student['course']) ?></span>
                                <?php else: ?>
                                    <span style="color: #ffa726; font-style: italic;">Unassigned</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($student['year'] ?? 'N/A') ?></td>
                            <td>
                                <span class="status-pill <?= $statusClass ?>">
                                    <i class="fas <?= $status === 'Active' ? 'fa-check-circle' : ($status === 'Inactive' ? 'fa-times-circle' : 'fa-graduation-cap') ?>"></i>
                                    <?= htmlspecialchars($status) ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="edit_student.php?student_id=<?= urlencode($student['student_id']) ?>" 
                                       class="btn btn-primary btn-sm" title="Edit Student">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="student_profile.php?student_id=<?= urlencode($student['student_id']) ?>" 
                                       class="btn btn-secondary btn-sm" title="View Profile">
                                        <i class="fas fa-user"></i> Profile
=======
                            $student_display_name = $student['student_name_computed'];
                            $has_class = !empty($student['class']) && $student['class'] !== 'Unassigned';
                            $has_course = !empty($student['course']) && $student['course'] !== 'Unassigned';
                            $status = $student['status'] ?? ($has_class ? 'Active' : 'Pending');
                        ?>
                        <tr>
                            <td>
                                <span class="student-id-badge"><?= htmlspecialchars($student['student_id']) ?></span>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($student['username'] ?? 'N/A') ?></strong>
                                <?php if (!empty($student['email'])): ?>
                                    <div class="contact-info">
                                        <i class="fas fa-envelope"></i> <?= htmlspecialchars($student['email']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($student_display_name) ?></strong>
                                <?php if (!empty($student['phone'])): ?>
                                    <div class="contact-info">
                                        <i class="fas fa-phone"></i> <?= htmlspecialchars($student['phone']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($has_class): ?>
                                    <span class="class-badge"><?= htmlspecialchars($student['class']) ?></span>
                                <?php else: ?>
                                    <span class="unassigned-text">Unassigned</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($has_course): ?>
                                    <span class="course-badge"><?= htmlspecialchars($student['course']) ?></span>
                                <?php else: ?>
                                    <span class="unassigned-text">Unassigned</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($student['year'] ?? 'N/A') ?></strong>
                            </td>
                            <td>
                                <?php if ($status === 'Active' || $has_class): ?>
                                    <span class="status-pill status-active">
                                        <i class="fas fa-check-circle"></i> Active
                                    </span>
                                <?php elseif ($status === 'Inactive'): ?>
                                    <span class="status-pill status-inactive">
                                        <i class="fas fa-times-circle"></i> Inactive
                                    </span>
                                <?php else: ?>
                                    <span class="status-pill status-pending">
                                        <i class="fas fa-clock"></i> Pending
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($student['enrollment_date'])): ?>
                                    <?= date('M j, Y', strtotime($student['enrollment_date'])) ?>
                                <?php elseif (!empty($student['created_at'])): ?>
                                    <?= date('M j, Y', strtotime($student['created_at'])) ?>
                                <?php else: ?>
                                    <span class="unassigned-text">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                                    <a href="edit_student.php?student_id=<?= urlencode($student['student_id']) ?>" 
                                       class="btn btn-primary btn-sm" 
                                       title="Edit Student">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="view_results.php?student_id=<?= urlencode($student['student_id']) ?>" 
                                       class="btn btn-success btn-sm" 
                                       title="View Results">
                                        <i class="fas fa-chart-bar"></i> Results
>>>>>>> b291daf7f49078bb0cccb1439969ad4a74e2db38
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php elseif (!isset($fetch_error)): ?>
                    <tr>
                        <td colspan="9" class="empty-state">
                            <i class="fas fa-users"></i><br>
                            <h3>No Student Records Found</h3>
                            <p>Start by adding your first student to the system.</p>
                            <a href="add_student.php" class="btn btn-success" style="margin-top: 1rem;">
                                <i class="fas fa-plus"></i> Add First Student
                            </a>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <?php if ($studentsResult && $studentsResult->num_rows > 0): ?>
        <div class="table-footer">
            <p>Showing <?= $studentsResult->num_rows ?> student<?= $studentsResult->num_rows !== 1 ? 's' : '' ?></p>
        </div>
    <?php endif; ?>
</div>

<?php
// Finally, include the footer.
include 'sa_footer.php';
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Enhanced search functionality for the students table
    const searchInput = document.getElementById('searchInput');
    const tableBody = document.getElementById('studentTableBody');
    const tableRows = Array.from(tableBody.getElementsByTagName('tr'));

    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const searchTerm = searchInput.value.toLowerCase().trim();
<<<<<<< HEAD
            let visibleCount = 0;
=======
            let visibleRows = 0;
>>>>>>> b291daf7f49078bb0cccb1439969ad4a74e2db38

            tableRows.forEach(function(row) {
                // Skip the "no records" row
                if (row.children.length === 1 && row.children[0].colSpan > 1) {
                    return;
                }

<<<<<<< HEAD
                const rowText = tableRows[i].textContent.toLowerCase();
                if (searchTerm === '' || rowText.includes(searchTerm)) {
                    tableRows[i].style.display = '';
                    visibleCount++;
=======
                const rowText = row.textContent.toLowerCase();
                const shouldShow = searchTerm === '' || rowText.includes(searchTerm);
                
                row.style.display = shouldShow ? '' : 'none';
                if (shouldShow) visibleRows++;
            });

            // Update the card title with filtered count
            const cardTitle = document.querySelector('.card-title');
            if (cardTitle) {
                const totalStudents = <?= $stats['total'] ?>;
                if (searchTerm === '') {
                    cardTitle.innerHTML = '<i class="fas fa-users"></i> All Students (' + totalStudents + ')';
>>>>>>> b291daf7f49078bb0cccb1439969ad4a74e2db38
                } else {
                    cardTitle.innerHTML = '<i class="fas fa-search"></i> Search Results (' + visibleRows + ' of ' + totalStudents + ')';
                }
            }
            
            // Update the footer count
            const footerElement = document.querySelector('.table-footer p');
            if (footerElement && searchTerm !== '') {
                footerElement.textContent = `Showing ${visibleCount} of ${tableRows.length} student${tableRows.length !== 1 ? 's' : ''}`;
            } else if (footerElement) {
                footerElement.textContent = `Showing ${tableRows.length} student${tableRows.length !== 1 ? 's' : ''}`;
            }
        });

        // Clear search on escape key
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                searchInput.value = '';
                searchInput.dispatchEvent(new Event('keyup'));
                searchInput.blur();
            }
        });
    }

    // Add keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + K to focus search
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            searchInput.focus();
        }
    });

    // Highlight search terms
    function highlightSearchTerm(element, term) {
        if (!term) return;
        
        const walker = document.createTreeWalker(
            element,
            NodeFilter.SHOW_TEXT,
            null,
            false
        );

        const textNodes = [];
        let node;
        while (node = walker.nextNode()) {
            textNodes.push(node);
        }

        textNodes.forEach(textNode => {
            const parent = textNode.parentNode;
            if (parent.tagName === 'SCRIPT' || parent.tagName === 'STYLE') return;
            
            const text = textNode.textContent;
            const regex = new RegExp(`(${term})`, 'gi');
            
            if (regex.test(text)) {
                const highlighted = text.replace(regex, '<mark>$1</mark>');
                const wrapper = document.createElement('span');
                wrapper.innerHTML = highlighted;
                parent.replaceChild(wrapper, textNode);
            }
        });
    }
});
</script>