<?php
// session_start() must be the very first thing.
session_start();

// Define page-specific variables
$pageTitle = "Student Records";
$currentPage = "records"; // This will highlight the correct link in the sidebar

// Include database configuration
include '../config.php';

// Function to get formatted student name (same as in fees.php)
function getFormattedStudentName($first_name, $last_name, $username, $student_id) {
    $first_name = trim($first_name ?? '');
    $last_name = trim($last_name ?? '');
    $username = trim($username ?? '');
    
    // If we have both first and last name
    if (!empty($first_name) && !empty($last_name)) {
        return $first_name . ' ' . $last_name;
    }
    // If we have only first name
    elseif (!empty($first_name)) {
        return $first_name;
    }
    // If we have only last name
    elseif (!empty($last_name)) {
        return $last_name;
    }
    // If we have username
    elseif (!empty($username)) {
        return $username;
    }
    // Fallback to student ID
    else {
        return 'Student ' . $student_id;
    }
}

// Fetch all students with their parents' names - FIXED QUERY
$query = "
    SELECT 
<<<<<<< HEAD
        s.student_id,
        CONCAT(s.first_name, ' ', s.last_name) AS student_name,
        s.class,
        CONCAT(u_parent.first_name, ' ', u_parent.last_name) AS parent_name,
        p.relationship,
        s.status
    FROM students s
    LEFT JOIN parents p ON p.student_id = s.student_id
    LEFT JOIN users u_parent ON p.user_id = u_parent.id
    WHERE s.student_id LIKE 'STU%'
    ORDER BY 
        CASE 
            WHEN s.class LIKE 'Form 1%' THEN 1
            WHEN s.class LIKE 'Form 2%' THEN 2
            WHEN s.class LIKE 'Form 3%' THEN 3
            WHEN s.class LIKE 'Form 4%' THEN 4
            WHEN s.class LIKE 'Form 5%' THEN 5
            WHEN s.class LIKE 'Form 6%' THEN 6
            ELSE 7
        END,
        s.class, 
        s.first_name, 
        s.last_name";
=======
        s.student_id, 
        s.first_name,
        s.last_name,
        s.username,
        s.class,
        s.status,
        CASE 
            WHEN TRIM(CONCAT(COALESCE(s.first_name, ''), ' ', COALESCE(s.last_name, ''))) != '' 
            THEN TRIM(CONCAT(COALESCE(s.first_name, ''), ' ', COALESCE(s.last_name, '')))
            WHEN s.username IS NOT NULL AND s.username != '' 
            THEN s.username
            ELSE CONCAT('Student ', s.student_id)
        END AS student_name_computed,
        (SELECT GROUP_CONCAT(CONCAT(u.username, ' (', p.relationship, ')') ORDER BY p.parent_id SEPARATOR ', ') 
         FROM parents p 
         INNER JOIN users u ON p.user_id = u.id 
         WHERE p.student_id = s.student_id) AS parent_names
    FROM students s
    ORDER BY s.class, s.student_id";
>>>>>>> b291daf7f49078bb0cccb1439969ad4a74e2db38

$result = $conn->query($query);
if (!$result) {
    $fetch_error = "SQL Error: " . $conn->error;
}

