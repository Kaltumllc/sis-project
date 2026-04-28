<?php
include 'config.php';
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Get admin user_id
$admin = mysqli_fetch_assoc(mysqli_query($conn, "SELECT user_id FROM users WHERE username='" . mysqli_real_escape_string($conn, $_SESSION['username']) . "'"));
$admin_id = $admin['user_id'] ?? 1;

// Post announcement
if (isset($_POST['post_announcement'])) {
    $title   = mysqli_real_escape_string($conn, $_POST['title']);
    $content = mysqli_real_escape_string($conn, $_POST['content']);
    mysqli_query($conn, "INSERT INTO announcements (title, content, posted_by) VALUES ('$title', '$content', $admin_id)");
    echo "<script>alert('Announcement posted!'); window.location='manage_announcements.php';</script>";
}

// Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM announcements WHERE announcement_id = $id");
    echo "<script>window.location='manage_announcements.php';</script>";
}

$announcements = mysqli_query($conn, "SELECT * FROM announcements ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Announcements – SIS Admin</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', sans-serif; display: flex; background: #f4f7f6; }

        .sidebar { width: 250px; min-height: 100vh; background: #2c3e50; color: white; padding: 20px; flex-shrink: 0; }
        .sidebar h2 { font-size: 18px; margin-bottom: 10px; }
        .sidebar hr { border-color: rgba(255,255,255,0.15); margin: 10px 0 20px; }
        .sidebar a { color: white; display: block; margin: 15px 0; text-decoration: none; font-size: 16px; }
        .sidebar a:hover { color: #3498db; }

        .main { flex: 1; padding: 40px; }
        h1 { font-size: 26px; color: #2c3e50; margin-bottom: 28px; }

        .form-card { background: white; padding: 28px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.07); margin-bottom: 28px; }
        .form-card h3 { margin-bottom: 18px; color: #2c3e50; }
        label { display: block; font-size: 12px; font-weight: 600; color: #555; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 14px; }
        input[type="text"], textarea {
            width: 100%; padding: 10px 12px; border: 1px solid #e2e8f0;
            border-radius: 6px; font-size: 14px; font-family: inherit;
        }
        textarea { resize: vertical; min-height: 100px; }
        input:focus, textarea:focus { outline: none; border-color: #3498db; }
        .btn-post { background: #16a34a; color: white; border: none; padding: 11px 24px; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 14px; margin-top: 14px; }
        .btn-post:hover { background: #15803d; }

        .ann-card {
            background: white; border-radius: 12px; margin-bottom: 14px;
            box-shadow: 0 1px 5px rgba(0,0,0,0.06); border-left: 4px solid #3498db; overflow: hidden;
        }
        .ann-header { padding: 16px 20px 8px; display: flex; justify-content: space-between; align-items: flex-start; }
        .ann-header h3 { font-size: 16px; color: #1e293b; }
        .ann-body { padding: 0 20px 12px; font-size: 14px; color: #555; line-height: 1.6; }
        .ann-footer { padding: 10px 20px; background: #f8fafc; border-top: 1px solid #f0f0f0; display: flex; justify-content: space-between; font-size: 12px; color: #aaa; }
        .btn-del { color: #dc2626; text-decoration: none; font-size: 13px; }
    </style>
</head>
<body>

<div class="sidebar">
    <h2>SIS Admin</h2>
    <hr>
    <a href="admin_dash.php">🏠 Dashboard</a>
    <a href="manage_students.php">👥 Manage Students</a>
    <a href="manage_courses.php">📚 Course Listings</a>
    <a href="enroll_student.php">📝 Enrollment</a>
    <a href="manage_assignments.php">📋 Assignments</a>
    <a href="manage_announcements.php" style="color:#3498db; font-weight:600;">📢 Announcements</a>
    <br><br>
    <a href="logout.php" style="color:#e74c3c; font-weight:bold;">Logout</a>
</div>

<div class="main">
    <h1>📢 Manage Announcements</h1>

    <div class="form-card">
        <h3>📝 Post New Announcement</h3>
        <form method="POST">
            <label>Title</label>
            <input type="text" name="title" placeholder="e.g. Mid-term Schedule Update" required>
            <label>Content</label>
            <textarea name="content" placeholder="Write your announcement here..." required></textarea>
            <button type="submit" name="post_announcement" class="btn-post">📢 Post Announcement</button>
        </form>
    </div>

    <?php if (mysqli_num_rows($announcements) > 0): ?>
        <?php while ($ann = mysqli_fetch_assoc($announcements)): ?>
        <div class="ann-card">
            <div class="ann-header">
                <h3><?php echo htmlspecialchars($ann['title']); ?></h3>
                <a href="manage_announcements.php?delete=<?php echo $ann['announcement_id']; ?>"
                   onclick="return confirm('Delete this announcement?')" class="btn-del">🗑️ Delete</a>
            </div>
            <div class="ann-body"><?php echo nl2br(htmlspecialchars($ann['content'])); ?></div>
            <div class="ann-footer">
                <span>Posted: <?php echo date('M j, Y g:i A', strtotime($ann['created_at'])); ?></span>
            </div>
        </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p style="color:#aaa; text-align:center; padding:40px;">No announcements yet.</p>
    <?php endif; ?>
</div>

</body>
</html>