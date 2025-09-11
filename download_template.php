<?php
// download_template.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check admin authentication (modify according to your auth system)
/*
if (!isset($_SESSION['admin_id']) || $_SESSION['role'] !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    exit('Access denied');
}
*/

$format = $_GET['format'] ?? 'csv';
$action = $_GET['action'] ?? '';

if ($action !== 'download_template') {
    header('HTTP/1.1 400 Bad Request');
    exit('Invalid action');
}

if ($format === 'excel') {
    // Generate Excel file using simple HTML table (Excel can read this)
    $filename = 'bulk_users_template.xls';
    
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    echo '<html>';
    echo '<head><meta charset="UTF-8"></head>';
    echo '<body>';
    echo '<table border="1">';
    
    // Headers
    echo '<tr>';
    echo '<th>username</th>';
    echo '<th>email</th>';
    echo '<th>password</th>';
    echo '<th>role</th>';
    echo '<th>status</th>';
    echo '</tr>';
    
    // Sample data
    $sampleData = [
         ['Frist name Last name', 'john@example.com', 'password123', 'student', 'pending'],
            ['Jane Smith', 'janesmith@example.com', 'securepass456', 'staff', 'pending'],
            ['Mike Fan', 'mike@example.com', 'mypassword789', 'student', 'pending'],
            ['Sarah Wilson', 'sarah@example.com', 'adminpass000', 'admin', 'pending'],
            ['Parent P', 'parent@example.com', 'parentpass111', 'parent', 'pending']
    ];
    
    foreach ($sampleData as $row) {
        echo '<tr>';
        foreach ($row as $cell) {
            echo '<td>' . htmlspecialchars($cell) . '</td>';
        }
        echo '</tr>';
    }
    
    echo '</table>';
    echo '</body>';
    echo '</html>';
    
} else {
    // CSV format
    $filename = 'bulk_users_template.csv';
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    $output = fopen('php://output', 'w');
    
    // Headers
    fputcsv($output, ['username', 'email', 'password', 'role', 'status']);
    
    // Sample data
    $sampleData = [
         ['Frist name Last name', 'john@example.com', 'password123', 'student', 'pending'],
            ['Jane Smith', 'janesmith@example.com', 'securepass456', 'staff', 'pending'],
            ['Mike Fan', 'mike@example.com', 'mypassword789', 'student', 'pending'],
            ['Sarah Wilson', 'sarah@example.com', 'adminpass000', 'admin', 'pending'],
            ['Parent P', 'parent@example.com', 'parentpass111', 'parent', 'pending']
    ];
    
    foreach ($sampleData as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
}
?>