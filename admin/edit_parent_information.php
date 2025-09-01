<?php
session_start();
include '../config.php';

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

// Initialize variables
$error_message = '';
$parent_id = null;
$parentData = null;
$userData = null;

// Check if parent_id is provided in the URL
if (!isset($_GET['parent_id']) || empty($_GET['parent_id'])) {
    $error_message = "Parent ID is missing. Please select a parent to edit.";
} else {
    $parent_id = intval($_GET['parent_id']); // Sanitize the input
    
    // Fetch parent details from the parents table and user details
    $parentQuery = "
        SELECT 
            p.parent_id, 
            p.user_id, 
            p.phone_number, 
            p.relationship, 
            p.address,
            u.username,
            u.email,
            u.first_name,
            u.last_name
        FROM parents p
        LEFT JOIN users u ON p.user_id = u.id
        WHERE p.parent_id = ?";
    
    $stmt = $conn->prepare($parentQuery);
    if ($stmt) {
        $stmt->bind_param("i", $parent_id);
        $stmt->execute();
        $parentResult = $stmt->get_result();
        $parentData = $parentResult->fetch_assoc();
        $stmt->close();
        
        if (!$parentData) {
            $error_message = "Parent not found. The parent may have been deleted or the ID is invalid.";
        }
    } else {
        $error_message = "Database error occurred. Please try again later.";
    }
}

