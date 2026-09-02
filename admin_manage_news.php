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

// 1. Delete News Logic (File Unlink ပါဝင်သည်)
if (isset($_GET['delete_id'])) {
    $deleteID = intval($_GET['delete_id']);
    
    $getImgStmt = $conn->prepare("SELECT image FROM newsarticle WHERE newsID = ?");
    $getImgStmt->bind_param("i", $deleteID);
    $getImgStmt->execute();
    $res = $getImgStmt->get_result();
    if ($row = $res->fetch_assoc()) {
        if (!empty($row['image']) && file_exists("uploads/news/" . $row['image'])) {
            unlink("uploads/news/" . $row['image']);
        }
    }
    $getImgStmt->close();

    $stmt = $conn->prepare("DELETE FROM newsarticle WHERE newsID = ?");
    $stmt->bind_param("i", $deleteID);
    if ($stmt->execute()) {
        $message = "News article deleted successfully!";
    } else {
        $error = "Failed to delete article.";
    }
    $stmt->close();
}

// 2. Quick Toggle Top News Logic
if (isset($_GET['toggle_top_id'])) {
    $toggleID = intval($_GET['toggle_top_id']);
    $currentStatus = intval($_GET['status']);
    $newStatus = ($currentStatus === 1) ? 0 : 1;

    $stmt = $conn->prepare("UPDATE newsarticle SET isTopNews = ? WHERE newsID = ?");
    $stmt->bind_param("ii", $newStatus, $toggleID);
    if ($stmt->execute()) {
        $message = "Top news status updated successfully!";
    } else {
        $error = "Failed to update top news status.";
    }
    $stmt->close();
}

// 3. Add New Article Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_news'])) {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $isTopNews = isset($_POST['is_top_news']) ? 1 : 0;
    $adminID = $_SESSION['admin_id'] ?? 1;
    $publishDate = date('Y-m-d H:i:s');
    $imageName = '';

    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $targetDir = "uploads/news/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $imageName = time() . '_' . uniqid() . '.' . $ext;
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
        $stmt->close();
    } else {
        $error = "Title and Content are required fields.";
    }
}

