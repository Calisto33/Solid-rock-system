<?php
session_start();
include '../config.php'; // Database connection

// Helper function to safely display values and handle NULL
function safe_display($value, $default = '') {
    if ($value === null || $value === '') {
        return htmlspecialchars($default);
    }
    return htmlspecialchars($value);
}

// Verify if the user is logged in and is an admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_GET['staff_id'] ?? $_GET['id'] ?? null;
if (!$user_id) {
    die("Staff ID is missing.");
}

// First, check if this is a staff user and create staff record if needed
$checkUserQuery = "SELECT id, username, email, role, status FROM users WHERE id = ? AND role = 'staff'";
$checkUserStmt = $conn->prepare($checkUserQuery);
$checkUserStmt->bind_param("i", $user_id);
$checkUserStmt->execute();
$userResult = $checkUserStmt->get_result();
$user = $userResult->fetch_assoc();

if (!$user) {
    die("Staff user not found or user is not a staff member.");
}

// Check if staff record exists, if not create it
$checkStaffQuery = "SELECT staff_id FROM staff WHERE id = ?";
$checkStaffStmt = $conn->prepare($checkStaffQuery);
$checkStaffStmt->bind_param("i", $user_id);
$checkStaffStmt->execute();
$staffExists = $checkStaffStmt->get_result();

if ($staffExists->num_rows === 0) {
    // Generate a WTC staff ID number
    $wtc_id_number = 'WTC-' . str_pad($user_id, 4, '0', STR_PAD_LEFT);
    
    // Create staff record with WTC ID
    $createStaffQuery = "INSERT INTO staff (id, username, email, id_number, department, position) VALUES (?, ?, ?, ?, 'Not Assigned', 'Staff Member')";
    $createStaffStmt = $conn->prepare($createStaffQuery);
    $createStaffStmt->bind_param("isss", $user_id, $user['username'], $user['email'], $wtc_id_number);
    $createStaffStmt->execute();
    $staff_id = $conn->insert_id;
    
    // Create staff profile
    $createProfileQuery = "INSERT INTO staff_profile (staff_id, department, position) VALUES (?, 'Not Assigned', 'Staff Member')";
    $createProfileStmt = $conn->prepare($createProfileQuery);
    $createProfileStmt->bind_param("i", $staff_id);
    $createProfileStmt->execute();
} else {
    // Get existing staff_id
    $staffRow = $staffExists->fetch_assoc();
    $staff_id = $staffRow['staff_id'];
}

// Now fetch comprehensive staff information
$staffQuery = "
    SELECT 
        s.staff_id, 
        s.id,
        u.username, 
        u.email,
        s.id_number, 
        s.department, 
        s.position, 
        s.phone_number,
        sp.staff_id as profile_id,
        sp.department as profile_department,
        sp.position as profile_position,
        sp.social_description,
        sp.profile_picture,
        sp.national_id,
        sp.address,
        sp.date_of_birth,
        sp.gender,
        sp.marital_status,
        sp.emergency_contact_name,
        sp.emergency_contact_phone,
        sp.qualification,
        sp.experience_years,
        sp.salary,
        sp.hire_date,
        sp.contract_type
    FROM staff s
    LEFT JOIN users u ON s.id = u.id
    LEFT JOIN staff_profile sp ON s.staff_id = sp.staff_id
    WHERE s.id = ?";