// Get assigned students count for this parent using the student_parent_relationships table
$assignedStudentsCount = 0;
if ($parent_id) {
    $countQuery = "SELECT COUNT(*) as count FROM student_parent_relationships WHERE parent_id = ?";
    $stmt = $conn->prepare($countQuery);
    if ($stmt) {
        $stmt->bind_param("i", $parent_id);
        $stmt->execute();
        $countResult = $stmt->get_result();
        if ($countResult) {
            $countData = $countResult->fetch_assoc();
            $assignedStudentsCount = $countData['count'];
        }
        $stmt->close();
    } else {
        // If the above query fails, we'll set count to 0 and continue
        $assignedStudentsCount = 0;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Parent Information | Wisetech College</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2563eb;
            --primary-dark: #1d4ed8;
            --secondary-color: #f3f4f6;
            --accent-color: #3b82f6;
            --text-color: #1f2937;
            --muted-text: #6b7280;
            --white: #ffffff;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-800: #1f2937;
            --success: #10b981;
            --error: #ef4444;
            --warning: #f59e0b;
            --border-radius: 12px;
            --box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --box-shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--gray-50);
            color: var(--text-color);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            line-height: 1.6;
        }

        .header {
            background-color: var(--white);
            padding: 1.25rem;
            box-shadow: var(--box-shadow);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
        }

        .logo h2 {
            color: var(--primary-color);
            font-weight: 700;
            font-size: 1.5rem;
            margin-left: 0.5rem;
        }

        .logo i {
            font-size: 1.75rem;
            color: var(--primary-color);
        }

        .header-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.625rem 1.25rem;
            border-radius: var(--border-radius);
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
            box-shadow: var(--box-shadow);
            border: none;
            cursor: pointer;
            font-size: 0.95rem;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: var(--white);
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--box-shadow-lg);
        }

        .btn-secondary {
            background-color: var(--gray-300);
            color: var(--text-color);
        }

        .btn-secondary:hover {
            background-color: var(--gray-200);
            transform: translateY(-2px);
            box-shadow: var(--box-shadow-lg);
        }

        .btn-success {
            background-color: var(--success);
            color: var(--white);
        }

        .btn-success:hover {
            background-color: #059669;
            transform: translateY(-2px);
            box-shadow: var(--box-shadow-lg);
        }

        main {
            flex: 1;
            max-width: 1000px;
            width: 100%;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }

        .card {
            background-color: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            transition: var(--transition);
        }

        .card:hover {
            box-shadow: var(--box-shadow-lg);
            transform: translateY(-5px);
        }

        .card-header {
            background-color: var(--primary-color);
            padding: 1.5rem;
            color: var(--white);
            text-align: center;
        }

        .card-header h2 {
            font-size: 1.75rem;
            font-weight: 700;
            margin: 0;
        }

        .card-body {
            padding: 2rem;
        }

        .info-box {
            background-color: #e0f2fe;
            border: 1px solid #81d4fa;
            color: #01579b;
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .info-box i {
            font-size: 1.25rem;
        }

        .success-message {
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #166534;
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .success-message i {
            font-size: 1.25rem;
        }

        .error-message {
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .error-message i {
            font-size: 1.25rem;
        }

        .form-section {
            border: 1px solid var(--gray-200);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 2rem;
            background-color: var(--gray-50);
        }

        .form-section h3 {
            color: var(--primary-color);
            margin-bottom: 1rem;
            font-size: 1.25rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group:last-of-type {
            margin-bottom: 0;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-color);
            font-size: 0.95rem;
        }

        .form-control {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 1px solid var(--gray-300);
            border-radius: var(--border-radius);
            background-color: var(--white);
            color: var(--text-color);
            font-size: 1rem;
            transition: var(--transition);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
        }

        .form-control:hover {
            border-color: var(--accent-color);
        }

        select.form-control {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%236b7280' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            padding-right: 2.5rem;
        }

        textarea.form-control {
            resize: vertical;
            min-height: 120px;
        }

        .btn-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .footer {
            background-color: var(--gray-800);
            color: var(--white);
            padding: 1.5rem;
            text-align: center;
            margin-top: 3rem;
        }

        .footer p {
            margin: 0;
            font-size: 0.95rem;
        }

        .input-icon-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            left: 1rem;
            color: var(--muted-text);
        }

        .input-with-icon {
            padding-left: 2.75rem;
        }

        .form-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-top: 2rem;
        }

        .password-note {
            background-color: #fef3c7;
            border: 1px solid #f59e0b;
            color: #92400e;
            padding: 0.75rem;
            border-radius: var(--border-radius);
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        @media screen and (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .header-container {
                flex-direction: column;
                gap: 1rem;
            }
            
            .card-body {
                padding: 1.5rem;
            }

            .form-actions {
                grid-template-columns: 1fr;
            }
        }

        @media screen and (max-width: 480px) {
            .card-header h2 {
                font-size: 1.5rem;
            }
            
            .card-body {
                padding: 1.25rem;
            }
            
            .form-label {
                font-size: 0.9rem;
            }
            
            .form-control, .btn {
                padding: 0.75rem;
                font-size: 0.95rem;
            }
        }

        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .card {
            animation: fadeIn 0.5s ease-out forwards;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-container">
            <div class="logo">
                <i class="fas fa-graduation-cap"></i>
                <h2>Wisetech College Portal</h2>
            </div>
            <div class="header-actions">
                <a href="parents.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> Back to Parents
                </a>
                <?php if ($parent_id): ?>
                <a href="manage_parent_students.php?parent_id=<?= $parent_id ?>" class="btn btn-success">
                    <i class="fas fa-users"></i> Manage Students
                </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main>
        <div class="card">
            <div class="card-header">
                <h2>Edit Parent Information</h2>
            </div>
            <div class="card-body">
                <?php if (!empty($error_message)): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span><?= htmlspecialchars($error_message) ?></span>
                    </div>
                    <a href="parents.php" class="btn btn-secondary btn-icon">
                        <i class="fas fa-arrow-left"></i> Go Back to Parents List
                    </a>
                <?php else: ?>
                    <?php if (isset($_SESSION['error_message'])): ?>
                        <div class="error-message">
                            <i class="fas fa-exclamation-triangle"></i>
                            <span><?= htmlspecialchars($_SESSION['error_message']) ?></span>
                        </div>
                        <?php unset($_SESSION['error_message']); ?>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['success_message'])): ?>
                        <div class="success-message">
                            <i class="fas fa-check-circle"></i>
                            <span><?= htmlspecialchars($_SESSION['success_message']) ?></span>
                        </div>
                        <?php unset($_SESSION['success_message']); ?>
                    <?php endif; ?>

                    <div class="info-box">
                        <i class="fas fa-info-circle"></i>
                        <span>This parent is currently assigned to <strong><?= $assignedStudentsCount ?></strong> student(s). Use the "Manage Students" button to modify student assignments.</span>
                    </div>

                    <form action="update_parent_information.php" method="POST">
                        <input type="hidden" name="parent_id" value="<?= $parent_id ?>">
                        <input type="hidden" name="user_id" value="<?= $parentData['user_id'] ?>">
                        
                        <!-- Personal Information Section -->
                        <div class="form-section">
                            <h3><i class="fas fa-user"></i> Personal Information</h3>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="first_name" class="form-label">First Name</label>
                                    <div class="input-icon-wrapper">
                                        <i class="fas fa-user input-icon"></i>
                                        <input type="text" name="first_name" id="first_name" class="form-control input-with-icon" 
                                               value="<?= htmlspecialchars($parentData['first_name'] ?? '') ?>" 
                                               required>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="last_name" class="form-label">Last Name</label>
                                    <div class="input-icon-wrapper">
                                        <i class="fas fa-user input-icon"></i>
                                        <input type="text" name="last_name" id="last_name" class="form-control input-with-icon" 
                                               value="<?= htmlspecialchars($parentData['last_name'] ?? '') ?>" 
                                               required>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="username" class="form-label">Username</label>
                                    <div class="input-icon-wrapper">
                                        <i class="fas fa-at input-icon"></i>
                                        <input type="text" name="username" id="username" class="form-control input-with-icon" 
                                               value="<?= htmlspecialchars($parentData['username'] ?? '') ?>" 
                                               required>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="email" class="form-label">Email Address</label>
                                    <div class="input-icon-wrapper">
                                        <i class="fas fa-envelope input-icon"></i>
                                        <input type="email" name="email" id="email" class="form-control input-with-icon" 
                                               value="<?= htmlspecialchars($parentData['email'] ?? '') ?>" 
                                               required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Contact Information Section -->
                        <div class="form-section">
                            <h3><i class="fas fa-phone"></i> Contact Information</h3>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="phone_number" class="form-label">Phone Number</label>
                                    <div class="input-icon-wrapper">
                                        <i class="fas fa-phone input-icon"></i>
                                        <input type="text" name="phone_number" id="phone_number" class="form-control input-with-icon" 
                                               value="<?= htmlspecialchars($parentData['phone_number'] ?? '') ?>" 
                                               pattern="[0-9]{10,15}" 
                                               title="Please enter a valid phone number (10-15 digits)" 
                                               required>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="relationship" class="form-label">Relationship</label>
                                    <div class="input-icon-wrapper">
                                        <i class="fas fa-user-friends input-icon"></i>
                                        <select name="relationship" id="relationship" class="form-control input-with-icon" required>
                                            <option value="">Select Relationship</option>
                                            <option value="Father" <?= (isset($parentData['relationship']) && $parentData['relationship'] == 'Father') ? 'selected' : '' ?>>Father</option>
                                            <option value="Mother" <?= (isset($parentData['relationship']) && $parentData['relationship'] == 'Mother') ? 'selected' : '' ?>>Mother</option>
                                            <option value="Guardian" <?= (isset($parentData['relationship']) && $parentData['relationship'] == 'Guardian') ? 'selected' : '' ?>>Guardian</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="address" class="form-label">Address</label>
                                <div class="input-icon-wrapper">
                                    <i class="fas fa-home input-icon" style="top: 1.25rem;"></i>
                                    <textarea name="address" id="address" rows="3" class="form-control input-with-icon" 
                                              placeholder="Enter full address" 
                                              required><?= htmlspecialchars($parentData['address'] ?? '') ?></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Password Section -->
                        <div class="form-section">
                            <h3><i class="fas fa-lock"></i> Password Settings</h3>
                            <div class="password-note">
                                <i class="fas fa-info-circle"></i> Leave password fields empty if you don't want to change the password.
                            </div>
                            
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <div class="input-icon-wrapper">
                                        <i class="fas fa-lock input-icon"></i>
                                        <input type="password" name="new_password" id="new_password" class="form-control input-with-icon" 
                                               placeholder="Enter new password (optional)" 
                                               minlength="6">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                    <div class="input-icon-wrapper">
                                        <i class="fas fa-lock input-icon"></i>
                                        <input type="password" name="confirm_password" id="confirm_password" class="form-control input-with-icon" 
                                               placeholder="Confirm new password" 
                                               minlength="6">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <a href="parents.php" class="btn btn-secondary btn-icon">
                                <i class="fas fa-arrow-left"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary btn-icon">
                                <i class="fas fa-save"></i> Update Parent Information
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer class="footer">
        <p>&copy; <?php echo date("Y"); ?> Wisetech College Portal. All rights reserved.</p>
    </footer>

    <script>
        // Add form validation
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const firstName = document.getElementById('first_name').value;
                    const lastName = document.getElementById('last_name').value;
                    const username = document.getElementById('username').value;
                    const email = document.getElementById('email').value;
                    const phoneNumber = document.getElementById('phone_number').value;
                    const relationship = document.getElementById('relationship').value;
                    const address = document.getElementById('address').value;
                    const newPassword = document.getElementById('new_password').value;
                    const confirmPassword = document.getElementById('confirm_password').value;

                    // Check required fields
                    if (!firstName || !lastName || !username || !email || !phoneNumber || !relationship || !address) {
                        e.preventDefault();
                        alert('Please fill in all required fields.');
                        return false;
                    }

                    // Validate phone number format
                    const phoneRegex = /^[0-9]{10,15}$/;
                    if (!phoneRegex.test(phoneNumber)) {
                        e.preventDefault();
                        alert('Please enter a valid phone number (10-15 digits only).');
                        return false;
                    }

                    // Validate email format
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(email)) {
                        e.preventDefault();
                        alert('Please enter a valid email address.');
                        return false;
                    }

                    // Password validation (only if password fields are filled)
                    if (newPassword || confirmPassword) {
                        if (newPassword !== confirmPassword) {
                            e.preventDefault();
                            alert('New password and confirm password do not match.');
                            return false;
                        }
                        
                        if (newPassword.length < 6) {
                            e.preventDefault();
                            alert('Password must be at least 6 characters long.');
                            return false;
                        }
                    }
                });

                // Real-time validation feedback
                const requiredFields = ['first_name', 'last_name', 'username', 'email', 'phone_number', 'relationship', 'address'];
                
                requiredFields.forEach(fieldName => {
                    const field = document.getElementById(fieldName);
                    if (field) {
                        field.addEventListener('blur', function() {
                            if (this.value.trim()) {
                                this.style.borderColor = '#10b981';
                            } else {
                                this.style.borderColor = '#ef4444';
                            }
                        });

                        field.addEventListener('input', function() {
                            if (this.style.borderColor === 'rgb(239, 68, 68)') { // #ef4444
                                this.style.borderColor = '#d1d5db';
                            }
                        });
                    }
                });

                // Password matching validation
                const newPasswordField = document.getElementById('new_password');
                const confirmPasswordField = document.getElementById('confirm_password');
                
                function validatePasswordMatch() {
                    if (newPasswordField.value && confirmPasswordField.value) {
                        if (newPasswordField.value === confirmPasswordField.value) {
                            confirmPasswordField.style.borderColor = '#10b981';
                        } else {
                            confirmPasswordField.style.borderColor = '#ef4444';
                        }
                    }
                }

                newPasswordField.addEventListener('input', validatePasswordMatch);
                confirmPasswordField.addEventListener('input', validatePasswordMatch);
            }
        });
    </script>
</body>
</html>