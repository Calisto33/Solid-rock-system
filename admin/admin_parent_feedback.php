<?php
session_start();
include '../config.php';

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

// Fetch all feedback
$feedbackQuery = "
    SELECT pf.feedback_id, pf.feedback, pf.status, pf.admin_response, pf.created_at, 
           p.parent_id, p.phone_number, p.relationship, s.username AS student_name
    FROM parent_feedback pf
    JOIN parents p ON pf.parent_id = p.parent_id
    JOIN students s ON p.student_id = s.student_id
    ORDER BY pf.created_at DESC";
$feedbackResult = $conn->query($feedbackQuery);

// Handle admin response submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $feedback_id = $_POST['feedback_id'];
    $admin_response = $_POST['admin_response'];
    $status = $_POST['status'];

    $stmt = $conn->prepare("UPDATE parent_feedback SET admin_response = ?, status = ? WHERE feedback_id = ?");
    $stmt->bind_param("ssi", $admin_response, $status, $feedback_id);
    if ($stmt->execute()) {
        $successMessage = "Feedback updated successfully!";
    } else {
        $errorMessage = "Error updating feedback: " . $stmt->error;
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Parent Feedback</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --primary-light: #e9efff;
            --secondary-color: #3a0ca3;
            --accent-color: #4cc9f0;
            --success-color: #4CAF50;
            --warning-color: #ff9e00;
            --danger-color: #ef476f;
            --text-dark: #2b2d42;
            --text-light: #8d99ae;
            --white: #ffffff;
            --light-bg: #f8f9fa;
            --card-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
            --border-radius: 12px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light-bg);
            color: var(--text-dark);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: var(--white);
            padding: 1.5rem;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
        }

        .logo h1 {
            font-size: 1.8rem;
            font-weight: 600;
            margin-left: 0.8rem;
        }

        .logo i {
            font-size: 2rem;
        }

        .nav-links {
            display: flex;
            gap: 1rem;
        }

        .link-btn {
            display: inline-block;
            padding: 0.7rem 1.2rem;
            color: var(--white);
            background-color: rgba(255, 255, 255, 0.15);
            text-decoration: none;
            border-radius: var(--border-radius);
            transition: var(--transition);
            font-weight: 500;
            backdrop-filter: blur(5px);
        }

        .link-btn:hover {
            background-color: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }

        .link-btn i {
            margin-right: 0.5rem;
        }

        .container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 1.5rem;
            flex: 1;
        }

        .page-title {
            margin-bottom: 1.5rem;
            position: relative;
            display: inline-block;
        }

        .page-title h2 {
            color: var(--primary-color);
            font-weight: 600;
            font-size: 1.8rem;
        }

        .page-title::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 0;
            width: 80px;
            height: 4px;
            background: var(--accent-color);
            border-radius: 2px;
        }

        .content-card {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            overflow: hidden;
            margin-top: 1rem;
        }

        .card-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--secondary-color);
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .card-body {
            padding: 1rem 1.5rem;
        }

        .message {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: var(--border-radius);
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .message i {
            font-size: 1.5rem;
        }

        .message.success {
            background-color: rgba(76, 175, 80, 0.15);
            color: var(--success-color);
            border-left: 4px solid var(--success-color);
        }

        .message.error {
            background-color: rgba(239, 71, 111, 0.15);
            color: var(--danger-color);
            border-left: 4px solid var(--danger-color);
        }

        .table-responsive {
            overflow-x: auto;
            border-radius: var(--border-radius);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
            background: var(--white);
        }

        thead {
            background-color: var(--primary-light);
        }

        th {
            color: var(--primary-color);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        th, td {
            padding: 1rem 1.5rem;
            text-align: left;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover {
            background-color: rgba(67, 97, 238, 0.03);
        }

        tbody tr {
            transition: var(--transition);
        }

        .status-badge {
            display: inline-block;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            text-align: center;
        }

        .status-pending {
            background-color: rgba(255, 158, 0, 0.15);
            color: var(--warning-color);
        }

        .status-reviewed {
            background-color: rgba(76, 201, 240, 0.15);
            color: var(--accent-color);
        }

        .status-resolved {
            background-color: rgba(76, 175, 80, 0.15);
            color: var(--success-color);
        }

        .form-group {
            margin-bottom: 1rem;
        }

        textarea {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: var(--border-radius);
            resize: vertical;
            min-height: 60px;
            font-family: inherit;
            transition: var(--transition);
        }

        textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }

        select {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: var(--border-radius);
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%238d99ae' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 16px 12px;
            transition: var(--transition);
            cursor: pointer;
        }

        select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }

        button {
            background-color: var(--primary-color);
            color: var(--white);
            padding: 0.8rem 1.2rem;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: var(--transition);
            width: 100%;
        }

        button:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .action-cell {
            max-width: 300px;
        }

        .student-name {
            font-weight: 600;
            color: var(--secondary-color);
        }

        .feedback-text {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
        }

        .admin-response {
            color: var(--text-light);
            font-style: italic;
            max-width: 250px;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        @media screen and (max-width: 992px) {
            .card-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
        }

        @media screen and (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }

            .nav-links {
                width: 100%;
                justify-content: center;
            }

            .logo {
                justify-content: center;
            }

            th, td {
                padding: 0.8rem;
            }
        }

        @media screen and (max-width: 576px) {
            th, td {
                padding: 0.6rem;
                font-size: 0.9rem;
            }

            .status-badge {
                padding: 0.3rem 0.6rem;
                font-size: 0.8rem;
            }

            .page-title h2 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <i class="fas fa-comments"></i>
                <h1>Parent Feedback Portal</h1>
            </div>
            <div class="nav-links">
                <a href="admin_home.php" class="link-btn"><i class="fas fa-home"></i>Dashboard</a>
                <a href="#" class="link-btn"><i class="fas fa-chart-bar"></i>Reports</a>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="page-title">
            <h2>Parent Feedback Management</h2>
        </div>
        
        <div class="content-card">
            <div class="card-header">
                <div class="card-title">
                    <i class="fas fa-clipboard-list"></i>
                    Feedback Overview
                </div>
                <div class="filters">
                    <select id="statusFilter" onchange="filterTable()">
                        <option value="all">All Statuses</option>
                        <option value="Pending">Pending</option>
                        <option value="Reviewed">Reviewed</option>
                        <option value="Resolved">Resolved</option>
                    </select>
                </div>
            </div>
            <div class="card-body">
                <?php if (isset($successMessage)): ?>
                    <div class="message success">
                        <i class="fas fa-check-circle"></i>
                        <div><?= $successMessage ?></div>
                    </div>
                <?php elseif (isset($errorMessage)): ?>
                    <div class="message error">
                        <i class="fas fa-exclamation-circle"></i>
                        <div><?= $errorMessage ?></div>
                    </div>
                <?php endif; ?>

                <div class="table-responsive">
                    <table id="feedbackTable">
                        <thead>
                            <tr>
                                <th><i class="fas fa-user"></i> Student</th>
                                <th><i class="fas fa-comment-alt"></i> Feedback</th>
                                <th><i class="fas fa-tag"></i> Status</th>
                                <th><i class="fas fa-reply"></i> Admin Response</th>
                                <th><i class="fas fa-edit"></i> Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (isset($feedbackResult) && $feedbackResult->num_rows > 0): ?>
                                <?php while ($feedback = $feedbackResult->fetch_assoc()): ?>
                                    <tr class="feedback-row" data-status="<?= htmlspecialchars($feedback['status']) ?>">
                                        <td class="student-name"><?= htmlspecialchars($feedback['student_name']) ?></td>
                                        <td class="feedback-text"><?= htmlspecialchars($feedback['feedback']) ?></td>
                                        <td>
                                            <span class="status-badge status-<?= strtolower(htmlspecialchars($feedback['status'])) ?>">
                                                <?= htmlspecialchars($feedback['status']) ?>
                                            </span>
                                        </td>
                                        <td class="admin-response">
                                            <?= empty($feedback['admin_response']) ? '—' : htmlspecialchars($feedback['admin_response']) ?>
                                        </td>
                                        <td class="action-cell">
                                            <form method="POST">
                                                <input type="hidden" name="feedback_id" value="<?= $feedback['feedback_id'] ?>">
                                                <div class="form-group">
                                                    <textarea name="admin_response" placeholder="Enter your response..."><?= htmlspecialchars($feedback['admin_response']) ?></textarea>
                                                </div>
                                                <div class="form-group">
                                                    <select name="status">
                                                        <option value="Pending" <?= $feedback['status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                                                        <option value="Reviewed" <?= $feedback['status'] == 'Reviewed' ? 'selected' : '' ?>>Reviewed</option>
                                                        <option value="Resolved" <?= $feedback['status'] == 'Resolved' ? 'selected' : '' ?>>Resolved</option>
                                                    </select>
                                                </div>
                                                <button type="submit"><i class="fas fa-save"></i> Update</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 2rem;">No feedback entries found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        function filterTable() {
            const filter = document.getElementById('statusFilter').value;
            const rows = document.querySelectorAll('.feedback-row');
            
            rows.forEach(row => {
                const status = row.getAttribute('data-status');
                if (filter === 'all' || status === filter) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>