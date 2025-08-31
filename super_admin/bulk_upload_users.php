<?php
// bulk_upload_users.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if session is already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'config.php';

// Check if admin is logged in (add your admin authentication check here)
// if (!isset($_SESSION['admin_id'])) {
//     header('HTTP/1.0 403 Forbidden');
//     exit('Access denied');
// }

header('Content-Type: application/json');

// CSRF protection
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

// Validate CSRF token
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit();
}

// Check if file was uploaded
if (!isset($_FILES['bulk_file']) || $_FILES['bulk_file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['error' => 'No file uploaded or upload error']);
    exit();
}

$file = $_FILES['bulk_file'];
$filename = $file['name'];
$tmp_name = $file['tmp_name'];
$file_size = $file['size'];

// Validate file size (5MB limit)
$max_file_size = 5 * 1024 * 1024; // 5MB
if ($file_size > $max_file_size) {
    echo json_encode(['error' => 'File size too large. Maximum 5MB allowed.']);
    exit();
}

// Validate file type
$file_extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
$allowed_extensions = ['csv', 'xlsx', 'xls'];

if (!in_array($file_extension, $allowed_extensions)) {
    echo json_encode(['error' => 'Invalid file type. Only CSV and Excel files allowed.']);
    exit();
}

// Process the file based on type
$users_data = [];

try {
    if ($file_extension === 'csv') {
        $users_data = processCsvFile($tmp_name);
    } else {
        // For Excel files, you'd use a library like PhpSpreadsheet
        $users_data = processExcelFile($tmp_name);
    }
} catch (Exception $e) {
    echo json_encode(['error' => 'Error processing file: ' . $e->getMessage()]);
    exit();
}

// Validate and process users
$results = processUsers($users_data, $conn);

// Return results
echo json_encode($results);

/**
 * Process CSV file
 */
function processCsvFile($file_path) {
    $users = [];
    $row_number = 0;
    
    if (($handle = fopen($file_path, "r")) !== FALSE) {
        $headers = null;
        
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $row_number++;
            
            if ($headers === null) {
                // First row is headers
                $headers = array_map('trim', $data);
                $headers = array_map('strtolower', $headers);
                
                // Validate required headers
                $required_headers = ['username', 'email', 'password', 'role'];
                foreach ($required_headers as $required) {
                    if (!in_array($required, $headers)) {
                        throw new Exception("Missing required header: $required");
                    }
                }
                continue;
            }
            
            // Skip empty rows
            if (empty(array_filter($data))) {
                continue;
            }
            
            // Map data to headers
            $user = [];
            foreach ($headers as $index => $header) {
                $user[$header] = isset($data[$index]) ? trim($data[$index]) : '';
            }
            
            $user['row_number'] = $row_number;
            $users[] = $user;
            
            // Limit to 1000 users
            if (count($users) >= 1000) {
                break;
            }
        }
        fclose($handle);
    }
    
    return $users;
}

/**
 * Process Excel file (requires PhpSpreadsheet library)
 */
function processExcelFile($file_path) {
    // This is a placeholder - you'd need to install PhpSpreadsheet
    // composer require phpoffice/phpspreadsheet
    
    /*
    require_once 'vendor/autoload.php';
    
    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file_path);
    $worksheet = $spreadsheet->getActiveSheet();
    $rows = $worksheet->toArray();
    
    $users = [];
    $headers = null;
    
    foreach ($rows as $row_number => $row) {
        if ($headers === null) {
            $headers = array_map('trim', $row);
            $headers = array_map('strtolower', $headers);
            continue;
        }
        
        if (empty(array_filter($row))) continue;
        
        $user = [];
        foreach ($headers as $index => $header) {
            $user[$header] = isset($row[$index]) ? trim($row[$index]) : '';
        }
        
        $user['row_number'] = $row_number + 1;
        $users[] = $user;
        
        if (count($users) >= 1000) break;
    }
    
    return $users;
    */
    
    throw new Exception('Excel processing not implemented. Please use CSV format.');
}

/**
 * Process and insert users into database
 */
