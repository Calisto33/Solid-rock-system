<?php
session_start();
include '../config.php';

// Helper function to safely display values and handle NULL
function safe_display($value, $default = 'N/A') {
    if ($value === null || $value === '') {
        return htmlspecialchars($default);
    }
    return htmlspecialchars($value);
}

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

// Handle staff approval
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['approve_staff'])) {
    $user_id = $_POST['user_id'];
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Get user information
        $getUserQuery = "SELECT * FROM users WHERE id = ? AND role = 'staff'";
        $getUserStmt = $conn->prepare($getUserQuery);
        $getUserStmt->bind_param("i", $user_id);
        $getUserStmt->execute();
        $userResult = $getUserStmt->get_result();
        $user = $userResult->fetch_assoc();
        
        if ($user) {
            // Check if staff record already exists
            $checkStaffQuery = "SELECT staff_id FROM staff WHERE id = ?";
            $checkStaffStmt = $conn->prepare($checkStaffQuery);
            $checkStaffStmt->bind_param("i", $user_id);
            $checkStaffStmt->execute();
            $staffExists = $checkStaffStmt->get_result()->num_rows > 0;
            
            if (!$staffExists) {
                // Generate WTC staff ID
                $wtc_id_number = 'WTC-' . str_pad($user_id, 4, '0', STR_PAD_LEFT);
                
                // Insert into staff table
                $insertStaffQuery = "INSERT INTO staff (id, username, email, id_number, department, position) VALUES (?, ?, ?, ?, 'Not Assigned', 'Staff Member')";
                $insertStaffStmt = $conn->prepare($insertStaffQuery);
                $insertStaffStmt->bind_param("isss", $user_id, $user['username'], $user['email'], $wtc_id_number);
                $insertStaffStmt->execute();
            }
            
            // Update user status to approved
            $updateUserQuery = "UPDATE users SET status = 'yes' WHERE id = ?";
            $updateUserStmt = $conn->prepare($updateUserQuery);
            $updateUserStmt->bind_param("i", $user_id);
            $updateUserStmt->execute();
        }
        
        $conn->commit();
        $_SESSION['success_message'] = "Staff member approved successfully!";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "Error approving staff: " . $e->getMessage();
    }
    
    header("Location: staff_management.php");
    exit();
}

// Handle staff removal
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['remove_staff'])) {
    $user_id = $_POST['user_id'];
    
    try {
        // Remove from staff table
        $removeStaffQuery = "DELETE FROM staff WHERE id = ?";
        $removeStaffStmt = $conn->prepare($removeStaffQuery);
        $removeStaffStmt->bind_param("i", $user_id);
        $removeStaffStmt->execute();
        
        // Update user status back to pending or remove entirely
        $updateUserQuery = "UPDATE users SET status = 'no' WHERE id = ?";
        $updateUserStmt = $conn->prepare($updateUserQuery);
        $updateUserStmt->bind_param("i", $user_id);
        $updateUserStmt->execute();
        
        $_SESSION['success_message'] = "Staff member removed successfully!";
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Error removing staff: " . $e->getMessage();
    }
    
    header("Location: staff_management.php");
    exit();
}

<<<<<<< HEAD
// --- FIXED: Updated query to match actual database structure ---
// Fetch all staff members (approved) with information from staff table only
$approvedStaffQuery = "
    SELECT 
        u.id,
        u.username,
        u.email,
        u.role,
        u.status,
        u.created_at,
        s.staff_id,
        s.id_number,
        s.department,
        s.position,
        s.phone_number
=======
// First, let's check what columns actually exist in staff_profile table
$checkColumnsQuery = "SHOW COLUMNS FROM staff_profile";
$columnsResult = $conn->query($checkColumnsQuery);
$existing_columns = [];
if ($columnsResult) {
    while ($row = $columnsResult->fetch_assoc()) {
        $existing_columns[] = $row['Field'];
    }
}

// Build the SELECT query dynamically based on existing columns
$basic_columns = "
    u.id,
    u.username,
    u.email as user_email,
    u.role,
    u.status,
    u.created_at,
    s.staff_id,
    s.id_number,
    s.department as staff_department,
    s.position as staff_position,
    s.phone_number,
    s.email as staff_email";

