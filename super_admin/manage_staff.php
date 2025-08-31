<?php
// session_start() MUST be the very first thing in your file.
session_start();

// Define variables for THIS page.
$pageTitle = "Manage Staff";
$currentPage = "staff"; // Used by the sidebar to highlight the active link.

// Include the database configuration.
include '../config.php';

// First, let's update any staff members without staff_id to assign them one
$updateStaffIdQuery = "
    UPDATE staff 
    SET staff_id = CONCAT('WTC-00', LPAD(id, 2, '0')) 
    WHERE staff_id IS NULL OR staff_id = ''";
$conn->query($updateStaffIdQuery);

// Fetch all staff records with proper NULL handling
$staffQuery = "
    SELECT 
        staff_id, 
        id as user_db_id, 
        id_number, 
        username, 
        department, 
        email, 
        position, 
        phone_number
    FROM staff 
    ORDER BY id DESC";
$staffResult = $conn->query($staffQuery);
if (!$staffResult) {
    $staff_fetch_error = "Error fetching staff records: " . $conn->error;
}

// --- Now, include the header. ---
include 'sa_header.php';
?>

<style>
/* Enhanced styling for the manage staff page */
.content-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding: 0 0.5rem;
}

.content-header .title {
    font-size: 2rem;
    font-weight: 700;
    color: #2c3e50;
    margin: 0;
}

.card {
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0, 70, 150, 0.08);
    border: none;
    overflow: hidden;
}

.card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1.5rem 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.card-title {
    font-size: 1.5rem;
    font-weight: 600;
    margin: 0;
}

.search-container {
    position: relative;
    width: 300px;
}

.search-icon {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #999;
    z-index: 1;
}

.search-input {
    width: 100%;
    padding: 12px 15px 12px 45px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-radius: 25px;
    background: rgba(255, 255, 255, 0.9);
    font-size: 14px;
    transition: all 0.3s ease;
}

.search-input:focus {
    outline: none;
    border-color: rgba(255, 255, 255, 0.8);
    background: rgba(255, 255, 255, 1);
    box-shadow: 0 0 20px rgba(255, 255, 255, 0.3);
}

.fees-table-container {
    overflow-x: auto;
    padding: 0;
}

.staff-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
}

.staff-table thead th {
    background: #f8f9fa;
    color: #495057;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 12px;
    letter-spacing: 0.5px;
    padding: 20px;
    text-align: left;
    border-bottom: 2px solid #e9ecef;
}

.staff-table tbody td {
    padding: 18px 20px;
    border-bottom: 1px solid #f1f3f4;
    vertical-align: middle;
}

.staff-table tbody tr {
    transition: background-color 0.2s ease;
}

.staff-table tbody tr:hover {
    background-color: #f8faff;
}

