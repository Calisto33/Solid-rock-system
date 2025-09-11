<?php
session_start();
include '../config.php';

// Check if the user is logged in as super admin
if (!isset($_SESSION['super_admin_id'])) {
    header("Location: super_admin_login.php");
    exit();
}

// Fetch staff details for editing
if (!isset($_GET['staff_id'])) {
    die("Staff ID is missing.");
}

$staff_id = $_GET['staff_id'];

// Initialize variables for form handling
$errors = [];
$success_message = '';

// Fetch staff and related user details
$staffQuery = "
    SELECT s.staff_id, s.id_number, s.username, s.department, s.position, s.phone_number, 
           u.email, u.password 
    FROM staff s 
    JOIN users u ON s.id = u.id 
    WHERE s.staff_id = ? AND u.role = 'staff'";
$stmt = $conn->prepare($staffQuery);
$stmt->bind_param("i", $staff_id);
$stmt->execute();
$staffResult = $stmt->get_result();
$staff = $staffResult->fetch_assoc();
$stmt->close();

if (!$staff) {
    die("Staff member not found.");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_number = trim($_POST['id_number']);
    $username = trim($_POST['username']);
    $department = trim($_POST['department']);
    $email = trim($_POST['email']);
    $password = $_POST['password'] ? password_hash($_POST['password'], PASSWORD_DEFAULT) : $staff['password'];
    $position = trim($_POST['position']);
    $phone_number = trim($_POST['phone_number']);

    // Server-side validation
    if (empty($id_number)) {
        $errors[] = "ID Number is required.";
    } elseif (strlen($id_number) > 20) {
        $errors[] = "ID Number cannot exceed 20 characters.";
    }

    if (empty($username)) {
        $errors[] = "Username is required.";
    } elseif (strlen($username) > 50) {
        $errors[] = "Username cannot exceed 50 characters.";
    }

    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    } elseif (strlen($email) > 100) {
        $errors[] = "Email cannot exceed 100 characters.";
    }

    if (empty($phone_number)) {
        $errors[] = "Phone number is required.";
    } elseif (strlen($phone_number) > 15) {
        $errors[] = "Phone number cannot exceed 15 characters.";
    } elseif (!preg_match('/^[\d\+\-\(\)\s]+$/', $phone_number)) {
        $errors[] = "Phone number contains invalid characters.";
    }

    if (empty($department)) {
        $errors[] = "Department is required.";
    } elseif (strlen($department) > 100) {
        $errors[] = "Department cannot exceed 100 characters.";
    }

    if (empty($position)) {
        $errors[] = "Position is required.";
    } elseif (strlen($position) > 100) {
        $errors[] = "Position cannot exceed 100 characters.";
    }

    // Check for duplicate username (excluding current user)
    if (empty($errors)) {
        $checkUsernameQuery = "SELECT staff_id FROM staff WHERE username = ? AND staff_id != ?";
        $stmt = $conn->prepare($checkUsernameQuery);
        $stmt->bind_param("si", $username, $staff_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $errors[] = "Username already exists. Please choose a different username.";
        }
        $stmt->close();
    }

    // Check for duplicate email (excluding current user)
    if (empty($errors)) {
        $checkEmailQuery = "SELECT u.id FROM users u JOIN staff s ON u.id = s.id WHERE u.email = ? AND s.staff_id != ?";
        $stmt = $conn->prepare($checkEmailQuery);
        $stmt->bind_param("si", $email, $staff_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $errors[] = "Email already exists. Please choose a different email.";
        }
        $stmt->close();
    }

    // If no errors, proceed with database update
    if (empty($errors)) {
        try {
            // Start transaction
            $conn->begin_transaction();

            // Update the staff table
            $updateStaffQuery = "
                UPDATE staff 
                SET id_number = ?, username = ?, department = ?, position = ?, phone_number = ? 
                WHERE staff_id = ?";
            $stmt = $conn->prepare($updateStaffQuery);
            $stmt->bind_param("sssssi", $id_number, $username, $department, $position, $phone_number, $staff_id);
            
            if (!$stmt->execute()) {
                throw new Exception("Error updating staff information: " . $stmt->error);
            }
            $stmt->close();

            // Update the users table
            $updateUserQuery = "
                UPDATE users 
                SET email = ?, password = ? 
                WHERE id = (SELECT id FROM staff WHERE staff_id = ?)";
            $stmt = $conn->prepare($updateUserQuery);
            $stmt->bind_param("ssi", $email, $password, $staff_id);

            if (!$stmt->execute()) {
                throw new Exception("Error updating user credentials: " . $stmt->error);
            }
            $stmt->close();

            // Commit transaction
            $conn->commit();
            
            $success_message = "Staff information updated successfully!";
            
            // Update the $staff array with new values for display
            $staff['id_number'] = $id_number;
            $staff['username'] = $username;
            $staff['department'] = $department;
            $staff['position'] = $position;
            $staff['phone_number'] = $phone_number;
            $staff['email'] = $email;

        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            
            // Handle specific MySQL errors
            if (strpos($e->getMessage(), 'Data too long') !== false) {
                if (strpos($e->getMessage(), 'phone_number') !== false) {
                    $errors[] = "Phone number is too long for the database. Please use a shorter phone number.";
                } else {
                    $errors[] = "One of the fields is too long for the database. Please shorten your input.";
                }
            } elseif (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $errors[] = "This information already exists in the system. Please check your input.";
            } else {
                $errors[] = "An error occurred while updating: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Staff</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --primary-light: #4895ef;
            --primary-dark: #3f37c9;
            --secondary: #4cc9f0;
            --text: #2b2d42;
            --text-light: #8d99ae;
            --background: #f8f9fa;
            --white: #ffffff;
            --success: #4ade80;
            --danger: #f87171;
            --warning: #fbbf24;
            --gray-light: #e9ecef;
            --shadow-sm: 0 1px 2px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 6px rgba(0,0,0,0.05), 0 1px 3px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 15px rgba(0,0,0,0.05), 0 4px 6px rgba(0,0,0,0.05);
            --radius-sm: 0.375rem;
            --radius-md: 0.5rem;
            --radius-lg: 0.75rem;
            --transition: all 0.2s ease-in-out;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Roboto, 'Helvetica Neue', sans-serif;
            background-color: var(--background);
            color: var(--text);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Alert Styles */
        .alert {
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            border-radius: var(--radius-md);
            border-left: 4px solid;
            animation: slideIn 0.3s ease-out;
        }

        .alert-success {
            background-color: #d1fae5;
            border-left-color: var(--success);
            color: #065f46;
        }

        .alert-danger {
            background-color: #fee2e2;
            border-left-color: var(--danger);
            color: #991b1b;
        }

        .alert-icon {
            margin-right: 0.5rem;
        }

        .alert ul {
            margin: 0;
            padding-left: 1.5rem;
        }

        .alert li {
            margin: 0.25rem 0;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: var(--white);
            padding: 1rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: var(--shadow-md);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .logo {
            height: 40px;
            width: auto;
            border-radius: var(--radius-sm);
        }

        .header h1 {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
        }

        .nav-buttons {
            display: flex;
            gap: 1rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.6rem 1.2rem;
            border-radius: var(--radius-md);
            font-weight: 500;
            text-decoration: none;
            transition: var(--transition);
            cursor: pointer;
            border: none;
            font-size: 0.95rem;
        }

        .btn-primary {
            background-color: var(--white);
            color: var(--primary);
        }

        .btn-primary:hover {
            background-color: var(--gray-light);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-secondary {
            background-color: rgba(255, 255, 255, 0.15);
            color: var(--white);
            backdrop-filter: blur(10px);
        }

        .btn-secondary:hover {
            background-color: rgba(255, 255, 255, 0.25);
            transform: translateY(-2px);
        }

        .btn-submit {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: var(--white);
            width: 100%;
            padding: 0.85rem;
            font-size: 1rem;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-submit:disabled {
            background: var(--gray-light);
            color: var(--text-light);
            cursor: not-allowed;
            transform: none;
        }

        .container {
            max-width: 700px;
            margin: 2.5rem auto;
            padding: 0 1.5rem;
            width: 100%;
            flex: 1;
        }

        .card {
            background-color: var(--white);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            padding: 2rem;
            transition: var(--transition);
        }

        .card:hover {
            box-shadow: var(--shadow-lg);
        }

        .card-header {
            margin-bottom: 2rem;
            text-align: center;
            position: relative;
        }

        .card-header::after {
            content: '';
            display: block;
            width: 50px;
            height: 4px;
            background-color: var(--primary);
            margin: 0.75rem auto 0;
            border-radius: 2px;
        }

        .card-header h2 {
            color: var(--text);
            font-weight: 600;
            font-size: 1.75rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group.full-width {
            grid-column: span 2;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text);
            font-size: 0.95rem;
        }

        .form-control {
            width: 100%;
            padding: 0.85rem 1rem;
            border: 1px solid var(--gray-light);
            border-radius: var(--radius-md);
            font-size: 1rem;
            color: var(--text);
            transition: var(--transition);
            background-color: var(--white);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-light);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.15);
        }

        .form-control.error {
            border-color: var(--danger);
            box-shadow: 0 0 0 3px rgba(248, 113, 113, 0.15);
        }

        .form-control::placeholder {
            color: var(--text-light);
        }

        .input-group {
            position: relative;
        }

        .input-icon {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            left: 1rem;
            color: var(--text-light);
        }

        .input-with-icon {
            padding-left: 2.5rem;
        }

        .footer {
            background-color: var(--white);
            padding: 1.5rem;
            text-align: center;
            margin-top: auto;
            box-shadow: 0 -1px 0 0 rgba(0,0,0,0.05);
        }

        .footer-content {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .footer img {
            height: 35px;
            width: auto;
        }

        .footer p {
            color: var(--text-light);
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-group.full-width {
                grid-column: span 1;
            }

            .header {
                padding: 1rem;
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .nav-buttons {
                width: 100%;
                justify-content: center;
            }

            .logo-section {
                flex-direction: column;
                gap: 0.5rem;
            }

            .card {
                padding: 1.5rem;
            }

            .container {
                margin: 1.5rem auto;
            }
        }

        @media (max-width: 480px) {
            .btn {
                padding: 0.5rem 1rem;
                font-size: 0.85rem;
            }

            .nav-buttons {
                flex-direction: column;
                gap: 0.75rem;
            }

            .card-header h2 {
                font-size: 1.5rem;
            }

            .form-control {
                padding: 0.75rem;
                font-size: 0.95rem;
            }

            .footer {
                padding: 1.25rem 1rem;
            }
            
            .footer-content {
                flex-direction: column;
                gap: 0.75rem;
            }
        }

        /* Animation */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .card {
            animation: fadeIn 0.5s ease-in-out;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="logo-section">
            <img src="../images/logo.jpeg" alt="Solid Rock  Logo" class="logo">
            <h1>Edit Staff Profile</h1>
        </div>
        <div class="nav-buttons">
            <a href="super_admin_dashboard.php" class="btn btn-secondary">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="../logout.php" class="btn btn-primary">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </header>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2>Staff Information</h2>
            </div>

            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle alert-icon"></i>
                    <?= htmlspecialchars($success_message) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle alert-icon"></i>
                    <strong>Please fix the following errors:</strong>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" id="editStaffForm">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="id_number" class="form-label">ID Number</label>
                        <div class="input-group">
                            <i class="fas fa-id-card input-icon"></i>
                            <input type="text" id="id_number" name="id_number" 
                                   class="form-control input-with-icon" 
                                   value="<?= htmlspecialchars($staff['id_number']) ?>" 
                                   maxlength="20" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="username" class="form-label">Username</label>
                        <div class="input-group">
                            <i class="fas fa-user input-icon"></i>
                            <input type="text" id="username" name="username" 
                                   class="form-control input-with-icon" 
                                   value="<?= htmlspecialchars($staff['username']) ?>" 
                                   maxlength="50" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email" class="form-label">Email Address</label>
                        <div class="input-group">
                            <i class="fas fa-envelope input-icon"></i>
                            <input type="email" id="email" name="email" 
                                   class="form-control input-with-icon" 
                                   value="<?= htmlspecialchars($staff['email']) ?>" 
                                   maxlength="100" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="phone_number" class="form-label">Phone Number</label>
                        <div class="input-group">
                            <i class="fas fa-phone input-icon"></i>
                            <input type="text" id="phone_number" name="phone_number" 
                                   class="form-control input-with-icon" 
                                   value="<?= htmlspecialchars($staff['phone_number']) ?>" 
                                   maxlength="15" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="department" class="form-label">Department</label>
                        <div class="input-group">
                            <i class="fas fa-building input-icon"></i>
                            <input type="text" id="department" name="department" 
                                   class="form-control input-with-icon" 
                                   value="<?= htmlspecialchars($staff['department']) ?>" 
                                   maxlength="100" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="position" class="form-label">Position</label>
                        <div class="input-group">
                            <i class="fas fa-briefcase input-icon"></i>
                            <input type="text" id="position" name="position" 
                                   class="form-control input-with-icon" 
                                   value="<?= htmlspecialchars($staff['position']) ?>" 
                                   maxlength="100" required>
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label for="password" class="form-label">Password (leave blank to keep unchanged)</label>
                        <div class="input-group">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" id="password" name="password" 
                                   class="form-control input-with-icon" 
                                   placeholder="Enter new password">
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-submit" id="submitBtn">
                    <i class="fas fa-save"></i> Update Staff Information
                </button>
            </form>
        </div>
    </div>

    <footer class="footer">
        <div class="footer-content">
            <img src="../images/logo.jpeg" alt="Solid Rock  Logo">
            <p>&copy; <?php echo date("Y"); ?> Mirilax-Scales. All rights reserved.</p>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('editStaffForm');
            const submitBtn = document.getElementById('submitBtn');

            // Real-time validation
            const inputs = form.querySelectorAll('input[required]');
            inputs.forEach(input => {
                input.addEventListener('input', function() {
                    validateField(this);
                });

                input.addEventListener('blur', function() {
                    validateField(this);
                });
            });

            function validateField(field) {
                const value = field.value.trim();
                const maxLength = parseInt(field.getAttribute('maxlength')) || 255;
                
                field.classList.remove('error');
                
                if (field.type === 'email' && value && !isValidEmail(value)) {
                    field.classList.add('error');
                    return false;
                }
                
                if (value.length > maxLength) {
                    field.classList.add('error');
                    return false;
                }
                
                if (field.id === 'phone_number' && value && !isValidPhone(value)) {
                    field.classList.add('error');
                    return false;
                }
                
                return true;
            }

            function isValidEmail(email) {
                return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
            }

            function isValidPhone(phone) {
                return /^[\d\+\-\(\)\s]+$/.test(phone);
            }

            // Form submission
            form.addEventListener('submit', function(e) {
                let isValid = true;
                
                inputs.forEach(input => {
                    if (!validateField(input)) {
                        isValid = false;
                    }
                });

                if (!isValid) {
                    e.preventDefault();
                    return false;
                }

                // Disable submit button to prevent double submission
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
            });

            // Auto-hide success message after 5 seconds
            const successAlert = document.querySelector('.alert-success');
            if (successAlert) {
                setTimeout(() => {
                    successAlert.style.opacity = '0';
                    setTimeout(() => {
                        successAlert.remove();
                    }, 300);
                }, 5000);
            }
        });
    </script>
</body>
</html>