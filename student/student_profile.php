<?php
// STEP 1: Start the session at the VERY TOP of the file.
session_start();

// STEP 2: Check if the user is logged in AND has the 'student' role.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    // Redirect to the login page if not a logged-in student.
    header("Location: ../login.php");
    exit();
}

// STEP 3: Get the logged-in student's ID from the session.
$loggedInUserId = $_SESSION['user_id'];

// Include the database connection
include '../config.php';

// --- DATABASE QUERIES ---

// 1. FETCH LOGGED-IN STUDENT'S BASIC INFO (FIXED: removed phone_number)
$stmt = $conn->prepare("SELECT student_id, username, class, year, first_name, last_name, email FROM students WHERE user_id = ?");
$stmt->bind_param("i", $loggedInUserId);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

if (!$student) {
    $stmt->close();
    die("Error: Could not find a profile for the logged-in user.");
}
$stmt->close();

$actualStudentId = $student['student_id'];
$fullName = trim(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? ''));

// 2. FETCH AVERAGE PASS RATE AND COMPLETION PERCENTAGE
$stmt_avg = $conn->prepare("SELECT AVG(final_mark) as average_grade FROM results WHERE student_id = ?");
$stmt_avg->bind_param("s", $actualStudentId);
$stmt_avg->execute();
$result_avg = $stmt_avg->get_result();
$avg_data = $result_avg->fetch_assoc();
$average_pass_rate = round($avg_data['average_grade'] ?? 0, 0);
$stmt_avg->close();
// Example completion logic
$profile_completion = 40; // Base percentage
if (!empty($fullName)) $profile_completion += 15;
if (!empty($student['email'])) $profile_completion += 15;
if ($average_pass_rate > 0) $profile_completion += 30; // Increased weight for results

