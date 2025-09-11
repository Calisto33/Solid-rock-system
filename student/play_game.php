<?php
session_start();
include '../config.php'; // Database connection

// Check if the user is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'student') {
    header("Location: ../login.php");
    exit();
}

// Check if game_id is provided
if (!isset($_GET['game_id'])) {
    die("Game ID is missing.");
}

$game_id = $_GET['game_id'];

// Fetch game details
$gameQuery = "SELECT * FROM educational_games WHERE game_id = ?";
$stmt = $conn->prepare($gameQuery);
$stmt->bind_param("i", $game_id);
$stmt->execute();
$result = $stmt->get_result();
$game = $result->fetch_assoc();

if (!$game) {
    die("Game not found.");
}

// Increment access count
$incrementQuery = "UPDATE educational_games SET access_count = access_count + 1 WHERE game_id = ?";
$stmt = $conn->prepare($incrementQuery);
$stmt->bind_param("i", $game_id);
$stmt->execute();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loading Game - <?= htmlspecialchars($game['game_name']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3a0ca3;
            --accent-color: #7209b7;
            --light-bg: #f8f9fa;
            --dark-bg: #212529;
            --white: #ffffff;
            --text-primary: #2b2d42;
            --text-secondary: #6c757d;
            --success: #4cc9f0;
            --shadow-sm: 0 4px 6px rgba(0, 0, 0, 0.05);
            --shadow-md: 0 10px 15px rgba(67, 97, 238, 0.1);
            --radius: 12px;
            --transition: all 0.3s ease-in-out;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            line-height: 1.6;
        }

        .container {
            background: var(--white);
            border-radius: var(--radius);
            box-shadow: var(--shadow-md);
            width: 100%;
            max-width: 500px;
            overflow: hidden;
            position: relative;
            transform: translateY(0);
            transition: var(--transition);
        }

        .container:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 25px rgba(67, 97, 238, 0.15);
        }

        .header {
            background: linear-gradient(to right, var(--primary-color), var(--accent-color));
            color: var(--white);
            padding: 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: "";
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 70%);
            transform: rotate(45deg);
        }

        h1 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 1;
        }

        .game-info {
            padding: 2rem;
            text-align: center;
        }

        h2 {
            color: var(--secondary-color);
            font-size: 1.6rem;
            margin-bottom: 1rem;
            font-weight: 600;
        }

        p {
            color: var(--text-secondary);
            margin-bottom: 1.5rem;
            font-size: 1rem;
        }

        .loading-area {
            padding: 0.5rem 2rem 2rem;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .progress-container {
            width: 100%;
            background-color: #e9ecef;
            border-radius: 50px;
            height: 12px;
            margin-bottom: 1.5rem;
            overflow: hidden;
            position: relative;
        }

        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
            border-radius: 50px;
            width: 0%;
            transition: width 2s ease-in-out;
            position: relative;
            overflow: hidden;
        }

        .progress-bar::after {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, 
                rgba(255,255,255,0) 0%,
                rgba(255,255,255,0.3) 50%,
                rgba(255,255,255,0) 100%);
            animation: shine 1.5s infinite;
        }

        .loading-icon {
            display: flex;
            justify-content: center;
            margin-bottom: 1.5rem;
        }

        .loading-icon i {
            font-size: 2rem;
            color: var(--primary-color);
            animation: pulse 1.5s infinite ease-in-out;
        }

        .message {
            font-weight: 500;
            color: var(--text-primary);
            margin-bottom: 2rem;
        }

        .actions {
            display: flex;
            justify-content: center;
        }

        .cancel-btn {
            display: inline-block;
            padding: 0.8rem 2rem;
            background: var(--white);
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            transition: var(--transition);
            font-size: 1rem;
        }

        .cancel-btn:hover {
            background: var(--primary-color);
            color: var(--white);
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
        }

        @keyframes shine {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }

        /* Responsive Design */
        @media screen and (max-width: 768px) {
            .container {
                max-width: 450px;
            }
        }

        @media screen and (max-width: 480px) {
            .header, .game-info, .loading-area {
                padding: 1.5rem;
            }
            
            h1 {
                font-size: 1.5rem;
            }
            
            h2 {
                font-size: 1.3rem;
            }
            
            .loading-icon i {
                font-size: 1.8rem;
            }
            
            .cancel-btn {
                padding: 0.7rem 1.8rem;
                font-size: 0.95rem;
            }
        }

        @media screen and (max-width: 380px) {
            .header, .game-info, .loading-area {
                padding: 1.2rem;
            }
            
            h1 {
                font-size: 1.3rem;
            }
            
            h2 {
                font-size: 1.1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Loading Game</h1>
        </div>
        
        <div class="game-info">
            <h2><?= htmlspecialchars($game['game_name']) ?></h2>
            <p><?= htmlspecialchars($game['game_description']) ?></p>
        </div>
        
        <div class="loading-area">
            <div class="loading-icon">
                <i class="fas fa-gamepad"></i>
            </div>
            
            <div class="progress-container">
                <div class="progress-bar" id="progressBar"></div>
            </div>
            
            <p class="message">Getting your game ready...</p>
            
            <div class="actions">
                <a href="educational_games.php" class="cancel-btn">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </div>
    </div>

    <script>
        // Animate the progress bar
        let progress = 0;
        const progressBar = document.getElementById('progressBar');
        
        const interval = setInterval(function() {
            progress += 5;
            progressBar.style.width = progress + '%';
            
            if (progress >= 100) {
                clearInterval(interval);
                // Redirect to game
                window.location.href = "<?= htmlspecialchars($game['game_link']) ?>";
            }
        }, 100);
        
        // Ensure redirection happens even if animation fails
        setTimeout(function() {
            window.location.href = "<?= htmlspecialchars($game['game_link']) ?>";
        }, 2000);
    </script>
</body>
</html>

<?php $stmt->close(); $conn->close(); ?>
