<?php
session_start();
include '../config.php';

// Helper function to safely handle null values in htmlspecialchars
function safe_html($value, $default = '') {
    return htmlspecialchars($value ?? $default);
}

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

// Handle adding new student-parent relationship
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_relationship'])) {
    $student_id = $_POST['student_id'];
    $parent_id = $_POST['parent_id'];
    $relationship_type = $_POST['relationship_type'];
    $is_primary = isset($_POST['is_primary_contact']) ? 1 : 0;
    $is_emergency = isset($_POST['is_emergency_contact']) ? 1 : 0;
    $can_pick_up = isset($_POST['can_pick_up']) ? 1 : 0;
    $notes = $_POST['notes'] ?? '';
    
    $insertQuery = "INSERT INTO student_parent_relationships 
                    (student_id, parent_id, relationship_type, is_primary_contact, is_emergency_contact, can_pick_up, notes, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insertQuery);
    $stmt->bind_param("sisiiisi", $student_id, $parent_id, $relationship_type, $is_primary, $is_emergency, $can_pick_up, $notes, $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Student-parent relationship added successfully!";
    } else {
        $_SESSION['error_message'] = "Error adding relationship: " . $conn->error;
    }
    
    header("Location: parents.php");
    exit();
}

// Handle removing relationship
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['remove_relationship'])) {
    $relationship_id = $_POST['relationship_id'];
    
    $deleteQuery = "DELETE FROM student_parent_relationships WHERE id = ?";
    $stmt = $conn->prepare($deleteQuery);
    $stmt->bind_param("i", $relationship_id);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Relationship removed successfully!";
    } else {
        $_SESSION['error_message'] = "Error removing relationship: " . $conn->error;
    }
    
    header("Location: parents.php");
    exit();
}

// Check if the relationship table exists
$tableCheck = $conn->query("SHOW TABLES LIKE 'student_parent_relationships'");
$relationshipTableExists = $tableCheck->num_rows > 0;

if ($relationshipTableExists) {
    // Fetch all parents with their relationships using direct joins
    $parentsQuery = "
        SELECT 
            p.parent_id, 
            p.user_id, 
            p.phone_number, 
            p.address, 
            p.created_at, 
            u.username, 
            u.email,
            u.first_name as parent_first_name,
            u.last_name as parent_last_name,
            GROUP_CONCAT(DISTINCT 
                CASE 
                    WHEN s.first_name IS NOT NULL AND s.first_name != '' AND s.last_name IS NOT NULL AND s.last_name != ''
                    THEN CONCAT(s.first_name, ' ', s.last_name)
                    WHEN s.first_name IS NOT NULL AND s.first_name != ''
                    THEN s.first_name
                    WHEN s.last_name IS NOT NULL AND s.last_name != ''
                    THEN s.last_name
                    WHEN s.username IS NOT NULL AND s.username != ''
                    THEN s.username
                    ELSE CONCAT('Student ID: ', s.student_id)
                END
                SEPARATOR ', '
            ) as student_names,
            GROUP_CONCAT(DISTINCT s.student_id SEPARATOR ', ') as student_numbers,
            GROUP_CONCAT(DISTINCT spr.relationship_type SEPARATOR ', ') as relationships,
            COUNT(DISTINCT spr.student_id) as student_count
        FROM parents p
        LEFT JOIN users u ON p.user_id = u.id
        LEFT JOIN student_parent_relationships spr ON p.parent_id = spr.parent_id
        LEFT JOIN students s ON spr.student_id = s.student_id
        GROUP BY p.parent_id
        ORDER BY p.created_at DESC";
        
    // Fetch all relationships for detailed view
    $relationshipsQuery = "
        SELECT 
            spr.id as relationship_id,
            spr.student_id,
            spr.parent_id,
            spr.relationship_type,
            spr.is_primary_contact,
            spr.is_emergency_contact,
            spr.can_pick_up,
            spr.notes,
            spr.created_at,
            s.student_id as student_number,
            s.first_name as student_first_name,
            s.last_name as student_last_name,
            s.username as student_username,
            s.class as student_class,
            p.phone_number as parent_phone,
            u.username as parent_username,
            u.email as parent_email,
            u.first_name as parent_first_name,
            u.last_name as parent_last_name
        FROM student_parent_relationships spr
        LEFT JOIN students s ON spr.student_id = s.student_id
        LEFT JOIN parents p ON spr.parent_id = p.parent_id
        LEFT JOIN users u ON p.user_id = u.id
        ORDER BY s.student_id, spr.relationship_type";
} else {
    // Fallback to original structure if relationship table doesn't exist
    $parentsQuery = "
        SELECT 
            p.parent_id, 
            p.user_id, 
            p.phone_number, 
            p.address, 
            p.created_at, 
            p.student_id,
            u.username, 
            u.email,
            u.first_name as parent_first_name,
            u.last_name as parent_last_name,
            s.first_name as student_first_name, 
            s.last_name as student_last_name,
            s.student_id as student_table_id
        FROM parents p
        LEFT JOIN users u ON p.user_id = u.id
        LEFT JOIN students s ON p.student_id = s.student_id
        ORDER BY p.created_at DESC";
    
    $relationshipsQuery = null; // No relationships table
}

