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
if (!in_array($audience, ['students', 'parents', 'staff'])) {
    die("Invalid audience selected.");
}

// Handle form submission to post the news
$message = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $news_content = $_POST['news_content'];

    // Insert the news into the `news` table with the selected audience
    $insertNewsQuery = "INSERT INTO news (audience, news_content) VALUES (?, ?)";
    $stmt = $conn->prepare($insertNewsQuery);
    $stmt->bind_param("ss", $audience, $news_content);

    if ($stmt->execute()) {
        $message = "News posted successfully for $audience.";
    } else {
        $message = "Error posting news: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send News</title>
    <style>
        :root {
            --primary-color: #2563eb;
            --primary-dark: #1d4ed8;
            --primary-light: #60a5fa;
            --accent-color: #818cf8;
            --text-color: #1f2937;
            --text-light: #6b7280;
            --white: #ffffff;
            --light-bg: #f9fafb;
            --card-bg: #ffffff;
            --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --radius-sm: 0.25rem;
            --radius-md: 0.5rem;
            --radius-lg: 1rem;
            --transition: all 0.3s ease;
            --success-bg: #ecfdf5;
            --success-text: #047857;
            --error-bg: #fef2f2;
            --error-text: #b91c1c;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--light-bg);
            color: var(--text-color);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            padding: 1.5rem 2rem;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: var(--shadow-md);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .header h2 {
            color: var(--white);
            font-weight: 600;
            font-size: 1.5rem;
            margin: 0;
        }

        .nav-links {
            display: flex;
            gap: 1rem;
        }

        .link-btn {
            display: inline-flex;
            align-items: center;
            background-color: rgba(255, 255, 255, 0.15);
            color: var(--white);
            padding: 0.6rem 1.2rem;
            border-radius: var(--radius-md);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            border: 1px solid rgba(255, 255, 255, 0.1);
            gap: 0.5rem;
        }

        .link-btn:hover {
            background-color: rgba(255, 255, 255, 0.25);
            transform: translateY(-2px);
        }

        .link-btn:active {
            transform: translateY(0);
        }

        .link-btn svg {
            width: 1.2rem;
            height: 1.2rem;
        }

        .container {
            max-width: 900px;
            width: 100%;
            margin: 2rem auto;
            padding: 0 1.5rem;
            flex: 1;
        }

        .page-title {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: var(--primary-dark);
            text-align: center;
        }

        .content-box {
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            padding: 2.5rem;
            transition: var(--transition);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .content-box:hover {
            box-shadow: var(--shadow-lg), 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .message {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            background-color: var(--success-bg);
            color: var(--success-text);
            font-weight: 500;
        }

        .message svg {
            width: 1.5rem;
            height: 1.5rem;
            flex-shrink: 0;
        }

        .error {
            background-color: var(--error-bg);
            color: var(--error-text);
        }

        .form-group {
            margin-bottom: 1.75rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-color);
            font-size: 1rem;
        }

        textarea {
            width: 100%;
            min-height: 200px;
            padding: 1rem;
            border: 1px solid #e5e7eb;
            border-radius: var(--radius-md);
            background-color: #f9fafb;
            color: var(--text-color);
            font-size: 1rem;
            transition: var(--transition);
            resize: vertical;
            font-family: inherit;
        }

        textarea:focus {
            outline: none;
            border-color: var(--primary-light);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.2);
            background-color: var(--white);
        }

        .btn-row {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        button {
            flex: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background-color: var(--primary-color);
            color: var(--white);
            padding: 0.875rem 1.5rem;
            border: none;
            border-radius: var(--radius-md);
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: var(--transition);
            gap: 0.5rem;
        }

        button svg {
            width: 1.25rem;
            height: 1.25rem;
        }

        button:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        button:active {
            transform: translateY(0);
            box-shadow: var(--shadow-sm);
        }

        .btn-secondary {
            background-color: var(--white);
            color: var(--text-color);
            border: 1px solid #e5e7eb;
        }

        .btn-secondary:hover {
            background-color: #f3f4f6;
        }

        .footer {
            background-color: var(--text-color);
            color: var(--white);
            text-align: center;
            padding: 1.5rem;
            margin-top: 2rem;
        }

        @media screen and (max-width: 768px) {
            .container {
                margin: 1.5rem auto;
            }
            
            .content-box {
                padding: 1.75rem;
            }
            
            .header {
                padding: 1.25rem 1.5rem;
            }
            
            .btn-row {
                flex-direction: column;
            }
        }

        @media screen and (max-width: 480px) {
            .header {
                padding: 1rem;
                flex-direction: column;
                align-items: flex-start;
            }
            
            .nav-links {
                width: 100%;
                justify-content: flex-end;
            }
            
            .container {
                padding: 0 1rem;
                margin: 1rem auto;
            }
            
            .content-box {
                padding: 1.25rem;
                border-radius: var(--radius-md);
            }
            
            .page-title {
                font-size: 1.5rem;
                margin-bottom: 1rem;
            }
            
            textarea {
                min-height: 150px;
                padding: 0.75rem;
            }
            
            button {
                padding: 0.75rem 1rem;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <h2>Send News to <?= ucfirst($audience) ?></h2>
        <div class="nav-links">
            <a href="admin_home.php" class="link-btn">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
                Dashboard
            </a>
        </div>
    </header>

    <div class="container">
        <h1 class="page-title">Compose News for <?= ucfirst($audience) ?></h1>
        
        <div class="content-box">
            <?php if ($message): ?>
                <div class="message">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
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

                <div class="btn-row">
                    <button type="submit">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="22" y1="2" x2="11" y2="13"></line>
                            <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                        </svg>
                        Post News
                    </button>
                    <button type="button" class="btn-secondary" onclick="window.location.href='admin_home.php'">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; <?php echo date("Y"); ?> Wisetech College Portal • Admin System</p>
    </footer>
</body>
</html>