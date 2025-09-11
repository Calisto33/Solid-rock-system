<?php
$pageTitle = "Learning Resources"; // This sets the title in the header
include 'header.php';           // This includes the sidebar and unified styles
include '../config.php';        // Your database connection

// Handle search and filter input
$search = $_GET['search'] ?? '';
$department_filter = $_GET['department'] ?? '';
$date_filter = $_GET['date'] ?? '';

// Build the query with search and filter conditions
$query = "SELECT department, description, resource_link, upload_date, download_count FROM student_resources WHERE 1=1";

$params = [];
$types = '';

if ($search) {
    $query .= " AND (description LIKE ? OR resource_link LIKE ?)";
    $searchTerm = "%" . $search . "%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= 'ss';
}
if ($department_filter) {
    $query .= " AND department = ?";
    $params[] = $department_filter;
    $types .= 's';
}
if ($date_filter) {
    $query .= " AND DATE(upload_date) = ?";
    $params[] = $date_filter;
    $types .= 's';
}

$query .= " ORDER BY upload_date DESC";
$stmt_resources = $conn->prepare($query);
if($types) {
    $stmt_resources->bind_param($types, ...$params);
}
$stmt_resources->execute();
$result = $stmt_resources->get_result();

// Handle download tracking
if (isset($_GET['download_link'])) {
    $link = $_GET['download_link'];
    $updateDownloadQuery = "UPDATE student_resources SET download_count = download_count + 1 WHERE resource_link = ?";
    $stmt_download = $conn->prepare($updateDownloadQuery);
    $stmt_download->bind_param("s", $link);
    $stmt_download->execute();
    $stmt_download->close();
    header("Location: " . $link); 
    exit();
}
?>

