<?php
session_start();

$pageTitle = "View Assignments";
include '../config.php';

// Security check for student role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Retrieve student's data for sidebar and assignments
$stmt = $conn->prepare("SELECT student_id, class, first_name, last_name, username FROM students WHERE user_id = ?");
if ($stmt === false) {
    die("Error preparing student query: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$studentData = $result->fetch_assoc();
$stmt->close();

if (!$studentData) {
    die("Error: Student data not found for user ID: " . $user_id);
}

$student_class = $studentData['class'];
$student_id = $studentData['student_id'];
$fullName = trim(($studentData['first_name'] ?? '') . ' ' . ($studentData['last_name'] ?? ''));

// Query for assignments
$assignmentsQuery = "
    SELECT
        assignment_id, 
        title,
        description, 
        due_date
    FROM assignments
    ORDER BY due_date DESC
";

$stmt = $conn->prepare($assignmentsQuery);
if ($stmt === false) {
    die("Error preparing assignments query: " . $conn->error);
}

$stmt->execute();
$assignmentsResult = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Solid Rock  </title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --bg-color: #f4f7fe;
            --sidebar-bg: #ffffff;
            --card-bg: #ffffff;
            --primary-text: #27272a;
            --secondary-text: #6b7280;
            --accent-purple: #7c3aed;
            --accent-blue: #3b82f6;
            --border-color: #eef2f9;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --rounded-lg: 0.75rem;
            --rounded-xl: 1rem;
            --shadow: 0 4px 15px -3px rgba(0, 0, 0, 0.05);
            --shadow-lg: 0 10px 25px -5px rgba(0, 0, 0, 0.07), 0 5px 10px -5px rgba(0, 0, 0, 0.04);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
        }

        body {
            background-color: var(--bg-color);
            color: var(--primary-text);
            display: flex;
            min-height: 100vh;
        }

        .page-layout {
            display: flex;
            width: 100%;
        }

        /* --- Sidebar Navigation (Matches student_home.php) --- */
        .sidebar {
            width: 260px;
            background-color: var(--sidebar-bg);
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            border-right: 1px solid var(--border-color);
            flex-shrink: 0;
        }
        
        .sidebar-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 2.5rem;
        }

        .sidebar-header img {
            height: 40px;
            width: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .sidebar-header .user-info h4 {
            font-size: 1rem;
            font-weight: 600;
        }
        
        .sidebar-header .user-info span {
            font-size: 0.875rem;
            color: var(--secondary-text);
        }

        .sidebar nav a {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.8rem 1rem;
            border-radius: var(--rounded-lg);
            text-decoration: none;
            color: var(--secondary-text);
            font-weight: 500;
            transition: var(--transition);
            margin-bottom: 0.5rem;
        }

        .sidebar nav a.active, .sidebar nav a:hover {
            background-color: var(--accent-purple);
            color: #ffffff;
            transform: translateX(5px);
            box-shadow: 0 4px 10px -2px rgba(124, 58, 237, 0.4);
        }

        .sidebar nav a i {
            width: 20px;
            text-align: center;
        }

        .sidebar .logout-btn {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.8rem 1rem;
            margin-top: auto;
            border-radius: var(--rounded-lg);
            background-color: #f3f4f6;
            color: var(--secondary-text);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
        }
        .sidebar .logout-btn:hover {
            background-color: #e94560;
            color: #fff;
        }

        /* --- Main Content --- */
        .main-content {
            flex: 1;
            padding: 2rem;
            overflow-y: auto;
        }

        .main-header {
            margin-bottom: 2rem;
        }
        
        .main-header h1 {
            font-size: 1.75rem;
            font-weight: 700;
        }

        .main-header p {
            color: var(--secondary-text);
            margin-top: 0.25rem;
        }
        
        .card {
            background-color: var(--card-bg);
            border-radius: var(--rounded-xl);
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
            overflow: hidden;
        }

        .assignments-table {
            width: 100%;
            border-collapse: collapse;
        }

        .assignments-table th {
            background-color: #f9fafb;
            color: var(--secondary-text);
            font-weight: 600;
            text-align: left;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
        }

        .assignments-table td {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
        }
        
        .assignments-table tbody tr:last-child td {
            border-bottom: none;
        }

        .assignments-table tbody tr:hover {
            background-color: #f9fafb;
        }

        .assignment-title {
            font-weight: 600;
            color: var(--primary-text);
            font-size: 1rem;
            margin-bottom: 0.25rem;
        }

        .assignment-description {
            color: var(--secondary-text);
            font-size: 0.9rem;
            max-width: 400px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: var(--rounded-lg);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.875rem;
            transition: var(--transition);
            border: none;
        }

        .btn-primary {
            background-color: var(--accent-blue);
            color: white;
        }

        .btn-primary:hover {
            background-color: #2563eb;
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .due-date {
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--secondary-text);
        }

        .due-date.overdue {
            color: var(--danger-color);
        }

        .due-date.due-soon {
            color: var(--warning-color);
        }

        .no-assignments {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--secondary-text);
        }

        .no-assignments i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        @media (max-width: 992px) {
            .page-layout { flex-direction: column; }
            .sidebar {
                width: 100%;
                height: auto;
                flex-direction: row;
                justify-content: space-around;
                align-items: center;
                border-right: none;
                border-bottom: 1px solid var(--border-color);
                padding: 0.5rem 1rem;
            }
            .sidebar-header, .sidebar .logout-btn { display: none; }
            .sidebar nav { display: flex; gap: 0.5rem; }
            .sidebar nav a span { display: none; }
        }

        @media (max-width: 768px) {
             .main-content { padding: 1rem; }
             .assignments-table { display: block; overflow-x: auto; }
        }
    </style>
