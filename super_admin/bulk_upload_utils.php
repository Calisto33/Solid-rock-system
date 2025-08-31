<?php
// bulk_upload_utils.php - Utility functions and management

/**
 * Database table creation script for bulk upload logging
 */
function createBulkUploadTables($conn) {
    $sql = "
    CREATE TABLE IF NOT EXISTS bulk_uploads (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admin_id INT NOT NULL,
        filename VARCHAR(255) NOT NULL,
        file_size INT NOT NULL,
        total_records INT DEFAULT 0,
        successful_records INT DEFAULT 0,
        failed_records INT DEFAULT 0,
        warning_records INT DEFAULT 0,
        status ENUM('processing', 'completed', 'failed') DEFAULT 'processing',
        upload_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        completion_time TIMESTAMP NULL,
        error_log TEXT,
        success_log TEXT,
        INDEX idx_admin_id (admin_id),
        INDEX idx_upload_time (upload_time),
        INDEX idx_status (status)
    );

    CREATE TABLE IF NOT EXISTS bulk_upload_errors (
        id INT AUTO_INCREMENT PRIMARY KEY,
        bulk_upload_id INT NOT NULL,
        row_number INT NOT NULL,
        error_type ENUM('validation', 'duplicate', 'database', 'system') NOT NULL,
        error_message TEXT NOT NULL,
        user_data JSON,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (bulk_upload_id) REFERENCES bulk_uploads(id) ON DELETE CASCADE,
        INDEX idx_bulk_upload_id (bulk_upload_id),
        INDEX idx_error_type (error_type)
    );

    CREATE TABLE IF NOT EXISTS bulk_upload_queue (
        id INT AUTO_INCREMENT PRIMARY KEY,
        filename VARCHAR(255) NOT NULL,
        file_path VARCHAR(500) NOT NULL,
        admin_id INT NOT NULL,
        priority INT DEFAULT 1,
        status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
        attempts INT DEFAULT 0,
        max_attempts INT DEFAULT 3,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        processed_at TIMESTAMP NULL,
        INDEX idx_status (status),
        INDEX idx_priority (priority),
        INDEX idx_created_at (created_at)
    );
    ";

    return $conn->multi_query($sql);
}

/**
 * Bulk Upload Manager Class
 */
class BulkUploadManager {
    private $conn;
    private $upload_id;
    private $admin_id;
    
    public function __construct($conn, $admin_id) {
        $this->conn = $conn;
        $this->admin_id = $admin_id;
    }
    
