<?php
// session_start() must be the very first thing.
session_start();
include '../config.php';

// Check if the user is logged in as super admin
if (!isset($_SESSION['super_admin_id'])) {
    header("Location: super_admin_login.php");
    exit();
}

// --- Page Setup & Data Fetching ---
$pageTitle = "System Analysis";
$currentPage = "analysis";

// Fetch total counts - CORRECTED to use actual tables
$totalStudents = 0;
$totalStaff = 0;
$totalSubjects = 0;
$totalParents = 0;
$totalUsers = 0;

// Get student count
$studentsQuery = "SELECT COUNT(*) AS total FROM students";
$studentsResult = $conn->query($studentsQuery);
if ($studentsResult) {
    $totalStudents = $studentsResult->fetch_assoc()['total'];
}

// Get staff count (check if staff table exists)
$staffQuery = "SHOW TABLES LIKE 'staff'";
$staffTableExists = $conn->query($staffQuery)->num_rows > 0;
if ($staffTableExists) {
    $staffResult = $conn->query("SELECT COUNT(*) AS total FROM staff");
    if ($staffResult) {
        $totalStaff = $staffResult->fetch_assoc()['total'];
    }
}

// Get unique subjects from results table
$subjectsQuery = "SELECT COUNT(DISTINCT subject) AS total FROM results WHERE subject IS NOT NULL AND subject != ''";
$subjectsResult = $conn->query($subjectsQuery);
if ($subjectsResult) {
    $totalSubjects = $subjectsResult->fetch_assoc()['total'];
}

// Get parents count
$parentsQuery = "SELECT COUNT(*) AS total FROM parents";
$parentsResult = $conn->query($parentsQuery);
if ($parentsResult) {
    $totalParents = $parentsResult->fetch_assoc()['total'];
}

// Get total users
$usersQuery = "SELECT COUNT(*) AS total FROM users";
$usersResult = $conn->query($usersQuery);
if ($usersResult) {
    $totalUsers = $usersResult->fetch_assoc()['total'];
}

// Student distribution by class
$classDistributionQuery = "
    SELECT 
        class, 
        COUNT(*) as student_count 
    FROM students 
    WHERE class IS NOT NULL AND class != '' AND class != 'Unassigned'
    GROUP BY class 
    ORDER BY class";
$classDistributionResult = $conn->query($classDistributionQuery);

// Fee status analysis
$feeStatusQuery = "
    SELECT 
        COALESCE(f.status, 'No Fee Assigned') as fee_status,
        COUNT(*) as count
    FROM students s
    LEFT JOIN fees f ON s.student_id = f.student_id
    GROUP BY COALESCE(f.status, 'No Fee Assigned')
    ORDER BY count DESC";
$feeStatusResult = $conn->query($feeStatusQuery);

// Results analysis by subject
$resultsAnalysisQuery = "
    SELECT 
        subject,
        COUNT(*) as total_results,
        AVG(CASE WHEN total_marks > 0 THEN (marks_obtained / total_marks) * 100 ELSE 0 END) as avg_percentage,
        COUNT(DISTINCT student_id) as unique_students
    FROM results 
    WHERE subject IS NOT NULL AND subject != ''
    GROUP BY subject
    ORDER BY total_results DESC
    LIMIT 10";
$resultsAnalysisResult = $conn->query($resultsAnalysisQuery);

// Payment analysis
$paymentAnalysisQuery = "
    SELECT 
        YEAR(payment_date) as payment_year,
        MONTH(payment_date) as payment_month,
        COUNT(*) as total_payments,
        SUM(amount) as total_amount
    FROM payments 
    WHERE payment_date IS NOT NULL 
    GROUP BY YEAR(payment_date), MONTH(payment_date)
    ORDER BY payment_year DESC, payment_month DESC
    LIMIT 12";
$paymentAnalysisResult = $conn->query($paymentAnalysisQuery);

// Student enrollment trends
$enrollmentTrendsQuery = "
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as enrollment_month,
        COUNT(*) as new_students
    FROM students 
    WHERE created_at IS NOT NULL
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY enrollment_month DESC
    LIMIT 12";
