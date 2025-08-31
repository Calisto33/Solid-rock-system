<?php
session_start();
include '../config.php';

// Check if the user is logged in and has permission
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'parent')) {
    header("Location: ../login.php");
    exit();
}

// --- START OF MODIFIED LOGIC ---

$pageTitle = "Our Expert Teaching Staff";
$pageSubtitle = "Meet the dedicated educators who inspire excellence every day";

// Check the user's role to determine which teachers to display
if ($_SESSION['role'] == 'parent') {
    // If the user is a parent, fetch only the teachers assigned to their child.
    $user_id = $_SESSION['user_id'];

    // First, find the student associated with this parent
    $parentQuery = "SELECT s.student_id, s.class FROM parents p JOIN students s ON p.student_id = s.student_id WHERE p.user_id = ?";
    $stmt_parent = $conn->prepare($parentQuery);
    $stmt_parent->bind_param("i", $user_id);
    $stmt_parent->execute();
    $parentResult = $stmt_parent->get_result();
    $parentData = $parentResult->fetch_assoc();
    $stmt_parent->close();

    if ($parentData) {
        $student_id = $parentData['student_id'];
        $class = $parentData['class'];
        
        $pageTitle = "Your Child's Teachers";
        $pageSubtitle = "Meet the educators dedicated to your child's success";

        // Now, fetch the profiles of only the teachers assigned to this student's subjects and class
        $query = "
            SELECT DISTINCT st.staff_id, sp.department, sp.position, sp.social_description, sp.profile_picture, st.username AS staff_name
            FROM student_subject ss
            JOIN table_subject ts ON ss.subject_id = ts.subject_id
            JOIN staff_subject sts ON ts.subject_id = sts.subject_id AND sts.class = ?
            JOIN staff st ON sts.staff_id = st.staff_id
            JOIN staff_profile sp ON st.staff_id = sp.staff_id
            WHERE ss.student_id = ?
            ORDER BY sp.department ASC";
        
        $stmt_teachers = $conn->prepare($query);
        $stmt_teachers->bind_param("si", $class, $student_id);
        $stmt_teachers->execute();
        $result = $stmt_teachers->get_result();

    } else {
        // Handle case where parent is not linked to a student
        $result = null; 
    }

} else {
    // If the user is an admin, fetch all teachers' profiles
    $query = "
        SELECT sp.staff_id, sp.department, sp.position, sp.social_description, sp.profile_picture, st.username AS staff_name
        FROM staff_profile sp
        JOIN staff st ON sp.staff_id = st.staff_id
        ORDER BY sp.department ASC";
    $result = $conn->query($query);
}

if (!$result && $_SESSION['role'] != 'parent') { // Avoid error for unlinked parent
    die("Error fetching teachers' profiles: " . $conn->error);
}

// --- END OF MODIFIED LOGIC ---


// --- Start of Templating ---
require 'header.php';
require 'sidebar.php';
?>

<main class="main-content">
    <div class="page-header-container">
        <h1 class="page-title"><?= $pageTitle ?></h1>
        <p class="page-subtitle"><?= $pageSubtitle ?></p>
    </div>

    <div class="search-filter-box">
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" placeholder="Search teachers by name..." id="teacherSearch">
        </div>
        <div class="filter-options">
            <select class="filter-select" id="departmentFilter">
                <option value="">All Departments</option>
                <option value="Mathematics">Mathematics</option>
                <option value="Science">Science</option>
                <option value="English">English</option>
                <option value="History">History</option>
                <option value="Arts">Arts</option>
            </select>
        </div>
    </div>

    <div class="profiles-grid" id="profilesGrid">
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="profile-card" data-department="<?= htmlspecialchars($row['department']) ?>" data-name="<?= strtolower(htmlspecialchars($row['staff_name'])) ?>">
                    <div class="profile-banner"></div>
                    
                    <?php
                    $profilePicturePath = "../uploads/" . htmlspecialchars($row['profile_picture']);
                    if (!empty($row['profile_picture']) && file_exists($profilePicturePath)) {
                        echo '<img src="' . $profilePicturePath . '" alt="Profile Picture" class="profile-image">';
                    } else {
                        // Provide a default image or initials
                        echo '<img src="../uploads/default_profile.png" alt="Default Profile" class="profile-image">';
                    }
                    ?>
                    
                    <div class="profile-content">
                        <h3 class="profile-name"><?= htmlspecialchars($row['staff_name']) ?></h3>
                        
                        <div class="profile-info">
                            <span class="profile-tag">
                                <i class="fas fa-briefcase"></i>
                                <?= htmlspecialchars($row['position']) ?>
                            </span>
                        </div>
                        
                        <p class="profile-bio"><?= htmlspecialchars($row['social_description']) ?></p>
                        
                        <div class="profile-actions">
                            <a href="mailto:<?= strtolower(str_replace(' ', '.', $row['staff_name'])) ?>@wisetech.edu" class="action-btn" title="Email <?= htmlspecialchars($row['staff_name']) ?>">
                                <i class="fas fa-envelope"></i>
                            </a>
                            <a href="#" class="action-btn" title="View Profile">
                                <i class="fas fa-user"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="text-align: center; width: 100%;">No teacher profiles found.</p>
        <?php endif; ?>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const teacherSearch = document.getElementById('teacherSearch');
    const departmentFilter = document.getElementById('departmentFilter');
    const profilesGrid = document.getElementById('profilesGrid');
    const profileCards = profilesGrid.querySelectorAll('.profile-card');

    function filterProfiles() {
        const searchTerm = teacherSearch.value.toLowerCase();
        const departmentValue = departmentFilter.value;

        profileCards.forEach(card => {
            const profileName = card.dataset.name;
            const profileDepartment = card.dataset.department;

            const matchesSearch = profileName.includes(searchTerm);
            const matchesDepartment = departmentValue === '' || profileDepartment === departmentValue;

            if (matchesSearch && matchesDepartment) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    }

    if (teacherSearch) teacherSearch.addEventListener('input', filterProfiles);
    if (departmentFilter) departmentFilter.addEventListener('change', filterProfiles);
});
</script>

<?php
// Close the database connection at the very end
$conn->close();

// Include the main footer
require 'footer.php';
?>