    /**
     * Start a new bulk upload session
     */
    public function startUpload($filename, $file_size) {
        $stmt = $this->conn->prepare("
            INSERT INTO bulk_uploads (admin_id, filename, file_size, status) 
            VALUES (?, ?, ?, 'processing')
        ");
        $stmt->bind_param("isi", $this->admin_id, $filename, $file_size);
        
        if ($stmt->execute()) {
            $this->upload_id = $this->conn->insert_id;
            $stmt->close();
            return $this->upload_id;
        }
        
        $stmt->close();
        return false;
    }
    
    /**
     * Update upload statistics
     */
    public function updateStats($total, $successful, $failed, $warnings = 0) {
        $stmt = $this->conn->prepare("
            UPDATE bulk_uploads 
            SET total_records = ?, successful_records = ?, failed_records = ?, warning_records = ?
            WHERE id = ?
        ");
        $stmt->bind_param("iiiii", $total, $successful, $failed, $warnings, $this->upload_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    
    /**
     * Complete the upload
     */
    public function completeUpload($status = 'completed', $success_log = null, $error_log = null) {
        $stmt = $this->conn->prepare("
            UPDATE bulk_uploads 
            SET status = ?, completion_time = NOW(), success_log = ?, error_log = ?
            WHERE id = ?
        ");
        $stmt->bind_param("sssi", $status, $success_log, $error_log, $this->upload_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    
    /**
     * Log an error
     */
    public function logError($row_number, $error_type, $error_message, $user_data = null) {
        $user_data_json = $user_data ? json_encode($user_data) : null;
        
        $stmt = $this->conn->prepare("
            INSERT INTO bulk_upload_errors (bulk_upload_id, row_number, error_type, error_message, user_data)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("iisss", $this->upload_id, $row_number, $error_type, $error_message, $user_data_json);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    
    /**
     * Get upload history for admin
     */
    public function getUploadHistory($limit = 50) {
        $stmt = $this->conn->prepare("
            SELECT id, filename, file_size, total_records, successful_records, 
                   failed_records, warning_records, status, upload_time, completion_time
            FROM bulk_uploads 
            WHERE admin_id = ? 
            ORDER BY upload_time DESC 
            LIMIT ?
        ");
        $stmt->bind_param("ii", $this->admin_id, $limit);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $result;
    }
    
    /**
     * Get detailed errors for an upload
     */
    public function getUploadErrors($bulk_upload_id) {
        $stmt = $this->conn->prepare("
            SELECT row_number, error_type, error_message, user_data, created_at
            FROM bulk_upload_errors 
            WHERE bulk_upload_id = ? 
            ORDER BY row_number
        ");
        $stmt->bind_param("i", $bulk_upload_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $result;
    }
}

/**
 * Advanced CSV Processor with better error handling
 */
class CSVProcessor {
    private $file_path;
    private $headers;
    private $data;
    private $errors;
    
    public function __construct($file_path) {
        $this->file_path = $file_path;
        $this->errors = [];
    }
    
    /**
     * Process CSV file with enhanced validation
     */
    public function process() {
        if (!file_exists($this->file_path)) {
            throw new Exception("File not found: " . $this->file_path);
        }
        
        // Detect file encoding
        $content = file_get_contents($this->file_path);
        $encoding = $this->detectEncoding($content);
        
        if ($encoding !== 'UTF-8') {
            $content = mb_convert_encoding($content, 'UTF-8', $encoding);
            file_put_contents($this->file_path, $content);
        }
        
        $handle = fopen($this->file_path, 'r');
        if (!$handle) {
            throw new Exception("Could not open file for reading");
        }
        
        $row_number = 0;
        $this->data = [];
        
        while (($row = fgetcsv($handle, 0, ",")) !== FALSE) {
            $row_number++;
            
            if ($this->headers === null) {
                $this->headers = array_map('trim', $row);
                $this->headers = array_map('strtolower', $this->headers);
                $this->validateHeaders();
                continue;
            }
            
            // Skip completely empty rows
            if (empty(array_filter($row, 'strlen'))) {
                continue;
            }
            
            $user_data = $this->mapRowToData($row, $row_number);
            if ($user_data !== false) {
                $this->data[] = $user_data;
            }
            
            // Safety limit
            if (count($this->data) >= 1000) {
                break;
            }
        }
        
        fclose($handle);
        return $this->data;
    }
    
    /**
     * Validate CSV headers
     */
    private function validateHeaders() {
        $required = ['username', 'email', 'password', 'role'];
        $missing = [];
        
        foreach ($required as $header) {
            if (!in_array($header, $this->headers)) {
                $missing[] = $header;
            }
        }
        
        if (!empty($missing)) {
            throw new Exception("Missing required headers: " . implode(', ', $missing));
        }
    }
    
    /**
     * Map CSV row to user data array
     */
    private function mapRowToData($row, $row_number) {
        $data = ['row_number' => $row_number];
        
        foreach ($this->headers as $index => $header) {
            $value = isset($row[$index]) ? trim($row[$index]) : '';
            $data[$header] = $value;
        }
        
        // Basic row validation
        if (empty($data['username']) && empty($data['email'])) {
            $this->errors[] = "Row $row_number: Both username and email are empty";
            return false;
        }
        
        return $data;
    }
    
    /**
     * Detect file encoding
     */
    private function detectEncoding($content) {
        $encodings = ['UTF-8', 'ISO-8859-1', 'Windows-1252', 'ASCII'];
        
        foreach ($encodings as $encoding) {
            if (mb_check_encoding($content, $encoding)) {
                return $encoding;
            }
        }
        
        return 'UTF-8'; // Default fallback
    }
    
    /**
     * Get processing errors
     */
    public function getErrors() {
        return $this->errors;
    }
    
    /**
     * Get detected headers
     */
    public function getHeaders() {
        return $this->headers;
    }
}

/**
 * User Validator Class
 */
class UserValidator {
    private $conn;
    private $valid_roles = ['student', 'staff', 'admin', 'parent'];
    private $valid_statuses = ['active', 'pending', 'suspended'];
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Validate user data
     */
    public function validate($user_data) {
        $errors = [];
        $warnings = [];
        
        $username = trim($user_data['username'] ?? '');
        $email = trim($user_data['email'] ?? '');
        $password = trim($user_data['password'] ?? '');
        $role = trim($user_data['role'] ?? '');
        $status = trim($user_data['status'] ?? 'pending');
        
        // Username validation
        if (empty($username)) {
            $errors[] = 'Username is required';
        } elseif (strlen($username) < 2) {
            $errors[] = 'Username must be at least 2 characters';
        } elseif (strlen($username) > 50) {
            $errors[] = 'Username must not exceed 50 characters';
        } elseif (!preg_match('/^[a-zA-Z0-9\s\-_\.]+$/', $username)) {
            $errors[] = 'Username contains invalid characters';
        }
        
        // Email validation
        if (empty($email)) {
            $errors[] = 'Email is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        } elseif (strlen($email) > 100) {
            $errors[] = 'Email must not exceed 100 characters';
        }
        
        // Password validation
        if (empty($password)) {
            $errors[] = 'Password is required';
        } elseif (strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters';
        } elseif (strlen($password) > 255) {
            $errors[] = 'Password is too long';
        }
        
        // Role validation
        if (empty($role)) {
            $errors[] = 'Role is required';
        } elseif (!in_array($role, $this->valid_roles)) {
            $errors[] = 'Invalid role. Must be: ' . implode(', ', $this->valid_roles);
        }
        
        // Status validation
        if (!in_array($status, $this->valid_statuses)) {
            $warnings[] = "Invalid status '$status', defaulting to 'pending'";
            $status = 'pending';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'cleaned_data' => [
                'username' => $username,
                'email' => $email,
                'password' => $password,
                'role' => $role,
                'status' => $status
            ]
        ];
    }
    
    /**
     * Check for duplicates
     */
    public function checkDuplicates($email, $username) {
        $errors = [];
        
        // Check email
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = "Email '$email' already exists";
        }
        $stmt->close();
        
        // Check username
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = "Username '$username' already exists";
        }
        $stmt->close();
        
        return $errors;
    }
}

/**
 * Batch processor for large uploads
 */
class BatchProcessor {
    private $conn;
    private $batch_size;
    
    public function __construct($conn, $batch_size = 50) {
        $this->conn = $conn;
        $this->batch_size = $batch_size;
    }
    
    /**
     * Process users in batches
     */
    public function processBatch($users_data, $callback = null) {
        $batches = array_chunk($users_data, $this->batch_size);
        $results = [
            'total' => count($users_data),
            'successful' => 0,
            'failed' => 0,
            'warnings' => 0,
            'success_list' => [],
            'errors' => []
        ];
        
        foreach ($batches as $batch_num => $batch) {
            $this->conn->begin_transaction();
            
            try {
                $batch_results = $this->processSingleBatch($batch);
                
                // Merge results
                $results['successful'] += $batch_results['successful'];
                $results['failed'] += $batch_results['failed'];
                $results['warnings'] += $batch_results['warnings'];
                $results['success_list'] = array_merge($results['success_list'], $batch_results['success_list']);
                $results['errors'] = array_merge($results['errors'], $batch_results['errors']);
                
                $this->conn->commit();
                
                // Callback for progress updates
                if ($callback) {
                    $progress = (($batch_num + 1) / count($batches)) * 100;
                    call_user_func($callback, $progress, $batch_results);
                }
                
            } catch (Exception $e) {
                $this->conn->rollback();
                $results['errors'][] = "Batch " . ($batch_num + 1) . " failed: " . $e->getMessage();
            }
        }
        
        return $results;
    }
    
    /**
     * Process a single batch of users
     */
    private function processSingleBatch($batch) {
        $validator = new UserValidator($this->conn);
        $results = [
            'successful' => 0,
            'failed' => 0,
            'warnings' => 0,
            'success_list' => [],
            'errors' => []
        ];
        
        foreach ($batch as $user_data) {
            $row_num = $user_data['row_number'];
            
            // Validate user
            $validation = $validator->validate($user_data);
            
            if (!$validation['valid']) {
                $results['failed']++;
                foreach ($validation['errors'] as $error) {
                    $results['errors'][] = "Row $row_num: $error";
                }
                continue;
            }
            
            // Check duplicates
            $duplicate_errors = $validator->checkDuplicates(
                $validation['cleaned_data']['email'],
                $validation['cleaned_data']['username']
            );
            
            if (!empty($duplicate_errors)) {
                $results['failed']++;
                foreach ($duplicate_errors as $error) {
                    $results['errors'][] = "Row $row_num: $error";
                }
                continue;
            }
            
            // Create user
            if ($this->createUser($validation['cleaned_data'])) {
                $results['successful']++;
                $results['success_list'][] = $validation['cleaned_data']['username'] . 
                    " (" . $validation['cleaned_data']['email'] . ")";
                
                // Add warnings
                foreach ($validation['warnings'] as $warning) {
                    $results['warnings']++;
                }
            } else {
                $results['failed']++;
                $results['errors'][] = "Row $row_num: Failed to create user in database";
            }
        }
        
        return $results;
    }
    
    /**
     * Create a single user
     */
    private function createUser($user_data) {
        $hashed_password = password_hash($user_data['password'], PASSWORD_DEFAULT);
        
        $stmt = $this->conn->prepare("
            INSERT INTO users (username, email, password, role, status, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->bind_param("sssss", 
            $user_data['username'],
            $user_data['email'],
            $hashed_password,
            $user_data['role'],
            $user_data['status']
        );
        
        if ($stmt->execute()) {
            $user_id = $this->conn->insert_id;
            $stmt->close();
            
            // Create student record if needed
            if ($user_data['role'] === 'student') {
                return $this->createStudentRecord($user_id, $user_data['username']);
            }
            
            return true;
        }
        
        $stmt->close();
        return false;
    }
    
    /**
     * Create student record
     */
    private function createStudentRecord($user_id, $username) {
        $year_suffix = substr(date('Y'), -2);
        $student_id = "WTC-{$year_suffix}" . str_pad($user_id, 3, '0', STR_PAD_LEFT) . "A";
        
        $stmt = $this->conn->prepare("
            INSERT INTO students (student_id, user_id, username) 
            VALUES (?, ?, ?)
        ");
        
        $stmt->bind_param("sis", $student_id, $user_id, $username);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
}

/**
 * Queue Management System
 */
class UploadQueue {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Add upload to queue
     */
    public function addToQueue($filename, $file_path, $admin_id, $priority = 1) {
        $stmt = $this->conn->prepare("
            INSERT INTO bulk_upload_queue (filename, file_path, admin_id, priority)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("ssii", $filename, $file_path, $admin_id, $priority);
        $result = $stmt->execute();
        $queue_id = $this->conn->insert_id;
        $stmt->close();
        
        return $queue_id;
    }
    
    /**
     * Get next item from queue
     */
    public function getNextInQueue() {
        $stmt = $this->conn->prepare("
            SELECT * FROM bulk_upload_queue 
            WHERE status = 'pending' AND attempts < max_attempts
            ORDER BY priority DESC, created_at ASC
            LIMIT 1
        ");
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        return $result;
    }
    
    /**
     * Update queue item status
     */
    public function updateQueueStatus($queue_id, $status, $increment_attempts = false) {
        $sql = "UPDATE bulk_upload_queue SET status = ?";
        $params = [$status];
        $types = "s";
        
        if ($increment_attempts) {
            $sql .= ", attempts = attempts + 1";
        }
        
        if ($status === 'processing') {
            $sql .= ", processed_at = NOW()";
        }
        
        $sql .= " WHERE id = ?";
        $params[] = $queue_id;
        $types .= "i";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
}

// Example usage and testing functions
if (basename(__FILE__) == basename($_SERVER["SCRIPT_NAME"])) {
    // This code runs only if the file is accessed directly (for testing)
    
    echo "Bulk Upload Utilities Test\n";
    echo "========================\n\n";
    
    // Test CSV processing
    if (isset($_GET['test']) && $_GET['test'] === 'csv') {
        $test_csv = "username,email,password,role,status\n";
        $test_csv .= "John Doe,john@test.com,pass123,student,active\n";
        $test_csv .= "Jane Smith,jane@test.com,pass456,staff,pending\n";
        
        file_put_contents('/tmp/test_bulk.csv', $test_csv);
        
        try {
            $processor = new CSVProcessor('/tmp/test_bulk.csv');
            $data = $processor->process();
            
            echo "CSV Processing Test Results:\n";
            echo "Headers: " . implode(', ', $processor->getHeaders()) . "\n";
            echo "Records: " . count($data) . "\n";
            echo "Errors: " . count($processor->getErrors()) . "\n";
            
            if (!empty($processor->getErrors())) {
                echo "Error details:\n";
                foreach ($processor->getErrors() as $error) {
                    echo "- $error\n";
                }
            }
            
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }
        
        unlink('/tmp/test_bulk.csv');
    }
}
?>