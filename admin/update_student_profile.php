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

// Check if the user is an admin or staff
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'staff')) {
    header("Location: ../login.php");
    exit();
}

// Fetch all students with fields that exist in your database
$studentsQuery = "SELECT student_id, user_id, username, first_name, last_name, course, year, class, date_of_birth, phone, address, email, status FROM students";
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
    <title>Update Student Profiles</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --primary-light: #4895ef;
            --secondary: #3f37c9;
            --accent: #4cc9f0;
            --dark: #2b2d42;
            --light: #f8f9fa;
            --gray: #e9ecef;
            --success: #4ade80;
            --white: #ffffff;
            --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 12px rgba(0, 0, 0, 0.08);
            --shadow-lg: 0 8px 24px rgba(0, 0, 0, 0.12);
            --radius-sm: 0.375rem;
            --radius-md: 0.75rem;
            --radius-lg: 1rem;
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background-color: var(--gray);
            color: var(--dark);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: var(--white);
            padding: 1.5rem 2rem;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: var(--shadow-md);
        }

        .header-content {
            max-width: 1300px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .header-title {
            font-size: 1.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .header-title i {
            font-size: 1.75rem;
        }

        .header-nav {
            display: flex;
            gap: 1rem;
        }

        .nav-btn {
            background-color: rgba(255, 255, 255, 0.15);
            color: var(--white);
            border: none;
            padding: 0.6rem 1.2rem;
            border-radius: var(--radius-sm);
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: var(--transition);
            backdrop-filter: blur(5px);
        }

        .nav-btn:hover {
            background-color: rgba(255, 255, 255, 0.25);
            transform: translateY(-2px);
        }

        .container {
            max-width: 1300px;
            width: 100%;
            margin: 2rem auto;
            padding: 0 1.5rem;
            flex: 1;
        }

        .page-title {
            margin-bottom: 1.5rem;
            font-weight: 600;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .page-title i {
            color: var(--primary);
        }

        .card {
            background: var(--white);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
            transition: var(--transition);
        }

        .card:hover {
            box-shadow: var(--shadow-md);
        }

        .card-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--gray);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--dark);
            margin: 0;
        }

        .card-body {
            padding: 0;
        }

        .search-box {
            position: relative;
            max-width: 300px;
            width: 100%;
        }

        .search-input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            border: 1px solid var(--gray);
            border-radius: var(--radius-sm);
            font-size: 0.9rem;
            transition: var(--transition);
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary-light);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.15);
        }

        .search-icon {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: #adb5bd;
        }

        .table-container {
            overflow-x: auto;
            border-radius: var(--radius-md);
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        th, td {
            padding: 1rem 1.5rem;
            text-align: left;
            border-bottom: 1px solid var(--gray);
        }

        th {
            background-color: var(--light);
            color: var(--dark);
            font-weight: 600;
            white-space: nowrap;
            position: sticky;
            top: 0;
        }

        tbody tr {
            transition: var(--transition);
        }

        tbody tr:hover {
            background-color: rgba(67, 97, 238, 0.05);
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        .student-id {
            font-weight: 600;
            color: var(--dark);
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-active {
            background-color: rgba(74, 222, 128, 0.15);
            color: #15803d;
        }

        .status-inactive {
            background-color: rgba(239, 68, 68, 0.15);
            color: #dc2626;
        }

        .status-graduated {
            background-color: rgba(59, 130, 246, 0.15);
            color: #2563eb;
        }

        .status-transfer {
            background-color: rgba(245, 158, 11, 0.15);
            color: #d97706;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 1rem;
            border-radius: var(--radius-sm);
            font-weight: 500;
            text-decoration: none;
            transition: var(--transition);
            border: none;
            cursor: pointer;
        }

        .btn-primary {
            background-color: var(--primary);
            color: var(--white);
        }

        .btn-primary:hover {
            background-color: var(--secondary);
            transform: translateY(-2px);
        }

        .btn-outline {
            background-color: transparent;
            border: 1px solid var(--primary);
            color: var(--primary);
        }

        .btn-outline:hover {
            background-color: var(--primary);
            color: var(--white);
        }

        .btn-icon {
            width: 2.25rem;
            height: 2.25rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            padding: 0;
        }

        .actions {
            display: flex;
            gap: 0.5rem;
            justify-content: flex-end;
        }

        .footer {
            background-color: var(--white);
            padding: 1.5rem;
            text-align: center;
            margin-top: auto;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.05);
        }

        .footer-content {
            max-width: 1300px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .footer-logo {
            font-weight: 600;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .footer-nav {
            display: flex;
            gap: 1.5rem;
        }

        .footer-link {
            color: var(--dark);
            text-decoration: none;
            transition: var(--transition);
        }

        .footer-link:hover {
            color: var(--primary);
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.25rem;
            margin: 1.5rem 0;
        }

        .page-item {
            list-style: none;
        }

        .page-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.25rem;
            height: 2.25rem;
            border-radius: var(--radius-sm);
            text-decoration: none;
            color: var(--dark);
            font-weight: 500;
            transition: var(--transition);
        }

        .page-link:hover {
            background-color: var(--gray);
        }

        .page-link.active {
            background-color: var(--primary);
            color: var(--white);
        }

        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 3rem;
            text-align: center;
        }

        .empty-state i {
            font-size: 3rem;
            color: #d1d5db;
            margin-bottom: 1rem;
        }

        .empty-state-text {
            color: #6b7280;
            margin-bottom: 1.5rem;
        }

        @media screen and (max-width: 992px) {
            .header-content {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .card-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .search-box {
                max-width: 100%;
            }
        }

        @media screen and (max-width: 768px) {
            .container {
                padding: 0 1rem;
                margin: 1rem auto;
            }
            
            th, td {
                padding: 0.75rem 1rem;
            }
            
            .footer-content {
                flex-direction: column;
            }
            
            .footer-nav {
                margin-top: 1rem;
                justify-content: center;
            }
        }

        @media screen and (max-width: 640px) {
            .table-container {
                margin: 0 -1rem;
                width: calc(100% + 2rem);
                border-radius: 0;
            }
            
            .card {
                border-radius: 0;
                box-shadow: none;
            }
            
            .hide-sm {
                display: none;
            }
            
            th, td {
                padding: 0.75rem 0.75rem;
            }
            
            .actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="header-title">
                <i class="fas fa-graduation-cap"></i>
                <span>WiseTech College</span>
            </div>
            <div class="header-nav">
                <a href="manage_students.php" class="nav-btn">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="#" class="nav-btn">
                    <i class="fas fa-bell"></i>
                    <span>Notifications</span>
                </a>
            </div>
        </div>
    </header>

    <div class="container">
        <h1 class="page-title">
            <i class="fas fa-users"></i>
            Student Profiles
        </h1>
        
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Manage Students</h2>
                <div class="search-box">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" class="search-input" placeholder="Search students...">
                </div>
            </div>
            
            <div class="card-body">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>First Name</th>
                                <th>Last Name</th>
                                <th>Username</th>
                                <th class="hide-sm">Course</th>
                                <th class="hide-sm">Year</th>
                                <th>Class</th>
                                <th class="hide-sm">Date of Birth</th>
                                <th class="hide-sm">Email</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($studentsResult->num_rows > 0): ?>
                                <?php while ($student = $studentsResult->fetch_assoc()): ?>
                                    <tr>
                                        <td class="student-id"><?= safe_display($student['student_id']) ?></td>
                                        <td><?= safe_display($student['first_name']) ?></td>
                                        <td><?= safe_display($student['last_name']) ?></td>
                                        <td><?= safe_display($student['username'], 'No Username') ?></td>
                                        <td class="hide-sm"><?= safe_display($student['course'], 'Not Assigned') ?></td>
                                        <td class="hide-sm"><?= safe_display($student['year'], 'Not Set') ?></td>
                                        <td><?= safe_display($student['class'], 'Not Assigned') ?></td>
                                        <td class="hide-sm"><?= safe_display($student['date_of_birth'], 'Not Set') ?></td>
                                        <td class="hide-sm"><?= safe_display($student['email'], 'No Email') ?></td>
                                        <td>
                                            <?php 
                                            $status = strtolower($student['status']);
                                            $status_class = 'status-active';
                                            $status_icon = 'fas fa-circle';
                                            
                                            switch($status) {
                                                case 'inactive':
                                                    $status_class = 'status-inactive';
                                                    $status_icon = 'fas fa-circle';
                                                    break;
                                                case 'graduated':
                                                    $status_class = 'status-graduated';
                                                    $status_icon = 'fas fa-graduation-cap';
                                                    break;
                                                case 'transfer':
                                                    $status_class = 'status-transfer';
                                                    $status_icon = 'fas fa-exchange-alt';
                                                    break;
                                                default:
                                                    $status_class = 'status-active';
                                                    $status_icon = 'fas fa-circle';
                                            }
                                            ?>
                                            <span class="status-badge <?= $status_class ?>">
                                                <i class="<?= $status_icon ?>"></i>
                                                <?= ucfirst(safe_display($student['status'], 'Active')) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="actions">
                                                <a href="edit_student_profile.php?student_id=<?= $student['student_id'] ?>" class="btn btn-primary">
                                                    <i class="fas fa-pen"></i>
                                                    Edit
                                                </a>
                                                <a href="view_student.php?student_id=<?= $student['student_id'] ?>" class="btn btn-outline btn-icon">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="11">
                                        <div class="empty-state">
                                            <i class="fas fa-user-slash"></i>
                                            <p class="empty-state-text">No students found in the database.</p>
                                            <a href="add_student.php" class="btn btn-primary">
                                                <i class="fas fa-plus"></i>
                                                Add Student
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <?php if ($studentsResult->num_rows > 0): ?>
        <ul class="pagination">
            <li class="page-item">
                <a href="#" class="page-link">
                    <i class="fas fa-chevron-left"></i>
                </a>
            </li>
            <li class="page-item"><a href="#" class="page-link active">1</a></li>
            <li class="page-item"><a href="#" class="page-link">2</a></li>
            <li class="page-item"><a href="#" class="page-link">3</a></li>
            <li class="page-item">
                <a href="#" class="page-link">
                <i class="fas fa-chevron-right"></i>
                </a>
            </li>
        </ul>
        <?php endif; ?>
    </div>

    <footer class="footer">
        <div class="footer-content">
            <div class="footer-logo">
                <i class="fas fa-graduation-cap"></i>
                <span>WiseTech College Portal</span>
            </div>
            <div class="footer-nav">
                <a href="#" class="footer-link">About</a>
                <a href="#" class="footer-link">Privacy Policy</a>
                <a href="#" class="footer-link">Help Center</a>
                <a href="#" class="footer-link">Contact</a>
            </div>
            <p>&copy; <?php echo date("Y"); ?> WiseTech College. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // Simple search functionality
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.querySelector('.search-input');
            const tableRows = document.querySelectorAll('tbody tr');
            
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                
                tableRows.forEach(row => {
                    if (row.querySelector('.empty-state')) return; // Skip empty state row
                    
                    const studentId = row.cells[0].textContent.toLowerCase();
                    const firstName = row.cells[1].textContent.toLowerCase();
                    const lastName = row.cells[2].textContent.toLowerCase();
                    const username = row.cells[3].textContent.toLowerCase();
                    const course = row.cells[4] ? row.cells[4].textContent.toLowerCase() : '';
                    const email = row.cells[8] ? row.cells[8].textContent.toLowerCase() : '';
                    
                    const matches = studentId.includes(searchTerm) || 
                                  firstName.includes(searchTerm) || 
                                  lastName.includes(searchTerm) || 
                                  username.includes(searchTerm) ||
                                  course.includes(searchTerm) ||
                                  email.includes(searchTerm);
                    
                    row.style.display = matches ? '' : 'none';
                });
            });
        });
    </script>
</body>
</html>
<?php
$conn->close();
?>