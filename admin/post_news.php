<?php
session_start();
include '../config.php'; // Database connection

// Check if the user is an admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

// Handle form submission to choose audience
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $audience = $_POST['audience'];
    
    // Redirect to send_news.php with the audience as a query parameter
    header("Location: send_news.php?audience=" . urlencode($audience));
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post News | Wisetech College</title>
    <style>
        :root {
            --primary-color: #2563eb;
            --primary-dark: #1d4ed8;
            --primary-light: #3b82f6;
            --secondary-color: #7c3aed;
            --accent-color: #4f46e5;
            --text-color: #1f2937;
            --text-light: #4b5563;
            --white: #fff;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --radius: 0.5rem;
            --radius-lg: 1rem;
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: var(--gray-50);
            color: var(--text-color);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            line-height: 1.5;
        }

        .header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
            color: var(--white);
            padding: 1.25rem 2rem;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: var(--shadow);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .header h2 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
            letter-spacing: -0.025em;
        }

        .header-actions {
            display: flex;
            gap: 0.75rem;
        }

        .container {
            max-width: 650px;
            width: 90%;
            margin: 2.5rem auto;
            flex: 1;
        }

        .content-box {
            background: var(--white);
            padding: 2.5rem;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            transition: var(--transition);
        }

        .content-box:hover {
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            transform: translateY(-2px);
        }

        h3 {
            color: var(--primary-dark);
            margin-bottom: 1.75rem;
            text-align: center;
            font-size: 1.5rem;
            font-weight: 700;
            position: relative;
            padding-bottom: 0.75rem;
        }

        h3::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 4rem;
            height: 3px;
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            border-radius: 999px;
        }

        .form-group {
            margin-bottom: 1.75rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-color);
            font-weight: 600;
            font-size: 0.95rem;
        }

        select {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 1px solid var(--gray-300);
            border-radius: var(--radius);
            background-color: var(--white);
            font-size: 1rem;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: var(--transition);
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%23666666'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1rem;
        }

        select:focus {
            outline: none;
            border-color: var(--primary-light);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
        }

        select:hover {
            border-color: var(--primary-light);
        }

        button {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
            color: var(--white);
            padding: 0.875rem 1.25rem;
            border: none;
            border-radius: var(--radius);
            cursor: pointer;
            width: 100%;
            font-size: 1rem;
            font-weight: 600;
            transition: var(--transition);
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            box-shadow: var(--shadow-sm);
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
            filter: brightness(1.1);
        }

        button:active {
            transform: translateY(0);
        }

        .link-btn {
            display: inline-block;
            padding: 0.625rem 1.25rem;
            color: var(--white);
            background-color: rgba(255, 255, 255, 0.2);
            text-decoration: none;
            border-radius: var(--radius);
            transition: var(--transition);
            font-weight: 500;
            font-size: 0.95rem;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .link-btn:hover {
            background-color: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }

        .message {
            color: #0f5132;
            background-color: #d1e7dd;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: var(--radius);
            text-align: center;
            border-left: 4px solid #0f5132;
        }

        .error {
            color: #842029;
            background-color: #f8d7da;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: var(--radius);
            text-align: center;
            border-left: 4px solid #842029;
        }

        .footer {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--accent-color) 100%);
            color: var(--white);
            text-align: center;
            padding: 1.5rem;
            margin-top: auto;
            font-size: 0.95rem;
        }

        /* Animate form elements on page load */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .content-box {
            animation: fadeInUp 0.5s ease-out forwards;
        }

        /* Icon styles */
        .icon {
            display: inline-block;
            width: 1.25rem;
            height: 1.25rem;
            stroke-width: 0;
            stroke: currentColor;
            fill: currentColor;
            vertical-align: middle;
        }

        @media screen and (max-width: 768px) {
            .header {
                padding: 1rem 1.5rem;
            }

            .container {
                width: 92%;
                margin: 1.5rem auto;
            }

            .content-box {
                padding: 2rem;
            }
        }

        @media screen and (max-width: 480px) {
            .header {
                flex-direction: column;
                align-items: center;
                padding: 1rem;
                gap: 0.75rem;
            }

            .container {
                width: 95%;
                margin: 1rem auto;
            }

            .content-box {
                padding: 1.5rem;
                border-radius: var(--radius);
            }

            h3 {
                font-size: 1.25rem;
            }

            label {
                font-size: 0.9rem;
            }

            select {
                padding: 0.75rem;
            }

            button, .link-btn {
                padding: 0.75rem 1rem;
                font-size: 0.95rem;
            }

            .footer {
                padding: 1rem;
                font-size: 0.85rem;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <h2>
            <svg class="icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 2L2 7L12 12L22 7L12 2Z"></path>
                <path d="M2 17L12 22L22 17"></path>
                <path d="M2 12L12 17L22 12"></path>
            </svg>
            Wisetech Admin Portal
        </h2>
        <div class="header-actions">
            <a href="admin_home.php" class="link-btn">
                <svg class="icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M3 12L5 10M5 10L12 3L19 10M5 10V20C5 20.5523 5.44772 21 6 21H9M19 10L21 12M19 10V20C19 20.5523 18.5523 21 18 21H15M9 21C9.55228 21 10 20.5523 10 20V16C10 15.4477 10.4477 15 11 15H13C13.5523 15 14 15.4477 14 16V20C14 20.5523 14.4477 21 15 21M9 21H15"></path>
                </svg>
                Dashboard
            </a>
        </div>
    </header>

    <div class="container">
        <div class="content-box">
            <h3>Select Target Audience</h3>
            
            <form action="post_news.php" method="POST">
                <div class="form-group">
                    <label for="audience">Choose who will receive this news:</label>
                    <select name="audience" id="audience" required>
                        <option value="">-- Select Audience --</option>
                        <option value="students">Students</option>
                        <option value="parents">Parents</option>
                        <option value="staff">Staff</option>
                        <option value="all">All Users</option>
                    </select>
                </div>

                <button type="submit">
                    <svg class="icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 5V19M12 5L5 12M12 5L19 12"></path>
                    </svg>
                    Continue to News Editor
                </button>
            </form>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; <?php echo date("Y"); ?> Wisetech College | Admin Portal System</p>
    </footer>
</body>
</html>