// Get unique classes for filter dropdown
<<<<<<< HEAD
$class_query = "SELECT DISTINCT class FROM students WHERE student_id LIKE 'STU%' ORDER BY class";
$class_result = $conn->query($class_query);
$classes = [];
if ($class_result) {
    while ($row = $class_result->fetch_assoc()) {
        $classes[] = $row['class'];
    }
=======
$classes_query = "SELECT DISTINCT class FROM students WHERE class IS NOT NULL AND class != '' ORDER BY class ASC";
$classes_result = $conn->query($classes_query);
$classes = [];
if ($classes_result) {
    $classes = $classes_result->fetch_all(MYSQLI_ASSOC);
>>>>>>> b291daf7f49078bb0cccb1439969ad4a74e2db38
}

// --- Now, include the main header and sidebar ---
include 'sa_header.php';
?>

<style>
/* Additional styles for student records page */
.search-filter-container {
    padding: 20px;
    background-color: #f8f9fa;
    border-bottom: 1px solid #eef2f7;
    display: flex;
    gap: 20px;
    align-items: center;
    flex-wrap: wrap;
}

.search-box {
    position: relative;
    flex: 1;
    min-width: 300px;
}

.search-box .search-icon {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #6b7280;
    z-index: 1;
}

.search-box input {
    width: 100%;
    padding: 12px 15px 12px 45px;
    border: 1px solid #dcdfe6;
    border-radius: 8px;
    font-size: 14px;
    background-color: #fff;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.search-box input:focus {
    outline: none;
    border-color: #409eff;
    box-shadow: 0 0 0 3px rgba(64, 158, 255, 0.1);
}

.search-filter-container select {
    padding: 12px 15px;
    border: 1px solid #dcdfe6;
    border-radius: 8px;
    background-color: #fff;
    font-size: 14px;
    min-width: 150px;
    cursor: pointer;
}

.search-filter-container select:focus {
    outline: none;
    border-color: #409eff;
    box-shadow: 0 0 0 3px rgba(64, 158, 255, 0.1);
}

.table-container {
    overflow-x: auto;
}

.table-container table {
    width: 100%;
    border-collapse: collapse;
    background: white;
}

.table-container th,
.table-container td {
    padding: 16px 20px;
    text-align: left;
    border-bottom: 1px solid #eef2f7;
    vertical-align: middle;
}

.table-container thead th {
    background-color: #34495e;
    color: #ffffff;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 12px;
    letter-spacing: 0.5px;
    position: sticky;
    top: 0;
    z-index: 10;
}

.table-container tbody tr {
    transition: background-color 0.25s ease;
}

.table-container tbody tr:hover {
    background-color: #f8faff;
}

.table-container tbody tr:last-child td {
    border-bottom: none;
}

.badge {
    display: inline-block;
    padding: 4px 12px;
    background-color: #e3f2fd;
    color: #1976d2;
    border-radius: 16px;
    font-size: 12px;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    color: #fff;
    text-transform: capitalize;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.status-active { 
    background-image: linear-gradient(45deg, #2ed573, #219d55); 
}

.status-inactive { 
    background-image: linear-gradient(45deg, #ff6b6b, #ee5a52); 
}

.status-pending { 
    background-image: linear-gradient(45deg, #ffc107, #ff8c00); 
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    border: none;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 500;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-primary {
    background-color: #409eff;
    color: white;
    box-shadow: 0 2px 4px rgba(64, 158, 255, 0.2);
}

.btn-primary:hover {
    background-color: #3a8ee6;
    box-shadow: 0 4px 8px rgba(64, 158, 255, 0.3);
    transform: translateY(-1px);
}

.btn-sm {
    padding: 6px 12px;
    font-size: 12px;
}

.no-results {
    text-align: center;
    padding: 3rem;
    color: #6b7280;
    font-size: 1.1em;
}

.no-results i {
    font-size: 3rem;
    margin-bottom: 1rem;
    color: #d1d5db;
}

@media (max-width: 768px) {
    .search-filter-container {
        flex-direction: column;
        align-items: stretch;
    }
    
    .search-box {
        min-width: auto;
    }
    
    .table-container {
        font-size: 14px;
    }
    
    .table-container th,
    .table-container td {
        padding: 12px 8px;
    }
}
</style>

<div class="content-header">
    <h1 class="title"><?= htmlspecialchars($pageTitle); ?></h1>
</div>

<?php if (isset($fetch_error)): ?>
    <div class="notification error">
        <strong>Error!</strong> <?= htmlspecialchars($fetch_error); ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="search-filter-container">
        <div class="search-box">
            <i class="fas fa-search search-icon"></i>
            <input type="text" placeholder="Search students or parents..." id="recordSearch">
        </div>
        <select id="classFilter">
            <option value="">All Classes</option>
            <?php foreach ($classes as $class): ?>
<<<<<<< HEAD
                <option value="<?= htmlspecialchars($class) ?>"><?= htmlspecialchars($class) ?></option>
=======
                <option value="<?= htmlspecialchars($class['class']); ?>">
                    <?= htmlspecialchars($class['class']); ?>
                </option>
>>>>>>> b291daf7f49078bb0cccb1439969ad4a74e2db38
            <?php endforeach; ?>
        </select>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Student ID</th>
                    <th>Student Name</th>
                    <th>Class</th>
<<<<<<< HEAD
                    <th>Parent/Guardian</th>
                    <th>Relationship</th>
=======
                    <th>Parent(s)</th>
>>>>>>> b291daf7f49078bb0cccb1439969ad4a74e2db38
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="recordsTableBody">
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <?php
<<<<<<< HEAD
                            // Determine status styling
                            $status = $row['status'] ?? 'Active';
                            $status_class = strtolower(str_replace(' ', '-', $status));
                            
                            // Set status colors
                            $status_colors = [
                                'active' => 'status-cleared',
                                'inactive' => 'status-overdue', 
                                'graduated' => 'status-pending',
                                'transferred' => 'status-no-fee-assigned'
                            ];
                            $pill_class = $status_colors[$status_class] ?? 'status-cleared';
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($row['student_name']) ?></td>
                            <td><span class="badge"><?= htmlspecialchars($row['class']) ?></span></td>
                            <td><?= htmlspecialchars($row['parent_name'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($row['relationship'] ?? 'N/A') ?></td>
                            <td><span class="status-pill <?= $pill_class ?>" style="min-width: 60px;"><?= htmlspecialchars($status) ?></span></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="view_results.php?student_id=<?= urlencode($row['student_id']) ?>" 
                                       class="btn btn-primary btn-sm" title="View Academic Results">
                                        <i class="fas fa-chart-bar"></i> Results
                                    </a>
                                    <a href="student_profile.php?student_id=<?= urlencode($row['student_id']) ?>" 
                                       class="btn btn-secondary btn-sm" title="View Student Profile">
                                        <i class="fas fa-user"></i> Profile
                                    </a>
                                </div>
=======
                            $status_class = strtolower($row['status'] ?? 'active');
                            $status_display = ucfirst($row['status'] ?? 'Active');
                        ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($row['student_id']) ?></strong></td>
                            <td><?= htmlspecialchars($row['student_name_computed']) ?></td>
                            <td>
                                <span class="badge">
                                    <?= htmlspecialchars($row['class'] ?? 'Unassigned') ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($row['parent_names'] ?? 'No parent assigned') ?></td>
                            <td>
                                <span class="status-pill status-<?= $status_class ?>">
                                    <i class="fas fa-circle"></i>
                                    <?= htmlspecialchars($status_display) ?>
                                </span>
                            </td>
                            <td>
                                <a href="view_results.php?student_id=<?= htmlspecialchars($row['student_id']) ?>" 
                                   class="btn btn-primary btn-sm" 
                                   title="View academic results">
                                    <i class="fas fa-chart-bar"></i> View Results
                                </a>
>>>>>>> b291daf7f49078bb0cccb1439969ad4a74e2db38
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php elseif (!isset($fetch_error)): ?>
                    <tr>
<<<<<<< HEAD
                        <td colspan="6" style="text-align: center; padding: 20px; font-style: italic; color: #666;">
                            No student records found.
=======
                        <td colspan="6" class="no-results">
                            <div>
                                <i class="fas fa-users"></i><br>
                                No student records found.
                            </div>
>>>>>>> b291daf7f49078bb0cccb1439969ad4a74e2db38
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <?php if ($result && $result->num_rows > 0): ?>
        <div class="table-footer">
            <p>Showing <?= $result->num_rows ?> student<?= $result->num_rows !== 1 ? 's' : '' ?></p>
        </div>
    <?php endif; ?>
</div>
<<<<<<< HEAD

<style>
/* Additional styles for the student records page */
.search-filter-container {
    display: flex;
    gap: 20px;
    align-items: center;
    padding: 20px;
    background-color: #f8f9fa;
    border-bottom: 1px solid #eef2f7;
}

.search-box {
    position: relative;
    flex: 1;
    max-width: 400px;
}

.search-box input {
    width: 100%;
    padding: 10px 15px 10px 40px;
    border: 1px solid #dcdfe6;
    border-radius: 8px;
    font-size: 14px;
}

.search-icon {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #909399;
}

.badge {
    background: linear-gradient(45deg, #409eff, #3a8ee6);
    color: white;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.action-buttons {
    display: flex;
    gap: 8px;
}

.btn-sm {
    padding: 6px 12px;
    font-size: 12px;
}

.table-footer {
    padding: 15px 20px;
    background-color: #f8f9fa;
    border-top: 1px solid #eef2f7;
    text-align: right;
    color: #666;
    font-size: 14px;
}
</style>

<?php
// Finally, include the footer.
include 'sa_footer.php';
?>
=======
>>>>>>> b291daf7f49078bb0cccb1439969ad4a74e2db38

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('recordSearch');
    const classFilter = document.getElementById('classFilter');
    const tableBody = document.getElementById('recordsTableBody');
    const tableRows = tableBody.getElementsByTagName('tr');

    function filterTable() {
        const searchTerm = searchInput.value.toLowerCase();
        const classTerm = classFilter.value.toLowerCase();
<<<<<<< HEAD
        let visibleCount = 0;

        for (let i = 0; i < tableRows.length; i++) {
            const row = tableRows[i];
            
            // Skip empty state row
            if (row.cells.length < 6) {
                continue;
            }
            
            const studentName = row.cells[0].textContent.toLowerCase();
            const className = row.cells[1].textContent.toLowerCase();
            const parentName = row.cells[2].textContent.toLowerCase();
            const relationship = row.cells[3].textContent.toLowerCase();
            
            const matchesSearch = studentName.includes(searchTerm) || 
                                  parentName.includes(searchTerm) ||
                                  relationship.includes(searchTerm);
=======
        let visibleRows = 0;

        for (let i = 0; i < tableRows.length; i++) {
            const row = tableRows[i];
            
            // Skip if this is the "no results" row
            if (row.cells.length === 1 && row.cells[0].colSpan > 1) {
                continue;
            }
            
            const studentId = row.cells[0].textContent.toLowerCase();
            const studentName = row.cells[1].textContent.toLowerCase();
            const className = row.cells[2].textContent.toLowerCase();
            const parentNames = row.cells[3].textContent.toLowerCase();
            
            const matchesSearch = searchTerm === "" || 
                                studentId.includes(searchTerm) ||
                                studentName.includes(searchTerm) || 
                                parentNames.includes(searchTerm);
>>>>>>> b291daf7f49078bb0cccb1439969ad4a74e2db38
            const matchesClass = classTerm === "" || className.includes(classTerm);

            if (matchesSearch && matchesClass) {
                row.style.display = '';
<<<<<<< HEAD
                visibleCount++;
=======
                visibleRows++;
>>>>>>> b291daf7f49078bb0cccb1439969ad4a74e2db38
            } else {
                row.style.display = 'none';
            }
        }
<<<<<<< HEAD
        
        // Update the count display if it exists
        const footerElement = document.querySelector('.table-footer p');
        if (footerElement && visibleCount !== tableRows.length) {
            footerElement.textContent = `Showing ${visibleCount} of ${tableRows.length} student${tableRows.length !== 1 ? 's' : ''}`;
=======

        // Show/hide "no results" message
        const noResultsRow = tableBody.querySelector('tr td[colspan]');
        if (noResultsRow) {
            const noResultsRowElement = noResultsRow.parentElement;
            if (visibleRows === 0 && (searchTerm !== "" || classTerm !== "")) {
                noResultsRowElement.style.display = '';
                noResultsRow.innerHTML = `
                    <div class="no-results">
                        <i class="fas fa-search"></i><br>
                        No students match your search criteria.
                    </div>
                `;
            } else if (visibleRows > 0) {
                noResultsRowElement.style.display = 'none';
            }
>>>>>>> b291daf7f49078bb0cccb1439969ad4a74e2db38
        }
    }

    if (searchInput) {
        searchInput.addEventListener('keyup', filterTable);
    }
    if (classFilter) {
        classFilter.addEventListener('change', filterTable);
    }
});
</script>

<?php
// Finally, include the footer.
include 'sa_footer.php';
?>