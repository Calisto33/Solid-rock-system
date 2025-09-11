<?php
// session_start() must be the very first thing.
session_start();

// Define page-specific variables
$pageTitle = "Manage Students";
$currentPage = "students";

// Include database configuration
include '../config.php';

// Fetch all students - Updated query for your school structure
$studentsQuery = "SELECT 
    student_id, 
    username, 
    first_name, 
    last_name, 
    course, 
    year, 
    class, 
    status,
    created_at 
FROM students 
ORDER BY created_at DESC";

$studentsResult = $conn->query($studentsQuery);
if (!$studentsResult) {
    $fetch_error = "Error fetching students: " . $conn->error;
}

// Get statistics for your school structure
$statsQuery = "SELECT 
    COUNT(*) as total_students,
    COUNT(CASE WHEN status = 'Active' THEN 1 END) as active_students,
    COUNT(CASE WHEN class LIKE 'ECD%' OR class LIKE 'Grade%' THEN 1 END) as primary_students,
    COUNT(CASE WHEN class LIKE 'Form%' THEN 1 END) as secondary_students
FROM students";

$statsResult = $conn->query($statsQuery);
$stats = $statsResult ? $statsResult->fetch_assoc() : [
    'total_students' => 0,
    'active_students' => 0,
    'primary_students' => 0,
    'secondary_students' => 0
];

// Function to determine class level and styling
function getClassInfo($class) {
    $info = [
        'level' => 'unknown',
        'type' => 'unknown',
        'icon' => '<i class="fas fa-question"></i>',
        'color_class' => 'class-unknown'
    ];
    
    if (empty($class) || $class === 'Unassigned') {
        return $info;
    }
    
    // Determine level (Primary or Secondary)
    if (strpos($class, 'ECD') !== false) {
        $info['level'] = 'ECD';
        $info['type'] = 'ECD';
        $info['icon'] = '<i class="fas fa-baby"></i>';
        $info['color_class'] = 'class-ecd';
    } elseif (strpos($class, 'Grade') !== false) {
        $info['level'] = 'Primary';
        $info['type'] = 'Primary';
        $info['icon'] = '<i class="fas fa-seedling"></i>';
        $info['color_class'] = 'class-primary';
    } elseif (strpos($class, 'Form') !== false) {
        $info['level'] = 'Secondary';
        $info['type'] = 'Secondary';
        $info['icon'] = '<i class="fas fa-graduation-cap"></i>';
        $info['color_class'] = 'class-secondary';
        
        // Further classify secondary forms
        if (strpos($class, 'Form 1') !== false || strpos($class, 'Form 2') !== false) {
            $info['color_class'] = 'class-lower-secondary';
        } elseif (strpos($class, 'Form 3') !== false || strpos($class, 'Form 4') !== false) {
            $info['color_class'] = 'class-o-level';
        } elseif (strpos($class, 'Form 5') !== false || strpos($class, 'Form 6') !== false) {
            $info['color_class'] = 'class-a-level';
        }
    }
    
    return $info;
}

// --- Now, include the main header and sidebar ---
include 'sa_header.php';
?>

<style>
/* Compact and browser-friendly styling */
.main-content {
    padding: 1rem;
    max-width: 100%;
    overflow-x: hidden;
}

.content-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.content-header .title {
    font-size: 1.75rem;
    font-weight: 700;
    color: #2c3e50;
    margin: 0;
}

/* Compact statistics cards */
.stats-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.stat-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1rem;
    border-radius: 8px;
    text-align: center;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
    min-height: auto;
}

.stat-card.primary {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.stat-card.secondary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.stat-card h3 {
    font-size: 1.5rem;
    margin: 0;
    font-weight: 700;
}

.stat-card p {
    margin: 0.5rem 0 0 0;
    opacity: 0.9;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

/* Compact card styling */
.card {
    background: #ffffff;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 70, 150, 0.08);
    border: none;
    overflow: hidden;
}

.card-header {
    background: #f8f9fa;
    padding: 1rem 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #eef2f7;
    flex-wrap: wrap;
    gap: 1rem;
}

.card-title {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0;
    color: #2c3e50;
}

.search-container {
    position: relative;
    width: 250px;
    min-width: 200px;
}

.search-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #999;
    z-index: 1;
}

.search-input {
    width: 100%;
    padding: 8px 12px 8px 35px;
    border: 2px solid #e9ecef;
    border-radius: 20px;
    background: white;
    font-size: 13px;
    transition: all 0.3s ease;
}

.search-input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

/* Responsive table */
.table-container {
    overflow-x: auto;
    border-radius: 8px;
    max-height: 60vh;
    overflow-y: auto;
}

table {
    width: 100%;
    min-width: 1000px;
    border-collapse: collapse;
    font-size: 13px;
    background: white;
}

/* Optimized column widths for better fit */
table th:nth-child(1), table td:nth-child(1) { width: 100px; } /* Student ID */
table th:nth-child(2), table td:nth-child(2) { width: 160px; } /* Full Name */
table th:nth-child(3), table td:nth-child(3) { width: 140px; } /* Username */
table th:nth-child(4), table td:nth-child(4) { width: 150px; } /* Class */
table th:nth-child(5), table td:nth-child(5) { width: 120px; } /* Course */
table th:nth-child(6), table td:nth-child(6) { width: 100px; } /* Year */
table th:nth-child(7), table td:nth-child(7) { width: 90px; }  /* Status */
table th:nth-child(8), table td:nth-child(8) { width: 140px; } /* Actions */

thead th {
    background: #f8f9fa;
    color: #495057;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 11px;
    letter-spacing: 0.3px;
    padding: 12px 10px;
    text-align: left;
    border-bottom: 2px solid #e9ecef;
    position: sticky;
    top: 0;
    z-index: 10;
}

tbody td {
    padding: 10px;
    border-bottom: 1px solid #f1f3f4;
    vertical-align: middle;
    word-wrap: break-word;
}

tbody tr {
    transition: background-color 0.2s ease;
}

tbody tr:hover {
    background-color: #f8faff;
}

/* Compact badges */
.badge {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    color: white;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 10px;
    font-weight: 600;
    display: inline-block;
    text-align: center;
}

/* Compact class badges */
.class-badge-with-icon {
    color: white;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 10px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    white-space: nowrap;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    max-width: 100%;
}

.class-badge-with-icon i {
    font-size: 9px;
}

/* Compact class colors */
.class-ecd {
    background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);
}

