<?php
session_start();
include '../config.php';

// Check if user is logged in as a parent
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'parent') {
    header("Location: ../login.php");
    exit();
}


$user_id = $_SESSION['user_id'];

// --- FETCH ALL CHILDREN ASSIGNED TO THIS PARENT ---
// Fixed table name: student_parent_relationships instead of parent_student_relationships
$childrenQuery = "
    SELECT DISTINCT
        s.student_id as student_internal_id,
        s.student_id,
        COALESCE(NULLIF(CONCAT_WS(' ', s.first_name, s.last_name), ''), s.username) AS student_name,
        s.first_name,
        s.last_name,
        s.username,
        s.class
    FROM student_parent_relationships spr
    INNER JOIN parents p ON spr.parent_id = p.parent_id
    INNER JOIN students s ON spr.student_id = s.student_id
    WHERE p.user_id = ?
    ORDER BY s.first_name, s.last_name";

$stmt = $conn->prepare($childrenQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$childrenResult = $stmt->get_result();
$children = [];
while ($child = $childrenResult->fetch_assoc()) {
    $children[] = $child;
}
$stmt->close();

if (empty($children)) {
    die("Error: This parent account is not linked to any students. Please contact administration.");
}

// --- DETERMINE WHICH CHILD TO DISPLAY ---
$selectedChildIndex = 0;
if (isset($_GET['child']) && is_numeric($_GET['child'])) {
    $requestedIndex = intval($_GET['child']);
    if ($requestedIndex >= 0 && $requestedIndex < count($children)) {
        $selectedChildIndex = $requestedIndex;
    }
}

// If showing all children (no specific child selected)
$showAllChildren = !isset($_GET['child']) || $_GET['child'] === 'all';

if ($showAllChildren) {
    // Query to fetch fees for ALL children - Updated to use correct table structure
    $query = "
        SELECT 
            s.student_id, 
            COALESCE(NULLIF(CONCAT_WS(' ', s.first_name, s.last_name), ''), s.username) AS student_name,
            s.class, 
            f.total_fee, 
            f.amount_paid, 
            (f.total_fee - f.amount_paid) AS amount_owed,
            f.payment_plan, 
            f.status, 
            f.due_date
        FROM student_parent_relationships spr
        INNER JOIN parents p ON spr.parent_id = p.parent_id
        INNER JOIN students s ON spr.student_id = s.student_id
        LEFT JOIN fees f ON s.student_id = f.student_id
        WHERE p.user_id = ?
        ORDER BY s.class, s.first_name, s.last_name";
} else {
    // Query to fetch fees for SPECIFIC child
    $currentChild = $children[$selectedChildIndex];
    $student_id = $currentChild['student_id'];
    
    $query = "
        SELECT 
            s.student_id, 
            COALESCE(NULLIF(CONCAT_WS(' ', s.first_name, s.last_name), ''), s.username) AS student_name,
            s.class, 
            f.total_fee, 
            f.amount_paid, 
            (f.total_fee - f.amount_paid) AS amount_owed,
            f.payment_plan, 
            f.status, 
            f.due_date
        FROM students s
        LEFT JOIN fees f ON s.student_id = f.student_id
        WHERE s.student_id = ?
        ORDER BY s.class, s.first_name, s.last_name";
}

$stmt = $conn->prepare($query);
if ($showAllChildren) {
    $stmt->bind_param("i", $user_id);
} else {
    $stmt->bind_param("s", $student_id);
}
$stmt->execute();
$result = $stmt->get_result();

// Calculate totals
$totalFees = 0;
$totalPaid = 0;
$totalOwed = 0;
$fees_data = [];

while ($row = $result->fetch_assoc()) {
    $fees_data[] = $row;
    $totalFees += $row['total_fee'] ?? 0;
    $totalPaid += $row['amount_paid'] ?? 0;
    $totalOwed += $row['amount_owed'] ?? 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fees Overview - Parent Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<!-- Favicon -->
    <link rel="icon" type="image/jpeg" href="../images/logo.jpeg">
    <link rel="shortcut icon" type="image/jpeg" href="../images/logo.jpeg">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            color: #333;
            line-height: 1.6;
        }

        .header {
            background: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #ddd;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .brand img {
            height: 50px;
            border-radius: 50%;
        }

        .brand h1 {
            font-size: 24px;
            color: #4A90E2;
        }

        .nav-actions {
            display: flex;
            gap: 15px;
        }

        .nav-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background-color: transparent;
            color: #4A90E2;
            border: 2px solid #4A90E2;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
        }

        .nav-btn:hover {
            background-color: #4A90E2;
            color: white;
        }

        .nav-btn.primary {
            background-color: #4A90E2;
            color: white;
        }

        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .child-selector {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .child-selector h3 {
            color: #4A90E2;
            margin-bottom: 15px;
        }

        .child-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .child-tab {
            padding: 10px 20px;
            background: #f0f0f0;
            color: #333;
            text-decoration: none;
            border-radius: 5px;
            border: 2px solid transparent;
        }

        .child-tab.active {
            background: #4A90E2;
            color: white;
            border-color: #357ABD;
        }

        .child-tab:hover {
            background: #e0e0e0;
        }

        .child-tab.active:hover {
            background: #357ABD;
        }

        .page-title {
            font-size: 28px;
            color: #333;
            margin-bottom: 30px;
            text-align: center;
        }

        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .summary-card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-left: 5px solid #4A90E2;
        }

        .summary-card.total { border-left-color: #4A90E2; }
        .summary-card.paid { border-left-color: #28a745; }
        .summary-card.owed { border-left-color: #dc3545; }

        .summary-card i {
            font-size: 32px;
            margin-bottom: 15px;
        }

        .summary-card.total i { color: #4A90E2; }
        .summary-card.paid i { color: #28a745; }
        .summary-card.owed i { color: #dc3545; }

        .summary-value {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }

        .summary-label {
            color: #666;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .content-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .card-header {
            background: #4A90E2;
            color: white;
            padding: 20px;
        }

        .card-title {
            font-size: 20px;
            margin: 0;
        }

        .card-body {
            padding: 30px;
        }

        .table-container {
            overflow-x: auto;
        }

        .fees-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .fees-table th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: bold;
            border-bottom: 2px solid #dee2e6;
            font-size: 14px;
        }

        .fees-table td {
            padding: 15px;
            border-bottom: 1px solid #dee2e6;
        }

        .fees-table tbody tr:hover {
            background: #f8f9fa;
        }

        .amount {
            font-weight: bold;
            color: #333;
        }

        .amount-owed {
            font-weight: bold;
            color: #dc3545;
        }

        .status-indicator {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .status-cleared {
            background: #d4edda;
            color: #155724;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-overdue {
            background: #f8d7da;
            color: #721c24;
        }

        .empty-state {
            text-align: center;
            padding: 50px;
            color: #666;
        }

        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.3;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
            }

            .nav-actions {
                flex-direction: column;
                width: 100%;
            }

            .summary-cards {
                grid-template-columns: 1fr;
            }

            .fees-table {
                font-size: 14px;
            }

            .fees-table th,
            .fees-table td {
                padding: 10px 8px;
            }

            .child-tabs {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="brand">
            <img src="../images/logo.jpeg" alt="Solid Rock  Logo">
            <h1>Parent Portal</h1>
        </div>
        <div class="nav-actions">
            <a href="parent_home.php<?= !$showAllChildren ? '?child='.$selectedChildIndex : '' ?>" class="nav-btn">
                <i class="fas fa-home"></i> Home
            </a>
            <a href="../logout.php" class="nav-btn primary">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </header>

    <div class="container">
        <?php if (count($children) > 1): ?>
        <div class="child-selector">
            <h3><i class="fas fa-users"></i> View Fees For</h3>
            <div class="child-tabs">
                <a href="parent_fees.php?child=all" class="child-tab <?= $showAllChildren ? 'active' : '' ?>">
                    <i class="fas fa-users"></i> All Children
                </a>
                <?php foreach ($children as $index => $child): ?>
                    <a href="parent_fees.php?child=<?= $index ?>" 
                       class="child-tab <?= !$showAllChildren && $index === $selectedChildIndex ? 'active' : '' ?>">
                        <?= htmlspecialchars($child['student_name']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <main class="main-content">
            <h1 class="page-title">
                Fees Overview
                <?php if (!$showAllChildren): ?>
                    - <?= htmlspecialchars($children[$selectedChildIndex]['student_name']) ?>
                <?php endif; ?>
            </h1>
            
            <?php if (!empty($fees_data)): ?>
            <div class="summary-cards">
                <div class="summary-card total">
                    <i class="fas fa-receipt"></i>
                    <div class="summary-value">$<?= number_format($totalFees, 2) ?></div>
                    <div class="summary-label">Total Fees</div>
                </div>
                <div class="summary-card paid">
                    <i class="fas fa-check-circle"></i>
                    <div class="summary-value">$<?= number_format($totalPaid, 2) ?></div>
                    <div class="summary-label">Amount Paid</div>
                </div>
                <div class="summary-card owed">
                    <i class="fas fa-exclamation-circle"></i>
                    <div class="summary-value">$<?= number_format($totalOwed, 2) ?></div>
                    <div class="summary-label">Amount Owed</div>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="content-card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-receipt"></i> 
                        <?php if ($showAllChildren): ?>
                            All Children's Fees
                        <?php else: ?>
                            <?= htmlspecialchars($children[$selectedChildIndex]['student_name']) ?>'s Fees
                        <?php endif; ?>
                    </h2>
                </div>
                <div class="card-body">
                    <?php if (!empty($fees_data)): ?>
                        <div class="table-container">
                            <table class="fees-table">
                                <thead>
                                    <tr>
                                        <th><i class="fas fa-user-graduate"></i> Student Name</th>
                                        <th><i class="fas fa-chalkboard"></i> Class</th>
                                        <th><i class="fas fa-coins"></i> Total Fee</th>
                                        <th><i class="fas fa-check-circle"></i> Amount Paid</th>
                                        <th><i class="fas fa-exclamation-circle"></i> Amount Owed</th>
                                        <th><i class="fas fa-info-circle"></i> Status</th>
                                        <th><i class="fas fa-clock"></i> Due Date</th>
                                        <th><i class="fas fa-calendar-alt"></i> Payment Plan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($fees_data as $row): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['student_name']) ?></td>
                                            <td><?= htmlspecialchars($row['class'] ?? 'N/A') ?></td>
                                            <td class="amount">$<?= number_format($row['total_fee'] ?? 0, 2) ?></td>
                                            <td class="amount">$<?= number_format($row['amount_paid'] ?? 0, 2) ?></td>
                                            <td class="amount-owed">$<?= number_format($row['amount_owed'] ?? 0, 2) ?></td>
                                            <td>
                                                <?php 
                                                    $status = strtolower($row['status'] ?? 'pending');
                                                    $statusClass = in_array($status, ['cleared', 'overdue', 'pending']) ? $status : 'pending';
                                                    $statusIcon = $statusClass === 'cleared' ? 'check-circle' : ($statusClass === 'overdue' ? 'exclamation-circle' : 'clock');
                                                ?>
                                                <span class="status-indicator status-<?= $statusClass ?>">
                                                    <i class="fas fa-<?= $statusIcon ?>"></i>
                                                    <?= htmlspecialchars(ucfirst($status)) ?>
                                                </span>
                                            </td>
                                            <td><?= $row['due_date'] ? date('M j, Y', strtotime($row['due_date'])) : 'N/A' ?></td>
                                            <td><?= htmlspecialchars($row['payment_plan'] ?? 'One-Time') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-file-invoice-dollar"></i>
                            <h3>No Fees Information Available</h3>
                            <p>There are currently no fee records<?= !$showAllChildren ? ' for this child' : ' for your children' ?>.</p>
                            <p style="margin-top: 10px; font-size: 14px; color: #999;">Contact the school administration if you believe this is an error.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <footer style="text-align: center; padding: 30px; color: #666;">
        <p>&copy; <?php echo date("Y"); ?> Mirilax-Scales Portal | All Rights Reserved</p>
    </footer>
</body>
</html>

<?php
// Close database resources
$stmt->close();
$conn->close();
?>