<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}

$newsCount = $conn->query("SELECT COUNT(*) AS total FROM newsarticle")->fetch_assoc()['total'] ?? 0;

$fixtureCount = $conn->query("SELECT COUNT(*) AS total FROM Fixture WHERE matchDate >= CURDATE()")->fetch_assoc()['total'] ?? 0;

$teamsCount = $conn->query("SELECT COUNT(*) AS total FROM Team")->fetch_assoc()['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KickOff - Staff Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <header class="navbar">
        <div class="logo">
            <h2>KICK<span>OFF</span> <small style="font-size:0.6rem; color:var(--text-muted); display:inline-block;">[ADMIN]</small></h2>
        </div>
        <nav>
            <ul class="nav-links">
                <li><a href="admin_dashboard.php" class="active">Dashboard</a></li>
                <li><a href="admin_manage_teams.php">Manage Teams</a></li> <!-- Team Management Link ထည့်ပေးထားသည် -->
                <li><a href="admin_manage_news.php">Manage News</a></li>
                <li><a href="admin_manage_fixtures.php">Manage Fixtures</a></li>
                <li><a href="admin_manage_table.php">Manage Table</a></li>
                <li><a href="admin_logout.php" style="color: #ff4d4d;">Logout</a></li>
            </ul>
        </nav>
    </header>

    <main class="container">
        <section class="section">
            <h2 class="section-title">Admin <span>Dashboard</span></h2>
            <p style="color: var(--text-muted); margin-bottom: 2rem;">Welcome back, <strong><?php echo htmlspecialchars($_SESSION['admin_user'] ?? 'Admin'); ?></strong>!</p>

            <div class="news-grid">
                <div class="card stat-card">
                    <div class="stat-icon"><i class="fa-solid fa-newspaper"></i></div>
                    <div class="stat-details">
                        <h3><?php echo $newsCount; ?></h3>
                        <p>Total News Articles</p>
                    </div>
                </div>

                <div class="card stat-card">
                    <div class="stat-icon"><i class="fa-solid fa-calendar-days"></i></div>
                    <div class="stat-details">
                        <h3><?php echo $fixtureCount; ?></h3>
                        <p>Upcoming Fixtures</p>
                    </div>
                </div>

                <div class="card stat-card">
                    <div class="stat-icon"><i class="fa-solid fa-shield-halved"></i></div>
                    <div class="stat-details">
                        <h3><?php echo $teamsCount; ?></h3>
                        <p>League Teams</p>
                    </div>
                </div>
            </div>
        </section>
    </main>

</body>
</html>