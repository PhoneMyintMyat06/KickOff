<?php
require_once 'db.php';

// Fetch Standings Data by joining leaguetable with Team table to get teamName and teamIcon
$tableQuery = "SELECT lt.*, t.teamName, t.teamIcon 
               FROM leaguetable lt 
               JOIN Team t ON lt.teamID = t.teamID 
               ORDER BY lt.points DESC, lt.gd DESC, lt.won DESC";
$tableResult = $conn->query($tableQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KickOff - League Table</title>
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
                <li><a href="results.php">Results</a></li>
                <li><a href="table.php" class="active">Standings</a></li>
            </ul>
        </nav>
    </header>

    <!-- Main Container -->
    <main class="container">

        <section class="section">
            <h2 class="section-title">Premier League <span>Standings</span></h2>
            
            <div class="card table-card">
                <div class="table-responsive">
                    <table class="standings-table">
                        <thead>
                            <tr>
                                <th>Pos</th>
                                <th class="text-left">Club</th>
                                <th>MP</th>
                                <th>W</th>
                                <th>D</th>
                                <th>L</th>
                                <th>GF</th>
                                <th>GA</th>
                                <th>GD</th>
                                <th>Pts</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($tableResult && $tableResult->num_rows > 0): ?>
                                <?php 
                                $position = 1;
                                while($row = $tableResult->fetch_assoc()): 
                                ?>
                                    <tr class="<?php echo ($position <= 4) ? 'top-four' : ''; ?>">
                                        <td class="pos-cell"><?php echo $position++; ?></td>
                                        <td class="team-info-cell text-left">
                                            <?php if (!empty($row['teamIcon'])): ?>
                                                <img src="<?php echo htmlspecialchars($row['teamIcon']); ?>" alt="Team Logo" class="table-team-icon">
                                            <?php endif; ?>
                                            <span class="team-title"><?php echo htmlspecialchars($row['teamName']); ?></span>
                                        </td>
                                        <td><?php echo isset($row['played']) ? $row['played'] : 0; ?></td>
                                        <td><?php echo isset($row['won']) ? $row['won'] : 0; ?></td>
                                        <td><?php echo isset($row['drawn']) ? $row['drawn'] : 0; ?></td>
                                        <td><?php echo isset($row['lost']) ? $row['lost'] : 0; ?></td>
                                        <td><?php echo isset($row['gf']) ? $row['gf'] : 0; ?></td>
                                        <td><?php echo isset($row['ga']) ? $row['ga'] : 0; ?></td>
                                        <td class="gd-cell"><?php echo isset($row['gd']) ? ($row['gd'] > 0 ? '+'.$row['gd'] : $row['gd']) : 0; ?></td>
                                        <td class="pts-cell"><?php echo isset($row['points']) ? $row['points'] : 0; ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="10" class="no-data">No standings data available.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
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