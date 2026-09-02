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

// 1. Add New Team Logic (with File Upload)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_team'])) {
    $teamName = trim($_POST['team_name']);
    $teamIconPath = 'uploads/default_team.png'; // Default Icon Path

    // File Upload Handling
    if (isset($_FILES['team_icon']) && $_FILES['team_icon']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['team_icon']['tmp_name'];
        $fileName = $_FILES['team_icon']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $allowedExtensions = ['png', 'jpg', 'jpeg', 'webp', 'svg'];

        if (in_array($fileExtension, $allowedExtensions)) {
            $newFileName = time() . '_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $teamName) . '.' . $fileExtension;
            $uploadFileDir = 'uploads/';

            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0755, true);
            }

            $dest_path = $uploadFileDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $teamIconPath = $dest_path;
            } else {
                $error = "There was an error moving the uploaded file.";
            }
        } else {
            $error = "Invalid file type. Only PNG, JPG, JPEG, WEBP, SVG are allowed.";
        }
    }

    if (empty($error) && !empty($teamName)) {
        if (tableExists($conn, 'Team')) {
            $stmt = $conn->prepare("INSERT INTO Team (teamName, teamIcon) VALUES (?, ?)");
            $stmt->bind_param("ss", $teamName, $teamIconPath);

            if ($stmt->execute()) {
                $message = "Team '$teamName' added successfully!";
            } else {
                $error = "Database Error: " . $conn->error;
            }
            $stmt->close();
        } else {
            $error = "Table 'Team' does not exist in the database.";
        }
    }
}

// 2. Edit Team Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_team'])) {
    $editID = intval($_POST['team_id']);
    $editTeamName = trim($_POST['team_name']);
    
    // Get current icon first
    $getIconStmt = $conn->prepare("SELECT teamIcon FROM Team WHERE teamID = ?");
    $getIconStmt->bind_param("i", $editID);
    $getIconStmt->execute();
    $res = $getIconStmt->get_result();
    $currentIcon = 'uploads/default_team.png';
    if ($row = $res->fetch_assoc()) {
        $currentIcon = $row['teamIcon'];
    }
    $getIconStmt->close();

    $teamIconPath = $currentIcon;

    // Check if new file is uploaded
    if (isset($_FILES['team_icon']) && $_FILES['team_icon']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['team_icon']['tmp_name'];
        $fileName = $_FILES['team_icon']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExtensions = ['png', 'jpg', 'jpeg', 'webp', 'svg'];

        if (in_array($fileExtension, $allowedExtensions)) {
            $newFileName = time() . '_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $editTeamName) . '.' . $fileExtension;
            $uploadFileDir = 'uploads/';

            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0755, true);
            }

            $dest_path = $uploadFileDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                // Delete old icon if not default
                if (!empty($currentIcon) && file_exists($currentIcon) && strpos($currentIcon, 'default') === false) {
                    unlink($currentIcon);
                }
                $teamIconPath = $dest_path;
            }
        }
    }

    if (!empty($editTeamName)) {
        $stmt = $conn->prepare("UPDATE Team SET teamName = ?, teamIcon = ? WHERE teamID = ?");
        $stmt->bind_param("ssi", $editTeamName, $teamIconPath, $editID);
        if ($stmt->execute()) {
            $message = "Team updated successfully!";
        } else {
            $error = "Failed to update team.";
        }
        $stmt->close();
    } else {
        $error = "Team name cannot be empty.";
    }
}

// 3. Delete Team Logic
if (isset($_GET['delete_id'])) {
    $deleteID = intval($_GET['delete_id']);
    
    $getIconStmt = $conn->prepare("SELECT teamIcon FROM Team WHERE teamID = ?");
    $getIconStmt->bind_param("i", $deleteID);
    $getIconStmt->execute();
    $res = $getIconStmt->get_result();
    if ($row = $res->fetch_assoc()) {
        if (!empty($row['teamIcon']) && file_exists($row['teamIcon']) && strpos($row['teamIcon'], 'default') === false) {
            unlink($row['teamIcon']);
        }
    }
    $getIconStmt->close();

    $stmt = $conn->prepare("DELETE FROM Team WHERE teamID = ?");
    $stmt->bind_param("i", $deleteID);
    if ($stmt->execute()) {
        $message = "Team deleted successfully!";
    } else {
        $error = "Failed to delete team.";
    }
    $stmt->close();
}

