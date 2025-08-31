<?php
/**
 * Simplified Secure Registration Handler
 * Compatible with existing database structure
 */

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Start session with secure settings
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database config
include 'config.php';

// Simple logging function
function logSecurityEvent($event, $details = []) {
    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'event' => $event,
        'details' => $details
    ];
    
    // Simple error logging - no file creation issues
    error_log("SECURITY: " . json_encode($log_entry));
}

// Enhanced error display function
function showSecureError($message, $log_details = []) {
    logSecurityEvent('registration_error', array_merge(['message' => $message], $log_details));
    
    echo "<!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Registration Error - Wisetech College</title>
        <style>
            body { font-family: 'Segoe UI', sans-serif; background: linear-gradient(135deg, #fee2e2, #fecaca); margin: 0; padding: 20px; }
            .error-card { background: white; padding: 3rem; border-radius: 16px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); 
                         text-align: center; max-width: 500px; margin: 2rem auto; border-left: 6px solid #ef4444; }
            .error-icon { font-size: 4rem; color: #ef4444; margin-bottom: 1.5rem; animation: pulse 2s infinite; }
            @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
            h1 { color: #1f2937; margin-bottom: 1rem; font-size: 1.8rem; }
            p { color: #6b7280; margin-bottom: 2rem; line-height: 1.6; }
            .btn { display: inline-block; padding: 1rem 2rem; background: linear-gradient(90deg, #2563eb, #3b82f6); 
                  color: white; text-decoration: none; border-radius: 8px; transition: all 0.3s; font-weight: 500; }
            .btn:hover { background: linear-gradient(90deg, #1d4ed8, #2563eb); transform: translateY(-2px); 
                        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3); }
        </style>
    </head>
    <body>
        <div class='error-card'>
            <div class='error-icon'>🛡️</div>
            <h1>Registration Error</h1>
            <p>" . htmlspecialchars($message) . "</p>
            <a href='register.php' class='btn'>Try Again</a>
        </div>
    </body>
    </html>";
    exit();
}

// Rate limiting function
function checkRateLimit() {
    $current_time = time();
    
    // Initialize rate limiting arrays
    if (!isset($_SESSION['registration_attempts'])) {
        $_SESSION['registration_attempts'] = [];
    }
    
    // Clean old attempts (older than 1 hour)
    $_SESSION['registration_attempts'] = array_filter($_SESSION['registration_attempts'], function($attempt_time) use ($current_time) {
        return ($current_time - $attempt_time) < 3600;
    });
    
    // Check hourly limit (max 5 attempts per hour)
    if (count($_SESSION['registration_attempts']) >= 5) {
        $remaining_time = 3600 - ($current_time - min($_SESSION['registration_attempts']));
        $remaining_minutes = ceil($remaining_time / 60);
        
        logSecurityEvent('rate_limit_exceeded', [
            'attempts' => count($_SESSION['registration_attempts'])
        ]);
        
        showSecureError("Too many registration attempts. Please wait {$remaining_minutes} minutes before trying again.");
    }
    
    // Record this attempt
    $_SESSION['registration_attempts'][] = $current_time;
}

// Enhanced CSRF validation
function validateCSRF() {
    // Check if CSRF components are present
    if (!isset($_SESSION['csrf_token']) || !isset($_POST['csrf_token'])) {
        logSecurityEvent('csrf_missing');
        showSecureError('Security validation failed. Please refresh the page and try again.');
    }
    
    // Check token validity
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        logSecurityEvent('csrf_token_mismatch');
        showSecureError('Security token mismatch. Please refresh the page and try again.');
    }
}

// Honeypot validation
function validateHoneypot() {
    // Find honeypot field (dynamic name based on CSRF token)
    $honeypot_field = 'website_url_' . substr($_SESSION['csrf_token'], 0, 8);
    
    if (isset($_POST[$honeypot_field]) && !empty($_POST[$honeypot_field])) {
        logSecurityEvent('honeypot_triggered', ['field' => $honeypot_field]);
        showSecureError('Automated submission detected. Please try again manually.');
    }
}

// Enhanced input validation and sanitization
function validateAndSanitizeInput() {
    $data = [];
    
    // Username validation
    $username = trim($_POST['username'] ?? '');
    if (empty($username)) {
        showSecureError('Full name is required.');
    }
    if (strlen($username) < 2 || strlen($username) > 50) {
        showSecureError('Full name must be between 2 and 50 characters.');
    }
    if (!preg_match('/^[a-zA-Z\s\'-]+$/u', $username)) {
        showSecureError('Full name contains invalid characters.');
    }
    $data['username'] = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
    
    // Email validation
    $email = trim($_POST['email'] ?? '');
    if (empty($email)) {
        showSecureError('Email address is required.');
    }
    if (strlen($email) > 100) {
        showSecureError('Email address is too long.');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        showSecureError('Please enter a valid email address.');
    }
    $data['email'] = strtolower($email);
    
    // Password validation
    $password = $_POST['password'] ?? '';
    if (empty($password)) {
        showSecureError('Password is required.');
    }
    if (strlen($password) < 8) {
        showSecureError('Password must be at least 8 characters long.');
    }
    
    // Password strength validation
    $strength_score = 0;
    $missing_requirements = [];
    
    if (!preg_match('/[a-z]/', $password)) {
        $missing_requirements[] = 'lowercase letter';
    } else {
        $strength_score++;
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $missing_requirements[] = 'uppercase letter';
    } else {
        $strength_score++;
    }
    
    if (!preg_match('/\d/', $password)) {
        $missing_requirements[] = 'number';
    } else {
        $strength_score++;
    }
    
    if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $password)) {
        $missing_requirements[] = 'special character';
    } else {
        $strength_score++;
    }
    
    // Require at least 3 out of 4 requirements
    if ($strength_score < 3) {
        showSecureError('Password needs: ' . implode(', ', $missing_requirements) . '.');
    }
    
    $data['password'] = $password;
    
    // Role validation
    $role = trim($_POST['role'] ?? '');
    $allowed_roles = ['student', 'staff', 'admin', 'parent'];
    if (!in_array($role, $allowed_roles)) {
        showSecureError('Invalid role selected.');
    }
    $data['role'] = $role;
    
    return $data;
}

// Database operations - compatible with existing structure
function createUserAccount($data) {
    global $conn;
    
    try {
        // Check database connection
        if ($conn->connect_error) {
            logSecurityEvent('database_connection_failed');
            showSecureError('Database connection failed. Please try again later.');
        }
        
        // Check for existing email
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        if (!$stmt) {
            throw new Exception("Database prepare error: " . $conn->error);
        }
        
        $stmt->bind_param("s", $data['email']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $stmt->close();
            logSecurityEvent('duplicate_email_attempt', ['email' => $data['email']]);
            showSecureError('An account with this email address already exists.');
        }
        $stmt->close();
        
        // Check for existing username
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
        if (!$stmt) {
            throw new Exception("Database prepare error: " . $conn->error);
        }
        
        $stmt->bind_param("s", $data['username']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $stmt->close();
            showSecureError('This username is already taken. Please choose a different one.');
        }
        $stmt->close();
        
        // Hash password with strong algorithm
        $password_hash = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // Insert user with existing table structure
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, role, status, created_at, updated_at) VALUES (?, ?, ?, ?, 'pending', NOW(), NOW())");
        
        if (!$stmt) {
            throw new Exception("Database prepare error: " . $conn->error);
        }
        
        $stmt->bind_param("ssss", $data['username'], $data['email'], $password_hash, $data['role']);
        
        if (!$stmt->execute()) {
            throw new Exception("User creation failed: " . $stmt->error);
        }
        
        $user_id = $conn->insert_id;
        $stmt->close();
        
        $student_id = null;
        
        // Create student record if applicable
        if ($data['role'] === 'student') {
            $year_suffix = substr(date('Y'), -2);
            $student_id = "WTC-{$year_suffix}" . str_pad($user_id, 4, '0', STR_PAD_LEFT);
            
            // Use existing students table structure
            $student_stmt = $conn->prepare("INSERT INTO students (student_id, user_id, username, email, status, created_at, updated_at) VALUES (?, ?, ?, ?, 'Active', NOW(), NOW())");
            
            if (!$student_stmt) {
                throw new Exception("Student table prepare error: " . $conn->error);
            }
            
            $student_stmt->bind_param("siss", $student_id, $user_id, $data['username'], $data['email']);
            
            if (!$student_stmt->execute()) {
                throw new Exception("Student record creation failed: " . $student_stmt->error);
            }
            $student_stmt->close();
        }
        
        // Log successful registration
        logSecurityEvent('user_registered', [
            'user_id' => $user_id,
            'email' => $data['email'],
            'role' => $data['role']
        ]);
        
        return [
            'user_id' => $user_id,
            'student_id' => $student_id
        ];
        
    } catch (Exception $e) {
        logSecurityEvent('database_error', ['error' => $e->getMessage()]);
        showSecureError('Account creation failed: ' . $e->getMessage());
    }
}

// Main execution
try {
    // Validate request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        logSecurityEvent('invalid_request_method');
        showSecureError('Invalid request method.');
    }
    
    // Security validations
    checkRateLimit();
    validateCSRF();
    validateHoneypot();
    
    // Input validation
    $validated_data = validateAndSanitizeInput();
    
    // Create account
    $result = createUserAccount($validated_data);
    
    // Regenerate CSRF token
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    
    // Success message
    if ($validated_data['role'] === 'student') {
        $success_message = "Student registered successfully! Student ID: {$result['student_id']}";
    } else {
        $success_message = "User registered successfully as {$validated_data['role']}!";
    }

} catch (Exception $e) {
    logSecurityEvent('unexpected_error', ['error' => $e->getMessage()]);
    showSecureError('An unexpected error occurred: ' . $e->getMessage());
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Successful - Wisetech College</title>
    <style>
        body {
            font-family: 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #f0f7ff 0%, #e6f0fd 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .success-card {
            background: white;
            padding: 3rem;
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
            text-align: center;
            max-width: 600px;
            width: 100%;
            position: relative;
            overflow: hidden;
        }
        .success-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 8px;
            background: linear-gradient(90deg, #10b981, #059669);
        }
        .success-icon {
            font-size: 5rem;
            margin-bottom: 2rem;
            animation: bounce 2s infinite;
        }
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-20px); }
            60% { transform: translateY(-10px); }
        }
        h1 {
            color: #1f2937;
            margin-bottom: 1rem;
            font-size: 2.5rem;
            font-weight: 700;
        }
        .success-message {
            color: #059669;
            font-size: 1.2rem;
            margin-bottom: 2rem;
            font-weight: 600;
        }
        .student-id-container {
            background: linear-gradient(135deg, #ecfdf5, #d1fae5);
            border: 3px solid #10b981;
            padding: 2rem;
            border-radius: 16px;
            margin: 2rem 0;
            box-shadow: 0 4px 6px rgba(16, 185, 129, 0.1);
        }
        .student-id-label {
            font-size: 1rem;
            color: #064e3b;
            margin-bottom: 1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
        }
        .student-id-value {
            font-size: 2.5rem;
            font-weight: bold;
            color: #059669;
            font-family: 'Courier New', monospace;
            letter-spacing: 3px;
            text-shadow: 0 2px 4px rgba(5, 150, 105, 0.2);
        }
        .info-box {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            border: 2px solid #f59e0b;
            padding: 1.5rem;
            margin: 2rem 0;
            border-radius: 12px;
            text-align: left;
        }
        .info-box h4 {
            color: #92400e;
            margin-bottom: 1rem;
            font-size: 1.1rem;
            font-weight: 600;
        }
        .info-box p {
            margin: 0.5rem 0;
            color: #92400e;
            line-height: 1.6;
        }
        .btn-container {
            margin-top: 3rem;
        }
        .btn {
            display: inline-block;
            padding: 1rem 2.5rem;
            margin: 0.5rem;
            background: linear-gradient(90deg, #2563eb, #3b82f6);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.3s ease;
            font-weight: 600;
            font-size: 1.1rem;
            box-shadow: 0 4px 6px rgba(37, 99, 235, 0.2);
        }
        .btn:hover {
            background: linear-gradient(90deg, #1d4ed8, #2563eb);
            transform: translateY(-3px);
            box-shadow: 0 8px 15px rgba(37, 99, 235, 0.3);
        }
        .btn-secondary {
            background: linear-gradient(90deg, #6b7280, #9ca3af);
            box-shadow: 0 4px 6px rgba(107, 114, 128, 0.2);
        }
        .btn-secondary:hover {
            background: linear-gradient(90deg, #4b5563, #6b7280);
            box-shadow: 0 8px 15px rgba(107, 114, 128, 0.3);
        }
        @media (max-width: 640px) {
            .success-card {
                padding: 2rem;
                margin: 1rem;
            }
            .student-id-value {
                font-size: 2rem;
            }
            .btn {
                display: block;
                margin: 0.5rem 0;
            }
        }
    </style>
</head>
<body>
    <div class="success-card">
        <div class="success-icon">🎓</div>
        <h1>Registration Successful!</h1>
        <div class="success-message">
            <?php echo htmlspecialchars($success_message); ?>
        </div>
        
        <?php if ($validated_data['role'] === 'student' && isset($result['student_id'])): ?>
            <div class="student-id-container">
                <div class="student-id-label">Your Student ID</div>
                <div class="student-id-value"><?php echo htmlspecialchars($result['student_id']); ?></div>
            </div>
        <?php endif; ?>
        
        <div class="info-box">
            <h4><i class="fas fa-info-circle"></i> Account Status</h4>
            <p><strong>Status:</strong> Your account is currently pending approval.</p>
            <p><strong>Next Steps:</strong> An administrator will review and activate your account.</p>
            <p><strong>Access:</strong> You will be notified once your account is activated.</p>
        </div>
        
        <div class="btn-container">
            <a href="register.php" class="btn btn-secondary">Register Another User</a>
            <a href="admin/admin_home.php" class="btn">Dashboard</a>
        </div>
    </div>
</body>
</html>