function processUsers($users_data, $conn) {
    $results = [
        'total' => count($users_data),
        'successful' => 0,
        'failed' => 0,
        'warnings' => 0,
        'success_list' => [],
        'errors' => [],
        'warnings_list' => []
    ];
    
    $valid_roles = ['student', 'staff', 'admin', 'parent'];
    $valid_statuses = ['active', 'pending', 'suspended'];
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        foreach ($users_data as $user_data) {
            $row_num = $user_data['row_number'];
            $errors = [];
            
            // Validate required fields
            $username = trim($user_data['username'] ?? '');
            $email = trim($user_data['email'] ?? '');
            $password = trim($user_data['password'] ?? '');
            $role = trim($user_data['role'] ?? '');
            $status = trim($user_data['status'] ?? 'pending');
            
            // Basic validation
            if (empty($username)) {
                $errors[] = "Row $row_num: Username is required";
            }
            
            if (empty($email)) {
                $errors[] = "Row $row_num: Email is required";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Row $row_num: Invalid email format";
            }
            
            if (empty($password)) {
                $errors[] = "Row $row_num: Password is required";
            } elseif (strlen($password) < 6) {
                $errors[] = "Row $row_num: Password must be at least 6 characters";
            }
            
            if (empty($role)) {
                $errors[] = "Row $row_num: Role is required";
            } elseif (!in_array($role, $valid_roles)) {
                $errors[] = "Row $row_num: Invalid role '$role'. Must be: " . implode(', ', $valid_roles);
            }
            
            if (!in_array($status, $valid_statuses)) {
                $status = 'pending';
                $results['warnings']++;
                $results['warnings_list'][] = "Row $row_num: Invalid status, defaulted to 'pending'";
            }
            
            if (!empty($errors)) {
                $results['failed']++;
                $results['errors'] = array_merge($results['errors'], $errors);
                continue;
            }
            
            // Check if email already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $results['failed']++;
                $results['errors'][] = "Row $row_num: Email '$email' already exists";
                $stmt->close();
                continue;
            }
            $stmt->close();
            
            // Check if username already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $results['failed']++;
                $results['errors'][] = "Row $row_num: Username '$username' already exists";
                $stmt->close();
                continue;
            }
            $stmt->close();
            
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, role, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("sssss", $username, $email, $hashed_password, $role, $status);
            
            if ($stmt->execute()) {
                $user_id = $conn->insert_id;
                $student_id = null;
                
                // If student, create student record
                if ($role === 'student') {
                    $year_suffix = substr(date('Y'), -2);
                    $student_id = "WTC-{$year_suffix}" . str_pad($user_id, 3, '0', STR_PAD_LEFT) . "A";
                    
                    $student_stmt = $conn->prepare("INSERT INTO students (student_id, user_id, username) VALUES (?, ?, ?)");
                    $student_stmt->bind_param("sis", $student_id, $user_id, $username);
                    
                    if (!$student_stmt->execute()) {
                        // If student record fails, rollback user creation
                        throw new Exception("Failed to create student record for $username");
                    }
                    $student_stmt->close();
                }
                
                $results['successful']++;
                $success_msg = "$username ($email) - $role";
                if ($student_id) {
                    $success_msg .= " - Student ID: $student_id";
                }
                $results['success_list'][] = $success_msg;
                
            } else {
                $results['failed']++;
                $results['errors'][] = "Row $row_num: Database error for $username - " . $stmt->error;
            }
            $stmt->close();
        }
        
        // Commit transaction
        $conn->commit();
        
    } catch (Exception $e) {
        // Rollback on any error
        $conn->rollback();
        $results['errors'][] = "Transaction failed: " . $e->getMessage();
    }
    
    return $results;
}

/**
 * Generate unique student ID
 */
function generateUniqueStudentId($conn) {
    $max_attempts = 10;
    $attempts = 0;
    
    do {
        $year_suffix = substr(date('Y'), -2);
        $random_num = str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
        $student_id = "WTC-{$year_suffix}{$random_num}A";
        
        $stmt = $conn->prepare("SELECT student_id FROM students WHERE student_id = ?");
        $stmt->bind_param("s", $student_id);
        $stmt->execute();
        $exists = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        
        $attempts++;
    } while ($exists && $attempts < $max_attempts);
    
    if ($exists) {
        throw new Exception("Could not generate unique student ID");
    }
    
    return $student_id;
}

