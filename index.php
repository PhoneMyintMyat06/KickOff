<?php
require_once 'db.php';

// 1. Fetch Top News
$topNewsQuery = "SELECT * FROM NewsArticle WHERE isTopNews = 1 ORDER BY publishDate DESC LIMIT 1";
$topNewsResult = $conn->query($topNewsQuery);
$topNews = $topNewsResult ? $topNewsResult->fetch_assoc() : null;

// 2. Fetch Match of the Week
$matchQuery = "SELECT * FROM Fixture WHERE isMatchOfWeek = 1 ORDER BY matchDate ASC LIMIT 1";
$matchResult = $conn->query($matchQuery);
$matchOfWeek = $matchResult ? $matchResult->fetch_assoc() : null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KickOff - Premier League Tracker</title>
    <!-- Font Awesome Icons for Social Media -->
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
                <li><a href="index.php" class="active">Home</a></li>
                <li><a href="news.php">News</a></li>
                <li><a href="fixtures.php">Fixtures</a></li>
                <li><a href="results.php">Results</a></li>
                <li><a href="table.php">Standings</a></li>
            </ul>
        </nav>
    </header>

    <!-- Main Container -->
    <main class="container">

        <!-- Top News (Featured) Section -->
        <section class="section">
            <h2 class="section-title">Top <span>News</span></h2>
            
            <?php if ($topNews): ?>
                <article class="card featured-news-card">
                    <?php if (!empty($topNews['image'])): ?>
                        <img src="uploads/news/<?php echo htmlspecialchars($topNews['image']); ?>" alt="News Image" class="news-banner">
                    <?php endif; ?>
                    <div class="news-content">
                        <span class="badge">TOP STORY</span>
                        <h3><?php echo htmlspecialchars($topNews['title']); ?></h3>
                        <p class="news-meta">Published: <?php echo date('M d, Y', strtotime($topNews['publishDate'])); ?></p>
                        <p><?php echo htmlspecialchars(substr($topNews['content'], 0, 200)) . '...'; ?></p>
                        <a href="news_detail.php?id=<?php echo $topNews['newsID']; ?>" class="btn-link">Read Full Story &rarr;</a>
                    </div>
                </article>
            <?php else: ?>
                <div class="card">
                    <p class="no-data">No top news featured at the moment.</p>
                </div>
            <?php endif; ?>
        </section>

        <!-- Match of the Week Section -->
        <section class="section">
            <h2 class="section-title">Match of the <span>Week</span></h2>
            
            <div class="card match-featured-card">
                <?php if ($matchOfWeek): ?>
                    <div class="featured-badge">FEATURED MATCH</div>
                    <div class="match-details">
                        <div class="team-box home">
                            <?php if(!empty($matchOfWeek['homeTeamIcon'])): ?>
                                <img src="uploads/teams/<?php echo htmlspecialchars($matchOfWeek['homeTeamIcon']); ?>" alt="Home Team" class="team-icon">
                            <?php endif; ?>
                            <h2><?php echo htmlspecialchars($matchOfWeek['homeTeam']); ?></h2>
                        </div>
                        
                        <div class="vs-box">
                            <span class="vs">VS</span>
                            <div class="match-time-info">
                                <p class="time"><?php echo date('H:i', strtotime($matchOfWeek['matchTime'])); ?></p>
                                <p class="date"><?php echo date('D, M d', strtotime($matchOfWeek['matchDate'])); ?></p>
                                <p class="venue">📍 <?php echo htmlspecialchars($matchOfWeek['venue']); ?></p>
                            </div>
                        </div>
                        
                        <div class="team-box away">
                            <?php if(!empty($matchOfWeek['awayTeamIcon'])): ?>
                                <img src="uploads/teams/<?php echo htmlspecialchars($matchOfWeek['awayTeamIcon']); ?>" alt="Away Team" class="team-icon">
                            <?php endif; ?>
                            <h2><?php echo htmlspecialchars($matchOfWeek['awayTeam']); ?></h2>
                        </div>
                    </div>
                <?php else: ?>
                    <p class="no-data">No Match of the Week scheduled.</p>
                <?php endif; ?>
            </div>
        </section>

    </main>

    <!-- Footer Section -->
    <footer class="footer">
        <div class="footer-container">
            <!-- 1. Paragraph / Site Info -->
            <div class="footer-info">
                <h3>KICK<span>OFF</span></h3>
                <p>Your ultimate destination for Premier League news, fixtures, and real-time standings.</p>
            </div>
            
            <!-- 2. Pure Social Media Icons (Facebook, Instagram, YouTube) -->
            <div class="footer-social">
                <div class="social-icons-only">
                    <a href="#" class="social-icon-btn" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-icon-btn" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="social-icon-btn" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
                </div>
            </div>

            <!-- 3. Smaller Staff Access Button -->
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