<?php
session_start();
include('../config.php');

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if admin is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// --- PHP TO GET USER COUNTS ---
// Fetch count for pending users
$sql_pending = "SELECT COUNT(*) as count FROM users WHERE status = 'pending'";
$pending_result = $conn->query($sql_pending);
$pending_count = $pending_result->fetch_assoc()['count'];

// Fetch count for approved users
$sql_approved = "SELECT COUNT(*) as count FROM users WHERE status = 'yes'";
$approved_result = $conn->query($sql_approved);
$approved_count = $approved_result->fetch_assoc()['count'];

// Fetch count for rejected users
$sql_rejected = "SELECT COUNT(*) as count FROM users WHERE status = 'no'";
$rejected_result = $conn->query($sql_rejected);
$rejected_count = $rejected_result->fetch_assoc()['count'];

// Fetch count for approved students
$sql_students = "SELECT COUNT(*) as count FROM users WHERE status = 'yes' AND role = 'student'";
$student_result = $conn->query($sql_students);
$student_count = $student_result->fetch_assoc()['count'];

// Fetch count for approved staff
$sql_staff = "SELECT COUNT(*) as count FROM users WHERE status = 'yes' AND role = 'staff'";
$staff_result = $conn->query($sql_staff);
$staff_count = $staff_result->fetch_assoc()['count'];
// --- END OF COUNTING CODE ---

