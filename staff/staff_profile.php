<?php
// Set the page title for the header
$pageTitle = "My Profile";

// Include the new header. It handles security, session, db connection, and the sidebar.
include 'header.php';

// --- PHP LOGIC SPECIFIC TO THIS PAGE ---

// Fetch the specific staff_id from the staff table based on the user_id from the session
$staffIdQuery = "SELECT staff_id FROM staff WHERE id = ?";
$stmt_staff = $conn->prepare($staffIdQuery);
if(!$stmt_staff) die("Prepare failed: ". $conn->error);
$stmt_staff->bind_param("i", $staff_id); // $staff_id comes from header.php
$stmt_staff->execute();
$result_staff = $stmt_staff->get_result();
$staffData = $result_staff->fetch_assoc();
$stmt_staff->close();

if (!$staffData) {
    die("Error: This user is not registered in the 'staff' table.");
}
$specific_staff_id = $staffData['staff_id'];


// Handle profile update POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $position = $_POST['position'];
    $social_description = $_POST['social_description'];
    
    // Fetch the current picture path first, in case no new file is uploaded
    $currentPicStmt = $conn->prepare("SELECT profile_picture FROM staff_profile WHERE staff_id = ?");
    $currentPicStmt->bind_param("i", $specific_staff_id);
    $currentPicStmt->execute();
    $currentProfile = $currentPicStmt->get_result()->fetch_assoc();
    $profile_picture = $currentProfile['profile_picture'] ?? 'default.png'; // Default if no profile exists
    $currentPicStmt->close();

    // Handle profile picture upload
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == UPLOAD_ERR_OK) {
        $targetDir = "../uploads/profiles/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }
        $fileName = time() . '_' . basename($_FILES['profile_picture']['name']);
        $targetFilePath = $targetDir . $fileName;

        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $targetFilePath)) {
            $profile_picture = $fileName; // Only update the picture name if upload is successful
        }
    }

    // Update or Insert profile in the database (UPSERT logic)
    $updateQuery = "
        INSERT INTO staff_profile (staff_id, position, social_description, profile_picture) 
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
        position = VALUES(position), 
        social_description = VALUES(social_description), 
        profile_picture = VALUES(profile_picture)
    ";
    $stmt_update = $conn->prepare($updateQuery);
    $stmt_update->bind_param("isss", $specific_staff_id, $position, $social_description, $profile_picture);

    if ($stmt_update->execute()) {
        header("Location: staff_profile.php?status=success");
        exit();
    } else {
        $errorMessage = "Error updating profile: " . $stmt_update->error;
    }
    $stmt_update->close();
}

// Fetch the staff member's profile for display
$query = "SELECT s.staff_id, s.department, p.position, p.social_description, p.profile_picture 
          FROM staff s 
          LEFT JOIN staff_profile p ON s.staff_id = p.staff_id 
          WHERE s.id = ?";
$stmt_profile = $conn->prepare($query);
$stmt_profile->bind_param("i", $staff_id);
$stmt_profile->execute();
$result = $stmt_profile->get_result();
$profile = $result->fetch_assoc();
$stmt_profile->close();

if (!$profile) {
    die("Error: Profile could not be fetched.");
}
?>

