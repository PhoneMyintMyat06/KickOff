<?php
require_once 'db.php';

// Fetch Upcoming Fixtures (ပွဲစဉ်များကို နေ့ရက်အလိုက် စီစဉ်ခြင်း)
$fixtureQuery = "SELECT * FROM Fixture WHERE matchDate >= CURDATE() ORDER BY matchDate ASC, matchTime ASC";
$fixtureResult = $conn->query($fixtureQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KickOff - Upcoming Fixtures</title>
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
                <li><a href="fixtures.php" class="active">Fixtures</a></li>
                <li><a href="results.php">Results</a></li>
                <li><a href="table.php">Standings</a></li>
            </ul>
        </nav>
    </header>

    <!-- Main Container -->
    <main class="container">

        <section class="section">
            <h2 class="section-title">Upcoming <span>Fixtures</span></h2>
            
            <div class="fixtures-wrapper">
                <?php if ($fixtureResult && $fixtureResult->num_rows > 0): ?>
                    <?php 
                    $currentDate = '';
                    while($row = $fixtureResult->fetch_assoc()): 
                        $matchDateFormatted = date('D, M d, Y', strtotime($row['matchDate']));
                        
                        // နေ့ရက်အသစ်ရောက်တိုင်း Header အသစ်ခွဲထုတ်ပေးခြင်း
                        if ($currentDate !== $matchDateFormatted): 
                            $currentDate = $matchDateFormatted;
                    ?>
                        <div class="date-divider">
                            <i class="fa-regular fa-calendar-check"></i> <?php echo $currentDate; ?>
                        </div>
                    <?php endif; ?>

                    <div class="card fixture-card">
                        <div class="fixture-team home-team">
                            <span class="team-name"><?php echo htmlspecialchars($row['homeTeam']); ?></span>
                            <?php if (!empty($row['homeTeamIcon'])): ?>
                                <img src="uploads/teams/<?php echo htmlspecialchars($row['homeTeamIcon']); ?>" alt="Home Logo" class="fixture-icon">
                            <?php else: ?>
                                <div class="icon-placeholder"></div>
                            <?php endif; ?>
                        </div>

                        <div class="fixture-center">
                            <span class="fixture-time"><?php echo date('H:i', strtotime($row['matchTime'])); ?></span>
                            <span class="fixture-venue"><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($row['venue']); ?></span>
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
                        <p class="no-data">No upcoming fixtures scheduled at the moment.</p>
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