/**
 * Validate file upload
 */
function validateUpload($file) {
    $errors = [];
    
    // Check file size (5MB limit)
    $max_size = 5 * 1024 * 1024;
    if ($file['size'] > $max_size) {
        $errors[] = 'File too large (max 5MB)';
    }
    
    // Check file type
    $allowed_types = ['text/csv', 'application/csv', 'application/vnd.ms-excel', 
                      'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime_type, $allowed_types)) {
        $errors[] = 'Invalid file type. Only CSV and Excel files allowed.';
    }
    
    return $errors;
}

/**
 * Log bulk upload activity
 */
function logBulkUpload($admin_id, $filename, $results) {
    global $conn;
    
    $log_data = json_encode([
        'filename' => $filename,
        'total' => $results['total'],
        'successful' => $results['successful'],
        'failed' => $results['failed'],
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, details, created_at) VALUES (?, 'bulk_upload', ?, NOW())");
    if ($stmt) {
        $stmt->bind_param("is", $admin_id, $log_data);
        $stmt->execute();
        $stmt->close();
    }
}

// Optional: Add rate limiting
function checkRateLimit($user_id) {
    global $conn;
    
    // Allow max 5 uploads per hour
    $stmt = $conn->prepare("SELECT COUNT(*) as upload_count FROM activity_logs 
                           WHERE user_id = ? AND action = 'bulk_upload' 
                           AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    return $result['upload_count'] < 5;
}

/**
 * Send notification emails (optional)
 */
function sendBulkUploadNotification($admin_email, $results) {
    $subject = "Bulk User Upload Complete";
    $message = "
    Bulk upload completed:
    - Total processed: {$results['total']}
    - Successful: {$results['successful']}
    - Failed: {$results['failed']}
    - Warnings: {$results['warnings']}
    ";
    
    // Use your preferred email library here
    // mail($admin_email, $subject, $message);
}

/**
 * Generate CSV template
 */
function generateCSVTemplate() {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="bulk_users_template.csv"');
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8 (helps with Excel compatibility)
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Headers
    fputcsv($output, ['username', 'email', 'password', 'role', 'status']);
    
    // Sample data
    $sampleData = [
        ['John Doe', 'john.doe@example.com', 'password123', 'student', 'active'],
        ['Jane Smith', 'jane.smith@example.com', 'securepass456', 'staff', 'pending'],
        ['Mike Johnson', 'mike.j@example.com', 'mypassword789', 'student', 'active'],
        ['Sarah Wilson', 'sarah.w@example.com', 'adminpass000', 'admin', 'active'],
        ['Tom Brown', 'tom.brown@example.com', 'parentpass111', 'parent', 'pending'],
        ['Lisa Davis', 'lisa.davis@example.com', 'staffpass222', 'staff', 'active'],
        ['Robert Taylor', 'robert.t@example.com', 'studentpass333', 'student', 'pending'],
        ['Emma Martinez', 'emma.m@example.com', 'parentpass444', 'parent', 'active']
    ];
    
    foreach ($sampleData as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit();
}

/**
 * Generate Excel template (SpreadsheetML format)
 */
function generateExcelTemplate() {
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="bulk_users_template.xlsx"');
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    $headers = ['username', 'email', 'password', 'role', 'status'];
    $sampleData = [
        ['John Doe', 'john.doe@example.com', 'password123', 'student', 'active'],
        ['Jane Smith', 'jane.smith@example.com', 'securepass456', 'staff', 'pending'],
        ['Mike Johnson', 'mike.j@example.com', 'mypassword789', 'student', 'active'],
        ['Sarah Wilson', 'sarah.w@example.com', 'adminpass000', 'admin', 'active'],
        ['Tom Brown', 'tom.brown@example.com', 'parentpass111', 'parent', 'pending'],
        ['Lisa Davis', 'lisa.davis@example.com', 'staffpass222', 'staff', 'active'],
        ['Robert Taylor', 'robert.t@example.com', 'studentpass333', 'student', 'pending'],
        ['Emma Martinez', 'emma.m@example.com', 'parentpass444', 'parent', 'active']
    ];
    
    $xml = '<?xml version="1.0" encoding="UTF-8"?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
    xmlns:o="urn:schemas-microsoft-com:office:office"
    xmlns:x="urn:schemas-microsoft-com:office:excel"
    xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
    xmlns:html="http://www.w3.org/TR/REC-html40">
    
    <Styles>
        <Style ss:ID="headerStyle">
            <Font ss:Bold="1" ss:Color="#FFFFFF"/>
            <Interior ss:Color="#2563eb" ss:Pattern="Solid"/>
            <Borders>
                <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
                <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
                <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
                <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>
            </Borders>
        </Style>
        <Style ss:ID="requiredStyle">
            <Interior ss:Color="#FEF3C7" ss:Pattern="Solid"/>
            <Borders>
                <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E5E7EB"/>
                <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E5E7EB"/>
                <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E5E7EB"/>
                <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E5E7EB"/>
            </Borders>
        </Style>
        <Style ss:ID="dataStyle">
            <Borders>
                <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E5E7EB"/>
                <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E5E7EB"/>
                <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E5E7EB"/>
                <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E5E7EB"/>
            </Borders>
        </Style>
    </Styles>
    
    <Worksheet ss:Name="Bulk Users Template">
        <Table>
            <Row>';
    
    // Add headers
    foreach ($headers as $header) {
        $xml .= '<Cell ss:StyleID="headerStyle"><Data ss:Type="String">' . htmlspecialchars($header) . '</Data></Cell>';
    }
    $xml .= '</Row>';
    
    // Add sample data
    foreach ($sampleData as $row) {
        $xml .= '<Row>';
        foreach ($row as $index => $cell) {
            $styleId = ($index < 4) ? 'requiredStyle' : 'dataStyle';
            $xml .= '<Cell ss:StyleID="' . $styleId . '"><Data ss:Type="String">' . htmlspecialchars($cell) . '</Data></Cell>';
        }
        $xml .= '</Row>';
    }
    
    // Add instructions
    $xml .= '<Row></Row>';
    $xml .= '<Row>
        <Cell ss:MergeAcross="4" ss:StyleID="dataStyle">
            <Data ss:Type="String">INSTRUCTIONS: Required columns (yellow) must be filled. Valid roles: student, staff, admin, parent. Valid statuses: active, pending, suspended (optional, defaults to pending).</Data>
        </Cell>
    </Row>';
    
    $xml .= '</Table>
    </Worksheet>
</Workbook>';
    
    echo $xml;
    exit();
}

/**
 * Enhanced error handling for different file formats
 */
function detectFileEncoding($file_path) {
    $encodings = ['UTF-8', 'ISO-8859-1', 'Windows-1252'];
    $content = file_get_contents($file_path);
    
    foreach ($encodings as $encoding) {
        if (mb_check_encoding($content, $encoding)) {
            return $encoding;
        }
    }
    
    return 'UTF-8'; // Default fallback
}

/**
 * Clean and sanitize user input
 */
function sanitizeUserData($data) {
    return array_map(function($value) {
        return trim(htmlspecialchars($value, ENT_QUOTES, 'UTF-8'));
    }, $data);
}

/**
 * Validate email domain (optional whitelist)
 */
function validateEmailDomain($email, $allowed_domains = []) {
    if (empty($allowed_domains)) {
        return true; // No domain restrictions
    }
    
    $domain = substr(strrchr($email, "@"), 1);
    return in_array($domain, $allowed_domains);
}

/**
 * Generate password if not provided
 */
function generateRandomPassword($length = 12) {
    $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    $password = '';
    
    for ($i = 0; $i < $length; $i++) {
        $password .= $characters[random_int(0, strlen($characters) - 1)];
    }
    
    return $password;
}

/**
 * Batch email sending for new users (optional)
 */
function sendWelcomeEmails($successful_users) {
    foreach ($successful_users as $user) {
        // Queue or send welcome emails
        // This would integrate with your email system
    }
}
?>