.class-primary {
    background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
    color: #2c3e50;
}

.class-lower-secondary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.class-o-level {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.class-a-level {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

/* Compact course tags */
.course-tag {
    background: #f8f9fa;
    color: #6c757d;
    padding: 3px 6px;
    border-radius: 8px;
    font-size: 9px;
    font-weight: 600;
    display: inline-block;
    text-align: center;
    border: 1px solid #e9ecef;
}

/* Compact status pills */
.status-pill {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    padding: 3px 6px;
    border-radius: 10px;
    font-size: 9px;
    font-weight: 600;
    color: #fff;
    justify-content: center;
}

.status-active { 
    background: #28a745;
}

.status-inactive { 
    background: #dc3545;
}

.status-graduated { 
    background: #6f42c1;
}

.status-transferred { 
    background: #fd7e14;
}

/* Compact buttons */
.btn {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 8px;
    border-radius: 6px;
    border: none;
    cursor: pointer;
    text-decoration: none;
    font-weight: 600;
    font-size: 10px;
    transition: all 0.2s ease;
}

.btn-primary {
    background: #667eea;
    color: white;
}

.btn-primary:hover {
    background: #5a67d8;
    color: white;
    text-decoration: none;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #5a6268;
    color: white;
    text-decoration: none;
}

.action-buttons {
    display: flex;
    gap: 4px;
    flex-wrap: wrap;
}

.notification {
    padding: 10px 15px;
    margin-bottom: 15px;
    border-radius: 6px;
    font-weight: 500;
    font-size: 14px;
}

.notification.error {
    background-color: #fff2f0;
    color: #ff4d4f;
    border-left: 3px solid #ff4d4f;
}

.empty-state {
    text-align: center;
    padding: 2rem;
    color: #6c757d;
    font-size: 1rem;
}

.empty-state i {
    font-size: 2rem;
    margin-bottom: 0.5rem;
    opacity: 0.5;
}

.table-footer {
    padding: 10px 15px;
    background-color: #f8f9fa;
    border-top: 1px solid #eef2f7;
    text-align: right;
    color: #666;
    font-size: 12px;
}

/* Mobile responsiveness */
@media (max-width: 768px) {
    .main-content {
        padding: 0.5rem;
    }
    
    .content-header {
        flex-direction: column;
        align-items: stretch;
    }
    
    .content-header .title {
        font-size: 1.5rem;
        text-align: center;
    }
    
    .stats-container {
        grid-template-columns: repeat(2, 1fr);
        gap: 0.5rem;
    }
    
    .stat-card {
        padding: 0.75rem;
    }
    
    .stat-card h3 {
        font-size: 1.25rem;
    }
    
    .card-header {
        flex-direction: column;
        align-items: stretch;
    }
    
    .search-container {
        width: 100%;
    }
    
    .table-container {
        max-height: 50vh;
    }
    
    table {
        min-width: 800px;
        font-size: 12px;
    }
    
    thead th {
        padding: 8px 6px;
        font-size: 10px;
    }
    
    tbody td {
        padding: 8px 6px;
    }
    
    .action-buttons {
        flex-direction: column;
        gap: 2px;
    }
}

@media (max-width: 480px) {
    .stats-container {
        grid-template-columns: 1fr;
    }
    
    table {
        min-width: 600px;
        font-size: 11px;
    }
}

/* Ensure proper spacing when page loads */
body {
    margin: 0;
    padding: 0;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    background-color: #f5f7fa;
}

/* Fix any potential overflow issues */
* {
    box-sizing: border-box;
}
</style>

<div class="main-content">
    <div class="content-header">
        <h1 class="title"><?= htmlspecialchars($pageTitle); ?></h1>
    </div>

    <!-- Compact Statistics Cards -->
    <div class="stats-container">
        <div class="stat-card">
            <h3><?= $stats['total_students'] ?></h3>
            <p>Total Students</p>
        </div>
        <div class="stat-card">
            <h3><?= $stats['active_students'] ?></h3>
            <p>Active Students</p>
        </div>
        <div class="stat-card primary">
            <h3><?= $stats['primary_students'] ?></h3>
            <p>Primary Students</p>
        </div>
        <div class="stat-card secondary">
            <h3><?= $stats['secondary_students'] ?></h3>
            <p>Secondary Students</p>
        </div>
    </div>

    <?php if (isset($fetch_error)): ?>
        <div class="notification error">
            <strong>Error!</strong> <?= htmlspecialchars($fetch_error); ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">All Students (<?= $stats['total_students'] ?>)</h2>
            <div class="search-container">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="searchInput" class="search-input" placeholder="Search students...">
            </div>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>Full Name</th>
                        <th>Username</th>
                        <th>Class</th>
                        <th>Combination</th>
                        <th>Year</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="studentTableBody">
                    <?php if ($studentsResult && $studentsResult->num_rows > 0): ?>
                        <?php while ($student = $studentsResult->fetch_assoc()): ?>
                            <?php
                                $fullName = trim(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? ''));
                                $status = $student['status'] ?? 'Active';
                                $statusClass = 'status-' . strtolower(str_replace(' ', '-', $status));
                                $classInfo = getClassInfo($student['class'] ?? '');
                            ?>
                            <tr>
                                <td>
                                    <span class="badge"><?= htmlspecialchars($student['student_id']) ?></span>
                                </td>
                                <td>
                                    <strong style="font-size: 12px;"><?= htmlspecialchars($fullName ?: 'N/A') ?></strong>
                                </td>
                                <td style="font-size: 12px;"><?= htmlspecialchars($student['username'] ?? 'N/A') ?></td>
                                <td>
                                    <?php if (!empty($student['class']) && $student['class'] !== 'Unassigned'): ?>
                                        <span class="class-badge-with-icon <?= $classInfo['color_class'] ?>" title="<?= $classInfo['type'] ?>">
                                            <?= $classInfo['icon'] ?>
                                            <span style="font-size: 9px;"><?= htmlspecialchars($student['class']) ?></span>
                                        </span>
                                    <?php else: ?>
                                        <span style="color: #ffa726; font-style: italic; font-size: 10px;">
                                            <i class="fas fa-exclamation-triangle"></i> Unassigned
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($student['course']) && $student['course'] !== 'Unassigned'): ?>
                                        <span class="course-tag"><?= htmlspecialchars($student['course']) ?></span>
                                    <?php else: ?>
                                        <span style="color: #ffa726; font-style: italic; font-size: 10px;">Unassigned</span>
                                    <?php endif; ?>
                                </td>
                                <td style="font-size: 11px;"><?= htmlspecialchars($student['year'] ?? 'N/A') ?></td>
                                <td>
                                    <span class="status-pill <?= $statusClass ?>">
                                        <i class="fas <?= $status === 'Active' ? 'fa-check-circle' : ($status === 'Inactive' ? 'fa-times-circle' : 'fa-graduation-cap') ?>"></i>
                                        <span style="font-size: 9px;"><?= htmlspecialchars($status) ?></span>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="edit_student.php?student_id=<?= urlencode($student['student_id']) ?>" 
                                           class="btn btn-primary" title="Edit Student">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php elseif (!isset($fetch_error)): ?>
                        <tr>
                            <td colspan="8" class="empty-state">
                                <i class="fas fa-users"></i><br>
                                No student records found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($studentsResult && $studentsResult->num_rows > 0): ?>
            <div class="table-footer">
                <p>Showing <?= $studentsResult->num_rows ?> student<?= $studentsResult->num_rows !== 1 ? 's' : '' ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Finally, include the footer.
include 'sa_footer.php';
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Enhanced search functionality for the students table
    const searchInput = document.getElementById('searchInput');
    const tableBody = document.getElementById('studentTableBody');
    const tableRows = tableBody.getElementsByTagName('tr');

    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const searchTerm = searchInput.value.toLowerCase().trim();
            let visibleCount = 0;

            for (let i = 0; i < tableRows.length; i++) {
                // Skip the "no records" row
                if (tableRows[i].children.length === 1) {
                    continue;
                }

                const rowText = tableRows[i].textContent.toLowerCase();
                if (searchTerm === '' || rowText.includes(searchTerm)) {
                    tableRows[i].style.display = '';
                    visibleCount++;
                } else {
                    tableRows[i].style.display = 'none';
                }
            }
            
            // Update the footer count
            const footerElement = document.querySelector('.table-footer p');
            if (footerElement && searchTerm !== '') {
                footerElement.textContent = `Showing ${visibleCount} of ${tableRows.length} student${tableRows.length !== 1 ? 's' : ''}`;
            } else if (footerElement) {
                footerElement.textContent = `Showing ${tableRows.length} student${tableRows.length !== 1 ? 's' : ''}`;
            }
        });
    }
});
</script>