.badge {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    color: white;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    display: inline-block;
    min-width: 80px;
    text-align: center;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    text-decoration: none;
    font-weight: 600;
    font-size: 12px;
    transition: all 0.2s ease;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

.btn-sm {
    padding: 6px 12px;
    font-size: 11px;
}

.btn-success {
    background: linear-gradient(135deg, #56ab2f 0%, #a8e6cf 100%);
    color: white;
}

.notification {
    padding: 15px 20px;
    margin-bottom: 20px;
    border-radius: 8px;
    font-weight: 500;
}

.notification.error {
    background-color: #fff2f0;
    color: #ff4d4f;
    border-left: 4px solid #ff4d4f;
}

.notification.success {
    background-color: #f6ffed;
    color: #52c41a;
    border-left: 4px solid #52c41a;
}

.empty-state {
    text-align: center;
    padding: 3rem;
    color: #6c757d;
    font-size: 1.1em;
}

.empty-state i {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.department-tag {
    background: #e3f2fd;
    color: #1976d2;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 500;
}

.position-tag {
    background: #f3e5f5;
    color: #7b1fa2;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 500;
}

.add-staff-btn {
    background: linear-gradient(135deg, #4caf50 0%, #66bb6a 100%);
    color: white;
    padding: 12px 24px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s ease;
    box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
}

.add-staff-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(76, 175, 80, 0.4);
}

.actions-cell {
    display: flex;
    gap: 8px;
    align-items: center;
}

.pagination {
    padding: 20px;
    text-align: right;
}

.pagination a {
    margin: 0 5px;
    padding: 8px 15px;
    text-decoration: none;
    border-radius: 8px;
    transition: all 0.2s ease;
    color: #57606f;
    background-color: #fff;
    border: 1px solid #dcdfe6;
}

.pagination a:hover {
    background-color: #409eff;
    color: #fff;
    border-color: #409eff;
}

.pagination .current-page {
    background: linear-gradient(45deg, #409eff, #3a8ee6);
    color: white;
    border: none;
    box-shadow: 0 4px 10px rgba(64, 158, 255, 0.3);
}
</style>

<div class="content-header">
    <h1 class="title"><?= htmlspecialchars($pageTitle); ?></h1>
    <a href="add_staff.php" class="add-staff-btn">
        <i class="fas fa-plus"></i> Add New Staff
    </a>
</div>

<?php if (isset($staff_fetch_error)): ?>
    <div class="notification error">
        <strong>Error!</strong> <?= htmlspecialchars($staff_fetch_error); ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Staff Directory</h2>
        <div class="search-container">
            <i class="fas fa-search search-icon"></i>
            <input type="text" id="staffSearchInput" class="search-input" placeholder="Search staff...">
        </div>
    </div>

    <div class="fees-table-container">
        <table class="staff-table">
            <thead>
                <tr>
                    <th>Staff ID</th>
                    <th>Username</th>
                    <th>ID Number</th>
                    <th>Department</th>
                    <th>Email</th>
                    <th>Position</th>
                    <th>Phone</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="staffTableBody">
                <?php if ($staffResult && $staffResult->num_rows > 0): ?>
                    <?php while ($staff = $staffResult->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <span class="badge">
                                    <?= htmlspecialchars($staff['staff_id'] ?? 'WTC-00' . str_pad($staff['user_db_id'], 2, '0', STR_PAD_LEFT)); ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($staff['username'] ?? 'N/A'); ?></td>
                            <td><?= htmlspecialchars($staff['staff_id'] ?? 'WTC-00' . str_pad($staff['user_db_id'], 2, '0', STR_PAD_LEFT)); ?></td>
                            <td>
                                <?php if (!empty($staff['department']) && $staff['department'] !== 'Not Assigned'): ?>
                                    <span class="department-tag"><?= htmlspecialchars($staff['department']); ?></span>
                                <?php else: ?>
                                    <span style="color: #ffa726; font-style: italic;">Not Assigned</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($staff['email'] ?? 'N/A'); ?></td>
                            <td>
                                <?php if (!empty($staff['position']) && $staff['position'] !== 'Staff Member'): ?>
                                    <span class="position-tag"><?= htmlspecialchars($staff['position']); ?></span>
                                <?php else: ?>
                                    <span style="color: #757575;">Staff Member</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($staff['phone_number'] ?? 'N/A'); ?></td>
                            <td class="actions-cell">
                                <a href="edit_staff.php?staff_id=<?= urlencode($staff['staff_id']); ?>" class="btn btn-primary btn-sm" title="Edit Staff">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                
                                <?php if (empty($staff['staff_id'])): ?>
                                    <a href="assign_staff_id.php?id=<?= urlencode($staff['user_db_id']); ?>" class="btn btn-success btn-sm" title="Assign Staff ID">
                                        <i class="fas fa-id-card"></i> Assign ID
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <?php if (!isset($staff_fetch_error)): ?>
                    <tr>
                        <td colspan="8" class="empty-state">
                            <i class="fas fa-users"></i><br>
                            No staff records found.
                        </td>
                    </tr>
                    <?php endif; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="pagination">
        <a href="#" class="current-page">1</a>
        <a href="#">2</a>
    </div>
</div>

<?php
// Finally, include the footer.
include 'sa_footer.php';
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Staff Table Search functionality
    const searchInput = document.getElementById('staffSearchInput');
    const tableBody = document.getElementById('staffTableBody');
    const tableRows = tableBody.getElementsByTagName('tr');

    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const searchTerm = searchInput.value.toLowerCase().trim();

            for (let i = 0; i < tableRows.length; i++) {
                // Skip the "no records" row
                if (tableRows[i].children.length === 1) {
                    continue;
                }

                const rowText = tableRows[i].textContent.toLowerCase();
                if (searchTerm === '' || rowText.includes(searchTerm)) {
                    tableRows[i].style.display = '';
                } else {
                    tableRows[i].style.display = 'none';
                }
            }
        });
    }

    // Show success message if staff IDs were updated
    <?php if ($conn->affected_rows > 0): ?>
        const notification = document.createElement('div');
        notification.className = 'notification success';
        notification.innerHTML = '<strong>Success!</strong> Staff IDs have been automatically assigned to staff members who didn\'t have them.';
        document.querySelector('.content-header').insertAdjacentElement('afterend', notification);
        
        // Auto-hide the notification after 5 seconds
        setTimeout(() => {
            notification.style.display = 'none';
        }, 5000);
    <?php endif; ?>
});
</script>