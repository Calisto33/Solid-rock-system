<?php
// Enhanced Security Registration Form
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security Headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\' cdnjs.cloudflare.com; style-src \'self\' \'unsafe-inline\' cdnjs.cloudflare.com; font-src \'self\' cdnjs.cloudflare.com; img-src \'self\' data:;');

// Rate Limiting - Track registration attempts
if (!isset($_SESSION['registration_attempts'])) {
    $_SESSION['registration_attempts'] = [];
}

<<<<<<< HEAD
// Check if user is logged in and get their role
$is_admin = false;
$user_role = '';
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    $user_role = $_SESSION['role'];
    $is_admin = ($user_role === 'admin');
}
=======
// Clean old attempts (older than 1 hour)
$current_time = time();
$_SESSION['registration_attempts'] = array_filter($_SESSION['registration_attempts'], function($attempt_time) use ($current_time) {
    return ($current_time - $attempt_time) < 3600; // 1 hour
});

// Check if too many attempts (max 5 per hour)
if (count($_SESSION['registration_attempts']) >= 5) {
    $remaining_time = 3600 - ($current_time - min($_SESSION['registration_attempts']));
    $remaining_minutes = ceil($remaining_time / 60);
    die("<!DOCTYPE html><html><head><title>Rate Limited</title><style>body{font-family:Arial,sans-serif;text-align:center;padding:50px;background:#f8f9fa;}h1{color:#dc3545;}</style></head><body><h1>Too Many Attempts</h1><p>Please wait {$remaining_minutes} minutes before trying again.</p></body></html>");
}

// Enhanced CSRF Token with timestamp and user agent
if (empty($_SESSION['csrf_token']) || empty($_SESSION['csrf_timestamp']) || (time() - $_SESSION['csrf_timestamp']) > 1800) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['csrf_timestamp'] = time();
    $_SESSION['csrf_user_agent'] = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '');
}