$profile_columns = [];
if (!empty($existing_columns)) {
    $profile_columns[] = "sp.department as profile_department";
    $profile_columns[] = "sp.position as profile_position";
    $profile_columns[] = "sp.social_description";

    // Add optional columns only if they exist
    $optional_columns = [
        'national_id', 'address', 'date_of_birth', 'gender', 'marital_status',
        'emergency_contact_name', 'emergency_contact_phone', 'qualification',
        'experience_years', 'salary', 'hire_date', 'contract_type'
    ];

    foreach ($optional_columns as $col) {
        if (in_array($col, $existing_columns)) {
            $profile_columns[] = "sp.$col";
        }
    }
}

$all_profile_columns = !empty($profile_columns) ? ",\n        " . implode(",\n        ", $profile_columns) : "";

// Fetch all staff members (approved) with comprehensive information
$approvedStaffQuery = "
    SELECT 
        $basic_columns$all_profile_columns
>>>>>>> b291daf7f49078bb0cccb1439969ad4a74e2db38
    FROM users u
    LEFT JOIN staff s ON u.id = s.id
    WHERE u.role = 'staff' AND u.status = 'yes'
    ORDER BY u.created_at DESC";

$approvedStaffResult = $conn->query($approvedStaffQuery);

// Fetch pending staff approvals
$pendingStaffQuery = "
    SELECT 
        u.id,
        u.username,
        u.email,
        u.created_at
    FROM users u
    WHERE u.role = 'staff' AND (u.status = 'no' OR u.status IS NULL OR u.status = 'pending')
    ORDER BY u.created_at DESC";