$parentsResult = $conn->query($parentsQuery);

// Fetch students for the add relationship form
$studentsQuery = "SELECT student_id, first_name, last_name, username, class FROM students ORDER BY 
    CASE 
        WHEN first_name IS NOT NULL AND first_name != '' THEN first_name
        WHEN username IS NOT NULL AND username != '' THEN username
        ELSE student_id
    END";
$studentsResult = $conn->query($studentsQuery);

// Fetch relationships if table exists
$relationshipsResult = null;
if ($relationshipTableExists && $relationshipsQuery) {
    $relationshipsResult = $conn->query($relationshipsQuery);
}

// Check if queries were successful
if (!$parentsResult || !$studentsResult) {
    die("Error executing query: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parents Management | Wisetech College</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2563eb;
            --primary-dark: #1d4ed8;
            --secondary-color: #f3f4f6;
            --accent-color: #3b82f6;
            --text-color: #1f2937;
            --muted-text: #6b7280;
            --white: #ffffff;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-800: #1f2937;
            --success: #10b981;
            --error: #ef4444;
            --warning: #f59e0b;
            --border-radius: 12px;
            --box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --box-shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--gray-50);
            color: var(--text-color);
            min-height: 100vh;
            line-height: 1.6;
        }

        .header {
            background-color: var(--white);
            padding: 1.25rem;
            box-shadow: var(--box-shadow);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
        }

        .logo h2 {
            color: var(--primary-color);
            font-weight: 700;
            font-size: 1.5rem;
            margin-left: 0.5rem;
        }

        .logo i {
            font-size: 1.75rem;
            color: var(--primary-color);
        }

        .header-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.625rem 1.25rem;
            border-radius: var(--border-radius);
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
            box-shadow: var(--box-shadow);
            border: none;
            cursor: pointer;
            font-size: 0.95rem;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: var(--white);
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--box-shadow-lg);
        }

        .btn-success {
            background-color: var(--success);
            color: var(--white);
        }

        .btn-success:hover {
            background-color: #059669;
            transform: translateY(-2px);
            box-shadow: var(--box-shadow-lg);
        }

        .btn-warning {
            background-color: var(--warning);
            color: var(--white);
        }

        .btn-warning:hover {
            background-color: #d97706;
            transform: translateY(-2px);
            box-shadow: var(--box-shadow-lg);
        }

        .btn-error {
            background-color: var(--error);
            color: var(--white);
        }

        .btn-error:hover {
            background-color: #dc2626;
            transform: translateY(-2px);
            box-shadow: var(--box-shadow-lg);
        }

        .btn-sm {
            padding: 0.5rem 0.875rem;
            font-size: 0.875rem;
        }

        main {
            max-width: 1400px;
            width: 100%;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }

        .page-header {
            background-color: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 2rem;
            margin-bottom: 2rem;
            text-align: center;
        }

        .page-header h1 {
            color: var(--primary-color);
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .page-header p {
            color: var(--muted-text);
            font-size: 1.1rem;
        }

        .alert {
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1rem;
        }

        .alert-success {
            background-color: #d1fae5;
            color: #065f46;
            border: 1px solid var(--success);
        }

        .alert-error {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid var(--error);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background-color: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 1.5rem;
            text-align: center;
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--box-shadow-lg);
        }

        .stat-card i {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .stat-card h3 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .stat-card p {
            color: var(--muted-text);
            font-weight: 600;
        }

        .content-card {
            background-color: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .card-header {
            background-color: var(--primary-color);
            color: var(--white);
            padding: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header h2 {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0;
        }

        .table-container {
            overflow-x: auto;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th,
        .table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--gray-200);
        }

        .table th {
            background-color: var(--gray-100);
            font-weight: 600;
            color: var(--text-color);
            white-space: nowrap;
        }

        .table tr:hover {
            background-color: var(--gray-50);
        }

        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-father {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .badge-mother {
            background-color: #fce7f3;
            color: #be185d;
        }

        .badge-guardian {
            background-color: #d1fae5;
            color: #065f46;
        }

        .badge-other {
            background-color: #f3f4f6;
            color: #374151;
        }

        .form-container {
            background-color: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-color);
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--gray-300);
            border-radius: var(--border-radius);
            font-size: 1rem;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .checkbox-group {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .tab {
            padding: 0.75rem 1.5rem;
            background-color: var(--gray-200);
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: var(--transition);
        }

        .tab.active {
            background-color: var(--primary-color);
            color: var(--white);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        @media screen and (max-width: 768px) {
            .header-container {
                flex-direction: column;
                gap: 1rem;
            }

            .header-actions {
                flex-wrap: wrap;
                justify-content: center;
            }

            .page-header h1 {
                font-size: 2rem;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .table th,
            .table td {
                padding: 0.5rem;
                font-size: 0.875rem;
            }

            .tabs {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-container">
            <div class="logo">
                <i class="fas fa-graduation-cap"></i>
                <h2>Wisetech College Portal</h2>
            </div>
            <div class="header-actions">
                <a href="add_parent.php" class="btn btn-success">
                    <i class="fas fa-plus"></i> Add Parent
                </a>
                <a href="admin_home.php" class="btn btn-primary">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </div>
        </div>
    </header>

    <main>
        <div class="page-header">
            <h1><i class="fas fa-users"></i> Parents Management</h1>
            <p>Manage parent information and student relationships</p>
            <?php if (!$relationshipTableExists): ?>
                <div style="margin-top: 1rem; padding: 1rem; background-color: #fef3c7; color: #92400e; border-radius: 8px;">
                    <i class="fas fa-info-circle"></i> 
                    Advanced relationship features not available. Create the student_parent_relationships table to enable full functionality.
                </div>
            <?php endif; ?>
        </div>

        <!-- Alert Messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?= safe_html($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?= safe_html($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>

        <?php
        // Calculate statistics
        $totalParents = $parentsResult->num_rows;
        
        if ($relationshipTableExists && $relationshipsResult) {
            $totalRelationships = $relationshipsResult->num_rows;
            $relationshipsResult->data_seek(0);
            $uniqueStudents = [];
            $primaryContacts = 0;
            while ($row = $relationshipsResult->fetch_assoc()) {
                $uniqueStudents[$row['student_id']] = true;
                if ($row['is_primary_contact']) {
                    $primaryContacts++;
                }
            }
            $studentsWithParents = count($uniqueStudents);
            $relationshipsResult->data_seek(0);
        } else {
            $totalRelationships = 0;
            $studentsWithParents = 0;
            $primaryContacts = 0;
            
            // Count from original structure
            $parentsResult->data_seek(0);
            while ($row = $parentsResult->fetch_assoc()) {
                if (!empty($row['student_id'])) {
                    $studentsWithParents++;
                }
            }
            $parentsResult->data_seek(0);
        }
        ?>

        <div class="stats-grid">
            <div class="stat-card">
                <i class="fas fa-users" style="color: var(--primary-color);"></i>
                <h3><?= $totalParents ?></h3>
                <p>Total Parents</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-user-graduate" style="color: #10b981;"></i>
                <h3><?= $studentsWithParents ?></h3>
                <p>Students with Parents</p>
            </div>
            <?php if ($relationshipTableExists): ?>
            <div class="stat-card">
                <i class="fas fa-link" style="color: #f59e0b;"></i>
                <h3><?= $totalRelationships ?></h3>
                <p>Total Relationships</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-star" style="color: #ef4444;"></i>
                <h3><?= $primaryContacts ?></h3>
                <p>Primary Contacts</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Tabs -->
        <?php if ($relationshipTableExists): ?>
        <div class="tabs">
            <div class="tab active" onclick="switchTab('parents')">
                <i class="fas fa-users"></i> Parents Overview
            </div>
            <div class="tab" onclick="switchTab('relationships')">
                <i class="fas fa-link"></i> All Relationships
            </div>
            <div class="tab" onclick="switchTab('add-relationship')">
                <i class="fas fa-plus"></i> Add Relationship
            </div>
        </div>
        <?php endif; ?>

        <!-- Parents Overview Tab -->
        <div id="parents-tab" class="tab-content active">
            <div class="content-card">
                <div class="card-header">
                    <h2><i class="fas fa-list"></i> Parents List</h2>
                    <span class="badge" style="background-color: rgba(255,255,255,0.2); color: white;">
                        <?= $totalParents ?> Total Records
                    </span>
                </div>
                
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Parent ID</th>
                                <th>Parent Name</th>
                                <th>Contact Info</th>
                                <th>Students</th>
                                <?php if ($relationshipTableExists): ?>
                                <th>Relationships</th>
                                <?php endif; ?>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $parentsResult->data_seek(0);
                            while ($parent = $parentsResult->fetch_assoc()): 
                            ?>
                                <tr>
                                    <td><strong><?= safe_html($parent['parent_id']) ?></strong></td>
                                    <td>
                                        <div>
                                            <i class="fas fa-user"></i>
                                            <strong>
                                                <?php 
                                                $parentName = '';
                                                if ($parent['parent_first_name'] || $parent['parent_last_name']) {
                                                    $parentName = trim(($parent['parent_first_name'] ?? '') . ' ' . ($parent['parent_last_name'] ?? ''));
                                                }
                                                if (empty($parentName)) {
                                                    $parentName = $parent['username'] ?: 'Parent ID: ' . $parent['parent_id'];
                                                }
                                                echo safe_html($parentName);
                                                ?>
                                            </strong>
                                            <?php if ($parent['email']): ?>
                                                <div style="font-size: 0.875rem; color: var(--muted-text);">
                                                    <?= safe_html($parent['email']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($parent['phone_number']): ?>
                                            <div><i class="fas fa-phone"></i> <?= safe_html($parent['phone_number']) ?></div>
                                        <?php endif; ?>
                                        <?php if ($parent['address']): ?>
                                            <div><i class="fas fa-map-marker-alt"></i> <?= safe_html(substr($parent['address'], 0, 30)) ?>...</div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                        if ($relationshipTableExists && isset($parent['student_names']) && $parent['student_names']): 
                                        ?>
                                            <div><?= safe_html($parent['student_names']) ?></div>
                                            <div style="font-size: 0.8rem; color: var(--muted-text);">
                                                IDs: <?= safe_html($parent['student_numbers']) ?>
                                            </div>
                                        <?php 
                                        elseif (!$relationshipTableExists && isset($parent['student_first_name']) && $parent['student_first_name']): 
                                        ?>
                                            <div><?= safe_html($parent['student_first_name'] . ' ' . $parent['student_last_name']) ?></div>
                                            <div style="font-size: 0.8rem; color: var(--muted-text);">
                                                ID: <?= safe_html($parent['student_table_id']) ?>
                                            </div>
                                        <?php else: ?>
                                            <span style="color: var(--muted-text);">No students</span>
                                        <?php endif; ?>
                                    </td>
                                    <?php if ($relationshipTableExists): ?>
                                    <td>
                                        <?php if (isset($parent['relationships']) && $parent['relationships']): ?>
                                            <?php 
                                            $relationships = explode(', ', $parent['relationships']);
                                            foreach (array_unique($relationships) as $rel): 
                                                $badgeClass = 'badge-other';
                                                switch (strtolower($rel)) {
                                                    case 'father': $badgeClass = 'badge-father'; break;
                                                    case 'mother': $badgeClass = 'badge-mother'; break;
                                                    case 'guardian': $badgeClass = 'badge-guardian'; break;
                                                }
                                            ?>
                                                <span class="badge <?= $badgeClass ?>"><?= safe_html($rel) ?></span>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <span style="color: var(--muted-text);">No relationships</span>
                                        <?php endif; ?>
                                    </td>
                                    <?php endif; ?>
                                    <td>
                                        <a href="edit_parent_information.php?parent_id=<?= $parent['parent_id'] ?>" class="btn btn-warning btn-sm">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Relationships Tab (only show if relationship table exists) -->
        <?php if ($relationshipTableExists && $relationshipsResult): ?>
        <div id="relationships-tab" class="tab-content">
            <div class="content-card">
                <div class="card-header">
                    <h2><i class="fas fa-link"></i> All Student-Parent Relationships</h2>
                    <span class="badge" style="background-color: rgba(255,255,255,0.2); color: white;">
                        <?= $totalRelationships ?> Total Relationships
                    </span>
                </div>
                
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Parent</th>
                                <th>Relationship</th>
                                <th>Contact Type</th>
                                <th>Permissions</th>
                                <th>Notes</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $relationshipsResult->data_seek(0);
                            while ($rel = $relationshipsResult->fetch_assoc()): 
                            ?>
                                <tr>
                                    <td>
                                        <div>
                                            <?php 
                                            // Build student name with fallback logic
                                            $studentName = '';
                                            if (!empty($rel['student_first_name']) && !empty($rel['student_last_name'])) {
                                                $studentName = trim($rel['student_first_name'] . ' ' . $rel['student_last_name']);
                                            } elseif (!empty($rel['student_first_name'])) {
                                                $studentName = $rel['student_first_name'];
                                            } elseif (!empty($rel['student_last_name'])) {
                                                $studentName = $rel['student_last_name'];
                                            } elseif (!empty($rel['student_username'])) {
                                                $studentName = $rel['student_username'];
                                            } else {
                                                $studentName = 'Student ID: ' . ($rel['student_number'] ?? $rel['student_id']);
                                            }
                                            ?>
                                            <strong><?= safe_html($studentName) ?></strong>
                                            <div style="font-size: 0.8rem; color: var(--muted-text);">
                                                ID: <?= safe_html($rel['student_number'] ?? $rel['student_id']) ?> | Class: <?= safe_html($rel['student_class'], 'N/A') ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <strong>
                                                <?php 
                                                $parentName = '';
                                                if ($rel['parent_first_name'] || $rel['parent_last_name']) {
                                                    $parentName = trim(($rel['parent_first_name'] ?? '') . ' ' . ($rel['parent_last_name'] ?? ''));
                                                }
                                                if (empty($parentName)) {
                                                    $parentName = $rel['parent_username'] ?: 'Parent ID: ' . $rel['parent_id'];
                                                }
                                                echo safe_html($parentName);
                                                ?>
                                            </strong>
                                            <?php if ($rel['parent_phone']): ?>
                                                <div style="font-size: 0.8rem; color: var(--muted-text);">
                                                    <i class="fas fa-phone"></i> <?= safe_html($rel['parent_phone']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php 
                                        $badgeClass = 'badge-other';
                                        switch (strtolower($rel['relationship_type'] ?? '')) {
                                            case 'father': $badgeClass = 'badge-father'; break;
                                            case 'mother': $badgeClass = 'badge-mother'; break;
                                            case 'guardian': $badgeClass = 'badge-guardian'; break;
                                        }
                                        ?>
                                        <span class="badge <?= $badgeClass ?>"><?= safe_html($rel['relationship_type']) ?></span>
                                    </td>
                                    <td>
                                        <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                                            <?php if ($rel['is_primary_contact']): ?>
                                                <span class="badge" style="background-color: #fee2e2; color: #991b1b;">Primary</span>
                                            <?php endif; ?>
                                            <?php if ($rel['is_emergency_contact']): ?>
                                                <span class="badge" style="background-color: #fef3c7; color: #92400e;">Emergency</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                                            <?php if ($rel['can_pick_up']): ?>
                                                <span class="badge" style="background-color: #d1fae5; color: #065f46;">Can Pick Up</span>
                                            <?php else: ?>
                                                <span class="badge" style="background-color: #fee2e2; color: #991b1b;">No Pick Up</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="max-width: 150px; overflow: hidden; text-overflow: ellipsis;">
                                            <?= safe_html($rel['notes'] ?? 'No notes') ?>
                                        </div>
                                    </td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="relationship_id" value="<?= $rel['relationship_id'] ?>">
                                            <button type="submit" name="remove_relationship" class="btn btn-error btn-sm" 
                                                    onclick="return confirm('Remove this relationship?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Add Relationship Tab (only show if relationship table exists) -->
        <?php if ($relationshipTableExists): ?>
        <div id="add-relationship-tab" class="tab-content">
            <div class="form-container">
                <h2><i class="fas fa-plus"></i> Add Student-Parent Relationship</h2>
                <form method="POST">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem;">
                        <div class="form-group">
                            <label for="student_id">Select Student:</label>
                            <select name="student_id" id="student_id" required>
                                <option value="">Choose a student...</option>
                                <?php 
                                $studentsResult->data_seek(0);
                                while ($student = $studentsResult->fetch_assoc()): 
                                    // Build student display name with fallback logic
                                    $studentDisplayName = '';
                                    if (!empty($student['first_name']) && !empty($student['last_name'])) {
                                        $studentDisplayName = trim($student['first_name'] . ' ' . $student['last_name']);
                                    } elseif (!empty($student['first_name'])) {
                                        $studentDisplayName = $student['first_name'];
                                    } elseif (!empty($student['last_name'])) {
                                        $studentDisplayName = $student['last_name'];
                                    } elseif (!empty($student['username'])) {
                                        $studentDisplayName = $student['username'];
                                    } else {
                                        $studentDisplayName = 'Student ' . $student['student_id'];
                                    }
                                ?>
                                    <option value="<?= safe_html($student['student_id']) ?>">
                                        <?= safe_html($studentDisplayName) ?> 
                                        (ID: <?= safe_html($student['student_id']) ?>, Class: <?= safe_html($student['class'], 'N/A') ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="parent_id">Select Parent:</label>
                            <select name="parent_id" id="parent_id" required>
                                <option value="">Choose a parent...</option>
                                <?php 
                                $parentsResult->data_seek(0);
                                while ($parent = $parentsResult->fetch_assoc()): 
                                    $parentName = '';
                                    if ($parent['parent_first_name'] || $parent['parent_last_name']) {
                                        $parentName = trim(($parent['parent_first_name'] ?? '') . ' ' . ($parent['parent_last_name'] ?? ''));
                                    }
                                    if (empty($parentName)) {
                                        $parentName = $parent['username'] ?: 'Parent ID: ' . $parent['parent_id'];
                                    }
                                ?>
                                    <option value="<?= $parent['parent_id'] ?>">
                                        <?= safe_html($parentName) ?>
                                        <?php if ($parent['phone_number']): ?>
                                            (<?= safe_html($parent['phone_number']) ?>)
                                        <?php endif; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="relationship_type">Relationship Type:</label>
                            <select name="relationship_type" id="relationship_type" required>
                                <option value="">Select relationship...</option>
                                <option value="father">Father</option>
                                <option value="mother">Mother</option>
                                <option value="guardian">Guardian</option>
                                <option value="stepfather">Stepfather</option>
                                <option value="stepmother">Stepmother</option>
                                <option value="grandparent">Grandparent</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Contact Permissions:</label>
                        <div class="checkbox-group">
                            <div class="checkbox-item">
                                <input type="checkbox" name="is_primary_contact" id="is_primary_contact">
                                <label for="is_primary_contact">Primary Contact</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" name="is_emergency_contact" id="is_emergency_contact">
                                <label for="is_emergency_contact">Emergency Contact</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" name="can_pick_up" id="can_pick_up" checked>
                                <label for="can_pick_up">Can Pick Up Student</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="notes">Notes (Optional):</label>
                        <textarea name="notes" id="notes" rows="3" placeholder="Any additional notes about this relationship..."></textarea>
                    </div>

                    <div style="text-align: right;">
                        <button type="submit" name="add_relationship" class="btn btn-success">
                            <i class="fas fa-plus"></i> Add Relationship
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </main>

    <script>
        function switchTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Remove active class from all tabs
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected tab content
            document.getElementById(tabName + '-tab').classList.add('active');
            
            // Add active class to clicked tab
            event.target.closest('.tab').classList.add('active');
        }

        // Add search functionality for tables
        function addSearchToTable(tableContainer, searchInputId) {
            const searchInput = document.getElementById(searchInputId);
            if (!searchInput) return;
            
            const table = tableContainer.querySelector('.table');
            if (!table) return;
            
            const rows = table.querySelectorAll('tbody tr');

            searchInput.addEventListener('keyup', function() {
                const filter = this.value.toUpperCase();
                
                rows.forEach(row => {
                    const cells = row.querySelectorAll('td');
                    let found = false;
                    
                    cells.forEach(cell => {
                        if (cell.textContent.toUpperCase().includes(filter)) {
                            found = true;
                        }
                    });
                    
                    row.style.display = found ? '' : 'none';
                });
            });
        }

        // Initialize search functionality when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Add search inputs to card headers
            const cardHeaders = document.querySelectorAll('.card-header h2');
            cardHeaders.forEach((header, index) => {
                const cardContent = header.closest('.content-card');
                if (cardContent && cardContent.querySelector('.table tbody tr')) {
                    const searchId = 'search-' + index;
                    const searchHTML = `
                        <div style="display: flex; align-items: center; gap: 1rem; margin-left: auto;">
                            <input type="text" id="${searchId}" placeholder="Search..." 
                                   style="padding: 0.5rem; border-radius: 6px; border: 1px solid rgba(255,255,255,0.3); 
                                          background: rgba(255,255,255,0.1); color: white; width: 200px;">
                        </div>
                    `;
                    header.parentNode.insertAdjacentHTML('beforeend', searchHTML);
                    
                    // Initialize search for this table
                    addSearchToTable(cardContent, searchId);
                }
            });
        });
    </script>
</body>
</html>

<?php
$conn->close();
?>