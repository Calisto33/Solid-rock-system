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
    <title>Admin - Manage Educational Games | Solid Rock Group of Schools</title>
    
    <!-- Favicon - matching main site -->
    <link rel="icon" type="image/ico" href="images/favicon.ico">
    <link rel="shortcut icon" type="image/jpeg" href="images/logo.jpeg">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    
    <style>
        /* Matching the main site's variables */
        :root {
            --primary-color: #003366;
            --secondary-color: #d12c2c;
            --accent-color: #f0f4f8;
            --text-color: #333;
            --light-text-color: #fff;
            --border-radius: 12px;
            --box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--accent-color);
            color: var(--text-color);
            line-height: 1.6;
            min-height: 100vh;
        }

        /* Header matching main site style */
        .main-header {
            background-color: #fff;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
            box-shadow: var(--box-shadow);
        }

        .main-header .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: flex-end;
            align-items: center;
        }

        .header-actions .btn {
            text-decoration: none;
            padding: 10px 20px;
            border-radius: var(--border-radius);
            font-weight: 500;
            transition: all 0.3s ease;
            background-color: var(--primary-color);
            color: var(--light-text-color);
            border: 2px solid var(--primary-color);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .header-actions .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        }

        /* Main container */
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 20px;
        }

        .content-card {
            background: #fff;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 2rem;
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary-color), #004080);
            color: var(--light-text-color);
            padding: 1.5rem 2rem;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .card-header h2 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .card-header i {
            font-size: 1.5rem;
        }

        .card-body {
            padding: 2rem;
        }

        /* Form styles matching main site */
        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--primary-color);
            font-weight: 600;
            font-size: 0.95rem;
        }

        input[type="text"],
        input[type="url"],
        textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e5e9;
            border-radius: var(--border-radius);
            font-family: 'Poppins', sans-serif;
            font-size: 1rem;
            transition: all 0.3s ease;
            background-color: #fff;
        }

        input[type="text"]:focus,
        input[type="url"]:focus,
        textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(0, 51, 102, 0.1);
        }

        textarea {
            resize: vertical;
            min-height: 120px;
        }

        /* Buttons matching main site style */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border: none;
            border-radius: var(--border-radius);
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            font-size: 1rem;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: var(--light-text-color);
        }

        .btn-success {
            background-color: var(--success-color);
            color: var(--light-text-color);
        }

        .btn-warning {
            background-color: var(--warning-color);
            color: var(--light-text-color);
        }

        .btn-danger {
            background-color: var(--danger-color);
            color: var(--light-text-color);
        }

        .btn-secondary {
            background-color: var(--secondary-color);
            color: var(--light-text-color);
        }

        .btn-sm {
            padding: 8px 16px;
            font-size: 0.875rem;
        }

        /* Table styles */
        .table-container {
            overflow-x: auto;
            border-radius: var(--border-radius);
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
        }

        th, td {
            padding: 15px 20px;
            text-align: left;
            border-bottom: 1px solid #e1e5e9;
        }

        th {
            background-color: var(--primary-color);
            color: var(--light-text-color);
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        tr:hover {
            background-color: rgba(0, 51, 102, 0.02);
        }

        .actions-cell {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        /* Message alerts */
        .alert {
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: var(--border-radius);
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
        }

        .alert-success {
            background-color: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
            border-left: 4px solid var(--success-color);
        }

        .alert-danger {
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
            border-left: 4px solid var(--danger-color);
        }

        /* Badge for view counts */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            background-color: var(--primary-color);
            color: var(--light-text-color);
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #666;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--primary-color);
            opacity: 0.5;
        }

        .empty-state p {
            font-size: 1.1rem;
            margin-bottom: 1.5rem;
        }

        /* Footer matching main site */
        .main-footer {
            background-color: var(--primary-color);
            color: var(--light-text-color);
            padding: 30px 0;
            margin-top: 3rem;
        }

        .main-footer .container {
            text-align: center;
        }

        .main-footer p {
            margin: 0;
            opacity: 0.9;
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .main-header .container {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .container {
                margin: 1rem auto;
                padding: 0 15px;
            }

            .card-body {
                padding: 1.5rem;
            }

            th, td {
                padding: 12px 10px;
                font-size: 0.9rem;
            }

            .actions-cell {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
                margin-bottom: 8px;
            }
        }
    </style>
</head>
<body>
    <!-- Simplified Header with Dashboard Button -->
    <header class="main-header">
        <div class="container">
            <div class="header-actions">
                <a href="admin_home.php" class="btn">
                    <i class="fas fa-home"></i>
                    Dashboard
                </a>
            </div>
        </div>
    </header>

    <div class="container">
        <!-- Add New Game Section -->
        <div class="content-card">
            <div class="card-header">
                <i class="fas fa-plus-circle"></i>
                <h2>Add New Educational Game</h2>
            </div>
            <div class="card-body">
                <!-- PHP message display -->
                <?php if (isset($message)): ?>
                    <div class="alert <?= strpos($message, 'Error') !== false ? 'alert-danger' : 'alert-success' ?>">
                        <i class="<?= strpos($message, 'Error') !== false ? 'fas fa-exclamation-circle' : 'fas fa-check-circle' ?>"></i>
                        <span><?= htmlspecialchars($message) ?></span>
                    </div>
                <?php endif; ?>

                <form action="add_games.php" method="POST">
                    <div class="form-group">
                        <label for="game_name">
                            <i class="fas fa-tag"></i> Game Name
                        </label>
                        <input type="text" id="game_name" name="game_name" placeholder="Enter the game name" required>
                    </div>

                    <div class="form-group">
                        <label for="game_description">
                            <i class="fas fa-align-left"></i> Game Description
                        </label>
                        <textarea id="game_description" name="game_description" placeholder="Describe what students will learn from this game" required></textarea>
                    </div>

                    <div class="form-group">
                        <label for="game_link">
                            <i class="fas fa-link"></i> Game URL
                        </label>
                        <input type="url" id="game_link" name="game_link" placeholder="https://example.com/educational-game" required>
                    </div>

                    <button type="submit" name="add_game" class="btn btn-success">
                        <i class="fas fa-plus"></i>
                        Add Game to Portal
                    </button>
                </form>
            </div>
        </div>

        <!-- Existing Games Section -->
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
                                <th><i class="fas fa-gamepad"></i> Game Name</th>
                                <th><i class="fas fa-info-circle"></i> Description</th>
                                <th><i class="fas fa-external-link-alt"></i> Link</th>
                                <th><i class="fas fa-chart-bar"></i> Student Views</th>
                                <th><i class="fas fa-cogs"></i> Actions</th>
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
                                               class="btn btn-secondary btn-sm" 
                                               target="_blank">
                                                <i class="fas fa-external-link-alt"></i> View Game
                                            </a>
                                        </td>
                                        <td>
                                            <span class="badge">
                                                <i class="fas fa-eye"></i> <?= $game['access_count'] ?? 0 ?>
                                            </span>
                                        </td>
                                        <td class="actions-cell">
                                            <a href="edit_game.php?game_id=<?= $game['game_id'] ?>" 
                                               class="btn btn-warning btn-sm">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <a href="add_games.php?delete=<?= $game['game_id'] ?>" 
                                               class="btn btn-danger btn-sm"
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
                                            <p style="color: #666; font-size: 0.9rem;">Start building your collection of educational games for students.</p>
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

    <!-- Footer matching main site -->
    <footer class="main-footer">
        <div class="container">
            <p>"Learning Today... Leading Tomorrow"</p>
            <p>&copy; <?php echo date("Y"); ?> Mirilax-Scales- Educational Games Portal</p>
        </div>
    </footer>
</body>
</html>
