<?php
session_start();
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Query to fetch super admin details
    $query = "SELECT * FROM super_admins WHERE username = ? AND status = 'active'";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $admin = $result->fetch_assoc();
        if (password_verify($password, $admin['password'])) {
            $_SESSION['super_admin_id'] = $admin['super_admin_id'];
            $_SESSION['username'] = $admin['username'];
            $_SESSION['email'] = $admin['email'];
            $_SESSION['role'] = 'superadmin';
            header("Location: super_admin/super_admin_dashboard.php");
            exit();
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "Invalid username or account inactive.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Login - Solid Rock Group of Schools</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/jpeg" href="images/logo.jpeg">
    <link rel="shortcut icon" type="image/jpeg" href="images/logo.jpeg">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    
    <style>
        /* Using the same variables as your brand */
        :root {
            --primary-color: #003366;
            --secondary-color: #d12c2c;
            --accent-color: #f0f4f8;
            --text-color: #333;
            --light-text-color: #fff;
            --border-radius: 12px;
            --box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
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

        /* Floating particles background */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23003366' fill-opacity='0.03'%3E%3Ccircle cx='30' cy='30' r='2'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E") repeat;
            animation: float 20s linear infinite;
            pointer-events: none;
            z-index: 1;
        }

        @keyframes float {
            0% { transform: translateY(0px) rotate(0deg); }
            100% { transform: translateY(-60px) rotate(360deg); }
        }

        /* Main container */
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            z-index: 2;
        }

        .login-card {
            display: flex;
            max-width: 900px;
            width: 100%;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        /* Left side - Branding */
        .login-brand {
            flex: 1;
            background: linear-gradient(135deg, var(--primary-color), #004080);
            padding: 3rem 2rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: white;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .login-brand::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Ccircle cx='30' cy='30' r='2'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E") repeat;
            animation: float 20s linear infinite;
        }

        .brand-content {
            position: relative;
            z-index: 2;
        }

        .brand-content h1 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 1rem;
            line-height: 1.3;
        }

        .brand-content p {
            font-size: 1rem;
            opacity: 0.9;
            line-height: 1.6;
            max-width: 300px;
        }

        /* Right side - Form */
        .login-form-container {
            flex: 1;
            padding: 3rem 2.5rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .form-header h2 {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .form-header p {
            color: #718096;
            font-size: 0.95rem;
        }

        .form-header .subtitle {
            font-weight: 600;
            color: var(--secondary-color);
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 0.8rem;
        }

        .error-message {
            background: #fee2e2;
            color: #dc2626;
            padding: 1rem 1.25rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            text-align: center;
            border-left: 4px solid #dc2626;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-color);
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #a0aec0;
            font-size: 1.1rem;
        }

        .form-control {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            border: 2px solid #e2e8f0;
            border-radius: var(--border-radius);
            font-size: 1rem;
            font-family: 'Poppins', sans-serif;
            transition: var(--transition);
            background: rgba(255, 255, 255, 0.8);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(0, 51, 102, 0.1);
            background: #fff;
        }

        .form-control::placeholder {
            color: #a0aec0;
        }

        .forgot-link {
            display: block;
            text-align: right;
            margin-top: 0.5rem;
            font-size: 0.9rem;
            color: var(--primary-color);
            text-decoration: none;
            transition: var(--transition);
        }

        .forgot-link:hover {
            color: var(--secondary-color);
        }

        .btn-login {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, var(--secondary-color), #b91c1c);
            color: var(--light-text-color);
            border: none;
            border-radius: var(--border-radius);
            font-size: 1.1rem;
            font-weight: 600;
            font-family: 'Poppins', sans-serif;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin: 2rem 0 1.5rem;
            position: relative;
            overflow: hidden;
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

        .login-footer {
            text-align: center;
            margin-top: 1.5rem;
        }

        .login-footer a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            transition: var(--transition);
        }

        .login-footer a:hover {
            color: var(--secondary-color);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .login-card {
                flex-direction: column;
                max-width: 400px;
            }
            
            .login-brand {
                padding: 2rem 1.5rem;
                min-height: 150px;
            }
            
            .brand-content h1 {
                font-size: 1.5rem;
            }
            
            .login-form-container {
                padding: 2rem 1.5rem;
            }
            
            .form-header {
                margin-bottom: 2rem;
            }
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 10px;
            }
            
            .login-form-container {
                padding: 1.5rem 1rem;
            }
            
            .login-brand {
                padding: 1.5rem 1rem;
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

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <!-- Left side - Branding -->
            <div class="login-brand">
                <div class="brand-content">
                    <h1>Solid Rock Group of Schools</h1>
                    <p>Super Administrator Portal - Manage your educational institution with advanced administrative controls</p>
                </div>
            </div>

            <!-- Right side - Login Form -->
            <div class="login-form-container">
                <div class="form-header">
                    <p class="subtitle">Super Administrator</p>
                    <h2>Welcome Back</h2>
                    <p>Please enter your credentials to access the super admin panel</p>
                </div>

                <?php if (isset($error)): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i>
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" id="loginForm">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <div class="input-wrapper">
                            <i class="fas fa-user-shield input-icon"></i>
                            <input type="text" id="username" name="username" class="form-control" placeholder="Enter your username" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" required>
                        </div>
                        <a href="#" class="forgot-link">Forgot password?</a>
                    </div>

                    <button type="submit" class="btn-login" id="loginBtn">
                        <i class="fas fa-sign-in-alt"></i>
                        Access Super Admin Panel
                    </button>
                </form>

                <div class="login-footer">
                    <a href="index.php">
                        <i class="fas fa-arrow-left"></i>
                        Back to Main Portal
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Add loading state to login button
        document.getElementById('loginForm').addEventListener('submit', function() {
            const loginBtn = document.getElementById('loginBtn');
            loginBtn.classList.add('loading');
            loginBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Authenticating...';
        });

        // Enhanced form interactions
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('.form-control');
            
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.parentElement.style.transform = 'translateY(-2px)';
                    this.parentElement.parentElement.style.transition = 'transform 0.3s ease';
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.parentElement.style.transform = 'translateY(0)';
                });
            });

            // Auto-focus first input
            document.getElementById('username').focus();
        });
    </script>
</body>
</html>