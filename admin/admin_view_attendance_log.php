<?php
session_start();
// IMPORTANT: Set your default timezone if not already set globally
date_default_timezone_set('Africa/Harare'); 

include '../config.php'; // Database connection

// Ensure the user is logged in as admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

// --- Get filter parameters from GET request ---
$filter_date = $_GET['filter_date'] ?? date("Y-m-d"); // Default to today
$filter_class = $_GET['filter_class'] ?? ''; // Empty string for 'All Classes'
$filter_status = $_GET['filter_status'] ?? ''; // Empty string for 'All Statuses'

$classes = [];
$classQuery = "SELECT DISTINCT class FROM students ORDER BY class ASC";
$classResult = $conn->query($classQuery);
if ($classResult) {
    while ($row = $classResult->fetch_assoc()) {
        $classes[] = $row['class'];
    }
}

// --- FIXED: Updated column names to match database structure ---
$sql = "SELECT a.attendance_date, s.student_id, s.username, a.class, a.status, a.notes, a.teacher_id, a.updated_at 
        FROM attendance a
        JOIN students s ON a.student_id = s.student_id
        WHERE 1=1"; 

$params = [];
$types = "";

if (!empty($filter_date)) {
    $sql .= " AND a.attendance_date = ?";
    $params[] = $filter_date;
    $types .= "s";
}
if (!empty($filter_class)) {
    $sql .= " AND a.class = ?";
    $params[] = $filter_class;
    $types .= "s";
}
if (!empty($filter_status)) {
    $sql .= " AND a.status = ?";
    $params[] = $filter_status;
    $types .= "s";
}

$sql .= " ORDER BY a.attendance_date DESC, s.username ASC";

