<?php
session_start();
include '../config.php';

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

// Get parent_id from URL
$parent_id = isset($_GET['parent_id']) ? intval($_GET['parent_id']) : 0;

if ($parent_id <= 0) {
    header("Location: parent_management.php");
    exit();
}

// Fetch parent information
$parentQuery = "
    SELECT p.parent_id, u.username AS parent_name, p.phone_number, p.relationship, p.address, p.student_id
    FROM parents p
    JOIN users u ON p.user_id = u.id
    WHERE p.parent_id = ?";

$parentStmt = $conn->prepare($parentQuery);
$parentStmt->bind_param("i", $parent_id);
$parentStmt->execute();
$parentResult = $parentStmt->get_result();

if ($parentResult->num_rows == 0) {
    header("Location: parent_management.php");
    exit();
}

$parent = $parentResult->fetch_assoc();

// Fetch all available students
$studentsQuery = "
    SELECT s.id, s.student_id, u.username AS student_name, u.email, s.created_at
    FROM students s
    JOIN users u ON s.user_id = u.id
    ORDER BY u.username";

$studentsResult = $conn->query($studentsQuery);

// Handle form submission
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Debug: Check what's being submitted (remove this in production)
    error_log("POST data: " . print_r($_POST, true));
    
    // Check if student_id is set and not empty
    if (!isset($_POST['student_id']) || empty($_POST['student_id'])) {
        $message = "Please select a student to assign.";
        $messageType = "error";
    } else {
        $selected_student_id = intval($_POST['student_id']); // Now this will be the integer ID
        
        if ($selected_student_id <= 0) {
            $message = "Please select a valid student.";
            $messageType = "error";
        } else {
            // Check if the student exists using the integer ID
            $checkStudentQuery = "SELECT id FROM students WHERE id = ?";
            $checkStmt = $conn->prepare($checkStudentQuery);
            $checkStmt->bind_param("i", $selected_student_id); // Use "i" for integer
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            
            if ($checkResult->num_rows > 0) {
                // Update the parent's student assignment
                $updateQuery = "UPDATE parents SET student_id = ? WHERE parent_id = ?";
                $updateStmt = $conn->prepare($updateQuery);
                $updateStmt->bind_param("ii", $selected_student_id, $parent_id); // Both integers now
                
                if ($updateStmt->execute()) {
                    $message = "Student successfully assigned to parent!";
                    $messageType = "success";
                    
                    // Update the parent info to reflect the change
                    $parent['student_id'] = $selected_student_id;
                } else {
                    $message = "Error assigning student: " . $conn->error;
                    $messageType = "error";
                }
                $updateStmt->close();
            } else {
                $message = "Selected student does not exist.";
                $messageType = "error";
            }
            $checkStmt->close();
        }
    }
}

// Get currently assigned student if any
$currentStudent = null;
if ($parent['student_id'] && $parent['student_id'] != 0) {
    $currentStudentQuery = "
        SELECT s.id, s.student_id, u.username AS student_name
        FROM students s
        JOIN users u ON s.user_id = u.id
        WHERE s.id = ?";
    
    $currentStmt = $conn->prepare($currentStudentQuery);
    $currentStmt->bind_param("i", $parent['student_id']); // Use "i" for integer
    $currentStmt->execute();
    $currentResult = $currentStmt->get_result();
    
    if ($currentResult->num_rows > 0) {
        $currentStudent = $currentResult->fetch_assoc();
    }
    $currentStmt->close();
}

