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

// Helper Function: Check Table
function tableExists($conn, $tableName) {
    $res = $conn->query("SHOW TABLES LIKE '$tableName'");
    return ($res && $res->num_rows > 0);
}

// Auto Recalculate Table Stats from Fixtures & MatchResult History
if (isset($_POST['recalculate_table'])) {
    if (tableExists($conn, 'leaguetable') && tableExists($conn, 'fixture') && tableExists($conn, 'matchresult')) {
        // Reset all table stats to 0 first
        $conn->query("UPDATE leaguetable SET played=0, won=0, drawn=0, lost=0, gf=0, ga=0, gd=0, points=0");

        // Fetch finished fixtures with scores from matchresult table
        $sql = "SELECT f.homeTeamID, f.awayTeamID, mr.homeScore, mr.awayScore 
                FROM fixture f 
                JOIN matchresult mr ON f.fixtureID = mr.fixtureID 
                WHERE mr.homeScore IS NOT NULL AND mr.awayScore IS NOT NULL";
        $fixturesRes = $conn->query($sql);
        
        if ($fixturesRes && $fixturesRes->num_rows > 0) {
            while ($fix = $fixturesRes->fetch_assoc()) {
                $hID = $fix['homeTeamID'];
                $aID = $fix['awayTeamID'];
                $hScore = intval($fix['homeScore']);
                $aScore = intval($fix['awayScore']);

                // Home Team Outcome
                $hWon = ($hScore > $aScore) ? 1 : 0;
                $hDrawn = ($hScore == $aScore) ? 1 : 0;
                $hLost = ($hScore < $aScore) ? 1 : 0;
                $hPts = ($hWon * 3) + ($hDrawn * 1);

                // Away Team Outcome
                $aWon = ($aScore > $hScore) ? 1 : 0;
                $aDrawn = ($aScore == $hScore) ? 1 : 0;
                $aLost = ($aScore < $hScore) ? 1 : 0;
                $aPts = ($aWon * 3) + ($aDrawn * 1);

                // Update Home Team
                $conn->query("UPDATE leaguetable SET 
                    played = played + 1, won = won + $hWon, drawn = drawn + $hDrawn, lost = lost + $hLost,
                    gf = gf + $hScore, ga = ga + $aScore, gd = gf - ga, points = points + $hPts 
                    WHERE teamID = $hID");

                // Update Away Team
                $conn->query("UPDATE leaguetable SET 
                    played = played + 1, won = won + $aWon, drawn = drawn + $aDrawn, lost = lost + $aLost,
                    gf = gf + $aScore, ga = ga + $hScore, gd = gf - ga, points = points + $aPts 
                    WHERE teamID = $aID");
            }

            // Final pass to ensure all GD values match exact (gf - ga) for safety
            $conn->query("UPDATE leaguetable SET gd = gf - ga");

            $message = "League table synchronized successfully from match results!";
        } else {
            $error = "No match results found in database to synchronize stats.";
        }
    } else {
        $error = "Required database tables do not exist.";
    }
}

// 1. Delete Team from Standings
if (isset($_GET['delete_id'])) {
    $deleteID = intval($_GET['delete_id']);
    if (tableExists($conn, 'leaguetable')) {
        $stmt = $conn->prepare("DELETE FROM leaguetable WHERE tableID = ?");
        $stmt->bind_param("i", $deleteID);
        if ($stmt->execute()) {
            $message = "Team removed from standings table!";
        } else {
            $error = "Failed to remove team.";
        }
        $stmt->close();
    }
}

// 2. Add Team to Table
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_team'])) {
    $teamID = intval($_POST['team_id']);

    if ($teamID > 0 && tableExists($conn, 'leaguetable')) {
        $checkStmt = $conn->prepare("SELECT tableID FROM leaguetable WHERE teamID = ?");
        $checkStmt->bind_param("i", $teamID);
        $checkStmt->execute();
        if ($checkStmt->get_result()->num_rows > 0) {
            $error = "Team already exists in table!";
        } else {
            $stmt = $conn->prepare("INSERT INTO leaguetable (teamID, played, won, drawn, lost, gf, ga, gd, points) VALUES (?, 0, 0, 0, 0, 0, 0, 0, 0)");
            $stmt->bind_param("i", $teamID);
            if ($stmt->execute()) {
                $message = "Team added to standings!";
            } else {
                $error = "Error adding team: " . $conn->error;
            }
            $stmt->close();
        }
        $checkStmt->close();
    } else {
        $error = "Please select a valid team.";
    }
}

// Fetch Registered Teams
$teamsListRes = tableExists($conn, 'Team') ? $conn->query("SELECT teamID, teamName FROM Team ORDER BY teamName ASC") : null;

// Fetch Standings
$tableResult = null;
if (tableExists($conn, 'leaguetable') && tableExists($conn, 'Team')) {
    $sql = "SELECT lt.*, t.teamName, t.teamIcon 
            FROM leaguetable lt 
            JOIN Team t ON lt.teamID = t.teamID 
            ORDER BY lt.points DESC, lt.gd DESC, lt.won DESC";
    $tableResult = $conn->query($sql);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KickOff - Manage League Table</title>
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
                <li><a href="admin_manage_fixtures.php">Manage Fixtures</a></li>
                <li><a href="admin_manage_table.php" class="active">Manage Table</a></li>
                <li><a href="admin_logout.php" class="logout-link">Logout</a></li>
            </ul>
        </nav>
    </header>

    <main class="container">
        <section class="section">
            <h2 class="section-title">Manage <span>League Table</span></h2>

            <?php if (!empty($message)): ?>
                <div class="alert-success admin-msg-success"><?php echo $message; ?></div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="alert-error admin-msg-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="admin-table-top-grid">
                <!-- Add Team Form -->
                <div class="card admin-table-add-card">
                    <h3 class="admin-form-heading"><i class="fa-solid fa-plus-circle"></i> Add Team to Standings</h3>
                    <form action="admin_manage_table.php" method="POST" class="admin-table-add-form">
                        <div class="form-group admin-table-select-group">
                            <label class="admin-form-label">Select Registered Team</label>
                            <select name="team_id" required class="admin-form-select">
                                <option value="">-- Choose Team --</option>
                                <?php if($teamsListRes): while ($t = $teamsListRes->fetch_assoc()): ?>
                                    <option value="<?php echo $t['teamID']; ?>"><?php echo htmlspecialchars($t['teamName']); ?></option>
                                <?php endwhile; endif; ?>
                            </select>
                        </div>
                        <button type="submit" name="add_team" class="admin-submit-btn admin-table-add-btn">Add to Table</button>
                    </form>
                </div>

                <!-- Synchronize Standings Card -->
                <div class="card admin-sync-card">
                    <h3 class="admin-form-heading"><i class="fa-solid fa-rotate"></i> Synchronize Standings</h3>
                    <p class="admin-sync-desc">Recalculate points & goals automatically from finished fixtures.</p>
                    <form action="admin_manage_table.php" method="POST">
                        <button type="submit" name="recalculate_table" class="admin-sync-btn"><i class="fa-solid fa-arrows-rotate"></i> Synchronize Standings</button>
                    </form>
                </div>
            </div>

            <!-- Existing Standings Table -->
            <div class="card table-card">
                <h3 class="admin-table-heading"><i class="fa-solid fa-list-ol"></i> Standings Overview</h3>
                <div class="table-responsive">
                    <table class="standings-table admin-manage-standings-table">
                        <thead>
                            <tr class="admin-standings-header-row">
                                <th class="admin-pos-th">POS</th>
                                <th class="admin-club-th">CLUB</th>
                                <th class="admin-stat-th">MP</th>
                                <th class="admin-stat-th">W</th>
                                <th class="admin-stat-th">D</th>
                                <th class="admin-stat-th">L</th>
                                <th class="admin-stat-th">GF</th>
                                <th class="admin-stat-th">GA</th>
                                <th class="admin-stat-th">GD</th>
                                <th class="admin-stat-th">PTS</th>
                                <th class="admin-action-th">ACTION</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($tableResult && $tableResult->num_rows > 0): $pos = 1; ?>
                                <?php while($row = $tableResult->fetch_assoc()): ?>
                                    <tr class="admin-standings-body-row">
                                        <td class="admin-pos-td"><strong><?php echo $pos++; ?></strong></td>
                                        <td class="admin-club-td">
                                            <div class="admin-club-info-flex">
                                                <?php if(!empty($row['teamIcon'])): ?>
                                                    <img src="<?php echo htmlspecialchars($row['teamIcon']); ?>" alt="logo" class="admin-club-icon-img">
                                                <?php endif; ?>
                                                <span><?php echo htmlspecialchars($row['teamName']); ?></span>
                                            </div>
                                        </td>
                                        
                                        <!-- Plain text stats view -->
                                        <td class="admin-stat-td"><?php echo $row['played']; ?></td>
                                        <td class="admin-stat-td"><?php echo $row['won']; ?></td>
                                        <td class="admin-stat-td"><?php echo $row['drawn']; ?></td>
                                        <td class="admin-stat-td"><?php echo $row['lost']; ?></td>
                                        <td class="admin-stat-td"><?php echo $row['gf']; ?></td>
                                        <td class="admin-stat-td"><?php echo $row['ga']; ?></td>
                                        <td class="admin-gd-td"><?php echo $row['gd']; ?></td>
                                        <td class="admin-pts-td"><?php echo $row['points']; ?></td>

                                        <td class="admin-action-td">
                                            <a href="admin_manage_table.php?delete_id=<?php echo $row['tableID']; ?>" onclick="return confirm('Are you sure you want to remove this team from standings?');" class="admin-delete-link"><i class="fa-solid fa-trash"></i></a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="11" class="admin-no-teams">No teams found in the table.</td>
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