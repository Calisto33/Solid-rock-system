<?php
session_start();
include '../config.php'; // Database connection

// Check if the user is an admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

$audience = $_GET['audience'] ?? null;

// Check if the audience is valid
if (!in_array($audience, ['students', 'parents', 'staff', 'all'])) {
    die("Invalid audience selected.");
}

// Handle form submission to post the news
$message = "";
$message_type = ""; // 'success' or 'error'
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $news_content = $_POST['news_content'];

    // Insert the news into the `news` table with the selected audience
    $insertNewsQuery = "INSERT INTO news (audience, news_content) VALUES (?, ?)";
    $stmt = $conn->prepare($insertNewsQuery);
    $stmt->bind_param("ss", $audience, $news_content);

    if ($stmt->execute()) {
        $message = "News posted successfully for " . ucfirst($audience) . ".";
        $message_type = "success";
    } else {
        $message = "Error posting news: " . $conn->error;
        $message_type = "error";
    }
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
            --success-bg: #EAF7E8;
            --success-text: #4CAF50;
            --error-bg: #F8E8E8;
            --error-text: #F44336;
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
            flex-direction: column;
            align-items: center;
            padding: 2rem;
        }

        .card {
            background-color: var(--card-background);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-light);
            padding: 2.5rem;
            width: 100%;
            max-width: 700px;
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

        .message {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 1rem;
            text-align: left;
        }

        .message.success {
            background-color: var(--success-bg);
            color: var(--success-text);
        }
        
        .message.error {
            background-color: var(--error-bg);
            color: var(--error-text);
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

        textarea {
            width: 100%;
            min-height: 250px;
            padding: 1rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 1rem;
            background-color: var(--background-color);
            resize: vertical;
            transition: border-color 0.3s, box-shadow 0.3s;
            font-family: 'Poppins', sans-serif;
        }
        
        textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.2);
            background-color: var(--card-background);
        }

        .button-group {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            justify-content: center;
        }

        .button-group button {
            flex: 1;
            padding: 1rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .primary-btn {
            background-color: var(--primary-color);
            color: var(--card-background);
        }
        
        .primary-btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .secondary-btn {
            background-color: var(--card-background);
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
        }
        
        .secondary-btn:hover {
            background-color: var(--primary-color);
            color: var(--card-background);
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
            .button-group button {
                flex-basis: 100%;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-left">
            <img src="../images/logo.jpeg" alt="Wisetech College Logo" class="logo">
            <h2>Wisetech Admin Portal</h2>
        </div>
        <a href="admin_home.php" class="nav-link">Dashboard</a>
    </header>

    <div class="container">
        <div class="card">
            <h3>Compose News for <?= ucfirst($audience) ?></h3>
            
            <?php if ($message): ?>
                <div class="message <?= $message_type ?>">
                    <?= $message ?>
                </div>
            <?php endif; ?>

            <form action="send_news.php?audience=<?= urlencode($audience) ?>" method="POST">
                <div class="form-group">
                    <label for="news_content">News Content:</label>
                    <textarea 
                        name="news_content" 
                        id="news_content" 
                        placeholder="Write your news announcement here..." 
                        required
                    ></textarea>
                </div>
                
                <div class="button-group">
                    <button type="submit" class="primary-btn">
                        Post News
                    </button>
                    <button type="button" class="secondary-btn" onclick="window.location.href='post_news.php'">
                        Change Audience
                    </button>
                </div>
            </form>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; <?php echo date("Y"); ?> Mirilax-Scales Portal | Admin System</p>
    </footer>
</body>
</html>