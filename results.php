<?php
require_once 'db.php';

// Fetch Completed Matches ( matchDate အဟောင်းများကိုပဲ ဆွဲထုတ်မည် )
$resultQuery = "SELECT * FROM Fixture WHERE matchDate < CURDATE() ORDER BY matchDate DESC, matchTime DESC";
$resultData = $conn->query($resultQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KickOff - Match Results</title>
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- External CSS Link -->
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <!-- Header Navigation Bar -->
    <header class="navbar">
        <div class="logo">
            <h2>KICK<span>OFF</span></h2>
        </div>
        <nav>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="news.php">News</a></li>
                <li><a href="fixtures.php">Fixtures</a></li>
                <li><a href="results.php" class="active">Results</a></li>
                <li><a href="table.php">Standings</a></li>
            </ul>
        </nav>
    </header>

    <!-- Main Container -->
    <main class="container">

        <section class="section">
            <h2 class="section-title">Match <span>Results</span></h2>
            
            <div class="fixtures-wrapper">
                <?php if ($resultData && $resultData->num_rows > 0): ?>
                    <?php 
                    $currentDate = '';
                    while($row = $resultData->fetch_assoc()): 
                        $matchDateFormatted = date('D, M d, Y', strtotime($row['matchDate']));
                        
                        if ($currentDate !== $matchDateFormatted): 
                            $currentDate = $matchDateFormatted;
                    ?>
                        <div class="date-divider">
                            <i class="fa-regular fa-calendar-check"></i> <?php echo $currentDate; ?>
                        </div>
                    <?php endif; ?>

                    <div class="card fixture-card result-card">
                        <div class="fixture-team home-team">
                            <span class="team-name"><?php echo htmlspecialchars($row['homeTeam']); ?></span>
                            <?php if (!empty($row['homeTeamIcon'])): ?>
                                <img src="uploads/teams/<?php echo htmlspecialchars($row['homeTeamIcon']); ?>" alt="Home Logo" class="fixture-icon">
                            <?php else: ?>
                                <div class="icon-placeholder"></div>
                            <?php endif; ?>
                        </div>

                        <div class="fixture-center">
                            <div class="score-box">
                                <span class="score"><?php echo isset($row['homeScore']) ? $row['homeScore'] : '0'; ?></span>
                                <span class="score-divider">-</span>
                                <span class="score"><?php echo isset($row['awayScore']) ? $row['awayScore'] : '0'; ?></span>
                            </div>
                            <span class="match-status-badge">FT</span>
                        </div>

                        <div class="fixture-team away-team">
                            <?php if (!empty($row['awayTeamIcon'])): ?>
                                <img src="uploads/teams/<?php echo htmlspecialchars($row['awayTeamIcon']); ?>" alt="Away Logo" class="fixture-icon">
                            <?php else: ?>
                                <div class="icon-placeholder"></div>
                            <?php endif; ?>
                            <span class="team-name"><?php echo htmlspecialchars($row['awayTeam']); ?></span>
                        </div>
                    </div>

                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="card">
                        <p class="no-data">No match results available.</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>

    </main>

    <!-- Footer Section -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-info">
                <h3>KICK<span>OFF</span></h3>
                <p>Your ultimate destination for Premier League news, fixtures, and real-time standings.</p>
            </div>
            
            <div class="footer-social">
                <div class="social-icons-only">
                    <a href="#" class="social-icon-btn" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-icon-btn" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="social-icon-btn" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
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