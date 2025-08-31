<?php
session_start(); // Good practice to start session
include '../config.php'; // Make sure this path to your DB connection is correct

// Optional: Add an admin/login check here if this page should be protected
// if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
//     header("Location: ../login.php");
//     exit();
// }

// Fetch the student data (Email column removed)
$studentsQuery = "SELECT student_id, username, class FROM students ORDER BY username"; 
$studentsResult = $conn->query($studentsQuery);

// Check if the query was successful
if ($studentsResult === false) {
    die("Database query failed: " . $conn->error);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Information</title>
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
            margin: 0;
            white-space: nowrap;
        }

        /* Container & Content */
        .container {
            max-width: 1400px;
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

        .content-box:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-3px);
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
        }

        .content-body {
            padding: 1.5rem 2rem;
        }

        /* Table Styles */
        .table-container {
            overflow-x: auto;
            border-radius: var(--radius);
        }

        .table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 1rem;
        }

        .table th,
        .table td {
            padding: 1rem 1.5rem;
            text-align: left;
        }

        .table thead th {
            background-color: var(--gray-100);
            color: var(--gray-800);
            font-weight: 600;
            border-bottom: 2px solid var(--gray-300);
            position: sticky;
            top: 0;
        }

        .table tbody tr {
            transition: var(--transition);
        }

        .table tbody tr:nth-child(even) {
            background-color: var(--gray-100);
        }

        .table tbody tr:hover {
            background-color: var(--gray-200);
        }

        .table td {
            border-bottom: 1px solid var(--gray-200);
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

        /* Footer */
        .footer {
            background-color: var(--gray-800);
            color: var(--light);
            padding: 2rem;
            margin-top: auto;
        }

        .footer-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .footer p {
            margin: 0;
        }

        .footer-nav {
            display: flex;
            gap: 1.5rem;
        }

        .footer-nav a {
            color: var(--gray-300);
            text-decoration: none;
            transition: var(--transition);
        }

        .footer-nav a:hover {
            color: white;
        }

        /* Responsive Styles */
        @media screen and (max-width: 768px) {
            .header, .content-header, .content-body {
                padding: 1rem 1.5rem;
            }

            .table th, .table td {
                padding: 0.75rem 1rem;
            }

            .header h2 {
                font-size: 1.5rem;
            }

            .container {
                padding: 0 1rem;
                margin: 1.5rem auto;
            }
        }

        @media screen and (max-width: 576px) {
            .header-content, .content-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .table th, .table td {
                padding: 0.625rem 0.75rem;
                font-size: 0.9rem;
            }

            .hide-xs {
                display: none;
            }

            .footer {
                padding: 1.5rem 1rem;
            }

            .footer-content {
                flex-direction: column;
                gap: 1.5rem;
                text-align: center;
            }

            .footer-nav {
                justify-content: center;
                flex-wrap: wrap;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <h2>Student Information Portal</h2>
            <a href="manage_students.php" class="btn"> <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
                Dashboard
            </a>
        </div>
    </header>

    <div class="container">
        <div class="content-box">
            <div class="content-header">
                <h3>Students Directory</h3>
                <div>
                    <button class="btn btn-outline btn-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        Add Student
                    </button>
                    <button class="btn btn-outline btn-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="7 10 12 15 17 10"></polyline>
                            <line x1="12" y1="15" x2="12" y2="3"></line>
                        </svg>
                        Export
                    </button>
                </div>
            </div>
            
            <div class="content-body">
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Full Name</th>
                                <th class="hide-xs">Class</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($studentsResult && $studentsResult->num_rows > 0): ?>
                                <?php while ($student = $studentsResult->fetch_assoc()): ?>
                                    <tr>
                                        <td>#<?= htmlspecialchars($student['student_id']) ?></td>
                                        <td><?= htmlspecialchars($student['username']) ?></td>
                                        <td class="hide-xs">
                                            <span class="badge badge-primary"><?= htmlspecialchars($student['class']) ?></span>
                                        </td>
                                        <td>
                                            <form action="view_student_details.php" method="GET" style="display:inline;">
                                                <input type="hidden" name="student_id" value="<?= $student['student_id'] ?>">
                                                <button type="submit" class="btn btn-sm">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                        <circle cx="12" cy="12" r="3"></circle>
                                                    </svg>
                                                    View
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; padding: 2rem;">No students found.</td>
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
            <p>&copy; <?php echo date("Y"); ?> Wisetech College Portal</p>
            <div class="footer-nav">
                <a href="#">Privacy Policy</a>
                <a href="#">Terms of Service</a>
                <a href="#">Help Center</a>
            </div>
        </div>
    </footer>
</body>
</html>
<?php 
// Close the database connection
if (isset($conn)) {
    $conn->close();
}
?>