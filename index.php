<?php
require_once 'db.php';

// 1. Fetch Top News (Title, Image, Date strictly for summary view)
$topNewsQuery = "SELECT * FROM NewsArticle WHERE isTopNews = 1 ORDER BY publishDate DESC LIMIT 1";
$topNewsResult = $conn->query($topNewsQuery);
$topNews = $topNewsResult ? $topNewsResult->fetch_assoc() : null;

// 2. Fetch Match of the Week (JOINed with Team table to get Team Names and Icons)
$matchQuery = "SELECT f.*, 
                      t1.teamName AS homeTeamName, 
                      t1.teamIcon AS homeTeamIcon, 
                      t2.teamName AS awayTeamName, 
                      t2.teamIcon AS awayTeamIcon 
               FROM Fixture f 
               JOIN Team t1 ON f.homeTeamID = t1.teamID 
               JOIN Team t2 ON f.awayTeamID = t2.teamID 
               WHERE f.isMatchOfWeek = 1 
               ORDER BY f.matchDate ASC LIMIT 1";
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
            
            <?php if ($matchOfWeek): ?>
                <div class="card fixture-card motw-card">
                    <!-- Home Team -->
                    <div class="fixture-team home-team">
                        <span class="team-name motw-team-name"><?php echo htmlspecialchars($matchOfWeek['homeTeamName']); ?></span>
                        <?php if (!empty($matchOfWeek['homeTeamIcon'])): ?>
                            <img src="<?php echo htmlspecialchars($matchOfWeek['homeTeamIcon']); ?>" alt="Home Team" class="fixture-icon motw-logo">
                        <?php else: ?>
                            <div class="icon-placeholder"></div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Center VS & Info -->
                    <div class="fixture-center">
                        <div class="score-box motw-vs-box">
                            <span class="score">VS</span>
                        </div>
                        <span class="match-status-badge motw-time-text">
                            <?php echo date('H:i', strtotime($matchOfWeek['matchTime'])); ?> | <?php echo date('D, M d', strtotime($matchOfWeek['matchDate'])); ?>
                        </span>
                        <span class="match-status-badge">📍 <?php echo htmlspecialchars($matchOfWeek['venue']); ?></span>
                    </div>
                    
                    <!-- Away Team -->
                    <div class="fixture-team away-team">
                        <?php if (!empty($matchOfWeek['awayTeamIcon'])): ?>
                            <img src="<?php echo htmlspecialchars($matchOfWeek['awayTeamIcon']); ?>" alt="Away Team" class="fixture-icon motw-logo">
                        <?php else: ?>
                            <div class="icon-placeholder"></div>
                        <?php endif; ?>
                        <span class="team-name motw-team-name"><?php echo htmlspecialchars($matchOfWeek['awayTeamName']); ?></span>
                    </div>
                </div>
            <?php else: ?>
                <div class="card">
                    <p class="no-data">No Match of the Week scheduled.</p>
                </div>
            <?php endif; ?>
        </section>

    </main>

    <!-- Footer Section -->
    <footer class="footer">
        <div class="footer-container">
            <!-- 1. Site Info -->
            <div class="footer-info">
                <h3>KICK<span>OFF</span></h3>
                <p>Your ultimate destination for Premier League news, fixtures, and real-time standings.</p>
            </div>
            
            <!-- 2. Social Media Icons -->
            <div class="footer-social">
                <div class="social-icons-only">
                    <a href="#" class="social-icon-btn" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-icon-btn" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="social-icon-btn" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
                </div>
            </div>

            <!-- 3. Staff Access Button -->
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