// --- ENHANCED POST HANDLING BLOCK FOR BOTH STUDENTS AND STAFF ---
$message = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['user_id']) && isset($_POST['action'])) {
        $user_id = intval($_POST['user_id']);
        $action = $_POST['action'];

        if ($action == 'approve') {
            // Step 1: Get user details (role and username) before approving
            $user_stmt = $conn->prepare("SELECT role, username, email FROM users WHERE id = ?");
            $user_stmt->bind_param("i", $user_id);
            $user_stmt->execute();
            $user_result = $user_stmt->get_result();
            $user = $user_result->fetch_assoc();
            $user_stmt->close();

            if ($user) {
                // Step 2: Update the user's status to 'yes'
                $update_stmt = $conn->prepare("UPDATE users SET status = 'yes' WHERE id = ?");
                $update_stmt->bind_param("i", $user_id);

                if ($update_stmt->execute()) {
                    
                    // Step 3: Handle role-specific actions
                    if ($user['role'] === 'student') {
                        // STUDENT APPROVAL LOGIC (existing code)
                        
                        // CHECK IF STUDENT ALREADY EXISTS TO PREVENT DUPLICATES
                        $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM students WHERE user_id = ?");
                        $check_stmt->bind_param("i", $user_id);
                        $check_stmt->execute();
                        $check_result = $check_stmt->get_result();
                        $exists = $check_result->fetch_assoc()['count'] > 0;
                        $check_stmt->close();
                        
                        // Only insert if student doesn't already exist
                        if (!$exists) {
                            // GENERATE PROPER STUDENT ID
                            // Get the latest student_id to generate next one
                            $latest_stmt = $conn->prepare("SELECT student_id FROM students WHERE student_id LIKE 'WTC-%' ORDER BY student_id DESC LIMIT 1");
                            $latest_stmt->execute();
                            $latest_result = $latest_stmt->get_result();
                            
                            if ($latest_result->num_rows > 0) {
                                $latest_row = $latest_result->fetch_assoc();
                                $latest_id = $latest_row['student_id'];
                                // Extract number from WTC-25162X format
                                preg_match('/WTC-(\d+)([A-Z])/', $latest_id, $matches);
                                if ($matches) {
                                    $number = intval($matches[1]);
                                    $letter = $matches[2];
                                    
                                    // Increment the letter, if Z then increment number and start with A
                                    if ($letter === 'Z') {
                                        $number++;
                                        $new_letter = 'A';
                                    } else {
                                        $new_letter = chr(ord($letter) + 1);
                                    }
                                    $new_student_id = "WTC-" . $number . $new_letter;
                                } else {
                                    // Fallback if pattern doesn't match
                                    $new_student_id = "WTC-" . (25162 + $user_id) . "A";
                                }
                            } else {
                                // First student
                                $new_student_id = "WTC-25162A";
                            }
                            $latest_stmt->close();
                            
                            // INSERT WITH PROPER STUDENT_ID
                            $insert_stmt = $conn->prepare(
                                "INSERT INTO students (student_id, user_id, username) VALUES (?, ?, ?)"
                            );
                            $insert_stmt->bind_param("sis", $new_student_id, $user_id, $user['username']);
                            
                            if ($insert_stmt->execute()) {
                                $message = "Student approved and added successfully with ID: " . $new_student_id;
                            } else {
                                $message = "Student approved but failed to add to students table: " . $insert_stmt->error;
                            }
                            $insert_stmt->close();
                        } else {
                            $message = "Student approved successfully (already exists in students table)";
                        }
                        
                    } elseif ($user['role'] === 'staff') {
                        // STAFF APPROVAL LOGIC (new code)
                        
                        // CHECK IF STAFF ALREADY EXISTS TO PREVENT DUPLICATES
                        $check_staff_stmt = $conn->prepare("SELECT COUNT(*) as count FROM staff WHERE id = ?");
                        $check_staff_stmt->bind_param("i", $user_id);
                        $check_staff_stmt->execute();
                        $check_staff_result = $check_staff_stmt->get_result();
                        $staff_exists = $check_staff_result->fetch_assoc()['count'] > 0;
                        $check_staff_stmt->close();
                        
                        // Only insert if staff doesn't already exist
                        if (!$staff_exists) {
                            // Start transaction for staff creation
                            $conn->begin_transaction();
                            
                            try {
                                // INSERT INTO STAFF TABLE
                                $insert_staff_stmt = $conn->prepare(
                                    "INSERT INTO staff (id, username, email, department, position) VALUES (?, ?, ?, 'Not Assigned', 'Staff Member')"
                                );
                                $insert_staff_stmt->bind_param("iss", $user_id, $user['username'], $user['email']);
                                
                                if ($insert_staff_stmt->execute()) {
                                    $staff_id = $conn->insert_id;
                                    
                                    // CREATE STAFF PROFILE RECORD
                                    $insert_profile_stmt = $conn->prepare(
                                        "INSERT INTO staff_profile (staff_id, department, position, social_description) VALUES (?, 'Not Assigned', 'Staff Member', 'No description provided')"
                                    );
                                    $insert_profile_stmt->bind_param("i", $staff_id);
                                    
                                    if ($insert_profile_stmt->execute()) {
                                        $conn->commit();
                                        $message = "Staff member approved and added successfully with Staff ID: " . $staff_id;
                                    } else {
                                        $conn->rollback();
                                        $message = "Staff approved but failed to create profile: " . $insert_profile_stmt->error;
                                    }
                                    $insert_profile_stmt->close();
                                } else {
                                    $conn->rollback();
                                    $message = "Staff approved but failed to add to staff table: " . $insert_staff_stmt->error;
                                }
                                $insert_staff_stmt->close();
                                
                            } catch (Exception $e) {
                                $conn->rollback();
                                $message = "Error processing staff approval: " . $e->getMessage();
                            }
                        } else {
                            $message = "Staff member approved successfully (already exists in staff table)";
                        }
                        
                    } else {
                        // OTHER ROLES (admin, parent, etc.)
                        $message = "User with role '" . $user['role'] . "' approved successfully";
                    }
                } else {
                    $message = "Error approving user: " . $update_stmt->error;
                }
                $update_stmt->close();
            }
        } else { // This is for the 'reject' action
            $status = 'no';
            $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $status, $user_id);
            
            if ($stmt->execute()) {
                $message = "User rejected successfully";
            } else {
                $message = "Error rejecting user: " . $stmt->error;
            }
            $stmt->close();
        }
        
        // Reload the page to show all changes and clear the POST request
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}
// --- END OF ENHANCED POST HANDLING ---

