<?php
$pageTitle = "Educational Games"; // This sets the title in the header
include 'header.php';            // This includes the sidebar and unified styles
include '../config.php';         // Your database connection

// Fetch all games
$gamesQuery = "SELECT * FROM educational_games ORDER BY game_name ASC";
$gamesResult = $conn->query($gamesQuery);
?>

<!-- Page-specific styles for the games page -->
<style>
    .page-title {
        font-weight: 700;
        color: var(--primary-text);
        font-size: 1.8rem;
        margin-bottom: 1rem;
    }
    .page-intro {
        margin-bottom: 2rem;
        color: var(--secondary-text);
        max-width: 70ch;
    }
    .games-container {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 1.5rem;
    }
    .game-card {
        background-color: var(--widget-bg);
        border-radius: var(--rounded-lg);
        overflow: hidden;
        box-shadow: var(--shadow);
        transition: var(--transition);
        display: flex;
        flex-direction: column;
    }
    .game-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-lg);
    }
    .game-icon {
        height: 140px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(45deg, var(--bg-color), #fff);
        color: var(--accent-purple);
    }
    .game-icon i {
        font-size: 3.5rem;
    }
    .game-details {
        padding: 1.5rem;
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    .game-name {
        margin-bottom: 0.75rem;
        font-size: 1.2rem;
        font-weight: 600;
        color: var(--primary-text);
    }
    .game-description {
        color: var(--secondary-text);
        margin-bottom: 1.5rem;
        flex: 1;
    }
    .play-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        width: 100%;
    }
    .game-empty {
        grid-column: 1 / -1;
        text-align: center;
        padding: 3rem;
    }
</style>

<h1 class="page-title">Learning Arcade</h1>
<p class="page-intro">Engage with these fun, educational games designed to help you learn.</p>

<div class="games-container">
    <?php if ($gamesResult && $gamesResult->num_rows > 0): ?>
        <?php 
        $icons = ['fa-puzzle-piece', 'fa-brain', 'fa-calculator', 'fa-spell-check', 'fa-microscope', 'fa-atom', 'fa-globe', 'fa-landmark', 'fa-flask', 'fa-chart-line', 'fa-book', 'fa-language'];
        $i = 0;
        while ($game = $gamesResult->fetch_assoc()): 
            $icon = $icons[$i % count($icons)];
            $i++;
        ?>
            <div class="game-card">
                <div class="game-icon">
                    <i class="fas <?= $icon ?>"></i>
                </div>
                <div class="game-details">
                    <h3 class="game-name"><?= htmlspecialchars($game['game_name']) ?></h3>
                    <p class="game-description"><?= htmlspecialchars($game['game_description']) ?></p>
                    <a href="play_game.php?game_id=<?= $game['game_id'] ?>" class="btn btn-primary play-btn" target="_blank">
                        <i class="fas fa-play"></i> Play Now
                    </a>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="card game-empty">
            <i class="fas fa-gamepad" style="font-size: 2.5rem; margin-bottom: 1rem;"></i>
            <p>No games are available at the moment. Please check back soon!</p>
        </div>
    <?php endif; ?>
</div>

<?php 
$gamesResult->close();
$conn->close();
include 'footer.php'; 
?>