$enrollmentTrendsResult = $conn->query($enrollmentTrendsQuery);

// --- Prepare chart data ---
// Class distribution chart
$class_labels = [];
$class_data = [];
if ($classDistributionResult) {
    while ($row = $classDistributionResult->fetch_assoc()) {
        $class_labels[] = $row['class'];
        $class_data[] = (int)$row['student_count'];
    }
}

// Fee status chart
$fee_labels = [];
$fee_data = [];
$fee_colors = [
    'Cleared' => '#10b981',
    'Pending' => '#f59e0b', 
    'Overdue' => '#ef4444',
    'No Fee Assigned' => '#6b7280'
];
$fee_chart_colors = [];

if ($feeStatusResult) {
    while ($row = $feeStatusResult->fetch_assoc()) {
        $fee_labels[] = $row['fee_status'];
        $fee_data[] = (int)$row['count'];
        $fee_chart_colors[] = $fee_colors[$row['fee_status']] ?? '#94a3b8';
    }
}

// Enrollment trends chart
$enrollment_labels = [];
$enrollment_data = [];
if ($enrollmentTrendsResult) {
    $trends = [];
    while ($row = $enrollmentTrendsResult->fetch_assoc()) {
        $trends[] = $row;
    }
    // Reverse to show chronological order
    $trends = array_reverse($trends);
    foreach ($trends as $row) {
        $enrollment_labels[] = date('M Y', strtotime($row['enrollment_month'] . '-01'));
        $enrollment_data[] = (int)$row['new_students'];
    }
}

// --- Now, include the main header and sidebar ---
include 'sa_header.php';
?>

<style>
/* Enhanced styling for system analysis */
.content-header {
    margin-bottom: 2rem;
}

.content-header .title {
    font-size: 2rem;
    font-weight: 700;
    color: #2c3e50;
    margin: 0;
}

.section-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: #374151;
    margin: 2rem 0 1rem 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.metric-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem;
    border-radius: 12px;
    text-align: center;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.metric-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
}

.metric-card.students {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.metric-card.staff {
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
}

.metric-card.subjects {
    background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
}

.metric-card.parents {
    background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
    color: #2d3748;
}

.metric-card.users {
    background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
    color: #744210;
}

.metric-icon {
    font-size: 2.5rem;
    margin-bottom: 1rem;
    opacity: 0.9;
}

.metric-card h3 {
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    opacity: 0.9;
}

.metric-value {
    font-size: 3rem;
    font-weight: 700;
    margin: 0;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.card {
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    border: none;
    overflow: hidden;
    margin-bottom: 2rem;
}

.card-header {
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    color: #2d3748;
    padding: 1.5rem 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #e2e8f0;
}

.card-title {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.card-body {
    padding: 2rem;
}

.table-container {
    overflow-x: auto;
    margin: 1rem 0;
}

table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
    min-width: 600px;
}

thead th {
    background: #f1f5f9;
    color: #374151;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 12px;
    letter-spacing: 0.5px;
    padding: 1rem;
    text-align: left;
    border-bottom: 2px solid #e5e7eb;
}

tbody td {
    padding: 1rem;
    border-bottom: 1px solid #f3f4f6;
    vertical-align: middle;
}

tbody tr {
    transition: background-color 0.2s ease;
}

tbody tr:hover {
    background-color: #f8faff;
}

.chart-container {
    position: relative;
    height: 400px;
    margin: 1rem 0;
}

.charts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 2rem;
    margin: 2rem 0;
}

.chart-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.chart-card .card-header {
    padding: 1rem 1.5rem;
}

.chart-card .card-body {
    padding: 1rem 1.5rem 2rem;
}

.chart-card .chart-container {
    height: 300px;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    border: none;
    cursor: pointer;
    text-decoration: none;
    font-weight: 500;
    font-size: 0.875rem;
    transition: all 0.2s ease;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
}

.btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.8rem;
}