</head>
<body>
    <div class="page-layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="../images/logo.jpeg" alt="User Avatar">
                <div class="user-info">
                    <h4>Solid Rock  </h4>
                    <span>Student</span>
                </div>
            </div>
            <nav>
                <a href="student_home.php"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a>
                <a href="student_profile.php"><i class="fas fa-user"></i><span>My Profile</span></a>
                <a href="view_assignment.php" class="active"><i class="fas fa-tasks"></i><span>Assignments</span></a>
                <a href="student_results.php"><i class="fas fa-graduation-cap"></i><span>My Results</span></a>
                <a href="view_resources.php"><i class="fas fa-folder-open"></i><span>Resources</span></a>
            </nav>
            <a href="../logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="main-header">
                <h1>Your Assignments</h1>
                <p>View and submit your assignments here.</p>
            </header>

            <div class="card">
                <table class="assignments-table">
                    <thead>
                        <tr>
                            <th>Assignment</th>
                            <th>Due Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($assignmentsResult->num_rows > 0): ?>
                            <?php while ($assignment = $assignmentsResult->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div class="assignment-title">
                                            <?= htmlspecialchars($assignment['title'] ?: 'Assignment #' . $assignment['assignment_id']) ?>
                                        </div>
                                        <div class="assignment-description">
                                            <?= htmlspecialchars($assignment['description'] ?: 'No description provided') ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if (!empty($assignment['due_date'])): ?>
                                            <?php 
                                            $due_date = strtotime($assignment['due_date']);
                                            $now = time();
                                            $days_until_due = ($due_date - $now) / (60 * 60 * 24);
                                            
                                            $due_class = '';
                                            if ($days_until_due < 0) {
                                                $due_class = 'overdue';
                                            } elseif ($days_until_due <= 3) {
                                                $due_class = 'due-soon';
                                            }
                                            ?>
                                            <div class="due-date <?= $due_class ?>">
                                                <?= date("M d, Y", $due_date) ?>
                                            </div>
                                        <?php else: ?>
                                            <span>-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="submit_assignments.php?id=<?= $assignment['assignment_id'] ?>" class="btn btn-primary">
                                            <i class="fas fa-paper-plane"></i> Submit
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="no-assignments">
                                    <i class="fas fa-check-circle"></i>
                                    <h3>All Caught Up!</h3>
                                    <p>You have no pending assignments.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>
<?php
$conn->close();
?>