// Fetch users pending approval
$sql = "SELECT id, username, email, role, status FROM users WHERE status = 'pending' ORDER BY created_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --primary-light: #dbeafe;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --dark: #1e293b;
            --light: #f8fafc;
            --gray: #64748b;
            --white: #ffffff;
            --border-radius: 8px;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', 'Roboto', 'Helvetica Neue', sans-serif;
        }

        body {
            background-color: #f1f5f9;
            color: var(--dark);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Navbar */
        .navbar {
            background-color: var(--white);
            box-shadow: var(--shadow);
            padding: 0.75rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .nav-logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .nav-logo h1 {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary);
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .nav-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background-color: var(--primary);
            color: var(--white);
            border: none;
            border-radius: var(--border-radius);
            font-weight: 500;
            text-decoration: none;
            transition: var(--transition);
        }

        .nav-btn:hover {
            background-color: var(--primary-dark);
        }

        /* Main Content */
        .container {
            max-width: 1200px;
            width: 100%;
            margin: 2rem auto;
            padding: 0 1rem;
            flex: 1;
        }

        .dashboard-card {
            background-color: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .card-header {
            background-color: var(--primary-light);
            padding: 1.5rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .card-header h2 {
            color: var(--primary-dark);
            font-size: 1.5rem;
            font-weight: 600;
        }

        .card-body {
            padding: 1.5rem;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 0.35rem 0.8rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-pending {
            background-color: #fef3c7;
            color: #92400e;
        }

        .role-student {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .role-staff {
            background-color: #d1fae5;
            color: #065f46;
        }

        .role-admin {
            background-color: #fde2e7;
            color: #991b1b;
        }

        .role-parent {
            background-color: #e0e7ff;
            color: #5b21b6;
        }

        /* Alert Message */
        .alert {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: var(--border-radius);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-success {
            background-color: #d1fae5;
            color: #065f46;
            border-left: 4px solid var(--success);
        }

        /* Table Styles */
        .table-container {
            overflow-x: auto;
            border-radius: var(--border-radius);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead tr {
            background-color: #f8fafc;
            border-bottom: 2px solid #e2e8f0;
        }

        th {
            text-align: left;
            padding: 1rem;
            font-weight: 600;
            color: var(--gray);
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
        }

        td {
            padding: 1rem;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
            font-size: 0.875rem;
        }

        tbody tr:hover {
            background-color: #f1f5f9;
        }

        /* Buttons */
        .btn-group {
            display: flex;
            gap: 0.5rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            font-size: 0.875rem;
        }

        .btn-approve {
            background-color: var(--success);
            color: var(--white);
        }

        .btn-approve:hover {
            background-color: #059669;
        }

        .btn-reject {
            background-color: var(--danger);
            color: var(--white);
        }

        .btn-reject:hover {
            background-color: #dc2626;
        }

        /* User Avatar */
        .user-cell {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .user-avatar {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 50%;
            background-color: var(--primary-light);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: var(--primary);
            font-size: 1rem;
        }

        .user-info {
            display: flex;
            flex-direction: column;
        }

        .user-name {
            font-weight: 500;
        }

        .user-email {
            color: var(--gray);
            font-size: 0.75rem;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background-color: var(--white);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .stat-icon {
            width: 3rem;
            height: 3rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }

        .pending-icon {
            background-color: #fef3c7;
            color: #92400e;
        }

        .approved-icon {
            background-color: #d1fae5;
            color: #065f46;
        }

        .rejected-icon {
            background-color: #fee2e2;
            color: #991b1b;
        }
        
        .students-icon {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .staff-icon {
            background-color: #d1fae5;
            color: #065f46;
        }

        .stat-content {
            display: flex;
            flex-direction: column;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            line-height: 1;
        }

        .stat-label {
            font-size: 0.875rem;
            color: var(--gray);
            margin-top: 0.25rem;
        }

        /* Footer */
        .footer {
            background-color: var(--white);
            padding: 1.5rem;
            text-align: center;
            margin-top: auto;
            box-shadow: 0 -1px 3px 0 rgba(0, 0, 0, 0.1);
        }

        .footer-content {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 1rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .footer-logo {
            width: 3rem;
            height: 3rem;
            object-fit: contain;
        }

        .footer-text {
            font-size: 0.875rem;
            color: var(--gray);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .container {
                margin: 1rem auto;
            }

            .btn {
                padding: 0.4rem 0.8rem;
                font-size: 0.8rem;
            }

            td, th {
                padding: 0.75rem 0.5rem;
            }

            .user-avatar {
                width: 2rem;
                height: 2rem;
                font-size: 0.8rem;
            }
        }

        @media (max-width: 576px) {
            .btn-group {
                flex-direction: column;
                width: 100%;
            }

            .btn {
                width: 100%;
            }

            .nav-logo h1 {
                font-size: 1rem;
            }

            .nav-btn {
                padding: 0.4rem 0.8rem;
                font-size: 0.8rem;
            }

            .card-header h2 {
                font-size: 1.25rem;
            }

            .table-container {
                margin: 0 -1rem;
                width: calc(100% + 2rem);
                border-radius: 0;
            }

            table {
                width: 100%;
                min-width: 600px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-logo">
            <i class="fas fa-graduation-cap" style="color: var(--primary); font-size: 1.5rem;"></i>
            <h1>Wisetech College Portal</h1>
        </div>
        <div class="nav-links">
            <a href="admin_home.php" class="nav-btn">
                <i class="fas fa-home"></i>
                Dashboard
            </a>
        </div>
    </nav>

    <div class="container">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon pending-icon">
                    <i class="fas fa-user-clock"></i>
                </div>
                <div class="stat-content">
                    <span class="stat-value"><?= $pending_count ?></span>
                    <span class="stat-label">Pending Users</span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon students-icon">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="stat-content">
                    <span class="stat-value"><?= $student_count ?></span>
                    <span class="stat-label">Approved Students</span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon staff-icon">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <div class="stat-content">
                    <span class="stat-value"><?= $staff_count ?></span>
                    <span class="stat-label">Approved Staff</span>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon approved-icon">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="stat-content">
                    <span class="stat-value"><?= $approved_count ?></span>
                    <span class="stat-label">Total Approved</span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon rejected-icon">
                    <i class="fas fa-user-times"></i>
                </div>
                <div class="stat-content">
                    <span class="stat-value"><?= $rejected_count ?></span>
                    <span class="stat-label">Rejected Users</span>
                </div>
            </div>
        </div>

        <div class="dashboard-card">
            <div class="card-header">
                <h2><i class="fas fa-users-cog"></i> User Approval Management</h2>
            </div>
            <div class="card-body">
                <?php if ($message): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <span><?= htmlspecialchars($message) ?></span>
                    </div>
                <?php endif; ?>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td>#<?= htmlspecialchars($row['id']) ?></td>
                                        <td class="user-cell">
                                            <div class="user-avatar">
                                                <?= strtoupper(substr($row['username'], 0, 1)) ?>
                                            </div>
                                            <div class="user-info">
                                                <span class="user-name"><?= htmlspecialchars($row['username']) ?></span>
                                                <span class="user-email"><?= htmlspecialchars($row['email']) ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="status-badge role-<?= $row['role'] ?>">
                                                <i class="fas fa-<?= $row['role'] === 'student' ? 'user-graduate' : ($row['role'] === 'staff' ? 'chalkboard-teacher' : ($row['role'] === 'admin' ? 'user-shield' : 'user')) ?>"></i>
                                                <?= htmlspecialchars($row['role']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status-badge status-pending">
                                                <i class="fas fa-clock" style="margin-right: 4px;"></i>
                                                <?= htmlspecialchars($row['status'] ?? 'Pending') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <form method="POST" action="approve_users.php" style="margin: 0;">
                                                <input type="hidden" name="user_id" value="<?= $row['id'] ?>">
                                                <div class="btn-group">
                                                    <button type="submit" name="action" value="approve" class="btn btn-approve"
                                                            onclick="return confirm('Approve this <?= $row['role'] ?>?')">
                                                        <i class="fas fa-check"></i> Approve
                                                    </button>
                                                    <button type="submit" name="action" value="reject" class="btn btn-reject"
                                                            onclick="return confirm('Reject this user?')">
                                                        <i class="fas fa-times"></i> Reject
                                                    </button>
                                                </div>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; color: var(--gray); padding: 3rem;">
                                        <i class="fas fa-users" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
                                        No pending users found.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <footer class="footer">
        <div class="footer-content">
            <img src="../images/logo.jpg" alt="College Logo" class="footer-logo">
            <p class="footer-text">&copy; <?php echo date("Y"); ?> Wisetech College Portal. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>

<?php
$conn->close();
?>