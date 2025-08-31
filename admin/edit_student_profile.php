<?php
session_start();
include '../config.php';

// Check if the user is an admin or staff
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'staff')) {
    header("Location: ../login.php");
    exit();
}

// Check if id is provided in the URL (using integer id from students table)
if (isset($_GET['id'])) {
    $student_id = intval($_GET['id']);
} else {
    die("Error: Student ID is missing.");
}

// Handle form submission
$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $year = trim($_POST['year']);
    $course = trim($_POST['course']);
    $class = trim($_POST['class']);

    // Update the student's information in the students table
    $updateQuery = "UPDATE students SET first_name = ?, last_name = ?, year = ?, course = ?, class = ? WHERE id = ?";
    $stmt = $conn->prepare($updateQuery);
    
    if ($stmt === false) {
        $message = "Error preparing update statement: " . $conn->error;
    } else {
        $stmt->bind_param("sssssi", $first_name, $last_name, $year, $course, $class, $student_id);

        if ($stmt->execute()) {
            $message = "Student information updated successfully!";
        } else {
            $message = "Error updating student information: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Fetch student details from the students table
$studentQuery = "SELECT id, student_id, username, first_name, last_name, year, course, class FROM students WHERE id = ?";
$stmt = $conn->prepare($studentQuery);
if ($stmt === false) {
    die("Error preparing statement: " . $conn->error);
}
$stmt->bind_param("i", $student_id);
$stmt->execute();
$studentResult = $stmt->get_result();
$student = $studentResult->fetch_assoc();
$stmt->close();

if (!$student) {
    die("Error: Student not found.");
}

// Close database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student Information | Admin Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --secondary: #4b5563;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --info: #3b82f6;
            --light: #f3f4f6;
            --dark: #1f2937;
            --body-bg: #f9fafb;
            --card-bg: #ffffff;
            --text-primary: #111827;
            --text-secondary: #4b5563;
            --border-color: #e5e7eb;
            --radius: 0.5rem;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--body-bg);
            color: var(--text-primary);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Header Styles */
        .header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 1.25rem;
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .header h2 {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
        }

        .header h2 i {
            margin-right: 0.75rem;
        }

        .header-actions {
            display: flex;
            gap: 0.75rem;
        }

        /* Main Content */
        .main {
            flex: 1;
            max-width: 800px;
            width: 100%;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }

        /* Breadcrumb */
        .breadcrumb {
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        .breadcrumb a {
            color: var(--primary);
            text-decoration: none;
            transition: var(--transition);
        }

        .breadcrumb a:hover {
            color: var(--primary-dark);
        }

        .breadcrumb-separator {
            color: var(--text-secondary);
        }

        /* Card Styles */
        .card {
            background-color: var(--card-bg);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            transition: var(--transition);
        }

        .card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-2px);
        }

        .card-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--primary);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .card-body {
            padding: 2rem;
        }

        /* Student Info Display */
        .student-info {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 1.5rem;
            border-radius: var(--radius);
            margin-bottom: 2rem;
            text-align: center;
        }

        .student-info h3 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .student-info p {
            font-size: 1rem;
            opacity: 0.9;
        }

        /* Form Styles */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            background-color: white;
            transition: var(--transition);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.2);
        }

        .form-select {
            width: 100%;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            background-color: white;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 0.75rem center;
            background-repeat: no-repeat;
            background-size: 1rem;
            appearance: none;
            transition: var(--transition);
        }

        .form-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.2);
        }

        /* Button Styles */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
            font-weight: 500;
            border: none;
            border-radius: var(--radius);
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            gap: 0.5rem;
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
        }

        .btn-secondary {
            background-color: var(--secondary);
            color: white;
        }

        .btn-secondary:hover {
            background-color: #374151;
        }

        .btn-lg {
            padding: 0.875rem 1.75rem;
        }

        .btn-block {
            width: 100%;
        }

        /* Alert Messages */
        .alert {
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            border-radius: var(--radius);
            display: flex;
            align-items: center;
            border-left: 4px solid transparent;
        }

        .alert-icon {
            margin-right: 1rem;
            font-size: 1.25rem;
        }

        .alert-success {
            background-color: rgba(16, 185, 129, 0.1);
            border-left-color: var(--success);
            color: #065f46;
        }

        .alert-danger {
            background-color: rgba(239, 68, 68, 0.1);
            border-left-color: var(--danger);
            color: #b91c1c;
        }

        /* Footer Styles */
        .footer {
            background-color: var(--dark);
            color: white;
            padding: 1.5rem;
            text-align: center;
            margin-top: auto;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .footer-text {
            font-size: 0.95rem;
        }

        .footer-links {
            display: flex;
            gap: 1.5rem;
        }

        .footer-link {
            color: #d1d5db;
            text-decoration: none;
            transition: var(--transition);
        }

        .footer-link:hover {
            color: white;
        }

        /* Responsive Styles */
        @media (max-width: 768px) {
            .header-content, .footer-content {
                flex-direction: column;
                text-align: center;
            }

            .card-body {
                padding: 1.5rem;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 576px) {
            .main {
                padding: 0 1rem;
                margin: 1rem auto;
            }

            .card-header {
                padding: 1.25rem 1.5rem;
            }

            .card-body {
                padding: 1.25rem;
            }

            .btn {
                padding: 0.625rem 1.25rem;
            }
        }

        /* Animation */
        @keyframes fadeIn {
            0% { opacity: 0; transform: translateY(10px); }
            100% { opacity: 1; transform: translateY(0); }
        }

        .card {
            animation: fadeIn 0.5s ease-out;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <h2><i class="fas fa-user-edit"></i> Edit Student Information</h2>
            <div class="header-actions">
                <a href="update_student_profile.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Students
                </a>
            </div>
        </div>
    </header>

    <main class="main">
        <div class="breadcrumb">
            <a href="dashboard.php">Dashboard</a>
            <span class="breadcrumb-separator">›</span>
            <a href="update_student_profile.php">Students</a>
            <span class="breadcrumb-separator">›</span>
            <span>Edit Student</span>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-user"></i> Student Information
                </h3>
            </div>
            <div class="card-body">
                <!-- Student Info Display -->
                <div class="student-info">
                    <h3><?= htmlspecialchars($student['first_name'] ?? $student['username']) ?> <?= htmlspecialchars($student['last_name'] ?? '') ?></h3>
                    <p>Student ID: <?= htmlspecialchars($student['student_id']) ?></p>
                </div>

                <?php if ($message): ?>
                    <div class="alert <?= strpos($message, 'Error') !== false ? 'alert-danger' : 'alert-success' ?>">
                        <i class="alert-icon <?= strpos($message, 'Error') !== false ? 'fas fa-exclamation-circle' : 'fas fa-check-circle' ?>"></i>
                        <?= $message ?>
                    </div>
                <?php endif; ?>

                <form action="edit_student_profile.php?id=<?= $student_id ?>" method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label" for="first_name">First Name</label>
                            <input type="text" id="first_name" name="first_name" class="form-control" 
                                   value="<?= htmlspecialchars($student['first_name'] ?? '') ?>" 
                                   placeholder="Enter first name" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="last_name">Last Name</label>
                            <input type="text" id="last_name" name="last_name" class="form-control" 
                                   value="<?= htmlspecialchars($student['last_name'] ?? '') ?>" 
                                   placeholder="Enter last name" required>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label" for="year">Year Enrolled</label>
                            <select id="year" name="year" class="form-select" required>
                                <option value="">-- Select Year --</option>
                                <?php 
                                $currentYear = date('Y');
                                for ($i = $currentYear - 5; $i <= $currentYear + 1; $i++): 
                                ?>
                                    <option value="<?= $i ?>" <?= ($student['year'] == $i) ? 'selected' : '' ?>>
                                        <?= $i ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="course">Combination (Course)</label>
                            <select id="course" name="course" class="form-select" required>
                                <option value="">-- Select Combination --</option>
                                <option value="Science" <?= ($student['course'] == 'Science') ? 'selected' : '' ?>>Science</option>
                                <option value="Arts" <?= ($student['course'] == 'Arts') ? 'selected' : '' ?>>Arts</option>
                                <option value="Commercial" <?= ($student['course'] == 'Commercial') ? 'selected' : '' ?>>Commercial</option>
                                <option value="Technical" <?= ($student['course'] == 'Technical') ? 'selected' : '' ?>>Technical</option>
                                <option value="Mathematics" <?= ($student['course'] == 'Mathematics') ? 'selected' : '' ?>>Mathematics</option>
                                <option value="Geography" <?= ($student['course'] == 'Geography') ? 'selected' : '' ?>>Geography</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="class">Class</label>
                        <select id="class" name="class" class="form-select" required>
                            <option value="">-- Select Class --</option>
                            <option value="Form 1" <?= ($student['class'] == 'Form 1') ? 'selected' : '' ?>>Form 1</option>
                            <option value="Form 2" <?= ($student['class'] == 'Form 2') ? 'selected' : '' ?>>Form 2</option>
                            <option value="Form 3" <?= ($student['class'] == 'Form 3') ? 'selected' : '' ?>>Form 3</option>
                            <option value="Form 4" <?= ($student['class'] == 'Form 4') ? 'selected' : '' ?>>Form 4</option>
                            <option value="Form 5" <?= ($student['class'] == 'Form 5') ? 'selected' : '' ?>>Form 5</option>
                            <option value="Form 6" <?= ($student['class'] == 'Form 6') ? 'selected' : '' ?>>Form 6</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block btn-lg">
                        <i class="fas fa-save"></i> Update Student Information
                    </button>
                </form>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="footer-content">
            <p class="footer-text">&copy; <?php echo date("Y"); ?> Wisetech College Portal | All Rights Reserved</p>
            <div class="footer-links">
                <a href="#" class="footer-link">Privacy Policy</a>
                <a href="#" class="footer-link">Terms of Service</a>
                <a href="#" class="footer-link">Help Center</a>
            </div>
        </div>
    </footer>
</body>
</html>