// Reset students result for the form
$studentsResult = $conn->query($studentsQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Student to Parent</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4361ee;
            --primary-light: #4895ef;
            --primary-dark: #3a0ca3;
            --accent-color: #f72585;
            --success-color: #06d6a0;
            --error-color: #ef476f;
            --warning-color: #ff9800;
            --text-dark: #2b2d42;
            --text-light: #8d99ae;
            --bg-light: #f8f9fa;
            --white: #ffffff;
            --card-shadow: 0 4px 20px rgba(138, 149, 158, 0.15);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: var(--bg-light);
            color: var(--text-dark);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: var(--white);
            padding: 1.5rem 2rem;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            display: flex;
            align-items: center;
        }

        .logo i {
            margin-right: 0.75rem;
            font-size: 1.75rem;
        }

        .nav-links {
            display: flex;
            gap: 1.5rem;
        }

        .nav-btn {
            color: var(--white);
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 500;
            transition: var(--transition);
            display: flex;
            align-items: center;
            border: 1px solid transparent;
        }

        .nav-btn i {
            margin-right: 0.5rem;
        }

        .nav-btn:hover {
            background-color: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.3);
        }

        .container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 2rem;
            flex: 1;
        }

        .breadcrumb {
            margin-bottom: 2rem;
            color: var(--text-light);
            font-size: 0.9rem;
        }

        .breadcrumb a {
            color: var(--primary-color);
            text-decoration: none;
            transition: var(--transition);
        }

        .breadcrumb a:hover {
            color: var(--primary-dark);
        }

        .page-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 600;
            color: var(--primary-dark);
            margin-bottom: 0.5rem;
        }

        .page-subtitle {
            color: var(--text-light);
            font-size: 1.1rem;
        }

        .alert {
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            border-radius: 8px;
            border: 1px solid transparent;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-success {
            background-color: rgba(6, 214, 160, 0.1);
            border-color: var(--success-color);
            color: #00695c;
        }

        .alert-error {
            background-color: rgba(239, 71, 111, 0.1);
            border-color: var(--error-color);
            color: #c62828;
        }

        .alert-warning {
            background-color: rgba(255, 152, 0, 0.1);
            border-color: var(--warning-color);
            color: #e65100;
        }

        .card {
            background: var(--white);
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .card-header {
            padding: 1.5rem 2rem;
            background: linear-gradient(to right, var(--primary-light), var(--primary-color));
            color: var(--white);
        }

        .card-header h3 {
            font-size: 1.3rem;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .card-body {
            padding: 2rem;
        }

        .parent-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .info-item {
            display: flex;
            flex-direction: column;
        }

        .info-label {
            font-weight: 600;
            color: var(--text-light);
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
        }

        .info-value {
            font-size: 1rem;
            color: var(--text-dark);
            font-weight: 500;
        }

        .current-assignment {
            background: rgba(6, 214, 160, 0.05);
            border: 1px solid rgba(6, 214, 160, 0.2);
            border-radius: 8px;
            padding: 1rem 1.5rem;
            margin-bottom: 2rem;
        }

        .no-assignment {
            background: rgba(255, 152, 0, 0.05);
            border: 1px solid rgba(255, 152, 0, 0.2);
            border-radius: 8px;
            padding: 1rem 1.5rem;
            margin-bottom: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.75rem;
            font-size: 0.95rem;
        }

        .form-control {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            font-size: 1rem;
            transition: var(--transition);
            background-color: var(--white);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-light);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }

        .student-option {
            padding: 0.75rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            transition: var(--transition);
        }

        .student-option:hover {
            background-color: rgba(67, 97, 238, 0.05);
        }

        .student-option:last-child {
            border-bottom: none;
        }

        .student-option.selected {
            background-color: rgba(67, 97, 238, 0.1);
            border-color: var(--primary-light);
        }

        .student-name {
            font-weight: 500;
            color: var(--text-dark);
        }

        .student-email {
            font-size: 0.85rem;
            color: var(--text-light);
        }

        .btn {
            padding: 0.875rem 2rem;
            border: none;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-size: 0.95rem;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: var(--white);
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(67, 97, 238, 0.3);
        }

        .btn-secondary {
            background-color: var(--text-light);
            color: var(--white);
        }

        .btn-secondary:hover {
            background-color: #6c757d;
        }

        .btn-outline {
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            background-color: transparent;
        }

        .btn-outline:hover {
            background-color: var(--primary-color);
            color: var(--white);
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid rgba(0, 0, 0, 0.1);
        }

        .search-box {
            position: relative;
            margin-bottom: 1rem;
        }

        .search-box input {
            padding-left: 2.5rem;
        }

        .search-box i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
        }

        .student-list {
            max-height: 300px;
            overflow-y: auto;
            border: 2px solid rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }

        .relationship-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-parent {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary-color);
        }

        .badge-guardian {
            background-color: rgba(72, 149, 239, 0.1);
            color: var(--primary-light);
        }

        .badge-other {
            background-color: rgba(141, 153, 174, 0.1);
            color: var(--text-light);
        }

        .radio-input {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        @media screen and (max-width: 768px) {
            .container {
                padding: 0 1rem;
            }

            .parent-info {
                grid-template-columns: 1fr;
            }

            .form-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="nav-container">
            <div class="logo">
                <i class="fas fa-graduation-cap"></i>
                <span>Wisetech College</span>
            </div>
            <div class="nav-links">
                <a href="admin_home.php" class="nav-btn">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="parent_management.php" class="nav-btn">
                    <i class="fas fa-users"></i> Parents
                </a>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="breadcrumb">
            <a href="admin_home.php">Dashboard</a> / 
            <a href="parent_management.php">Parent Management</a> / 
            Assign Student
        </div>

        <div class="page-header">
            <h1 class="page-title">Assign Student to Parent</h1>
            <p class="page-subtitle">Link a student to their parent or guardian</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?>">
                <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : ($messageType === 'error' ? 'exclamation-circle' : 'info-circle') ?>"></i>
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- Parent Information Card -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-user"></i> Parent Information</h3>
            </div>
            <div class="card-body">
                <div class="parent-info">
                    <div class="info-item">
                        <span class="info-label">Parent Name</span>
                        <span class="info-value"><?= htmlspecialchars($parent['parent_name']) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Phone Number</span>
                        <span class="info-value"><?= htmlspecialchars($parent['phone_number']) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Relationship</span>
                        <span class="info-value">
                            <?php
                            $relationship = htmlspecialchars($parent['relationship']);
                            $badgeClass = 'badge-other';
                            
                            if (strcasecmp($relationship, 'parent') == 0) {
                                $badgeClass = 'badge-parent';
                            } elseif (strcasecmp($relationship, 'guardian') == 0) {
                                $badgeClass = 'badge-guardian';
                            }
                            ?>
                            <span class="relationship-badge <?= $badgeClass ?>"><?= $relationship ?></span>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Address</span>
                        <span class="info-value"><?= htmlspecialchars($parent['address']) ?></span>
                    </div>
                </div>

                <!-- Current Assignment Status -->
                <?php if ($currentStudent): ?>
                    <div class="current-assignment">
                        <h4 style="margin: 0 0 0.5rem 0; display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-check-circle" style="color: var(--success-color);"></i>
                            Currently Assigned Student
                        </h4>
                        <p style="margin: 0; font-weight: 500;"><?= htmlspecialchars($currentStudent['student_name']) ?></p>
                        <p style="margin: 0.25rem 0 0 0; font-size: 0.85rem; color: var(--text-light);">
                            You can reassign this parent to a different student using the form below.
                        </p>
                    </div>
                <?php else: ?>
                    <div class="no-assignment">
                        <h4 style="margin: 0 0 0.5rem 0; display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-exclamation-triangle" style="color: var(--warning-color);"></i>
                            No Student Assigned
                        </h4>
                        <p style="margin: 0;">This parent is not currently assigned to any student.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Assignment Form -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-user-plus"></i> <?= $currentStudent ? 'Reassign to Different Student' : 'Assign Student' ?></h3>
            </div>
            <div class="card-body">
                <form method="POST" action="" id="assignmentForm">
                    <div class="form-group">
                        <label for="student_search" class="form-label">Search Students</label>
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" id="student_search" placeholder="Type to search students..." class="form-control">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="student_id" class="form-label">Select Student *</label>
                        <div class="student-list" id="student_list">
                            <?php while ($student = $studentsResult->fetch_assoc()): ?>
                                <div class="student-option" data-student-id="<?= htmlspecialchars($student['id']) ?>">
                                    <div>
                                        <div class="student-name"><?= htmlspecialchars($student['student_name']) ?> (<?= htmlspecialchars($student['student_id']) ?>)</div>
                                        <div class="student-email"><?= htmlspecialchars($student['email']) ?></div>
                                    </div>
                                    <input type="radio" name="student_id" value="<?= $student['id'] ?>" 
                                           class="radio-input"
                                           <?= ($currentStudent && $currentStudent['id'] == $student['id']) ? 'checked' : '' ?>>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="parent_management.php" class="btn btn-outline">
                            <i class="fas fa-arrow-left"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> 
                            <?= $currentStudent ? 'Update Assignment' : 'Assign Student' ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Search functionality
            document.getElementById('student_search').addEventListener('keyup', function() {
                const searchTerm = this.value.toLowerCase();
                const studentOptions = document.querySelectorAll('.student-option');
                
                studentOptions.forEach(option => {
                    const studentName = option.querySelector('.student-name').textContent.toLowerCase();
                    const studentEmail = option.querySelector('.student-email').textContent.toLowerCase();
                    
                    if (studentName.includes(searchTerm) || studentEmail.includes(searchTerm)) {
                        option.style.display = 'flex';
                    } else {
                        option.style.display = 'none';
                    }
                });
            });

            // Make entire student option clickable
            document.querySelectorAll('.student-option').forEach(option => {
                option.addEventListener('click', function() {
                    const radio = this.querySelector('input[type="radio"]');
                    radio.checked = true;
                    
                    // Remove selected class from all options
                    document.querySelectorAll('.student-option').forEach(opt => {
                        opt.classList.remove('selected');
                    });
                    
                    // Add selected class to clicked option
                    this.classList.add('selected');
                });
            });

            // Set initial selected state
            const checkedRadio = document.querySelector('input[name="student_id"]:checked');
            if (checkedRadio) {
                checkedRadio.closest('.student-option').classList.add('selected');
            }

            // Form validation
            document.getElementById('assignmentForm').addEventListener('submit', function(e) {
                const selectedStudent = document.querySelector('input[name="student_id"]:checked');
                
                if (!selectedStudent) {
                    e.preventDefault();
                    alert('Please select a student to assign to this parent.');
                    return false;
                }
                
                // Confirm assignment
                const studentName = selectedStudent.closest('.student-option').querySelector('.student-name').textContent;
                const parentName = '<?= htmlspecialchars($parent['parent_name']) ?>';
                
                if (!confirm(`Are you sure you want to assign "${studentName}" to parent "${parentName}"?`)) {
                    e.preventDefault();
                    return false;
                }
                
                return true;
            });
        });
    </script>
</body>
</html>

<?php
// Close all prepared statements and connection
$parentStmt->close();
$conn->close();
?>