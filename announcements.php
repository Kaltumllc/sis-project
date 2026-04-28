<?php
include 'config.php';
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: index.php");
    exit();
}

$announcements = mysqli_query($conn, "
    SELECT a.*, u.username AS posted_by_name
    FROM announcements a
    LEFT JOIN users u ON a.posted_by = u.user_id
    ORDER BY a.created_at DESC
");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Announcements – SIS</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', sans-serif; display: flex; background: #f4f7f6; }

        .sidebar { width: 250px; min-height: 100vh; background: #2c3e50; color: white; padding: 20px; flex-shrink: 0; }
        .sidebar h2 { font-size: 18px; margin-bottom: 10px; }
        .sidebar hr { border-color: rgba(255,255,255,0.15); margin: 10px 0 20px; }
        .sidebar a { color: white; display: block; margin: 15px 0; text-decoration: none; font-size: 16px; }
        .sidebar a:hover { color: #3498db; }

        .main { flex: 1; padding: 40px; max-width: 860px; }
        h1 { font-size: 26px; color: #2c3e50; margin-bottom: 6px; }
        .subtitle { color: #888; font-size: 14px; margin-bottom: 28px; }

        .announcement-card {
            background: white; border-radius: 12px; margin-bottom: 18px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
            border-left: 5px solid #3498db; overflow: hidden;
        }
        .announcement-card.recent { border-left-color: #16a34a; }

        .card-header {
            padding: 18px 24px 10px;
            display: flex; justify-content: space-between; align-items: flex-start; gap: 12px;
        }
        .card-header h3 { font-size: 17px; color: #1e293b; }
        .new-badge {
            background: #dcfce7; color: #16a34a; font-size: 11px;
            font-weight: 700; padding: 3px 10px; border-radius: 20px;
            text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap;
        }

        .card-body { padding: 0 24px 18px; font-size: 14px; color: #4b5563; line-height: 1.7; }

        .card-footer {
            padding: 10px 24px; background: #f8fafc; border-top: 1px solid #f0f0f0;
            display: flex; justify-content: space-between; font-size: 12px; color: #aaa;
        }

        .empty-state { text-align: center; padding: 60px; color: #aaa; background: white; border-radius: 12px; }
        .empty-state .icon { font-size: 48px; margin-bottom: 14px; }
    </style>
</head>
<body>

<div class="sidebar">
    <h2>Student Portal</h2>
    <hr>
    <a href="student_dash.php">🏠 Home</a>
    <a href="my_courses.php">📚 My Classes</a>
    <a href="view_grades.php">🎓 My Grades</a>
    <a href="assignments.php">📝 Assignments</a>
    <a href="announcements.php" style="color:#3498db; font-weight:600;">📢 Announcements</a>
    <a href="collaboration.php">📞 Study Groups</a>
    <br><br>
    <a href="logout.php" style="color:#e74c3c; font-weight:bold;">Logout</a>
</div>

<div class="main">
    <h1>📢 Announcements</h1>
    <p class="subtitle">Latest updates from your institution</p>

    <?php
    $count = mysqli_num_rows($announcements);
    if ($count > 0):
        mysqli_data_seek($announcements, 0);
        $i = 0;
        while ($ann = mysqli_fetch_assoc($announcements)):
            $posted = new DateTime($ann['created_at']);
            $now = new DateTime();
            $diff = $now->diff($posted);
            $is_new = ($diff->days < 3);

            if ($diff->days === 0) {
                $time_ago = "Today at " . $posted->format('g:i A');
            } elseif ($diff->days === 1) {
                $time_ago = "Yesterday";
            } else {
                $time_ago = $posted->format('M j, Y');
            }
    ?>
        <div class="announcement-card <?php echo $is_new ? 'recent' : ''; ?>">
            <div class="card-header">
                <h3><?php echo htmlspecialchars($ann['title']); ?></h3>
                <?php if ($is_new): ?>
                    <span class="new-badge">🟢 New</span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php echo nl2br(htmlspecialchars($ann['content'])); ?>
            </div>
            <div class="card-footer">
                <span>👤 Posted by: <?php echo htmlspecialchars($ann['posted_by_name'] ?? 'Admin'); ?></span>
                <span>🕐 <?php echo $time_ago; ?></span>
            </div>
        </div>
    <?php $i++; endwhile; else: ?>
        <div class="empty-state">
            <div class="icon">📭</div>
            <p>No announcements yet. Check back later!</p>
        </div>
    <?php endif; ?>
</div>

</body>
</html>