// Honeypot field for bot detection
$honeypot_field = 'website_url_' . substr($_SESSION['csrf_token'], 0, 8);
>>>>>>> b291daf7f49078bb0cccb1439969ad4a74e2db38
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Secure Registration - Wisetech College Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous">
    <style>
        :root {
            --primary-color: #2563eb;
            --primary-dark: #1d4ed8;
            --secondary-color: #3b82f6;
            --accent-color: #60a5fa;
            --success-color: #10b981;
            --text-color: #1f2937;
            --text-light: #6b7280;
            --white: #ffffff;
            --gray-light: #f3f4f6;
            --error-color: #ef4444;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --radius: 12px;
            --radius-sm: 8px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #f0f7ff 0%, #e6f0fd 100%);
            color: var(--text-color);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Security indicator */
        .security-indicator {
            background: linear-gradient(90deg, var(--success-color), #059669);
            color: white;
            padding: 0.5rem;
            text-align: center;
            font-size: 0.85rem;
            font-weight: 500;
        }

        header {
            background-color: var(--white);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .logo-container {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        header img {
            height: 45px;
            border-radius: 8px;
        }

        header h1 {
            font-size: 1.4rem;
            font-weight: 600;
            color: var(--primary-color);
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .bulk-upload-btn {
            background: linear-gradient(90deg, var(--success-color), #059669);
            color: var(--white);
            padding: 0.75rem 1.25rem;
            border: none;
            border-radius: var(--radius-sm);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            box-shadow: var(--shadow);
        }

        .bulk-upload-btn:hover {
            background: linear-gradient(90deg, #059669, #047857);
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(16, 185, 129, 0.3);
        }

        .bulk-upload-btn i {
            font-size: 1rem;
        }

        .container {
            flex: 1;
            width: 90%;
            max-width: 500px;
            margin: 2.5rem auto;
            background-color: var(--white);
            padding: 2.5rem;
            border-radius: var(--radius);
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
            position: relative;
        }

        .container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
            border-radius: var(--radius) var(--radius) 0 0;
        }

        .form-header {
            margin-bottom: 2rem;
            text-align: center;
        }

        .form-header h2 {
            color: var(--text-color);
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .form-header p {
            color: var(--text-light);
            font-size: 0.95rem;
        }

        .admin-notice {
            background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
            border-left: 4px solid var(--success-color);
            padding: 1rem;
            border-radius: var(--radius-sm);
            margin-bottom: 2rem;
            text-align: center;
        }

        .admin-notice h3 {
            color: #065f46;
            font-size: 1rem;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .admin-notice p {
            color: #047857;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .admin-notice .bulk-btn-large {
            background: var(--success-color);
            color: var(--white);
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: var(--radius-sm);
            text-decoration: none;
            font-size: 1rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .admin-notice .bulk-btn-large:hover {
            background: #059669;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(16, 185, 129, 0.3);
        }

        .form-group {
            margin-bottom: 1.8rem;
            position: relative;
        }

        .form-group.has-icon i.field-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            font-size: 1.1rem;
            z-index: 2;
        }

        label {
            display: block;
            margin-bottom: 0.6rem;
            font-weight: 500;
            color: var(--text-color);
            font-size: 0.95rem;
        }

        .required::after {
            content: ' *';
            color: var(--error-color);
        }

        input[type="text"],
        input[type="email"],
        select {
            width: 100%;
            padding: 0.9rem 1rem 0.9rem 2.8rem;
            border: 1px solid #e5e7eb;
            border-radius: var(--radius-sm);
            font-size: 1rem;
            background-color: var(--gray-light);
            transition: all 0.3s ease;
        }

        .password-container {
            position: relative;
            display: flex;
            align-items: center;
        }

        .password-container input[type="password"],
        .password-container input[type="text"] {
            width: 100%;
            padding: 0.9rem 3rem 0.9rem 2.8rem;
            border: 1px solid #e5e7eb;
            border-radius: var(--radius-sm);
            font-size: 1rem;
            background-color: var(--gray-light);
            transition: all 0.3s ease;
        }

        input:focus,
        select:focus {
            outline: none;
            border-color: var(--accent-color);
            background-color: var(--white);
            box-shadow: 0 0 0 3px rgba(96, 165, 250, 0.2);
        }

        input.valid {
            border-color: var(--success-color);
            background-color: #f0fdf4;
        }

        input.invalid {
            border-color: var(--error-color);
            background-color: #fef2f2;
        }

        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            cursor: pointer;
            font-size: 1rem;
            z-index: 3;
            padding: 0.5rem;
            transition: all 0.3s ease;
            border-radius: 4px;
        }

        .password-toggle:hover {
            color: var(--primary-color);
            background-color: rgba(37, 99, 235, 0.1);
        }

        /* Password strength indicator */
        .password-strength {
            margin-top: 0.5rem;
            height: 4px;
            background-color: #e5e7eb;
            border-radius: 2px;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .password-strength-bar {
            height: 100%;
            width: 0%;
            transition: all 0.3s ease;
            border-radius: 2px;
        }

        .password-strength.weak .password-strength-bar {
            width: 25%;
            background-color: var(--error-color);
        }

        .password-strength.fair .password-strength-bar {
            width: 50%;
            background-color: var(--warning-color);
        }

        .password-strength.good .password-strength-bar {
            width: 75%;
            background-color: #3b82f6;
        }

        .password-strength.strong .password-strength-bar {
            width: 100%;
            background-color: var(--success-color);
        }

        .password-requirements {
            margin-top: 0.5rem;
            font-size: 0.8rem;
            color: var(--text-light);
        }

        .requirement {
            display: flex;
            align-items: center;
            margin: 0.2rem 0;
        }

        .requirement i {
            margin-right: 0.5rem;
            width: 12px;
        }

        .requirement.met {
            color: var(--success-color);
        }

        .requirement.unmet {
            color: var(--error-color);
        }

        /* Honeypot field - hidden from users */
        .honeypot {
            position: absolute;
            left: -9999px;
            opacity: 0;
            pointer-events: none;
            tab-index: -1;
        }

        button {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            color: var(--white);
            border: none;
            border-radius: var(--radius-sm);
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        button:hover:not(:disabled) {
            background: linear-gradient(90deg, var(--primary-dark), var(--primary-color));
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .login-link {
            display: block;
            text-align: center;
            margin-top: 1.5rem;
            color: var(--text-light);
            font-size: 0.95rem;
        }

        .login-link a {
            color: var(--primary-color);
            font-weight: 500;
            text-decoration: none;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

<<<<<<< HEAD
        .divider {
            display: flex;
            align-items: center;
            margin: 2rem 0;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e5e7eb;
        }

        .divider span {
            padding: 0 1rem;
            color: var(--text-light);
            font-size: 0.9rem;
            font-weight: 500;
=======
        .security-features {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: var(--radius-sm);
            padding: 1rem;
            margin-top: 1.5rem;
            font-size: 0.85rem;
            color: var(--text-light);
        }

        .security-features h4 {
            color: var(--text-color);
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .security-features ul {
            list-style: none;
            padding: 0;
        }

        .security-features li {
            margin: 0.2rem 0;
            display: flex;
            align-items: center;
        }

        .security-features li i {
            color: var(--success-color);
            margin-right: 0.5rem;
            width: 12px;
>>>>>>> b291daf7f49078bb0cccb1439969ad4a74e2db38
        }

        @media screen and (max-width: 768px) {
            header {
                flex-direction: column;
                gap: 1rem;
                padding: 1.25rem;
            }

            .header-actions {
                width: 100%;
                justify-content: center;
            }

            .container {
                width: 90%;
                padding: 2rem;
                margin: 2rem auto;
            }

            .admin-notice h3 {
                font-size: 0.9rem;
            }

            .bulk-upload-btn {
                font-size: 0.85rem;
                padding: 0.65rem 1rem;
            }
        }

        @media screen and (max-width: 480px) {
            .container {
                width: 95%;
                padding: 1.8rem;
            }

            .form-header h2 {
                font-size: 1.5rem;
            }

            .bulk-upload-btn {
                font-size: 0.8rem;
                padding: 0.6rem 0.9rem;
            }

            .admin-notice {
                padding: 0.8rem;
            }
        }

        /* Animation for bulk upload button */
        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(16, 185, 129, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(16, 185, 129, 0);
            }
        }

        .bulk-upload-btn.pulse {
            animation: pulse 2s infinite;
        }
    </style>
</head>
<body>
    <div class="security-indicator">
        <i class="fas fa-shield-alt"></i> Secure Connection - Your data is protected with enterprise-grade security
    </div>

    <header>
        <div class="logo-container">
            <img src="images/logo.jpg" alt="Wisetech College Logo">
            <h1>Wisetech College Portal</h1>
        </div>
        
        <?php if ($is_admin): ?>
        <div class="header-actions">
            <a href="admin_bulk_upload.php" class="bulk-upload-btn" title="Add multiple users at once">
                <i class="fas fa-users-cog"></i>
                <span>Bulk Upload Users</span>
            </a>
        </div>
        <?php endif; ?>
    </header>

    <main>
        <div class="container">
            <?php if ($is_admin): ?>
            <div class="admin-notice">
                <h3>
                    <i class="fas fa-crown"></i>
                    Administrator Panel
                </h3>
                <p>Need to add multiple users? Use our bulk upload feature to save time!</p>
                <a href="admin_bulk_upload.php" class="bulk-btn-large">
                    <i class="fas fa-cloud-upload-alt"></i>
                    Upload Multiple Users
                </a>
            </div>
            
            <div class="divider">
                <span>OR ADD INDIVIDUAL USER</span>
            </div>
            <?php endif; ?>
            
            <div class="form-header">
<<<<<<< HEAD
                <h2>Create an Account</h2>
                <p><?php echo $is_admin ? 'Add a single user manually' : 'Join our community to access exclusive resources'; ?></p>
=======
                <h2>Create Your Account</h2>
                <p>Join our secure community platform</p>
>>>>>>> b291daf7f49078bb0cccb1439969ad4a74e2db38
            </div>
            
            <form action="register_user.php" method="POST" id="registrationForm" autocomplete="off">
                <!-- Enhanced CSRF Token -->
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="csrf_timestamp" value="<?php echo $_SESSION['csrf_timestamp']; ?>">
                <input type="hidden" name="form_signature" value="<?php echo hash_hmac('sha256', $_SESSION['csrf_token'] . $_SESSION['csrf_timestamp'], 'wisetech_secret_key_2024'); ?>">
                
                <!-- Honeypot field for bot detection -->
                <input type="text" name="<?php echo $honeypot_field; ?>" class="honeypot" tabindex="-1" autocomplete="off">

                <div class="form-group has-icon">
                    <label for="username" class="required">Full Name</label>
                    <i class="fas fa-user field-icon"></i>
                    <input type="text" id="username" name="username" placeholder="Enter your full name" required maxlength="50" autocomplete="name" data-validate="name">
                </div>

                <div class="form-group has-icon">
                    <label for="email" class="required">Email Address</label>
                    <i class="fas fa-envelope field-icon"></i>
                    <input type="email" id="email" name="email" placeholder="Enter your email" required maxlength="100" autocomplete="email" data-validate="email">
                </div>

                <div class="form-group">
                    <label for="password" class="required">Password</label>
                    <div class="password-container">
                        <i class="fas fa-lock field-icon"></i>
                        <input type="password" id="password" name="password" placeholder="Create a strong password" required minlength="8" autocomplete="new-password" data-validate="password">
                        <i class="fas fa-eye password-toggle" id="togglePassword" title="Show/Hide Password"></i>
                    </div>
                    <div class="password-strength" id="passwordStrength">
                        <div class="password-strength-bar"></div>
                    </div>
                    <div class="password-requirements">
                        <div class="requirement unmet" id="req-length">
                            <i class="fas fa-times"></i> At least 8 characters
                        </div>
                        <div class="requirement unmet" id="req-uppercase">
                            <i class="fas fa-times"></i> One uppercase letter
                        </div>
                        <div class="requirement unmet" id="req-lowercase">
                            <i class="fas fa-times"></i> One lowercase letter
                        </div>
                        <div class="requirement unmet" id="req-number">
                            <i class="fas fa-times"></i> One number
                        </div>
                        <div class="requirement unmet" id="req-special">
                            <i class="fas fa-times"></i> One special character
                        </div>
                    </div>
                </div>

                <div class="form-group has-icon">
                    <label for="role" class="required">Select Your Role</label>
                    <i class="fas fa-user-tag field-icon"></i>
                    <select id="role" name="role" required data-validate="role">
                        <option value="">Choose your role</option>
                        <option value="student">Student</option>
                        <option value="staff">Faculty/Staff</option>
                        <?php if ($is_admin): ?>
                        <option value="admin">Administrator</option>
                        <?php endif; ?>
                        <option value="parent">Parent/Guardian</option>
                    </select>
                </div>

<<<<<<< HEAD
                <?php if ($is_admin): ?>
                <div class="form-group">
                    <label for="status">Account Status</label>
                    <i class="fas fa-toggle-on"></i>
                    <select id="status" name="status">
                        <option value="pending">Pending Approval</option>
                        <option value="active">Active</option>
                        <option value="suspended">Suspended</option>
                    </select>
                </div>
                <?php endif; ?>

                <button type="submit">
                    <?php echo $is_admin ? '<i class="fas fa-user-plus"></i> Create User Account' : 'Create Account'; ?>
                </button>
                
                <div class="login-link">
                    <?php if ($is_admin): ?>
                        <a href="admin_home.php">
                            <i class="fas fa-arrow-left"></i> Back to Admin Dashboard
                        </a>
                        <br><br>
                        Need help? <a href="admin_help.php">View Registration Guide</a>
                    <?php else: ?>
                        Already have an account? <a href="login.php">Sign in</a>
                    <?php endif; ?>
=======
                <button type="submit" id="submitBtn" disabled>
                    <i class="fas fa-shield-alt"></i>
                    Create Secure Account
                    <i class="fas fa-spinner fa-spin" id="loadingSpinner" style="display: none; margin-left: 0.5rem;"></i>
                </button>
                
                <div class="login-link">
                    Already have an account? <a href="login.php">Sign in securely</a>
                </div>

                <div class="security-features">
                    <h4><i class="fas fa-shield-alt"></i> Security Features Active</h4>
                    <ul>
                        <li><i class="fas fa-check"></i> CSRF protection enabled</li>
                        <li><i class="fas fa-check"></i> Rate limiting active</li>
                        <li><i class="fas fa-check"></i> Bot detection running</li>
                        <li><i class="fas fa-check"></i> Password strength validation</li>
                        <li><i class="fas fa-check"></i> Data encryption in transit</li>
                    </ul>
>>>>>>> b291daf7f49078bb0cccb1439969ad4a74e2db38
                </div>
            </form>
        </div>
    </main>

    <script>
        // Enhanced security and validation
        const form = document.getElementById('registrationForm');
        const submitBtn = document.getElementById('submitBtn');
        const togglePassword = document.getElementById('togglePassword');
        const password = document.getElementById('password');
        const passwordStrength = document.getElementById('passwordStrength');
        
        // Password strength calculation
        function calculatePasswordStrength(password) {
            let score = 0;
            const requirements = {
                length: password.length >= 8,
                uppercase: /[A-Z]/.test(password),
                lowercase: /[a-z]/.test(password),
                number: /\d/.test(password),
                special: /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)
            };
            
            // Update requirement indicators
            Object.keys(requirements).forEach(req => {
                const element = document.getElementById(`req-${req}`);
                if (requirements[req]) {
                    element.classList.remove('unmet');
                    element.classList.add('met');
                    element.querySelector('i').className = 'fas fa-check';
                    score++;
                } else {
                    element.classList.remove('met');
                    element.classList.add('unmet');
                    element.querySelector('i').className = 'fas fa-times';
                }
            });
            
            return score;
        }
        
        // Password strength indicator
        password.addEventListener('input', function() {
            const score = calculatePasswordStrength(this.value);
            const strength = ['', 'weak', 'fair', 'good', 'strong'][Math.min(score, 4)];
            
            passwordStrength.className = `password-strength ${strength}`;
            
            if (score >= 4) {
                this.classList.add('valid');
                this.classList.remove('invalid');
            } else if (this.value.length > 0) {
                this.classList.add('invalid');
                this.classList.remove('valid');
            } else {
                this.classList.remove('valid', 'invalid');
            }
            
            checkFormValidity();
        });
        
        // Password toggle
        togglePassword.addEventListener('click', function() {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
        
        // Real-time validation
        function validateField(field) {
            const value = field.value.trim();
            const type = field.dataset.validate;
            let isValid = false;
            
            switch(type) {
                case 'name':
                    isValid = value.length >= 2 && /^[a-zA-Z\s'-]+$/.test(value);
                    break;
                case 'email':
                    isValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
                    break;
                case 'password':
                    isValid = calculatePasswordStrength(value) >= 4;
                    break;
                case 'role':
                    isValid = ['student', 'staff', 'admin', 'parent'].includes(value);
                    break;
            }
            
            if (value.length > 0) {
                field.classList.toggle('valid', isValid);
                field.classList.toggle('invalid', !isValid);
            } else {
                field.classList.remove('valid', 'invalid');
            }
            
            return isValid;
        }
        
        // Check overall form validity
        function checkFormValidity() {
            const fields = form.querySelectorAll('[data-validate]');
            let allValid = true;
            
            fields.forEach(field => {
                if (!validateField(field) || field.value.trim() === '') {
                    allValid = false;
                }
            });
            
            submitBtn.disabled = !allValid;
        }
        
        // Add validation listeners
        form.querySelectorAll('[data-validate]').forEach(field => {
            field.addEventListener('blur', () => validateField(field));
            field.addEventListener('input', checkFormValidity);
        });
        
        // Form submission with enhanced validation
        form.addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const role = document.getElementById('role').value;
            
            // Final validation
            if (!username || !email || !password || !role) {
                e.preventDefault();
                alert('Please fill in all required fields.');
                return;
            }
            
            if (calculatePasswordStrength(password) < 4) {
                e.preventDefault();
                alert('Please create a stronger password that meets all requirements.');
                return;
            }
            
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                e.preventDefault();
                alert('Please enter a valid email address.');
                return;
            }
            
            // Check honeypot
            const honeypot = form.querySelector('.honeypot');
            if (honeypot.value !== '') {
                e.preventDefault();
                alert('Security check failed. Please try again.');
                return;
            }
            
            // Show loading state
            submitBtn.disabled = true;
            document.getElementById('loadingSpinner').style.display = 'inline-block';
            submitBtn.innerHTML = '<i class="fas fa-shield-alt"></i> Creating Account... <i class="fas fa-spinner fa-spin" style="margin-left: 0.5rem;"></i>';
        });
        
        // Prevent form resubmission
        window.addEventListener('beforeunload', function() {
            if (form.querySelector('[name="csrf_token"]')) {
                form.querySelector('[name="csrf_token"]').value = '';
            }
        });
        
        // Auto-focus first field
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('username').focus();
        });
        
        // Disable right-click on form (optional security measure)
        form.addEventListener('contextmenu', function(e) {
            e.preventDefault();
        });

        // Admin-specific enhancements
        <?php if ($is_admin): ?>
        // Add pulse animation to bulk upload button periodically
        setInterval(() => {
            const bulkBtn = document.querySelector('.bulk-upload-btn');
            if (bulkBtn) {
                bulkBtn.classList.add('pulse');
                setTimeout(() => bulkBtn.classList.remove('pulse'), 2000);
            }
        }, 30000); // Pulse every 30 seconds

        // Auto-fill demo data for testing (admin only)
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.shiftKey && e.code === 'KeyT') { // Ctrl+Shift+T for test data
                document.getElementById('username').value = 'Test User ' + Math.floor(Math.random() * 1000);
                document.getElementById('email').value = 'test' + Math.floor(Math.random() * 1000) + '@example.com';
                document.getElementById('password').value = 'testpass123';
                document.getElementById('role').value = 'student';
                
                // Show notification
                const notification = document.createElement('div');
                notification.textContent = 'Test data filled! (Admin shortcut)';
                notification.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: #10b981;
                    color: white;
                    padding: 10px 20px;
                    border-radius: 8px;
                    z-index: 1000;
                    font-size: 14px;
                `;
                document.body.appendChild(notification);
                setTimeout(() => notification.remove(), 3000);
            }
        });

        // Enhanced form validation for admins
        document.querySelector('form').addEventListener('submit', function(e) {
            const role = document.getElementById('role').value;
            const status = document.getElementById('status')?.value;
            
            if (role === 'admin' && status === 'active') {
                const confirm = window.confirm(
                    'You are creating an active administrator account. This user will have full system access. Continue?'
                );
                if (!confirm) {
                    e.preventDefault();
                    return;
                }
            }
        });

        // Show quick stats tooltip on hover
        document.querySelector('.bulk-upload-btn')?.addEventListener('mouseenter', function() {
            // You could add a tooltip showing current user stats here
            this.title = 'Upload CSV/Excel files with multiple user accounts at once';
        });
        <?php endif; ?>
    </script>
</body>
</html>