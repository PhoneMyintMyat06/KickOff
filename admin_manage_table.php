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

// 1. Delete Team Logic
if (isset($_GET['delete_id'])) {
    $deleteID = intval($_GET['delete_id']);
    $stmt = $conn->prepare("DELETE FROM leaguetable WHERE tableID = ?");
    $stmt->bind_param("i", $deleteID);
    if ($stmt->execute()) {
        $message = "Team deleted successfully from table!";
    } else {
        $error = "Failed to delete team.";
    }
}

// 2. Update Team Stats Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_team'])) {
    $tableID = intval($_POST['team_id']);
    $played = intval($_POST['played']);
    $won = intval($_POST['won']);
    $drawn = intval($_POST['drawn']);
    $lost = intval($_POST['lost']);
    $gf = intval($_POST['gf']);
    $ga = intval($_POST['ga']);

    // Auto-calculate Points and Goal Difference
    $gd = $gf - $ga;
    $pts = ($won * 3) + ($drawn * 1);

    $stmt = $conn->prepare("UPDATE leaguetable SET played = ?, won = ?, drawn = ?, lost = ?, gf = ?, ga = ?, gd = ?, points = ? WHERE tableID = ?");
    $stmt->bind_param("iiiiiiiii", $played, $won, $drawn, $lost, $gf, $ga, $gd, $pts, $tableID);
    
    if ($stmt->execute()) {
        $message = "Team stats updated successfully!";
    } else {
        $error = "Failed to update stats.";
    }
}

// 3. Add New Team Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_team'])) {
    $teamName = trim($_POST['team_name']);

    if (!empty($teamName)) {
        $stmt = $conn->prepare("INSERT INTO leaguetable (teamName, played, won, drawn, lost, gf, ga, gd, points) VALUES (?, 0, 0, 0, 0, 0, 0, 0, 0)");
        $stmt->bind_param("s", $teamName);
        if ($stmt->execute()) {
            $message = "New team added to league table!";
        } else {
            $error = "Error adding team: " . $conn->error;
        }
    } else {
        $error = "Team Name is required.";
    }
}

// Fetch All Teams Sorted by Points & GD
$tableResult = $conn->query("SELECT * FROM leaguetable ORDER BY points DESC, gd DESC, won DESC");
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
            <h2>KICK<span>OFF</span> <small style="font-size:0.6rem; color:var(--text-muted);">[ADMIN]</small></h2>
        </div>
        <nav>
            <ul class="nav-links">
                <li><a href="admin_dashboard.php">Dashboard</a></li>
                <li><a href="admin_manage_news.php">Manage News</a></li>
                <li><a href="admin_manage_fixtures.php">Manage Fixtures</a></li>
                <li><a href="admin_manage_table.php" class="active">Manage Table</a></li>
                <li><a href="admin_logout.php" style="color: #ff4d4d;">Logout</a></li>
            </ul>
        </nav>
    </header>

    <main class="container">
        <section class="section">
            <h2 class="section-title">Manage <span>League Table</span></h2>

            <?php if (!empty($message)): ?>
                <div class="alert-success" style="color:var(--accent-lime); background:rgba(204,255,0,0.1); padding:0.8rem; border-radius:4px; margin-bottom:1rem; border:1px solid var(--accent-lime);"><?php echo $message; ?></div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="alert-error" style="margin-bottom:1rem;"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Add Team Form -->
            <div class="card" style="margin-bottom: 2rem;">
                <h3 style="color: var(--accent-lime); margin-bottom: 1rem;"><i class="fa-solid fa-shield-halved"></i> Add New Team</h3>
                <form action="admin_manage_table.php" method="POST" class="login-form" style="display:flex; gap:1rem; align-items:flex-end;">
                    <div class="form-group" style="flex:1; margin:0;">
                        <label>Team Name</label>
                        <input type="text" name="team_name" placeholder="e.g. Manchester City" required>
                    </div>
                    <button type="submit" name="add_team" class="btn-submit" style="width:auto; padding:0.8rem 1.5rem;">Add Team</button>
                </form>
            </div>

            <!-- Existing Standings Table with Inline Edit -->
            <div class="card table-card">
                <h3 style="color: var(--accent-lime); padding: 1rem;"><i class="fa-solid fa-list-ol"></i> Standings & Update Stats</h3>
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
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($tableResult && $tableResult->num_rows > 0): $pos = 1; ?>
                                <?php while($row = $tableResult->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?php echo $pos++; ?></strong></td>
                                        <td class="text-left" style="font-weight:600; color:var(--accent-lime);">
                                            <?php echo htmlspecialchars($row['teamName']); ?>
                                        </td>

                                        <form action="admin_manage_table.php" method="POST">
                                            <input type="hidden" name="team_id" value="<?php echo $row['tableID']; ?>">
                                            
                                            <td><input type="number" name="played" value="<?php echo $row['played']; ?>" style="width:40px; text-align:center; background:var(--bg-primary); border:1px solid var(--border-color); color:#fff;"></td>
                                            <td><input type="number" name="won" value="<?php echo $row['won']; ?>" style="width:40px; text-align:center; background:var(--bg-primary); border:1px solid var(--border-color); color:#fff;"></td>
                                            <td><input type="number" name="drawn" value="<?php echo $row['drawn']; ?>" style="width:40px; text-align:center; background:var(--bg-primary); border:1px solid var(--border-color); color:#fff;"></td>
                                            <td><input type="number" name="lost" value="<?php echo $row['lost']; ?>" style="width:40px; text-align:center; background:var(--bg-primary); border:1px solid var(--border-color); color:#fff;"></td>
                                            <td><input type="number" name="gf" value="<?php echo $row['gf']; ?>" style="width:40px; text-align:center; background:var(--bg-primary); border:1px solid var(--border-color); color:#fff;"></td>
                                            <td><input type="number" name="ga" value="<?php echo $row['ga']; ?>" style="width:40px; text-align:center; background:var(--bg-primary); border:1px solid var(--border-color); color:#fff;"></td>
                                            
                                            <td style="font-size:0.85rem; color:var(--text-muted);"><?php echo $row['gd']; ?></td>
                                            <td style="font-weight:bold; color:var(--accent-lime);"><?php echo $row['points']; ?></td>

                                            <td>
                                                <button type="submit" name="update_team" title="Save Changes" style="background:var(--accent-lime); border:none; padding:0.3rem 0.6rem; border-radius:4px; cursor:pointer;"><i class="fa-solid fa-floppy-disk" style="color:#000;"></i></button>
                                                <a href="admin_manage_table.php?delete_id=<?php echo $row['tableID']; ?>" onclick="return confirm('Are you sure you want to delete this team?');" style="color: #ff4d4d; text-decoration:none; margin-left:0.5rem;"><i class="fa-solid fa-trash"></i></a>
                                            </td>
                                        </form>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="11" class="no-data">No teams found in the table.</td>
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