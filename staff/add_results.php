<?php
// Set the page title for this specific page
$pageTitle = "Add Student Results";

// Include the new header. It handles security, session, db connection, and the sidebar.
include 'header.php';

// --- PHP LOGIC SPECIFIC TO THE ADD RESULTS PAGE ---
$studentsQuery = "SELECT s.student_id, s.username, s.class, s.year 
                  FROM students s
                  ORDER BY s.class, s.year DESC, s.username ASC";
$studentsResult = $conn->query($studentsQuery);

if (!$studentsResult) {
    die("Error executing students query: " . $conn->error);
}
?>

<style>
    .page-title::after {
        content: ''; position: absolute; bottom: 0; left: 0;
        width: 60px; height: 4px; background-color: var(--primary-color); border-radius: 2px;
    }
    .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
    .card-title { font-size: 1.2rem; font-weight: 600; }
    .search-container { display: flex; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem; }
    .search-input {
        flex-grow: 1; min-width: 250px; padding: 0.8rem 1rem; border: 1px solid #e2e8f0;
        border-radius: 12px; font-size: 0.95rem; transition: all 0.3s ease;
    }
    .search-input:focus {
        outline: none; border-color: var(--primary-color); box-shadow: 0 0 0 3px var(--primary-light);
    }
    .table-container { overflow-x: auto; border-radius: 12px; background-color: #ffffff; }
    .table { width: 100%; border-collapse: separate; border-spacing: 0; min-width: 600px; }
    .table th, .table td { padding: 1rem; text-align: left; }
    .table th {
        background-color: #f8fafc; font-weight: 600; font-size: 0.9rem;
        text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #e2e8f0;
    }
    .table td { border-bottom: 1px solid #e2e8f0; font-size: 0.95rem; }
    .table tbody tr:hover { background-color: #f8fafc; }
    .table tr:last-child td { border-bottom: none; }
    .btn {
        display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem;
        padding: 0.6rem 1.2rem; border-radius: 12px; font-weight: 500;
        cursor: pointer; transition: all 0.3s ease; text-decoration: none; border: none; font-size: 0.95rem;
    }
    .btn-primary { background-color: var(--primary-color); color: #ffffff; }
    .btn-primary:hover { background-color: #1d4ed8; }
    .btn-outline { background-color: transparent; color: var(--primary-color); border: 1px solid var(--primary-color); }
    .btn-outline:hover { background-color: #dbeafe; }
    .btn-success { background-color: #10b981; color: #ffffff; }
    .btn-success:hover { background-color: #0ca678; }
    .btn-sm { padding: 0.4rem 0.8rem; font-size: 0.85rem; }
    .badge { display: inline-block; padding: 0.35rem 0.7rem; border-radius: 999px; font-size: 0.75rem; font-weight: 500; }
    .badge-primary { background-color: #dbeafe; color: #1d4ed8; }
</style>

<h1 class="page-title">Student Results Management</h1>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Select Student to Add/Update Results</h2>
        <div>
            <button class="btn btn-outline" onclick="resetFilter()"><i class="fas fa-list"></i> Show All</button>
        </div>
    </div>

    <div class="search-container">
        <input type="text" id="studentSearchInput" class="search-input" placeholder="Search by student name, ID or class..." onkeyup="filterStudents()">
    </div>

    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Student ID</th>
                    <th>Student Name</th>
                    <th>Class</th>
                    <th>Year</th>
                    <th>Status</th>
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
                            <td><span class="badge badge-primary">Enrolled</span></td>
                            <td>
                                <a href="update_result.php?student_id=<?= htmlspecialchars($student['student_id']) ?>"
                                   class="btn btn-success btn-sm"><i class="fas fa-plus-circle"></i> Add/Edit Results</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr class="no-php-results">
                        <td colspan="6" style="text-align:center; padding: 2rem;">No students found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    function filterStudents() {
        const input = document.getElementById('studentSearchInput');
        const filter = input.value.toLowerCase();
        const tableBody = document.getElementById('studentsTableBody');
        const rows = tableBody.getElementsByTagName('tr');
        let visibleRowCount = 0;

        for (let i = 0; i < rows.length; i++) {
            if (rows[i].classList.contains('no-results-row')) continue; // Skip message rows

            const studentName = rows[i].dataset.name || "";
            const studentId = rows[i].dataset.id || "";
            const studentClass = rows[i].dataset.class || "";
            
            if (studentName.includes(filter) || studentId.includes(filter) || studentClass.includes(filter)) {
                rows[i].style.display = "";
                visibleRowCount++;
            } else {
                rows[i].style.display = "none";
            }
        }
        handleNoResultsMessage(tableBody, visibleRowCount);
    }

    function resetFilter() {
        document.getElementById('studentSearchInput').value = '';
        filterStudents(); // Just re-run the filter with an empty value
    }
    
    function handleNoResultsMessage(tableBody, visibleRowCount) {
        const noResultsRowId = 'no-matching-results-row';
        let noResultsRow = document.getElementById(noResultsRowId);

        if (visibleRowCount === 0) {
            if (!noResultsRow) {
                noResultsRow = tableBody.insertRow();
                noResultsRow.id = noResultsRowId;
                const cell = noResultsRow.insertCell();
                cell.colSpan = 6;
                cell.textContent = 'No matching students found for your search.';
                cell.style.textAlign = 'center';
                cell.style.padding = '2rem';
            }
        } else {
            if (noResultsRow) {
                noResultsRow.remove();
            }
        }
    }
    
    // Initial setup to handle the "no php results" case
    document.addEventListener('DOMContentLoaded', function() {
        const tableBody = document.getElementById('studentsTableBody');
        if (tableBody.getElementsByTagName('tr').length === 1 && tableBody.querySelector('.no-php-results')) {
            // Table is empty from the start, do nothing, the message is already there.
        }
    });
</script>

<?php
// Include the new footer to close the page layout
$conn->close();
include 'footer.php';
?>