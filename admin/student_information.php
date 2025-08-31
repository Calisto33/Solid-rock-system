<?php
session_start();
include '../config.php'; // Database connection

// Helper function to safely display values and handle NULL
function safe_display($value, $default = 'N/A') {
    if ($value === null || $value === '') {
        return htmlspecialchars($default);
    }
    return htmlspecialchars($value);
}

// First, let's check what columns exist in the students table
$columnsQuery = "SHOW COLUMNS FROM students";
$columnsResult = $conn->query($columnsQuery);
$availableColumns = [];
if ($columnsResult) {
    while ($col = $columnsResult->fetch_assoc()) {
        $availableColumns[] = $col['Field'];
    }
}

// Check what columns exist in the users table
$userColumnsQuery = "SHOW COLUMNS FROM users";
$userColumnsResult = $conn->query($userColumnsQuery);
$availableUserColumns = [];
if ($userColumnsResult) {
    while ($col = $userColumnsResult->fetch_assoc()) {
        $availableUserColumns[] = $col['Field'];
    }
}

// Build the query based on available columns
$selectFields = ['s.id', 's.student_id'];

// Add columns only if they exist
$possibleColumns = ['username', 'first_name', 'last_name', 'course', 'year', 'class', 'created_at'];
foreach ($possibleColumns as $col) {
    if (in_array($col, $availableColumns)) {
        $selectFields[] = 's.' . $col;
    }
}

// Try to join with users table if possible
$joinClause = '';
$userFields = [];
if (in_array('user_id', $availableColumns)) {
    $joinClause = 'LEFT JOIN users u ON s.user_id = u.id';
    
    // Only add user fields that actually exist
    $possibleUserColumns = ['email', 'role', 'last_login', 'created_at'];
    foreach ($possibleUserColumns as $col) {
        if (in_array($col, $availableUserColumns)) {
            $userFields[] = 'u.' . $col . ' as user_' . $col;
        }
    }
} elseif (in_array('email', $availableColumns)) {
    // If email is directly in students table
    $selectFields[] = 's.email';
}

$allFields = array_merge($selectFields, $userFields);

// Fetch all students with available information
$studentsQuery = "
    SELECT " . implode(', ', $allFields) . "
    FROM students s
    " . $joinClause . "
    ORDER BY " . (in_array('class', $availableColumns) ? 's.class ASC, ' : '') . "s.student_id ASC";

$studentsResult = $conn->query($studentsQuery);

