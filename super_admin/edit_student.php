<?php
session_start();
include '../config.php';

// Check if super admin is logged in
if (!isset($_SESSION['super_admin_id'])) {
    header("Location: super_admin_login.php");
    exit();
}

if (!isset($_GET['student_id'])) {
    die("Student ID is missing.");
}

$student_id = $_GET['student_id'];

// FIXED: Corrected JOIN condition from s.id = u.id to s.user_id = u.id
$studentQuery = "
    SELECT s.student_id, s.user_id, s.username, s.first_name, s.last_name, s.class, s.course, s.year, u.email, u.password 
    FROM students s 
    LEFT JOIN users u ON s.user_id = u.id 
    WHERE s.student_id = ?";
$stmt = $conn->prepare($studentQuery);
$stmt->bind_param("s", $student_id);
$stmt->execute();
$studentResult = $stmt->get_result();
$student = $studentResult->fetch_assoc();
$stmt->close();

if (!$student) {
    die("Student not found.");
}

// Define your complete school class structure
function generateClassOptions($selectedClass = '') {
    $schoolStructure = [
        'Primary School' => [
            'ECD' => ['ECD A', 'ECD B'],
            'Grades' => ['Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7']
        ],
        'Secondary School' => [
            'Forms' => ['Form 1', 'Form 2', 'Form 3', 'Form 4', 'Form 5', 'Form 6']
        ]
    ];
    
    $streams = ['Gold', 'Silver', 'Platinum', 'Bronze', 'Diamond'];
    $html = '';
    
    // Add default option
    $selected = ($selectedClass === '' || $selectedClass === 'Unassigned') ? 'selected' : '';
    $html .= "<option value='Unassigned' {$selected}>-- Select Class --</option>\n";
    
    // Generate options for each school level
    foreach ($schoolStructure as $schoolLevel => $categories) {
        $html .= "<optgroup label='{$schoolLevel}'>\n";
        
        foreach ($categories as $categoryName => $grades) {
            foreach ($grades as $grade) {
                foreach ($streams as $stream) {
                    $classValue = "{$grade} {$stream}";
                    $selected = ($selectedClass === $classValue) ? 'selected' : '';
                    $html .= "    <option value='{$classValue}' {$selected}>{$classValue}</option>\n";
                }
            }
        }
        
        $html .= "</optgroup>\n";
    }
    
    return $html;
}

