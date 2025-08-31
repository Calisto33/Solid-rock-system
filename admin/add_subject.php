<?php
session_start();
include '../config.php'; // Database connection

// Verify user role
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

// Function to add a subject - CORRECTED to use 'subjects' table
function addSubject($conn, $subject_name) {
    // Insert into 'subjects' table (not 'table_subject')
    $query = "INSERT INTO subjects (subject_name) VALUES (?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $subject_name);
    return $stmt->execute();
}

// Function to add a class - NEW function for classes
function addClass($conn, $class_name) {
    $query = "INSERT INTO classes (class_name) VALUES (?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $class_name);
    return $stmt->execute();
}

// Process form submission
$message = "";
$action = $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($action == 'add_subject') {
        $subject_name = trim($_POST['subject_name']);
        
        if (!empty($subject_name)) {
            if (addSubject($conn, $subject_name)) {
                $message = "Subject '$subject_name' added successfully.";
            } else {
                $message = "Error adding subject. Please try again.";
            }
        } else {
            $message = "Subject name cannot be empty.";
        }
    } elseif ($action == 'add_class') {
        $class_name = trim($_POST['class_name']);
        
        if (!empty($class_name)) {
            if (addClass($conn, $class_name)) {
                $message = "Class '$class_name' added successfully.";
            } else {
                $message = "Error adding class. Please try again.";
            }
        } else {
            $message = "Class name cannot be empty.";
        }
    }
}

// Fetch existing subjects and classes for display
$subjectsQuery = "SELECT subject_id, subject_name FROM subjects ORDER BY subject_name ASC";
$subjectsResult = $conn->query($subjectsQuery);

$classesQuery = "SELECT class_id, class_name FROM classes ORDER BY class_name ASC";
$classesResult = $conn->query($classesQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Subjects & Classes | Admin Portal</title>
    <!-- Favicon - matching main site -->
    <link rel="icon" type="image/ico" href="images/favicon.ico">
    <link rel="shortcut icon" type="image/jpeg" href="images/logo.jpeg">
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

        .main {
            flex: 1;
            max-width: 1200px;
            width: 100%;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }

        .row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

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
            background-color: var(--light);
            transition: var(--transition);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.2);
        }

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

        .btn-block {
            width: 100%;
        }

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

        .list-section {
            grid-column: 1 / -1;
        }

        .items-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .item-card {
            background: var(--light);
            padding: 1rem;
            border-radius: var(--radius);
            border: 1px solid var(--border-color);
            text-align: center;
            transition: var(--transition);
        }

        .item-card:hover {
            background: #e2e8f0;
            transform: translateY(-1px);
        }

        .item-name {
            font-weight: 500;
            color: var(--text-primary);
        }

        .item-id {
            font-size: 0.85rem;
            color: var(--text-secondary);
            margin-top: 0.25rem;
        }

        @media (max-width: 768px) {
            .row {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
        }

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
            <h2><i class="fas fa-school"></i> Admin Portal - Manage Subjects & Classes</h2>
            <div class="header-actions">
                <a href="admin_home.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </header>

    <main class="main">
        <?php if (isset($message) && !empty($message)): ?>
            <div class="alert <?= strpos($message, 'Error') !== false ? 'alert-danger' : 'alert-success' ?>">
                <i class="alert-icon <?= strpos($message, 'Error') !== false ? 'fas fa-exclamation-circle' : 'fas fa-check-circle' ?>"></i>
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Add Subject Form -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-book"></i> Add New Subject
                    </h3>
                </div>
                <div class="card-body">
                    <form action="" method="POST">
                        <input type="hidden" name="action" value="add_subject">
                        <div class="form-group">
                            <label class="form-label" for="subject_name">Subject Name</label>
                            <input type="text" id="subject_name" name="subject_name" class="form-control" placeholder="Enter subject name (e.g., Mathematics, English)" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-plus-circle"></i> Add Subject
                        </button>
                    </form>
                </div>
            </div>

            <!-- Add Class Form -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-users"></i> Add New Class
                    </h3>
                </div>
                <div class="card-body">
                    <form action="" method="POST">
                        <input type="hidden" name="action" value="add_class">
                        <div class="form-group">
                            <label class="form-label" for="class_name">Class Name</label>
                            <input type="text" id="class_name" name="class_name" class="form-control" placeholder="Enter class name (e.g., Form 1, Grade 10)" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-plus-circle"></i> Add Class
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Display Current Subjects and Classes -->
        <div class="row">
            <div class="card list-section">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-list"></i> Current Subjects & Classes
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div style="margin-bottom: 2rem;">
                            <h4 style="color: var(--primary); margin-bottom: 1rem;">
                                <i class="fas fa-book"></i> Subjects (<?= $subjectsResult ? $subjectsResult->num_rows : 0 ?>)
                            </h4>
                            <div class="items-grid">
                                <?php if ($subjectsResult && $subjectsResult->num_rows > 0): ?>
                                    <?php while ($subject = $subjectsResult->fetch_assoc()): ?>
                                        <div class="item-card">
                                            <div class="item-name"><?= htmlspecialchars($subject['subject_name']) ?></div>
                                            <div class="item-id">ID: <?= $subject['subject_id'] ?></div>
                                        </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <div class="item-card" style="grid-column: 1 / -1; color: var(--text-secondary);">
                                        No subjects added yet
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div>
                            <h4 style="color: var(--primary); margin-bottom: 1rem;">
                                <i class="fas fa-users"></i> Classes (<?= $classesResult ? $classesResult->num_rows : 0 ?>)
                            </h4>
                            <div class="items-grid">
                                <?php if ($classesResult && $classesResult->num_rows > 0): ?>
                                    <?php while ($class = $classesResult->fetch_assoc()): ?>
                                        <div class="item-card">
                                            <div class="item-name"><?= htmlspecialchars($class['class_name']) ?></div>
                                            <div class="item-id">ID: <?= $class['class_id'] ?></div>
                                        </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <div class="item-card" style="grid-column: 1 / -1; color: var(--text-secondary);">
                                        No classes added yet
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>