// 3. FETCH RECENT ACADEMIC PERFORMANCE (LAST 5 RESULTS)
$stmt_results = $conn->prepare("SELECT subject, final_mark, grade FROM results WHERE student_id = ? ORDER BY exam_date DESC LIMIT 5");
$stmt_results->bind_param("s", $actualStudentId);
$stmt_results->execute();
$academic_results = $stmt_results->get_result();
$stmt_results->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - <?php echo htmlspecialchars($fullName ?: $student['username']); ?></title>
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

        .profile-layout {
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
            padding: 1.5rem;
            border-radius: var(--rounded-xl);
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
            margin-bottom: 2rem;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .card-header h2 {
            font-size: 1.25rem;
            font-weight: 600;
        }

        /* Profile Picture Section */
        .profile-info-header {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .profile-avatar img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--bg-color);
            box-shadow: var(--shadow);
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
        }
        
        .info-item label {
            display: block;
            color: var(--secondary-text);
            font-size: 0.85rem;
            margin-bottom: 0.25rem;
        }

        .info-item span {
            font-size: 1rem;
            font-weight: 500;
        }

        /* Academic Performance & Profile Completion Card */
        .academic-card-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            align-items: flex-start;
        }

        .performance-table {
            width: 100%;
            border-collapse: collapse;
        }

        .performance-table th, .performance-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        .performance-table tr:last-child td {
            border-bottom: none;
        }

        .performance-table th {
            color: var(--secondary-text);
            font-size: 0.8rem;
            text-transform: uppercase;
        }

        .grade-badge {
            padding: 0.2rem 0.6rem;
            border-radius: 1rem;
            font-weight: 700;
            font-size: 0.8rem;
            background-color: var(--accent-blue);
            color: #fff;
        }
        
        .completion-widget {
            text-align: center;
            padding: 1rem;
            border-left: 1px solid var(--border-color);
        }

        .progress-circle {
            position: relative;
            width: 130px;
            height: 130px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            margin: 0 auto 1.5rem auto;
            background: conic-gradient(var(--success-color) <?php echo $profile_completion * 3.6; ?>deg, #eef2f9 0deg);
        }

        .progress-circle::before {
            content: '';
            position: absolute;
            height: 85%;
            width: 85%;
            background-color: var(--card-bg);
            border-radius: 50%;
        }
        
        .progress-text {
            position: relative;
            font-size: 2rem;
            font-weight: 700;
            color: var(--success-color);
        }
        .progress-text span {
            font-size: 1.25rem;
        }

        .checklist {
            list-style: none;
            text-align: left;
        }
        
        .checklist li {
            margin-bottom: 0.75rem;
            color: var(--secondary-text);
            font-size: 0.9rem;
        }
        
        .checklist li.complete {
            color: var(--primary-text);
        }

        .checklist li i {
            margin-right: 0.75rem;
            color: var(--success-color);
        }
        
        /* Responsive Design */
        @media (max-width: 992px) {
            .profile-layout { flex-direction: column; }
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
            .sidebar-header, .sidebar .logout-btn { display: none; } /* Hide for simple mobile nav */
            .sidebar nav { display: flex; gap: 0.5rem; }
            .sidebar nav a span { display: none; }
        }

        @media (max-width: 768px) {
             .main-content { padding: 1rem; }
             .academic-card-grid { grid-template-columns: 1fr; }
             .completion-widget { border-left: none; border-top: 1px solid var(--border-color); padding-top: 2rem; margin-top: 1rem; }
             .profile-info-header { flex-direction: column; text-align: center; gap: 1rem; }
        }
    </style>
</head>
<body>
    <div class="profile-layout">
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
                <a href="student_profile.php" class="active"><i class="fas fa-user"></i><span>My Profile</span></a>
                <a href="view_assignment.php"><i class="fas fa-tasks"></i><span>Assignments</span></a>
                <a href="student_results.php"><i class="fas fa-graduation-cap"></i><span>My Results</span></a>
                 <a href="view_resources.php"><i class="fas fa-folder-open"></i><span>Resources</span></a>
            </nav>
            <a href="../logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="main-header">
                <h1>My Profile</h1>
                <p>Manage your personal information and view your academic progress.</p>
            </header>

            <div class="profile-container">
                <!-- Personal Info Card -->
                <div class="card">
                    <div class="card-header">
                        <h2>Personal Information</h2>
                    </div>
                    
                    <div class="profile-info-header">
                        <div class="profile-avatar">
                            <img src="../images/logo.jpeg" alt="User Avatar">
                        </div>
                        <div class="info-grid">
                            <div class="info-item">
                                <label>Full Name</label>
                                <span><?php echo htmlspecialchars($fullName ?: $student['username']); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Email Address</label>
                                <span><?php echo htmlspecialchars($student['email'] ?? 'Not set'); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="info-grid">
                        <div class="info-item">
                            <label>Student ID</label>
                            <span><?php echo htmlspecialchars($student['student_id'] ?? 'N/A'); ?></span>
                        </div>
                         <div class="info-item">
                            <label>Class</label>
                            <span><?php echo htmlspecialchars($student['class'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="info-item">
                            <label>Current Year</label>
                            <span><?php echo htmlspecialchars($student['year'] ?? 'N/A'); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Academic Performance & Completion Card -->
                <div class="card">
                    <div class="card-header">
                        <h2>Academic Performance</h2>
                    </div>
                    <div class="academic-card-grid">
                        <div class="results-table-container">
                            <?php if ($academic_results->num_rows > 0): ?>
                            <table class="performance-table">
                                <thead>
                                    <tr>
                                        <th>Subject</th>
                                        <th>Final Mark</th>
                                        <th>Grade</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row = $academic_results->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['subject']); ?></td>
                                        <td><?php echo htmlspecialchars($row['final_mark']); ?>%</td>
                                        <td><span class="grade-badge"><?php echo htmlspecialchars($row['grade']); ?></span></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                            <?php else: ?>
                                <p>No recent results found to display.</p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="completion-widget">
                            <h4>Profile Completion</h4>
                            <div class="progress-circle">
                                <div class="progress-text"><?php echo $profile_completion; ?><span>%</span></div>
                            </div>
                            <ul class="checklist">
                                <li class="<?php echo !empty($fullName) ? 'complete' : ''; ?>">
                                    <i class="fas fa-check-circle"></i> Basic Info
                                </li>
                                <li class="<?php echo !empty($student['email']) ? 'complete' : ''; ?>">
                                    <i class="fas fa-check-circle"></i> Email Added
                                </li>
                                <li class="<?php echo $average_pass_rate > 0 ? 'complete' : ''; ?>">
                                    <i class="fas fa-check-circle"></i> Results Found
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
<?php
$conn->close();
?>