// Fetch All Teams
$teams = [];
if (tableExists($conn, 'Team')) {
    $result = $conn->query("SELECT * FROM Team ORDER BY teamName ASC");
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $teams[] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KickOff - Manage Teams</title>
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
                <li><a href="admin_manage_teams.php" class="active">Manage Teams</a></li>
                <li><a href="admin_manage_news.php">Manage News</a></li>
                <li><a href="admin_manage_fixtures.php">Manage Fixtures</a></li>
                <li><a href="admin_manage_table.php">Manage Table</a></li>
                <li><a href="admin_logout.php" class="logout-link">Logout</a></li>
            </ul>
        </nav>
    </header>

    <main class="container">
        <section class="section">
            <h2 class="section-title">Manage <span>Teams</span></h2>

            <?php if (!empty($message)): ?>
                <div class="alert-success admin-msg-success"><?php echo $message; ?></div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="alert-error admin-msg-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Add Team Form -->
            <div class="card admin-form-card">
                <h3 class="admin-form-heading"><i class="fa-solid fa-plus-circle"></i> Add New Team</h3>
                <form action="admin_manage_teams.php" method="POST" enctype="multipart/form-data" class="admin-team-form">
                    
                    <div class="form-group admin-input-group">
                        <label class="admin-form-label">Team Name</label>
                        <input type="text" name="team_name" required placeholder="e.g. Manchester United" class="admin-form-input">
                    </div>

                    <div class="form-group admin-input-group-file">
                        <label class="admin-form-label">Team Logo / Icon (Choose File)</label>
                        <input type="file" name="team_icon" accept="image/*" required class="admin-form-input-file">
                    </div>

                    <button type="submit" name="add_team" class="admin-submit-btn">Add Team</button>
                </form>
            </div>

            <!-- Teams List Table -->
            <div class="card table-card">
                <h3 class="admin-table-heading"><i class="fa-solid fa-shield-halved"></i> Registered Teams</h3>
                <div class="table-responsive">
                    <table class="admin-teams-table">
                        <thead>
                            <tr class="admin-table-header-row">
                                <th class="admin-th-id">ID</th>
                                <th class="admin-th-logo">Logo</th>
                                <th class="admin-th-name">Team Name</th>
                                <th class="admin-th-action">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($teams)): ?>
                                <?php foreach ($teams as $team): ?>
                                    <tr class="admin-table-body-row">
                                        <td class="admin-td-id"><?php echo $team['teamID']; ?></td>
                                        <td class="admin-td-logo">
                                            <?php if (!empty($team['teamIcon']) && file_exists($team['teamIcon'])): ?>
                                                <div class="admin-team-logo-badge">
                                                    <img src="<?php echo htmlspecialchars($team['teamIcon']); ?>" alt="Logo" class="admin-team-logo-img">
                                                </div>
                                            <?php else: ?>
                                                <i class="fa-solid fa-shield-cat admin-fallback-icon"></i>
                                            <?php endif; ?>
                                        </td>
                                        <td class="admin-td-name">
                                            <?php echo htmlspecialchars($team['teamName']); ?>
                                        </td>
                                        <td class="admin-td-action" style="display: flex; gap: 10px; align-items: center; justify-content: center;">
                                            <!-- Edit Button triggers modal -->
                                            <button onclick="openEditModal(<?php echo $team['teamID']; ?>, '<?php echo htmlspecialchars($team['teamName'], ENT_QUOTES); ?>')" class="admin-edit-btn" style="background: var(--accent-lime); color: #000; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; font-weight: bold;"><i class="fa-solid fa-pen-to-square"></i> Edit</button>
                                            
                                            <a href="admin_manage_teams.php?delete_id=<?php echo $team['teamID']; ?>" onclick="return confirm('Are you sure you want to delete this team?');" class="admin-delete-link"><i class="fa-solid fa-trash"></i> Delete</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="admin-no-teams">No teams added yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>

    <!-- Edit Modal Box -->
    <div id="editTeamModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); z-index:1000; justify-content:center; align-items:center;">
        <div class="card" style="width: 100%; max-width: 400px; padding: 2rem; position: relative; background: var(--bg-secondary, #1a1a1a); border: 1px solid var(--border-color, #333);">
            <h3 class="admin-form-heading" style="margin-bottom: 1.5rem;"><i class="fa-solid fa-pen-to-square"></i> Edit Team</h3>
            <form action="admin_manage_teams.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="team_id" id="edit_team_id">
                
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label class="admin-form-label">Team Name</label>
                    <input type="text" name="team_name" id="edit_team_name" required class="admin-form-input" style="width:100%; padding:0.6rem; background:var(--bg-primary); border:1px solid var(--border-color); color:#fff; border-radius:4px;">
                </div>

                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label class="admin-form-label">New Logo / Icon (Optional)</label>
                    <input type="file" name="team_icon" accept="image/*" class="admin-form-input-file" style="width:100%;">
                </div>

                <div style="display: flex; gap: 10px;">
                    <button type="submit" name="edit_team" class="admin-submit-btn" style="flex: 1;">Update Team</button>
                    <button type="button" onclick="closeEditModal()" style="background: #444; color: #fff; border: none; padding: 0.6rem 1rem; border-radius: 4px; cursor: pointer; flex: 1;">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEditModal(id, name) {
            document.getElementById('edit_team_id').value = id;
            document.getElementById('edit_team_name').value = name;
            document.getElementById('editTeamModal').style.display = 'flex';
        }

        function closeEditModal() {
            document.getElementById('editTeamModal').style.display = 'none';
        }
    </script>
</body>
</html>