$stmt = $conn->prepare($staffQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$staffResult = $stmt->get_result();
$staff = $staffResult->fetch_assoc();

if (!$staff) {
    die("Staff member data could not be retrieved.");
}

// Handle form submission for updating staff details
$message = "";
$messageType = "success";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Get form data
        $id_number = $_POST['id_number'];
        $email = $_POST['email'];
        $department = $_POST['department'];
        $position = $_POST['position'];
        $phone_number = $_POST['phone_number'];
        
        // New fields
        $national_id = $_POST['national_id'];
        $address = $_POST['address'];
        $date_of_birth = $_POST['date_of_birth'];
        $gender = $_POST['gender'];
        $marital_status = $_POST['marital_status'];
        $emergency_contact_name = $_POST['emergency_contact_name'];
        $emergency_contact_phone = $_POST['emergency_contact_phone'];
        $qualification = $_POST['qualification'];
        $experience_years = $_POST['experience_years'];
        $salary = $_POST['salary'];
        $hire_date = $_POST['hire_date'];
        $contract_type = $_POST['contract_type'];
        $social_description = $_POST['social_description'];

        // Update the staff table
        $updateStaffQuery = "UPDATE staff SET id_number = ?, department = ?, position = ?, phone_number = ? WHERE staff_id = ?";
        $updateStaffStmt = $conn->prepare($updateStaffQuery);
        $updateStaffStmt->bind_param("ssssi", $id_number, $department, $position, $phone_number, $staff['staff_id']);
        $updateStaffStmt->execute();
        
        // Update the email in users table
        $updateUserQuery = "UPDATE users SET email = ? WHERE id = ?";
        $updateUserStmt = $conn->prepare($updateUserQuery);
        $updateUserStmt->bind_param("si", $email, $staff['id']);
        $updateUserStmt->execute();

        // Update staff profile
        $updateProfileQuery = "
            UPDATE staff_profile 
            SET department = ?, position = ?, social_description = ?, national_id = ?, 
                address = ?, date_of_birth = ?, gender = ?, marital_status = ?, 
                emergency_contact_name = ?, emergency_contact_phone = ?, qualification = ?, 
                experience_years = ?, salary = ?, hire_date = ?, contract_type = ?
            WHERE staff_id = ?";
        $updateProfileStmt = $conn->prepare($updateProfileQuery);
        $updateProfileStmt->bind_param("sssssssssssisssi", 
            $department, $position, $social_description, $national_id, $address, 
            $date_of_birth, $gender, $marital_status, $emergency_contact_name, 
            $emergency_contact_phone, $qualification, $experience_years, $salary, 
            $hire_date, $contract_type, $staff['staff_id']);
        $updateProfileStmt->execute();
        
        $conn->commit();
        $message = "Staff details updated successfully!";
        
        // Refresh the data
        $stmt->execute();
        $staffResult = $stmt->get_result();
        $staff = $staffResult->fetch_assoc();
        
    } catch (Exception $e) {
        $conn->rollback();
        $message = "Error updating staff details: " . $e->getMessage();
        $messageType = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Staff Details - <?= safe_display($staff['username']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2563eb;
            --primary-light: #3b82f6;
            --secondary-color: #6366f1;
            --accent-color: #8b5cf6;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --text-color: #1f2937;
            --text-secondary: #4b5563;
            --white: #ffffff;
            --light-bg: #f9fafb;
            --border-color: #e5e7eb;
            --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --transition: all 0.3s ease;
            --border-radius: 12px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--light-bg);
            color: var(--text-color);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Header */
        .header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: var(--white);
            padding: 1.5rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header h2 {
            margin: 0;
            font-size: 1.75rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .link-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.25rem;
            color: var(--white);
            background-color: rgba(255, 255, 255, 0.15);
            text-decoration: none;
            border-radius: 8px;
            transition: var(--transition);
            font-weight: 500;
            font-size: 0.95rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .link-btn:hover {
            background-color: rgba(255, 255, 255, 0.25);
            transform: translateY(-2px);
        }

        /* Container */
        .container {
            max-width: 1200px;
            width: 100%;
            margin: 2rem auto;
            padding: 0 1.5rem;
            flex: 1;
        }

        /* Content Box */
        .content-box {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .form-header {
            background: linear-gradient(135deg, var(--primary-light), var(--accent-color));
            color: var(--white);
            padding: 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .staff-avatar {
            width: 4rem;
            height: 4rem;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .staff-info h3 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .staff-info p {
            margin: 0.25rem 0 0 0;
            opacity: 0.9;
        }

        /* Form Body */
        .form-body {
            padding: 2rem;
        }

        /* Section Headers */
        .section-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin: 2rem 0 1.5rem 0;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid var(--border-color);
            color: var(--primary-color);
            font-size: 1.25rem;
            font-weight: 600;
        }

        .section-header:first-child {
            margin-top: 0;
        }

        /* Form Grid */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .form-grid-3 {
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        /* Labels and Inputs */
        label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-secondary);
            font-weight: 500;
            font-size: 0.9rem;
            transition: var(--transition);
        }

        .required::after {
            content: "*";
            color: var(--danger-color);
            margin-left: 0.25rem;
        }

        .input-wrapper {
            position: relative;
        }

        input[type="text"],
        input[type="email"],
        input[type="number"],
        input[type="date"],
        select,
        textarea {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 1rem;
            transition: var(--transition);
            background-color: var(--white);
            color: var(--text-color);
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        select {
            cursor: pointer;
        }

        /* Messages */
        .message {
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 500;
        }

        .message.success {
            background-color: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .message.error {
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        /* Buttons */
        .buttons-container {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border-color);
        }

        .btn {
            padding: 0.875rem 2rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: var(--white);
            flex: 1;
        }

        .btn-primary:hover {
            background-color: var(--primary-light);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .btn-secondary {
            background-color: var(--text-secondary);
            color: var(--white);
        }

        .btn-secondary:hover {
            background-color: var(--text-color);
        }

        /* Footer */
        .footer {
            background-color: var(--text-color);
            color: var(--white);
            text-align: center;
            padding: 1.5rem;
            margin-top: auto;
        }

        /* Responsive Design */
        @media screen and (max-width: 768px) {
            .header {
                padding: 1rem 1.5rem;
                flex-direction: column;
                text-align: center;
            }

            .form-grid,
            .form-grid-3 {
                grid-template-columns: 1fr;
            }

            .buttons-container {
                flex-direction: column;
            }

            .form-body {
                padding: 1.5rem;
            }

            .form-header {
                padding: 1.5rem;
                flex-direction: column;
                text-align: center;
            }
        }

        @media screen and (max-width: 480px) {
            .container {
                padding: 0 1rem;
                margin: 1rem auto;
            }

            .form-body {
                padding: 1rem;
            }

            .form-header {
                padding: 1rem;
            }

            .staff-avatar {
                width: 3rem;
                height: 3rem;
                font-size: 1.25rem;
            }

            .staff-info h3 {
                font-size: 1.25rem;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <h2>
            <i class="fas fa-user-edit"></i>
            Edit Staff Details
        </h2>
        <a href="manage_staff.php" class="link-btn">
            <i class="fas fa-arrow-left"></i>
            Back to Staff Management
        </a>
    </header>

    <div class="container">
        <div class="content-box">
            <div class="form-header">
                <div class="staff-avatar">
                    <?= strtoupper(substr($staff['username'], 0, 1)) ?>
                </div>
                <div class="staff-info">
                    <h3><?= safe_display($staff['username']) ?></h3>
                    <p>Staff ID: <?= safe_display($staff['staff_id']) ?></p>
                    <p><?= safe_display($staff['department'] ?: $staff['profile_department'], 'Department not assigned') ?></p>
                </div>
            </div>
            
            <div class="form-body">
                <form action="" method="POST">
                    <?php if ($message): ?>
                        <div class="message <?= $messageType ?>">
                            <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                            <?= $message ?>
                        </div>
                    <?php endif; ?>

                    <!-- Basic Information Section -->
                    <div class="section-header">
                        <i class="fas fa-user"></i>
                        Basic Information
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="id_number" class="required">Employee ID Number</label>
                            <input type="text" id="id_number" name="id_number" value="<?= safe_display($staff['id_number']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="national_id">National ID Number</label>
                            <input type="text" id="national_id" name="national_id" value="<?= safe_display($staff['national_id']) ?>">
                        </div>

                        <div class="form-group">
                            <label for="email" class="required">Email Address</label>
                            <input type="email" id="email" name="email" value="<?= safe_display($staff['email']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="phone_number">Phone Number</label>
                            <input type="text" id="phone_number" name="phone_number" value="<?= safe_display($staff['phone_number']) ?>">
                        </div>

                        <div class="form-group">
                            <label for="date_of_birth">Date of Birth</label>
                            <input type="date" id="date_of_birth" name="date_of_birth" value="<?= safe_display($staff['date_of_birth']) ?>">
                        </div>

                        <div class="form-group">
                            <label for="gender">Gender</label>
                            <select id="gender" name="gender">
                                <option value="">Select Gender</option>
                                <option value="Male" <?= $staff['gender'] === 'Male' ? 'selected' : '' ?>>Male</option>
                                <option value="Female" <?= $staff['gender'] === 'Female' ? 'selected' : '' ?>>Female</option>
                                <option value="Other" <?= $staff['gender'] === 'Other' ? 'selected' : '' ?>>Other</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="marital_status">Marital Status</label>
                            <select id="marital_status" name="marital_status">
                                <option value="">Select Status</option>
                                <option value="Single" <?= $staff['marital_status'] === 'Single' ? 'selected' : '' ?>>Single</option>
                                <option value="Married" <?= $staff['marital_status'] === 'Married' ? 'selected' : '' ?>>Married</option>
                                <option value="Divorced" <?= $staff['marital_status'] === 'Divorced' ? 'selected' : '' ?>>Divorced</option>
                                <option value="Widowed" <?= $staff['marital_status'] === 'Widowed' ? 'selected' : '' ?>>Widowed</option>
                            </select>
                        </div>

                        <div class="form-group full-width">
                            <label for="address">Home Address</label>
                            <textarea id="address" name="address" rows="3"><?= safe_display($staff['address']) ?></textarea>
                        </div>
                    </div>

                    <!-- Employment Information Section -->
                    <div class="section-header">
                        <i class="fas fa-briefcase"></i>
                        Employment Information
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="department">Department</label>
                            <select id="department" name="department">
                                <option value="">Select Department</option>
                                <option value="Mathematics" <?= ($staff['department'] ?: $staff['profile_department']) === 'Mathematics' ? 'selected' : '' ?>>Mathematics</option>
                                <option value="English" <?= ($staff['department'] ?: $staff['profile_department']) === 'English' ? 'selected' : '' ?>>English</option>
                                <option value="Science" <?= ($staff['department'] ?: $staff['profile_department']) === 'Science' ? 'selected' : '' ?>>Science</option>
                                <option value="History" <?= ($staff['department'] ?: $staff['profile_department']) === 'History' ? 'selected' : '' ?>>History</option>
                                <option value="Physical Education" <?= ($staff['department'] ?: $staff['profile_department']) === 'Physical Education' ? 'selected' : '' ?>>Physical Education</option>
                                <option value="Arts" <?= ($staff['department'] ?: $staff['profile_department']) === 'Arts' ? 'selected' : '' ?>>Arts</option>
                                <option value="Administration" <?= ($staff['department'] ?: $staff['profile_department']) === 'Administration' ? 'selected' : '' ?>>Administration</option>
                                <option value="Information Technology" <?= ($staff['department'] ?: $staff['profile_department']) === 'Information Technology' ? 'selected' : '' ?>>Information Technology</option>
                                <option value="Library" <?= ($staff['department'] ?: $staff['profile_department']) === 'Library' ? 'selected' : '' ?>>Library</option>
                                <option value="Maintenance" <?= ($staff['department'] ?: $staff['profile_department']) === 'Maintenance' ? 'selected' : '' ?>>Maintenance</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="position">Position/Job Title</label>
                            <select id="position" name="position">
                                <option value="">Select Position</option>
                                <option value="Teacher" <?= ($staff['position'] ?: $staff['profile_position']) === 'Teacher' ? 'selected' : '' ?>>Teacher</option>
                                <option value="Senior Teacher" <?= ($staff['position'] ?: $staff['profile_position']) === 'Senior Teacher' ? 'selected' : '' ?>>Senior Teacher</option>
                                <option value="Head of Department" <?= ($staff['position'] ?: $staff['profile_position']) === 'Head of Department' ? 'selected' : '' ?>>Head of Department</option>
                                <option value="Principal" <?= ($staff['position'] ?: $staff['profile_position']) === 'Principal' ? 'selected' : '' ?>>Principal</option>
                                <option value="Vice Principal" <?= ($staff['position'] ?: $staff['profile_position']) === 'Vice Principal' ? 'selected' : '' ?>>Vice Principal</option>
                                <option value="Administrator" <?= ($staff['position'] ?: $staff['profile_position']) === 'Administrator' ? 'selected' : '' ?>>Administrator</option>
                                <option value="Secretary" <?= ($staff['position'] ?: $staff['profile_position']) === 'Secretary' ? 'selected' : '' ?>>Secretary</option>
                                <option value="Librarian" <?= ($staff['position'] ?: $staff['profile_position']) === 'Librarian' ? 'selected' : '' ?>>Librarian</option>
                                <option value="IT Support" <?= ($staff['position'] ?: $staff['profile_position']) === 'IT Support' ? 'selected' : '' ?>>IT Support</option>
                                <option value="Maintenance Staff" <?= ($staff['position'] ?: $staff['profile_position']) === 'Maintenance Staff' ? 'selected' : '' ?>>Maintenance Staff</option>
                                <option value="Security Guard" <?= ($staff['position'] ?: $staff['profile_position']) === 'Security Guard' ? 'selected' : '' ?>>Security Guard</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="hire_date">Hire Date</label>
                            <input type="date" id="hire_date" name="hire_date" value="<?= safe_display($staff['hire_date']) ?>">
                        </div>

                        <div class="form-group">
                            <label for="contract_type">Contract Type</label>
                            <select id="contract_type" name="contract_type">
                                <option value="">Select Type</option>
                                <option value="Permanent" <?= $staff['contract_type'] === 'Permanent' ? 'selected' : '' ?>>Permanent</option>
                                <option value="Temporary" <?= $staff['contract_type'] === 'Temporary' ? 'selected' : '' ?>>Temporary</option>
                                <option value="Contract" <?= $staff['contract_type'] === 'Contract' ? 'selected' : '' ?>>Contract</option>
                                <option value="Part-time" <?= $staff['contract_type'] === 'Part-time' ? 'selected' : '' ?>>Part-time</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="experience_years">Years of Experience</label>
                            <input type="number" id="experience_years" name="experience_years" value="<?= safe_display($staff['experience_years']) ?>" min="0" max="50">
                        </div>

                        <div class="form-group">
                            <label for="salary">Monthly Salary</label>
                            <input type="number" id="salary" name="salary" value="<?= safe_display($staff['salary']) ?>" min="0" step="0.01">
                        </div>

                        <div class="form-group full-width">
                            <label for="qualification">Qualifications</label>
                            <textarea id="qualification" name="qualification" rows="3" placeholder="e.g., Bachelor of Education, Master's in Mathematics"><?= safe_display($staff['qualification']) ?></textarea>
                        </div>
                    </div>

                    <!-- Emergency Contact Section -->
                    <div class="section-header">
                        <i class="fas fa-phone-alt"></i>
                        Emergency Contact
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="emergency_contact_name">Emergency Contact Name</label>
                            <input type="text" id="emergency_contact_name" name="emergency_contact_name" value="<?= safe_display($staff['emergency_contact_name']) ?>">
                        </div>

                        <div class="form-group">
                            <label for="emergency_contact_phone">Emergency Contact Phone</label>
                            <input type="text" id="emergency_contact_phone" name="emergency_contact_phone" value="<?= safe_display($staff['emergency_contact_phone']) ?>">
                        </div>
                    </div>

                    <!-- Additional Information Section -->
                    <div class="section-header">
                        <i class="fas fa-info-circle"></i>
                        Additional Information
                    </div>

                    <div class="form-group full-width">
                        <label for="social_description">Bio/Description</label>
                        <textarea id="social_description" name="social_description" rows="4" placeholder="Brief description about the staff member..."><?= safe_display($staff['social_description']) ?></textarea>
                    </div>

                    <div class="buttons-container">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Update Staff Information
                        </button>
                        <a href="staff_management.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i>
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; <?php echo date("Y"); ?> Wisetech College Portal | Staff Management System</p>
    </footer>

    <script>
        // Auto-format phone numbers
        const phoneInputs = document.querySelectorAll('input[type="text"][name*="phone"]');
        phoneInputs.forEach(input => {
            input.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length > 0) {
                    if (value.length <= 3) {
                        value = value;
                    } else if (value.length <= 6) {
                        value = value.slice(0, 3) + '-' + value.slice(3);
                    } else {
                        value = value.slice(0, 3) + '-' + value.slice(3, 6) + '-' + value.slice(6, 10);
                    }
                }
                e.target.value = value;
            });
        });

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const requiredFields = document.querySelectorAll('input[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.style.borderColor = 'var(--danger-color)';
                    isValid = false;
                } else {
                    field.style.borderColor = 'var(--border-color)';
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields.');
            }
        });

        // Auto-calculate years of experience if hire date is provided
        const hireDateInput = document.getElementById('hire_date');
        const experienceInput = document.getElementById('experience_years');
        
        hireDateInput.addEventListener('change', function() {
            if (this.value && !experienceInput.value) {
                const hireDate = new Date(this.value);
                const currentDate = new Date();
                const years = Math.floor((currentDate - hireDate) / (365.25 * 24 * 60 * 60 * 1000));
                if (years >= 0) {
                    experienceInput.value = years;
                }
            }
        });

        // Salary formatting
        const salaryInput = document.getElementById('salary');
        salaryInput.addEventListener('blur', function() {
            if (this.value) {
                this.value = parseFloat(this.value).toFixed(2);
            }
        });
    </script>
</body>
</html>