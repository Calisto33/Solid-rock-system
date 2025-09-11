<?php
session_start();
include '../config.php';

// Verify super admin session
if (!isset($_SESSION['super_admin_id'])) {
    header("Location: super_admin_login.php");
    exit();
}

// Fetch student ID
$student_id = $_GET['student_id'] ?? null;
if (!$student_id) {
    die("Error: Student ID is missing.");
}

// Fetch student details
$studentQuery = "SELECT username, class FROM students WHERE student_id = ?";
$stmt = $conn->prepare($studentQuery);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
if (!$student) {
    die("Error: Student not found.");
}

// Fetch results for the student --- THIS QUERY HAS BEEN CORRECTED ---
$resultsQuery = "
    SELECT ts.subject_name, r.term, r.year, r.final_mark, r.final_grade, r.comments
    FROM results r
    JOIN table_subject ts ON r.subject_id = ts.subject_id
    WHERE r.student_id = ?
    ORDER BY r.year, r.term, ts.subject_name";
$stmt = $conn->prepare($resultsQuery);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$results = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/jpeg" href="../images/logo.jpeg">
    <link rel="shortcut icon" type="image/jpeg" href="../images/logo.jpeg">
    <title>Student Results | Solid Rock </title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2563eb;
            --primary-dark: #1e40af;
            --secondary-color: #0f172a;
            --accent-color: #3b82f6;
            --accent-light: #93c5fd;
            --text-color: #1e293b;
            --text-light: #64748b;
            --white: #ffffff;
            --off-white: #f8fafc;
            --gray-light: #f1f5f9;
            --gray-mid: #e2e8f0;
            --border-radius: 0.75rem;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --transition: all 0.3s ease;
            --font-main: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: var(--font-main);
            background: var(--off-white);
            color: var(--text-color);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            line-height: 1.6;
        }

        .header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: var(--white);
            padding: 1.25rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow-md);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .header-logo {
            height: 45px;
            width: auto;
            border-radius: 8px;
            box-shadow: var(--shadow-sm);
        }

        .header-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
        }

        .header-nav {
            display: flex;
            gap: 1rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-weight: 500;
            padding: 0.6rem 1.25rem;
            border-radius: var(--border-radius);
            text-decoration: none;
            transition: var(--transition);
            cursor: pointer;
            border: none;
            font-size: 0.95rem;
        }

        .btn-primary {
            background-color: var(--white);
            color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: var(--gray-light);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background-color: var(--accent-color);
            color: var(--white);
        }

        .btn-secondary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .container {
            max-width: 1200px;
            width: 92%;
            margin: 2rem auto;
            flex: 1;
        }

        .card {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
            overflow: hidden;
            transition: var(--transition);
        }

        .card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-3px);
        }

        .card-header {
            background: var(--gray-light);
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--gray-mid);
        }

        .student-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .student-name {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--primary-dark);
            margin: 0;
        }

        .student-class {
            display: inline-block;
            padding: 0.35rem 0.75rem;
            background-color: var(--accent-light);
            color: var(--primary-dark);
            border-radius: var(--border-radius);
            font-weight: 500;
            font-size: 0.9rem;
        }

        .card-body {
            padding: 0 1rem 1.5rem;
        }

        .table-wrapper {
            overflow-x: auto;
            margin-top: 1rem;
            border-radius: calc(var(--border-radius) - 4px);
            box-shadow: var(--shadow-sm);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            white-space: nowrap;
        }

        th, td {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--gray-mid);
        }

        th {
            background-color: var(--primary-color);
            color: var(--white);
            font-weight: 500;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.05em;
        }

        th:first-child {
            border-top-left-radius: 0.5rem;
        }

        th:last-child {
            border-top-right-radius: 0.5rem;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover {
            background-color: var(--gray-light);
        }

        .grade-cell {
            font-weight: 600;
        }

        .actions {
            margin-top: 2rem;
            display: flex;
            justify-content: center;
        }

        .footer {
            background-color: var(--secondary-color);
            color: var(--white);
            text-align: center;
            padding: 1.5rem;
            margin-top: 3rem;
        }

        .footer-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .footer-logo {
            height: 40px;
            border-radius: 8px;
        }

        .footer-text {
            opacity: 0.8;
            font-size: 0.9rem;
        }

        /* Mobile Menu */
        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            color: var(--white);
            font-size: 1.5rem;
            cursor: pointer;
        }

        /* Responsive Design */
        @media screen and (max-width: 992px) {
            th, td {
                padding: 0.9rem 1.25rem;
            }
        }

        @media screen and (max-width: 768px) {
            .header {
                padding: 1rem 1.5rem;
            }

            .header-title {
                font-size: 1.25rem;
            }

            .mobile-menu-btn {
                display: block;
            }

            .header-nav {
                position: fixed;
                top: 73px;
                right: -100%;
                width: 70%;
                height: calc(100vh - 73px);
                background-color: var(--secondary-color);
                flex-direction: column;
                padding: 2rem;
                transition: right 0.3s ease;
                z-index: 99;
            }

            .header-nav.active {
                right: 0;
            }

            th, td {
                padding: 0.8rem 1rem;
                font-size: 0.9rem;
            }

            .student-name {
                font-size: 1.25rem;
            }
        }

        @media screen and (max-width: 576px) {
            .container {
                width: 96%;
                margin: 1rem auto;
            }

            .card-header {
                padding: 1.25rem 1.5rem;
            }

            .student-info {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }

            th, td {
                padding: 0.75rem 0.85rem;
                font-size: 0.85rem;
            }

            .actions {
                margin-top: 1.5rem;
            }

            .btn {
                padding: 0.5rem 1rem;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-left">
            <img src="../images/logo.jpeg" alt="Solid Rock Logo" class="header-logo">
            <h1 class="header-title">Student Results</h1>
        </div>
        
        <button class="mobile-menu-btn" id="mobileMenuBtn">
            <i class="fas fa-bars"></i>
        </button>
        
        <nav class="header-nav" id="headerNav">
            <a href="super_admin_dashboard.php" class="btn btn-primary">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="../logout.php" class="btn btn-secondary">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </nav>
    </header>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <div class="student-info">
                    <h2 class="student-name">
                        <i class="fas fa-user-graduate"></i>
                        <?= htmlspecialchars($student['username']) ?>
                    </h2>
                    <span class="student-class">
                        <i class="fas fa-users"></i>
                        Class: <?= htmlspecialchars($student['class']) ?>
                    </span>
                </div>
            </div>
            
            <div class="card-body">
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th><i class="fas fa-book"></i> Subject</th>
                                <th><i class="fas fa-calendar-alt"></i> Term</th>
                                <th><i class="fas fa-calendar-day"></i> Year</th>
                                <th><i class="fas fa-clipboard-check"></i> Final Mark</th>
                                <th><i class="fas fa-award"></i> Final Grade</th>
                                <th><i class="fas fa-comment"></i> Comments</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if ($results->num_rows > 0):
                                while ($row = $results->fetch_assoc()): 
                            ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['subject_name']) ?></td>
                                    <td><?= htmlspecialchars($row['term']) ?></td>
                                    <td><?= htmlspecialchars($row['year']) ?></td>
                                    <td><?= htmlspecialchars($row['final_mark']) ?></td>
                                    <td class="grade-cell"><?= htmlspecialchars($row['final_grade']) ?></td>
                                    <td><?= htmlspecialchars($row['comments']) ?></td>
                                </tr>
                            <?php 
                                endwhile; 
                            else: 
                            ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 2rem;">No results found for this student</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="actions">
                    <a href="student_records.php" class="btn btn-primary">
                        <i class="fas fa-arrow-left"></i> Back to Student Records
                    </a>
                </div>
            </div>
        </div>
    </div>

    <footer class="footer">
        <div class="footer-content">
            <img src="../images/logo.jpg" alt="Solid Rock  Logo" class="footer-logo">
            <p class="footer-text">&copy; <?php echo date("Y"); ?> Mirilax-Scales Portal. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // Mobile menu toggle
        document.getElementById('mobileMenuBtn').addEventListener('click', function() {
            document.getElementById('headerNav').classList.toggle('active');
            
            // Change icon based on menu state
            const icon = this.querySelector('i');
            if (icon.classList.contains('fa-bars')) {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-times');
            } else {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
        });
    </script>
</body>
</html>

<?php $stmt->close(); $conn->close(); ?>