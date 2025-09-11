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
    <title>Post News | Wisetech</title>
    <link rel="icon" type="image/jpeg" href="../images/logo.jpeg">
    <link rel="shortcut icon" type="image/jpeg" href="../images/logo.jpeg">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4A90E2;
            --primary-dark: #3771C8;
            --background-color: #F8F9FA;
            --card-background: #FFFFFF;
            --text-color: #333333;
            --text-light: #666666;
            --border-color: #E0E0E0;
            --shadow-light: 0 4px 12px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
            --border-radius: 12px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
            line-height: 1.6;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .header {
            background-color: var(--card-background);
            padding: 1.5rem 2.5rem;
            box-shadow: var(--shadow-light);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1.5rem;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .logo {
            height: 40px; /* Adjust as needed */
            width: auto;
        }

        .header h2 {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0;
        }

        .nav-link {
            padding: 0.75rem 1.5rem;
            border: 1px solid var(--primary-color);
            color: var(--primary-color);
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: var(--transition);
        }

        .nav-link:hover {
            background-color: var(--primary-color);
            color: var(--card-background);
        }

        .container {
            flex-grow: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem;
        }

        .card {
            background-color: var(--card-background);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-light);
            padding: 2.5rem;
            width: 100%;
            max-width: 500px;
            text-align: center;
            transition: var(--transition);
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }

        .card h3 {
            font-size: 1.75rem;
            color: var(--primary-dark);
            margin-bottom: 2rem;
            position: relative;
        }

        .card h3::after {
            content: '';
            position: absolute;
            left: 50%;
            bottom: -10px;
            transform: translateX(-50%);
            width: 60px;
            height: 4px;
            background-color: var(--primary-color);
            border-radius: 2px;
        }

        .form-group {
            text-align: left;
            margin-bottom: 2rem;
        }

        label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.75rem;
        }

        select {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 1rem;
            background-color: var(--background-color);
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            cursor: pointer;
            transition: border-color 0.3s;
        }

        select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.2);
        }

        .submit-btn {
            width: 100%;
            padding: 1rem;
            background-color: var(--primary-color);
            color: var(--card-background);
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
        }

        .submit-btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .footer {
            background-color: var(--card-background);
            padding: 1.5rem;
            text-align: center;
            color: var(--text-light);
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.05);
            margin-top: auto;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
                padding: 1.5rem;
            }

            .header-left {
                width: 100%;
                justify-content: center;
                margin-bottom: 1rem;
            }

            .header h2 {
                font-size: 1.2rem;
                text-align: center;
            }

            .nav-link {
                width: 100%;
                text-align: center;
            }

            .container {
                padding: 1rem;
            }

            .card {
                padding: 2rem 1.5rem;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-left">
            <img src="../images/logo.jpeg" alt="Wisetech College Logo" class="logo">
            <h2>Mirilax Portal</h2>
        </div>
        <a href="admin_home.php" class="nav-link">Dashboard</a>
    </header>

    <div class="container">
        <div class="card">
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

                <button type="submit" class="submit-btn">
                    Continue to News Editor
                </button>
            </form>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; <?php echo date("Y"); ?> Mirilax-Scales| Admin Portal System</p>
    </footer>
</body>
</html>