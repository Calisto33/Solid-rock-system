<?php
session_start();
include '../config.php';

// Check if the user is logged in as super admin
if (!isset($_SESSION['super_admin_id'])) {
    header("Location: super_admin_login.php");
    exit();
}

if (!isset($_GET['parent_id'])) {
    die("Parent ID is missing.");
}

$parent_id = $_GET['parent_id'];

// Fetch parent details
$parentQuery = "
    SELECT p.parent_id, p.user_id, p.phone_number, p.address, u.username, u.email, u.password 
    FROM parents p 
    JOIN users u ON p.user_id = u.id 
    WHERE p.parent_id = ?";
$stmt = $conn->prepare($parentQuery);
$stmt->bind_param("i", $parent_id);
$stmt->execute();
$parentResult = $stmt->get_result();
$parent = $parentResult->fetch_assoc();
$stmt->close();

if (!$parent) {
    die("Parent not found.");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'] ? password_hash($_POST['password'], PASSWORD_DEFAULT) : $parent['password'];
    $phone_number = $_POST['phone_number'];
    $address = $_POST['address'];

    // Update parent details
    $updateParentQuery = "UPDATE parents SET phone_number = ?, address = ? WHERE parent_id = ?";
    $stmt = $conn->prepare($updateParentQuery);
    $stmt->bind_param("ssi", $phone_number, $address, $parent_id);
    $stmt->execute();
    $stmt->close();

    // Update user details
    $user_id = $parent['user_id'];
    $updateUserQuery = "UPDATE users SET username = ?, email = ?, password = ? WHERE id = ?";
    $stmt = $conn->prepare($updateUserQuery);
    $stmt->bind_param("sssi", $username, $email, $password, $user_id);

    if ($stmt->execute()) {
        header("Location: manage_parents.php");
        exit();
    } else {
        die("Error updating parent: " . $stmt->error);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Parent | Solid Rock</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --secondary: #2b2d42;
            --accent: #00b4d8;
            --success: #06d6a0;
            --warning: #ffd166;
            --danger: #ef476f;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --light-gray: #e9ecef;
            --white: #ffffff;
            --shadow-sm: 0 2px 5px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.08);
            --shadow-lg: 0 8px 24px rgba(0,0,0,0.12);
            --border-radius: 12px;
            --transition: all 0.3s ease;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fb;
            color: var(--dark);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        /* Header styles */
        .header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: var(--white);
            padding: 1rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: var(--shadow-md);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .header-logo {
            height: 40px;
            width: 40px;
            object-fit: cover;
            border-radius: 8px;
        }
        
        .header-title {
            font-size: 1.3rem;
            font-weight: 600;
        }
        
        .header-right {
            display: flex;
            gap: 1rem;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.6rem 1.2rem;
            border-radius: 8px;
            border: none;
            font-weight: 500;
            transition: var(--transition);
            text-decoration: none;
            cursor: pointer;
            font-size: 0.95rem;
        }
        
        .btn-primary {
            background-color: var(--white);
            color: var(--primary);
        }
        
        .btn-primary:hover {
            background-color: rgba(255, 255, 255, 0.9);
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background-color: rgba(255, 255, 255, 0.15);
            color: var(--white);
            backdrop-filter: blur(10px);
        }
        
        .btn-secondary:hover {
            background-color: rgba(255, 255, 255, 0.25);
            transform: translateY(-2px);
        }
        
        /* Main content */
        .main {
            flex: 1;
            padding: 2rem;
            max-width: 900px;
            margin: 0 auto;
            width: 100%;
        }
        
        .card {
            background-color: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
            padding: 2rem;
            margin-bottom: 2rem;
            transition: var(--transition);
        }
        
        .card:hover {
            box-shadow: var(--shadow-lg);
        }
        
        .card-header {
            margin-bottom: 1.5rem;
        }
        
        .card-title {
            color: var(--secondary);
            font-size: 1.5rem;
            font-weight: 600;
            text-align: center;
            position: relative;
            padding-bottom: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .card-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: linear-gradient(to right, var(--primary), var(--accent));
            border-radius: 2px;
        }
        
        /* Form styles */
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--secondary);
        }
        
        .form-control {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid var(--light-gray);
            border-radius: 8px;
            font-size: 1rem;
            transition: var(--transition);
            background-color: var(--light);
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.15);
        }
        
        .input-icon-wrapper {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            top: 50%;
            left: 1rem;
            transform: translateY(-50%);
            color: var(--gray);
        }
        
        .input-with-icon {
            padding-left: 2.8rem;
        }
        
        .submit-btn {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: var(--white);
            padding: 0.8rem;
            width: 100%;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            margin-top: 1rem;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(67, 97, 238, 0.3);
        }
        
        /* Footer styles */
        .footer {
            background-color: var(--secondary);
            color: var(--white);
            padding: 1.5rem;
            text-align: center;
            margin-top: auto;
        }
        
        .footer-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
        }
        
        .footer-logo {
            height: 40px;
            border-radius: 8px;
        }
        
        .footer-text {
            font-size: 0.9rem;
            opacity: 0.8;
        }
        
        /* Responsive styles */
        @media (max-width: 768px) {
            .header {
                padding: 1rem;
                flex-direction: column;
                gap: 1rem;
            }
            
            .header-left, .header-right {
                width: 100%;
                justify-content: space-between;
            }
            
            .main {
                padding: 1.5rem;
            }
            
            .card {
                padding: 1.5rem;
            }
        }
        
        @media (max-width: 480px) {
            .header-title {
                font-size: 1.1rem;
            }
            
            .btn {
                padding: 0.5rem 0.8rem;
                font-size: 0.85rem;
            }
            
            .main {
                padding: 1rem;
            }
            
            .card {
                padding: 1.25rem;
                border-radius: 8px;
            }
            
            .card-title {
                font-size: 1.3rem;
            }
            
            .form-control {
                padding: 0.7rem;
            }
        }
    </style>
