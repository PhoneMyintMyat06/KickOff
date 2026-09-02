<?php
require_once 'db.php';

// GET method နဲ့ ရောက်လာတဲ့ newsID ကို စစ်ဆေးခြင်း
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: news.php");
    exit();
}

$newsID = intval($_GET['id']);

// Database မှ သတင်းအချက်အလက်ကို ဆွဲထုတ်ခြင်း
$stmt = $conn->prepare("SELECT * FROM NewsArticle WHERE newsID = ?");
$stmt->bind_param("i", $newsID);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // သတင်း ရှာမတွေ့ရင် news.php သို့ ပြန်ပို့မည်
    header("Location: news.php");
    exit();
}

$article = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($article['title']); ?> - KickOff</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            <a href="javascript:history.back()" class="btn-link" style="margin-bottom: 1rem; display: inline-block;">&larr; Back to News</a>
            
            <article class="card news-detail-card">
                <span class="news-date">
                    PUBLISHED: <?php echo date('F d, Y', strtotime($article['publishDate'])); ?>
                </span>
                
                <h1 class="news-detail-title"><?php echo htmlspecialchars($article['title']); ?></h1>

                <?php if (!empty($article['image'])): ?>
                    <div class="news-detail-img-container">
                        <img src="uploads/news/<?php echo htmlspecialchars($article['image']); ?>" alt="News Banner" class="news-detail-img">
                    </div>
                <?php endif; ?>

                <div class="news-detail-body">
                    <?php echo nl2br(htmlspecialchars($article['content'])); ?>
                </div>
            </article>
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