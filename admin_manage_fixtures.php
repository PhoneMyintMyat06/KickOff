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
$adminID = $_SESSION['admin_id'] ?? 1;

// 1. Add New Fixture Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_fixture'])) {
    $homeTeamID     = intval($_POST['home_team_id'] ?? 0);
    $awayTeamID     = intval($_POST['away_team_id'] ?? 0);
    $matchday       = intval($_POST['matchday'] ?? 1);
    $matchDate      = trim($_POST['match_date'] ?? '');
    $matchTime      = trim($_POST['match_time'] ?? '');
    $venue          = trim($_POST['venue'] ?? '');
    $isMatchOfWeek  = isset($_POST['is_match_of_week']) ? 1 : 0;

    if ($homeTeamID > 0 && $awayTeamID > 0 && $homeTeamID !== $awayTeamID) {
        $stmt = $conn->prepare("INSERT INTO fixture (homeTeamID, awayTeamID, matchday, matchDate, matchTime, venue, isMatchOfWeek, adminID) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiisssii", $homeTeamID, $awayTeamID, $matchday, $matchDate, $matchTime, $venue, $isMatchOfWeek, $adminID);
        
        if ($stmt->execute()) {
            $message = "New fixture scheduled successfully!";
        } else {
            $error = "Failed to add fixture: " . $conn->error;
        }
        $stmt->close();
    } else {
        $error = "Please select two different valid teams.";
    }
}

// 2. Direct Quick Toggle MOTW
if (isset($_GET['toggle_motw_id']) && isset($_GET['status'])) {
    $toggleID = intval($_GET['toggle_motw_id']);
    $newStatus = intval($_GET['status']);

    $stmt = $conn->prepare("UPDATE fixture SET isMatchOfWeek = ? WHERE fixtureID = ?");
    $stmt->bind_param("ii", $newStatus, $toggleID);
    if ($stmt->execute()) {
        $message = ($newStatus === 1) ? "Marked as Match of the Week!" : "Removed from Match of the Week!";
    } else {
        $error = "Failed to update MOTW status.";
    }
    $stmt->close();
}

// 3. Update Existing Fixture Details
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_fixture'])) {
    $fixtureID      = intval($_POST['fixture_id'] ?? 0);
    $homeTeamID     = intval($_POST['home_team_id'] ?? 0);
    $awayTeamID     = intval($_POST['away_team_id'] ?? 0);
    $matchday       = intval($_POST['matchday'] ?? 1);
    $matchDate      = trim($_POST['match_date'] ?? '');
    $matchTime      = trim($_POST['match_time'] ?? '');
    $venue          = trim($_POST['venue'] ?? '');
    $isMatchOfWeek  = isset($_POST['is_match_of_week']) ? 1 : 0;

    if ($fixtureID > 0 && $homeTeamID > 0 && $awayTeamID > 0 && $homeTeamID !== $awayTeamID) {
        $stmt = $conn->prepare("UPDATE fixture SET homeTeamID = ?, awayTeamID = ?, matchday = ?, matchDate = ?, matchTime = ?, venue = ?, isMatchOfWeek = ? WHERE fixtureID = ?");
        $stmt->bind_param("iiisssii", $homeTeamID, $awayTeamID, $matchday, $matchDate, $matchTime, $venue, $isMatchOfWeek, $fixtureID);
        
        if ($stmt->execute()) {
            $message = "Fixture updated successfully!";
        } else {
            $error = "Failed to update fixture: " . $conn->error;
        }
        $stmt->close();
    } else {
        $error = "Invalid fixture update details or teams are identical.";
    }
}