<style>
    .page-title { text-align: center; font-size: 2rem; position: relative; padding-bottom: 0.75rem; margin-bottom: 2rem; }
    .page-title::after { content: ''; position: absolute; bottom: 0; left: 50%; transform: translateX(-50%); width: 80px; height: 3px; background: linear-gradient(to right, var(--primary-color), var(--accent-color)); border-radius: 3px; }
    .message { padding: 1rem; border-radius: 12px; margin-bottom: 1.5rem; font-weight: 500; }
    .success { background-color: rgba(16, 185, 129, 0.15); color: #10b981; border-left: 4px solid #10b981; }
    .error { background-color: rgba(239, 68, 68, 0.15); color: #ef4444; border-left: 4px solid #ef4444; }
    .form-grid { display: grid; grid-template-columns: 1fr; gap: 2rem; }
    .profile-section { display: flex; flex-direction: column; align-items: center; gap: 1.5rem; }
    .form-section { display: flex; flex-direction: column; gap: 1.5rem; }
    .form-group { display: flex; flex-direction: column; gap: 0.5rem; }
    label { font-weight: 500; color: var(--text-secondary); }
    input, textarea { padding: 0.75rem 1rem; border: 1px solid #94a3b8; border-radius: 12px; font-size: 1rem; }
    input:focus, textarea:focus { outline: none; border-color: var(--primary-color); box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1); }
    input:disabled { background-color: #f1f5f9; cursor: not-allowed; }
    textarea { min-height: 150px; resize: vertical; }
    .file-input-wrapper { position: relative; overflow: hidden; display: inline-block; width: 100%; }
    .file-input-label { display: flex; align-items: center; justify-content: center; gap: 0.5rem; cursor: pointer; padding: 0.75rem 1.5rem; background-color: #f1f5f9; border: 1px dashed var(--primary-color); border-radius: 12px; color: var(--primary-color); }
    .file-input-wrapper input[type="file"] { position: absolute; left: 0; top: 0; opacity: 0; width: 100%; height: 100%; cursor: pointer; }
    .profile-image { position: relative; width: 160px; height: 160px; border-radius: 50%; overflow: hidden; border: 4px solid white; box-shadow: var(--shadow); }
    .profile-image img { width: 100%; height: 100%; object-fit: cover; }
    .profile-image .edit-overlay { position: absolute; bottom: 0; left: 0; right: 0; background-color: rgba(0,0,0,0.6); color: white; text-align: center; padding: 0.5rem 0; font-size: 0.85rem; opacity: 0; transition: all 0.3s ease; }
    .profile-image:hover .edit-overlay { opacity: 1; }
    .btn-primary { background: linear-gradient(135deg, var(--primary-color), var(--primary-dark)); }
    .button-group { display: flex; gap: 1rem; justify-content: center; margin-top: 1.5rem; }
    @media screen and (min-width: 768px) { .form-grid { grid-template-columns: 1fr 2fr; align-items: start; } .button-group { justify-content: flex-end; } }
</style>

<h1 class="page-title">My Profile</h1>

<?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
    <div class="message success"><i class="fas fa-check-circle"></i> Profile updated successfully!</div>
<?php elseif (isset($errorMessage)): ?>
    <div class="message error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errorMessage) ?></div>
<?php endif; ?>

<div class="card">
    <form method="POST" enctype="multipart/form-data">
        <div class="form-grid">
            <div class="profile-section">
                <div class="profile-image">
                    <?php if (!empty($profile['profile_picture'])): ?>
                        <img src="../uploads/profiles/<?= htmlspecialchars($profile['profile_picture']) ?>" alt="Profile Picture">
                    <?php else: ?>
                        <img src="../images/default-avatar.png" alt="Default Profile Picture" />
                    <?php endif; ?>
                    <div class="edit-overlay">Click to change</div>
                </div>
                <div class="form-group" style="width: 100%;">
                    <div class="file-input-wrapper">
                        <label for="profile_picture" class="file-input-label">
                            <i class="fas fa-camera"></i> Update Picture
                        </label>
                        <input type="file" id="profile_picture" name="profile_picture" accept="image/*">
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <div class="form-group">
                    <label for="department"><i class="fas fa-building"></i> Department</label>
                    <input type="text" id="department" value="<?= htmlspecialchars($profile['department'] ?? 'Not Set') ?>" disabled>
                </div>
                <div class="form-group">
                    <label for="position"><i class="fas fa-id-badge"></i> Position</label>
                    <input type="text" id="position" name="position" value="<?= htmlspecialchars($profile['position'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label for="social_description"><i class="fas fa-user-circle"></i> About Me</label>
                    <textarea id="social_description" name="social_description" placeholder="Share a bit about yourself..." required><?= htmlspecialchars($profile['social_description'] ?? '') ?></textarea>
                </div>
            </div>
        </div>
        <div class="button-group">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Profile</button>
        </div>
    </form>
</div>
<?php
// Include the new footer to close the page layout
$conn->close();
include 'footer.php';
?>