// Define enhanced subject combinations for your school structure
$course_combinations = [
    // Primary Level Subjects (for ECD and Lower Grades)
    'primary_subjects' => [
        'General Studies' => 'General Studies',
        'English, Mathematics, Science' => 'English, Mathematics, Science',
        'Basic Education' => 'Basic Education',
    ],
    
    // Main Category Streams (for Forms 1-6)
    'main_streams' => [
        'Sciences' => 'Sciences',
        'Commercials' => 'Commercials',
        'Humanities' => 'Humanities', 
        'Arts' => 'Arts',
    ],
    
    // Detailed Science Combinations
    'sciences' => [
        'Physics, Chemistry, Mathematics' => 'Physics, Chemistry, Mathematics (PCM)',
        'Physics, Chemistry, Biology' => 'Physics, Chemistry, Biology (PCB)',
        'Biology, Chemistry, Mathematics' => 'Biology, Chemistry, Mathematics (BCM)',
        'Physics, Biology, Mathematics' => 'Physics, Biology, Mathematics (PBM)',
        'Computer Science, Mathematics, Physics' => 'Computer Science, Mathematics, Physics (CMP)',
        'Mathematics, Physics, Geography' => 'Mathematics, Physics, Geography (MPG)',
    ],
    
    // Commercial Combinations
    'commercials' => [
        'Accounting, Business Studies, Economics' => 'Accounting, Business Studies, Economics (ABE)',
        'Accounting, Business Studies, Mathematics' => 'Accounting, Business Studies, Mathematics (ABM)',
        'Business Studies, Economics, Mathematics' => 'Business Studies, Economics, Mathematics (BEM)',
        'Accounting, Economics, Mathematics' => 'Accounting, Economics, Mathematics (AEM)',
        'Accounting, Business Studies, Geography' => 'Accounting, Business Studies, Geography (ABG)',
    ],
    
    // Humanities Combinations
    'humanities' => [
        'History, Geography, Literature' => 'History, Geography, Literature (HGL)',
        'History, Geography, Divinity' => 'History, Geography, Divinity (HGD)',
        'Literature, Divinity, History' => 'Literature, Divinity, History (LDH)',
        'Geography, Literature, Economics' => 'Geography, Literature, Economics (GLE)',
        'History, Literature, Shona' => 'History, Literature, Shona (HLS)',
    ],
    
    // Arts Combinations
    'arts' => [
        'English, French, Literature' => 'English, French, Literature (EFL)',
        'English, Shona, Literature' => 'English, Shona, Literature (ESL)',
        'Art, Design, Literature' => 'Art, Design, Literature (ADL)',
        'Music, Literature, History' => 'Music, Literature, History (MLH)',
        'Fashion & Fabrics, Art, English' => 'Fashion & Fabrics, Art, English (FAE)',
    ],
    
    // Mixed/Other Combinations
    'mixed_combinations' => [
        'Mathematics, Geography, History' => 'Mathematics, Geography, History (MGH)',
        'English, Mathematics, Geography' => 'English, Mathematics, Geography (EMG)',
        'Science, Geography, Mathematics' => 'Science, Geography, Mathematics (SGM)',
    ],
    
    // Other/Unassigned
    'other' => [
        'Other' => 'Other',
        'Unassigned' => 'Unassigned'
    ]
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $class = $_POST['class'];
    $course = $_POST['course'];
    $year = $_POST['year'];
    $email = $_POST['email'];
    $password = $_POST['password'] ? password_hash($_POST['password'], PASSWORD_DEFAULT) : $student['password'];

    // Update students table
    $updateStudentQuery = "UPDATE students SET username = ?, first_name = ?, last_name = ?, class = ?, course = ?, year = ? WHERE student_id = ?";
    $stmt = $conn->prepare($updateStudentQuery);
    $stmt->bind_param("sssssss", $username, $first_name, $last_name, $class, $course, $year, $student_id);
    
    if ($stmt->execute()) {
        $student_updated = true;
    } else {
        $error_message = "Error updating students table: " . $stmt->error;
    }
    $stmt->close();

    // Update users table (only if user_id exists and students table was updated)
    if (isset($student_updated) && $student['user_id']) {
        $user_id = $student['user_id'];
        $updateUserQuery = "UPDATE users SET email = ?, password = ? WHERE id = ?";
        $stmt = $conn->prepare($updateUserQuery);
        $stmt->bind_param("ssi", $email, $password, $user_id);

        if ($stmt->execute()) {
            $_SESSION['message'] = array('text' => 'Student updated successfully!', 'type' => 'success');
            header("Location: manage_students.php");
            exit();
        } else {
            $error_message = "Error updating users table: " . $stmt->error;
        }
        $stmt->close();
    } elseif (isset($student_updated)) {
        // Student updated but no user record
        $_SESSION['message'] = array('text' => 'Student updated successfully (no user account)!', 'type' => 'success');
        header("Location: manage_students.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student</title>
    <link rel="icon" type="image/jpeg" href="../images/logo.jpeg">
    <link rel="shortcut icon" type="image/jpeg" href="../images/logo.jpeg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --primary-light: #4895ef;
            --secondary-color: #3f37c9;
            --accent-color: #4cc9f0;
            --dark-color: #1d3557;
            --text-color: #2b2d42;
            --light-text: #8d99ae;
            --white: #fff;
            --off-white: #f8f9fa;
            --light-gray: #e9ecef;
            --border-radius: 12px;
            --input-radius: 8px;
            --card-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--off-white) 0%, var(--light-gray) 100%);
            color: var(--text-color);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: var(--white);
            padding: 1.5rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            position: relative;
            z-index: 10;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .header-logo {
            height: 45px;
            width: auto;
            border-radius: 8px;
        }

        .header h1 {
            font-size: 1.6rem;
            font-weight: 600;
            margin: 0;
        }

        .header-nav {
            display: flex;
            gap: 10px;
        }

        .container {
            max-width: 900px;
            width: 95%;
            margin: 2rem auto;
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            overflow: hidden;
            flex: 1;
        }

        .page-header {
            background: linear-gradient(to right, var(--primary-light), var(--accent-color));
            color: var(--white);
            padding: 1.5rem 2rem;
            position: relative;
        }

        .page-header h2 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .page-header h2 i {
            font-size: 1.2rem;
        }

        .form-container {
            padding: 2rem;
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

        label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-color);
            font-weight: 500;
            font-size: 0.9rem;
        }

        input, select {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid var(--light-gray);
            border-radius: var(--input-radius);
            font-size: 1rem;
            transition: var(--transition);
            background-color: var(--off-white);
        }

        input:focus, select:focus {
            outline: none;
            border-color: var(--primary-light);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.15);
            background-color: var(--white);
        }

        select {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 0.5rem center;
            background-repeat: no-repeat;
            background-size: 1.5em 1.5em;
            padding-right: 2.5rem;
        }

        /* Enhanced optgroup styling */
        optgroup {
            font-weight: bold;
            color: var(--primary-color);
            background-color: var(--off-white);
            padding: 5px 0;
        }

        optgroup option {
            font-weight: normal;
            color: var(--text-color);
            padding-left: 20px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 0.75rem 1.5rem;
            color: var(--white);
            text-decoration: none;
            border-radius: var(--input-radius);
            font-weight: 500;
            transition: var(--transition);
            border: none;
            cursor: pointer;
            font-size: 0.95rem;
        }

        .btn-primary {
            background: linear-gradient(to right, var(--primary-color), var(--primary-light));
            box-shadow: 0 4px 15px rgba(67, 97, 238, 0.2);
        }

        .btn-accent {
            background: var(--accent-color);
        }

        .btn-secondary {
            background: var(--secondary-color);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(67, 97, 238, 0.25);
        }

        .button-group {
            margin-top: 2rem;
            grid-column: span 2;
            display: flex;
            gap: 1rem;
            justify-content: center;
        }

        .error-message {
            background: #ffebee;
            color: #c62828;
            padding: 1rem;
            border-left: 4px solid #f44336;
            margin-bottom: 1rem;
            border-radius: 4px;
        }

        .student-info {
            background: var(--off-white);
            padding: 1rem;
            border-radius: var(--input-radius);
            margin-bottom: 1.5rem;
            border: 1px solid var(--light-gray);
        }

        .student-info h3 {
            color: var(--primary-color);
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
        }

        .student-info p {
            margin: 0.25rem 0;
            color: var(--light-text);
            font-size: 0.9rem;
        }

        .warning-box {
            background: #fff3cd;
            color: #856404;
            padding: 1rem;
            border-left: 4px solid #ffc107;
            margin-bottom: 1rem;
            border-radius: 4px;
        }

        .info-box {
            background: #e3f2fd;
            color: #1565c0;
            padding: 1rem;
            border-left: 4px solid #2196f3;
            margin-bottom: 1rem;
            border-radius: 4px;
            font-size: 0.9rem;
        }

        .class-preview {
            background: #f0f8ff;
            border: 2px solid var(--primary-light);
            border-radius: var(--input-radius);
            padding: 1rem;
            margin-top: 0.5rem;
            display: none;
        }

        .class-preview.show {
            display: block;
            animation: fadeIn 0.3s ease-in;
        }

        .class-badge {
            display: inline-block;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            color: white;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
            margin-right: 8px;
        }

        .stream-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-left: 5px;
        }

        .stream-gold { background: #ffd700; }
        .stream-silver { background: #c0c0c0; }
        .stream-platinum { background: #4a90e2; }
        .stream-bronze { background: #cd7f32; }
        .stream-diamond { background: #b9f2ff; }

        /* Utilities */
        .text-center { text-align: center; }
        .mb-0 { margin-bottom: 0; }
        .mt-4 { margin-top: 1.5rem; }

        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .container {
            animation: fadeIn 0.5s ease-out;
        }

        /* Responsive Design */
        @media screen and (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .form-group.full-width {
                grid-column: span 1;
            }
            
            .button-group {
                grid-column: span 1;
                flex-direction: column;
            }
            
            .header {
                flex-direction: column;
                padding: 1rem;
                gap: 1rem;
            }
            
            .header-nav {
                width: 100%;
                justify-content: center;
            }

            .container {
                width: 98%;
                margin: 1rem auto;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-left">
            <img src="../images/logo.jpeg" alt="Solid Rock   Logo" class="header-logo">
            <h1>Solid Rock  Portal</h1>
        </div>
        <div class="header-nav">
            <a href="super_admin_dashboard.php" class="btn btn-secondary">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="manage_students.php" class="btn btn-accent">
                <i class="fas fa-users"></i> All Students
            </a>
        </div>
    </header>

    <div class="container">
        <div class="page-header">
            <h2><i class="fas fa-user-edit"></i> Edit Student Profile</h2>
        </div>
        
        <div class="form-container">
            <?php if (isset($error_message)): ?>
                <div class="error-message">
                    <strong>Error:</strong> <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>

            <div class="student-info">
                <h3><i class="fas fa-id-card"></i> Student Information</h3>
                <p><strong>Student ID:</strong> <?= htmlspecialchars($student['student_id']) ?></p>
                <p><strong>Current Username:</strong> <?= htmlspecialchars($student['username']) ?></p>
                <p><strong>Current Class:</strong> <?= htmlspecialchars($student['class'] ?: 'Unassigned') ?></p>
                <?php if (!$student['user_id']): ?>
                    <div class="warning-box">
                        <i class="fas fa-exclamation-triangle"></i> <strong>Warning:</strong> This student doesn't have an associated user account. Email and password fields will not be saved.
                    </div>
                <?php endif; ?>
            </div>

            <div class="info-box">
                <i class="fas fa-info-circle"></i> <strong>School Structure:</strong> 
                <br><strong>Primary:</strong> ECD A/B, Grades 1-7 (each with Gold, Silver, Platinum, Bronze, Diamond streams)
                <br><strong>Secondary:</strong> Forms 1-6 (each with Gold, Silver, Platinum, Bronze, Diamond streams)
                <br>Select the appropriate class and subject combination for the student.
            </div>

            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="username">
                            <i class="fas fa-user"></i> Username
                        </label>
                        <input type="text" id="username" name="username" value="<?= htmlspecialchars($student['username'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email">
                            <i class="fas fa-envelope"></i> Email Address
                        </label>
                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($student['email'] ?? '') ?>" <?= $student['user_id'] ? 'required' : 'disabled' ?>>
                    </div>

                    <div class="form-group">
                        <label for="first_name">
                            <i class="fas fa-user"></i> First Name
                        </label>
                        <input type="text" id="first_name" name="first_name" value="<?= htmlspecialchars($student['first_name'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="last_name">
                            <i class="fas fa-user"></i> Last Name
                        </label>
                        <input type="text" id="last_name" name="last_name" value="<?= htmlspecialchars($student['last_name'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="class">
                            <i class="fas fa-chalkboard"></i> Class
                        </label>
                        <select id="class" name="class" required onchange="showClassPreview()">
                            <?= generateClassOptions($student['class'] ?? '') ?>
                        </select>
                        <div id="classPreview" class="class-preview">
                            <!-- Class preview will be shown here -->
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="course">
                            <i class="fas fa-book"></i> Subject Combination
                        </label>
                        <select id="course" name="course" required>
                            <option value="">Select Subject Combination</option>
                            
                            <optgroup label="📚 Primary Level Subjects">
                                <?php foreach ($course_combinations['primary_subjects'] as $value => $label): ?>
                                    <option value="<?= htmlspecialchars($value) ?>" <?= ($student['course'] === $value) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                            
                            <optgroup label=" Main Academic Streams">
                                <?php foreach ($course_combinations['main_streams'] as $value => $label): ?>
                                    <option value="<?= htmlspecialchars($value) ?>" <?= ($student['course'] === $value) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                            
                            <optgroup label=" Science Combinations">
                                <?php foreach ($course_combinations['sciences'] as $value => $label): ?>
                                    <option value="<?= htmlspecialchars($value) ?>" <?= ($student['course'] === $value) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                            
                            <optgroup label=" Commercial Combinations">
                                <?php foreach ($course_combinations['commercials'] as $value => $label): ?>
                                    <option value="<?= htmlspecialchars($value) ?>" <?= ($student['course'] === $value) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                            
                            <optgroup label=" Humanities Combinations">
                                <?php foreach ($course_combinations['humanities'] as $value => $label): ?>
                                    <option value="<?= htmlspecialchars($value) ?>" <?= ($student['course'] === $value) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                            
                            <optgroup label=" Arts Combinations">
                                <?php foreach ($course_combinations['arts'] as $value => $label): ?>
                                    <option value="<?= htmlspecialchars($value) ?>" <?= ($student['course'] === $value) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                            
                            <optgroup label=" Mixed Combinations">
                                <?php foreach ($course_combinations['mixed_combinations'] as $value => $label): ?>
                                    <option value="<?= htmlspecialchars($value) ?>" <?= ($student['course'] === $value) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                            
                            <optgroup label="❓ Other">
                                <?php foreach ($course_combinations['other'] as $value => $label): ?>
                                    <option value="<?= htmlspecialchars($value) ?>" <?= ($student['course'] === $value) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="year">
                            <i class="fas fa-calendar-alt"></i> Academic Year
                        </label>
                        <input type="number" id="year" name="year" value="<?= htmlspecialchars($student['year'] ?? date('Y')) ?>" min="2020" max="2030" required>
                    </div>

                    <div class="form-group">
                        <label for="password">
                            <i class="fas fa-lock"></i> Password (leave blank to keep unchanged)
                        </label>
                        <input type="password" id="password" name="password" placeholder="Enter new password or leave blank" <?= $student['user_id'] ? '' : 'disabled' ?>>
                    </div>

                    <div class="form-group full-width button-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Student
                        </button>
                        <a href="manage_students.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Students
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showClassPreview() {
            const classSelect = document.getElementById('class');
            const preview = document.getElementById('classPreview');
            const selectedClass = classSelect.value;

            if (selectedClass && selectedClass !== 'Unassigned') {
                let level = '';
                let type = '';
                let stream = '';
                let streamClass = '';

                // Determine level and type
                if (selectedClass.includes('ECD')) {
                    level = 'Early Childhood Development';
                    type = 'Primary School';
                } else if (selectedClass.includes('Grade')) {
                    level = 'Primary Education';
                    type = 'Primary School';
                } else if (selectedClass.includes('Form')) {
                    level = 'Secondary Education';
                    type = 'Secondary School';
                    
                    const formNumber = selectedClass.match(/Form (\d+)/);
                    if (formNumber) {
                        const num = parseInt(formNumber[1]);
                        if (num <= 2) type = 'Lower Secondary';
                        else if (num <= 4) type = 'O-Level';
                        else type = 'A-Level';
                    }
                }

                // Determine stream
                const streams = ['Gold', 'Silver', 'Platinum', 'Bronze', 'Diamond'];
                for (let s of streams) {
                    if (selectedClass.includes(s)) {
                        stream = s;
                        streamClass = `stream-${s.toLowerCase()}`;
                        break;
                    }
                }

                preview.innerHTML = `
                    <h4><i class="fas fa-info-circle"></i> Class Information</h4>
                    <p><strong>Selected Class:</strong> <span class="class-badge">${selectedClass}</span> 
                    ${stream ? `<span class="stream-indicator ${streamClass}" title="${stream} Stream"></span>` : ''}</p>
                    <p><strong>Education Level:</strong> ${level}</p>
                    <p><strong>School Type:</strong> ${type}</p>
                    ${stream ? `<p><strong>Stream:</strong> ${stream}</p>` : ''}
                `;
                preview.classList.add('show');
            } else {
                preview.classList.remove('show');
            }
        }

        // Show preview on page load if class is already selected
        document.addEventListener('DOMContentLoaded', function() {
            showClassPreview();
        });

        // Auto-suggest course combinations based on class level
        document.getElementById('class').addEventListener('change', function() {
            const selectedClass = this.value;
            const courseSelect = document.getElementById('course');
            
            // Auto-select appropriate course category based on class
            if (selectedClass.includes('ECD') || selectedClass.includes('Grade')) {
                // For primary classes, suggest primary subjects
                const primaryOptions = courseSelect.querySelectorAll('optgroup[label*="Primary"] option');
                if (primaryOptions.length > 0 && courseSelect.value === '') {
                    courseSelect.value = 'General Studies';
                }
            } else if (selectedClass.includes('Form')) {
                // For secondary classes, suggest main streams
                if (courseSelect.value === '' || courseSelect.value === 'General Studies') {
                    courseSelect.value = '';
                    // Don't auto-select for secondary as there are many options
                }
            }
        });

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const requiredFields = ['username', 'first_name', 'last_name', 'class', 'course', 'year'];
            let isValid = true;
            
            requiredFields.forEach(fieldName => {
                const field = document.querySelector(`[name="${fieldName}"]`);
                if (!field.value.trim()) {
                    field.style.borderColor = '#f44336';
                    isValid = false;
                } else {
                    field.style.borderColor = '#e9ecef';
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields.');
            }
        });

        // Real-time validation feedback
        document.querySelectorAll('input[required], select[required]').forEach(field => {
            field.addEventListener('blur', function() {
                if (this.value.trim()) {
                    this.style.borderColor = '#28a745';
                } else {
                    this.style.borderColor = '#f44336';
                }
            });
            
            field.addEventListener('input', function() {
                if (this.style.borderColor === 'rgb(244, 67, 54)' && this.value.trim()) {
                    this.style.borderColor = '#e9ecef';
                }
            });
        });
    </script>
</body>
</html>