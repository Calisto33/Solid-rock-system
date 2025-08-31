<?php
// session_start() must be the very first thing.
session_start();
include '../config.php';

// Check if the user is logged in as super admin
if (!isset($_SESSION['super_admin_id'])) {
    header("Location: super_admin_login.php");
    exit();
}

// --- IMPORTANT: Handle Actions (Toggle, Delete) BEFORE loading the page ---
$message = '';
$message_type = '';

// Handle activation/deactivation
if (isset($_GET['toggle_status']) && isset($_GET['super_admin_id'])) {
    $super_admin_id = intval($_GET['super_admin_id']);
    $current_status = $_GET['toggle_status'];
    // Prevent self-deactivation
    if ($super_admin_id == $_SESSION['super_admin_id']) {
        $message = "Error: You cannot change your own status.";
        $message_type = 'error';
    } else {
        $new_status = $current_status == 'active' ? 'inactive' : 'active';
        $toggleQuery = "UPDATE super_admins SET status = ? WHERE super_admin_id = ?";
        $stmt = $conn->prepare($toggleQuery);
        $stmt->bind_param("si", $new_status, $super_admin_id);
        if ($stmt->execute()) {
            header("Location: manage_super_admins.php"); // Redirect to clean the URL
            exit();
        } else {
            $message = "Error updating status: " . $stmt->error;
            $message_type = 'error';
        }
        $stmt->close();
    }
}

// Handle deletion
if (isset($_GET['delete']) && isset($_GET['super_admin_id'])) {
    $super_admin_id = intval($_GET['super_admin_id']);
     // Prevent self-deletion
    if ($super_admin_id == $_SESSION['super_admin_id']) {
        $message = "Error: You cannot delete your own account.";
        $message_type = 'error';
    } else {
        $deleteQuery = "DELETE FROM super_admins WHERE super_admin_id = ?";
        $stmt = $conn->prepare($deleteQuery);
        $stmt->bind_param("i", $super_admin_id);
        if ($stmt->execute()) {
            header("Location: manage_super_admins.php"); // Redirect to clean the URL
            exit();
        } else {
            $message = "Error deleting super admin: " . $stmt->error;
            $message_type = 'error';
        }
        $stmt->close();
    }
}


// --- Page Setup ---
$pageTitle = "Manage Super Admins";
$currentPage = "super_admins"; // This highlights the correct link in the sidebar

// Fetch all super admins
$superAdminQuery = "SELECT * FROM super_admins";
$superAdminResult = $conn->query($superAdminQuery);
if (!$superAdminResult) {
    $fetch_error = "Error fetching super admins: " . $conn->error;
}

// --- Now, include the main header and sidebar ---
include 'sa_header.php';
?>

<div class="content-header">
    <h1 class="title"><?= htmlspecialchars($pageTitle); ?></h1>
    <a href="add_super_admin.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> Add Super Admin
    </a>
</div>

<?php if (!empty($message)): ?>
    <div class="notification <?= $message_type; ?>">
        <?= htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<?php if (isset($fetch_error)): ?>
    <div class="notification error">
        <strong>Error!</strong> <?= htmlspecialchars($fetch_error); ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Super Administrator Accounts</h2>
    </div>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($superAdminResult && $superAdminResult->num_rows > 0): ?>
                    <?php while ($superAdmin = $superAdminResult->fetch_assoc()): ?>
                        <tr>
                            <td><span class="badge">#<?= htmlspecialchars($superAdmin['super_admin_id']) ?></span></td>
                            <td>
                                <div class="user-cell">
                                    <div class="table-avatar">
                                        <?= strtoupper(substr(htmlspecialchars($superAdmin['username']), 0, 1)) ?>
                                    </div>
                                    <?= htmlspecialchars($superAdmin['username']) ?>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($superAdmin['email']) ?></td>
                            <td>
                                <span class="status-pill <?= $superAdmin['status'] == 'active' ? 'status-cleared' : 'status-not-set' ?>">
                                    <?= ucfirst(htmlspecialchars($superAdmin['status'])) ?>
                                </span>
                            </td>
                            <td>
                                <div class="actions-container">
                                    <a href="?toggle_status=<?= htmlspecialchars($superAdmin['status']) ?>&super_admin_id=<?= $superAdmin['super_admin_id'] ?>" 
                                       class="btn btn-sm <?= $superAdmin['status'] == 'active' ? 'btn-warning' : 'btn-success' ?>"
                                       title="<?= $superAdmin['status'] == 'active' ? 'Deactivate' : 'Activate' ?>">
                                        <?php if($superAdmin['status'] == 'active'): ?>
                                            <i class="fas fa-user-slash"></i> Deactivate
                                        <?php else: ?>
                                            <i class="fas fa-user-check"></i> Activate
                                        <?php endif; ?>
                                    </a>
                                    <a href="?delete=true&super_admin_id=<?= $superAdmin['super_admin_id'] ?>" 
                                       class="btn btn-sm btn-danger" 
                                       title="Delete"
                                       onclick="return confirm('Are you sure you want to permanently delete this super admin?');">
                                        <i class="fas fa-trash-alt"></i> Delete
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php elseif (!isset($fetch_error)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 2rem;">No super administrator records found.</td>
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