<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include 'config.php';

// Generate CSRF Token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Check for login errors
$login_error = $_SESSION['login_error'] ?? null;
unset($_SESSION['login_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Solid Rock Group of Schools</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/jpeg" href="images/logo.jpeg">
    <link rel="shortcut icon" type="image/jpeg" href="images/logo.jpeg">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    
    <style>
        /* Using the same variables as landing page */
        :root {
            --primary-color: #003366;
            --secondary-color: #d12c2c;
            --accent-color: #f0f4f8;
            --text-color: #333;
            --light-text-color: #fff;
            --border-radius: 12px;
            --box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(-45deg, #f0f4f8, #e2e8f0, #f7fafc, #edf2f7);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
            color: var(--text-color);
            line-height: 1.6;
            min-height: 100vh;
            overflow-x: hidden;
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }



        /* Login Section - Full screen centered */
        .login-section {
            background: transparent;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
        }

        /* Floating particles background */
        .login-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23003366' fill-opacity='0.03'%3E%3Ccircle cx='30' cy='30' r='2'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E") repeat;
            /* animation: float 20s linear infinite; */
            pointer-events: none;
        }

        @keyframes float {
            0% { transform: translateY(0px) rotate(0deg); }
            100% { transform: translateY(-60px) rotate(360deg); }
        }

        .login-container {
            display: flex;
            align-items: center;
            gap: 50px;
            max-width: 1000px;
            width: 100%;
            position: relative;
            z-index: 2;
        }

        .login-image {
            flex: 1;
            text-align: center;
            position: relative;
        }

        .login-image img {
            max-width: 100%;
            border-radius: var(--border-radius);
            box-shadow: 0 20px 40px rgba(0, 51, 102, 0.15);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .login-image img:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 25px 50px rgba(0, 51, 102, 0.2);
        }

        /* Decorative elements around image */
        .login-image::before {
            content: '';
            position: absolute;
            top: -20px;
            right: -20px;
            width: 40px;
            height: 40px;
            border: 3px solid var(--secondary-color);
            border-radius: 50%;
            opacity: 0.6;
            animation: pulse 2s infinite;
        }

        .login-image::after {
            content: '';
            position: absolute;
            bottom: -15px;
            left: -15px;
            width: 30px;
            height: 30px;
            background: var(--primary-color);
            border-radius: 4px;
            opacity: 0.4;
            animation: rotate 3s linear infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.6; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }

        @keyframes rotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .login-form-container {
            flex: 1;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 50px 40px;
            border-radius: var(--border-radius);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }

        /* Subtle gradient overlay */
        .login-form-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
        }

        .login-header {
            text-align: center;
            margin-bottom: 40px;
            position: relative;
        }

        .login-header::before {
            content: '';
            position: absolute;
            top: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 50px;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            border-radius: 2px;
        }

        .login-header .subtitle {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 0.9rem;
            opacity: 0;
            animation: slideInUp 0.6s ease forwards 0.2s;
        }

        .login-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            line-height: 1.2;
            margin-bottom: 15px;
            color: var(--primary-color);
            opacity: 0;
            animation: slideInUp 0.6s ease forwards 0.4s;
        }

        .login-header p {
            color: #555;
            margin-bottom: 0;
            opacity: 0;
            animation: slideInUp 0.6s ease forwards 0.6s;
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-group {
            margin-bottom: 25px;
            opacity: 0;
            animation: slideInUp 0.6s ease forwards;
        }

        .form-group:nth-child(1) { animation-delay: 0.8s; }
        .form-group:nth-child(2) { animation-delay: 1.0s; }
        .form-group:nth-child(3) { animation-delay: 1.2s; }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-color);
        }

        .form-control {
            width: 100%;
            padding: 15px 18px;
            border: 2px solid #e2e8f0;
            border-radius: var(--border-radius);
            font-size: 1rem;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.8);
            position: relative;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(0, 51, 102, 0.1);
            transform: translateY(-2px);
            background: #fff;
        }

        .form-control::placeholder {
            color: #a0aec0;
        }

        .form-select {
            width: 100%;
            padding: 15px 18px;
            border: 2px solid #e2e8f0;
            border-radius: var(--border-radius);
            font-size: 1rem;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s ease;
            background: #fff;
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%23a0aec0' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 18px center;
            background-repeat: no-repeat;
            background-size: 16px;
            padding-right: 3rem;
        }

        .form-select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(0, 51, 102, 0.1);
        }

        .password-field {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #a0aec0;
            cursor: pointer;
            font-size: 1.1rem;
            transition: color 0.3s ease;
        }

        .password-toggle:hover {
            color: var(--primary-color);
        }

        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            opacity: 0;
            animation: slideInUp 0.6s ease forwards 1.4s;
        }

        .form-check {
            display: flex;
            align-items: center;
        }

        .form-check-input {
            width: 18px;
            height: 18px;
            margin-right: 8px;
            accent-color: var(--primary-color);
        }

        .form-check-label {
            font-size: 0.95rem;
            color: #555;
            cursor: pointer;
        }

        .forgot-link {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .forgot-link:hover {
            color: var(--secondary-color);
        }

        .btn-login {
            width: 100%;
            padding: 15px 30px;
            background: linear-gradient(135deg, var(--secondary-color), #b91c1c);
            color: var(--light-text-color);
            border: none;
            border-radius: var(--border-radius);
            font-size: 1.1rem;
            font-weight: 600;
            font-family: 'Poppins', sans-serif;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            position: relative;
            overflow: hidden;
            opacity: 0;
            animation: slideInUp 0.6s ease forwards 1.6s;
        }

        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .btn-login:hover::before {
            left: 100%;
        }

        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(209, 44, 44, 0.3);
        }

        .btn-login:active {
            transform: translateY(-1px);
        }

        .login-footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            opacity: 0;
            animation: slideInUp 0.6s ease forwards 1.8s;
        }

        .login-footer a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .login-footer a:hover {
            color: var(--secondary-color);
        }

        .error-message {
            background: #fee2e2;
            color: #dc2626;
            padding: 15px 20px;
            border-radius: var(--border-radius);
            margin-bottom: 25px;
            font-size: 0.95rem;
            text-align: center;
            border-left: 4px solid #dc2626;
        }

        /* Enhanced Responsive Design */
        @media (max-width: 1200px) {
            .login-container {
                max-width: 900px;
                gap: 40px;
            }
        }

        @media (max-width: 992px) {
            .login-container {
                flex-direction: column;
                text-align: center;
                gap: 30px;
                max-width: 600px;
                padding: 0 15px;
            }
            
            .login-image {
                order: 1;
                margin-bottom: 0;
            }
            
            .login-form-container {
                order: 2;
                max-width: 100%;
                width: 100%;
                padding: 40px 35px;
            }
            
            .login-header h1 {
                font-size: 2.2rem;
            }
        }

        @media (max-width: 768px) {
            .login-section {
                padding: 15px;
                min-height: 100vh;
            }
            
            .login-container {
                gap: 25px;
                padding: 0 10px;
            }
            
            .login-header h1 {
                font-size: 2rem;
            }
            
            .login-form-container {
                padding: 35px 25px;
                margin: 0 5px;
                border-radius: 10px;
            }
            
            .login-image img {
                border-radius: 10px;
                max-height: 300px;
                object-fit: cover;
            }
        }

        @media (max-width: 640px) {
            .login-container {
                flex-direction: column-reverse;
                gap: 20px;
            }
            
            .login-image {
                order: 2;
            }
            
            .login-form-container {
                order: 1;
            }
        }

        @media (max-width: 480px) {
            .login-section {
                padding: 10px;
                min-height: 100vh;
            }
            
            .login-form-container {
                padding: 25px 20px;
                margin: 0;
            }
            
            .login-header {
                margin-bottom: 30px;
            }
            
            .login-header h1 {
                font-size: 1.8rem;
            }
            
            .login-header .subtitle {
                font-size: 0.8rem;
            }
            
            .form-control, .form-select {
                padding: 12px 15px;
                font-size: 0.95rem;
            }
            
            .btn-login {
                padding: 12px 25px;
                font-size: 1rem;
            }
            
            .form-options {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
                margin-bottom: 25px;
            }
            
            .login-image {
                display: none;
            }
        }

        @media (max-width: 360px) {
            .login-form-container {
                padding: 20px 15px;
            }
            
            .login-header h1 {
                font-size: 1.6rem;
            }
            
            .form-control, .form-select {
                padding: 10px 12px;
            }
            
            .btn-login {
                padding: 10px 20px;
            }
        }

        /* Improved mobile landscape orientation */
        @media (max-height: 600px) and (orientation: landscape) {
            .login-section {
                padding: 15px;
                min-height: 100vh;
            }
            
            .login-container {
                flex-direction: row;
                max-width: 100%;
                gap: 30px;
            }
            
            .login-image {
                flex: 0.6;
            }
            
            .login-form-container {
                flex: 1;
                padding: 25px 30px;
            }
            
            .login-header {
                margin-bottom: 25px;
            }
            
            .login-header h1 {
                font-size: 1.8rem;
                margin-bottom: 10px;
            }
            
            .form-group {
                margin-bottom: 20px;
            }
        }

        /* Loading animation for button */
        .btn-login.loading {
            opacity: 0.8;
            pointer-events: none;
        }

        .btn-login.loading::after {
            content: '';
            width: 16px;
            height: 16px;
            border: 2px solid transparent;
            border-top: 2px solid var(--light-text-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-left: 10px;
        }

        /* @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        } */
    </style>
</head>
<body>


    <!-- Login Section -->
    <section class="login-section">
        <div class="login-container">
            <div class="login-image">
                <img src="images/high school.jpeg" alt="Solid Rock School Campus" onerror="this.onerror=null;this.src='https://placehold.co/600x400/f0f4f8/003366?text=School+Campus';">
            </div>
            
            <div class="login-form-container">
                <div class="login-header">
                    <p class="subtitle">Portal Access</p>
                    <h1>Welcome Back</h1>
                    <p>Sign in to access your personalized learning dashboard</p>
                </div>

                <?php if ($login_error): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo htmlspecialchars($login_error); ?>
                    </div>
                <?php endif; ?>

                <form action="authenticate.php" method="POST" id="loginForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    
                    <div class="form-group">
                        <label for="email" class="form-label">
                            <i class="fas fa-envelope me-2"></i>Email Address
                        </label>
                        <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email address" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password" class="form-label">
                            <i class="fas fa-lock me-2"></i>Password
                        </label>
                        <div class="password-field">
                            <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                            <button type="button" class="password-toggle" onclick="togglePassword()">
                                <i class="fas fa-eye" id="passwordIcon"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="role" class="form-label">
                            <i class="fas fa-user-tag me-2"></i>Select Role
                        </label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="" disabled selected>Choose your role</option>
                            <option value="student">Student</option>
                            <option value="staff">Staff</option>
                            <option value="admin">Admin</option>
                            <option value="parent">Parent</option>
                        </select>
                    </div>
                    
                    <div class="form-options">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="remember" name="remember">
                            <label class="form-check-label" for="remember">Remember me</label>
                        </div>
                        <a href="#" class="forgot-link">Forgot password?</a>
                    </div>
                    
                    <button type="submit" class="btn-login" id="loginBtn">
                        <i class="fas fa-sign-in-alt"></i>
                        Sign In to Portal
                    </button>
                </form>
            </div>
        </div>
    </section>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const passwordIcon = document.getElementById('passwordIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordIcon.classList.remove('fa-eye');
                passwordIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                passwordIcon.classList.remove('fa-eye-slash');
                passwordIcon.classList.add('fa-eye');
            }
        }

        // Add loading state to login button
        document.getElementById('loginForm').addEventListener('submit', function() {
            const loginBtn = document.getElementById('loginBtn');
            loginBtn.classList.add('loading');
            loginBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Signing In...';
        });

        // Enhanced form interactions and animations
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('loginForm');
            const inputs = form.querySelectorAll('.form-control, .form-select');
            
            // Add ripple effect to button
            const button = document.querySelector('.btn-login');
            button.addEventListener('click', function(e) {
                let ripple = document.createElement('span');
                let rect = this.getBoundingClientRect();
                let size = Math.max(rect.width, rect.height);
                let x = e.clientX - rect.left - size / 2;
                let y = e.clientY - rect.top - size / 2;
                
                ripple.style.cssText = `
                    width: ${size}px;
                    height: ${size}px;
                    left: ${x}px;
                    top: ${y}px;
                    position: absolute;
                    background: rgba(255,255,255,0.3);
                    border-radius: 50%;
                    transform: scale(0);
                    animation: ripple 0.6s linear;
                    pointer-events: none;
                `;
                
                this.appendChild(ripple);
                setTimeout(() => ripple.remove(), 600);
            });
            
            // Add focus/blur effects with enhanced animations
            inputs.forEach((input, index) => {
                input.addEventListener('focus', function() {
                    this.parentElement.style.transform = 'translateY(-2px)';
                    this.parentElement.style.transition = 'transform 0.3s ease';
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.style.transform = 'translateY(0)';
                });
            });

            // Add typing animation effect
            const title = document.querySelector('.login-header h1');
            const originalText = title.textContent;
            title.textContent = '';
            
            setTimeout(() => {
                let i = 0;
                const typeWriter = () => {
                    if (i < originalText.length) {
                        title.textContent += originalText.charAt(i);
                        i++;
                        setTimeout(typeWriter, 100);
                    }
                };
                typeWriter();
            }, 1000);
        });

        Add CSS for ripple effect
        const rippleStyle = document.createElement('style');
        rippleStyle.textContent = `
            @keyframes ripple {
                to {
                    transform: scale(4);
                    opacity: 0;
                }
            }
            
            .form-group.focused {
                transform: translateY(-2px);
            }
            
            .shake {
                animation: shake 0.5s ease-in-out;
            }
            
            @keyframes shake {
                0%, 20%, 40%, 60%, 80% {
                    transform: translateX(0);
                }
                10%, 30%, 50%, 70%, 90% {
                    transform: translateX(-5px);
                }
            }
        `;
        document.head.appendChild(rippleStyle);
    </script>
</body>
</html>