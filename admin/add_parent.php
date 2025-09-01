<?php
session_start();
include '../config.php';

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Parent</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Favicon - matching main site -->
    <link rel="icon" type="image/ico" href="images/favicon.ico">
    <link rel="shortcut icon" type="image/jpeg" href="images/logo.jpeg">
    
    <style>
        :root {
            --primary-color: #4361ee;
            --primary-light: #4895ef;
            --primary-dark: #3f37c9;
            --secondary-color: #72efdd;
            --text-color: #212529;
            --text-light: #6c757d;
            --white: #fff;
            --light-bg: #f8f9fa;
            --border-radius: 12px;
            --box-shadow: 0 10px 20px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            color: var(--text-color);
            line-height: 1.6;
        }

        .header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: var(--white);
            padding: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header h1 {
            margin: 0;
            font-size: 1.8rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .nav-links {
            display: flex;
            gap: 1rem;
        }

        .link-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 1.2rem;
            color: var(--white);
            background-color: rgba(255, 255, 255, 0.15);
            text-decoration: none;
            border-radius: 50px;
            font-weight: 500;
            transition: var(--transition);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .link-btn:hover {
            background-color: rgba(255, 255, 255, 0.25);
            transform: translateY(-2px);
        }

        .container {
            max-width: 700px;
            margin: 2rem auto;
            padding: 0 1.5rem;
            flex: 1;
            width: 100%;
        }

        .breadcrumb {
            margin-bottom: 1.5rem;
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

        .content-box {
            background: var(--white);
            padding: 2.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            border: 1px solid rgba(0, 0, 0, 0.05);
            position: relative;
            overflow: hidden;
        }

        .content-box::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
        }

        h2 {
            color: var(--primary-dark);
            margin-bottom: 2rem;
            text-align: center;
            font-weight: 600;
            position: relative;
            padding-bottom: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
        }

        h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background-color: var(--primary-light);
            border-radius: 50px;
        }

        .info-box {
            background: linear-gradient(135deg, rgba(67, 97, 238, 0.05), rgba(114, 239, 221, 0.05));
            border: 1px solid rgba(67, 97, 238, 0.1);
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .info-box i {
            color: var(--primary-color);
            font-size: 1.2rem;
        }

        .info-box p {
            margin: 0;
            color: var(--text-color);
            font-size: 0.95rem;
        }

        .form-group {
            margin-bottom: 1.8rem;
            position: relative;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-color);
            font-weight: 500;
            font-size: 0.95rem;
        }

        .required {
            color: #e74c3c;
        }

        input, select, textarea {
            width: 100%;
            padding: 0.9rem 1rem;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            font-family: inherit;
            font-size: 1rem;
            color: var(--text-color);
            background-color: #f9f9f9;
            transition: var(--transition);
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--primary-light);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.15);
            background-color: var(--white);
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        button {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: var(--white);
            padding: 1rem 1.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            width: 100%;
            font-size: 1.1rem;
            font-weight: 600;
            transition: var(--transition);
            letter-spacing: 0.5px;
            box-shadow: 0 4px 6px rgba(67, 97, 238, 0.2);
            margin-top: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(67, 97, 238, 0.3);
        }

        button:active {
            transform: translateY(0);
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        .form-full {
            grid-column: 1 / -1;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn-secondary {
            background: var(--text-light);
            color: var(--white);
            padding: 1rem 1.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: var(--transition);
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            flex: 1;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-1px);
        }

        .optional-note {
            font-size: 0.85rem;
            color: var(--text-light);
            font-style: italic;
            margin-top: 0.25rem;
        }

        .student-selection {
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 0.5rem;
            background-color: #f9f9f9;
        }

        .student-checkbox {
            display: flex;
            align-items: center;
            padding: 0.5rem;
            margin-bottom: 0.25rem;
            border-radius: 4px;
            transition: var(--transition);
        }

        .student-checkbox:hover {
            background-color: rgba(67, 97, 238, 0.05);
        }

        .student-checkbox input[type="checkbox"] {
            width: auto;
            margin-right: 0.75rem;
            transform: scale(1.2);
        }

        .student-checkbox label {
            margin: 0;
            cursor: pointer;
            flex: 1;
        }

        @media screen and (max-width: 768px) {
            .header {
                padding: 1rem;
                flex-direction: column;
                gap: 1rem;
            }

            .nav-links {
                flex-direction: column;
                width: 100%;
            }

            .form-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .container {
                padding: 0 1rem;
                margin: 1.5rem auto;
            }

            .content-box {
                padding: 2rem 1.5rem;
            }

            .form-actions {
                flex-direction: column;
            }
        }

        @media screen and (max-width: 480px) {
            .header h1 {
                font-size: 1.4rem;
            }

            h2 {
                font-size: 1.3rem;
            }

            .content-box {
                padding: 1.5rem 1rem;
                border-radius: 8px;
            }

            input, select, textarea, button {
                font-size: 0.95rem;
                padding: 0.8rem;
            }

            label {
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <h1>
            <i class="fas fa-user-plus"></i>
            Parent Registration Portal
        </h1>
        <div class="nav-links">
            <a href="admin_home.php" class="link-btn">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="parents.php" class="link-btn">
                <i class="fas fa-users"></i> Parents
            </a>
        </div>
    </header>

    <div class="container">
        <div class="breadcrumb">
            <a href="admin_home.php">Dashboard</a> / 
            <a href="parents.php">Parent Management</a> / 
            Add Parent
        </div>

        <div class="content-box">
            <h2>
                <i class="fas fa-user-plus"></i>
                Add New Parent
            </h2>

            <div class="info-box">
                <i class="fas fa-info-circle"></i>
                <p>Create a new parent account. You can assign multiple students to this parent during registration or later from the parent management page.</p>
            </div>

            <form action="process_add_parent.php" method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="first_name">First Name <span class="required">*</span></label>
                        <input type="text" id="first_name" name="first_name" required>
                    </div>

                    <div class="form-group">
                        <label for="last_name">Last Name <span class="required">*</span></label>
                        <input type="text" id="last_name" name="last_name" required>
                    </div>

                    <div class="form-group">
                        <label for="username">Username <span class="required">*</span></label>
                        <input type="text" id="username" name="username" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address <span class="required">*</span></label>
                        <input type="email" id="email" name="email" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Password <span class="required">*</span></label>
                        <input type="password" id="password" name="password" required>
                    </div>

                    <div class="form-group">
                        <label for="phone_number">Phone Number <span class="required">*</span></label>
                        <input type="text" id="phone_number" name="phone_number" required>
                    </div>

                    <div class="form-group form-full">
                        <label for="relationship">Relationship <span class="required">*</span></label>
                        <select id="relationship" name="relationship" required>
                            <option value="" disabled selected>Select relationship...</option>
                            <option value="Father">Father</option>
                            <option value="Mother">Mother</option>
                            <option value="Guardian">Guardian</option>
                            <option value="Stepfather">Stepfather</option>
                            <option value="Stepmother">Stepmother</option>
                            <option value="Grandparent">Grandparent</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div class="form-group form-full">
                        <label for="address">Home Address <span class="required">*</span></label>
                        <textarea id="address" name="address" rows="3" required placeholder="Enter full home address..."></textarea>
                    </div>

                    <div class="form-group form-full">
                        <label>Assign Students (Optional)</label>
                        <div class="student-selection">
                            <?php
                            // Updated query to match your actual database structure
                            $studentsQuery = "SELECT student_id, user_id, first_name, last_name, username FROM students ORDER BY first_name, last_name";
                            $studentsResult = $conn->query($studentsQuery);
                            
                            if ($studentsResult && $studentsResult->num_rows > 0) {
                                while ($student = $studentsResult->fetch_assoc()) {
                                    // Build student name from available fields
                                    $studentName = '';
                                    if (!empty($student['first_name']) && !empty($student['last_name'])) {
                                        $studentName = trim($student['first_name'] . ' ' . $student['last_name']);
                                    } else if (!empty($student['username'])) {
                                        $studentName = $student['username'];
                                    } else {
                                        $studentName = 'Student ID: ' . $student['student_id'];
                                    }
                                    
                                    // Use student_id as the value since that's your primary key column
                                    echo '<div class="student-checkbox">';
                                    echo '<input type="checkbox" name="student_ids[]" value="' . htmlspecialchars($student['student_id']) . '" id="student_' . htmlspecialchars($student['student_id']) . '">';
                                    echo '<label for="student_' . htmlspecialchars($student['student_id']) . '">' . htmlspecialchars($studentName) . '</label>';
                                    echo '</div>';
                                }
                            } else {
                                echo '<p style="color: var(--text-light); font-style: italic; padding: 1rem;">No students available for assignment.</p>';
                            }
                            ?>
                        </div>
                        <div class="optional-note">You can select multiple students to assign to this parent, or do it later from the parent management page.</div>
                    </div>

                    <!-- Hidden role field since this is specifically for parents -->
                    <input type="hidden" name="role" value="parent">
                </div>

                <div class="form-actions">
                    <a href="parents.php" class="btn-secondary">
                        <i class="fas fa-arrow-left"></i> Cancel
                    </a>
                    <button type="submit">
                        <i class="fas fa-save"></i>
                        Register Parent
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const requiredFields = ['first_name', 'last_name', 'username', 'email', 'password', 'phone_number', 'relationship', 'address'];
            let hasErrors = false;

            requiredFields.forEach(fieldName => {
                const field = document.getElementById(fieldName);
                if (!field.value.trim()) {
                    field.style.borderColor = '#e74c3c';
                    hasErrors = true;
                } else {
                    field.style.borderColor = '#e0e0e0';
                }
            });

            // Email validation
            const email = document.getElementById('email');
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (email.value && !emailRegex.test(email.value)) {
                email.style.borderColor = '#e74c3c';
                alert('Please enter a valid email address.');
                hasErrors = true;
                e.preventDefault();
                return;
            }

            // Password validation
            const password = document.getElementById('password');
            if (password.value && password.value.length < 6) {
                password.style.borderColor = '#e74c3c';
                alert('Password must be at least 6 characters long.');
                hasErrors = true;
                e.preventDefault();
                return;
            }

            if (hasErrors) {
                e.preventDefault();
                alert('Please fill in all required fields correctly.');
            }
        });

        // Real-time validation feedback
        document.querySelectorAll('input[required], select[required], textarea[required]').forEach(field => {
            field.addEventListener('blur', function() {
                if (this.value.trim()) {
                    this.style.borderColor = '#28a745';
                } else {
                    this.style.borderColor = '#e74c3c';
                }
            });

            field.addEventListener('input', function() {
                if (this.style.borderColor === 'rgb(231, 76, 60)') { // #e74c3c
                    this.style.borderColor = '#e0e0e0';
                }
            });
        });

        // Student selection counter
        document.addEventListener('DOMContentLoaded', function() {
            const checkboxes = document.querySelectorAll('input[name="student_ids[]"]');
            const updateCounter = () => {
                const checkedCount = document.querySelectorAll('input[name="student_ids[]"]:checked').length;
                const label = document.querySelector('label[for="address"]').previousElementSibling;
                if (label && label.tagName === 'LABEL') {
                    const counterText = checkedCount > 0 ? ` (${checkedCount} selected)` : '';
                    label.textContent = label.textContent.replace(/ \(\d+ selected\)/, '') + counterText;
                }
            };
            
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateCounter);
            });
        });
    </script>
</body>
</html>