</head>

<body>
    <header class="header">
        <div class="header-left">
            <img src="../images/logo.jpeg" alt="Solid Rock Logo" class="header-logo">
            <h1 class="header-title">Edit Parent Profile</h1>
        </div>
        <div class="header-right">
            <a href="super_admin_dashboard.php" class="btn btn-secondary">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            <a href="../logout.php" class="btn btn-primary">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </header>

    <main class="main">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Update Parent Information</h2>
            </div>
            
            <form method="POST">
                <div class="form-group">
                    <label for="username" class="form-label">Username</label>
                    <div class="input-icon-wrapper">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" id="username" name="username" class="form-control input-with-icon" value="<?= htmlspecialchars($parent['username']) ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <div class="input-icon-wrapper">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" id="email" name="email" class="form-control input-with-icon" value="<?= htmlspecialchars($parent['email']) ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Password (leave blank to keep unchanged)</label>
                    <div class="input-icon-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" id="password" name="password" class="form-control input-with-icon">
                    </div>
                </div>

                <div class="form-group">
                    <label for="phone_number" class="form-label">Phone Number</label>
                    <div class="input-icon-wrapper">
                        <i class="fas fa-phone input-icon"></i>
                        <input type="text" id="phone_number" name="phone_number" class="form-control input-with-icon" value="<?= htmlspecialchars($parent['phone_number']) ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="address" class="form-label">Address</label>
                    <div class="input-icon-wrapper">
                        <i class="fas fa-home input-icon" style="top: 1rem;"></i>
                        <textarea id="address" name="address" class="form-control input-with-icon" rows="4"><?= htmlspecialchars($parent['address']) ?></textarea>
                    </div>
                </div>

                <button type="submit" class="submit-btn">
                    <i class="fas fa-save"></i> Update Parent Information
                </button>
            </form>
        </div>
    </main>

    <footer class="footer">
        <div class="footer-content">
            <img src="../images/logo.jpeg" alt="Solid Rock Logo" class="footer-logo">
            <p class="footer-text">&copy; <?php echo date("Y"); ?> Mirilax-Scales Portal. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>