.empty-state {
    text-align: center;
    padding: 3rem;
    color: #6b7280;
    font-style: italic;
}

.stats-highlight {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-weight: 600;
}

@media (max-width: 768px) {
    .metrics-grid {
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
    }
    
    .metric-card {
        padding: 1.5rem;
    }
    
    .metric-value {
        font-size: 2rem;
    }
    
    .metric-icon {
        font-size: 2rem;
    }
    
    .charts-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .chart-card .chart-container {
        height: 250px;
    }
    
    .card-header {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }
}
</style>

<div class="content-header">
    <h1 class="title"><?= htmlspecialchars($pageTitle); ?></h1>
</div>

<h2 class="section-title"><i class="fas fa-th-large"></i> System Overview</h2>
<div class="metrics-grid">
    <div class="metric-card students">
        <div class="metric-icon"><i class="fas fa-user-graduate"></i></div>
        <h3>Total Students</h3>
        <div class="metric-value"><?= number_format($totalStudents) ?></div>
    </div>
    <div class="metric-card parents">
        <div class="metric-icon"><i class="fas fa-users"></i></div>
        <h3>Total Parents</h3>
        <div class="metric-value"><?= number_format($totalParents) ?></div>
    </div>
    <div class="metric-card subjects">
        <div class="metric-icon"><i class="fas fa-book"></i></div>
        <h3>Subjects Taught</h3>
        <div class="metric-value"><?= number_format($totalSubjects) ?></div>
    </div>
    <div class="metric-card users">
        <div class="metric-icon"><i class="fas fa-user-circle"></i></div>
        <h3>System Users</h3>
        <div class="metric-value"><?= number_format($totalUsers) ?></div>
    </div>
    <?php if ($staffTableExists): ?>
    <div class="metric-card staff">
        <div class="metric-icon"><i class="fas fa-chalkboard-teacher"></i></div>
        <h3>Staff Members</h3>
        <div class="metric-value"><?= number_format($totalStaff) ?></div>
    </div>
    <?php endif; ?>
</div>

<!-- Charts Grid -->
<div class="charts-grid">
    <!-- Class Distribution Chart -->
    <div class="chart-card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-chart-pie"></i> Students by Class</h3>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <canvas id="classDistributionChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Fee Status Chart -->
    <div class="chart-card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-money-bill-wave"></i> Fee Status Distribution</h3>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <canvas id="feeStatusChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Enrollment Trends -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-chart-line"></i> Student Enrollment Trends</h2>
    </div>
    <div class="card-body">
        <div class="chart-container">
            <canvas id="enrollmentTrendsChart"></canvas>
        </div>
    </div>
</div>

<!-- Academic Performance Analysis -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-chart-bar"></i> Academic Performance by Subject</h2>
        <button class="btn btn-primary btn-sm" onclick="exportTableToCSV('results-table', 'academic_performance.csv')">
            <i class="fas fa-download"></i> Export
        </button>
    </div>
    <div class="table-container">
        <table id="results-table">
            <thead>
                <tr>
                    <th>Subject</th>
                    <th>Total Results</th>
                    <th>Unique Students</th>
                    <th>Average Percentage</th>
                    <th>Performance Grade</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($resultsAnalysisResult && $resultsAnalysisResult->num_rows > 0): ?>
                    <?php while ($row = $resultsAnalysisResult->fetch_assoc()): ?>
                        <?php 
                            $avg_percentage = round($row['avg_percentage'], 1);
                            $grade = '';
                            $grade_color = '';
                            if ($avg_percentage >= 80) {
                                $grade = 'Excellent';
                                $grade_color = '#10b981';
                            } elseif ($avg_percentage >= 70) {
                                $grade = 'Good';
                                $grade_color = '#3b82f6';
                            } elseif ($avg_percentage >= 60) {
                                $grade = 'Average';
                                $grade_color = '#f59e0b';
                            } else {
                                $grade = 'Needs Improvement';
                                $grade_color = '#ef4444';
                            }
                        ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($row['subject']) ?></strong></td>
                            <td><?= number_format($row['total_results']) ?></td>
                            <td><?= number_format($row['unique_students']) ?></td>
                            <td><span class="stats-highlight"><?= $avg_percentage ?>%</span></td>
                            <td><span style="color: <?= $grade_color ?>; font-weight: 600;"><?= $grade ?></span></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="empty-state">No academic results data available.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Payment Analysis -->
