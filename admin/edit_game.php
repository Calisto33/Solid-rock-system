<?php
session_start();
include '../config.php'; // Database connection

// Check if the user is logged in as an admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
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

// Handle form submission for updating the game
$message = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_game'])) {
    $game_name = $_POST['game_name'];
    $game_description = $_POST['game_description'];
    $game_link = $_POST['game_link'];

    $updateGameQuery = "UPDATE educational_games SET game_name = ?, game_description = ?, game_link = ? WHERE game_id = ?";
    $stmt = $conn->prepare($updateGameQuery);
    $stmt->bind_param("sssi", $game_name, $game_description, $game_link, $game_id);

    if ($stmt->execute()) {
        $message = "Game updated successfully.";
        // Refresh game details
        $stmt = $conn->prepare($gameQuery);
        $stmt->bind_param("i", $game_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $game = $result->fetch_assoc();
    } else {
        $message = "Error updating game: " . $conn->error;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Game | Admin Portal</title>
    <style>
        :root {
            --primary-color: #3a36e0;
            --primary-light: #6e6ae4;
            --primary-dark: #2a27b5;
            --accent-color: #ff6b6b;
            --text-color: #2d3748;
            --text-light: #718096;
            --white: #ffffff;
            --light-bg: #f7fafc;
            --card-bg: #ffffff;
            --border-radius: 10px;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24);
            --shadow-md: 0 4px 6px rgba(0,0,0,0.1), 0 1px 3px rgba(0,0,0,0.08);
            --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05);
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f6f9fc 0%, #edf2f7 100%);
            color: var(--text-color);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            line-height: 1.6;
        }

        .header {
            background: linear-gradient(90deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: var(--white);
            padding: 1.2rem 2rem;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: var(--shadow-md);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }

        .header h2 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .container {
            max-width: 900px;
            width: 90%;
            margin: 2.5rem auto;
            padding: 0;
            flex: 1;
        }

        .content-box {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
            padding: 2.5rem;
            transition: var(--transition);
        }

        .content-box:hover {
            box-shadow: var(--shadow-lg);
        }

        h3 {
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            text-align: left;
            font-size: 1.75rem;
            font-weight: 600;
            position: relative;
            padding-bottom: 0.75rem;
        }

        h3::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            height: 3px;
            width: 50px;
            background-color: var(--accent-color);
            border-radius: 3px;
        }

        .form-group {
            margin-bottom: 1.75rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-color);
            font-weight: 500;
            font-size: 0.95rem;
        }

        input[type="text"],
        input[type="url"],
        textarea {
            width: 100%;
            padding: 0.9rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            box-sizing: border-box;
            margin-bottom: 0.25rem;
            font-family: inherit;
            color: var(--text-color);
            transition: var(--transition);
            background-color: #f8fafc;
        }

        input[type="text"]:focus,
        input[type="url"]:focus,
        textarea:focus {
            outline: none;
            border-color: var(--primary-light);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
            background-color: var(--white);
        }

        textarea {
            resize: vertical;
            min-height: 120px;
        }

        .field-hint {
            display: block;
            font-size: 0.8rem;
            color: var(--text-light);
            margin-top: 0.25rem;
        }

        .button-group {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn {
            display: inline-block;
            background-color: var(--primary-color);
            color: var(--white);
            padding: 0.9rem 1.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            text-align: center;
            text-decoration: none;
            transition: var(--transition);
            flex: 1;
        }

        .btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-primary {
            background-color: var(--primary-color);
        }

        .btn-secondary {
            background-color: #718096;
        }

        .btn-accent {
            background-color: var(--accent-color);
        }

        .message {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 500;
            background-color: #ebf8ff;
            color: #2c5282;
            border-left: 4px solid #4299e1;
        }

        .success {
            background-color: #f0fff4;
            color: #22543d;
            border-left: 4px solid #48bb78;
        }

        .footer {
            background-color: var(--primary-dark);
            color: var(--white);
            text-align: center;
            padding: 1.5rem;
            margin-top: 3rem;
            font-size: 0.9rem;
        }

        .back-link {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--white);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
        }

        .back-link:hover {
            opacity: 0.8;
        }

        .back-icon {
            display: inline-block;
            width: 6px;
            height: 6px;
            border-left: 2px solid;
            border-bottom: 2px solid;
            transform: rotate(45deg);
            margin-right: 5px;
        }

        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .content-box {
            animation: fadeIn 0.5s ease-out forwards;
        }

        @media screen and (max-width: 768px) {
            .header {
                padding: 1rem;
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .nav-actions {
                width: 100%;
                justify-content: center;
            }

            .container {
                margin: 1.5rem auto;
                width: 95%;
            }

            .content-box {
                padding: 1.5rem;
            }

            .button-group {
                flex-direction: column;
            }
        }

        @media screen and (max-width: 480px) {
            .header h2 {
                font-size: 1.2rem;
            }

            .content-box {
                padding: 1.25rem;
            }

            h3 {
                font-size: 1.4rem;
            }

            label {
                font-size: 0.9rem;
            }

            input[type="text"],
            input[type="url"],
            textarea {
                padding: 0.8rem;
                font-size: 0.95rem;
            }

            .btn {
                padding: 0.8rem 1rem;
                font-size: 0.95rem;
            }

            .footer {
                padding: 1rem;
                font-size: 0.8rem;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <h2>Admin Portal - Edit Game</h2>
        <div class="nav-actions">
            <a href="add_games.php" class="back-link"><span class="back-icon"></span> Back to Games</a>
        </div>
    </header>

    <div class="container">
        <div class="content-box">
            <h3>Edit Game Details</h3>
            
            <?php if ($message): ?>
                <div class="message success"><?= $message ?></div>
            <?php endif; ?>

            <form action="edit_game.php?game_id=<?= $game_id ?>" method="POST">
                <div class="form-group">
                    <label for="game_name">Game Name</label>
                    <input type="text" id="game_name" name="game_name" value="<?= htmlspecialchars($game['game_name']) ?>" required>
                    <span class="field-hint">Enter the complete name of the game</span>
                </div>

                <div class="form-group">
                    <label for="game_description">Game Description</label>
                    <textarea id="game_description" name="game_description" rows="4" required><?= htmlspecialchars($game['game_description']) ?></textarea>
                    <span class="field-hint">Provide a detailed description of the game</span>
                </div>

                <div class="form-group">
                    <label for="game_link">Game Link (URL)</label>
                    <input type="url" id="game_link" name="game_link" value="<?= htmlspecialchars($game['game_link']) ?>" required>
                    <span class="field-hint">Enter the complete URL including https://</span>
                </div>

                <div class="button-group">
                    <button type="submit" name="update_game" class="btn btn-primary">Update Game</button>
                    <a href="add_games.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; <?php echo date("Y"); ?> Wisetech College Portal | All Rights Reserved</p>
    </footer>
</body>
</html>
