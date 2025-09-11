<?php
// session_start() must be the very first thing.
session_start();

// Define page-specific variables
$pageTitle = "Manage Parents";
$currentPage = "parents"; // This highlights the correct link in the sidebar

// Include database configuration
include '../config.php';

// Fetch all parents
$parentsQuery = "
    SELECT p.parent_id, p.user_id, p.phone_number, p.address, u.username, u.email 
    FROM parents p 
    JOIN users u ON p.user_id = u.id 
    WHERE u.role = 'parent'";
$parentsResult = $conn->query($parentsQuery);
if (!$parentsResult) {
    $fetch_error = "Error fetching parents: " . $conn->error;
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
    <div class="card-header">
        <h2 class="card-title">All Parent Accounts</h2>
        <div class="search-container">
            <i class="fas fa-search search-icon"></i>
            <input type="text" id="parentSearchInput" class="search-input" placeholder="Search by name, email, or ID...">
        </div>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Parent ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Phone Number</th>
                    <th>Address</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="parentTableBody">
                <?php if ($parentsResult && $parentsResult->num_rows > 0): ?>
                    <?php while ($parent = $parentsResult->fetch_assoc()): ?>
                        <tr>
                            <td><span class="badge">#<?= htmlspecialchars($parent['parent_id']) ?></span></td>
                            <td><?= htmlspecialchars($parent['username']) ?></td>
                            <td><?= htmlspecialchars($parent['email']) ?></td>
                            <td><?= htmlspecialchars($parent['phone_number'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($parent['address'] ?? 'N/A') ?></td>
                            <td><span class="status-pill status-cleared" style="min-width: 60px;">Active</span></td>
                            <td>
                                <div class="actions-container">
                                    <a href="edit_parent.php?parent_id=<?= $parent['parent_id'] ?>" class="btn-icon btn-edit" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php elseif (!isset($fetch_error)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 2rem;">No parent records found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
// Finally, include the footer.
include 'sa_footer.php';
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Search functionality for the parents table
    const searchInput = document.getElementById('parentSearchInput');
    const tableBody = document.getElementById('parentTableBody');
    const tableRows = tableBody.getElementsByTagName('tr');

    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const searchTerm = searchInput.value.toLowerCase();

            for (let i = 0; i < tableRows.length; i++) {
                const rowText = tableRows[i].textContent.toLowerCase();
                if (rowText.includes(searchTerm)) {
                    tableRows[i].style.display = '';
                } else {
                    tableRows[i].style.display = 'none';
                }
            }
        });
    }
});
</script>