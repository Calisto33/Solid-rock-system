<?php
// Set the page title for the header
$pageTitle = "Student List";

// Include the new header. It handles security, session, db connection, and the sidebar.
// Note: The included header already checks if the role is 'staff'.
include 'header.php';

// --- PHP LOGIC SPECIFIC TO THIS PAGE ---

// Pagination variables
$itemsPerPage = 10;
$currentPage = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($currentPage < 1) {
    $currentPage = 1;
}
$offset = ($currentPage - 1) * $itemsPerPage;

// Get total number of students for pagination
$totalStudentsQuery = "SELECT COUNT(*) as total FROM students";
$totalResult = $conn->query($totalStudentsQuery);
$totalRow = $totalResult->fetch_assoc();
$totalStudents = $totalRow['total'];
$totalPages = ceil($totalStudents / $itemsPerPage);

if ($currentPage > $totalPages && $totalPages > 0) {
    header("Location: student_list.php?page=" . $totalPages);
    exit();
}

// Fetch students for the current page
$studentsQuery = "SELECT s.student_id, s.username, s.class, s.year 
                  FROM students s
                  ORDER BY s.class ASC, s.year DESC, s.username ASC
                  LIMIT ? OFFSET ?";
$stmtStudents = $conn->prepare($studentsQuery);
$stmtStudents->bind_param("ii", $itemsPerPage, $offset);
$stmtStudents->execute();
$studentsResult = $stmtStudents->get_result();
?>

<style>
    .card { background-color: #ffffff; border-radius: 12px; box-shadow: var(--shadow); padding: 1.5rem; margin-bottom: 2rem; }
    .card-header-controls { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; }
    .search-container { display: flex; gap: 10px; margin-bottom: 20px; }
    .search-container input[type="text"] { flex-grow: 1; padding: 10px; border: 1px solid #ccc; border-radius: 4px; }
    .btn {
        display: inline-block; background-color: var(--primary-color); color: white; padding: 10px 15px;
        text-decoration: none; border: none; border-radius: 4px; cursor: pointer; text-align: center;
    }
    .btn-outline { background-color: #6c757d; }
    .table-container { overflow-x: auto; border: 1px solid #e0e0e0; border-radius: 5px; margin-bottom: 25px; }
    .table { width: 100%; border-collapse: collapse; }
    .table th, .table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #e7e7e7; }
    .table th { background-color: #f5f5f5; font-weight: bold; text-transform: uppercase; font-size: 0.9em; }
    .table tbody tr:hover { background-color: #f0f5ff; }
    .pagination { margin-top: 25px; text-align: center; }
    .pagination a, .pagination span { display: inline-block; padding: 8px 12px; margin: 0 3px; border: 1px solid #ddd; color: var(--primary-color); text-decoration: none; border-radius: 4px; }
    .pagination a:hover { background-color: #e9ecef; }
    .pagination .current-page { background-color: var(--primary-color); color: white; border-color: var(--primary-color); }
    .pagination .disabled { color: #aaa; border-color: #ddd; pointer-events: none; }
</style>

<h1 class="page-title">Student List & Results Management</h1>

<div class="card">
    <div class="card-header-controls">
        <h2>All Students (Page <?= $currentPage ?> of <?= $totalPages > 0 ? $totalPages : 1 ?>)</h2>
    </div>
    
    <div class="search-container">
        <input type="text" id="studentSearchInput" placeholder="Search by name, ID, or class on this page..." onkeyup="filterStudents()">
        <button class="btn btn-outline" onclick="resetFilter()"><i class="fas fa-undo"></i> Reset</button>
    </div>

    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Student ID</th>
                    <th>Student Name</th>
                    <th>Class</th>
                    <th>Year</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="studentsTableBody">
                <?php if ($studentsResult->num_rows > 0): ?>
                    <?php while ($student = $studentsResult->fetch_assoc()): ?>
                        <tr data-name="<?= strtolower(htmlspecialchars($student['username'])) ?>" 
                            data-id="<?= strtolower(htmlspecialchars($student['student_id'])) ?>"
                            data-class="<?= strtolower(htmlspecialchars($student['class'])) ?>">
                            <td><?= htmlspecialchars($student['student_id']) ?></td>
                            <td><?= htmlspecialchars($student['username']) ?></td>
                            <td><?= htmlspecialchars($student['class']) ?></td>
                            <td><?= htmlspecialchars($student['year']) ?></td>
                            <td>
                                <a href="student_results_history.php?student_id=<?= htmlspecialchars($student['student_id']) ?>" class="btn"><i class="fas fa-history"></i> View Results</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr class="no-php-results">
                        <td colspan="5" style="text-align:center; padding: 2rem;">No students found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($currentPage > 1): ?>
                <a href="?page=<?= ($currentPage - 1) ?>">&laquo; Previous</a>
            <?php else: ?>
                <span class="disabled">&laquo; Previous</span>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?>" class="<?= ($i == $currentPage) ? 'current-page' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>

            <?php if ($currentPage < $totalPages): ?>
                <a href="?page=<?= ($currentPage + 1) ?>">Next &raquo;</a>
            <?php else: ?>
                <span class="disabled">Next &raquo;</span>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
<script>
    function filterStudents() {
        const input = document.getElementById('studentSearchInput');
        const filter = input.value.toLowerCase().trim();
        const tableBody = document.getElementById('studentsTableBody');
        const rows = tableBody.getElementsByTagName('tr');

        for (let i = 0; i < rows.length; i++) {
            if (rows[i].classList.contains('no-php-results')) continue;

            const studentName = rows[i].dataset.name || "";
            const studentId = rows[i].dataset.id || "";
            const studentClass = rows[i].dataset.class || "";
            
            if (studentName.includes(filter) || studentId.includes(filter) || studentClass.includes(filter)) {
                rows[i].style.display = "";
            } else {
                rows[i].style.display = "none";
            }
        }
    }

    function resetFilter() {
        document.getElementById('studentSearchInput').value = '';
        filterStudents(); // Re-run filter to show all rows
    }
</script>


<?php
// Include the new footer to close the page layout
$stmtStudents->close();
$conn->close();
include 'footer.php';
?>