<!-- Page-specific styles for the resources page -->
<style>
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        flex-wrap: wrap;
        gap: 1rem;
    }
    .page-title {
        font-weight: 700;
        color: var(--primary-text);
        font-size: 1.8rem;
    }
    .view-toggle-buttons {
        display: flex;
        gap: 0.5rem;
        background-color: var(--bg-color);
        padding: 0.5rem;
        border-radius: var(--rounded-lg);
    }
    .view-btn {
        background: transparent;
        border: none;
        padding: 0.5rem 1rem;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        color: var(--secondary-text);
        transition: var(--transition);
    }
    .view-btn.active {
        background: var(--widget-bg);
        color: var(--accent-purple);
        box-shadow: var(--shadow);
    }
    
    .filter-card {
        padding: 1.5rem 2rem;
        margin-bottom: 2rem;
    }
    .search-form {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
        align-items: end;
    }
    .form-group label {
        font-weight: 600;
        font-size: 0.9rem;
        margin-bottom: 0.5rem;
        display: block;
    }
    .form-group input, .form-group select {
        width: 100%;
        padding: 0.8rem 1rem;
        border: 1px solid var(--border-color);
        border-radius: var(--rounded-lg);
        background-color: var(--bg-color);
        font-size: 1rem;
    }
    
    /* Card View Styles */
    .resources-grid-view {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 1.5rem;
    }
    .resource-card {
        display: flex;
        flex-direction: column;
        background-color: var(--widget-bg);
        border-radius: var(--rounded-xl);
        box-shadow: var(--shadow);
        transition: var(--transition);
        overflow: hidden;
    }
    .resource-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-lg);
    }
    .resource-card-header {
        padding: 1rem 1.5rem;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    .resource-icon {
        font-size: 1.5rem;
        color: var(--accent-purple);
    }
    .resource-department {
        font-weight: 600;
        font-size: 1.1rem;
    }
    .resource-card-body {
        padding: 1.5rem;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }
    .resource-description { flex-grow: 1; margin-bottom: 1.5rem; }
    .resource-meta {
        display: flex;
        justify-content: space-between;
        font-size: 0.85rem;
        color: var(--secondary-text);
        margin-bottom: 1.5rem;
    }
    
    /* Table View Styles */
    .resources-table-view { display: none; }
    .table-container { overflow-x: auto; }
</style>

<div class="page-header">
    <h1 class="page-title">Learning Resources</h1>
    <div class="view-toggle-buttons">
        <button class="view-btn active" id="card-view-btn"><i class="fas fa-th-large"></i> Card View</button>
        <button class="view-btn" id="table-view-btn"><i class="fas fa-list"></i> Table View</button>
    </div>
</div>

<div class="card filter-card">
    <form method="GET" action="" class="search-form">
        <div class="form-group">
            <label for="search">Search</label>
            <input type="text" id="search" name="search" placeholder="Search by keyword..." value="<?= htmlspecialchars($search) ?>">
        </div>
        <div class="form-group">
            <label for="department">Department</label>
            <select id="department" name="department">
                <option value="">All Departments</option>
                <option value="Arts" <?= $department_filter === 'Arts' ? 'selected' : '' ?>>Arts</option>
                <option value="Sciences" <?= $department_filter === 'Sciences' ? 'selected' : '' ?>>Sciences</option>
                <option value="Commercial" <?= $department_filter === 'Commercial' ? 'selected' : '' ?>>Commercial</option>
            </select>
        </div>
        <div class="form-group">
            <label for="date">Date</label>
            <input type="date" id="date" name="date" value="<?= htmlspecialchars($date_filter) ?>">
        </div>
        <div class="form-group">
            <button type="submit" class="btn btn-primary" style="width: 100%;"><i class="fas fa-filter"></i> Apply</button>
        </div>
    </form>
</div>

<!-- Card View Container -->
<div class="resources-grid-view" id="card-view">
    <?php if ($result->num_rows > 0): ?>
        <?php $result->data_seek(0); ?>
        <?php while ($resource = $result->fetch_assoc()): ?>
            <?php
            // Determine the icon based on department
            $icon = 'fas fa-book'; // Default icon
            if ($resource['department'] === 'Arts') $icon = 'fas fa-palette';
            if ($resource['department'] === 'Sciences') $icon = 'fas fa-flask';
            if ($resource['department'] === 'Commercial') $icon = 'fas fa-chart-line';
            ?>
            <div class="resource-card">
                <div class="resource-card-header">
                    <i class="resource-icon <?= $icon ?>"></i>
                    <span class="resource-department"><?= htmlspecialchars($resource['department']) ?></span>
                </div>
                <div class="resource-card-body">
                    <p class="resource-description"><?= htmlspecialchars($resource['description']) ?></p>
                    <div class="resource-meta">
                        <span><i class="fas fa-calendar-alt"></i> <?= date('d M, Y', strtotime($resource['upload_date'])) ?></span>
                        <span><i class="fas fa-download"></i> <?= htmlspecialchars($resource['download_count']) ?> Downloads</span>
                    </div>
                    <a href="?download_link=<?= urlencode($resource['resource_link']) ?>" class="btn btn-primary" style="width:100%;" target="_blank">Download</a>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p style="text-align:center; color: var(--secondary-text); padding: 2rem;">No resources found matching your criteria.</p>
    <?php endif; ?>
</div>

<!-- Table View Container (Initially Hidden) -->
<div class="resources-table-view card" id="table-view" style="padding:0;">
    <div class="table-container">
        <table style="width:100%; border-collapse: collapse;">
            <thead>
                <tr style="background-color: #f9fafb;">
                    <th style="padding:1rem; text-align:left;">Department</th>
                    <th style="padding:1rem; text-align:left;">Description</th>
                    <th style="padding:1rem; text-align:left;">Date</th>
                    <th style="padding:1rem; text-align:center;">Downloads</th>
                    <th style="padding:1rem; text-align:center;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php $result->data_seek(0); ?>
                    <?php while ($resource = $result->fetch_assoc()): ?>
                        <tr style="border-top: 1px solid var(--border-color);">
                            <td style="padding:1rem;"><?= htmlspecialchars($resource['department']) ?></td>
                            <td style="padding:1rem;"><?= htmlspecialchars($resource['description']) ?></td>
                            <td style="padding:1rem;"><?= date('d M, Y', strtotime($resource['upload_date'])) ?></td>
                            <td style="padding:1rem; text-align:center;"><?= htmlspecialchars($resource['download_count']) ?></td>
                            <td style="padding:1rem; text-align:center;">
                                <a href="?download_link=<?= urlencode($resource['resource_link']) ?>" class="btn btn-primary" target="_blank">Download</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="text-align:center; padding: 2rem;">No resources found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const cardViewBtn = document.getElementById('card-view-btn');
        const tableViewBtn = document.getElementById('table-view-btn');
        const cardView = document.getElementById('card-view');
        const tableView = document.getElementById('table-view');

        cardViewBtn.addEventListener('click', function() {
            cardView.style.display = 'grid';
            tableView.style.display = 'none';
            cardViewBtn.classList.add('active');
            tableViewBtn.classList.remove('active');
        });

        tableViewBtn.addEventListener('click', function() {
            cardView.style.display = 'none';
            tableView.style.display = 'block';
            tableViewBtn.classList.add('active');
            cardViewBtn.classList.remove('active');
        });
    });
</script>

<?php 
$stmt_resources->close();
include 'footer.php'; 
?>
