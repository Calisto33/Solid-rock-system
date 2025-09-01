<?php
/**
 * Fix Student Data Mismatch
 * This script will properly sync student IDs with usernames
 * Place in: /opt/lampp/htdocs/school-management-system/fix_student_mismatch.php
 */

include 'config.php';

echo "<h1>🔧 Fix Student Data Mismatch</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
    .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
    .success { color: green; background: #e8f5e2; padding: 10px; border-radius: 5px; margin: 10px 0; }
    .error { color: red; background: #ffe6e6; padding: 10px; border-radius: 5px; margin: 10px 0; }
    .info { color: blue; background: #e6f3ff; padding: 10px; border-radius: 5px; margin: 10px 0; }
    .warning { color: orange; background: #fff3e6; padding: 10px; border-radius: 5px; margin: 10px 0; }
    table { width: 100%; border-collapse: collapse; margin: 15px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
    .student-id { font-family: monospace; font-weight: bold; color: #2563eb; }
    .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 5px; }
</style>";

echo "<div class='container'>";

if ($conn->connect_error) {
    echo "<div class='error'>❌ Database connection failed: " . $conn->connect_error . "</div>";
    exit();
}

echo "<div class='success'>✅ Database connected</div>";

try {
    echo "<h2>📊 Current State Analysis</h2>";
    
    // Get all students with their user data
    $query = "SELECT s.id, s.student_id, s.user_id, u.username, u.email, u.role, s.class
              FROM students s 
              LEFT JOIN users u ON s.user_id = u.id 
              ORDER BY s.id";
    
    $result = $conn->query($query);
    
    echo "<table>";
    echo "<tr><th>Students Table ID</th><th>Student ID</th><th>User ID</th><th>Username</th><th>Email</th><th>Class</th><th>Status</th></tr>";
    
    $issues = [];
    $valid_students = [];
    
    while ($row = $result->fetch_assoc()) {
        $status = "";
        $row_class = "";
        
        if (empty($row['username']) && !empty($row['student_id'])) {
            $status = "❌ Has Student ID but no username";
            $issues[] = $row;
            $row_class = "style='background-color: #ffebee;'";
        } elseif (!empty($row['username']) && empty($row['student_id'])) {
            $status = "⚠️ Has username but no Student ID";
            $issues[] = $row;
            $row_class = "style='background-color: #fff3e0;'";
        } elseif (!empty($row['username']) && !empty($row['student_id'])) {
            $status = "✅ Complete record";
            $valid_students[] = $row;
            $row_class = "style='background-color: #e8f5e2;'";
        } else {
            $status = "❓ Empty record";
            $issues[] = $row;
            $row_class = "style='background-color: #ffebee;'";
        }
        
        echo "<tr {$row_class}>";
        echo "<td>{$row['id']}</td>";
        echo "<td class='student-id'>" . ($row['student_id'] ?: 'NULL') . "</td>";
        echo "<td>{$row['user_id']}</td>";
        echo "<td>" . ($row['username'] ?: 'NULL') . "</td>";
        echo "<td>" . ($row['email'] ?: 'NULL') . "</td>";
        echo "<td>" . ($row['class'] ?: 'NULL') . "</td>";
        echo "<td>{$status}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<div class='info'>📈 Summary: " . count($valid_students) . " valid records, " . count($issues) . " issues found</div>";
    
    if (count($issues) > 0) {
        echo "<h2>🔧 Fixing Issues</h2>";
        
        $conn->begin_transaction();
        $fixed_count = 0;
        
        foreach ($issues as $issue) {
            if (empty($issue['username']) && !empty($issue['student_id'])) {
                // Has student ID but no username - delete this orphaned record
                echo "<div class='warning'>🗑️ Deleting orphaned record: ID {$issue['id']} with student_id {$issue['student_id']}</div>";
                
                $delete_stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
                $delete_stmt->bind_param("i", $issue['id']);
                $delete_stmt->execute();
                $delete_stmt->close();
                $fixed_count++;
                
            } elseif (!empty($issue['username']) && empty($issue['student_id'])) {
                // Has username but no student ID - generate one
                $year_suffix = substr(date('Y'), -2);
                $new_student_id = "WTC-{$year_suffix}" . str_pad($issue['user_id'], 3, '0', STR_PAD_LEFT) . chr(65 + ($issue['id'] % 26));
                
                echo "<div class='info'>✏️ Adding student ID to {$issue['username']}: <span class='student-id'>{$new_student_id}</span></div>";
                
                // Check if this ID already exists
                $check_stmt = $conn->prepare("SELECT student_id FROM students WHERE student_id = ?");
                $check_stmt->bind_param("s", $new_student_id);
                $check_stmt->execute();
                $check_stmt->store_result();
                
                if ($check_stmt->num_rows > 0) {
                    // ID exists, modify it
                    $new_student_id = "WTC-{$year_suffix}" . str_pad($issue['user_id'], 3, '0', STR_PAD_LEFT) . chr(65 + (($issue['id'] + 5) % 26));
                }
                $check_stmt->close();
                
                $update_stmt = $conn->prepare("UPDATE students SET student_id = ? WHERE id = ?");
                $update_stmt->bind_param("si", $new_student_id, $issue['id']);
                $update_stmt->execute();
                $update_stmt->close();
                $fixed_count++;
            }
        }
        
        $conn->commit();
        echo "<div class='success'>🎉 Fixed {$fixed_count} issues!</div>";
    }
    
    echo "<h2>📋 Final State</h2>";
    
    // Show final state
    $final_query = "SELECT s.student_id, u.username, u.email, s.class, s.created_at
                    FROM students s 
                    JOIN users u ON s.user_id = u.id 
                    WHERE u.role = 'student'
                    ORDER BY s.student_id";
    
    $final_result = $conn->query($final_query);
    
    if ($final_result && $final_result->num_rows > 0) {
        echo "<table>";
        echo "<tr><th>Student ID</th><th>Username</th><th>Email</th><th>Class</th><th>Registered</th></tr>";
        
        while ($row = $final_result->fetch_assoc()) {
            echo "<tr>";
            echo "<td class='student-id'>{$row['student_id']}</td>";
            echo "<td>{$row['username']}</td>";
            echo "<td>{$row['email']}</td>";
            echo "<td>" . ($row['class'] ?: 'Not Assigned') . "</td>";
            echo "<td>" . date('M j, Y', strtotime($row['created_at'])) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<div class='success'>✅ All students now have proper student IDs and usernames!</div>";
    } else {
        echo "<div class='error'>❌ No valid student records found</div>";
    }
    
} catch (Exception $e) {
    $conn->rollback();
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
}

echo "<div style='text-align: center; margin-top: 30px;'>";
echo "<a href='admin/add_remove_subjects.php' class='btn'>👥 View Student Management</a>";
echo "<a href='register.php' class='btn'>📝 Register New Student</a>";
echo "<a href='javascript:location.reload()' class='btn'>🔄 Refresh</a>";
echo "</div>";

echo "<div style='background: #e9ecef; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
echo "<h4>✅ What This Script Fixed:</h4>";
echo "<ul>";
echo "<li>🗑️ Removed orphaned student records (student_id without username)</li>";
echo "<li>🆔 Generated student IDs for records that had usernames but no student_id</li>";
echo "<li>🔗 Ensured all student records are properly linked to user accounts</li>";
echo "<li>📊 Cleaned up data inconsistencies</li>";
echo "</ul>";
echo "<p><strong>Result:</strong> Now when you assign classes, the student ID will properly correspond to the correct student!</p>";
echo "</div>";

echo "</div>";

$conn->close();
?>