<?php
session_start();
include '../config.php';

// Check if the user is logged in as super admin
if (!isset($_SESSION['super_admin_id'])) {
    header("Location: super_admin_login.php");
    exit();
}

// Check if admin ID is provided
if (!isset($_GET['admin_id'])) {
    die("Admin ID is missing.");
}

$admin_id = $_GET['admin_id'];

// Fetch admin details from the users table
$adminQuery = "SELECT id, username, email, password FROM users WHERE id = ? AND role = 'admin'";
$stmt = $conn->prepare($adminQuery);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$adminResult = $stmt->get_result();
$admin = $adminResult->fetch_assoc();
$stmt->close();

if (!$admin) {
    die("Admin not found.");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'] ? password_hash($_POST['password'], PASSWORD_DEFAULT) : $admin['password'];

    // Update the users table
    $updateAdminQuery = "UPDATE users SET username = ?, email = ?, password = ? WHERE id = ?";
    $stmt = $conn->prepare($updateAdminQuery);
    $stmt->bind_param("sssi", $username, $email, $password, $admin_id);

    if ($stmt->execute()) {
        header("Location: manage_admins.php");
        exit();
    } else {
        die("Error updating admin: " . $stmt->error);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --primary-light: #4895ef;
            --primary-dark: #3a0ca3;
            --secondary-color: #4cc9f0;
            --text-color: #2b2d42;
            --text-light: #8d99ae;
            --background: #f8f9fa;
            --white: #ffffff;
            --danger: #ef476f;
            --success: #06d6a0;
            --border-radius: 12px;
            --shadow-sm: 0 4px 6px rgba(0, 0, 0, 0.05);
            --shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--background);
            color: var(--text-color);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            line-height: 1.6;
        }

        .header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: var(--white);
            padding: 1rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: var(--shadow);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .header-left img {
            height: 40px;
            width: auto;
            border-radius: 8px;
            box-shadow: var(--shadow-sm);
        }

        .header h1 {
            font-size: 1.5rem;
            font-weight: 600;
        }

        .header-buttons {
            display: flex;
            gap: 1rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 1.2rem;
            border-radius: var(--border-radius);
            font-weight: 500;
            text-decoration: none;
            transition: var(--transition);
            cursor: pointer;
            border: none;
            font-size: 0.9rem;
        }

        .btn-primary {
            background-color: var(--primary-light);
            color: var(--white);
        }

        .btn-primary:hover {
            background-color: var(--primary-color);
            transform: translateY(-2px);
        }

        .btn-outline {
            background-color: transparent;
            color: var(--white);
            border: 1px solid var(--white); 
        }

        .btn-outline:hover {
            background-color: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
        }

        .btn-danger {
            background-color: var(--danger);
            color: var(--white);
        }

        .btn-danger:hover {
            background-color: #d64161;
            transform: translateY(-2px);
        }

        .container {
            max-width: 700px;
            width: 90%;
            margin: 2rem auto;
            padding: 0;
            flex: 1;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .card {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(to right, var(--primary-color), var(--primary-light));
            color: var(--white);
            padding: 1.5rem;
            position: relative;
        }

        .card-header h2 {
            font-size: 1.5rem;
            margin: 0;
            font-weight: 600;
        }

        .card-header::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 5px;
            background-color: var(--secondary-color);
            border-radius: 10px;
        }

        .card-body {
            padding: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-group:last-child {
            margin-bottom: 0;
        }

        .form-group i {
            position: absolute;
            left: 1rem;
            top: 2.8rem;
            color: var(--text-light);
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-color);
            font-weight: 500;
        }

        input, select {
            width: 100%;
            padding: 0.8rem 1rem 0.8rem 2.5rem;
            border: 1px solid #e0e0e0;
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: var(--transition);
            background-color: #f8f9fa;
        }

        input:focus, select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.15);
            background-color: var(--white);
        }

        button {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(to right, var(--primary-color), var(--primary-light));
            color: var(--white);
            border: none;
            border-radius: var(--border-radius);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        button:hover {
            background: linear-gradient(to right, var(--primary-dark), var(--primary-color));
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .footer {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-color));
            color: var(--white);
            text-align: center;
            padding: 1.5rem 0;
            margin-top: 2rem;
        }

        .footer-content {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .footer img {
            height: 35px;
            border-radius: 6px;
        }

        .footer p {
            margin: 0;
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .menu-toggle {
            display: none;
            background: transparent;
            border: none;
            color: var(--white);
            font-size: 1.5rem;
            cursor: pointer;
        }

        /* Responsive Design */
        @media screen and (max-width: 768px) {
            .header {
                padding: 1rem;
            }

            .container {
                width: 95%;
                margin: 1.5rem auto;
            }

            .card-body {
                padding: 1.5rem;
            }
        }

        @media screen and (max-width: 576px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
                padding: 1rem;
            }

            .header-left {
                width: 100%;
                justify-content: space-between;
            }

            .header-buttons {
                display: none;
                flex-direction: column;
                width: 100%;
                margin-top: 1rem;
            }

            .header-buttons.active {
                display: flex;
            }

            .menu-toggle {
                display: block;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }

            .card-header, .card-body {
                padding: 1.2rem;
            }

            .footer-content {
                flex-direction: column;
                gap: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-left">
            <img src="../images/logo.jpeg" alt="Solid Rock   Logo">
            <h1>Edit Admin</h1>
            <button class="menu-toggle" id="menuToggle">
                <i class="fas fa-bars"></i>
            </button>
        </div>
        <div class="header-buttons" id="headerButtons">
            <a href="super_admin_dashboard.php" class="btn btn-outline">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="../logout.php" class="btn btn-danger">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </header>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2>Edit Admin Details</h2>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <i class="fas fa-user"></i>
                        <input type="text" id="username" name="username" value="<?= htmlspecialchars($admin['username']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($admin['email']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Password (leave blank to keep unchanged)</label>
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password">
                    </div>

                    <button type="submit">
                        <i class="fas fa-save"></i> Update Admin
                    </button>
                </form>
            </div>
        </div>
    </div>

    <footer class="footer">
        <div class="footer-content">
            <img src="../images/logo.jpeg" alt="Solid Rock  Logo">
            <p>&copy; <?php echo date("Y"); ?> Mirilax-Scales Portal. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // Mobile menu toggle
        document.getElementById('menuToggle').addEventListener('click', function() {
            document.getElementById('headerButtons').classList.toggle('active');
        });

        // Form animations
        const inputs = document.querySelectorAll('input');
        inputs.forEach(input => {
            input.addEventListener('focus', () => {
                input.parentElement.querySelector('i').style.color = 'var(--primary-color)';
            });
            
            input.addEventListener('blur', () => {
                input.parentElement.querySelector('i').style.color = 'var(--text-light)';
            });
        });
    </script>
</body>
</html>