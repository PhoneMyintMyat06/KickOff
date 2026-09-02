<?php
require_once 'db.php';

// 1. Fetch available Matchdays for Filter Dropdown
$matchdayQuery = "SELECT DISTINCT matchday FROM Fixture ORDER BY matchday ASC";
$matchdaysResult = $conn->query($matchdayQuery);

// 2. Get Selected Matchday (Default to 1 or smallest available matchday)
$selectedMatchday = isset($_GET['matchday']) ? intval($_GET['matchday']) : 1;

// 3. Fetch Fixtures for the selected matchday
$sql = "SELECT f.*, 
               t1.teamName AS homeTeamName, t1.teamIcon AS homeTeamIcon, 
               t2.teamName AS awayTeamName, t2.teamIcon AS awayTeamIcon 
        FROM Fixture f 
        JOIN Team t1 ON f.homeTeamID = t1.teamID 
        JOIN Team t2 ON f.awayTeamID = t2.teamID 
        WHERE f.matchday = ? 
        ORDER BY f.matchDate ASC, f.matchTime ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $selectedMatchday);
$stmt->execute();
$fixturesResult = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fixtures - KickOff</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <header class="navbar">
        <div class="logo">
            <h2>KICK<span>OFF</span></h2>
        </div>
        <nav>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="news.php">News</a></li>
                <li><a href="fixtures.php" class="active">Fixtures</a></li>
                <li><a href="results.php">Results</a></li>
                <li><a href="table.php">Standings</a></li>
            </ul>
        </nav>
    </header>

    <main class="container">
        <section class="section">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
                <h2 class="section-title" style="margin-bottom: 0;">Match <span>Fixtures</span></h2>
                
                <!-- Matchday Filter Dropdown -->
                <form action="fixtures.php" method="GET" style="display: flex; align-items: center; gap: 0.5rem;">
                    <label for="matchday" style="color: var(--text-muted); font-weight: 600;">Select Round:</label>
                    <select name="matchday" id="matchday" onchange="this.form.submit()" class="admin-form-select" style="width: auto; padding: 0.5rem 1rem;">
                        <?php if ($matchdaysResult && $matchdaysResult->num_rows > 0): ?>
                            <?php while($m = $matchdaysResult->fetch_assoc()): ?>
                                <option value="<?php echo $m['matchday']; ?>" <?php echo ($m['matchday'] == $selectedMatchday) ? 'selected' : ''; ?>>
                                    Matchday <?php echo $m['matchday']; ?>
                                </option>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <option value="1">Matchday 1</option>
                        <?php endif; ?>
                    </select>
                </form>
            </div>

            <div class="fixtures-wrapper">
                <?php if ($fixturesResult && $fixturesResult->num_rows > 0): ?>
                    <?php while($row = $fixturesResult->fetch_assoc()): ?>
                        <div class="card fixture-card">
                            <div class="fixture-team home-team">
                                <span class="team-name"><?php echo htmlspecialchars($row['homeTeamName']); ?></span>
                                <?php if (!empty($row['homeTeamIcon'])): ?>
                                    <img src="<?php echo htmlspecialchars($row['homeTeamIcon']); ?>" alt="Logo" class="fixture-icon motw-logo">
                                <?php endif; ?>
                            </div>

                            <div class="fixture-center">
                                <div class="score-box">
                                    <span class="score" style="font-size: 1rem;">VS</span>
                                </div>
                                <span class="match-status-badge">
                                    <?php echo date('H:i', strtotime($row['matchTime'])); ?> | <?php echo date('D, M d', strtotime($row['matchDate'])); ?>
                                </span>
                                <span class="match-status-badge">📍 <?php echo htmlspecialchars($row['venue']); ?></span>
                            </div>

                            <div class="fixture-team away-team">
                                <?php if (!empty($row['awayTeamIcon'])): ?>
                                    <img src="<?php echo htmlspecialchars($row['awayTeamIcon']); ?>" alt="Logo" class="fixture-icon motw-logo">
                                <?php endif; ?>
                                <span class="team-name"><?php echo htmlspecialchars($row['awayTeamName']); ?></span>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="card">
                        <p class="no-data">No fixtures scheduled for Matchday <?php echo $selectedMatchday; ?>.</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <footer class="footer">
        <div class="footer-container">
            <div class="footer-info">
                <h3>KICK<span>OFF</span></h3>
                <p>Your ultimate destination for Premier League news, fixtures, and real-time standings.</p>
            </div>
            <div class="footer-social">
                <div class="social-icons-only">
                    <a href="#" class="social-icon-btn"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-icon-btn"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="social-icon-btn"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
            <div class="footer-admin">
                <a href="admin_login.php" class="admin-login-btn">Staff Access Portal &rarr;</a>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2026 KickOff Premier League Tracking System. All rights reserved.</p>
        </div>
    </footer>

</body>
</html>