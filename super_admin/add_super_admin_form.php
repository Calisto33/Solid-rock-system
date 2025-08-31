<?php
session_start();
include '../config.php';

// Check if the user is logged in as super admin
if (!isset($_SESSION['super_admin_id'])) {
    header("Location: super_admin_login.php");
    exit();
}

$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Basic validation
    if (empty($username) || empty($email) || empty($password)) {
        $error_message = "All fields are required.";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $insertQuery = "INSERT INTO super_admins (username, email, password, status) VALUES (?, ?, ?, 'active')";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param("sss", $username, $email, $hashed_password);

        if ($stmt->execute()) {
            // Redirect to the management page on success
            header("Location: manage_super_admins.php");
            exit();
        } else {
            $error_message = "Error adding super admin: " . $stmt->error;
        }
        $stmt->close();
    }
}

// --- Page Setup ---
$pageTitle = "Add New Super Admin";
$currentPage = "super_admins"; // Keep the 'super_admins' link active in the sidebar

// --- Include the main header and sidebar ---
include 'sa_header.php';
?>

<div class="content-header">
    <h1 class="title"><?= htmlspecialchars($pageTitle); ?></h1>
    <a href="manage_super_admins.php" class="btn btn-outline-primary">
        <i class="fas fa-arrow-left"></i> Back to List
    </a>
</div>

<?php if (!empty($error_message)): ?>
    <div class="notification error">
        <?= htmlspecialchars($error_message); ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">New Super Admin Details</h2>
    </div>
    <div class="card-body" style="padding: 2rem;">
        <form method="POST" action="add_super_admin_form.php">
            <div class="form-group">
                <label for="username" class="form-label">Username</label>
                <input type="text" id="username" name="username" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>

            <div class="form-actions">
                <a href="manage_super_admins.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Create Account
                </button>
            </div>
        </form>
    </div>
</div>
<?php
// Finally, include the footer.
include 'sa_footer.php';
?>