// 4. Update Match Result Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_result'])) {
    $fixtureID = intval($_POST['fixture_id'] ?? 0);
    $homeScore = (isset($_POST['home_score']) && $_POST['home_score'] !== '') ? intval($_POST['home_score']) : null;
    $awayScore = (isset($_POST['away_score']) && $_POST['away_score'] !== '') ? intval($_POST['away_score']) : null;

    if ($fixtureID > 0 && $homeScore !== null && $awayScore !== null) {
        $checkStmt = $conn->prepare("SELECT resultID FROM matchresult WHERE fixtureID = ?");
        $checkStmt->bind_param("i", $fixtureID);
        $checkStmt->execute();
        $checkRes = $checkStmt->get_result();

        if ($checkRes && $checkRes->num_rows > 0) {
            $stmt = $conn->prepare("UPDATE matchresult SET homeScore = ?, awayScore = ?, adminID = ? WHERE fixtureID = ?");
            $stmt->bind_param("iiii", $homeScore, $awayScore, $adminID, $fixtureID);
        } else {
            $stmt = $conn->prepare("INSERT INTO matchresult (homeScore, awayScore, fixtureID, adminID) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiii", $homeScore, $awayScore, $fixtureID, $adminID);
        }

        if ($stmt->execute()) {
            $message = "Match result saved successfully!";
        } else {
            $error = "Failed to save match result: " . $conn->error;
        }
        
        $checkStmt->close();
        $stmt->close();
    } else {
        $error = "Please enter valid scores for both teams.";
    }
}

// 5. Delete Fixture Logic
if (isset($_GET['delete_id'])) {
    $deleteID = intval($_GET['delete_id']);
    $stmt = $conn->prepare("DELETE FROM fixture WHERE fixtureID = ?");
    $stmt->bind_param("i", $deleteID);
    
    if ($stmt->execute()) {
        $message = "Fixture deleted successfully!";
    } else {
        $error = "Failed to delete fixture.";
    }
    $stmt->close();
}

// Fetch Teams for Dropdown
$teamsRes = $conn->query("SELECT teamID, teamName FROM Team ORDER BY teamName ASC");
$teams = [];
if ($teamsRes && $teamsRes->num_rows > 0) {
    while ($t = $teamsRes->fetch_assoc()) {
        $teams[] = $t;
    }
}

// Fetch All Fixtures safely
$sql = "SELECT f.*, 
               t1.teamName AS homeTeam, 
               t2.teamName AS awayTeam,
               mr.homeScore,
               mr.awayScore 
        FROM fixture f
        JOIN Team t1 ON f.homeTeamID = t1.teamID
        JOIN Team t2 ON f.awayTeamID = t2.teamID
        LEFT JOIN matchresult mr ON f.fixtureID = mr.fixtureID
        ORDER BY f.matchday ASC, f.matchDate DESC, f.matchTime DESC";