<?php if ($paymentAnalysisResult && $paymentAnalysisResult->num_rows > 0): ?>
<div class="card">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-credit-card"></i> Recent Payment Activity</h2>
        <button class="btn btn-primary btn-sm" onclick="exportTableToCSV('payments-table', 'payment_analysis.csv')">
            <i class="fas fa-download"></i> Export
        </button>
    </div>
    <div class="table-container">
        <table id="payments-table">
            <thead>
                <tr>
                    <th>Month</th>
                    <th>Total Payments</th>
                    <th>Total Amount</th>
                    <th>Average Payment</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $paymentAnalysisResult->fetch_assoc()): ?>
                    <?php 
                        $month_name = date('F Y', mktime(0, 0, 0, $row['payment_month'], 1, $row['payment_year']));
                        $avg_payment = $row['total_payments'] > 0 ? $row['total_amount'] / $row['total_payments'] : 0;
                    ?>
                    <tr>
                        <td><strong><?= $month_name ?></strong></td>
                        <td><?= number_format($row['total_payments']) ?></td>
                        <td><span class="stats-highlight">$<?= number_format($row['total_amount'], 2) ?></span></td>
                        <td>$<?= number_format($avg_payment, 2) ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php include 'sa_footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Class Distribution Chart
    const classCtx = document.getElementById('classDistributionChart');
    if (classCtx && <?= json_encode($class_labels) ?>.length > 0) {
        new Chart(classCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($class_labels) ?>,
                datasets: [{
                    data: <?= json_encode($class_data) ?>,
                    backgroundColor: [
                        '#4facfe', '#00f2fe', '#43e97b', '#38f9d7',
                        '#fa709a', '#fee140', '#a8edea', '#fed6e3',
                        '#ffecd2', '#fcb69f', '#667eea', '#764ba2'
                    ],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' },
                    title: { display: true, text: 'Student Distribution by Class' }
                }
            }
        });
    }

    // Fee Status Chart
    const feeCtx = document.getElementById('feeStatusChart');
    if (feeCtx && <?= json_encode($fee_labels) ?>.length > 0) {
        new Chart(feeCtx, {
            type: 'pie',
            data: {
                labels: <?= json_encode($fee_labels) ?>,
                datasets: [{
                    data: <?= json_encode($fee_data) ?>,
                    backgroundColor: <?= json_encode($fee_chart_colors) ?>,
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' },
                    title: { display: true, text: 'Fee Payment Status' }
                }
            }
        });
    }

    // Enrollment Trends Chart
    const enrollmentCtx = document.getElementById('enrollmentTrendsChart');
    if (enrollmentCtx && <?= json_encode($enrollment_labels) ?>.length > 0) {
        new Chart(enrollmentCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode($enrollment_labels) ?>,
                datasets: [{
                    label: 'New Students',
                    data: <?= json_encode($enrollment_data) ?>,
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    title: { display: true, text: 'Monthly Student Enrollment' }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    }
});

// Export function
function exportTableToCSV(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) { 
        console.error('Table not found!'); 
        return; 
    }
    
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    for (let i = 0; i < rows.length; i++) {
        const row = [];
        const cols = rows[i].querySelectorAll('td, th');
        for (let j = 0; j < cols.length; j++) {
            // Clean the text content and escape quotes
            const cellText = cols[j].innerText.replace(/"/g, '""').replace(/\n/g, ' ').trim();
            row.push('"' + cellText + '"');
        }
        csv.push(row.join(','));
    }
    
    const csvContent = 'data:text/csv;charset=utf-8,' + csv.join('\n');
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement('a');
    link.setAttribute('href', encodedUri);
    link.setAttribute('download', filename);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
</script>