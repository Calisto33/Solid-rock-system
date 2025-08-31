<?php
// Set the page title for the header
$pageTitle = "Add New Resource";

// Include the new header. It handles security, session, db connection, and the sidebar.
include 'header.php';

// --- PHP LOGIC SPECIFIC TO THIS PAGE ---

// Fetch the specific staff_id from the staff table based on the user_id from the session
$staffIdQuery = "SELECT staff_id FROM staff WHERE id = ?";
$stmt_staff = $conn->prepare($staffIdQuery);
if (!$stmt_staff) die("Prepare failed: ". $conn->error);
$stmt_staff->bind_param("i", $staff_id); // $staff_id comes from header.php
$stmt_staff->execute();
$result_staff = $stmt_staff->get_result();
$staffData = $result_staff->fetch_assoc();
$stmt_staff->close();

if (!$staffData) {
    die("Error: This user is not registered in the 'staff' table.");
}
$specific_staff_id = $staffData['staff_id'];

// Initialize the message variable
$message = "";
$message_type = "success";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $department = $_POST['department'];
    $description = trim($_POST['description']);
    $resource_link = trim($_POST['resource_link']);

    // Insert the resource into the student_resources table
    $insertQuery = "INSERT INTO student_resources (staff_id, department, description, resource_link, upload_date) VALUES (?, ?, ?, ?, NOW())";
    $stmt_insert = $conn->prepare($insertQuery);
    $stmt_insert->bind_param("isss", $specific_staff_id, $department, $description, $resource_link);

    if ($stmt_insert->execute()) {
        $message = "Resource added successfully.";
    } else {
        $message = "Error adding resource: " . $stmt_insert->error;
        $message_type = "error";
    }
    $stmt_insert->close();
}
?>

<style>
    .card {
        background-color: var(--card-bg);
        border-radius: var(--border-radius);
        box-shadow: var(--shadow-md);
        padding: 2rem;
        transition: all 0.3s ease;
    }
    .page-title {
        margin-bottom: 2rem;
        font-weight: 700;
        color: var(--primary-color);
        font-size: 1.75rem;
    }
    form {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }
    .form-group {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    label {
        font-weight: 500;
        color: var(--text-color);
    }
    select, input[type="url"], textarea {
        padding: 0.9rem 1rem;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        font-size: 1rem;
        width: 100%;
        background-color: #fff;
    }
    textarea {
        min-height: 120px;
        resize: vertical;
    }
    .message {
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 1.5rem;
    }
    .success-message {
        background-color: #dcfce7;
        color: #16a34a;
        border-left: 4px solid #16a34a;
    }
    .error-message {
        background-color: #fee2e2;
        color: #ef4444;
        border-left: 4px solid #ef4444;
    }
    .btn-primary {
        background-color: var(--primary-color);
        color: var(--white);
        border: none;
    }
</style>

<h1 class="page-title">Add New Resource</h1>

<div class="card">
    <?php if (!empty($message)): ?>
        <div class="message <?= $message_type === 'success' ? 'success-message' : 'error-message' ?>">
            <i class="fas <?= $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
            <span><?= htmlspecialchars($message) ?></span>
        </div>
    <?php endif; ?>

    <form method="POST" action="add_resource.php">
        <div class="form-group">
            <label for="department">Department</label>
            <select name="department" id="department" required>
                <option value="">Select department</option>
                <option value="Arts">Arts</option>
                <option value="Sciences">Sciences</option>
                <option value="Commercial">Commercial</option>
            </select>
        </div>

        <div class="form-group">
            <label for="description">Resource Description</label>
            <textarea name="description" id="description" placeholder="Enter a detailed description of this resource..." required></textarea>
        </div>

        <div class="form-group">
            <label for="resource_link">Resource Link</label>
            <input type="url" name="resource_link" id="resource_link" placeholder="https://example.com/resource" required>
        </div>

        <button type="submit" class="btn btn-primary">
            <i class="fas fa-plus-circle"></i> Add Resource
        </button>
    </form>
</div>

<?php
// Include the new footer to close the page layout
$conn->close();
include 'footer.php';
?>