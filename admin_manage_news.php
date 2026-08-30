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

// 1. Delete News Logic
if (isset($_GET['delete_id'])) {
    $deleteID = intval($_GET['delete_id']);
    $stmt = $conn->prepare("DELETE FROM newsarticle WHERE newsID = ?");
    $stmt->bind_param("i", $deleteID);
    if ($stmt->execute()) {
        $message = "News article deleted successfully!";
    } else {
        $error = "Failed to delete article.";
    }
}

// 2. Add New Article Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_news'])) {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $isTopNews = isset($_POST['is_top_news']) ? 1 : 0;
    $adminID = $_SESSION['admin_id'] ?? 1; // Fallback to 1 if session is not set
    $publishDate = date('Y-m-d H:i:s');
    $imageName = '';

    // Image Upload Handling
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $targetDir = "uploads/news/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        $imageName = time() . '_' . basename($_FILES['image']['name']);
        $targetFile = $targetDir . $imageName;
        move_uploaded_file($_FILES['image']['tmp_name'], $targetFile);
    }

    if (!empty($title) && !empty($content)) {
        $stmt = $conn->prepare("INSERT INTO newsarticle (title, content, publishDate, image, adminID, isTopNews) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssii", $title, $content, $publishDate, $imageName, $adminID, $isTopNews);
        if ($stmt->execute()) {
            $message = "News article published successfully!";
        } else {
            $error = "Failed to add news article: " . $conn->error;
        }
    } else {
        $error = "Title and Content are required fields.";
    }
}

// Fetch All News ordered by publishDate & newsID
$newsResult = $conn->query("SELECT * FROM newsarticle ORDER BY publishDate DESC, newsID DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KickOff - Manage News</title>
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
                <li><a href="admin_manage_news.php" class="active">Manage News</a></li>
                <li><a href="admin_manage_fixtures.php">Manage Fixtures</a></li>
                <li><a href="admin_manage_table.php">Manage Table</a></li>
                <li><a href="admin_logout.php" style="color: #ff4d4d;">Logout</a></li>
            </ul>
        </nav>
    </header>

    <main class="container">
        <section class="section">
            <h2 class="section-title">Manage <span>News Articles</span></h2>

            <?php if (!empty($message)): ?>
                <div class="alert-success" style="color:var(--accent-lime); background:rgba(204,255,0,0.1); padding:0.8rem; border-radius:4px; margin-bottom:1rem; border:1px solid var(--accent-lime);"><?php echo $message; ?></div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="alert-error" style="margin-bottom:1rem;"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Add News Form -->
            <div class="card" style="margin-bottom: 2rem;">
                <h3 style="color: var(--accent-lime); margin-bottom: 1rem;"><i class="fa-solid fa-plus"></i> Post New Article</h3>
                <form action="admin_manage_news.php" method="POST" enctype="multipart/form-data" class="login-form">
                    <div class="form-group">
                        <label>Title</label>
                        <input type="text" name="title" required placeholder="Enter article title">
                    </div>
                    <div class="form-group">
                        <label>Content</label>
                        <textarea name="content" rows="5" required style="background:var(--bg-primary); border:1px solid var(--border-color); color:var(--text-main); padding:0.8rem; border-radius:4px; font-family:inherit;" placeholder="Write full article here..."></textarea>
                    </div>
                    <div class="form-group">
                        <label>Featured Image</label>
                        <input type="file" name="image" accept="image/*">
                    </div>
                    <div class="form-group" style="display:flex; align-items:center; gap:0.5rem; margin-bottom:1.5rem;">
                        <input type="checkbox" name="is_top_news" id="is_top_news" style="width:auto; cursor:pointer;">
                        <label for="is_top_news" style="margin:0; cursor:pointer; color:var(--accent-lime);">Set as Top News / Breaking News</label>
                    </div>
                    <button type="submit" name="add_news" class="btn-submit">Publish Article</button>
                </form>
            </div>

            <!-- Existing News List -->
            <div class="card table-card">
                <h3 style="color: var(--accent-lime); padding: 1rem;"><i class="fa-solid fa-list"></i> Existing Articles</h3>
                <div class="table-responsive">
                    <table class="standings-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th class="text-left">Title</th>
                                <th>Publish Date</th>
                                <th>Top News</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($newsResult && $newsResult->num_rows > 0): ?>
                                <?php while($row = $newsResult->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $row['newsID']; ?></td>
                                        <td class="text-left" style="font-weight:600;"><?php echo htmlspecialchars($row['title']); ?></td>
                                        <td style="font-size:0.85rem; color:var(--text-muted);"><?php echo date('M d, Y H:i', strtotime($row['publishDate'])); ?></td>
                                        <td>
                                            <?php if ($row['isTopNews']): ?>
                                                <span style="color:var(--accent-lime); font-weight:bold;"><i class="fa-solid fa-star"></i> Yes</span>
                                            <?php else: ?>
                                                <span style="color:var(--text-muted);">No</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="admin_manage_news.php?delete_id=<?php echo $row['newsID']; ?>" onclick="return confirm('Are you sure you want to delete this article?');" style="color: #ff4d4d; text-decoration:none;"><i class="fa-solid fa-trash"></i> Delete</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="no-data">No news articles found.</td>
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