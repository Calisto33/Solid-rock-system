<?php
session_start();
include '../config.php'; // Database connection

// Check if the user is logged in as an admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

// Handle form submission for adding a new game
$message = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_game'])) {
    $game_name = $_POST['game_name'];
    $game_description = $_POST['game_description'];
    $game_link = $_POST['game_link'];

    $insertGameQuery = "INSERT INTO educational_games (game_name, game_description, game_link) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($insertGameQuery);
    $stmt->bind_param("sss", $game_name, $game_description, $game_link);

    if ($stmt->execute()) {
        $message = "Game added successfully.";
    } else {
        $message = "Error adding game: " . $conn->error;
    }
}

// Handle deletion of a game
if (isset($_GET['delete'])) {
    $game_id = $_GET['delete'];

    $deleteGameQuery = "DELETE FROM educational_games WHERE game_id = ?";
    $stmt = $conn->prepare($deleteGameQuery);
    $stmt->bind_param("i", $game_id);

    if ($stmt->execute()) {
        $message = "Game deleted successfully.";
    } else {
        $message = "Error deleting game: " . $conn->error;
    }
}

// Fetch all games
$gamesQuery = "SELECT * FROM educational_games";
$gamesResult = $conn->query($gamesQuery);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Educational Games</title>
    <link rel="icon" type="image/jpeg" href="../images/logo.jpeg">
    <link rel="shortcut icon" type="image/jpeg" href="../images/logo.jpeg">
    <!-- Favicon -->
    <link rel="icon" type="image/jpeg" href="../images/logo.jpeg">
    <link rel="shortcut icon" type="image/jpeg" href="../images/logo.jpeg">
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    
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
            min-height: 100vh;
            display: flex;
            flex-direction: column;
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
            padding: 2rem;
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
        }

        .content-card {
            background: var(--card-background);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-light);
            margin-bottom: 2rem;
            overflow: hidden;
            transition: var(--transition);
        }

        .content-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            background-color: var(--primary-color);
            color: var(--card-background);
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .card-header h2 {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0;
        }

        .card-body {
            padding: 2.5rem;
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

        input[type="text"],
        input[type="url"],
        textarea {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 1rem;
            background-color: var(--background-color);
            transition: border-color 0.3s, box-shadow 0.3s;
            font-family: 'Poppins', sans-serif;
        }
        
        input[type="text"]:focus,
        input[type="url"]:focus,
        textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.2);
            background-color: var(--card-background);
        }

        textarea {
            min-height: 150px;
            resize: vertical;
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
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .submit-btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .table-container {
            overflow-x: auto;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-light);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.95rem;
        }
        
        thead {
            background-color: #f3f4f6;
            color: var(--text-color);
        }

        th {
            padding: 1rem;
            border-bottom: 2px solid var(--border-color);
        }
        
        tbody tr {
            border-bottom: 1px solid var(--border-color);
        }

        tbody tr:last-child {
            border-bottom: none;
        }

        tbody tr:nth-child(even) {
            background-color: #fafafa;
        }

        tbody tr:hover {
            background-color: #f0f4f8;
        }

        td {
            padding: 1rem;
            vertical-align: top;
        }

        .actions-cell {
            display: flex;
            gap: 0.5rem;
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            text-decoration: none;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 0.25rem;
            transition: var(--transition);
            cursor: pointer;
        }

        .btn-warning {
            background-color: #F7D04B;
            color: var(--text-color);
            border: 1px solid #F7D04B;
        }
        .btn-warning:hover {
            background-color: #e6b93b;
            color: var(--text-color);
            transform: translateY(-2px);
        }
        
        .btn-danger {
            background-color: #F44336;
            color: var(--card-background);
            border: 1px solid #F44336;
        }
        .btn-danger:hover {
            background-color: #d12c2c;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background-color: var(--card-background);
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
        }
        .btn-secondary:hover {
            background-color: var(--primary-color);
            color: var(--card-background);
            transform: translateY(-2px);
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.75rem;
            border-radius: 999px;
            font-size: 0.8rem;
            font-weight: 600;
            background-color: #E0E0E0;
            color: var(--text-light);
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--text-light);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--border-color);
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
            .card-body {
                padding: 1.5rem;
            }
            .actions-cell {
                flex-direction: column;
            }
            .btn-sm {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-left">
            <img src="../images/logo.jpeg" alt="Solid Rock Logo" class="logo">
            <h2>Solid rock Admin Portal</h2>
        </div>
        <a href="admin_home.php" class="nav-link">Dashboard</a>
    </header>

    <div class="container">
        <div class="content-card">
            <div class="card-header">
                <i class="fas fa-plus-circle"></i>
                <h2>Add New Educational Game</h2>
            </div>
            <div class="card-body">
                <?php if (isset($message)): ?>
                    <div class="message <?= strpos($message, 'Error') !== false ? 'error' : 'success' ?>">
                        <i class="fas <?= strpos($message, 'Error') !== false ? 'fa-exclamation-circle' : 'fa-check-circle' ?>"></i>
                        <span><?= htmlspecialchars($message) ?></span>
                    </div>
                <?php endif; ?>

                <form action="add_games.php" method="POST">
                    <div class="form-group">
                        <label for="game_name">Game Name</label>
                        <input type="text" id="game_name" name="game_name" class="form-control" placeholder="Enter the game name" required>
                    </div>

                    <div class="form-group">
                        <label for="game_description">Game Description</label>
                        <textarea id="game_description" name="game_description" class="form-control" placeholder="Describe what students will learn from this game" required></textarea>
                    </div>

                    <div class="form-group">
                        <label for="game_link">Game URL</label>
                        <input type="url" id="game_link" name="game_link" class="form-control" placeholder="https://example.com/educational-game" required>
                    </div>

                    <button type="submit" name="add_game" class="submit-btn">
                        <i class="fas fa-plus"></i>
                        Add Game to Portal
                    </button>
                </form>
            </div>
        </div>

        <div class="content-card">
            <div class="card-header">
                <i class="fas fa-list"></i>
                <h2>Current Educational Games</h2>
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Game Name</th>
                                <th>Description</th>
                                <th>Link</th>
                                <th>Student Views</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (isset($gamesResult) && $gamesResult->num_rows > 0): ?>
                                <?php while ($game = $gamesResult->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($game['game_name']) ?></strong></td>
                                        <td><?= htmlspecialchars($game['game_description']) ?></td>
                                        <td>
                                            <a href="<?= htmlspecialchars($game['game_link']) ?>" 
                                                class="btn-sm btn-secondary" 
                                                target="_blank">
                                                <i class="fas fa-external-link-alt"></i> View
                                            </a>
                                        </td>
                                        <td>
                                            <span class="badge">
                                                <i class="fas fa-eye"></i> <?= $game['access_count'] ?? 0 ?>
                                            </span>
                                        </td>
                                        <td class="actions-cell">
                                            <a href="edit_game.php?game_id=<?= $game['game_id'] ?>" 
                                                class="btn-sm btn-warning">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <a href="add_games.php?delete=<?= $game['game_id'] ?>" 
                                                class="btn-sm btn-danger"
                                                onclick="return confirm('Are you sure you want to remove this game from the portal?');">
                                                <i class="fas fa-trash-alt"></i> Remove
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5">
                                        <div class="empty-state">
                                            <i class="fas fa-gamepad"></i>
                                            <p>No educational games added yet.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; <?php echo date("Y"); ?> Mirilax-Sclaes Portal | Admin System</p>
    </footer>
</body>
</html>
