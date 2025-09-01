<?php
// session_start() must be the very first thing.
session_start();

// Define page-specific variables
$pageTitle = "Student Records";
$currentPage = "records"; // This will highlight the correct link in the sidebar

// Include database configuration
include '../config.php';

// Fetch all students with their parents' names
$query = "
    SELECT 
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

$result = $conn->query($query);
if (!$result) {
    $fetch_error = "SQL Error: " . $conn->error;
}

// Get unique classes for filter dropdown
$class_query = "SELECT DISTINCT class FROM students WHERE student_id LIKE 'STU%' ORDER BY class";
$class_result = $conn->query($class_query);
$classes = [];
if ($class_result) {
    while ($row = $class_result->fetch_assoc()) {
        $classes[] = $row['class'];
    }
}

// --- Now, include the main header and sidebar ---
include 'sa_header.php';
?>

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
                <option value="<?= htmlspecialchars($class) ?>"><?= htmlspecialchars($class) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Class</th>
                    <th>Parent/Guardian</th>
                    <th>Relationship</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="recordsTableBody">
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <?php
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
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php elseif (!isset($fetch_error)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 20px; font-style: italic; color: #666;">
                            No student records found.
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('recordSearch');
    const classFilter = document.getElementById('classFilter');
    const tableBody = document.getElementById('recordsTableBody');
    const tableRows = tableBody.getElementsByTagName('tr');

    function filterTable() {
        const searchTerm = searchInput.value.toLowerCase();
        const classTerm = classFilter.value.toLowerCase();
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
            const matchesClass = classTerm === "" || className.includes(classTerm);

            if (matchesSearch && matchesClass) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        }
        
        // Update the count display if it exists
        const footerElement = document.querySelector('.table-footer p');
        if (footerElement && visibleCount !== tableRows.length) {
            footerElement.textContent = `Showing ${visibleCount} of ${tableRows.length} student${tableRows.length !== 1 ? 's' : ''}`;
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