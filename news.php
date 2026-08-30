<?php
require_once 'db.php';

// Fetch All News (Top News မပါဘဲ အားလုံး သို့မဟုတ် publishDate အလိုက် စီစဉ်ခြင်း)
$newsQuery = "SELECT * FROM NewsArticle ORDER BY publishDate DESC";
$newsResult = $conn->query($newsQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KickOff - Latest News</title>
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
                <li><a href="news.php" class="active">News</a></li>
                <li><a href="fixtures.php">Fixtures</a></li>
                <li><a href="results.php">Results</a></li>
                <li><a href="table.php">Standings</a></li>
            </ul>
        </nav>
    </header>

    <!-- Main Container -->
    <main class="container">

        <section class="section">
            <h2 class="section-title">Latest <span>Football News</span></h2>
            
            <div class="news-grid">
                <?php if ($newsResult && $newsResult->num_rows > 0): ?>
                    <?php while($row = $newsResult->fetch_assoc()): ?>
                        <article class="card news-card">
                            <div class="news-img-holder">
                                <?php if (!empty($row['image'])): ?>
                                    <img src="uploads/news/<?php echo htmlspecialchars($row['image']); ?>" alt="News Image">
                                <?php else: ?>
                                    <div class="no-img-placeholder"><i class="fa-solid fa-newspaper"></i></div>
                                <?php endif; ?>
                            </div>
                            <div class="news-card-body">
                                <span class="news-date"><i class="fa-regular fa-calendar-days"></i> <?php echo date('M d, Y', strtotime($row['publishDate'])); ?></span>
                                <h3><?php echo htmlspecialchars($row['title']); ?></h3>
                                <p><?php echo htmlspecialchars(substr($row['content'], 0, 120)) . '...'; ?></p>
                                <a href="news_detail.php?id=<?php echo $row['newsID']; ?>" class="btn-link">Read More &rarr;</a>
                            </div>
                        </article>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="card full-width">
                        <p class="no-data">No news articles found.</p>
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