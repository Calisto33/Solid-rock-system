<?php
// Set the page title for the header
$pageTitle = "Resource Management";

// Include the new header. It handles security, session, db connection, and the sidebar.
include 'header.php';

// --- PHP LOGIC SPECIFIC TO THIS PAGE ---

// NOTE: header.php provides $staff_id as the user's ID.
// This page uses a specific staff_id from the `staff` table, so we fetch it.
$staffIdQuery = "SELECT staff_id FROM staff WHERE id = ?";
$stmt_staff = $conn->prepare($staffIdQuery);
if (!$stmt_staff) die("Prepare failed: ". $conn->error);
$stmt_staff->bind_param("i", $staff_id);
$stmt_staff->execute();
$result_staff = $stmt_staff->get_result();
$staffData = $result_staff->fetch_assoc();
$stmt_staff->close();

if (!$staffData) {
    die("Error: This user is not registered as a staff member with a staff_id.");
}
$specific_staff_id = $staffData['staff_id'];

// Fetch all resources posted by this staff member
$resourcesQuery = "SELECT resource_id, department, description, resource_link, upload_date FROM student_resources WHERE staff_id = ?";
$stmt_resources = $conn->prepare($resourcesQuery);
$stmt_resources->bind_param("i", $specific_staff_id);
$stmt_resources->execute();
$result = $stmt_resources->get_result();
?>

<style>
    .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem; }
    .page-title { font-size: 1.75rem; font-weight: 600; color: var(--secondary); }
    .btn {
        padding: 0.6rem 1.25rem; border-radius: 8px; font-weight: 500; font-size: 0.95rem; text-decoration: none;
        transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 0.5rem; border: none; cursor: pointer;
    }
    .btn-primary { background-color: var(--primary); color: var(--white); }
    .btn-primary:hover { background-color: #1d4ed8; transform: translateY(-2px); box-shadow: var(--shadow); }
    .card-header { padding: 1.25rem; border-bottom: 1px solid #e2e8f0; }
    .card-title { font-size: 1.25rem; font-weight: 600; }
    .card-body { padding: 0; }
    .table-container { overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; text-align: left; }
    th {
        background-color: #f1f5f9; color: #1e293b; font-weight: 600; padding: 1rem 1.5rem;
        font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #e2e8f0;
    }
    td { padding: 1rem 1.5rem; border-bottom: 1px solid #e2e8f0; color: #64748b; font-size: 0.95rem; }
    tr:hover { background-color: rgba(37, 99, 235, 0.03); }
    tr:last-child td { border-bottom: none; }
    .resource-link { color: var(--primary); text-decoration: none; font-weight: 500; display: inline-flex; align-items: center; gap: 0.35rem; }
    .resource-link:hover { color: #1d4ed8; text-decoration: underline; }
    .action-btn {
        padding: 0.45rem 1rem; border-radius: 6px; font-weight: 500; font-size: 0.85rem;
        text-decoration: none; display: inline-flex; align-items: center; gap: 0.35rem;
    }
    .btn-update { background-color: #f1f5f9; color: #1e293b; }
    .btn-update:hover { background-color: #e2e8f0; }
    .btn-delete { background-color: rgba(239, 68, 68, 0.1); color: #ef4444; }
    .btn-delete:hover { background-color: #ef4444; color: var(--white); }
    .department-tag {
        padding: 0.35rem 0.75rem; border-radius: 50px; font-size: 0.8rem; font-weight: 500;
        background-color: rgba(37, 99, 235, 0.1); color: var(--primary); display: inline-block;
    }
    .action-cell { display: flex; gap: 0.5rem; }
</style>

<div class="page-header">
    <h1 class="page-title">Resource Management</h1>
    <a href="add_resource.php" class="btn btn-primary">
        <i class="fas fa-plus"></i>
        Add New Resource
    </a>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">My Uploaded Resources</h3>
    </div>
    <div class="card-body">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Department</th>
                        <th>Description</th>
                        <th>Resource</th>
                        <th>Upload Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($resource = $result->fetch_assoc()): ?>
                            <tr>
                                <td><span class="department-tag"><?= htmlspecialchars($resource['department']) ?></span></td>
                                <td><?= htmlspecialchars($resource['description']) ?></td>
                                <td>
                                    <a href="<?= htmlspecialchars($resource['resource_link']) ?>" class="resource-link" target="_blank">
                                        <i class="fas fa-external-link-alt"></i> View Resource
                                    </a>
                                </td>
                                <td><?= date("d M, Y", strtotime($resource['upload_date'])) ?></td>
                                <td class="action-cell">
                                    <a href="update_resource.php?resource_id=<?= htmlspecialchars($resource['resource_id']) ?>" class="action-btn btn-update">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="delete_resource.php?resource_id=<?= htmlspecialchars($resource['resource_id']) ?>" class="action-btn btn-delete" onclick="return confirm('Are you sure you want to delete this resource?');">
                                        <i class="fas fa-trash-alt"></i> Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 2rem;">You have not uploaded any resources yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
// Include the new footer to close the page layout
$stmt_resources->close();
$conn->close();
include 'footer.php';
?>