$pendingStaffResult = $conn->query($pendingStaffQuery);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --secondary: #7209b7;
            --success: #06d6a0;
            --warning: #ffd166;
            --danger: #ef476f;
            --light: #f8f9fa;
            --dark: #2b2d42;
            --gray-100: #f8f9fa;
            --gray-200: #e9ecef;
            --gray-600: #6c757d;
            --gray-800: #343a40;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.1);
            --radius: 0.5rem;
            --radius-lg: 1rem;
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: var(--gray-100);
            color: var(--gray-800);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Header */
        .header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 1.5rem 2rem;
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .header h2 {
            font-size: 1.75rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Container */
        .container {
            max-width: 1400px;
            width: 100%;
            margin: 2rem auto;
            padding: 0 2rem;
            flex: 1;
        }

        /* Alert Messages */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: var(--radius);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .alert-success {
            background-color: rgba(6, 214, 160, 0.1);
            border: 1px solid rgba(6, 214, 160, 0.3);
            color: #059669;
        }

        .alert-danger {
            background-color: rgba(239, 71, 111, 0.1);
            border: 1px solid rgba(239, 71, 111, 0.3);
            color: #dc2626;
        }

        /* Content Box */
        .content-box {
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .box-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .box-header h3 {
            color: var(--primary);
            font-size: 1.4rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .box-content {
            padding: 0;
        }

        /* Statistics */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
            text-align: center;
            border-top: 3px solid var(--primary);
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
        }

        .stat-card.success {
            border-top-color: var(--success);
        }

        .stat-card.warning {
            border-top-color: var(--warning);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .stat-card.success .stat-number {
            color: var(--success);
        }

        .stat-card.warning .stat-number {
            color: var(--warning);
        }

        .stat-label {
            color: var(--gray-600);
            font-size: 0.9rem;
            font-weight: 500;
        }

        /* Table */
        .table-container {
            overflow-x: auto;
        }

        .table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            min-width: 800px;
        }

        .table th,
        .table td {
            padding: 1rem 1.5rem;
            text-align: left;
            border-bottom: 1px solid var(--gray-200);
        }

        .table thead th {
            background-color: var(--gray-100);
            color: var(--gray-800);
            font-weight: 600;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .table tbody tr {
            transition: var(--transition);
        }

        .table tbody tr:hover {
            background-color: rgba(67, 97, 238, 0.05);
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.625rem 1.25rem;
            border: none;
            border-radius: var(--radius);
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: var(--transition);
            font-size: 0.9rem;
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-success {
            background-color: var(--success);
            color: white;
        }

        .btn-success:hover {
            background-color: #05b394;
        }

        .btn-danger {
            background-color: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background-color: #e02c57;
        }

        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
        }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 2rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-success {
            background-color: rgba(6, 214, 160, 0.1);
            color: var(--success);
        }

        .badge-warning {
            background-color: rgba(255, 209, 102, 0.1);
            color: #d97706;
        }

        .badge-primary {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary);
        }

        /* Staff Info Display */
        .staff-info {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .staff-name {
            font-weight: 600;
            color: var(--dark);
        }

        .staff-detail {
            font-size: 0.85rem;
            color: var(--gray-600);
        }

        .contact-info {
            font-size: 0.85rem;
            color: var(--gray-600);
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        /* Actions */
        .actions {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
        }

        .empty-state i {
            font-size: 3rem;
            color: var(--gray-600);
            margin-bottom: 1rem;
        }

        .empty-state p {
            color: var(--gray-600);
            margin-bottom: 1.5rem;
        }

        /* Footer */
        .footer {
            background-color: var(--gray-800);
            color: var(--light);
            padding: 2rem;
            text-align: center;
            margin-top: auto;
        }

        /* Debug info */
        .debug-info {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 0.25rem;
            padding: 1rem;
            margin-bottom: 1rem;
            font-family: monospace;
            font-size: 0.875rem;
        }

        /* Responsive */
        @media screen and (max-width: 768px) {
            .container {
                padding: 0 1rem;
                margin: 1rem auto;
            }

            .table th,
            .table td {
                padding: 0.75rem 1rem;
            }

            .hide-sm {
                display: none;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <h2>
                <i class="fas fa-users-cog"></i>
                Staff Management System
            </h2>
            <div style="display: flex; gap: 1rem;">
                <a href="admin_home.php" class="btn btn-primary">
                    <i class="fas fa-home"></i>
                    Dashboard
                </a>
            </div>
        </div>
    </header>

    <div class="container">
        <!-- Debug Information (remove in production) -->
        <?php if (true): // Set to false to hide debug info ?>
        <div class="debug-info">
            <strong>Debug:</strong> Available staff_profile columns: <?= implode(', ', $existing_columns) ?><br>
            <strong>Staff table data sample:</strong> Found <?= $approvedStaffResult->num_rows ?> approved staff members
        </div>
        <?php endif; ?>

        <!-- Alert Messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?= $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?= $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card success">
                <div class="stat-number"><?= $approvedStaffResult->num_rows ?></div>
                <div class="stat-label">Approved Staff</div>
            </div>
            <div class="stat-card warning">
                <div class="stat-number"><?= $pendingStaffResult->num_rows ?></div>
                <div class="stat-label">Pending Approval</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $approvedStaffResult->num_rows + $pendingStaffResult->num_rows ?></div>
                <div class="stat-label">Total Staff</div>
            </div>
        </div>

        <!-- Pending Approvals -->
        <?php if ($pendingStaffResult->num_rows > 0): ?>
        <div class="content-box">
            <div class="box-header">
                <h3>
                    <i class="fas fa-clock"></i>
                    Pending Staff Approvals
                </h3>
            </div>
            <div class="box-content">
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Email</th>
                                <th class="hide-sm">Registration Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($pending = $pendingStaffResult->fetch_assoc()): ?>
                            <tr>
                                <td><?= safe_display($pending['username']) ?></td>
                                <td><?= safe_display($pending['email']) ?></td>
                                <td class="hide-sm"><?= date('M d, Y', strtotime($pending['created_at'])) ?></td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="user_id" value="<?= $pending['id'] ?>">
                                        <button type="submit" name="approve_staff" class="btn btn-success btn-sm" 
                                                onclick="return confirm('Approve this staff member?')">
                                            <i class="fas fa-check"></i> Approve
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Approved Staff -->
        <div class="content-box">
            <div class="box-header">
                <h3>
                    <i class="fas fa-users"></i>
                    Approved Staff Members
                </h3>
            </div>
            <div class="box-content">
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Staff Information</th>
                                <th>Contact Details</th>
<<<<<<< HEAD
                                <th class="hide-sm">Department & Position</th>
=======
                                <th class="hide-sm">Employment Info</th>
                                <th class="hide-sm">Additional Details</th>
>>>>>>> b291daf7f49078bb0cccb1439969ad4a74e2db38
                                <th class="hide-sm">Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($approvedStaffResult->num_rows > 0): ?>
                                <?php while ($staff = $approvedStaffResult->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div class="staff-info">
                                            <div class="staff-name"><?= safe_display($staff['username']) ?></div>
                                            <div class="staff-detail">ID: <?= safe_display($staff['id_number'], 'Not Set') ?></div>
                                            <?php if ($staff['staff_id']): ?>
                                                <div class="staff-detail">Staff ID: <?= $staff['staff_id'] ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="contact-info">
                                            <div><i class="fas fa-envelope"></i> <?= safe_display($staff['user_email'] ?: $staff['staff_email']) ?></div>
                                            <?php if ($staff['phone_number']): ?>
                                                <div><i class="fas fa-phone"></i> <?= safe_display($staff['phone_number']) ?></div>
                                            <?php endif; ?>
<<<<<<< HEAD
=======
                                            <?php if (isset($staff['address']) && $staff['address']): ?>
                                                <div><i class="fas fa-map-marker-alt"></i> <?= safe_display(substr($staff['address'], 0, 30)) ?>...</div>
                                            <?php endif; ?>
>>>>>>> b291daf7f49078bb0cccb1439969ad4a74e2db38
                                        </div>
                                    </td>
                                    <td class="hide-sm">
                                        <div class="contact-info">
<<<<<<< HEAD
                                            <div><strong>Dept:</strong> <?= safe_display($staff['department'], 'Not Assigned') ?></div>
                                            <div><strong>Position:</strong> <?= safe_display($staff['position'], 'Not Assigned') ?></div>
=======
                                            <div><strong>Dept:</strong> <?= safe_display($staff['profile_department'] ?: $staff['staff_department'], 'Not Assigned') ?></div>
                                            <div><strong>Position:</strong> <?= safe_display($staff['profile_position'] ?: $staff['staff_position'], 'Not Assigned') ?></div>
                                            <?php if (isset($staff['hire_date']) && $staff['hire_date']): ?>
                                                <div><strong>Hired:</strong> <?= date('M Y', strtotime($staff['hire_date'])) ?></div>
                                            <?php endif; ?>
                                            <?php if (isset($staff['contract_type']) && $staff['contract_type']): ?>
                                                <div><strong>Type:</strong> <?= safe_display($staff['contract_type']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="hide-sm">
                                        <div class="contact-info">
                                            <?php if (isset($staff['national_id']) && $staff['national_id']): ?>
                                                <div><strong>National ID:</strong> <?= safe_display($staff['national_id']) ?></div>
                                            <?php endif; ?>
                                            <?php if (isset($staff['date_of_birth']) && $staff['date_of_birth']): ?>
                                                <div><strong>DOB:</strong> <?= date('M d, Y', strtotime($staff['date_of_birth'])) ?></div>
                                            <?php endif; ?>
                                            <?php if (isset($staff['gender']) && $staff['gender']): ?>
                                                <div><strong>Gender:</strong> <?= safe_display($staff['gender']) ?></div>
                                            <?php endif; ?>
                                            <?php if (isset($staff['emergency_contact_name']) && $staff['emergency_contact_name']): ?>
                                                <div><strong>Emergency:</strong> <?= safe_display($staff['emergency_contact_name']) ?></div>
                                            <?php endif; ?>
>>>>>>> b291daf7f49078bb0cccb1439969ad4a74e2db38
                                        </div>
                                    </td>
                                    <td class="hide-sm">
                                        <span class="badge badge-success">Active</span>
                                        <div class="staff-detail">Since <?= date('M Y', strtotime($staff['created_at'])) ?></div>
<<<<<<< HEAD
=======
                                        <?php if (isset($staff['experience_years']) && $staff['experience_years']): ?>
                                            <div class="staff-detail"><?= $staff['experience_years'] ?> years exp.</div>
                                        <?php endif; ?>
>>>>>>> b291daf7f49078bb0cccb1439969ad4a74e2db38
                                    </td>
                                    <td>
                                        <div class="actions">
                                            <a href="edit_staff.php?id=<?= $staff['id'] ?>" class="btn btn-primary btn-sm">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="user_id" value="<?= $staff['id'] ?>">
                                                <button type="submit" name="remove_staff" class="btn btn-danger btn-sm"
                                                        onclick="return confirm('Remove this staff member?')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5">
                                        <div class="empty-state">
                                            <i class="fas fa-users"></i>
                                            <p>No approved staff members found.</p>
                                            <p class="staff-detail">Staff members will appear here after approval.</p>
                                        </div>
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
        <p>&copy; <?php echo date("Y"); ?> Wisetech College Portal - All Rights Reserved</p>
    </footer>
</body>
</html>

<?php
$conn->close();
?>