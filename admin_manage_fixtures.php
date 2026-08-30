<?php
session_start();
require_once 'db.php';

// Auth Protection Check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}

$message = '';
$error = '';

// 1. Delete Fixture Logic
if (isset($_GET['delete_id'])) {
    $deleteID = intval($_GET['delete_id']);
    $stmt = $conn->prepare("DELETE FROM Fixture WHERE fixtureID = ?");
    $stmt->bind_param("i", $deleteID);
    if ($stmt->execute()) {
        $message = "Fixture deleted successfully!";
    } else {
        $error = "Failed to delete fixture.";
    }
}

// 2. Score Update Logic (Match Results Input)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_score'])) {
    $fixtureID = intval($_POST['fixture_id']);
    $homeScore = intval($_POST['home_score']);
    $awayScore = intval($_POST['away_score']);

    $stmt = $conn->prepare("UPDATE Fixture SET homeScore = ?, awayScore = ? WHERE fixtureID = ?");
    $stmt->bind_param("iii", $homeScore, $awayScore, $fixtureID);
    if ($stmt->execute()) {
        $message = "Match result updated successfully!";
    } else {
        $error = "Failed to update score.";
    }
}

// 3. Add New Fixture Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_fixture'])) {
    $homeTeam = trim($_POST['home_team']);
    $awayTeam = trim($_POST['away_team']);
    $matchDate = $_POST['match_date'];
    $matchTime = $_POST['match_time'];
    $venue = trim($_POST['venue']);

    if (!empty($homeTeam) && !empty($awayTeam) && !empty($matchDate) && !empty($matchTime)) {
        $stmt = $conn->prepare("INSERT INTO Fixture (homeTeam, awayTeam, matchDate, matchTime, venue) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $homeTeam, $awayTeam, $matchDate, $matchTime, $venue);
        if ($stmt->execute()) {
            $message = "New fixture added successfully!";
        } else {
            $error = "Error adding fixture: " . $conn->error;
        }
    } else {
        $error = "Please fill in all required fields.";
    }
}

// Fetch All Fixtures
$fixtureResult = $conn->query("SELECT * FROM Fixture ORDER BY matchDate DESC, matchTime DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KickOff - Manage Fixtures</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <header class="navbar">
        <div class="logo">
            <h2>KICK<span>OFF</span> <small style="font-size:0.6rem; color:var(--text-muted);">[ADMIN]</small></h2>
        </div>
        <nav>
            <ul class="nav-links">
                <li><a href="admin_dashboard.php">Dashboard</a></li>
                <li><a href="admin_manage_news.php">Manage News</a></li>
                <li><a href="admin_manage_fixtures.php" class="active">Manage Fixtures</a></li>
                <li><a href="admin_manage_table.php">Manage Table</a></li>
                <li><a href="admin_logout.php" style="color: #ff4d4d;">Logout</a></li>
            </ul>
        </nav>
    </header>

    <main class="container">
        <section class="section">
            <h2 class="section-title">Manage <span>Fixtures & Results</span></h2>

            <?php if (!empty($message)): ?>
                <div class="alert-success" style="color:var(--accent-lime); background:rgba(204,255,0,0.1); padding:0.8rem; border-radius:4px; margin-bottom:1rem; border:1px solid var(--accent-lime);"><?php echo $message; ?></div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="alert-error" style="margin-bottom:1rem;"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Add Fixture Form -->
            <div class="card" style="margin-bottom: 2rem;">
                <h3 style="color: var(--accent-lime); margin-bottom: 1rem;"><i class="fa-solid fa-plus"></i> Add New Fixture</h3>
                <form action="admin_manage_fixtures.php" method="POST" class="login-form">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label>Home Team</label>
                            <input type="text" name="home_team" placeholder="e.g. Arsenal" required>
                        </div>
                        <div class="form-group">
                            <label>Away Team</label>
                            <input type="text" name="away_team" placeholder="e.g. Chelsea" required>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label>Match Date</label>
                            <input type="date" name="match_date" required style="color-scheme: dark;">
                        </div>
                        <div class="form-group">
                            <label>Match Time</label>
                            <input type="time" name="match_time" required style="color-scheme: dark;">
                        </div>
                        <div class="form-group">
                            <label>Venue / Stadium</label>
                            <input type="text" name="venue" placeholder="e.g. Emirates Stadium">
                        </div>
                    </div>

                    <button type="submit" name="add_fixture" class="btn-submit">Add Fixture</button>
                </form>
            </div>

            <!-- Existing Fixtures List & Result Update -->
            <div class="card table-card">
                <h3 style="color: var(--accent-lime); padding: 1rem;"><i class="fa-solid fa-list"></i> All Fixtures & Update Scores</h3>
                <div class="table-responsive">
                    <table class="standings-table">
                        <thead>
                            <tr>
                                <th>Date & Time</th>
                                <th class="text-left">Matchup</th>
                                <th>Venue</th>
                                <th>Score (H - A)</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($fixtureResult && $fixtureResult->num_rows > 0): ?>
                                <?php while($row = $fixtureResult->fetch_assoc()): ?>
                                    <tr>
                                        <td style="font-size: 0.85rem;">
                                            <?php echo date('M d, Y', strtotime($row['matchDate'])); ?><br>
                                            <span style="color:var(--accent-lime);"><?php echo date('H:i', strtotime($row['matchTime'])); ?></span>
                                        </td>
                                        <td class="text-left" style="font-weight:600;">
                                            <?php echo htmlspecialchars($row['homeTeam']); ?> <span style="color:var(--text-muted);">vs</span> <?php echo htmlspecialchars($row['awayTeam']); ?>
                                        </td>
                                        <td style="font-size: 0.85rem; color:var(--text-muted);"><?php echo htmlspecialchars($row['venue'] ?? '-'); ?></td>
                                        
                                        <!-- Score Update Form Column -->
                                        <td>
                                            <form action="admin_manage_fixtures.php" method="POST" style="display:flex; align-items:center; justify-content:center; gap:0.3rem;">
                                                <input type="hidden" name="fixture_id" value="<?php echo $row['fixtureID']; ?>">
                                                <input type="number" name="home_score" value="<?php echo $row['homeScore'] ?? 0; ?>" min="0" style="width:45px; text-align:center; padding:0.2rem; background:var(--bg-primary); border:1px solid var(--border-color); color:#fff; border-radius:4px;">
                                                <span>-</span>
                                                <input type="number" name="away_score" value="<?php echo $row['awayScore'] ?? 0; ?>" min="0" style="width:45px; text-align:center; padding:0.2rem; background:var(--bg-primary); border:1px solid var(--border-color); color:#fff; border-radius:4px;">
                                                <button type="submit" name="update_score" title="Save Score" style="background:var(--accent-lime); border:none; padding:0.3rem 0.6rem; border-radius:4px; cursor:pointer;"><i class="fa-solid fa-check" style="color:#000;"></i></button>
                                            </form>
                                        </td>

                                        <td>
                                            <a href="admin_manage_fixtures.php?delete_id=<?php echo $row['fixtureID']; ?>" onclick="return confirm('Are you sure you want to delete this fixture?');" style="color: #ff4d4d; text-decoration:none;"><i class="fa-solid fa-trash"></i> Delete</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="no-data">No fixtures found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

    </main>

</body>
</html>