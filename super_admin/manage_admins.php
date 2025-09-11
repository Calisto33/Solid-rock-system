<?php
// session_start() must be the very first thing.
session_start();

// Define page-specific variables
$pageTitle = "Manage Admins";
$currentPage = "admins"; // This will highlight the correct link in the sidebar

// Include database configuration
include '../config.php';

// Fetch all admins
$adminQuery = "SELECT id, username, email FROM users WHERE role = 'admin'";
$adminResult = $conn->query($adminQuery);
if (!$adminResult) {
    $fetch_error = "Error fetching admins: " . $conn->error;
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
        <h2 class="card-title">Administrator Accounts</h2>
        <div class="search-container">
            <i class="fas fa-search search-icon"></i>
            <input type="text" id="adminSearchInput" class="search-input" placeholder="Search admins...">
        </div>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Admin ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="adminTableBody">
                <?php if ($adminResult && $adminResult->num_rows > 0): ?>
                    <?php while ($admin = $adminResult->fetch_assoc()): ?>
                        <tr>
                            <td><span class="badge">#<?= htmlspecialchars($admin['id']) ?></span></td>
                            <td><?= htmlspecialchars($admin['username']) ?></td>
                            <td><?= htmlspecialchars($admin['email']) ?></td>
                            <td><span class="status-pill status-cleared" style="min-width: 60px;">Active</span></td>
                            <td>
                                <div class="actions-container">
                                    <a href="edit_admin.php?admin_id=<?= $admin['id'] ?>" class="btn-icon btn-edit" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php elseif (!isset($fetch_error)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 2rem;">No admin records found.</td>
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
    // Search functionality for the admins table
    const searchInput = document.getElementById('adminSearchInput');
    const tableBody = document.getElementById('adminTableBody');
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