$fixturesResult = $conn->query($sql);
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
            <h2>KICK<span>OFF</span> <small class="admin-badge-text">[ADMIN]</small></h2>
        </div>
        <nav>
            <ul class="nav-links">
                <li><a href="admin_dashboard.php">Dashboard</a></li>
                <li><a href="admin_manage_teams.php">Manage Teams</a></li>
                <li><a href="admin_manage_news.php">Manage News</a></li>
                <li><a href="admin_manage_fixtures.php" class="active">Manage Fixtures</a></li>
                <li><a href="admin_manage_table.php">Manage Table</a></li>
                <li><a href="admin_logout.php" class="logout-link">Logout</a></li>
            </ul>
        </nav>
    </header>

    <main class="container">
        <section class="section">
            <h2 class="section-title">Manage <span>Fixtures</span></h2>

            <?php if (!empty($message)): ?>
                <div class="alert-success admin-msg-success"><?php echo $message; ?></div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="alert-error admin-msg-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Add Fixture Form -->
            <div class="card admin-fixture-add-card">
                <h3 class="admin-form-heading"><i class="fa-solid fa-calendar-plus"></i> Schedule New Match</h3>
                <form action="admin_manage_fixtures.php" method="POST" class="admin-fixture-form">
                    
                    <div class="form-group admin-fixture-form-group">
                        <label class="admin-form-label">Matchday (Week)</label>
                        <input type="number" name="matchday" min="1" max="38" value="1" required class="admin-fixture-input-date">
                    </div>

                    <div class="form-group admin-fixture-form-group">
                        <label class="admin-form-label">Home Team</label>
                        <select name="home_team_id" required class="admin-fixture-select">
                            <option value="">-- Home Team --</option>
                            <?php foreach ($teams as $t): ?>
                                <option value="<?php echo $t['teamID']; ?>"><?php echo htmlspecialchars($t['teamName']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group admin-fixture-form-group">
                        <label class="admin-form-label">Away Team</label>
                        <select name="away_team_id" required class="admin-fixture-select">
                            <option value="">-- Away Team --</option>
                            <?php foreach ($teams as $t): ?>
                                <option value="<?php echo $t['teamID']; ?>"><?php echo htmlspecialchars($t['teamName']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group admin-fixture-date-group">
                        <label class="admin-form-label">Match Date</label>
                        <input type="date" name="match_date" required class="admin-fixture-input-date">
                    </div>

                    <div class="form-group admin-fixture-time-group">
                        <label class="admin-form-label">Match Time</label>
                        <input type="time" name="match_time" required class="admin-fixture-input-time">
                    </div>

                    <div class="form-group admin-fixture-venue-group">
                        <label class="admin-form-label">Venue / Stadium</label>
                        <input type="text" name="venue" placeholder="e.g. Old Trafford" class="admin-fixture-input-venue">
                    </div>

                    <div class="form-group admin-fixture-checkbox-group">
                        <input type="checkbox" name="is_match_of_week" id="motw" value="1" class="admin-fixture-checkbox">
                        <label for="motw" class="admin-fixture-checkbox-label">Match of Week?</label>
                    </div>

                    <button type="submit" name="add_fixture" class="admin-fixture-submit-btn">Add Match</button>
                </form>
            </div>

            <!-- Manage Fixtures Table -->
            <div class="card table-card">
                <h3 class="admin-table-heading"><i class="fa-solid fa-trophy"></i> Scheduled Matches</h3>
                <div class="table-responsive">
                    <table class="admin-fixture-table">
                        <thead>
                            <tr class="admin-fixture-header-row">
                                <th class="admin-fixture-id-th">Matchday</th>
                                <th class="admin-fixture-datetime-th">Date & Time</th>
                                <th class="admin-fixture-home-th">Home</th>
                                <th class="admin-fixture-vs-th">Score / Update</th>
                                <th class="admin-fixture-away-th">Away</th>
                                <th class="admin-fixture-venue-th">Venue</th>
                                <th class="admin-fixture-special-th">Special (MOTW)</th>
                                <th class="admin-fixture-action-th">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($fixturesResult && $fixturesResult->num_rows > 0): ?>
                                <?php while($fix = $fixturesResult->fetch_assoc()): ?>
                                    <tr class="admin-fixture-body-row">
                                        <td class="admin-fixture-id-td">Week <?php echo $fix['matchday']; ?></td>
                                        <td class="admin-fixture-datetime-td">
                                            <?php echo htmlspecialchars($fix['matchDate']) . ' ' . htmlspecialchars($fix['matchTime']); ?>
                                        </td>
                                        <td class="admin-fixture-home-td">
                                            <?php echo htmlspecialchars($fix['homeTeam']); ?>
                                        </td>
                                        <td class="admin-fixture-score-td">
                                            <form action="admin_manage_fixtures.php" method="POST" class="admin-score-form">
                                                <input type="hidden" name="fixture_id" value="<?php echo $fix['fixtureID']; ?>">
                                                <input type="number" name="home_score" min="0" value="<?php echo isset($fix['homeScore']) ? $fix['homeScore'] : ''; ?>" placeholder="0" class="admin-score-input">
                                                <span class="admin-score-divider">-</span>
                                                <input type="number" name="away_score" min="0" value="<?php echo isset($fix['awayScore']) ? $fix['awayScore'] : ''; ?>" placeholder="0" class="admin-score-input">
                                                <button type="submit" name="update_result" class="admin-score-save-btn" title="Save Result"><i class="fa-solid fa-check"></i></button>
                                            </form>
                                        </td>
                                        <td class="admin-fixture-away-td">
                                            <?php echo htmlspecialchars($fix['awayTeam']); ?>
                                        </td>
                                        <td class="admin-fixture-venue-td">
                                            <?php echo htmlspecialchars($fix['venue'] ?? 'N/A'); ?>
                                        </td>
                                        <td class="admin-fixture-special-td">
                                            <?php if ($fix['isMatchOfWeek']): ?>
                                                <a href="admin_manage_fixtures.php?toggle_motw_id=<?php echo $fix['fixtureID']; ?>&status=0" 
                                                   class="motw-toggle-btn" title="Click to remove MOTW">
                                                   <span class="admin-fixture-motw-badge"><i class="fa-solid fa-star"></i> MOTW (Remove)</span>
                                                </a>
                                            <?php else: ?>
                                                <a href="admin_manage_fixtures.php?toggle_motw_id=<?php echo $fix['fixtureID']; ?>&status=1" 
                                                   class="motw-toggle-btn" title="Click to set MOTW">
                                                   <span class="admin-fixture-no-motw">+ Set MOTW</span>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                        <td class="admin-fixture-action-td">
                                            <button type="button" class="admin-score-save-btn" style="background:#007bff; margin-right:5px;" onclick='openEditModal(<?php echo json_encode($fix); ?>)'>
                                                <i class="fa-solid fa-pen-to-square"></i> Edit
                                            </button>
                                            <a href="admin_manage_fixtures.php?delete_id=<?php echo $fix['fixtureID']; ?>" onclick="return confirm('Are you sure you want to delete this match?');" class="admin-delete-link"><i class="fa-solid fa-trash"></i> Delete</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="admin-no-teams">No fixtures scheduled yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>

    <!-- Edit Fixture Modal Form -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeEditModal()">&times;</span>
            <h3 class="admin-form-heading" style="margin-bottom: 15px;"><i class="fa-solid fa-pen-to-square"></i> Edit Fixture</h3>
            
            <form action="admin_manage_fixtures.php" method="POST">
                <input type="hidden" name="fixture_id" id="edit_fixture_id">

                <div class="modal-form-group">
                    <label class="admin-form-label">Matchday (Week)</label>
                    <input type="number" name="matchday" id="edit_matchday" min="1" max="38" class="admin-form-input" required>
                </div>

                <div class="modal-form-group">
                    <label class="admin-form-label">Home Team</label>
                    <select name="home_team_id" id="edit_home_team_id" class="admin-form-select" required>
                        <?php foreach ($teams as $t): ?>
                            <option value="<?php echo $t['teamID']; ?>"><?php echo htmlspecialchars($t['teamName']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="modal-form-group">
                    <label class="admin-form-label">Away Team</label>
                    <select name="away_team_id" id="edit_away_team_id" class="admin-form-select" required>
                        <?php foreach ($teams as $t): ?>
                            <option value="<?php echo $t['teamID']; ?>"><?php echo htmlspecialchars($t['teamName']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="modal-form-group">
                    <label class="admin-form-label">Match Date</label>
                    <input type="date" name="match_date" id="edit_match_date" class="admin-form-input" required>
                </div>

                <div class="modal-form-group">
                    <label class="admin-form-label">Match Time</label>
                    <input type="time" name="match_time" id="edit_match_time" class="admin-form-input" required>
                </div>

                <div class="modal-form-group">
                    <label class="admin-form-label">Venue / Stadium</label>
                    <input type="text" name="venue" id="edit_venue" class="admin-form-input">
                </div>

                <div class="admin-fixture-checkbox-group" style="margin-top: 10px;">
                    <input type="checkbox" name="is_match_of_week" id="edit_motw" value="1" class="admin-fixture-checkbox">
                    <label for="edit_motw" class="admin-fixture-checkbox-label">Match of Week (MOTW)</label>
                </div>

                <div class="modal-footer-btns">
                    <button type="button" class="btn-modal-close" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" name="edit_fixture" class="btn-modal-save">
                        <i class="fa-solid fa-check"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEditModal(data) {
            document.getElementById('edit_fixture_id').value = data.fixtureID;
            document.getElementById('edit_matchday').value = data.matchday;
            document.getElementById('edit_home_team_id').value = data.homeTeamID;
            document.getElementById('edit_away_team_id').value = data.awayTeamID;
            document.getElementById('edit_match_date').value = data.matchDate;
            document.getElementById('edit_match_time').value = data.matchTime;
            document.getElementById('edit_venue').value = data.venue;
            document.getElementById('edit_motw').checked = (parseInt(data.isMatchOfWeek) === 1);

            document.getElementById('editModal').style.display = 'block';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        window.onclick = function(event) {
            var modal = document.getElementById('editModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>