$stmt = $conn->prepare($sql);
$attendance_records = [];
if ($stmt) {
    if (!empty($types)) {
        $stmt->bind_param($types, ...$params);
    }
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $attendance_records[] = $row;
        }
    } else {
        // echo "Error executing query: " . $stmt->error; // For debugging
    }
    $stmt->close();
} else {
    // echo "Error preparing query: " . $conn->error; // For debugging
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - View Attendance Log</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root {
            --primary-color: #2c3e50;   /* Admin Portal Theme */
            --secondary-color: #3498db; /* Admin Portal Theme */
            --accent-color: #1abc9c;    /* Admin Portal Theme (for Present) */
            --danger-color: #e74c3c;    /* Admin Portal Theme (for Absent) */
            --text-color: #333333;
            --text-light: #7f8c8d;
            --white: #ffffff;
            --light-bg: #f5f7fa;       /* Admin Portal Theme */
            --card-bg: #ffffff;
            --border-color: #e0e0e0;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --radius-md: 8px;
            --radius-sm: 4px;
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light-bg);
            color: var(--text-color);
            line-height: 1.6;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .admin-header {
            background-color: var(--primary-color);
            color: var(--white);
            padding: 1rem 2rem;
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .admin-header h1 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .dashboard-link {
            color: var(--white);
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: var(--radius-md);
            transition: var(--transition);
            background-color: rgba(255,255,255,0.1);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .dashboard-link:hover {
            background-color: rgba(255,255,255,0.2);
            transform: translateY(-1px);
        }

        .container {
            max-width: 1200px; /* Wider for logs */
            width: 95%;
            margin: 2rem auto;
            padding: 2rem;
            background-color: var(--card-bg);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow);
            flex-grow: 1;
        }

        .container h2 {
            color: var(--primary-color);
            text-align: center;
            margin-bottom: 1.5rem;
            font-size: 1.8rem;
        }
        .container h3 {
            font-size: 1.4rem;
            color: var(--text-color);
            margin-top: 2rem;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            margin-bottom: 2rem;
            padding: 1.5rem;
            background-color: #f9fafb; /* Slightly off-white */
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
        }

        .filter-form .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            flex: 1; /* Allow groups to grow */
            min-width: 180px; /* Minimum width for filter items */
        }
        .filter-form label {
            font-weight: 500;
            font-size: 0.9rem;
            color: var(--text-color);
        }
        .filter-form input[type="date"],
        .filter-form select {
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            font-size: 1rem;
            background-color: var(--white);
        }
        .filter-form input[type="date"]:focus,
        .filter-form select:focus {
            outline: none;
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }

        .btn-filter {
            padding: 0.75rem 1.5rem;
            background-color: var(--secondary-color);
            color: var(--white);
            border: none;
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: var(--transition);
            align-self: flex-end; /* Aligns with bottom of other inputs */
        }
        .btn-filter:hover {
            background-color: #2980b9; /* Darker secondary */
            transform: translateY(-1px);
        }
        .btn-filter i {
            margin-right: 0.5rem;
        }
        
        .table-responsive {
            overflow-x: auto;
            margin-top: 1rem;
        }
        .results-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }
        .results-table th, .results-table td {
            border: 1px solid var(--border-color);
            padding: 0.75rem 1rem;
            text-align: left;
            vertical-align: middle;
        }
        .results-table thead th {
            background-color: var(--light-bg);
            font-weight: 600;
            color: var(--primary-color);
            position: sticky; /* For scrolling if table is very long */
            top: 0;
            z-index: 10;
        }
        .results-table tbody tr:nth-child(even) {
            background-color: #fdfdfd;
        }
        .results-table tbody tr:hover {
            background-color: #f0f6ff; /* Light blue hover */
        }

        .status-badge {
            padding: 0.3em 0.7em;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: 600;
            color: var(--white);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .status-badge.status-present {
            background-color: var(--accent-color); /* Using accent for Present */
        }
        .status-badge.status-absent {
            background-color: var(--danger-color); /* Using danger for Absent */
        }
        /* Add other status styles if needed */
        .status-badge.status-late { background-color: #f39c12; /* Example: Orange for Late */ }


        .no-records-message {
            text-align: center;
            padding: 2rem;
            color: var(--text-light);
            font-style: italic;
        }
        
        .admin-footer {
            background: var(--primary-color);
            color: var(--white);
            text-align: center;
            padding: 1.5rem;
            margin-top: auto; /* Pushes footer to bottom */
        }

        /* Responsive adjustments */
        @media screen and (max-width: 768px) {
            .admin-header h1 { font-size: 1.25rem; }
            .dashboard-link { padding: 0.4rem 0.8rem; font-size: 0.9rem; }
            .filters-form { flex-direction: column; align-items: stretch; gap: 1rem;}
            .filters-form .form-group { min-width: 100%; }
            .filters-form input[type="date"], .filters-form select, .btn-filter { width: 100%; }
            .container { padding: 1rem; }
            .container h2 { font-size: 1.5rem; }
            .container h3 { font-size: 1.2rem; }
        }
         @media screen and (max-width: 480px) {
            .admin-header { padding: 0.75rem 1rem; }
            .header-content { flex-direction: column; gap: 0.5rem; }
         }

    </style>
</head>
<body>
    <header class="admin-header">
        <div class="header-content">
            <h1>View Attendance Log</h1>
            <a href="admin_home.php" class="dashboard-link"><i class="fas fa-tachometer-alt"></i> Admin Dashboard</a> 
        </div>
    </header>

    <main class="container">
        <h2>Filter Attendance Records</h2>
        <form method="GET" action="" class="filter-form">
            <div class="form-group">
                <label for="filter_date">Date:</label>
                <input type="date" id="filter_date" name="filter_date" value="<?= htmlspecialchars($filter_date) ?>">
            </div>
            <div class="form-group">
                <label for="filter_class">Class:</label>
                <select id="filter_class" name="filter_class">
                    <option value="">All Classes</option>
                    <?php foreach ($classes as $class_item): ?>
                        <option value="<?= htmlspecialchars($class_item) ?>" <?= ($filter_class == $class_item) ? 'selected' : '' ?>>
                            <?= htmlspecialchars(ucwords(str_replace('_', ' ', $class_item))) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="filter_status">Status:</label>
                <select id="filter_status" name="filter_status">
                    <option value="">All Statuses</option>
                    <option value="Present" <?= ($filter_status == 'Present') ? 'selected' : '' ?>>Present</option>
                    <option value="Absent" <?= ($filter_status == 'Absent') ? 'selected' : '' ?>>Absent</option>
                    <option value="Late" <?= ($filter_status == 'Late') ? 'selected' : '' ?>>Late</option>
                    </select>
            </div>
            <div class="form-group">
                <button type="submit" class="btn-filter">
                    <i class="fas fa-filter"></i> Apply Filters
                </button>
            </div>
        </form>

        <hr style="margin: 2rem 0; border: 0; border-top: 1px solid var(--border-color);">

        <h3>Attendance Records Found</h3>
        <?php if (!empty($attendance_records)): ?>
            <div class="table-responsive">
                <table class="results-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Student ID</th>
                            <th>Student Name</th>
                            <th>Class</th>
                            <th>Status</th>
                            <th>Notes</th>
                            <th>Teacher ID</th>
                            <th>Last Updated</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($attendance_records as $record): ?>
                            <tr>
                                <td><?= htmlspecialchars(date("M d, Y", strtotime($record['attendance_date']))) ?></td>
                                <td><?= htmlspecialchars($record['student_id']) ?></td>
                                <td><?= htmlspecialchars($record['username']) ?></td>
                                <td><?= htmlspecialchars(ucwords(str_replace('_', ' ', $record['class']))) ?></td>
                                <td>
                                    <span class="status-badge status-<?= strtolower(htmlspecialchars($record['status'])) ?>">
                                        <?= htmlspecialchars($record['status']) ?>
                                    </span>
                                </td>
                                <td><?= nl2br(htmlspecialchars($record['notes'] ?: '-')) ?></td>
                                <td><?= htmlspecialchars($record['teacher_id'] ?: '-') ?></td>
                                <td><?= htmlspecialchars(date("M d, Y H:i", strtotime($record['updated_at']))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="no-records-message">No attendance records found matching your criteria.</p>
        <?php endif; ?>
    </main>

    <footer class="admin-footer">
        <p>&copy; <?= date("Y") ?> Wisetech College. All rights reserved.</p>
    </footer>
</body>
</html>