// 4. Edit Article Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_news'])) {
    $newsID = intval($_POST['news_id']);
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $isTopNews = isset($_POST['is_top_news']) ? 1 : 0;

    if (!empty($title) && !empty($content)) {
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $targetDir = "uploads/news/";
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0777, true);
            }

            $getImgStmt = $conn->prepare("SELECT image FROM newsarticle WHERE newsID = ?");
            $getImgStmt->bind_param("i", $newsID);
            $getImgStmt->execute();
            $res = $getImgStmt->get_result();
            if ($row = $res->fetch_assoc()) {
                if (!empty($row['image']) && file_exists("uploads/news/" . $row['image'])) {
                    unlink("uploads/news/" . $row['image']);
                }
            }
            $getImgStmt->close();

            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $imageName = time() . '_' . uniqid() . '.' . $ext;
            $targetFile = $targetDir . $imageName;
            move_uploaded_file($_FILES['image']['tmp_name'], $targetFile);

            $stmt = $conn->prepare("UPDATE newsarticle SET title = ?, content = ?, image = ?, isTopNews = ? WHERE newsID = ?");
            $stmt->bind_param("sssii", $title, $content, $imageName, $isTopNews, $newsID);
        } else {
            $stmt = $conn->prepare("UPDATE newsarticle SET title = ?, content = ?, isTopNews = ? WHERE newsID = ?");
            $stmt->bind_param("ssii", $title, $content, $isTopNews, $newsID);
        }

        if ($stmt->execute()) {
            $message = "News article updated successfully!";
        } else {
            $error = "Failed to update article: " . $conn->error;
        }
        $stmt->close();
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
            <h2>KICK<span>OFF</span> <small class="admin-badge-text">[ADMIN]</small></h2>
        </div>
        <nav>
            <ul class="nav-links">
                <li><a href="admin_dashboard.php">Dashboard</a></li>
                <li><a href="admin_manage_teams.php">Manage Teams</a></li>
                <li><a href="admin_manage_news.php" class="active">Manage News</a></li>
                <li><a href="admin_manage_fixtures.php">Manage Fixtures</a></li>
                <li><a href="admin_manage_table.php">Manage Table</a></li>
                <li><a href="admin_logout.php" class="logout-link">Logout</a></li>
            </ul>
        </nav>
    </header>

    <main class="container">
        <section class="section">
            <h2 class="section-title">Manage <span>News Articles</span></h2>

            <?php if (!empty($message)): ?>
                <div class="alert-success admin-msg-success"><?php echo $message; ?></div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="alert-error admin-msg-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Add News Form -->
            <div class="card admin-news-add-card">
                <h3 class="admin-form-heading"><i class="fa-solid fa-plus"></i> Post New Article</h3>
                <form action="admin_manage_news.php" method="POST" enctype="multipart/form-data" class="login-form">
                    <div class="form-group admin-news-form-group">
                        <label class="admin-form-label">Title</label>
                        <input type="text" name="title" required placeholder="Enter article title" class="admin-news-input">
                    </div>
                    
                    <div class="form-group admin-news-form-group">
                        <label class="admin-form-label">Content</label>
                        <textarea name="content" rows="6" required class="admin-news-textarea" placeholder="Write full article here..."></textarea>
                    </div>

                    <div class="admin-news-grid">
                        <div class="form-group">
                            <label class="admin-form-label">Featured Image</label>
                            <input type="file" name="image" accept="image/*" class="admin-news-file-input">
                        </div>
                        <div class="form-group admin-news-checkbox-group">
                            <input type="checkbox" name="is_top_news" id="is_top_news" class="admin-news-checkbox">
                            <label for="is_top_news" class="admin-news-checkbox-label">Set as Top News / Breaking News</label>
                        </div>
                    </div>

                    <button type="submit" name="add_news" class="admin-submit-btn">Publish Article</button>
                </form>
            </div>

            <!-- Existing News List -->
            <div class="card table-card">
                <h3 class="admin-table-heading"><i class="fa-solid fa-list"></i> Existing Articles</h3>
                <div class="table-responsive">
                    <table class="standings-table admin-manage-news-table">
                        <thead>
                            <tr class="admin-news-header-row">
                                <th class="admin-news-id-th">ID</th>
                                <th class="admin-news-img-th">Image</th>
                                <th class="admin-news-title-th">Title</th>
                                <th class="admin-news-date-th">Publish Date</th>
                                <th class="admin-news-top-th">Top News (Status)</th>
                                <th class="admin-news-action-th">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($newsResult && $newsResult->num_rows > 0): ?>
                                <?php while($row = $newsResult->fetch_assoc()): ?>
                                    <tr class="admin-news-body-row">
                                        <td class="admin-news-id-td"><?php echo $row['newsID']; ?></td>
                                        <td class="admin-news-img-td">
                                            <?php if (!empty($row['image']) && file_exists("uploads/news/" . $row['image'])): ?>
                                                <img src="uploads/news/<?php echo htmlspecialchars($row['image']); ?>" alt="News Thumbnail" class="admin-news-thumb">
                                            <?php else: ?>
                                                <i class="fa-solid fa-image admin-news-no-img-icon"></i>
                                            <?php endif; ?>
                                        </td>
                                        <td class="admin-news-title-td">
                                            <?php echo htmlspecialchars($row['title']); ?>
                                        </td>
                                        <td class="admin-news-date-td"><?php echo date('M d, Y H:i', strtotime($row['publishDate'])); ?></td>
                                        <td class="admin-news-top-td">
                                            <?php if ($row['isTopNews'] == 1): ?>
                                                <a href="admin_manage_news.php?toggle_top_id=<?php echo $row['newsID']; ?>&status=1" class="motw-toggle-btn" style="color: var(--accent-lime); font-weight: bold;">
                                                    <i class="fa-solid fa-star"></i> Top News
                                                </a>
                                            <?php else: ?>
                                                <a href="admin_manage_news.php?toggle_top_id=<?php echo $row['newsID']; ?>&status=0" class="motw-toggle-btn" style="color: var(--text-muted); opacity: 0.7;">
                                                    + Set Top News
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                        <td class="admin-news-action-td">
                                            <button type="button" class="btn-modal-save" style="padding: 4px 10px; font-size: 0.8rem; margin-right: 5px; cursor: pointer;" 
                                                onclick='openEditNewsModal(<?php echo json_encode($row); ?>)'>
                                                <i class="fa-solid fa-pen-to-square"></i> Edit
                                            </button>
                                            <a href="admin_manage_news.php?delete_id=<?php echo $row['newsID']; ?>" onclick="return confirm('Are you sure you want to delete this article?');" class="admin-delete-link"><i class="fa-solid fa-trash"></i> Delete</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="admin-no-teams">No news articles found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>

    <!-- Edit News Modal Form -->
    <div id="editNewsModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeEditNewsModal()">&times;</span>
            <h3 class="admin-form-heading" style="margin-bottom: 15px;"><i class="fa-solid fa-pen-to-square"></i> Edit Article</h3>
            
            <form action="admin_manage_news.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="news_id" id="edit_news_id">

                <div class="modal-form-group">
                    <label class="admin-form-label">Title</label>
                    <input type="text" name="title" id="edit_title" class="admin-news-input" required>
                </div>

                <div class="modal-form-group">
                    <label class="admin-form-label">Content</label>
                    <textarea name="content" id="edit_content" rows="6" class="admin-news-textarea" required></textarea>
                </div>

                <div class="modal-form-group">
                    <label class="admin-form-label">Change Image (Optional)</label>
                    <input type="file" name="image" accept="image/*" class="admin-news-file-input">
                </div>

                <div class="admin-news-checkbox-group" style="margin-top: 10px;">
                    <input type="checkbox" name="is_top_news" id="edit_is_top_news" value="1" class="admin-news-checkbox">
                    <label for="edit_is_top_news" class="admin-news-checkbox-label">Set as Top News / Breaking News</label>
                </div>

                <!-- Submit & Cancel Buttons -->
                <div class="modal-footer-btns">
                    <button type="button" class="btn-modal-close" onclick="closeEditNewsModal()">Cancel</button>
                    <button type="submit" name="edit_news" class="btn-modal-save">
                        <i class="fa-solid fa-check"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal JavaScript -->
    <script>
        function openEditNewsModal(newsData) {
            document.getElementById('edit_news_id').value = newsData.newsID;
            document.getElementById('edit_title').value = newsData.title;
            document.getElementById('edit_content').value = newsData.content;
            document.getElementById('edit_is_top_news').checked = newsData.isTopNews == 1;
            
            document.getElementById('editNewsModal').style.display = 'block';
        }

        function closeEditNewsModal() {
            document.getElementById('editNewsModal').style.display = 'none';
        }

        window.onclick = function(event) {
            var modal = document.getElementById('editNewsModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>

</body>
</html>