if (!$studentsResult) {
    die("Error fetching students: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Information Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Modern Color Scheme & Variables */
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --secondary: #7209b7;
            --accent: #4cc9f0;
            --success: #2ec4b6;
            --warning: #ff9f1c;
            --danger: #e71d36;
            --light: #f8f9fa;
            --dark: #212529;
            --gray-100: #f8f9fa;
            --gray-200: #e9ecef;
            --gray-300: #dee2e6;
            --gray-600: #6c757d;
            --gray-800: #343a40;
            --shadow-sm: 0 2px 4px rgba(0, 0, 0, 0.05);
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.1);
            --radius: 0.5rem;
            --radius-lg: 1rem;
            --transition: all 0.3s ease;
        }

        /* Reset & Base Styles */
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

        /* Header Styles */
        .header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 1.25rem 2rem;
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header-content {
            max-width: 1600px;
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
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Container & Content */
        .container {
            max-width: 1600px;
            width: 100%;
            margin: 2rem auto;
            padding: 0 2rem;
            flex: 1;
        }

        .content-box {
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
            overflow: hidden;
            transition: var(--transition);
        }

        .content-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .content-header h3 {
            color: var(--primary);
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .content-body {
            padding: 0;
        }

        /* Controls */
        .controls {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .search-box {
            position: relative;
            max-width: 300px;
            flex: 1;
        }

        .search-input {
            width: 100%;
            padding: 0.625rem 1rem 0.625rem 2.5rem;
            border: 1px solid var(--gray-300);
            border-radius: var(--radius);
            font-size: 0.9rem;
            transition: var(--transition);
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }

        .search-icon {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-600);
        }

        /* Table Styles */
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

        /* Button Styles */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.625rem 1.25rem;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: var(--radius);
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: var(--transition);
            font-size: 0.95rem;
            line-height: 1.4;
            box-shadow: var(--shadow-sm);
        }

        .btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .btn-outline {
            background-color: transparent;
            border: 1px solid var(--primary);
            color: var(--primary);
        }

        .btn-outline:hover {
            background-color: var(--primary);
            color: white;
        }

        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
        }

        .btn-success {
            background-color: var(--success);
        }

        .btn-warning {
            background-color: var(--warning);
        }

        /* Badge & Status */
        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 2rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .badge-primary {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary);
        }

        .badge-success {
            background-color: rgba(46, 196, 182, 0.1);
            color: var(--success);
        }

        .badge-warning {
            background-color: rgba(255, 159, 28, 0.1);
            color: var(--warning);
        }

        /* Student Info Cell */
        .student-info {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .student-name {
            font-weight: 600;
            color: var(--dark);
        }

        .student-id {
            font-size: 0.85rem;
            color: var(--gray-600);
        }

        /* Actions */
        .actions {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        /* Statistics */
        .stats {
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
            border-left: 4px solid var(--primary);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--gray-600);
            font-size: 0.9rem;
        }

        /* Debug Info */
        .debug-info {
            background: #f0f0f0;
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: var(--radius);
            font-family: monospace;
            font-size: 0.85rem;
        }

        /* Footer */
        .footer {
            background-color: var(--gray-800);
            color: var(--light);
            padding: 2rem;
            margin-top: auto;
        }

        .footer-content {
            max-width: 1600px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        /* Responsive Styles */
        @media screen and (max-width: 768px) {
            .header, .content-header, .controls {
                padding: 1rem 1.5rem;
            }

            .table th, .table td {
                padding: 0.75rem 1rem;
            }

            .container {
                padding: 0 1rem;
                margin: 1.5rem auto;
            }
        }

        @media screen and (max-width: 576px) {
            .hide-xs {
                display: none;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <h2>
                <i class="fas fa-graduation-cap"></i>
                Student Information Portal
            </h2>
            <div style="display: flex; gap: 1rem;">
                <a href="manage_students.php" class="btn">
                    <i class="fas fa-home"></i>
                    Dashboard
                </a>
            </div>
        </div>
    </header>

    <div class="container">


        <!-- Statistics Cards -->
        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?= $studentsResult->num_rows ?></div>
                <div class="stat-label">Total Students</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">
                    <?php
                    // Count unique classes
                    mysqli_data_seek($studentsResult, 0);
                    $classes = [];
                    while ($row = $studentsResult->fetch_assoc()) {
                        if ($row['class']) $classes[] = $row['class'];
                    }
                    echo count(array_unique($classes));
                    mysqli_data_seek($studentsResult, 0);
                    ?>
                </div>
                <div class="stat-label">Active Classes</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">
                    <?php
                    // Count students with complete profiles
                    mysqli_data_seek($studentsResult, 0);
                    $complete = 0;
                    while ($row = $studentsResult->fetch_assoc()) {
                        if ($row['first_name'] && $row['last_name'] && $row['user_email']) {
                            $complete++;
                        }
                    }
                    echo $complete;
                    mysqli_data_seek($studentsResult, 0);
                    ?>
                </div>
                <div class="stat-label">Complete Profiles</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">
                    <?php
                    // Count students with email accounts
                    mysqli_data_seek($studentsResult, 0);
                    $withEmail = 0;
                    while ($row = $studentsResult->fetch_assoc()) {
                        if ($row['user_email']) {
                            $withEmail++;
                        }
                    }
                    echo $withEmail;
                    mysqli_data_seek($studentsResult, 0);
                    ?>
                </div>
                <div class="stat-label">Have Email Accounts</div>
            </div>
        </div>

        <div class="content-box">
            <div class="content-header">
                <h3>
                    <i class="fas fa-users"></i>
                    Students Directory
                </h3>
            </div>

            <div class="controls">
                <div class="search-box">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" class="search-input" placeholder="Search students..." id="searchInput">
                </div>
                <div style="display: flex; gap: 1rem;">
                    <?php if (in_array('class', $availableColumns)): ?>
                        <select class="filter-select" id="classFilter">
                            <option value="">All Classes</option>
                            <?php
                            mysqli_data_seek($studentsResult, 0);
                            $classes = [];
                            while ($row = $studentsResult->fetch_assoc()) {
                                if ($row['class'] && !in_array($row['class'], $classes)) {
                                    $classes[] = $row['class'];
                                }
                            }
                            sort($classes);
                            foreach ($classes as $class) {
                                echo "<option value='" . htmlspecialchars($class) . "'>" . htmlspecialchars($class) . "</option>";
                            }
                            mysqli_data_seek($studentsResult, 0);
                            ?>
                        </select>
                    <?php endif; ?>
                    
                    <?php if (in_array('year', $availableColumns)): ?>
                        <select class="filter-select" id="yearFilter">
                            <option value="">All Years</option>
                            <?php
                            mysqli_data_seek($studentsResult, 0);
                            $years = [];
                            while ($row = $studentsResult->fetch_assoc()) {
                                if ($row['year'] && !in_array($row['year'], $years)) {
                                    $years[] = $row['year'];
                                }
                            }
                            sort($years);
                            foreach ($years as $year) {
                                echo "<option value='" . htmlspecialchars($year) . "'>" . htmlspecialchars($year) . "</option>";
                            }
                            mysqli_data_seek($studentsResult, 0);
                            ?>
                        </select>
                    <?php endif; ?>
                    
                    <button class="btn btn-outline btn-sm" onclick="window.print()">
                        <i class="fas fa-print"></i>
                        Print
                    </button>
                </div>
            </div>
            
            <div class="content-body">
                <div class="table-container">
                    <table class="table" id="studentsTable">
                        <thead>
                            <tr>
                                <th>Student Information</th>
                                <th>Academic Details</th>
                                <th class="hide-xs">Account Information</th>
                                <th class="hide-xs">Registration</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($studentsResult->num_rows > 0): ?>
                                <?php while ($student = $studentsResult->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <div class="student-info">
                                                <div class="student-name">
                                                    <?php 
                                                    $firstName = $student['first_name'] ?? '';
                                                    $lastName = $student['last_name'] ?? '';
                                                    $fullName = trim($firstName . ' ' . $lastName);
                                                    echo safe_display($fullName ?: $student['username'] ?: 'Unnamed Student');
                                                    ?>
                                                </div>
                                                <div class="student-id">ID: <?= safe_display($student['student_id']) ?></div>
                                                <div class="student-id">Username: <?= safe_display($student['username']) ?></div>
                                            </div>
                                        </td>
                                        
                                        <td>
                                            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                                                <span class="badge badge-primary"><?= safe_display($student['class'], 'No Class') ?></span>
                                                <div style="font-size: 0.85rem;">
                                                    <div><strong>Course:</strong> <?= safe_display($student['course'], 'Not Assigned') ?></div>
                                                    <div><strong>Year:</strong> <?= safe_display($student['year'], 'Not Set') ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        
                                        <td class="hide-xs">
                                            <div style="font-size: 0.85rem; color: var(--gray-600);">
                                                <div><i class="fas fa-envelope"></i> <?= safe_display($student['user_email'], 'No Email') ?></div>
                                                <div><i class="fas fa-user-tag"></i> Role: <?= safe_display($student['user_role'], 'Student') ?></div>
                                                <div>
                                                    <?php if ($student['user_email']): ?>
                                                        <span class="badge badge-success">Has Account</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-warning">No Account</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        
                                        <td class="hide-xs">
                                            <div style="font-size: 0.85rem; color: var(--gray-600);">
                                                <div><strong>Student Added:</strong> 
                                                    <?= safe_display($student['created_at'] ? date('M d, Y', strtotime($student['created_at'])) : '', 'Unknown') ?>
                                                </div>
                                                <div><strong>Account Created:</strong> 
                                                    <?= safe_display($student['user_created_at'] ? date('M d, Y', strtotime($student['user_created_at'])) : '', 'No Account') ?>
                                                </div>
                                                <div><strong>Profile:</strong> 
                                                    <?php if ($student['first_name'] && $student['last_name'] && $student['user_email']): ?>
                                                        <span class="badge badge-success">Complete</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-warning">Incomplete</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        
                                        <td>
                                            <div class="actions">
                                                <a href="view_student_details.php?id=<?= $student['id'] ?>" class="btn btn-sm" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="edit_student_profile.php?id=<?= $student['id'] ?>" class="btn btn-sm btn-warning" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 3rem;">
                                        <i class="fas fa-users" style="font-size: 3rem; color: var(--gray-300); margin-bottom: 1rem;"></i>
                                        <p style="color: var(--gray-600); margin-bottom: 1rem;">No students found in the database.</p>
                                        <a href="add_student.php" class="btn">
                                            <i class="fas fa-plus"></i>
                                            Add First Student
                                        </a>
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
            <p>&copy; <?php echo date("Y"); ?> Wisetech College Portal - All rights reserved</p>
        </div>
    </footer>

    <script>
        // Search and Filter Functionality
        const searchInput = document.getElementById('searchInput');
        const classFilter = document.getElementById('classFilter');
        const yearFilter = document.getElementById('yearFilter');
        const table = document.getElementById('studentsTable');

        function filterTable() {
            const searchTerm = searchInput.value.toLowerCase();
            const selectedClass = classFilter ? classFilter.value : '';
            const selectedYear = yearFilter ? yearFilter.value : '';
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

            for (let row of rows) {
                const cells = row.getElementsByTagName('td');
                if (cells.length === 0) continue;

                const studentInfo = cells[0].textContent.toLowerCase();
                const academicInfo = cells[1].textContent;
                const accountInfo = cells[2] ? cells[2].textContent.toLowerCase() : '';
                
                const matchesSearch = studentInfo.includes(searchTerm) || accountInfo.includes(searchTerm);
                const matchesClass = !selectedClass || academicInfo.includes(selectedClass);
                const matchesYear = !selectedYear || academicInfo.includes(selectedYear);

                if (matchesSearch && matchesClass && matchesYear) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            }
        }

        searchInput.addEventListener('input', filterTable);
        if (classFilter) classFilter.addEventListener('change', filterTable);
        if (yearFilter) yearFilter.addEventListener('change', filterTable);
    </script>
</body>
</html>
<?php
$conn->close();
?>