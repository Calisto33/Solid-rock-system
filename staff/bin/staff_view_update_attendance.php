<?php
session_start();
include '../config.php'; // Database connection

// Ensure the user is logged in as staff
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'staff') {
    header("Location: ../login.php");
    exit();
}

// Get class and date parameters from GET or set defaults
$class = $_GET['class'] ?? 'f_1'; // Default to 'f_1' initially
$date = $_GET['date'] ?? date("Y-m-d"); // Default to today's date

// Process attendance updates
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    foreach ($_POST['status'] as $attendance_id => $status) {
        $updateQuery = "UPDATE attendance SET status = ?, updated_by = 'staff', last_updated = NOW() WHERE attendance_id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("si", $status, $attendance_id);
        $stmt->execute();
    }
    $stmt->close();
    // Refresh the page after updates
    header("Location: staff_view_update_attendance.php?class=$class&date=$date");
    exit();
}

// Fetch attendance records for the selected class and date
$attendanceQuery = "
    SELECT a.attendance_id, s.username AS student_name, a.status
    FROM attendance a
    JOIN students s ON a.student_id = s.student_id
    WHERE a.class = ? AND a.date = ?
    ORDER BY s.username";
$stmt = $conn->prepare($attendanceQuery);
$stmt->bind_param("ss", $class, $date);
$stmt->execute();
$attendanceResult = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff View Attendance</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #3a6ea5;
            --primary-light: #eef5fc;
            --primary-dark: #004d80;
            --accent-color: #ff6b6b;
            --success-color: #2ecc71;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --neutral-color: #6c757d;
            --text-color: #2d3436;
            --text-light: #636e72;
            --white: #ffffff;
            --light-bg: #f8f9fa;
            --border-radius: 12px;
            --box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8eb 100%);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            color: var(--text-color);
        }

        header {
            background: linear-gradient(to right, var(--primary-color), var(--primary-dark));
            color: var(--white);
            padding: 1.5rem 2rem;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }

        header h1 {
            margin: 0;
            font-size: 1.8rem;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .nav-links {
            display: flex;
            align-items: center;
        }

        .link-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 0.7rem 1.2rem;
            color: var(--white);
            background-color: rgba(255, 255, 255, 0.15);
            text-decoration: none;
            border-radius: 8px;
            transition: var(--transition);
            font-weight: 500;
            backdrop-filter: blur(10px);
        }

        .link-btn:hover {
            background-color: rgba(255, 255, 255, 0.25);
            transform: translateY(-2px);
        }

        .link-btn i {
            font-size: 0.9rem;
        }

        .container {
            max-width: 1200px;
            width: 92%;
            margin: 2rem auto;
            padding: 0;
            flex: 1;
        }

        .card {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            transition: var(--transition);
            margin-bottom: 2rem;
        }

        .card:hover {
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.08);
            transform: translateY(-5px);
        }

        .card-header {
            padding: 1.5rem 2rem;
            background-color: var(--primary-light);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .card-header h2 {
            color: var(--primary-color);
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .card-body {
            padding: 2rem;
        }

        .filters {
            display: flex;
            gap: 1.5rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .filter-group {
            flex: 1;
            min-width: 200px;
        }

        label {
            display: block;
            margin-bottom: 0.8rem;
            color: var(--text-light);
            font-weight: 500;
            font-size: 0.95rem;
        }

        select, input[type="date"] {
            width: 100%;
            padding: 0.9rem 1rem;
            border: 1px solid #e1e5eb;
            border-radius: 8px;
            font-size: 1rem;
            background-color: var(--white);
            color: var(--text-color);
            transition: var(--transition);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.02);
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
        }

        select {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23666' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            padding-right: 2.5rem;
        }

        select:focus, input[type="date"]:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(58, 110, 165, 0.1);
        }

        .table-responsive {
            overflow-x: auto;
            border-radius: 8px;
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            min-width: 600px;
        }

        th, td {
            padding: 1.2rem 1.5rem;
            text-align: left;
        }

        th {
            background-color: var(--primary-light);
            color: var(--primary-dark);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        th:first-child {
            border-top-left-radius: 8px;
        }

        th:last-child {
            border-top-right-radius: 8px;
        }

        tr:last-child td:first-child {
            border-bottom-left-radius: 8px;
        }

        tr:last-child td:last-child {
            border-bottom-right-radius: 8px;
        }

        tbody tr {
            transition: var(--transition);
            border-bottom: 1px solid #eee;
        }

        tbody tr:hover {
            background-color: rgba(58, 110, 165, 0.03);
        }

        tbody tr:last-child {
            border-bottom: none;
        }

        td {
            color: var(--text-color);
            font-weight: 400;
        }

        .student-name {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background-color: var(--primary-light);
            color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1rem;
        }

        .status-select {
            width: 100%;
            padding: 0.7rem 1rem;
            border: 1px solid #e1e5eb;
            border-radius: 6px;
            font-size: 0.95rem;
            background-color: var(--white);
            transition: var(--transition);
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23666' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            padding-right: 2.5rem;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
        }

        .status-select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(58, 110, 165, 0.1);
        }

        .status-select option[value="present"] {
            background-color: var(--success-color);
            color: white;
        }

        .status-select option[value="late"] {
            background-color: var(--warning-color);
            color: white;
        }

        .status-select option[value="absent"] {
            background-color: var(--danger-color);
            color: white;
        }

        .status-select option[value="holiday"] {
            background-color: var(--neutral-color);
            color: white;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 500;
            display: inline-block;
            text-align: center;
            min-width: 100px;
        }

        .present {
            background-color: rgba(46, 204, 113, 0.15);
            color: var(--success-color);
        }

        .late {
            background-color: rgba(243, 156, 18, 0.15);
            color: var(--warning-color);
        }

        .absent {
            background-color: rgba(231, 76, 60, 0.15);
            color: var(--danger-color);
        }

        .holiday {
            background-color: rgba(108, 117, 125, 0.15);
            color: var(--neutral-color);
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--text-light);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #cfd8dc;
        }

        .empty-state p {
            font-size: 1.1rem;
            margin-bottom: 1rem;
        }

        button {
            background: linear-gradient(to right, var(--primary-color), var(--primary-dark));
            color: var(--white);
            padding: 1rem 2rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: auto;
            min-width: 200px;
            margin: 1.5rem auto;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        button:hover {
            background: linear-gradient(to right, var(--primary-dark), var(--primary-color));
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        button i {
            font-size: 1rem;
        }

        .notification {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background-color: var(--success-color);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 10px;
            transform: translateY(100px);
            opacity: 0;
            transition: all 0.3s cubic-bezier(0.68, -0.55, 0.27, 1.55);
            z-index: 1000;
        }

        .notification.show {
            transform: translateY(0);
            opacity: 1;
        }

        .notification i {
            font-size: 1.2rem;
        }

        /* Responsive Design */
        @media screen and (max-width: 768px) {
            header {
                padding: 1.2rem;
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            header h1 {
                font-size: 1.5rem;
            }

            .container {
                width: 95%;
                margin: 1.5rem auto;
            }

            .card-header, .card-body {
                padding: 1.5rem;
            }

            .card-header h2 {
                font-size: 1.3rem;
            }

            .filters {
                gap: 1rem;
            }

            th, td {
                padding: 1rem;
            }

            button {
                width: 100%;
                max-width: none;
            }
        }

        @media screen and (max-width: 480px) {
            header h1 {
                font-size: 1.3rem;
            }

            .link-btn {
                padding: 0.6rem 1rem;
                font-size: 0.9rem;
            }

            .container {
                width: 100%;
                margin: 1rem auto;
            }

            .card {
                border-radius: 0;
            }

            .card-header, .card-body {
                padding: 1.2rem;
            }

            .card-header h2 {
                font-size: 1.2rem;
            }

            .filter-group {
                min-width: 100%;
            }

            select, input[type="date"] {
                padding: 0.8rem;
            }

            th, td {
                padding: 0.8rem;
                font-size: 0.9rem;
            }

            .status-select {
                padding: 0.6rem;
                font-size: 0.85rem;
            }

            button {
                padding: 0.9rem 1.5rem;
                font-size: 0.95rem;
            }

            .notification {
                bottom: 1rem;
                right: 1rem;
                left: 1rem;
                width: calc(100% - 2rem);
            }
        }
    </style>
</head>
<body>
    <header>
        <h1><i class="fas fa-clipboard-check"></i> Staff Attendance Management</h1>
        <div class="nav-links">
            <a href="staff_home.php" class="link-btn"><i class="fas fa-home"></i> Home</a>
        </div>
    </header>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-calendar-alt"></i> Manage Attendance</h2>
            </div>
            <div class="card-body">
                <!-- Class and Date Selection Form -->
                <form method="GET" action="staff_view_update_attendance.php" class="filters">
                    <div class="filter-group">
                        <label for="class"><i class="fas fa-users"></i> Select Class:</label>
                        <select name="class" id="class" onchange="this.form.submit()">
                            <option value="f_1" <?= $class == 'f_1' ? 'selected' : '' ?>>Form 1</option>
                            <option value="f_2" <?= $class == 'f_2' ? 'selected' : '' ?>>Form 2</option>
                            <option value="f_3" <?= $class == 'f_3' ? 'selected' : '' ?>>Form 3</option>
                            <option value="f_4" <?= $class == 'f_4' ? 'selected' : '' ?>>Form 4</option>
                            <option value="f_5" <?= $class == 'f_5' ? 'selected' : '' ?>>Form 5</option>
                            <option value="f_6" <?= $class == 'f_6' ? 'selected' : '' ?>>Form 6</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="date"><i class="fas fa-calendar-day"></i> Select Date:</label>
                        <input type="date" name="date" id="date" 
                               value="<?= htmlspecialchars($date) ?>" onchange="this.form.submit()">
                    </div>
                </form>

                <!-- Attendance Table -->
                <form method="POST" action="staff_view_update_attendance.php?class=<?= htmlspecialchars($class) ?>&date=<?= htmlspecialchars($date) ?>">
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Student Name</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($attendanceResult->num_rows > 0): ?>
                                    <?php while ($row = $attendanceResult->fetch_assoc()): ?>
                                        <?php 
                                            // Get initials for avatar
                                            $name_parts = explode(' ', $row['student_name']);
                                            $initials = '';
                                            foreach ($name_parts as $part) {
                                                $initials .= strtoupper(substr($part, 0, 1));
                                            }
                                            if (strlen($initials) > 2) {
                                                $initials = substr($initials, 0, 2);
                                            }
                                        ?>
                                        <tr>
                                            <td>
                                                <div class="student-name">
                                                    <div class="avatar"><?= $initials ?></div>
                                                    <?= htmlspecialchars($row['student_name']) ?>
                                                </div>
                                            </td>
                                            <td>
                                                <select name="status[<?= $row['attendance_id'] ?>]" class="status-select" data-status="<?= $row['status'] ?>">
                                                    <option value="present" <?= $row['status'] == 'present' ? 'selected' : '' ?>>Present</option>
                                                    <option value="late" <?= $row['status'] == 'late' ? 'selected' : '' ?>>Late</option>
                                                    <option value="absent" <?= $row['status'] == 'absent' ? 'selected' : '' ?>>Absent</option>
                                                    <option value="holiday" <?= $row['status'] == 'holiday' ? 'selected' : '' ?>>Holiday</option>
                                                </select>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="2">
                                            <div class="empty-state">
                                                <i class="fas fa-clipboard"></i>
                                                <p>No attendance records found for this class and date.</p>
                                                <small>Please check your selection or create new records.</small>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($attendanceResult && $attendanceResult->num_rows > 0): ?>
                        <button type="submit"><i class="fas fa-save"></i> Save Changes</button>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <div class="notification" id="saveNotification">
        <i class="fas fa-check-circle"></i>
        <span>Changes saved successfully!</span>
    </div>

    <script>
        // Check if URL has a success parameter to show notification
        window.addEventListener('DOMContentLoaded', () => {
            // Style the status selects based on their value
            document.querySelectorAll('.status-select').forEach(select => {
                styleStatusSelect(select);
                
                select.addEventListener('change', function() {
                    styleStatusSelect(this);
                });
            });
            
            // Show notification if needed
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('saved') && urlParams.get('saved') === 'success') {
                const notification = document.getElementById('saveNotification');
                notification.classList.add('show');
                
                setTimeout(() => {
                    notification.classList.remove('show');
                }, 3000);
            }
        });
        
        // Function to style the status selects
        function styleStatusSelect(select) {
            // First reset
            select.classList.remove('present', 'late', 'absent', 'holiday');
            
            // Add appropriate class
            const status = select.value;
            select.classList.add(status);
            
            // Style based on status
            switch(status) {
                case 'present':
                    select.style.borderColor = 'rgba(46, 204, 113, 0.3)';
                    select.style.backgroundColor = 'rgba(46, 204, 113, 0.05)';
                    break;
                case 'late':
                    select.style.borderColor = 'rgba(243, 156, 18, 0.3)';
                    select.style.backgroundColor = 'rgba(243, 156, 18, 0.05)';
                    break;
                case 'absent':
                    select.style.borderColor = 'rgba(231, 76, 60, 0.3)';
                    select.style.backgroundColor = 'rgba(231, 76, 60, 0.05)';
                    break;
                case 'holiday':
                    select.style.borderColor = 'rgba(108, 117, 125, 0.3)';
                    select.style.backgroundColor = 'rgba(108, 117, 125, 0.05)';
                    break;
                default:
                    select.style.borderColor = '#e1e5eb';
                    select.style.backgroundColor = 'white';
            }
        }
    </script>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
